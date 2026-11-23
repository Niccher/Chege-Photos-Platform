from __future__ import annotations

import logging
import torch
from PIL import Image

from app.config import settings
from app.services.model_store import ensure_model_directory

log = logging.getLogger("ml_chege_photos.florence")

_florence_model = None
_florence_processor = None

FLORENCE_MINIO_PREFIX = "florence2/microsoft--Florence-2-base"


def _resolve_device() -> tuple[str, torch.dtype]:
    if getattr(settings, "ml_device", "cpu") == "cuda" and torch.cuda.is_available():
        return "cuda", torch.float16
    return "cpu", torch.float32


def load_florence_model(force: bool = False):
    global _florence_model, _florence_processor
    if _florence_model is None or force:
        from transformers import AutoModelForCausalLM, AutoProcessor

        local_source = None
        try:
            local_dir = ensure_model_directory(FLORENCE_MINIO_PREFIX)
            if local_dir is not None:
                local_source = str(local_dir)
        except Exception as minio_err:
            log.warning("MinIO Florence-2 fetch unavailable (%s); falling back to Hugging Face hub.", minio_err)

        source = local_source or settings.florence_model_name
        device, dtype = _resolve_device()
        log.info("Loading Florence-2 model from %s on %s (%s)...", source, device, dtype)
        _florence_processor = AutoProcessor.from_pretrained(source, trust_remote_code=True)
        _florence_model = AutoModelForCausalLM.from_pretrained(
            source,
            torch_dtype=dtype,
            attn_implementation="eager",
            trust_remote_code=True,
        ).to(device)
        _florence_model.eval()
        log.info("Florence-2 loaded successfully.")


def _run_task(image: Image.Image, task_prompt: str) -> str:
    if _florence_model is None:
        load_florence_model()

    device = next(_florence_model.parameters()).device
    dtype = next(_florence_model.parameters()).dtype
    inputs = _florence_processor(text=task_prompt, images=image, return_tensors="pt")
    inputs = {
        k: (v.to(device, dtype=dtype) if v.is_floating_point() else v.to(device))
        for k, v in inputs.items()
    }

    with torch.no_grad():
        generated_ids = _florence_model.generate(
            input_ids=inputs["input_ids"],
            pixel_values=inputs["pixel_values"],
            max_new_tokens=256,
            num_beams=3,
            do_sample=False,
        )

    generated_text = _florence_processor.batch_decode(generated_ids, skip_special_tokens=False)[0]
    parsed = _florence_processor.post_process_generation(
        generated_text,
        task=task_prompt,
        image_size=(image.width, image.height),
    )
    val = parsed.get(task_prompt, "")
    return str(val).strip() if val else ""


def analyze_image(image_path: str) -> dict[str, str]:
    """
    Runs Florence-2 <MORE_DETAILED_CAPTION> and <OCR> on an image.
    Returns {"caption": "...", "ocr_text": "..."}.
    """
    try:
        image = Image.open(image_path).convert("RGB")
        caption = _run_task(image, "<MORE_DETAILED_CAPTION>")
        ocr_text = _run_task(image, "<OCR>")
        return {
            "caption": caption[:2000],
            "ocr_text": ocr_text[:5000],
        }
    except Exception as exc:
        log.error("Florence-2 analysis failed for %s: %s", image_path, exc)
        return {"caption": "", "ocr_text": ""}
