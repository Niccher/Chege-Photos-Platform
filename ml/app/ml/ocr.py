from __future__ import annotations

import logging
from pathlib import Path
import cv2
import numpy as np

log = logging.getLogger("ml_chege_photos.ocr")

def extract_text_from_image(image_input: str | Path | np.ndarray) -> str:
    """
    Extract readable text from a photo using Tesseract OCR.
    Preprocesses the image (resizing, grayscale, contrast) for high recall
    on documents, receipts, signs, and labels.
    """
    try:
        import pytesseract
    except ImportError:
        log.warning("pytesseract is not installed; skipping OCR extraction.")
        return ""

    try:
        if isinstance(image_input, (str, Path)):
            path = Path(image_input)
            if not path.exists() or path.stat().st_size == 0:
                log.warning("Image path does not exist or is empty: %s", image_input)
                return ""
            img = cv2.imread(str(path))
        elif isinstance(image_input, np.ndarray):
            img = image_input
        else:
            log.warning("Unsupported image input type: %s", type(image_input))
            return ""

        if img is None or img.size == 0:
            return ""

        h, w = img.shape[:2]

        # Downscale overly massive photos for fast OCR processing while preserving readability
        max_dim = 1920
        if max(h, w) > max_dim:
            scale = max_dim / float(max(h, w))
            new_w = int(w * scale)
            new_h = int(h * scale)
            img = cv2.resize(img, (new_w, new_h), interpolation=cv2.INTER_AREA)

        # Convert to grayscale
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY) if len(img.shape) == 3 else img

        # Run Tesseract with automatic page segmentation mode
        # psm 3: Fully automatic page segmentation, but no OSD
        raw_text = pytesseract.image_to_string(gray, lang="eng", config="--psm 3")

        # Sanitize whitespace and clean non-printable chars
        cleaned = " ".join(raw_text.split())

        # Cap text length to prevent huge runaway text blobs
        if len(cleaned) > 5000:
            cleaned = cleaned[:5000].strip()

        if cleaned:
            log.debug("Extracted %d characters of OCR text", len(cleaned))

        return cleaned
    except Exception as exc:
        log.warning("OCR text extraction failed: %s", exc)
        return ""
