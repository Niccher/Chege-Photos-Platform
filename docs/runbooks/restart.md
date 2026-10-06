# Runbook: Worker Restarts, Qdrant Index Recovery & OOM Triage

Operational procedures for recovering the ML inference worker, diagnosing Out-Of-Memory terminations, and restarting vector database instances.

---

## 1. Graceful Container Restart

When applying environment variable changes or updating model configurations:

```bash
# Gracefully stop the ML container
docker compose stop ml-service

# Start the container
docker compose start ml-service

# Verify health status
curl -I http://localhost:8000/health
```

---

## 2. Diagnosing Exit Code 137 (Linux OOM Killer)

Deep learning models (InsightFace Buffalo-L, YOLOv8, OpenAI CLIP) consume significant RAM during initialization and batch processing:

* **Symptom**: The container suddenly exits without Python tracebacks, and `docker inspect` reports `ExitCode: 137`.
* **Root Cause**: Host kernel invoked the OOM killer (`SIGKILL 9`) because RAM exceeded Docker memory limits.

### Remediation:
1. Verify memory limits in `docker-compose.yml`:
   ```yaml
   deploy:
     resources:
       limits:
         memory: 4096M
       reservations:
         memory: 2048M
   ```
2. Throttle worker concurrency in `.env`:
   ```env
   ML_CONCURRENT_WORKERS=2
   ```
3. Restart the container:
   ```bash
   docker compose up -d ml-service
   ```

---

## 3. Qdrant Vector Collection Recovery

If Qdrant fails to start or reports corrupted WAL (Write-Ahead Log) segments:

```bash
# Check Qdrant logs
docker compose logs --tail=100 qdrant

# Test collection accessibility via HTTP
curl http://localhost:6333/collections

# If index rebuild is necessary, invoke the background rescan endpoint:
curl -X POST http://localhost:8000/api/v1/faces/rescan-all \
  -H "X-API-KEY: your_secure_key"
```
