from __future__ import annotations

import os
import json
import asyncio
import logging
from datetime import datetime, timezone
from typing import Callable, Any

from app.config import settings

log = logging.getLogger("ml_chege_photos.queue")

QUEUE_KEY = "chege:ml:scan:queue"
EVENT_CHANNEL = "chege:events:photo_scanned"


class JobQueue:
    def __init__(self):
        self.queue: asyncio.Queue = asyncio.Queue()
        self.worker_tasks: list[asyncio.Task] = []
        self.is_running: bool = False
        self._current_tasks: dict[int, str] = {}
        self.total_processed: int = 0
        self.total_failed: int = 0
        self.last_error: str | None = None
        self.last_error_time: str | None = None
        self.num_workers: int = int(os.getenv("ML_CONCURRENT_WORKERS", str(settings.ml_concurrent_workers or 4)))
        self.redis_client: Any | None = None
        self.use_redis: bool = False
        self._func_registry: dict[str, Callable] = {}

    def register_func(self, func: Callable) -> None:
        """Register a function name for deserialization from Redis."""
        self._func_registry[func.__name__] = func

    @property
    def _current_task(self) -> str | None:
        """Compatibility property: returns any currently active task name."""
        if self._current_tasks:
            return next(iter(self._current_tasks.values()))
        return None

    async def _init_redis(self) -> None:
        """Attempt to connect to Redis for distributed durable queueing."""
        try:
            import redis.asyncio as aioredis
            redis_url = getattr(settings, "redis_url", None) or os.getenv("REDIS_URL", "redis://redis:6379/0")
            client = aioredis.from_url(redis_url, decode_responses=True)
            await client.ping()
            self.redis_client = client
            self.use_redis = True
            log.info("Connected to Redis broker at %s for durable ML job queueing.", redis_url)
        except Exception as e:
            self.use_redis = False
            self.redis_client = None
            log.warning("Redis broker unavailable (%s). Falling back to in-memory asyncio.Queue.", e)

    async def start(self):
        self.is_running = True
        await self._init_redis()
        self.worker_tasks = [
            asyncio.create_task(self._worker(worker_id=i))
            for i in range(max(1, self.num_workers))
        ]
        broker_type = "Redis broker" if self.use_redis else "in-memory queue"
        log.info(
            "Background ML job queue started with %d concurrent workers (%s).",
            len(self.worker_tasks),
            broker_type,
        )

    async def stop(self):
        self.is_running = False
        for task in self.worker_tasks:
            task.cancel()
        if self.worker_tasks:
            await asyncio.gather(*self.worker_tasks, return_exceptions=True)
        self.worker_tasks.clear()
        self._current_tasks.clear()

        if self.redis_client:
            try:
                await self.redis_client.aclose()
            except Exception:
                pass
            self.redis_client = None

        log.info("Background ML job queue workers stopped.")

    async def add_job(self, func: Callable, *args, **kwargs):
        self.register_func(func)
        func_name = getattr(func, "__name__", "unknown")

        if self.use_redis and self.redis_client:
            try:
                payload = json.dumps({
                    "func": func_name,
                    "args": args,
                    "kwargs": kwargs,
                    "enqueued_at": datetime.now(timezone.utc).isoformat(),
                })
                q_len = await self.redis_client.rpush(QUEUE_KEY, payload)
                log.info(
                    "Job %s persisted to Redis queue '%s'. Queue depth: %d",
                    func_name,
                    QUEUE_KEY,
                    q_len,
                )
                return
            except Exception as e:
                log.warning("Failed to push job to Redis (%s), falling back to in-memory queue.", e)

        # In-memory fallback
        await self.queue.put((func, args, kwargs))
        log.info(
            "Job %s added to in-memory queue. Pending queue size: %d",
            func_name,
            self.queue.qsize(),
        )

    async def _worker(self, worker_id: int):
        while self.is_running:
            func = None
            args: tuple = ()
            kwargs: dict = {}
            from_redis = False

            try:
                # 1. Check Redis first if available
                if self.use_redis and self.redis_client:
                    item = await self.redis_client.blpop([QUEUE_KEY], timeout=2)
                    if item:
                        _, payload_raw = item
                        data = json.loads(payload_raw)
                        func_name = data.get("func")
                        args = tuple(data.get("args", []))
                        kwargs = data.get("kwargs", {})
                        func = self._func_registry.get(func_name)
                        if not func:
                            try:
                                from app.api.faces import process_photo_pipeline_queued
                                if func_name == "process_photo_pipeline_queued":
                                    func = process_photo_pipeline_queued
                            except ImportError:
                                pass
                        if not func:
                            raise ValueError(f"Unknown job function '{func_name}'")
                        from_redis = True

                # 2. If no Redis item, check in-memory queue
                if func is None:
                    try:
                        func, args, kwargs = await asyncio.wait_for(self.queue.get(), timeout=1.0)
                        from_redis = False
                    except asyncio.TimeoutError:
                        continue

                task_name = getattr(func, "__name__", "unknown")
                self._current_tasks[worker_id] = task_name
                log.info(
                    "[Worker %d] Running ML job %s (source: %s)...",
                    worker_id,
                    task_name,
                    "Redis" if from_redis else "in-memory",
                )

                loop = asyncio.get_running_loop()
                await loop.run_in_executor(None, lambda: func(*args, **kwargs))
                self.total_processed += 1
                log.info("[Worker %d] ML job %s finished successfully.", worker_id, task_name)

                # Broadcast completion event on Redis Pub/Sub
                if self.use_redis and self.redis_client:
                    try:
                        event_payload = json.dumps({
                            "func": task_name,
                            "photo_id": kwargs.get("photo_id") or (args[0] if args else None),
                            "status": "completed",
                            "worker_id": worker_id,
                            "completed_at": datetime.now(timezone.utc).isoformat(),
                        })
                        await self.redis_client.publish(EVENT_CHANNEL, event_payload)
                    except Exception as pub_err:
                        log.debug("Failed to publish Redis completion event: %s", pub_err)

            except asyncio.CancelledError:
                break
            except Exception as e:
                self.total_failed += 1
                self.last_error = str(e)
                self.last_error_time = datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M:%S UTC")
                log.error(
                    "[Worker %d] Error running ML job %s: %s",
                    worker_id,
                    getattr(func, "__name__", "unknown") if func else "unknown",
                    e,
                    exc_info=True,
                )
            finally:
                self._current_tasks.pop(worker_id, None)
                if not from_redis and func is not None:
                    self.queue.task_done()


ml_job_queue = JobQueue()
