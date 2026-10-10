# syntax=docker/dockerfile:1
FROM python:3.12-slim

# ── OS deps ───────────────────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
        libgl1 \
        libglib2.0-0 \
        libsm6 \
        libxext6 \
        libxrender-dev \
        libgomp1 \
        tesseract-ocr \
        tesseract-ocr-eng \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# ── Python dependencies ───────────────────────────────────────────────────────
COPY requirements.txt .
RUN --mount=type=cache,target=/root/.cache/pip \
    pip install --no-cache-dir -r requirements.txt

# ── Pre-download all ML Models in dedicated cached layer ─────────────────────
# (Cached independently of application code so code changes rebuild in ~2s)
COPY scripts/download_all_models.py scripts/download_all_models.py
RUN mkdir -p /app/models && (python scripts/download_all_models.py || true)

# ── Application code ──────────────────────────────────────────────────────────
COPY . .

ENV PORT=8000
EXPOSE 8000
CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000"]
