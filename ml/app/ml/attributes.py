from __future__ import annotations

import logging

import numpy as np

from app.config import settings

log = logging.getLogger("ml_chege_photos.attributes")

EMOTION_LABELS = ["angry", "disgust", "fear", "happy", "sad", "surprise", "neutral"]


def extract_attributes(face) -> dict:
    result = {}

    if settings.include_sensitive_attributes:
        result["age"] = int(face.age) if hasattr(face, "age") and face.age is not None else None
        gender_val = None
        if hasattr(face, "gender") and face.gender is not None:
            gender_val = "male" if int(face.gender) == 1 else "female"
        result["gender"] = gender_val
    else:
        result["age"] = None
        result["gender"] = None

    # Derive baseline emotion from facial landmarks geometry
    emotion = "Neutral 🙂"
    if hasattr(face, "kps") and face.kps is not None and face.kps.shape == (5, 2):
        try:
            le = face.kps[0]
            re = face.kps[1]
            lm = face.kps[3]
            rm = face.kps[4]
            ipd = float(np.linalg.norm(re - le))
            if ipd > 0:
                mouth_w = float(np.linalg.norm(rm - lm))
                ratio = mouth_w / ipd
                if ratio >= 0.78:
                    emotion = "Smiling 😊"
                elif ratio <= 0.58:
                    emotion = "Serious/Neutral 😐"
        except Exception as e:
            log.debug("Emotion landmark calculation error: %s", e)

    result["emotion"] = emotion
    return result


def extract_aligned_crop(img: np.ndarray, face, size: tuple[int, int] = (112, 112)) -> np.ndarray | None:
    try:
        from insightface.utils import face_align

        crop = face_align.norm_crop(img, face.kps, image_size=size[0])
        return crop
    except Exception as exc:
        log.warning("Failed to extract aligned crop: %s", exc)
        return None
