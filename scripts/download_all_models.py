#!/usr/bin/env python3
"""
Pre-download all machine learning model weights during Docker build.
Ensures zero-latency container boot and offline runtime readiness.
"""
import os
import gc
import logging
import urllib.request
from pathlib import Path

logging.basicConfig(level=logging.INFO, format="%(asctime)s [%(levelname)s] %(message)s")
log = logging.getLogger("download_all_models")

MODELS_DIR = Path("/app/models")
MODELS_DIR.mkdir(parents=True, exist_ok=True)

def download_insightface():
    log.info("── 1/5: Downloading InsightFace model pack (buffalo_l) ──")
    try:
        from insightface.app import FaceAnalysis
        pack_dir = MODELS_DIR / "insightface_models"
        app = FaceAnalysis(name="buffalo_l", root=str(pack_dir), providers=["CPUExecutionProvider"])
        app.prepare(ctx_id=-1, det_thresh=0.5)
        log.info("✓ InsightFace models downloaded and prepared at %s", pack_dir)
    except Exception as e:
        log.error("✗ Failed to download InsightFace models: %s", e)
        raise

def download_yolov8():
    log.info("── 2/5: Downloading YOLOv8 Nano object detection model ──")
    yolo_url = "https://huggingface.co/Kalray/yolov8/resolve/main/yolov8n.onnx"
    yolo_dest = MODELS_DIR / "yolov8n.onnx"
    if yolo_dest.exists() and yolo_dest.stat().st_size > 1000000:
        log.info("✓ YOLOv8 model already exists at %s", yolo_dest)
        return
    try:
        urllib.request.urlretrieve(yolo_url, str(yolo_dest))
        log.info("✓ YOLOv8 model downloaded successfully (%d bytes)", yolo_dest.stat().st_size)
    except Exception as e:
        log.error("✗ Failed to download YOLOv8 model: %s", e)
        raise

def download_clip():
    log.info("── 3/5: Downloading CLIP Transformer (openai/clip-vit-base-patch32) ──")
    try:
        from transformers import CLIPModel, CLIPProcessor
        clip_name = "openai/clip-vit-base-patch32"
        CLIPProcessor.from_pretrained(clip_name)
        CLIPModel.from_pretrained(clip_name)
        log.info("✓ CLIP processor and model downloaded into Hugging Face cache")
    except Exception as e:
        log.error("✗ Failed to download CLIP model: %s", e)
        raise

def download_opennsfw():
    log.info("── 4/5: Downloading OpenNSFW ResNet-50 ONNX model ──")
    nsfw_dest = MODELS_DIR / "open_nsfw.onnx"
    if nsfw_dest.exists() and nsfw_dest.stat().st_size > 1000000:
        log.info("✓ OpenNSFW model already exists at %s", nsfw_dest)
        return

    # Attempt copy from opennsfw2 package first
    try:
        import opennsfw2 as _pkg
        pkg_dir = Path(_pkg.__file__).parent
        data_dir = pkg_dir / "data"
        if data_dir.exists():
            for f in data_dir.glob("*.onnx"):
                if f.is_file() and f.stat().st_size > 1000000:
                    import shutil
                    shutil.copy2(f, nsfw_dest)
                    log.info("✓ Copied opennsfw2 ONNX model from package data: %s -> %s", f, nsfw_dest)
                    return
    except Exception as e:
        log.debug("Package copy skipped: %s", e)

    nsfw_url = "https://raw.githubusercontent.com/bhky/opennsfw2/master/opennsfw2/data/open_nsfw-90be3a4a.onnx"
    try:
        urllib.request.urlretrieve(nsfw_url, str(nsfw_dest))
        log.info("✓ OpenNSFW model downloaded successfully (%d bytes)", nsfw_dest.stat().st_size)
    except Exception as e:
        log.error("✗ Failed to download OpenNSFW model: %s", e)
        raise

def download_falconsai_vit():
    log.info("── 5/5: Downloading Falconsai ViT NSFW model ──")
    try:
        from transformers import AutoImageProcessor, AutoModelForImageClassification
        vit_name = "Falconsai/nsfw_image_detection"
        AutoImageProcessor.from_pretrained(vit_name)
        AutoModelForImageClassification.from_pretrained(vit_name)
        log.info("✓ Falconsai ViT processor and model weights downloaded into Hugging Face cache")
    except Exception as e:
        log.warning("Falconsai ViT pre-download notice (optional alternative): %s", e)

def main():
    log.info("Starting build-time pre-download of all ML models...")
    download_insightface()
    gc.collect()

    download_yolov8()
    gc.collect()

    download_clip()
    gc.collect()

    download_opennsfw()
    gc.collect()

    download_falconsai_vit()
    gc.collect()

    log.info("All ML models successfully baked into container!")

if __name__ == "__main__":
    main()
