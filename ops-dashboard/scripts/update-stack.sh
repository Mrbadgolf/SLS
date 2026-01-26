#!/bin/bash
# Update Stack Script for Streaming Stack
# Used by OliveTin for safe stack updates
#
# Flow: Backup -> Pull -> Recreate -> Verify
#
# Usage: ./update-stack.sh <stack_name> [--no-backup]
# Example: ./update-stack.sh stremio
# Example: ./update-stack.sh stremio --no-backup

set -e

# Configuration
COMPOSE_DIR="${COMPOSE_DIR:-/opt/stremio-stack}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_FILE="/var/log/update-stack.log"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log() {
    echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
    echo "[ERROR] $1" >> "$LOG_FILE"
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
    echo "[WARN] $1" >> "$LOG_FILE"
}

# Get parameters
STACK_NAME="${1:-stremio}"
NO_BACKUP="${2:-}"
STACK_PATH="${COMPOSE_DIR}/${STACK_NAME}"
COMPOSE_FILE="$STACK_PATH/docker-compose.yml"

# Fallback to main compose directory if stack-specific doesn't exist
if [ ! -f "$COMPOSE_FILE" ]; then
    STACK_PATH="$COMPOSE_DIR"
    COMPOSE_FILE="$STACK_PATH/docker-compose.yml"
fi

if [ ! -f "$COMPOSE_FILE" ]; then
    error "Compose file not found: $COMPOSE_FILE"
    exit 1
fi

log "Starting update for stack: $STACK_NAME"
log "Compose file: $COMPOSE_FILE"

# Step 1: Backup (unless --no-backup)
if [ "$NO_BACKUP" != "--no-backup" ]; then
    log "Step 1/4: Creating backup before update..."
    if [ -f "$SCRIPT_DIR/backup.sh" ]; then
        "$SCRIPT_DIR/backup.sh" "$STACK_NAME" || {
            error "Backup failed! Aborting update."
            exit 1
        }
    else
        warn "Backup script not found, skipping backup"
    fi
else
    warn "Skipping backup (--no-backup flag set)"
fi

# Step 2: Pull latest images
log "Step 2/4: Pulling latest images..."
docker compose -f "$COMPOSE_FILE" pull 2>&1 | tee -a "$LOG_FILE"

PULL_EXIT_CODE=${PIPESTATUS[0]}
if [ $PULL_EXIT_CODE -ne 0 ]; then
    error "Image pull failed with exit code: $PULL_EXIT_CODE"
    exit $PULL_EXIT_CODE
fi

# Step 3: Recreate containers
log "Step 3/4: Recreating containers..."
docker compose -f "$COMPOSE_FILE" up -d --remove-orphans 2>&1 | tee -a "$LOG_FILE"

UP_EXIT_CODE=${PIPESTATUS[0]}
if [ $UP_EXIT_CODE -ne 0 ]; then
    error "Container recreation failed with exit code: $UP_EXIT_CODE"
    error "You may need to restore from backup!"
    exit $UP_EXIT_CODE
fi

# Step 4: Verify
log "Step 4/4: Verifying containers..."
sleep 5  # Give containers time to start

docker compose -f "$COMPOSE_FILE" ps

# Check for unhealthy containers
UNHEALTHY=$(docker compose -f "$COMPOSE_FILE" ps --format json | jq -r 'select(.Health == "unhealthy") | .Name' 2>/dev/null || true)

if [ -n "$UNHEALTHY" ]; then
    warn "Some containers are unhealthy:"
    echo "$UNHEALTHY"
    warn "Consider checking logs or rolling back"
else
    log "All containers appear healthy!"
fi

log ""
log "Update completed for $STACK_NAME"
log "If issues occur, restore from backup using: ./restore.sh $STACK_NAME"
