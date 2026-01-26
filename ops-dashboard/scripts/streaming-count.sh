#!/bin/bash
# Streaming Count Script
# Used by OliveTin to display current streaming activity
#
# Parses Traefik access logs to count unique IPs streaming in the last N minutes
#
# Usage: ./streaming-count.sh [minutes]
# Example: ./streaming-count.sh 5

# Configuration
TRAEFIK_LOG="${TRAEFIK_ACCESS_LOG:-/var/log/traefik/access.log}"
WINDOW_MINUTES="${1:-5}"

# Streaming patterns to match
PATTERNS="stream|play|video|hls|dash|manifest|stremio"

# Colors for output
GREEN='\033[0;32m'
CYAN='\033[0;36m'
NC='\033[0m'

# Check if log file exists
if [ ! -f "$TRAEFIK_LOG" ]; then
    echo "0 active streams (log file not found)"
    exit 0
fi

# Calculate cutoff time
CUTOFF=$(date -d "$WINDOW_MINUTES minutes ago" '+%d/%b/%Y:%H:%M:%S' 2>/dev/null || \
         date -v-${WINDOW_MINUTES}M '+%d/%b/%Y:%H:%M:%S' 2>/dev/null)

# Count unique IPs with streaming requests
# This handles Common Log Format
COUNT=$(awk -v cutoff="$CUTOFF" -v patterns="$PATTERNS" '
BEGIN {
    split(patterns, pat_arr, "|")
}
{
    # Extract timestamp and check if its recent enough
    match($0, /\[([^\]]+)\]/, ts)
    if (ts[1] >= cutoff) {
        # Check if request matches streaming patterns
        for (i in pat_arr) {
            if (tolower($0) ~ tolower(pat_arr[i])) {
                # Extract IP (first field in Common Log Format)
                ips[$1] = 1
                break
            }
        }
    }
}
END {
    print length(ips)
}
' "$TRAEFIK_LOG" 2>/dev/null)

# Handle JSON format logs as fallback
if [ "$COUNT" = "0" ] || [ -z "$COUNT" ]; then
    # Try parsing JSON format
    CUTOFF_EPOCH=$(date -d "$WINDOW_MINUTES minutes ago" '+%s' 2>/dev/null || \
                   date -v-${WINDOW_MINUTES}M '+%s' 2>/dev/null)

    COUNT=$(tail -n 10000 "$TRAEFIK_LOG" 2>/dev/null | \
            jq -r --arg cutoff "$CUTOFF_EPOCH" --arg patterns "$PATTERNS" '
                select(.StartUTC != null) |
                select((.StartUTC | split(".")[0] | strptime("%Y-%m-%dT%H:%M:%S") | mktime) > ($cutoff | tonumber)) |
                select(.RequestPath | test($patterns; "i")) |
                .ClientAddr | split(":")[0]
            ' 2>/dev/null | sort -u | wc -l)
fi

# Default to 0 if still empty
COUNT="${COUNT:-0}"

echo -e "${GREEN}$COUNT${NC} active streams (last $WINDOW_MINUTES min)"

# Show breakdown by endpoint if requested
if [ "${2:-}" = "--verbose" ] || [ "${2:-}" = "-v" ]; then
    echo ""
    echo -e "${CYAN}Breakdown by endpoint:${NC}"
    grep -iE "$PATTERNS" "$TRAEFIK_LOG" 2>/dev/null | \
        tail -n 1000 | \
        grep -oE "/(stream|play|video|hls|dash|manifest|stremio)[^\"' ]*" | \
        cut -d'?' -f1 | \
        sort | uniq -c | sort -rn | head -10
fi
