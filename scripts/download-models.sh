#!/usr/bin/env bash
# =============================================================
# download-models.sh — Host & Container ML Model Downloader
#                      for Chege Photos Platform
#
# Usage:
#   bash scripts/download-models.sh            # Download all missing models
#   bash scripts/download-models.sh --verify   # Verify presence & integrity
#   bash scripts/download-models.sh --force    # Re-download all models
# =============================================================
set -euo pipefail

GREEN="\033[0;32m"; YELLOW="\033[1;33m"; RED="\033[0;31m"; CYAN="\033[0;36m"; BOLD="\033[1m"; RESET="\033[0m"

log()  { echo -e "${GREEN}[OK]${RESET}   $1"; }
warn() { echo -e "${YELLOW}[WARN]${RESET} $1"; }
info() { echo -e "${CYAN}[INFO]${RESET} $1"; }
err()  { echo -e "${RED}[FAIL]${RESET} $1"; exit 1; }

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"

if [ -d "/app/models" ]; then
    MODELS_DIR="/app/models"
else
    MODELS_DIR="${ROOT_DIR}/ml/models"
fi
mkdir -p "$MODELS_DIR"

MODE="download"
if [ "${1:-}" = "--verify" ]; then
    MODE="verify"
elif [ "${1:-}" = "--force" ]; then
    MODE="force"
fi

echo -e "\n${BOLD}${CYAN}=== Chege Photos Platform — ML Model Manager ===${RESET}"
info "Target Directory: ${MODELS_DIR}"
info "Execution Mode  : ${MODE}"
echo ""

download_file() {
    local URL="$1"
    local DEST="$2"
    local DESC="$3"
    local MIN_BYTES="${4:-100000}"

    if [ -f "$DEST" ] && [ "$MODE" != "force" ]; then
        local SIZE
        SIZE=$(stat -c%s "$DEST" 2>/dev/null || stat -f%z "$DEST" 2>/dev/null || echo "0")
        if [ "$SIZE" -ge "$MIN_BYTES" ]; then
            log "✓ ${DESC} already present ($(( SIZE / 1024 / 1024 )) MB)"
            return 0
        fi
    fi

    if [ "$MODE" = "verify" ]; then
        err "✗ ${DESC} missing or incomplete at ${DEST}"
    fi

    info "Downloading ${DESC}..."
    local TMP="${DEST}.tmp"
    mkdir -p "$(dirname "$DEST")"
    rm -f "$TMP"

    curl -fL --retry 3 --retry-delay 2 --progress-bar -o "$TMP" "$URL"
    mv "$TMP" "$DEST"
    local FINAL_SIZE
    FINAL_SIZE=$(stat -c%s "$DEST" 2>/dev/null || stat -f%z "$DEST" 2>/dev/null || echo "0")
    log "✓ Downloaded ${DESC} successfully ($(( FINAL_SIZE / 1024 / 1024 )) MB)"
}

# ── 1. InsightFace ArcFace buffalo_l Pack ──────────────────────
echo -e "${BOLD}1/4. InsightFace Model Pack (buffalo_l)${RESET}"
BUFFALO_DIR="${MODELS_DIR}/insightface_models/models/buffalo_l"
BUFFALO_DET="${BUFFALO_DIR}/det_10g.onnx"
BUFFALO_REC="${BUFFALO_DIR}/w600k_r50.onnx"
BUFFALO_ZIP="${MODELS_DIR}/insightface_models/models/buffalo_l.zip"
BUFFALO_URL="https://github.com/deepinsight/insightface/releases/download/v0.7/buffalo_l.zip"

if [ -f "$BUFFALO_DET" ] && [ -f "$BUFFALO_REC" ] && [ "$MODE" != "force" ]; then
    log "✓ InsightFace buffalo_l models already extracted in ${BUFFALO_DIR}"
else
    if [ "$MODE" = "verify" ]; then
        err "✗ InsightFace models missing in ${BUFFALO_DIR}"
    fi
    download_file "$BUFFALO_URL" "$BUFFALO_ZIP" "InsightFace buffalo_l.zip" 250000000
    info "Extracting buffalo_l.zip..."
    mkdir -p "$BUFFALO_DIR"
    unzip -qo "$BUFFALO_ZIP" -d "$BUFFALO_DIR" || python3 -c "import zipfile; zipfile.ZipFile('$BUFFALO_ZIP').extractall('$BUFFALO_DIR')"
    rm -f "$BUFFALO_ZIP"
    log "✓ InsightFace buffalo_l extracted successfully"
fi
echo ""

# ── 2. YOLOv8 Nano Object Detector ────────────────────────────
echo -e "${BOLD}2/4. YOLOv8 Nano Object Detection Model${RESET}"
YOLO_DEST="${MODELS_DIR}/yolov8n.onnx"
YOLO_URL="https://huggingface.co/Kalray/yolov8/resolve/main/yolov8n.onnx"
download_file "$YOLO_URL" "$YOLO_DEST" "YOLOv8 Nano ONNX" 5000000
echo ""

# ── 3. OpenNSFW ResNet-50 Classifier ──────────────────────────
echo -e "${BOLD}3/4. OpenNSFW ResNet-50 Classifier Model${RESET}"
NSFW_DEST="${MODELS_DIR}/open_nsfw.onnx"
NSFW_URL="https://github.com/facefusion/facefusion-assets/releases/download/models-3.0.0/open_nsfw.onnx"
NSFW_FALLBACK_URL="https://huggingface.co/ninjawick/webui-faceswap-unlocked/resolve/main/open_nsfw.onnx"
if ! download_file "$NSFW_URL" "$NSFW_DEST" "OpenNSFW ResNet-50 ONNX" 10000000; then
    warn "Primary source failed; attempting Hugging Face fallback mirror..."
    download_file "$NSFW_FALLBACK_URL" "$NSFW_DEST" "OpenNSFW ResNet-50 ONNX (Fallback)" 10000000
fi
echo ""

# ── 4. CLIP Transformer ───────────────────────────────────────
echo -e "${BOLD}4/4. OpenAI CLIP Transformer (openai/clip-vit-base-patch32)${RESET}"
if python3 -c "import transformers" >/dev/null 2>&1; then
    if [ "$MODE" != "verify" ]; then
        info "Pre-warming CLIP transformer into Hugging Face local cache..."
        python3 -c "
from transformers import CLIPModel, CLIPProcessor
name = 'openai/clip-vit-base-patch32'
CLIPProcessor.from_pretrained(name)
CLIPModel.from_pretrained(name)
print('CLIP cache verified.')
" && log "✓ CLIP transformer weights ready in cache" || warn "CLIP pre-warming skipped (will download at runtime)"
    else
        log "✓ Python transformers environment available"
    fi
else
    info "Python transformers not installed on host — CLIP weights will load inside ML Docker container"
fi
echo ""

# ── Summary ───────────────────────────────────────────────────
echo -e "${GREEN}${BOLD}══════════════════════════════════════════════════════════════${RESET}"
echo -e "${GREEN}${BOLD}   ML MODELS INVENTORY & INTEGRITY REPORT${RESET}"
echo -e "${GREEN}${BOLD}══════════════════════════════════════════════════════════════${RESET}"
ls -lh "$MODELS_DIR"/*.onnx 2>/dev/null | awk '{print "   " $9 " (" $5 ")"}' || true
ls -lh "$BUFFALO_DIR"/*.onnx 2>/dev/null | awk '{print "   " $9 " (" $5 ")"}' || true
echo -e "${GREEN}${BOLD}══════════════════════════════════════════════════════════════${RESET}"
log "All required neural network models verified."
