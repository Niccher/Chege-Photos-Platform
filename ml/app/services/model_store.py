from __future__ import annotations

import logging
import os
import tempfile
from pathlib import Path
from typing import Optional

from app.config import settings

log = logging.getLogger("ml_chege_photos.model_store")

MODELS_CACHE_ROOT = Path(os.getenv("ML_MODELS_CACHE_DIR", str(Path(tempfile.gettempdir()) / "chege_models")))


def _get_s3_client():
    import boto3
    from botocore.config import Config

    endpoint = settings.effective_s3_endpoint or "http://minio:9000"
    access_key = settings.effective_s3_access_key or "minio_admin"
    secret_key = settings.effective_s3_secret_key or "minio_password123"
    region = getattr(settings, "s3_region", "us-east-1")

    return boto3.client(
        "s3",
        endpoint_url=endpoint,
        aws_access_key_id=access_key,
        aws_secret_access_key=secret_key,
        region_name=region,
        config=Config(
            signature_version="s3v4",
            s3={"addressing_style": "path"},
            retries={"max_attempts": 3, "mode": "standard"},
        ),
    )


def get_models_bucket() -> str:
    return getattr(settings, "effective_s3_models_bucket", None) or os.getenv("S3_MODELS_BUCKET", "chege-models")


def ensure_bucket_exists(bucket: Optional[str] = None) -> None:
    target_bucket = bucket or get_models_bucket()
    s3 = _get_s3_client()
    try:
        s3.head_bucket(Bucket=target_bucket)
    except Exception:
        try:
            s3.create_bucket(Bucket=target_bucket)
            log.info("Created MinIO models bucket '%s'.", target_bucket)
        except Exception as exc:
            log.warning("Could not create bucket '%s': %s", target_bucket, exc)


def object_exists_in_minio(key: str, bucket: Optional[str] = None) -> bool:
    target_bucket = bucket or get_models_bucket()
    s3 = _get_s3_client()
    try:
        resp = s3.head_object(Bucket=target_bucket, Key=key.lstrip("/"))
        return int(resp.get("ContentLength", 0)) > 0
    except Exception:
        return False


def prefix_exists_in_minio(prefix: str, bucket: Optional[str] = None) -> bool:
    target_bucket = bucket or get_models_bucket()
    s3 = _get_s3_client()
    try:
        resp = s3.list_objects_v2(Bucket=target_bucket, Prefix=prefix.lstrip("/"), MaxKeys=1)
        return resp.get("KeyCount", 0) > 0
    except Exception:
        return False


def upload_file_to_minio(local_path: Path, key: str, bucket: Optional[str] = None) -> bool:
    target_bucket = bucket or get_models_bucket()
    clean_key = key.lstrip("/")
    if not local_path.exists() or local_path.stat().st_size == 0:
        return False
    s3 = _get_s3_client()
    ensure_bucket_exists(target_bucket)
    s3.upload_file(str(local_path), target_bucket, clean_key)
    log.info("Uploaded model artifact -> minio://%s/%s (%d MB)", target_bucket, clean_key, local_path.stat().st_size // 1048576)
    return True


def upload_directory_to_minio(local_dir: Path, prefix: str, bucket: Optional[str] = None) -> int:
    target_bucket = bucket or get_models_bucket()
    clean_prefix = prefix.strip("/")
    ensure_bucket_exists(target_bucket)
    s3 = _get_s3_client()
    uploaded = 0
    for file_path in local_dir.rglob("*"):
        if file_path.is_file():
            rel = file_path.relative_to(local_dir).as_posix()
            key = f"{clean_prefix}/{rel}" if clean_prefix else rel
            if object_exists_in_minio(key, target_bucket):
                continue
            s3.upload_file(str(file_path), target_bucket, key)
            uploaded += 1
    log.info("Synced directory %s -> minio://%s/%s (%d new files)", local_dir, target_bucket, clean_prefix, uploaded)
    return uploaded


def ensure_model_file(minio_key: str, local_rel_path: Optional[str] = None) -> Path:
    """
    Ensures a single model file from minio://chege-models/<minio_key> is cached in
    ephemeral /tmp/chege_models and returns its local Path.
    """
    clean_key = minio_key.lstrip("/")
    dest = MODELS_CACHE_ROOT / (local_rel_path or clean_key)
    if dest.exists() and dest.stat().st_size > 1000:
        return dest

    dest.parent.mkdir(parents=True, exist_ok=True)
    target_bucket = get_models_bucket()
    s3 = _get_s3_client()
    tmp = dest.with_suffix(dest.suffix + f".tmp.{os.getpid()}")
    try:
        log.info("Pulling model artifact minio://%s/%s -> %s", target_bucket, clean_key, dest)
        s3.download_file(target_bucket, clean_key, str(tmp))
        tmp.replace(dest)
        return dest
    except Exception as exc:
        if tmp.exists():
            tmp.unlink(missing_ok=True)
        log.warning("Failed to pull minio://%s/%s: %s", target_bucket, clean_key, exc)
        raise


def ensure_model_directory(minio_prefix: str, local_rel_dir: Optional[str] = None) -> Optional[Path]:
    """
    Pulls all objects under minio://chege-models/<minio_prefix>/ into ephemeral /tmp/chege_models.
    Returns the local Path if objects exist in MinIO, or None if the prefix is not yet seeded.
    """
    clean_prefix = minio_prefix.strip("/") + "/"
    dest_dir = MODELS_CACHE_ROOT / (local_rel_dir or minio_prefix.strip("/"))
    marker = dest_dir / ".minio_synced"
    if marker.exists() and any(dest_dir.iterdir()):
        return dest_dir

    target_bucket = get_models_bucket()
    s3 = _get_s3_client()
    paginator = s3.get_paginator("list_objects_v2")
    found = 0
    for page in paginator.paginate(Bucket=target_bucket, Prefix=clean_prefix):
        for obj in page.get("Contents", []):
            key = obj["Key"]
            rel = key[len(clean_prefix):]
            if not rel or rel.endswith("/"):
                continue
            target_file = dest_dir / rel
            if target_file.exists() and target_file.stat().st_size == obj.get("Size", -1):
                found += 1
                continue
            target_file.parent.mkdir(parents=True, exist_ok=True)
            tmp = target_file.with_suffix(target_file.suffix + f".tmp.{os.getpid()}")
            s3.download_file(target_bucket, key, str(tmp))
            tmp.replace(target_file)
            found += 1

    if found > 0:
        dest_dir.mkdir(parents=True, exist_ok=True)
        marker.write_text("ok", encoding="utf-8")
        log.info("Hydrated %d model files from minio://%s/%s -> %s", found, target_bucket, clean_prefix, dest_dir)
        return dest_dir

    return None
