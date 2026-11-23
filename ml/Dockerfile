# syntax=docker/dockerfile:1
FROM python:3.15-slim

# Default CPU PyTorch index; scripts/deploy.sh overrides to https://download.pytorch.org/whl/cu124 when GPU detected
ARG TORCH_INDEX_URL=https://download.pytorch.org/whl/cpu

# ── OS deps (includes ffmpeg for Whisper Large-v3-Turbo video audio extraction) ──
RUN apt-get update && apt-get install -y --no-install-recommends \
        ffmpeg \
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

# ── Python dependencies (PyTorch CPU/CUDA controlled by TORCH_INDEX_URL) ──────
COPY requirements.txt .
RUN --mount=type=cache,target=/root/.cache/pip \
    pip install --no-cache-dir torch --index-url "${TORCH_INDEX_URL}" \
    && pip install --no-cache-dir -r requirements.txt

# ── Application code (Models are stored centrally in minio://chege-models/) ───
COPY . .

ENV PORT=8000
EXPOSE 8000
CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000"]
