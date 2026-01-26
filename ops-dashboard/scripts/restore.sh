#!/bin/bash
# Restore Script for Streaming Stack
# Used by OliveTin for one-click restores
#
# Usage: ./restore.sh <stack_name> [snapshot_id]
# Example: ./restore.sh stremio latest
# Example: ./restore.sh stremio abc123

set -e

# Configuration
RESTIC_REPOSITORY="${RESTIC_REPOSITORY:-/opt/backups/restic}"
RESTIC_PASSWORD_FILE="${RESTIC_PASSWORD_FILE:-/opt/backups/.restic-password}"
COMPOSE_DIR="${COMPOSE_DIR:-/opt/stremio-stack}"
LOG_FILE="/var/log/restore.log"

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
SNAPSHOT_ID="${2:-latest}"
STACK_PATH="${COMPOSE_DIR}/${STACK_NAME}"

# Fallback to main compose directory if stack-specific doesn't exist
if [ ! -d "$STACK_PATH" ]; then
    STACK_PATH="$COMPOSE_DIR"
fi

log "Starting restore for stack: $STACK_NAME"
log "Target path: $STACK_PATH"
log "Snapshot: $SNAPSHOT_ID"

# Safety check - ensure stack is stopped
warn "WARNING: You should stop the stack before restoring!"
warn "Run: docker compose -f $STACK_PATH/docker-compose.yml down"
echo ""
read -p "Have you stopped the stack? (yes/no): " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    error "Restore aborted. Please stop the stack first."
    exit 1
fi

# If 'latest', find the most recent snapshot for this stack
if [ "$SNAPSHOT_ID" = "latest" ]; then
    log "Finding latest snapshot for stack: $STACK_NAME"

    SNAPSHOT_ID=$(restic snapshots \
        --repo "$RESTIC_REPOSITORY" \
        --password-file "$RESTIC_PASSWORD_FILE" \
        --tag "stack:$STACK_NAME" \
        --json | jq -r 'sort_by(.time) | last | .short_id')

    if [ -z "$SNAPSHOT_ID" ] || [ "$SNAPSHOT_ID" = "null" ]; then
        error "No snapshots found for stack: $STACK_NAME"
        exit 1
    fi

    log "Latest snapshot ID: $SNAPSHOT_ID"
fi

# Show snapshot details before restore
log "Snapshot details:"
restic snapshots \
    --repo "$RESTIC_REPOSITORY" \
    --password-file "$RESTIC_PASSWORD_FILE" \
    "$SNAPSHOT_ID"

echo ""
read -p "Proceed with restore? (yes/no): " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    log "Restore cancelled by user"
    exit 0
fi

# Create backup of current state before restore
log "Creating backup of current state before restore..."
TIMESTAMP=$(date '+%Y%m%d-%H%M%S')
restic backup "$STACK_PATH" \
    --repo "$RESTIC_REPOSITORY" \
    --password-file "$RESTIC_PASSWORD_FILE" \
    --tag "stack:$STACK_NAME" \
    --tag "pre-restore:$TIMESTAMP" \
    --verbose 2>&1 | tee -a "$LOG_FILE"

# Perform restore
log "Restoring from snapshot: $SNAPSHOT_ID"

restic restore "$SNAPSHOT_ID" \
    --repo "$RESTIC_REPOSITORY" \
    --password-file "$RESTIC_PASSWORD_FILE" \
    --target "/" \
    --verbose 2>&1 | tee -a "$LOG_FILE"

RESTORE_EXIT_CODE=${PIPESTATUS[0]}

if [ $RESTORE_EXIT_CODE -eq 0 ]; then
    log "Restore completed successfully!"
    log ""
    log "Next steps:"
    log "1. Review the restored files in: $STACK_PATH"
    log "2. Start the stack: docker compose -f $STACK_PATH/docker-compose.yml up -d"
    log "3. Verify services are working correctly"
else
    error "Restore failed with exit code: $RESTORE_EXIT_CODE"
    exit $RESTORE_EXIT_CODE
fi
