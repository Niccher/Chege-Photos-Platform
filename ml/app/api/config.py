from __future__ import annotations

import logging
from fastapi import APIRouter

from app.config import settings

log = logging.getLogger("ml_chege_photos.config_api")
router = APIRouter(prefix="/api/v1/config", tags=["config"])


@router.get("/nsfw")
def get_nsfw_config():
    """Returns active NSFW model, threshold, auto-vault rules, and available model catalog."""
    from app.ml import nsfw
    return {
        "status": "success",
        "nsfw_model_name": settings.nsfw_model_name,
        "active_model_loaded": nsfw._active_model_name,
        "nsfw_threshold": settings.nsfw_threshold,
        "auto_vault_nsfw": settings.auto_vault_nsfw,
        "supported_models": [
            {
                "id": "opennsfw2",
                "name": "opennsfw2 (Recommended)",
                "architecture": "ResNet-50",
                "weights_size": "~6 MB",
                "ram_usage": "<50 MB",
                "speed": "Fastest (30-50ms CPU)",
            },
            {
                "id": "falconsai",
                "name": "Falconsai/nsfw_image_detection",
                "architecture": "Vision Transformer (ViT)",
                "weights_size": "~300 MB",
                "ram_usage": "~400 MB",
                "speed": "Accurate (150-250ms CPU)",
            },
            {
                "id": "nudenet",
                "name": "NudeNet",
                "architecture": "YOLO 18 Anatomical Classes",
                "weights_size": "~150 MB",
                "ram_usage": "~250 MB",
                "speed": "Granular (100-200ms CPU)",
            },
        ],
    }


@router.post("/nsfw")
def update_nsfw_config(payload: dict):
    """
    Hot-reloads the active NSFW model and updates sensitivity threshold/auto-vault preferences.
    """
    model = payload.get("nsfw_model_name")
    thresh = payload.get("nsfw_threshold")
    auto_vault = payload.get("auto_vault_nsfw")

    if model:
        clean_model = str(model).strip().lower()
        if clean_model in ["opennsfw2", "falconsai", "nudenet", "vit", "nude"]:
            settings.nsfw_model_name = clean_model
            from app.ml.nsfw import get_detector
            get_detector(clean_model)
    if thresh is not None:
        settings.nsfw_threshold = float(thresh)
    if auto_vault is not None:
        settings.auto_vault_nsfw = bool(auto_vault)

    return {
        "status": "success",
        "nsfw_model_name": settings.nsfw_model_name,
        "nsfw_threshold": settings.nsfw_threshold,
        "auto_vault_nsfw": settings.auto_vault_nsfw,
    }
