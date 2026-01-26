#!/bin/bash
# Backup Script for Streaming Stack
# Used by OliveTin for one-click backups
#
# Usage: ./backup.sh <stack_name>
# Example: ./backup.sh stremio

set -e

# Configuration
RESTIC_REPOSITORY="${RESTIC_REPOSITORY:-/opt/backups/restic}"
RESTIC_PASSWORD_FILE="${RESTIC_PASSWORD_FILE:-/opt/backups/.restic-password}"
COMPOSE_DIR="${COMPOSE_DIR:-/opt/stremio-stack}"
LOG_FILE="/var/log/backup.log"

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

# Get stack name from argument
STACK_NAME="${1:-stremio}"
STACK_PATH="${COMPOSE_DIR}/${STACK_NAME}"

# Fallback to main compose directory if stack-specific doesn't exist
if [ ! -d "$STACK_PATH" ]; then
    STACK_PATH="$COMPOSE_DIR"
fi

log "Starting backup for stack: $STACK_NAME"
log "Backup path: $STACK_PATH"

# Check if restic repository exists
if [ ! -d "$RESTIC_REPOSITORY" ]; then
    log "Initializing restic repository..."
    restic init --repo "$RESTIC_REPOSITORY" --password-file "$RESTIC_PASSWORD_FILE" 2>&1 || {
        error "Failed to initialize restic repository"
        exit 1
    }
fi

# Create timestamp tag
TIMESTAMP=$(date '+%Y%m%d-%H%M%S')

# Run backup
log "Creating backup with tags: stack:$STACK_NAME, timestamp:$TIMESTAMP"

restic backup "$STACK_PATH" \
    --repo "$RESTIC_REPOSITORY" \
    --password-file "$RESTIC_PASSWORD_FILE" \
    --tag "stack:$STACK_NAME" \
    --tag "timestamp:$TIMESTAMP" \
    --verbose 2>&1 | tee -a "$LOG_FILE"

BACKUP_EXIT_CODE=${PIPESTATUS[0]}

if [ $BACKUP_EXIT_CODE -eq 0 ]; then
    log "Backup completed successfully!"

    # Show snapshot info
    log "Latest snapshot:"
    restic snapshots \
        --repo "$RESTIC_REPOSITORY" \
        --password-file "$RESTIC_PASSWORD_FILE" \
        --tag "stack:$STACK_NAME" \
        --latest 1

    # Prune old snapshots (keep last 10, daily for 7 days, weekly for 4 weeks)
    log "Pruning old snapshots..."
    restic forget \
        --repo "$RESTIC_REPOSITORY" \
        --password-file "$RESTIC_PASSWORD_FILE" \
        --tag "stack:$STACK_NAME" \
        --keep-last 10 \
        --keep-daily 7 \
        --keep-weekly 4 \
        --prune 2>&1 | tee -a "$LOG_FILE"

    log "Backup process completed for $STACK_NAME"
else
    error "Backup failed with exit code: $BACKUP_EXIT_CODE"
    exit $BACKUP_EXIT_CODE
fi
