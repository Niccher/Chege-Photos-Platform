from __future__ import annotations

import logging
import os
from typing import Any

from app.config import settings
from app.services.model_store import ensure_model_directory

log = logging.getLogger("ml_chege_photos.transcription")

_whisper_model = None

WHISPER_MINIO_PREFIX = "whisper/large-v3-turbo"


def _resolve_whisper_runtime() -> tuple[str, str]:
    device = os.getenv("WHISPER_DEVICE", getattr(settings, "whisper_device", "cpu")).strip().lower()
    compute_type = os.getenv("WHISPER_COMPUTE_TYPE", getattr(settings, "whisper_compute_type", "int8")).strip().lower()

    if device == "cuda":
        try:
            import torch
            if not torch.cuda.is_available():
                log.warning("WHISPER_DEVICE=cuda requested but CUDA unavailable; falling back to CPU (int8).")
                return "cpu", "int8"
        except Exception:
            return "cpu", "int8"

    return device, compute_type


def load_whisper_model(force: bool = False):
    global _whisper_model
    if _whisper_model is None or force:
        from faster_whisper import WhisperModel

        local_source = None
        try:
            local_dir = ensure_model_directory(WHISPER_MINIO_PREFIX)
            if local_dir is not None:
                local_source = str(local_dir)
        except Exception as minio_err:
            log.warning("MinIO Whisper fetch unavailable (%s); falling back to Hugging Face hub.", minio_err)

        source = local_source or "mobiuslabsgmbh/faster-whisper-large-v3-turbo"
        device, compute_type = _resolve_whisper_runtime()
        log.info("Loading Whisper Large-v3-Turbo from %s (device=%s, compute_type=%s)...", source, device, compute_type)
        _whisper_model = WhisperModel(source, device=device, compute_type=compute_type)
        log.info("Whisper Large-v3-Turbo loaded successfully.")


def transcribe_media(media_path: str) -> dict[str, Any]:
    """
    Transcribes spoken audio from video/audio media using Whisper Large-v3-Turbo.
    Requires ffmpeg installed in the container.
    Returns {"transcript": str, "segments": list[dict], "language": str}.
    """
    try:
        if _whisper_model is None:
            load_whisper_model()

        segments_iter, info = _whisper_model.transcribe(
            media_path,
            beam_size=5,
            vad_filter=True,
            vad_parameters={"min_silence_duration_ms": 500},
        )

        segments: list[dict[str, Any]] = []
        full_parts: list[str] = []

        for seg in segments_iter:
            text = (seg.text or "").strip()
            if not text:
                continue
            segments.append({
                "start": round(float(seg.start), 2),
                "end": round(float(seg.end), 2),
                "text": text,
            })
            full_parts.append(text)

        return {
            "transcript": " ".join(full_parts).strip(),
            "segments": segments,
            "language": getattr(info, "language", "unknown") or "unknown",
        }
    except Exception as exc:
        log.error("Whisper Large-v3-Turbo transcription failed for %s: %s", media_path, exc)
        return {"transcript": "", "segments": [], "language": "unknown"}
