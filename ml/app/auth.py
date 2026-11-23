from __future__ import annotations

import secrets
from fastapi import Depends, HTTPException, status
from fastapi.security.api_key import APIKeyHeader

from app.config import settings

API_KEY_NAME = "X-API-KEY"
api_key_header = APIKeyHeader(name=API_KEY_NAME, auto_error=True)


async def get_api_key(api_key_header_value: str = Depends(api_key_header)) -> str:
    """Verifies X-API-KEY header with constant-time comparison."""
    if not secrets.compare_digest(str(api_key_header_value), str(settings.ml_api_key)):
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Forbidden: Invalid or missing X-API-KEY credential header",
        )
    return api_key_header_value
