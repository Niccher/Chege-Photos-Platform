#!/usr/bin/env bash
# Syncs all AI models directly into MinIO (minio://chege-models/) inside the ML container.
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

echo "Syncing AI models (InsightFace, YOLOv8n, OpenNSFW, SigLIP, Florence-2, Whisper Large-v3-Turbo) to MinIO..."
if docker compose ps --status running ml >/dev/null 2>&1; then
    docker compose exec -T ml python scripts/download_all_models.py "$@"
else
    python3 ml/scripts/download_all_models.py "$@"
fi
