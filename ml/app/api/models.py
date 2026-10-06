from __future__ import annotations

import logging
from fastapi import APIRouter, HTTPException, Query

from app.config import settings
from app.ml.manifest import get_model_inventory, format_bytes

log = logging.getLogger("ml_chege_photos.models_api")
router = APIRouter(prefix="/api/v1/models", tags=["models"])


@router.get("/inventory")
def get_inventory():
    """Returns real-time on-disk presence, size, location, and memory state of all models."""
    inv = get_model_inventory()
    total_bytes = sum(item["size_bytes"] for item in inv)

    return {
        "status": "success",
        "inventory": inv,
        "total_disk_usage": format_bytes(total_bytes),
        "total_disk_bytes": total_bytes,
        "active_face_pack": settings.face_model_pack,
        "active_clip_model": settings.clip_model_name,
    }


@router.post("/download")
def download_models(
    group: str = Query("all", description="Model group to download: insightface, yolov8n, clip, or all")
):
    """
    Forces download of missing model files and loads them into memory (RAM / GPU).
    Can be invoked on-demand from the WebApp admin interface.
    """
    from app.ml.loader import load_models
    from app.ml.semantic_search import load_clip_model
    from app.ml.object_detection import download_yolov8_model, load_yolo_model

    results = []

    # 1. InsightFace Pack
    if group in ("all", "insightface"):
        try:
            log.info("Downloading/verifying InsightFace model pack: %s", settings.face_model_pack)
            load_models()
            results.append({"group": "insightface", "status": "success", "message": "InsightFace models ready"})
        except Exception as exc:
            log.error("Failed downloading InsightFace: %s", exc)
            results.append({"group": "insightface", "status": "error", "message": str(exc)})

    # 2. YOLOv8 Objects
    if group in ("all", "yolov8n"):
        try:
            log.info("Downloading/verifying YOLOv8 model")
            download_yolov8_model()
            load_yolo_model()
            results.append({"group": "yolov8n", "status": "success", "message": "YOLOv8 model ready"})
        except Exception as exc:
            log.error("Failed downloading YOLOv8: %s", exc)
            results.append({"group": "yolov8n", "status": "error", "message": str(exc)})

    # 3. CLIP Transformer
    if group in ("all", "clip"):
        try:
            log.info("Downloading/verifying CLIP model: %s", settings.clip_model_name)
            load_clip_model(force=True)
            results.append({"group": "clip", "status": "success", "message": "CLIP model ready"})
        except Exception as exc:
            log.error("Failed downloading CLIP: %s", exc)
            results.append({"group": "clip", "status": "error", "message": str(exc)})

    # 4. NSFW Sensitive Media Detector
    if group in ("all", "nsfw"):
        try:
            from app.ml.nsfw import get_detector
            log.info("Downloading/verifying NSFW model: %s", settings.nsfw_model_name)
            get_detector(settings.nsfw_model_name)
            results.append({"group": "nsfw", "status": "success", "message": f"NSFW model '{settings.nsfw_model_name}' ready"})
        except Exception as exc:
            log.error("Failed downloading NSFW model: %s", exc)
            results.append({"group": "nsfw", "status": "error", "message": str(exc)})

    updated_inv = get_model_inventory()
    total_bytes = sum(item["size_bytes"] for item in updated_inv)

    has_errors = any(r["status"] == "error" for r in results)

    return {
        "status": "partial_error" if has_errors else "success",
        "results": results,
        "inventory": updated_inv,
        "total_disk_usage": format_bytes(total_bytes),
        "total_disk_bytes": total_bytes,
    }


@router.get("/nsfw/config")
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


@router.post("/nsfw/config")
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


@router.post("/nsfw/detect")
def test_nsfw_detection(payload: dict):
    """
    Test NSFW detection on a photo path or photo ID on demand.
    """
    photo_path = payload.get("photo_path")
    model_name = payload.get("model_name")
    threshold = payload.get("threshold")

    if not photo_path:
        raise HTTPException(status_code=400, detail="photo_path is required")

    from app.ml.nsfw import predict_nsfw
    result = predict_nsfw(
        photo_path,
        model_name=model_name,
        threshold=float(threshold) if threshold is not None else None,
    )
    return {
        "status": "success",
        "result": result,
    }
