#!/usr/bin/env bash
# ==============================================================================
# Chege Photos Platform — Unified Diagnostic & Container Logging Utility
#
# Usage:
#   bash scripts/logs.sh               # Tail all containers live with color-coded tags
#   bash scripts/logs.sh <service>     # Tail specific service (web, ml, mysql, redis, qdrant, minio, dozzle)
#   bash scripts/logs.sh --errors      # Search across all containers for recent errors & tracebacks
#   bash scripts/logs.sh --dump        # Export a compressed diagnostic bundle for troubleshooting
#   bash scripts/logs.sh --help        # Show usage guide
# ==============================================================================

set -euo pipefail

# ── ANSI Colors ───────────────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
BOLD='\033[1m'
DIM='\033[2m'
RESET='\033[0m'

CONTAINERS=(
    "chege-photos-web"
    "chege-photos-ml"
    "chege-mysql"
    "chege-redis"
    "chege-qdrant"
    "chege-minio"
    "chege-minio-init"
    "chege-dozzle"
)

SERVICES=(
    "web"
    "ml"
    "mysql"
    "redis"
    "qdrant"
    "minio"
    "minio-init"
    "dozzle"
)

# ── Helper Functions ──────────────────────────────────────────────────────────
print_banner() {
    echo -e "${CYAN}${BOLD}╔══════════════════════════════════════════════════════════════╗${RESET}"
    echo -e "${CYAN}${BOLD}║   Chege Photos Platform — Diagnostic & Log Inspector        ║${RESET}"
    echo -e "${CYAN}${BOLD}╚══════════════════════════════════════════════════════════════╝${RESET}"
}

show_help() {
    print_banner
    echo -e "Usage: ${BOLD}bash scripts/logs.sh [command|service]${RESET}\n"
    echo -e "Commands:"
    echo -e "  ${GREEN}(no arguments)${RESET}     Live streaming tail of all stack containers"
    echo -e "  ${YELLOW}<service>${RESET}          Stream logs for a specific service (${CYAN}web, ml, mysql, redis, qdrant, minio, dozzle${RESET})"
    echo -e "  ${RED}-e, --errors${RESET}       Scan across all containers for errors, tracebacks, and warnings"
    echo -e "  ${BLUE}-d, --dump${RESET}         Generate a timestamped diagnostic bundle (.tar.gz) for sharing"
    echo -e "  ${MAGENTA}-h, --help${RESET}         Display this documentation"
    echo ""
    echo -e "Examples:"
    echo -e "  bash scripts/logs.sh web       # Follow WebApp PHP & Apache logs"
    echo -e "  bash scripts/logs.sh ml        # Follow ML inference & latency logs"
    echo -e "  bash scripts/logs.sh --errors  # Check system-wide failures"
    echo -e "  bash scripts/logs.sh --dump    # Create bundle in ./diagnostics/"
    exit 0
}

# ── Mode 1: Error Scanner ─────────────────────────────────────────────────────
scan_errors() {
    print_banner
    echo -e "${YELLOW}${BOLD}Scanning last 300 lines of each container for errors & exceptions...${RESET}\n"
    
    local FOUND_ERRORS=0
    local ERROR_PATTERN="ERROR|CRITICAL|FATAL|Exception|Traceback|Deadlock|\[FAIL\]|SQLSTATE|Failed to|500 Internal"

    for c in "${CONTAINERS[@]}"; do
        if docker ps -a --format '{{.Names}}' | grep -q "^${c}$"; then
            echo -e "${CYAN}${BOLD}── Container: ${c} ───────────────────────────────────────${RESET}"
            
            # Fetch recent logs and highlight matching lines
            local LOGS
            LOGS=$(docker logs --tail 300 "$c" 2>&1 || true)
            local MATCHES
            MATCHES=$(echo "$LOGS" | grep -Ei "$ERROR_PATTERN" || true)

            if [ -n "$MATCHES" ]; then
                echo "$MATCHES" | while IFS= read -r line; do
                    echo -e "  ${RED}✖${RESET} $line"
                done
                FOUND_ERRORS=$((FOUND_ERRORS + 1))
            else
                echo -e "  ${GREEN}✔ No recent errors or exceptions detected.${RESET}"
            fi
            echo ""
        fi
    done

    if [ "$FOUND_ERRORS" -eq 0 ]; then
        echo -e "${GREEN}${BOLD}All running containers report a clean operational state.${RESET}\n"
    else
        echo -e "${YELLOW}${BOLD}Identified issues in above containers. Use 'bash scripts/logs.sh <service>' to inspect context.${RESET}\n"
    fi
}

# ── Mode 2: Diagnostic Bundle Dump ────────────────────────────────────────────
dump_diagnostics() {
    print_banner
    local TIMESTAMP
    TIMESTAMP=$(date +%Y%m%d_%H%M%S)
    local DUMP_DIR="./diagnostics/dump_${TIMESTAMP}"
    local ARCHIVE="./diagnostics/chege-diagnostics-${TIMESTAMP}.tar.gz"

    mkdir -p "$DUMP_DIR"
    echo -e "${BLUE}Generating comprehensive diagnostic archive...${RESET}"

    # 1. Host Resources & Environment
    {
        echo "=== CHEGE PHOTOS SYSTEM DIAGNOSTICS ==="
        echo "Timestamp: $(date -u)"
        echo ""
        echo "--- Host OS & Kernel ---"
        uname -a 2>/dev/null || true
        echo ""
        echo "--- Memory Usage (free -m) ---"
        free -m 2>/dev/null || true
        echo ""
        echo "--- Disk Usage (df -h) ---"
        df -h 2>/dev/null || true
        echo ""
        echo "--- Docker Containers ---"
        docker ps -a --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" 2>/dev/null || true
        echo ""
        echo "--- Docker Image Sizes ---"
        docker images --filter "reference=*chege*" --filter "reference=*mysql*" --filter "reference=*redis*" --filter "reference=*qdrant*" --filter "reference=*minio*" --filter "reference=*dozzle*" --format "table {{.Repository}}\t{{.Tag}}\t{{.Size}}" 2>/dev/null || true
    } > "${DUMP_DIR}/system_overview.txt"

    # 2. Container Logs
    for c in "${CONTAINERS[@]}"; do
        if docker ps -a --format '{{.Names}}' | grep -q "^${c}$"; then
            echo -e "  Extracting logs for ${c}..."
            docker logs --tail 500 "$c" > "${DUMP_DIR}/${c}.log" 2>&1 || true
            docker inspect "$c" > "${DUMP_DIR}/${c}_inspect.json" 2>&1 || true
        fi
    done

    # 3. Create compressed tarball
    tar -czf "$ARCHIVE" -C "./diagnostics" "dump_${TIMESTAMP}"
    rm -rf "$DUMP_DIR"

    echo ""
    echo -e "${GREEN}${BOLD}✔ Diagnostic archive created successfully:${RESET}"
    echo -e "  ${BOLD}${ARCHIVE}${RESET} ($(du -h "$ARCHIVE" | cut -f1))"
    echo -e "\nAttach this archive when reporting platform issues or reviewing system state."
}

# ── Mode 3: Specific Service Tail ─────────────────────────────────────────────
tail_service() {
    local SVC="$1"
    local TARGET_CONTAINER=""

    case "$SVC" in
        web)         TARGET_CONTAINER="chege-photos-web" ;;
        ml)          TARGET_CONTAINER="chege-photos-ml" ;;
        mysql)       TARGET_CONTAINER="chege-mysql" ;;
        redis)       TARGET_CONTAINER="chege-redis" ;;
        qdrant)      TARGET_CONTAINER="chege-qdrant" ;;
        minio)       TARGET_CONTAINER="chege-minio" ;;
        minio-init)  TARGET_CONTAINER="chege-minio-init" ;;
        dozzle)      TARGET_CONTAINER="chege-dozzle" ;;
        *)
            echo -e "${RED}Unknown service: '${SVC}'${RESET}"
            echo -e "Available services: ${CYAN}${SERVICES[*]}${RESET}"
            exit 1
            ;;
    esac

    print_banner
    echo -e "${GREEN}Streaming live logs for ${BOLD}${TARGET_CONTAINER}${RESET} (Press Ctrl+C to exit)..."
    echo -e "${DIM}------------------------------------------------------------${RESET}"
    docker logs -f --tail 100 "$TARGET_CONTAINER"
}

# ── Mode 4: All Containers Multi-Tail ─────────────────────────────────────────
tail_all() {
    print_banner
    echo -e "${GREEN}Streaming live multi-container logs (Press Ctrl+C to exit)...${RESET}"
    echo -e "${DIM}------------------------------------------------------------${RESET}"
    docker compose logs -f --tail 50
}

# ── Main Entrypoint ───────────────────────────────────────────────────────────
ARG="${1:-}"

case "$ARG" in
    "" )
        tail_all
        ;;
    -h|--help|help)
        show_help
        ;;
    -e|--errors|errors)
        scan_errors
        ;;
    -d|--dump|dump)
        dump_diagnostics
        ;;
    *)
        tail_service "$ARG"
        ;;
esac
