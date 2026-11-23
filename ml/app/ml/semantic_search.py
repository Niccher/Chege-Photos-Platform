from __future__ import annotations

import logging
import torch
from PIL import Image

from app.config import settings
from app.services.model_store import ensure_model_directory

log = logging.getLogger("ml_chege_photos.semantic_search")

_siglip_model = None
_siglip_processor = None
_loaded_model_name = None

SIGLIP_MINIO_PREFIX = "siglip/google--siglip-base-patch16-224"


def _resolve_device() -> str:
    if getattr(settings, "ml_device", "cpu") == "cuda" and torch.cuda.is_available():
        return "cuda"
    return "cpu"


def load_siglip_model(force: bool = False):
    global _siglip_model, _siglip_processor, _loaded_model_name
    target_name = settings.siglip_model_name
    if _siglip_model is None or force or _loaded_model_name != target_name:
        try:
            from transformers import AutoModel, AutoProcessor

            local_source = None
            try:
                local_dir = ensure_model_directory(SIGLIP_MINIO_PREFIX)
                if local_dir is not None:
                    local_source = str(local_dir)
            except Exception as minio_err:
                log.warning("MinIO SigLIP fetch unavailable (%s); falling back to Hugging Face hub.", minio_err)

            source = local_source or target_name
            device = _resolve_device()
            log.info("Loading SigLIP model (%s) from %s on %s ...", target_name, source, device)
            _siglip_processor = AutoProcessor.from_pretrained(source)
            _siglip_model = AutoModel.from_pretrained(source).to(device)
            _siglip_model.eval()
            _loaded_model_name = target_name
            log.info("SigLIP 768-d model loaded successfully on %s", device)
        except Exception as exc:
            log.error("Failed to load SigLIP model: %s", exc)
            raise


# Keep compatibility function name for internal callers
load_clip_model = load_siglip_model


def get_siglip_model_and_processor():
    if _siglip_model is None:
        load_siglip_model()
    return _siglip_model, _siglip_processor


def encode_text(text: str) -> list[float]:
    model, processor = get_siglip_model_and_processor()
    device = next(model.parameters()).device
    # SigLIP requires padding="max_length" and truncation=True to match pre-training
    inputs = processor(text=[text], return_tensors="pt", padding="max_length", truncation=True)
    inputs = {k: v.to(device) for k, v in inputs.items()}
    with torch.no_grad():
        features = model.get_text_features(**inputs)
        if hasattr(features, "pooler_output"):
            features = features.pooler_output
    features = features / features.norm(dim=-1, keepdim=True)
    return features[0].cpu().numpy().tolist()


def encode_image(image_path: str) -> list[float]:
    model, processor = get_siglip_model_and_processor()
    device = next(model.parameters()).device
    try:
        image = Image.open(image_path).convert("RGB")
        inputs = processor(images=image, return_tensors="pt")
        inputs = {k: v.to(device) for k, v in inputs.items()}
        with torch.no_grad():
            features = model.get_image_features(**inputs)
            if hasattr(features, "pooler_output"):
                features = features.pooler_output
        features = features / features.norm(dim=-1, keepdim=True)
        return features[0].cpu().numpy().tolist()
    except Exception as exc:
        log.error("Failed to generate SigLIP 768-d embedding for image %s: %s", image_path, exc)
        raise
