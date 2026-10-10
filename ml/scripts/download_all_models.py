#!/usr/bin/env python3
"""
Robust build-time model weight downloader for Docker builds.
Pre-downloads InsightFace (buffalo_l), YOLOv8 Nano, CLIP, and OpenNSFW
with clean progress logging, 1MB streaming chunks, and retry resilience.
"""
import os
import gc
import sys
import time
import zipfile
import logging
import urllib.request
from pathlib import Path

logging.basicConfig(level=logging.INFO, format="%(asctime)s [%(levelname)s] %(message)s")
log = logging.getLogger("download_all_models")

MODELS_DIR = Path(os.environ.get("MODELS_DIR", "/app/models"))
try:
    MODELS_DIR.mkdir(parents=True, exist_ok=True)
except Exception:
    MODELS_DIR = Path(__file__).resolve().parent.parent / "models"
    MODELS_DIR.mkdir(parents=True, exist_ok=True)

USER_AGENT = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"

def download_file_stream(url: str, dest: Path, description: str, max_retries: int = 3, force: bool = False) -> bool:
    """Download file in 1MB chunks with User-Agent header, speed tracking, and retry logic."""
    if not force and dest.exists() and dest.stat().st_size > 100000:
        log.info("✓ %s already present (%d MB), skipping.", description, dest.stat().st_size // 1048576)
        return True

    dest.parent.mkdir(parents=True, exist_ok=True)
    temp_dest = dest.with_suffix(".tmp")

    for attempt in range(1, max_retries + 1):
        try:
            log.info("Downloading %s (Attempt %d/%d) from %s ...", description, attempt, max_retries, url)
            start_time = time.time()
            req = urllib.request.Request(url, headers={"User-Agent": USER_AGENT})
            with urllib.request.urlopen(req, timeout=120) as resp:
                total_bytes = int(resp.headers.get("Content-Length", 0))
                downloaded = 0
                last_logged_pct = 0
                with open(temp_dest, "wb") as f:
                    while True:
                        chunk = resp.read(1024 * 1024)  # 1 MB chunk
                        if not chunk:
                            break
                        f.write(chunk)
                        downloaded += len(chunk)
                        if total_bytes > 0:
                            pct = int((downloaded / total_bytes) * 100)
                            if pct >= last_logged_pct + 25:
                                elapsed = max(0.1, time.time() - start_time)
                                speed_mb = (downloaded / 1048576) / elapsed
                                log.info("  -> %s progress: %d%% (%d / %d MB @ %.1f MB/s)", description, pct, downloaded // 1048576, total_bytes // 1048576, speed_mb)
                                last_logged_pct = pct

            temp_dest.rename(dest)
            elapsed_total = max(0.1, time.time() - start_time)
            avg_speed = (dest.stat().st_size / 1048576) / elapsed_total
            log.info("✓ %s downloaded successfully (%d MB @ %.1f MB/s in %.1fs).", description, dest.stat().st_size // 1048576, avg_speed, elapsed_total)
            return True
        except Exception as exc:
            log.warning("✗ Download attempt %d failed for %s: %s", attempt, description, exc)
            if temp_dest.exists():
                temp_dest.unlink(missing_ok=True)
            if attempt < max_retries:
                time.sleep(3)

    log.error("Failed to download %s after %d attempts.", description, max_retries)
    return False

def download_insightface():
    log.info("── 1/4: InsightFace model pack (buffalo_l) ──")
    target_dir = MODELS_DIR / "insightface_models" / "models" / "buffalo_l"
    det_onnx = target_dir / "det_10g.onnx"
    if det_onnx.exists() and det_onnx.stat().st_size > 1000000:
        log.info("✓ InsightFace buffalo_l models already extracted at %s", target_dir)
        return

    zip_path = MODELS_DIR / "insightface_models" / "models" / "buffalo_l.zip"
    url = "https://github.com/deepinsight/insightface/releases/download/v0.7/buffalo_l.zip"

    success = download_file_stream(url, zip_path, "InsightFace buffalo_l.zip")
    if success and zip_path.exists():
        try:
            log.info("Extracting %s into %s ...", zip_path, target_dir)
            target_dir.mkdir(parents=True, exist_ok=True)
            with zipfile.ZipFile(zip_path, "r") as z:
                z.extractall(target_dir)
            zip_path.unlink(missing_ok=True)
            log.info("✓ InsightFace buffalo_l models extracted successfully.")
        except Exception as exc:
            log.error("Failed to extract InsightFace zip: %s", exc)

def download_yolov8():
    log.info("── 2/4: YOLOv8 Nano object detection model ──")
    yolo_url = "https://huggingface.co/Kalray/yolov8/resolve/main/yolov8n.onnx"
    yolo_dest = MODELS_DIR / "yolov8n.onnx"
    download_file_stream(yolo_url, yolo_dest, "YOLOv8 Nano ONNX")

def download_opennsfw():
    log.info("── 3/4: OpenNSFW ResNet-50 ONNX model ──")
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
    download_file_stream(nsfw_url, nsfw_dest, "OpenNSFW ONNX")

def download_clip():
    log.info("── 4/4: CLIP Transformer (openai/clip-vit-base-patch32) ──")
    try:
        from transformers import CLIPModel, CLIPProcessor
        clip_name = "openai/clip-vit-base-patch32"
        CLIPProcessor.from_pretrained(clip_name)
        CLIPModel.from_pretrained(clip_name)
        log.info("✓ CLIP processor and model downloaded into Hugging Face cache.")
    except Exception as exc:
        log.warning("CLIP pre-download encountered non-fatal issue: %s. (Will download at runtime on first search)", exc)

def verify_models() -> bool:
    log.info("Verifying installed models in %s ...", MODELS_DIR)
    det_onnx = MODELS_DIR / "insightface_models" / "models" / "buffalo_l" / "det_10g.onnx"
    w600k_onnx = MODELS_DIR / "insightface_models" / "models" / "buffalo_l" / "w600k_r50.onnx"
    yolo_dest = MODELS_DIR / "yolov8n.onnx"
    nsfw_dest = MODELS_DIR / "open_nsfw.onnx"

    status = {
        "InsightFace Det (det_10g.onnx)": det_onnx.exists() and det_onnx.stat().st_size > 1000000,
        "InsightFace Rec (w600k_r50.onnx)": w600k_onnx.exists() and w600k_onnx.stat().st_size > 10000000,
        "YOLOv8 Nano (yolov8n.onnx)": yolo_dest.exists() and yolo_dest.stat().st_size > 1000000,
        "OpenNSFW (open_nsfw.onnx)": nsfw_dest.exists() and nsfw_dest.stat().st_size > 1000000,
    }
    all_ok = True
    for name, ok in status.items():
        if ok:
            log.info("✓ %s: Present & Valid", name)
        else:
            log.warning("✗ %s: Missing or Incomplete", name)
            all_ok = False
    return all_ok

def main():
    args = sys.argv[1:]
    if "--verify" in args or "--verify-only" in args:
        ok = verify_models()
        sys.exit(0 if ok else 1)

    log.info("Starting build-time pre-download of ML models into %s ...", MODELS_DIR)
    try:
        download_insightface()
    except Exception as e:
        log.warning("InsightFace download warning: %s", e)
    gc.collect()

    try:
        download_yolov8()
    except Exception as e:
        log.warning("YOLOv8 download warning: %s", e)
    gc.collect()

    try:
        download_opennsfw()
    except Exception as e:
        log.warning("OpenNSFW download warning: %s", e)
    gc.collect()

    try:
        download_clip()
    except Exception as e:
        log.warning("CLIP download warning: %s", e)
    gc.collect()

    log.info("Build-time model download stage finished.")
    verify_models()
    sys.exit(0)

if __name__ == "__main__":
    main()
