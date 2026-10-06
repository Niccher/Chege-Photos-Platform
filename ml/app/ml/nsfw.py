from __future__ import annotations

import os
import gc
import logging
import urllib.request
from abc import ABC, abstractmethod
from pathlib import Path
from typing import Optional

import cv2
import numpy as np
from PIL import Image

from app.config import settings

log = logging.getLogger("ml_chege_photos.nsfw")

OPENNSFW_ONNX_PATH = Path("/app/models/open_nsfw.onnx")
OPENNSFW_DOWNLOAD_URL = "https://raw.githubusercontent.com/bhky/opennsfw2/master/opennsfw2/data/open_nsfw-90be3a4a.onnx"

_active_model_name: str | None = None
_cached_detector: Optional["BaseNSFWDetector"] = None


class BaseNSFWDetector(ABC):
    @abstractmethod
    def predict(self, image_path: str) -> dict:
        """Analyze an image and return a dict with nsfw_score (0.0 - 1.0) and extra details."""
        raise NotImplementedError

    def cleanup(self) -> None:
        """Free model weights and memory."""
        pass


def ensure_opennsfw_onnx_present() -> Path:
    """Ensures /app/models/open_nsfw.onnx exists on disk for OpenCV DNN and manifest inventory."""
    if OPENNSFW_ONNX_PATH.exists() and OPENNSFW_ONNX_PATH.stat().st_size > 1000000:
        return OPENNSFW_ONNX_PATH

    try:
        OPENNSFW_ONNX_PATH.parent.mkdir(parents=True, exist_ok=True)
    except Exception as e:
        log.warning("Could not create directory for opennsfw onnx model: %s", e)

    # 1. Try copying from installed opennsfw2 package
    try:
        import opennsfw2 as _pkg
        pkg_dir = Path(_pkg.__file__).parent
        data_dir = pkg_dir / "data"
        if data_dir.exists():
            for f in data_dir.glob("*.onnx"):
                if f.is_file() and f.stat().st_size > 1000000:
                    import shutil
                    shutil.copy2(f, OPENNSFW_ONNX_PATH)
                    log.info("Copied opennsfw2 ONNX model from package data: %s -> %s", f, OPENNSFW_ONNX_PATH)
                    return OPENNSFW_ONNX_PATH
    except Exception as e:
        log.debug("Could not copy from opennsfw2 package data: %s", e)

    # 2. Download from official repository
    log.info("Downloading OpenNSFW ONNX model (~6.2 MB) from %s ...", OPENNSFW_DOWNLOAD_URL)
    try:
        urllib.request.urlretrieve(OPENNSFW_DOWNLOAD_URL, str(OPENNSFW_ONNX_PATH))
        log.info("OpenNSFW ONNX model downloaded successfully to %s", OPENNSFW_ONNX_PATH)
    except Exception as exc:
        log.error("Failed to download OpenNSFW ONNX model: %s", exc)

    return OPENNSFW_ONNX_PATH


class OpenNSFW2Detector(BaseNSFWDetector):
    """
    Lightweight, fast Yahoo Open_NSFW ResNet-50 detector.
    Footprint: ~6MB weights, <50MB RAM, 30-50ms CPU execution.
    """
    def __init__(self):
        self.net = None
        self.use_library = False

        # Ensure model weights file is present on disk for manifest and fallback
        ensure_opennsfw_onnx_present()

        # Attempt 1: Check if opennsfw2 library is importable
        try:
            import opennsfw2 as nsfw
            self.nsfw_lib = nsfw
            self.use_library = True
            log.info("OpenNSFW2: using opennsfw2 package for inference")
        except Exception as exc:
            log.info("OpenNSFW2 package not directly usable (%s), falling back to OpenCV DNN ONNX", exc)
            self._init_onnx()

    def _init_onnx(self):
        ensure_opennsfw_onnx_present()
        try:
            self.net = cv2.dnn.readNetFromONNX(str(OPENNSFW_ONNX_PATH))
            self.net.setPreferableBackend(cv2.dnn.DNN_BACKEND_OPENCV)
            self.net.setPreferableTarget(cv2.dnn.DNN_TARGET_CPU)
            log.info("OpenNSFW ONNX model loaded into OpenCV DNN")
        except Exception as exc:
            log.error("Failed to load OpenNSFW ONNX model: %s", exc)
            raise

    def predict(self, image_path: str) -> dict:
        if self.use_library:
            try:
                prob = float(self.nsfw_lib.predict_image(image_path))
                return {
                    "nsfw_score": round(max(0.0, min(1.0, prob)), 4),
                    "details": {"backend": "opennsfw2-library"},
                }
            except Exception as lib_err:
                log.warning("opennsfw2 library prediction failed: %s; falling back to ONNX", lib_err)
                if self.net is None:
                    self._init_onnx()

        if self.net is None:
            self._init_onnx()

        # Preprocess for Yahoo OpenNSFW (ResNet-50, 224x224, mean [104, 117, 123] in BGR)
        img = cv2.imread(image_path)
        if img is None:
            raise ValueError(f"Could not read image for NSFW detection: {image_path}")

        # Resize keeping aspect ratio or direct center crop
        resized = cv2.resize(img, (224, 224), interpolation=cv2.INTER_LINEAR)
        blob = cv2.dnn.blobFromImage(
            resized,
            scalefactor=1.0,
            size=(224, 224),
            mean=(104.00698793, 116.66876762, 122.67891434),
            swapRB=False,
            crop=False,
        )
        self.net.setInput(blob)
        preds = self.net.forward()  # Shape: (1, 2) [neutral, nsfw]

        # Apply softmax over logits
        exp_preds = np.exp(preds[0] - np.max(preds[0]))
        softmax_probs = exp_preds / np.sum(exp_preds)
        nsfw_prob = float(softmax_probs[1]) if len(softmax_probs) > 1 else float(softmax_probs[0])

        return {
            "nsfw_score": round(max(0.0, min(1.0, nsfw_prob)), 4),
            "details": {"backend": "opencv-dnn-onnx", "raw_probs": [float(p) for p in softmax_probs]},
        }

    def cleanup(self) -> None:
        self.net = None


class FalconsaiDetector(BaseNSFWDetector):
    """
    Hugging Face Vision Transformer (ViT) detector.
    Footprint: ~300MB weights, ~400MB RAM, state-of-the-art accuracy.
    """
    MODEL_ID = "Falconsai/nsfw_image_detection"

    def __init__(self):
        log.info("Loading Falconsai ViT NSFW model (%s)...", self.MODEL_ID)
        from transformers import AutoImageProcessor, AutoModelForImageClassification
        import torch

        self.torch = torch
        self.processor = AutoImageProcessor.from_pretrained(self.MODEL_ID)
        self.model = AutoModelForImageClassification.from_pretrained(self.MODEL_ID)
        self.model.eval()
        log.info("Falconsai ViT NSFW model loaded successfully")

    def predict(self, image_path: str) -> dict:
        image = Image.open(image_path).convert("RGB")
        inputs = self.processor(images=image, return_tensors="pt")

        with self.torch.no_grad():
            outputs = self.model(**inputs)
            logits = outputs.logits
            probs = self.torch.nn.functional.softmax(logits, dim=-1)[0]

        # Model labels: typically {"0": "normal", "1": "nsfw"}
        id2label = self.model.config.id2label or {0: "normal", 1: "nsfw"}
        nsfw_index = 1
        for idx, label in id2label.items():
            if "nsfw" in label.lower():
                nsfw_index = int(idx)
                break

        score = float(probs[nsfw_index])
        return {
            "nsfw_score": round(max(0.0, min(1.0, score)), 4),
            "details": {
                "backend": "transformers-vit",
                "probabilities": {str(id2label.get(i, i)): round(float(p), 4) for i, p in enumerate(probs)},
            },
        }

    def cleanup(self) -> None:
        del self.model
        del self.processor
        self.model = None
        self.processor = None
        gc.collect()


class NudeNetDetector(BaseNSFWDetector):
    """
    NudeNet anatomical detector identifying specific exposed body parts.
    Footprint: ~150MB weights, ~250MB RAM.
    """
    EXPOSED_LABELS = {
        "FEMALE_GENITALIA_EXPOSED",
        "FEMALE_BREAST_EXPOSED",
        "BUTTOCKS_EXPOSED",
        "MALE_GENITALIA_EXPOSED",
        "ANUS_EXPOSED",
        "EXPOSED_GENITALIA_F",
        "EXPOSED_GENITALIA_M",
        "EXPOSED_BREAST_F",
        "EXPOSED_BUTTOCKS",
        "EXPOSED_ANUS",
    }

    def __init__(self):
        log.info("Loading NudeNet detector...")
        try:
            from nudenet import NudeDetector
            self.detector = NudeDetector()
            log.info("NudeNet detector loaded successfully")
        except Exception as exc:
            log.error("Failed to load NudeNet detector: %s", exc)
            raise

    def predict(self, image_path: str) -> dict:
        detections = self.detector.detect(image_path)
        max_exposed_score = 0.0
        exposed_found = []

        for d in detections:
            cls_name = d.get("class", "").upper()
            score = float(d.get("score", 0.0))
            if any(exp in cls_name for exp in ["EXPOSED", "GENITALIA", "ANUS"]) or cls_name in self.EXPOSED_LABELS:
                exposed_found.append({"class": cls_name, "score": score})
                if score > max_exposed_score:
                    max_exposed_score = score

        return {
            "nsfw_score": round(max_exposed_score, 4),
            "details": {
                "backend": "nudenet",
                "detections_count": len(detections),
                "exposed_found": exposed_found,
            },
        }

    def cleanup(self) -> None:
        del self.detector
        self.detector = None
        gc.collect()


def get_detector(model_name: str | None = None) -> BaseNSFWDetector:
    """Factory and cache manager for the active NSFW detector."""
    global _active_model_name, _cached_detector

    requested = (model_name or settings.nsfw_model_name or "opennsfw2").lower().strip()
    if requested in ["vit", "transformers", "falconsai/nsfw_image_detection"]:
        requested = "falconsai"
    elif requested in ["nude", "nudenet"]:
        requested = "nudenet"
    else:
        requested = "opennsfw2"

    if _cached_detector is not None and _active_model_name == requested:
        return _cached_detector

    # Switch model: clean up previous model
    if _cached_detector is not None:
        log.info("Unloading previous NSFW detector '%s'", _active_model_name)
        try:
            _cached_detector.cleanup()
        except Exception as exc:
            log.warning("Error cleaning up detector %s: %s", _active_model_name, exc)
        _cached_detector = None
        gc.collect()

    log.info("Instantiating NSFW detector '%s'", requested)
    try:
        if requested == "falconsai":
            _cached_detector = FalconsaiDetector()
        elif requested == "nudenet":
            _cached_detector = NudeNetDetector()
        else:
            _cached_detector = OpenNSFW2Detector()
        _active_model_name = requested
    except Exception as init_err:
        log.error("Could not load requested model '%s': %s. Falling back to opennsfw2.", requested, init_err)
        _cached_detector = OpenNSFW2Detector()
        _active_model_name = "opennsfw2"

    return _cached_detector


def verify_sensitive_gate(
    image_path: str,
    base_score: float,
    threshold: float,
) -> dict:
    """
    2-Stage Hierarchical Verification Gate:
    Stage 1: Fast Screening via OpenNSFW (or active detector).
    Stage 2 (Verification Gate):
      Check A: YOLOv8 Human Presence (Class 0: "person", confidence >= 0.35).
               If no human is detected, suppresses false positives like food,
               sand dunes, furniture, and animals.
      Check B: CLIP Zero-Shot Art & Classical Statue Filter.
               If classified as a marble statue, museum sculpture, painting,
               or comic illustration, suppresses the sensitive flag.
    """
    # Check A: Human Presence via YOLOv8
    has_person = True
    max_person_conf = 0.0
    try:
        from app.ml.object_detection import detect_objects
        objects = detect_objects(image_path, conf_threshold=0.35)
        person_detections = [o for o in objects if o.get("tag") == "person"]
        if person_detections:
            max_person_conf = max([float(o.get("confidence", 0.0)) for o in person_detections])
            has_person = max_person_conf >= 0.35
        else:
            has_person = False
            max_person_conf = 0.0
    except Exception as exc:
        log.warning("Stage 2 Check A (YOLO person presence) failed: %s", exc)
        has_person = True
        max_person_conf = 0.0

    if not has_person:
        return {
            "gate_enabled": True,
            "gate_verified": False,
            "suppressed": True,
            "suppression_reason": f"Suppressed by Check A: No human presence detected (YOLO person confidence {max_person_conf*100:.1f}% < 35%)",
            "human_presence": False,
            "yolo_person_confidence": round(float(max_person_conf), 4),
            "art_statue_filtered": False,
            "clip_top_label": None,
            "clip_probabilities": {},
        }

    # Check B: Art / Statue Zero-Shot Filter via CLIP
    is_art_or_statue = False
    clip_probs = {}
    top_label = ""
    candidate_labels = [
        "a photographic picture of an intimate person",
        "a museum marble statue or classical sculpture",
        "a painting, artistic drawing, or comic illustration",
        "a landscape, inanimate object, or furniture",
    ]

    try:
        from app.ml.semantic_search import get_clip_model_and_processor
        import torch
        from PIL import Image

        model, processor = get_clip_model_and_processor()
        img = Image.open(image_path).convert("RGB")
        inputs = processor(text=candidate_labels, images=img, return_tensors="pt", padding=True)
        with torch.no_grad():
            outputs = model(**inputs)
            logits = outputs.logits_per_image  # shape (1, 4)
            probs = torch.nn.functional.softmax(logits, dim=-1)[0].cpu().numpy()

        for lbl, p in zip(candidate_labels, probs):
            clip_probs[lbl] = round(float(p), 4)

        top_idx = int(np.argmax(probs))
        top_label = candidate_labels[top_idx]
        real_person_prob = float(probs[0])
        statue_art_prob = float(probs[1]) + float(probs[2])

        # If museum statue or classical sculpture or painting/comic has higher confidence than real photo
        if top_idx in (1, 2, 3) or (statue_art_prob > real_person_prob):
            is_art_or_statue = True
    except Exception as exc:
        log.warning("Stage 2 Check B (CLIP art/statue filter) failed: %s", exc)
        is_art_or_statue = False

    if is_art_or_statue:
        return {
            "gate_enabled": True,
            "gate_verified": False,
            "suppressed": True,
            "suppression_reason": f"Suppressed by Check B: Classified as non-photographic artwork or sculpture via CLIP ({top_label}, {clip_probs.get(top_label, 0.0)*100:.1f}%)",
            "human_presence": True,
            "yolo_person_confidence": round(float(max_person_conf), 4),
            "art_statue_filtered": True,
            "clip_top_label": top_label,
            "clip_probabilities": clip_probs,
        }

    # Both checks passed!
    return {
        "gate_enabled": True,
        "gate_verified": True,
        "suppressed": False,
        "suppression_reason": None,
        "human_presence": True,
        "yolo_person_confidence": round(float(max_person_conf), 4),
        "art_statue_filtered": False,
        "clip_top_label": top_label or candidate_labels[0],
        "clip_probabilities": clip_probs,
    }


def predict_nsfw(
    image_path: str,
    model_name: str | None = None,
    threshold: float | None = None,
) -> dict:
    """
    Analyze image and return standardized sensitivity evaluation.
    Utilizes 2-stage hierarchical gate when score >= threshold to suppress false positives.
    """
    thresh = float(threshold if threshold is not None else settings.nsfw_threshold)
    try:
        detector = get_detector(model_name)
        res = detector.predict(image_path)
        score = float(res.get("nsfw_score", 0.0))
        active_name = _active_model_name or "opennsfw2"
        details = res.get("details", {})

        # ── 2-Stage Hierarchical Verification Gate ────────────────
        gate_info = None
        if score >= thresh:
            gate_info = verify_sensitive_gate(image_path, score, thresh)
            if gate_info["suppressed"]:
                is_nsfw = False
                reason = gate_info["suppression_reason"]
            else:
                is_nsfw = True
                reason = f"Confirmed sensitive human photo (passed Stage 2 human & non-art gates, score {score*100:.1f}% >= {thresh*100:.1f}%)"
        else:
            is_nsfw = False
            gate_info = {
                "gate_enabled": True,
                "gate_verified": None,
                "suppressed": False,
                "skipped": True,
                "reason": f"Within safe boundary ({score*100:.1f}% < {thresh*100:.1f}%) via {active_name}",
            }
            if active_name == "nudenet":
                reason = f"No exposed anatomy detected above threshold ({thresh*100:.1f}%)"
            elif active_name == "falconsai":
                reason = f"Classified as safe by Vision Transformer ({score*100:.1f}% < {thresh*100:.1f}%)"
            else:
                reason = f"Within safe boundary ({score*100:.1f}% < {thresh*100:.1f}%) via OpenNSFW ResNet-50"

        details["gate_info"] = gate_info

        from datetime import datetime
        return {
            "nsfw_score": score,
            "is_nsfw": is_nsfw,
            "threshold": thresh,
            "model": active_name,
            "reason": reason,
            "scanned_at": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            "details": details,
        }
    except Exception as exc:
        log.warning("NSFW prediction failed for %s: %s", image_path, exc)
        return {
            "nsfw_score": 0.0,
            "is_nsfw": False,
            "threshold": thresh,
            "model": _active_model_name or "opennsfw2",
            "reason": f"Analysis error: {exc}",
            "scanned_at": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            "error": str(exc),
        }
