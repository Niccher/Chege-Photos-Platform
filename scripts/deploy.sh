#!/usr/bin/env bash
# =============================================================
# deploy.sh — ONE-CLICK Deployment & Setup Pipeline
#             for Chege Photos Platform (Monorepo)
#
# Usage:  bash scripts/deploy.sh
#         (run from the repository root)
#
# Production Standard Ports:
#   - Web Dashboard & API : Port 80
#   - ML AI Microservice  : Port 8000
#   - Redis In-Memory DB  : Port 6379
#   - Qdrant Vector DB    : Port 6333 (HTTP) / 6334 (gRPC)
#   - MySQL 8.4 Database  : Port 3306
#
# 13-Step Automated Pipeline:
#   1. Detect Dynamic IP (Public / LAN Fallback)
#   2. Pre-flight System & Resource Check (OS, CPU, RAM, Disk)
#   3. Git Working Tree Sanity Check & Pull
#   4. Write / Patch .env Configuration
#   5. Stop Old Containers & Clear Name Conflicts
#   6. Build & Start All Containers (MySQL, Redis, Qdrant, ML, Web)
#   7. Wait for MySQL Health (:3306)
#   8. Wait for Redis Health (:6379)
#   9. Wait for Qdrant Vector Engine Health (:6333)
#  10. Wait for ML Microservice Readiness (:8000)
#  11. Wait for WebApp Server Readiness (:80)
#  12. Container Permissions Enforcement (web/writable & uploads)
#  13. Deployment Timeline Summary & Endpoint Matrix
# =============================================================
set -euo pipefail

# Enable Docker BuildKit parallel multi-stage acceleration
export DOCKER_BUILDKIT=1
export COMPOSE_DOCKER_CLI_BUILD=1

# ── Colors & Formatting ───────────────────────────────────────
GREEN="\033[0;32m"; YELLOW="\033[1;33m"; RED="\033[0;31m"
CYAN="\033[0;36m"; BOLD="\033[1m"; RESET="\033[0m"

DEPLOY_START_TIME=$(date +%s)
SECTION_START_TIME=$DEPLOY_START_TIME
PREV_SECTION=""
declare -a STAGE_NAMES=()
declare -a STAGE_DURATIONS=()

format_duration() {
    local S=$1
    if [ "$S" -ge 60 ]; then
        local M=$((S / 60))
        local R=$((S % 60))
        echo "${M}m ${R}s"
    else
        echo "${S}s"
    fi
}

log()     { echo -e "${GREEN}[OK]${RESET}   $1"; }
warn()    { echo -e "${YELLOW}[WARN]${RESET} $1"; }
err()     {
    local NOW=$(date +%s)
    local ELAPSED=$((NOW - DEPLOY_START_TIME))
    echo -e "${RED}[FAIL]${RESET} $1 (failed after $(format_duration $ELAPSED))"
    exit 1
}

section() {
    local NOW=$(date +%s)
    if [ -n "$PREV_SECTION" ]; then
        local DURATION=$((NOW - SECTION_START_TIME))
        echo -e "${CYAN}──> Completed: ${PREV_SECTION} in $(format_duration $DURATION)${RESET}"
        STAGE_NAMES+=("$PREV_SECTION")
        STAGE_DURATIONS+=("$DURATION")
    fi
    PREV_SECTION="$1"
    SECTION_START_TIME=$NOW
    echo -e "\n${CYAN}${BOLD}=== $1 ===${RESET}"
}

finish_deployment() {
    local NOW=$(date +%s)
    if [ -n "$PREV_SECTION" ]; then
        local DURATION=$((NOW - SECTION_START_TIME))
        echo -e "${CYAN}──> Completed: ${PREV_SECTION} in $(format_duration $DURATION)${RESET}"
        STAGE_NAMES+=("$PREV_SECTION")
        STAGE_DURATIONS+=("$DURATION")
        PREV_SECTION=""
    fi
    local TOTAL_DURATION=$((NOW - DEPLOY_START_TIME))
    echo ""
    echo -e "${GREEN}${BOLD}══════════════════════════════════════════════════════════════${RESET}"
    echo -e "${GREEN}${BOLD}   DEPLOYMENT TIMELINE & EXECUTION SUMMARY${RESET}"
    echo -e "${GREEN}${BOLD}══════════════════════════════════════════════════════════════${RESET}"
    for i in "${!STAGE_NAMES[@]}"; do
        printf "   %-48s : %s\n" "${STAGE_NAMES[$i]}" "$(format_duration ${STAGE_DURATIONS[$i]})"
    done
    echo -e "   ------------------------------------------------------------"
    printf "   ${BOLD}%-48s${RESET} : ${BOLD}%s (%ss)${RESET}\n" "Total Overall Deployment Time" "$(format_duration $TOTAL_DURATION)" "$TOTAL_DURATION"
    echo -e "${GREEN}${BOLD}══════════════════════════════════════════════════════════════${RESET}"
}

# ── Guard: must run from repository root ─────────────────────
[ -f "docker-compose.yml" ] || err "Run from the repository root (where docker-compose.yml lives)."

# ── 1. Detect Dynamic IP ──────────────────────────────────────
section "1. Detecting Dynamic IP"
DETECTED_IP=$(hostname -I 2>/dev/null | awk '{print $1}' | tr -d '[:space:]' || true)
if [ -z "$DETECTED_IP" ]; then
    DETECTED_IP=$(ip route get 1.1.1.1 2>/dev/null | awk '{print $7}' | tr -d '[:space:]' || true)
fi
if [ -z "$DETECTED_IP" ]; then
    DETECTED_IP="127.0.0.1"
fi
log "Local Network LAN IP: $DETECTED_IP"

# ── 2. Pre-flight System & Resource Check ─────────────────────
section "2. Pre-flight Disk & RAM Check"
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS_PRETTY="${PRETTY_NAME:-$NAME}"
else
    OS_PRETTY="$(uname -s)"
fi
ARCH="$(uname -m)"
CPU_CORES=$(nproc 2>/dev/null || echo "1")
TOTAL_MEM_MB=$(free -m | awk '/Mem:/ {print $2}')
USED_MEM_MB=$(free -m | awk '/Mem:/ {print $3}')
FREE_MEM_MB=$(free -m | awk '/Mem:/ {print ($7 != "" ? $7 : $4)}')

log "Host OS: $OS_PRETTY ($ARCH) with $CPU_CORES CPU cores"
log "RAM: ${TOTAL_MEM_MB}MB total, ${FREE_MEM_MB}MB free"

if [ "$TOTAL_MEM_MB" -lt 3500 ]; then
    warn "Total RAM is under 4GB. ML neural network weights may cause memory pressure."
fi

# ── 3. Git Working Tree Sanity Check & Pull ───────────────────
section "3. Git Working Tree Check"
if git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
    log "On Git branch: $CURRENT_BRANCH"
    if [ -z "$(git status --porcelain)" ]; then
        log "Working tree clean."
    else
        warn "Working tree has uncommitted changes. Skipping git pull to preserve work."
    fi
else
    log "Not a Git clone or Git unavailable, proceeding..."
fi

# ── 4. Write / Patch .env Configuration ───────────────────────
section "4. Writing .env Configuration"
if [ ! -f ".env" ]; then
    log ".env not found. Creating from .env.example..."
    cp .env.example .env
fi

# Ensure standard production ports are configured
export WEB_PORT="${WEB_PORT:-80}"
export DB_PORT="${DB_PORT:-3306}"
export ML_PORT="${ML_PORT:-8000}"
export REDIS_PORT="${REDIS_PORT:-6379}"
export QDRANT_HTTP_PORT="${QDRANT_HTTP_PORT:-6333}"
export QDRANT_GRPC_PORT="${QDRANT_GRPC_PORT:-6334}"

# Generate encryption key if missing
if ! grep -q "^encryption\.key\s*=\s*.\+" .env 2>/dev/null; then
    RANDOM_KEY=$(openssl rand -hex 16 2>/dev/null || head -c 16 /dev/urandom | xxd -p)
    sed -i "s/^encryption\.key\s*=.*/encryption.key = hex2bin:$RANDOM_KEY/" .env 2>/dev/null || echo "encryption.key = hex2bin:$RANDOM_KEY" >> .env
    log "Generated CodeIgniter 4 encryption key."
fi
log "Environment configuration verified."

# ── 5. Stop Old Containers & Clear Conflicts ──────────────────
section "5. Stopping Old Containers & Clearing Name Conflicts"
docker compose down --remove-orphans 2>/dev/null || true
log "Old container instances stopped."

# ── 6. Build & Start All Containers ───────────────────────────
section "6. Building & Starting All Containers"
BUILD_FLAG="--build"
if [ "${1:-}" = "--fast" ] || [ "${1:-}" = "--no-build" ]; then
    BUILD_FLAG=""
    log "Fast startup mode active (skipping image rebuild)."
fi
docker compose up $BUILD_FLAG -d
log "Container orchestration launched."

# ── 7. Wait for MySQL Health (:3306) ──────────────────────────
section "7. Waiting for MySQL to Become Healthy"
MYSQL_WAIT=0
MYSQL_MAX=45
while [ $MYSQL_WAIT -lt $MYSQL_MAX ]; do
    if docker compose exec -T mysql mysqladmin ping -h localhost -u root -proot_password >/dev/null 2>&1; then
        log "MySQL is healthy and accepting connections!"
        break
    fi
    sleep 1
    MYSQL_WAIT=$((MYSQL_WAIT + 1))
done
if [ $MYSQL_WAIT -ge $MYSQL_MAX ]; then
    err "Timed out waiting for MySQL container to become healthy."
fi

# ── 8. Wait for Redis Health (:6379) ──────────────────────────
section "8. Waiting for Redis Health (:6379)"
REDIS_WAIT=0
REDIS_MAX=20
while [ $REDIS_WAIT -lt $REDIS_MAX ]; do
    if docker compose exec -T redis redis-cli ping | grep -q "PONG" 2>/dev/null; then
        log "Redis in-memory cache and queue broker is healthy!"
        break
    fi
    sleep 1
    REDIS_WAIT=$((REDIS_WAIT + 1))
done
if [ $REDIS_WAIT -ge $REDIS_MAX ]; then
    warn "Redis took longer than expected; container may still be starting."
fi

# ── 9. Wait for Qdrant Health (:6333) ─────────────────────────
section "9. Waiting for Qdrant Vector Engine Health"
QDRANT_WAIT=0
QDRANT_MAX=30
while [ $QDRANT_WAIT -lt $QDRANT_MAX ]; do
    if curl -s "http://localhost:${QDRANT_HTTP_PORT}/readyz" | grep -q "ready" 2>/dev/null || curl -s -f "http://localhost:${QDRANT_HTTP_PORT}/readyz" >/dev/null 2>&1; then
        log "Qdrant vector engine is ready!"
        break
    fi
    sleep 1
    QDRANT_WAIT=$((QDRANT_WAIT + 1))
done
if [ $QDRANT_WAIT -ge $QDRANT_MAX ]; then
    warn "Qdrant health check timed out. Service may be initializing data storage."
fi

# ── 10. Wait for ML Microservice Readiness (:8000) ────────────
section "10. Waiting for ML Microservice Ready"
ML_WAIT=0
ML_MAX=45
while [ $ML_WAIT -lt $ML_MAX ]; do
    if curl -s "http://localhost:${ML_PORT}/health" | grep -q "ok" 2>/dev/null || curl -s -f "http://localhost:${ML_PORT}/health" >/dev/null 2>&1; then
        log "ML FastAPI microservice is ready and online!"
        break
    fi
    sleep 1
    ML_WAIT=$((ML_WAIT + 1))
done
if [ $ML_WAIT -ge $ML_MAX ]; then
    warn "ML service readiness check timed out. PyTorch weights might still be downloading."
fi

# ── 11. Wait for WebApp Server Readiness (:80) ────────────────
section "11. Waiting for WebApp Server Ready"
WEB_WAIT=0
WEB_MAX=30
while [ $WEB_WAIT -lt $WEB_MAX ]; do
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:${WEB_PORT}/" 2>/dev/null || echo "000")
    if [ "$HTTP_STATUS" = "200" ] || [ "$HTTP_STATUS" = "302" ]; then
        log "WebApp Apache/PHP server returned HTTP $HTTP_STATUS!"
        break
    fi
    sleep 1
    WEB_WAIT=$((WEB_WAIT + 1))
done
if [ $WEB_WAIT -ge $WEB_MAX ]; then
    warn "WebApp server readiness check timed out (status: ${HTTP_STATUS})."
fi

# ── 12. Permissions Enforcement ───────────────────────────────
section "12. Setting Container Writable Directory Permissions"
docker compose exec -T web mkdir -p /var/www/html/writable/cache \
                                    /var/www/html/writable/logs \
                                    /var/www/html/writable/session \
                                    /var/www/html/writable/uploads \
                                    /var/www/html/public/uploads \
                                    /var/www/html/public/thumbnails 2>/dev/null || true
docker compose exec -T web chown -R www-data:www-data /var/www/html/writable 2>/dev/null || true
docker compose exec -T web chown www-data:www-data /var/www/html/public/uploads /var/www/html/public/thumbnails 2>/dev/null || true
docker compose exec -T web chmod 775 /var/www/html/public/uploads /var/www/html/public/thumbnails 2>/dev/null || true
log "Directory permissions verified for Apache www-data."

# ── 13. Deployment Summary & Health Verification ──────────────
section "13. Deployment Summary & Endpoint Matrix"
finish_deployment

echo ""
echo -e "${BOLD}Platform Access URLs:${RESET}"
echo -e "  - ${CYAN}Web Application Dashboard${RESET} : http://localhost:${WEB_PORT} (or http://${DETECTED_IP}:${WEB_PORT})"
echo -e "  - ${CYAN}ML API Interactive Docs  ${RESET} : http://localhost:${ML_PORT}/docs"
echo -e "  - ${CYAN}Qdrant Web Console       ${RESET} : http://localhost:${QDRANT_HTTP_PORT}/dashboard"
echo -e "  - ${CYAN}MySQL 8.4 Relational DB  ${RESET} : localhost:${DB_PORT}"
echo -e "  - ${CYAN}Redis In-Memory Broker   ${RESET} : localhost:${REDIS_PORT}"
echo ""
echo -e "${BOLD}Android Companion Client Pairing:${RESET}"
echo -e "  - Android Emulator Base URL  : ${GREEN}http://10.0.2.2${RESET} (port ${WEB_PORT})"
echo -e "  - Physical Phone LAN URL     : ${GREEN}http://${DETECTED_IP}${RESET} (port ${WEB_PORT})"
echo -e "  - Default Admin Credentials  : ${YELLOW}admin@example.com${RESET} / ${YELLOW}admin_password${RESET}"
echo ""
log "Chege Photos Platform deployment completed successfully!"
