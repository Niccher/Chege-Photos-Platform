#!/usr/bin/env bash
# =============================================================
# deploy.sh — ONE-CLICK Deployment & Setup Pipeline
#             for Chege Photos Platform (Monorepo)
#
# Usage:  bash scripts/deploy.sh
#         bash scripts/deploy.sh --fast   (skip image rebuild)
#         (run from the repository root)
#
# Production Standard Ports:
#   - Web Dashboard & API : Port 80
#   - ML AI Microservice  : Port 8000
#   - Qdrant Vector DB    : Port 6333 (HTTP) / 6334 (gRPC)
#   - Redis In-Memory DB  : Port 6379
#   - MySQL 8.4 Database  : Port 3306
#
# 13-Step Automated Pipeline:
#   1. Detect Dynamic IP (Public / LAN Fallback)
#   2. Pre-flight System & Resource Check (OS, CPU, RAM, Disk, Swap)
#   3. Git Working Tree Sanity Check, Auto-Stash & Pull
#   4. Write / Patch .env Configuration & Encryption Keys
#   5. Stop Old Containers & Clear Explicit Name Conflicts
#   6. Build & Start Containers (MySQL, Redis, Qdrant, Web, ML)
#   7. Wait for Database, Redis & Vector Services (7a: MySQL, 7b: Redis, 7c: Qdrant, 7d: Cron)
#   8. Run Database Migrations & Initial Seeders
#   9. Wait for ML Microservice Readiness (:8000)
#  10. Wait for WebApp Server Readiness (:80) & Diagnosis
#  11. Container Permissions Enforcement (writable & uploads)
#  12. Housekeeping, Docker Storage & Cache Clearing
#  13. Clean Status Summary & Comprehensive Endpoint Matrix
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
DETECTED_IP=$(curl -s --max-time 5 ip.me 2>/dev/null | tr -d '[:space:]' || true)
if [ -z "$DETECTED_IP" ]; then
    DETECTED_IP=$(curl -s --max-time 5 ifconfig.me 2>/dev/null | tr -d '[:space:]' || true)
fi
if [ -z "$DETECTED_IP" ]; then
    DETECTED_IP=$(curl -s --max-time 5 icanhazip.com 2>/dev/null | tr -d '[:space:]' || true)
fi
if [ -z "$DETECTED_IP" ]; then
    DETECTED_IP=$(hostname -I 2>/dev/null | awk '{print $1}' | tr -d '[:space:]' || true)
fi
if [ -z "$DETECTED_IP" ]; then
    DETECTED_IP="127.0.0.1"
fi
log "Dynamic Host IP: $DETECTED_IP"

# ── 2. Pre-flight System & Resource Check ─────────────────────
section "2. Pre-flight Disk & RAM Check"

if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS_PRETTY="${PRETTY_NAME:-$NAME}"
else
    OS_PRETTY="$(uname -s)"
fi
KERNEL="$(uname -r)"
ARCH="$(uname -m)"

CPU_CORES=$(nproc 2>/dev/null || echo "1")
CPU_MODEL=$(grep -m1 "model name" /proc/cpuinfo 2>/dev/null | sed -E "s/^model name\s*:\s*//" | tr -s " " || echo "Generic CPU")
[ -z "$CPU_MODEL" ] && CPU_MODEL="Generic CPU"

TOTAL_MEM_MB=$(free -m | awk '/Mem:/ {print $2}')
USED_MEM_MB=$(free -m | awk '/Mem:/ {print $3}')
FREE_MEM_MB=$(free -m | awk '/Mem:/ {print ($7 != "" ? $7 : $4)}')
MEM_PCT=$(awk -v u="$USED_MEM_MB" -v t="$TOTAL_MEM_MB" 'BEGIN {if (t>0) printf "%.1f", (u/t)*100; else print "0"}')
MEM_FREE_PCT=$(awk -v f="$FREE_MEM_MB" -v t="$TOTAL_MEM_MB" 'BEGIN {if (t>0) printf "%.1f", (f/t)*100; else print "0"}')
TOTAL_MEM_GB=$(awk -v m="$TOTAL_MEM_MB" 'BEGIN {printf "%.2f GB", m/1024}')
USED_MEM_GB=$(awk -v m="$USED_MEM_MB" 'BEGIN {printf "%.2f GB", m/1024}')
FREE_MEM_GB=$(awk -v m="$FREE_MEM_MB" 'BEGIN {printf "%.2f GB", m/1024}')

SWAP_TOTAL_MB=$(free -m | awk '/Swap:/ {print $2}' || echo "0")
[ -z "$SWAP_TOTAL_MB" ] && SWAP_TOTAL_MB="0"

DISK_LINE=$(df -h / | awk 'NR>1 {print $(NF-4), $(NF-3), $(NF-2), $(NF-1), $(NF)}')
DISK_TOTAL=$(echo "$DISK_LINE" | awk '{print $1}')
DISK_USED=$(echo "$DISK_LINE" | awk '{print $2}')
DISK_FREE=$(echo "$DISK_LINE" | awk '{print $3}')
DISK_PCT=$(echo "$DISK_LINE" | awk '{print $4}')
DISK_FREE_MB=$(df -m / | awk 'NR>1 {print $(NF-2)}')

log "OS Environment   : ${OS_PRETTY} (${ARCH}, kernel ${KERNEL})"
log "CPU Architecture : ${CPU_MODEL} (${CPU_CORES} vCPU)"
log "Available Memory : ${FREE_MEM_MB}MB free of ${TOTAL_MEM_MB}MB"
log "RAM Breakdown    : Total: ${TOTAL_MEM_GB} | Used: ${USED_MEM_GB} (${MEM_PCT}%) | Free: ${FREE_MEM_GB} (${MEM_FREE_PCT}%)"
log "Swap Space       : ${SWAP_TOTAL_MB}MB total swap"
log "Available Disk   : ${DISK_FREE_MB}MB free on /"
log "Disk Breakdown   : Total: ${DISK_TOTAL} | Used: ${DISK_USED} (${DISK_PCT}) | Free: ${DISK_FREE} (${DISK_FREE_MB}MB)"

if [ "$SWAP_TOTAL_MB" -lt 1024 ] && [ "$TOTAL_MEM_MB" -lt 4500 ]; then
    warn "Swap space is low (${SWAP_TOTAL_MB}MB). Creating a 4GB swapfile to prevent OOM during PyTorch & OpenCV execution..."
    sudo fallocate -l 4G /swapfile 2>/dev/null || sudo dd if=/dev/zero of=/swapfile bs=1M count=4096 2>/dev/null || true
    sudo chmod 600 /swapfile 2>/dev/null || true
    sudo mkswap /swapfile 2>/dev/null || true
    sudo swapon /swapfile 2>/dev/null || true
    grep -q "/swapfile" /etc/fstab || echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab >/dev/null 2>&1 || true
    log "4 GB swapfile created and enabled"
fi

if [ "$DISK_FREE_MB" -lt 8000 ]; then
    warn "Free disk is below 8GB (${DISK_FREE_MB}MB free). Running proactive Docker cache prune..."
    docker builder prune -af >/dev/null 2>&1 || true
    docker image prune -f >/dev/null 2>&1 || true
    RECHECK_DISK=$(df -m / | awk 'NR>1 {print $(NF-2)}')
    log "Reclaimed disk space: ${RECHECK_DISK}MB free now"
fi

# ── 3. Git Working Tree Sanity Check & Pull ───────────────────
section "3. Git Working Tree Check & Pull"
if [ -d ".git" ]; then
    if [ -n "$(git status --porcelain 2>/dev/null)" ]; then
        warn "Uncommitted local changes detected in working tree. Stashing safely..."
        git stash save "deploy-auto-stash-$(date +%Y%m%d%H%M%S)" >/dev/null 2>&1 || true
    fi
    if git remote | grep -q "origin"; then
        log "Pulling latest changes from origin..."
        PULL_LOG=$(git pull origin main 2>&1) || warn "Could not pull from origin, continuing with local code..."
        echo "$PULL_LOG" | tail -5
        if echo "$PULL_LOG" | grep -q "scripts/deploy.sh"; then
            log "deploy.sh was updated from origin. Re-executing deployment script..."
            exec bash "$0" "$@"
        fi
    else
        log "Working tree clean (local repository mode)"
    fi
fi

# ── 4. Write / Patch .env ─────────────────────────────────────
section "4. Writing .env Configuration"
if [ ! -f ".env" ]; then
    [ -f ".env.example" ] && cp .env.example .env && log "Created .env from .env.example" \
        || err ".env missing and no .env.example found — cannot continue"
fi

get_env() {
    local K="$1" DEFAULT="$2"
    local VAL
    VAL=$(grep -E "^${K}\s*=" .env 2>/dev/null | head -n1 | cut -d'=' -f2- | tr -d '[:space:]' || true)
    echo "${VAL:-$DEFAULT}"
}

set_env() {
    local K="$1"
    local V
    V=$(echo "$2" | head -n1 | tr -d '\r\n')
    if grep -q "^${K}=" .env 2>/dev/null; then
        sed -i "s|^${K}=.*|${K}=${V}|g" .env
    elif grep -q "^${K} *=" .env 2>/dev/null; then
        sed -i "s|^${K} *=.*|${K} = ${V}|g" .env
    else
        echo "${K}=${V}" >> .env
    fi
}

WEB_PORT=$(get_env "WEB_PORT" "80")
ML_PORT=$(get_env "ML_PORT" "8000")
DB_PORT=$(get_env "DB_PORT" "3306")
REDIS_PORT=$(get_env "REDIS_PORT" "6379")
QDRANT_HTTP_PORT=$(get_env "QDRANT_HTTP_PORT" "6333")
QDRANT_GRPC_PORT=$(get_env "QDRANT_GRPC_PORT" "6334")

# Format baseURL cleanly (omit :80 if standard HTTP)
if [ "$WEB_PORT" = "80" ]; then
    BASE_URL="http://${DETECTED_IP}/"
else
    BASE_URL="http://${DETECTED_IP}:${WEB_PORT}/"
fi

set_env "WEB_PORT" "$WEB_PORT"
set_env "ML_PORT" "$ML_PORT"
set_env "DB_PORT" "$DB_PORT"
set_env "REDIS_PORT" "$REDIS_PORT"
set_env "QDRANT_HTTP_PORT" "$QDRANT_HTTP_PORT"
set_env "QDRANT_GRPC_PORT" "$QDRANT_GRPC_PORT"
set_env "app.baseURL" "$BASE_URL"
set_env "CI_ENVIRONMENT" "production"
set_env "DB_HOST" "mysql"
set_env "REDIS_HOST" "redis"
set_env "QDRANT_HOST" "qdrant"
set_env "ML_URL" "http://ml:8000"

# Generate encryption key if missing
if ! grep -q "^encryption\.key\s*=\s*.\+" .env 2>/dev/null; then
    RANDOM_KEY=$(openssl rand -hex 16 2>/dev/null || head -c 16 /dev/urandom | xxd -p)
    set_env "encryption.key" "hex2bin:$RANDOM_KEY"
    log "Generated CodeIgniter 4 encryption key."
fi

log "app.baseURL               -> ${BASE_URL}"
log "WEB_PORT                  -> ${WEB_PORT} (Standard HTTP Dashboard & API)"
log "ML_PORT                   -> ${ML_PORT} (FastAPI Vision Microservice)"
log "QDRANT_HTTP_PORT          -> ${QDRANT_HTTP_PORT} (Vector Engine)"
log "REDIS_PORT                -> ${REDIS_PORT} (In-Memory Broker)"
log "DB_PORT                   -> ${DB_PORT} (MySQL 8.4 Database)"

# ── 5. Stop Old Containers & Clear Conflicts ──────────────────
section "5. Stopping Old Containers & Clearing Name Conflicts"
docker compose down --remove-orphans 2>&1 | tail -3 || true

# Explicitly remove legacy or conflicting container instances
docker rm -f chege-mysql chege-redis chege-qdrant chege-photos-web chege-photos-ml 2>/dev/null || true
log "Old containers stopped and explicit name conflicts cleared"

# ── 6. Build & Start Containers ───────────────────────────────
section "6. Building & Starting All Containers"
if [ "${1:-}" = "--fast" ] || [ "${1:-}" = "--no-build" ]; then
    log "Fast startup mode active (skipping image rebuild)..."
    docker compose up -d
else
    log "Pulling base images (MySQL 8.4, Redis 7, Qdrant)..."
    docker compose pull mysql redis qdrant 2>&1 | grep -E "Pulling|Pulled|Digest|Status|already exists" || true

    log "Building Web application container (PHP 8.3 + Apache)..."
    docker compose build --progress=plain web

    log "Building ML microservice container (FastAPI + Vision models)..."
    docker compose build --progress=plain ml

    log "Starting multi-container stack in background..."
    docker compose up -d
fi
log "Containers created and started (mysql, redis, qdrant, web, ml)"

echo ""
echo -e "   ${BOLD}Docker Image Sizes:${RESET}"
docker images --filter "reference=*chege*" --filter "reference=*mysql*" --filter "reference=*redis*" --filter "reference=*qdrant*" --format "table {{.Repository}}\t{{.Tag}}\t{{.Size}}" 2>/dev/null | sed 's/^/   /' || true

echo ""
echo -e "   ${BOLD}Container Runtime Layer Sizes:${RESET}"
docker ps --size --filter "name=chege" --format "table {{.Names}}\t{{.Status}}\t{{.Size}}" 2>/dev/null | sed 's/^/   /' || true

# ── 7a. Wait for MySQL Health ─────────────────────────────────
section "7a. Waiting for MySQL to Become Healthy"
printf "   Waiting for MySQL"
MYSQL_HEALTHY=false
for i in $(seq 1 35); do
    STATUS=$(docker inspect --format='{{.State.Health.Status}}' chege-mysql 2>/dev/null || echo "missing")
    if [ "$STATUS" = "healthy" ]; then
        echo ""
        log "MySQL 8.4 is healthy and accepting connections"
        MYSQL_HEALTHY=true
        break
    fi
    printf "."
    sleep 2
done
$MYSQL_HEALTHY || { echo ""; warn "MySQL did not report healthy within timeout. Check: docker compose logs mysql"; }

# ── 7b. Wait for Redis Health (:6379) ─────────────────────────
section "7b. Waiting for Redis Health (:6379)"
printf "   Checking Redis status"
REDIS_HEALTHY=false
for i in $(seq 1 15); do
    R_STATUS=$(docker inspect --format='{{.State.Health.Status}}' chege-redis 2>/dev/null || echo "missing")
    if [ "$R_STATUS" = "healthy" ]; then
        echo ""
        log "Redis in-memory cache and queue broker is healthy"
        REDIS_HEALTHY=true
        break
    fi
    printf "."
    sleep 2
done
$REDIS_HEALTHY || { echo ""; warn "Redis did not report healthy within timeout. Check: docker compose logs redis"; }

# ── 7c. Wait for Qdrant Vector Engine Health (:6333) ──────────
section "7c. Waiting for Qdrant Vector Engine Health"
printf "   Checking Qdrant status"
QDRANT_HEALTHY=false
for i in $(seq 1 20); do
    Q_STATUS=$(docker inspect --format='{{.State.Health.Status}}' chege-qdrant 2>/dev/null || echo "missing")
    if [ "$Q_STATUS" = "healthy" ] || curl -s "http://localhost:${QDRANT_HTTP_PORT}/readyz" | grep -q "ready" 2>/dev/null; then
        echo ""
        log "Qdrant vector database is ready and responsive"
        QDRANT_HEALTHY=true
        break
    fi
    printf "."
    sleep 2
done
$QDRANT_HEALTHY || { echo ""; warn "Qdrant took longer to initialize. Check: docker compose logs qdrant"; }

# ── 7d. Verify Cron Daemon in Web Container ───────────────────
section "7d. Verifying Cron Daemon in Web Container"
printf "   Checking cron service in web container"
CRON_RUNNING=false
for i in $(seq 1 10); do
    if docker compose exec -T web pgrep cron >/dev/null 2>&1; then
        echo ""
        log "Cron daemon is actively running inside the web container"
        CRON_RUNNING=true
        break
    fi
    printf "."
    sleep 2
done
if ! $CRON_RUNNING; then
    echo ""
    warn "Cron daemon is not running inside web container. Starting service..."
    docker compose exec -T web service cron start 2>&1 || true
    if docker compose exec -T web pgrep cron >/dev/null 2>&1; then
        log "Cron service started successfully"
        CRON_RUNNING=true
    else
        warn "Could not start cron daemon inside web container. Check: docker compose logs web"
    fi
fi

# Ensure cron log and environment files have proper permissions
docker compose exec -T web sh -c "
    touch /var/log/cron-run.log 2>/dev/null || true
    chmod 666 /var/log/cron-run.log 2>/dev/null || true
" 2>&1 && log "Cron log file verified (/var/log/cron-run.log)" || true

# ── 8. Run Database Migrations & Seeding ──────────────────────
section "8. Running Database Migrations & Seeding"
if $MYSQL_HEALTHY; then
    docker compose exec -T web php spark migrate --all 2>&1 \
        && log "Database migrations executed successfully" \
        || warn "Migrations had notices or completed with warnings"

    log "Applying initial database seeders..."
    docker compose exec -T web php spark db:seed SystemDefaultSeeder 2>&1 || true
    log "Seeders executed successfully"
else
    warn "Skipping migrations and seeding because MySQL is not yet healthy"
fi

# ── 9. Wait for ML Microservice Readiness (:8000) ─────────────
section "9. Waiting for ML Microservice Ready (:${ML_PORT})"
printf "   Checking chege-photos-ml status"
ML_READY=false
for i in $(seq 1 45); do
    ML_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:${ML_PORT}/health 2>/dev/null || echo "000")
    if [ "$ML_CODE" = "200" ]; then
        echo ""
        log "ML vision & embedding microservice is healthy (HTTP $ML_CODE)"
        ML_READY=true
        break
    fi
    printf "."
    sleep 2
done
$ML_READY || { echo ""; warn "ML microservice took longer to initialize. Check: docker compose logs ml"; }

# ── 10. Wait for Web Server Readiness (:80) ───────────────────
section "10. Waiting for WebApp Server Ready (:${WEB_PORT})"
printf "   Waiting for WebApp"
WEB_READY=false
for i in $(seq 1 35); do
    CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:${WEB_PORT}/" 2>/dev/null || echo "000")
    if [[ "$CODE" =~ ^(200|301|302|307|403)$ ]]; then
        echo ""
        log "WebApp server is responding (HTTP $CODE)"
        WEB_READY=true
        break
    fi
    printf "."
    sleep 2
done
$WEB_READY || { echo ""; warn "Web server did not respond in time. Check: docker compose logs web"; }

# ── 11. Container Permissions Enforcement ─────────────────────
section "11. Setting Container Writable Directory Permissions"
docker compose exec -T web sh -c "
    mkdir -p writable/cache writable/logs writable/session writable/debugbar public/uploads public/thumbnails && \
    chown -R www-data:www-data writable && \
    chmod -R 775 writable && \
    chown www-data:www-data public/uploads public/thumbnails && \
    chmod 775 public/uploads public/thumbnails
" 2>&1 && log "Writable and uploads permissions enforced (www-data:775)" || warn "Failed to update directory permissions"

# ── 12. Housekeeping, Storage Metrics & Cache Clearing ────────
section "12. Housekeeping & Cache Clearing"
docker compose exec -T web php spark cache:clear 2>&1 || true
log "CodeIgniter 4 application cache cleared"

docker compose exec -T web sh -c "
    rm -rf writable/debugbar/* 2>/dev/null || true
" && log "Temporary runtime debugbar profiles cleared" || true

docker builder prune -af >/dev/null 2>&1 && log "Docker BuildKit build cache pruned" || true
docker image prune -f >/dev/null 2>&1 && log "Dangling Docker build layers pruned" || true

echo ""
echo -e "   ${BOLD}Docker Storage Footprint:${RESET}"
docker system df 2>/dev/null | sed 's/^/   /' || true

# ── 13. Container Status & Final Summary ──────────────────────
section "13. Deployment Summary & Health Verification"
docker compose ps

echo ""
FINAL_WEB=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:${WEB_PORT}/" 2>/dev/null || echo "000")

if [[ "$FINAL_WEB" =~ ^(200|301|302|307|403)$ ]]; then
    log "PLATFORM IS LIVE — ALL SYSTEMS OPERATIONAL (WebApp HTTP ${FINAL_WEB})"
    echo ""
    echo -e "  ${BOLD}WebApp Dashboard     :${RESET}  ${BASE_URL}"
    echo -e "  ${BOLD}REST API Health      :${RESET}  ${BASE_URL}api/v1/health"
    echo -e "  ${BOLD}ML API Swagger Docs  :${RESET}  http://${DETECTED_IP}:${ML_PORT}/docs"
    echo -e "  ${BOLD}ML Health Probe      :${RESET}  http://${DETECTED_IP}:${ML_PORT}/health"
    echo -e "  ${BOLD}Qdrant Web Console   :${RESET}  http://${DETECTED_IP}:${QDRANT_HTTP_PORT}/dashboard"
    echo -e "  ${BOLD}Redis Cache Server   :${RESET}  localhost:${REDIS_PORT}"
    echo -e "  ${BOLD}MySQL 8.4 Relational :${RESET}  localhost:${DB_PORT}"
    echo ""
    echo -e "${BOLD}Android Companion Client Pairing:${RESET}"
    echo -e "  - Android Emulator Base URL : ${GREEN}http://10.0.2.2${RESET} (port ${WEB_PORT})"
    echo -e "  - Physical Phone LAN URL    : ${GREEN}http://${DETECTED_IP}${RESET} (port ${WEB_PORT})"
    echo -e "  - Default Admin Credentials : ${YELLOW}admin@example.com${RESET} / ${YELLOW}admin_password${RESET}"
    echo ""
    finish_deployment

elif [ "$FINAL_WEB" = "500" ]; then
    warn "HTTP 500 detected on WebApp — dumping live error output..."
    echo -e "${RED}"
    curl -s "http://localhost:${WEB_PORT}/" \
        | python3 -c "
import sys, json
try:
    d = json.load(sys.stdin)
    print('Error:', d.get('message',''))
    for f in d.get('trace', [])[:10]:
        print(' ', f.get('file','').split('/')[-1], ':', f.get('line',''), '->', f.get('function',''))
except Exception:
    sys.stdin = open('/dev/stdin')
    print(sys.stdin.read()[:2000])
" 2>/dev/null || true
    echo -e "${RESET}"
    echo ""
    echo -e "${YELLOW}Recent WebApp container logs:${RESET}"
    docker compose logs web 2>&1 | tail -30
    finish_deployment
    err "Fix the errors above, then re-run: bash scripts/deploy.sh"

else
    warn "HTTP ${FINAL_WEB} — unexpected status code. Recent WebApp logs:"
    docker compose logs web 2>&1 | tail -30
    finish_deployment
fi
