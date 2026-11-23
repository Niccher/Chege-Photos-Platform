#!/usr/bin/env python3
"""
MinIO-Primary AI Model Seeder for Chege Photos Platform.
Downloads all AI model artifacts into ephemeral /tmp staging and uploads them directly
to minio://chege-models/ with zero permanent local volume storage:
  - insightface/buffalo_l/ (det_10g.onnx, w600k_r50.onnx, etc.)
  - yolo/yolov8n.onnx
  - nsfw/open_nsfw.onnx
  - siglip/google--siglip-base-patch16-224/
  - florence2/microsoft--Florence-2-base/
  - whisper/large-v3-turbo/ (CTranslate2)
"""
from __future__ import annotations

import gc
import logging
import os
import shutil
import sys
import tempfile
import time
import urllib.request
import zipfile
from pathlib import Path

logging.basicConfig(level=logging.INFO, format="%(asctime)s [%(levelname)s] %(message)s")
log = logging.getLogger("sync_models_to_minio")

USER_AGENT = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"

S3_ENDPOINT = os.getenv("S3_ENDPOINT", "http://minio:9000").rstrip("/")
S3_MODELS_BUCKET = os.getenv("S3_MODELS_BUCKET", "chege-models")
S3_ACCESS_KEY = os.getenv("S3_ACCESS_KEY") or os.getenv("MINIO_ROOT_USER") or "minio_admin"
S3_SECRET_KEY = os.getenv("S3_SECRET_KEY") or os.getenv("MINIO_ROOT_PASSWORD") or "minio_password123"
S3_REGION = os.getenv("S3_REGION", "us-east-1")


def _get_s3():
    import boto3
    from botocore.config import Config

    return boto3.client(
        "s3",
        endpoint_url=S3_ENDPOINT,
        aws_access_key_id=S3_ACCESS_KEY,
        aws_secret_access_key=S3_SECRET_KEY,
        region_name=S3_REGION,
        config=Config(signature_version="s3v4", s3={"addressing_style": "path"}),
    )


def ensure_bucket(s3) -> None:
    try:
        s3.head_bucket(Bucket=S3_MODELS_BUCKET)
    except Exception:
        s3.create_bucket(Bucket=S3_MODELS_BUCKET)
        log.info("Created MinIO bucket: %s", S3_MODELS_BUCKET)


def minio_has_key(s3, key: str) -> bool:
    try:
        resp = s3.head_object(Bucket=S3_MODELS_BUCKET, Key=key.lstrip("/"))
        return int(resp.get("ContentLength", 0)) > 0
    except Exception:
        return False


def minio_has_prefix(s3, prefix: str) -> bool:
    try:
        resp = s3.list_objects_v2(Bucket=S3_MODELS_BUCKET, Prefix=prefix.strip("/") + "/", MaxKeys=1)
        return resp.get("KeyCount", 0) > 0
    except Exception:
        return False


def upload_file(s3, local_path: Path, key: str) -> None:
    clean_key = key.lstrip("/")
    s3.upload_file(str(local_path), S3_MODELS_BUCKET, clean_key)
    log.info("✓ Uploaded -> minio://%s/%s (%d MB)", S3_MODELS_BUCKET, clean_key, local_path.stat().st_size // 1048576)


def upload_dir(s3, local_dir: Path, prefix: str) -> None:
    clean_prefix = prefix.strip("/")
    count = 0
    for f in local_dir.rglob("*"):
        if f.is_file():
            rel = f.relative_to(local_dir).as_posix()
            key = f"{clean_prefix}/{rel}"
            if not minio_has_key(s3, key):
                s3.upload_file(str(f), S3_MODELS_BUCKET, key)
                count += 1
    log.info("✓ Synced directory -> minio://%s/%s/ (%d files uploaded)", S3_MODELS_BUCKET, clean_prefix, count)


def download_file_stream(url: str, dest: Path, description: str, max_retries: int = 3) -> bool:
    dest.parent.mkdir(parents=True, exist_ok=True)
    temp_dest = dest.with_suffix(".tmp")
    for attempt in range(1, max_retries + 1):
        try:
            log.info("Downloading %s (Attempt %d/%d) ...", description, attempt, max_retries)
            req = urllib.request.Request(url, headers={"User-Agent": USER_AGENT})
            with urllib.request.urlopen(req, timeout=180) as resp:
                with open(temp_dest, "wb") as f:
                    while True:
                        chunk = resp.read(1024 * 1024)
                        if not chunk:
                            break
                        f.write(chunk)
            temp_dest.rename(dest)
            return True
        except Exception as exc:
            log.warning("Attempt %d failed for %s: %s", attempt, description, exc)
            temp_dest.unlink(missing_ok=True)
            if attempt < max_retries:
                time.sleep(3)
    return False


def sync_insightface(s3, stage: Path) -> None:
    log.info("── 1/6: InsightFace buffalo_l -> minio://%s/insightface/buffalo_l/ ──", S3_MODELS_BUCKET)
    if minio_has_key(s3, "insightface/buffalo_l/det_10g.onnx") and minio_has_key(s3, "insightface/buffalo_l/w600k_r50.onnx"):
        log.info("✓ InsightFace buffalo_l already stored in MinIO, skipping.")
        return

    zip_path = stage / "buffalo_l.zip"
    extract_dir = stage / "buffalo_l"
    url = "https://github.com/deepinsight/insightface/releases/download/v0.7/buffalo_l.zip"
    if download_file_stream(url, zip_path, "InsightFace buffalo_l.zip"):
        extract_dir.mkdir(parents=True, exist_ok=True)
        with zipfile.ZipFile(zip_path, "r") as z:
            z.extractall(extract_dir)
        upload_dir(s3, extract_dir, "insightface/buffalo_l")
        shutil.rmtree(extract_dir, ignore_errors=True)
        zip_path.unlink(missing_ok=True)


def sync_yolov8(s3, stage: Path) -> None:
    log.info("── 2/6: YOLOv8 Nano -> minio://%s/yolo/yolov8n.onnx ──", S3_MODELS_BUCKET)
    if minio_has_key(s3, "yolo/yolov8n.onnx"):
        log.info("✓ YOLOv8n already stored in MinIO, skipping.")
        return
    dest = stage / "yolov8n.onnx"
    if download_file_stream("https://huggingface.co/Kalray/yolov8/resolve/main/yolov8n.onnx", dest, "YOLOv8n ONNX"):
        upload_file(s3, dest, "yolo/yolov8n.onnx")
        dest.unlink(missing_ok=True)


def sync_opennsfw(s3, stage: Path) -> None:
    log.info("── 3/6: OpenNSFW -> minio://%s/nsfw/open_nsfw.onnx ──", S3_MODELS_BUCKET)
    if minio_has_key(s3, "nsfw/open_nsfw.onnx"):
        log.info("✓ OpenNSFW already stored in MinIO, skipping.")
        return
    dest = stage / "open_nsfw.onnx"
    urls = [
        "https://github.com/facefusion/facefusion-assets/releases/download/models-3.0.0/open_nsfw.onnx",
        "https://huggingface.co/ninjawick/webui-faceswap-unlocked/resolve/main/open_nsfw.onnx",
    ]
    for url in urls:
        if download_file_stream(url, dest, "OpenNSFW ONNX"):
            upload_file(s3, dest, "nsfw/open_nsfw.onnx")
            dest.unlink(missing_ok=True)
            return


def sync_hf_repo(s3, stage: Path, repo_id: str, minio_prefix: str, label: str) -> None:
    log.info("── %s -> minio://%s/%s/ ──", label, S3_MODELS_BUCKET, minio_prefix)
    if minio_has_prefix(s3, minio_prefix):
        log.info("✓ %s already stored in MinIO, skipping.", label)
        return
    try:
        from huggingface_hub import snapshot_download

        local_dir = stage / minio_prefix.replace("/", "_")
        snapshot_download(
            repo_id=repo_id,
            local_dir=str(local_dir),
            local_dir_use_symlinks=False,
        )
        upload_dir(s3, local_dir, minio_prefix)
        shutil.rmtree(local_dir, ignore_errors=True)
    except Exception as exc:
        log.warning("✗ Failed syncing %s (%s): %s", label, repo_id, exc)


def verify_minio_models(s3) -> bool:
    checks = {
        "InsightFace (det_10g.onnx)": minio_has_key(s3, "insightface/buffalo_l/det_10g.onnx"),
        "InsightFace (w600k_r50.onnx)": minio_has_key(s3, "insightface/buffalo_l/w600k_r50.onnx"),
        "YOLOv8n (yolov8n.onnx)": minio_has_key(s3, "yolo/yolov8n.onnx"),
        "OpenNSFW (open_nsfw.onnx)": minio_has_key(s3, "nsfw/open_nsfw.onnx"),
        "SigLIP (google/siglip-base-patch16-224)": minio_has_prefix(s3, "siglip/google--siglip-base-patch16-224"),
        "Florence-2 (microsoft/Florence-2-base)": minio_has_prefix(s3, "florence2/microsoft--Florence-2-base"),
        "Whisper Large-v3-Turbo (CTranslate2)": minio_has_prefix(s3, "whisper/large-v3-turbo"),
    }
    all_ok = True
    for name, ok in checks.items():
        if ok:
            log.info("✓ %s: Present in minio://%s", name, S3_MODELS_BUCKET)
        else:
            log.warning("✗ %s: Missing in minio://%s", name, S3_MODELS_BUCKET)
            all_ok = False
    return all_ok


def main() -> None:
    s3 = _get_s3()
    ensure_bucket(s3)

    if "--verify" in sys.argv or "--verify-only" in sys.argv:
        sys.exit(0 if verify_minio_models(s3) else 1)

    with tempfile.TemporaryDirectory(prefix="chege_model_sync_") as tmp:
        stage = Path(tmp)
        for fn in (
            lambda: sync_insightface(s3, stage),
            lambda: sync_yolov8(s3, stage),
            lambda: sync_opennsfw(s3, stage),
            lambda: sync_hf_repo(s3, stage, "google/siglip-base-patch16-224", "siglip/google--siglip-base-patch16-224", "4/6: SigLIP (768-d)"),
            lambda: sync_hf_repo(s3, stage, "microsoft/Florence-2-base", "florence2/microsoft--Florence-2-base", "5/6: Microsoft Florence-2"),
            lambda: sync_hf_repo(s3, stage, "mobiuslabsgmbh/faster-whisper-large-v3-turbo", "whisper/large-v3-turbo", "6/6: Whisper Large-v3-Turbo"),
        ):
            try:
                fn()
            except Exception as exc:
                log.warning("Model sync step warning: %s", exc)
            gc.collect()

    verify_minio_models(s3)
    log.info("MinIO model registry sync finished (zero local model persistence).")


if __name__ == "__main__":
    main()
