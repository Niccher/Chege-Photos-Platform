from __future__ import annotations

import os
import hmac
import hashlib
import logging
import urllib.request
from datetime import datetime, timezone
from pathlib import Path
from typing import Optional
from urllib.parse import urlparse

from app.config import settings

log = logging.getLogger("ml_chege_photos.storage")


def _build_sigv4_headers(
    endpoint: str,
    bucket: str,
    key: str,
    access_key: str,
    secret_key: str,
    region: str = "us-east-1",
) -> dict[str, str]:
    now = datetime.now(timezone.utc)
    amz_date = now.strftime("%Y%m%dT%H%M%SZ")
    date_stamp = now.strftime("%Y%m%d")

    clean_endpoint = endpoint.rstrip("/")
    parsed = urlparse(clean_endpoint)
    host = parsed.netloc

    clean_key = key.lstrip("/")
    uri = f"/{bucket}/{clean_key}"
    payload_hash = hashlib.sha256(b"").hexdigest()

    signed_headers = "host;x-amz-content-sha256;x-amz-date"
    canonical_headers = f"host:{host}\nx-amz-content-sha256:{payload_hash}\nx-amz-date:{amz_date}\n"
    canonical_request = f"GET\n{uri}\n\n{canonical_headers}\n{signed_headers}\n{payload_hash}"
    canonical_request_hash = hashlib.sha256(canonical_request.encode("utf-8")).hexdigest()

    credential_scope = f"{date_stamp}/{region}/s3/aws4_request"
    string_to_sign = f"AWS4-HMAC-SHA256\n{amz_date}\n{credential_scope}\n{canonical_request_hash}"

    k_secret = ("AWS4" + secret_key).encode("utf-8")
    k_date = hmac.new(k_secret, date_stamp.encode("utf-8"), hashlib.sha256).digest()
    k_region = hmac.new(k_date, region.encode("utf-8"), hashlib.sha256).digest()
    k_service = hmac.new(k_region, b"s3", hashlib.sha256).digest()
    k_signing = hmac.new(k_service, b"aws4_request", hashlib.sha256).digest()

    signature = hmac.new(k_signing, string_to_sign.encode("utf-8"), hashlib.sha256).hexdigest()
    auth_header = (
        f"AWS4-HMAC-SHA256 Credential={access_key}/{credential_scope}, "
        f"SignedHeaders={signed_headers}, Signature={signature}"
    )

    return {
        "x-amz-date": amz_date,
        "x-amz-content-sha256": payload_hash,
        "Authorization": auth_header,
    }


def resolve_photo_path(
    raw_path: str,
    webapp_url: Optional[str] = None,
) -> Path:
    """
    Resolves the physical or cached local Path for a photo.
    If the file does not exist locally (e.g. running on separate host or container),
    attempts to fetch it directly from MinIO / S3 object storage or WebApp streaming endpoint.
    """
    clean_rel = raw_path.strip().lstrip("/").removeprefix("uploads/").lstrip("/")
    uploads_base = Path(settings.uploads_dir)
    local_path = uploads_base / clean_rel

    if local_path.exists() and local_path.stat().st_size > 0:
        return local_path

    local_path.parent.mkdir(parents=True, exist_ok=True)

    candidates: list[tuple[str, dict[str, str]]] = []

    # 1. Direct S3 / MinIO Object Storage
    endpoint = settings.effective_s3_endpoint
    if endpoint:
        endpoint_clean = endpoint.rstrip("/")
        bucket = settings.effective_s3_bucket
        access_key = settings.effective_s3_access_key
        secret_key = settings.effective_s3_secret_key
        region = getattr(settings, "s3_region", "us-east-1")

        s3_url = f"{endpoint_clean}/{bucket}/uploads/{clean_rel}"
        headers = {"User-Agent": "Mozilla/5.0 (compatible; ChegePhotosML/1.0)"}

        if access_key and secret_key:
            try:
                signed = _build_sigv4_headers(
                    endpoint=endpoint_clean,
                    bucket=bucket,
                    key=f"uploads/{clean_rel}",
                    access_key=access_key,
                    secret_key=secret_key,
                    region=region,
                )
                headers.update(signed)
            except Exception as sign_err:
                log.debug("SigV4 header generation skipped: %s", sign_err)

        candidates.append((s3_url, headers))

    # 2. WebApp media streaming fallback
    active_webapp = (
        webapp_url.strip().rstrip("/") if webapp_url else None
    ) or settings.effective_webapp_url

    if active_webapp:
        if not active_webapp.startswith(("http://", "https://")):
            active_webapp = "https://" + active_webapp
        web_headers = {"User-Agent": "Mozilla/5.0 (compatible; ChegePhotosML/1.0)"}
        if "railway" in active_webapp.lower():
            candidates.append((f"http://chege-photos-webapp.railway.internal/uploads/{clean_rel}", web_headers))
        candidates.append((f"{active_webapp}/uploads/{clean_rel}", web_headers))

    download_success = False
    last_err: Optional[Exception] = None

    for url, req_headers in candidates:
        try:
            req = urllib.request.Request(url, headers=req_headers)
            with urllib.request.urlopen(req, timeout=20) as resp:
                if resp.status == 200:
                    data = resp.read()
                    if len(data) > 100:
                        temp_path = local_path.with_suffix(local_path.suffix + f".tmp.{os.getpid()}")
                        with open(temp_path, "wb") as f:
                            f.write(data)
                        temp_path.replace(local_path)
                        download_success = True
                        log.info("Downloaded %s from %s (%d bytes)", clean_rel, url, len(data))
                        break
        except Exception as e:
            last_err = e

    if not download_success and not (local_path.exists() and local_path.stat().st_size > 0):
        log.warning(
            "Could not resolve photo %s across candidates %s: %s",
            clean_rel,
            [c[0] for c in candidates],
            last_err,
        )

    return local_path
