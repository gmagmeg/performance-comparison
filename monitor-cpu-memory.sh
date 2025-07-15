#!/bin/bash

# FrankenPHP Docker Container Performance Monitor
# Usage: ./monitor_frankenphp.sh [container_name_or_id] [interval_seconds] [output_file]

CONTAINER_ID=${1:-"58f28803edc1"}
INTERVAL=${2:-10}
OUTPUT_FILE=${3:-"frankenphp_stats.csv"}

echo "Starting FrankenPHP performance monitoring..."
echo "Container: $CONTAINER_ID"
echo "Interval: ${INTERVAL}s"
echo "Output: $OUTPUT_FILE"
echo ""

# Check if container exists and is running
if ! docker ps --format "table {{.ID}}" | grep -q "$CONTAINER_ID"; then
    echo "Error: Container $CONTAINER_ID is not running"
    exit 1
fi

# Create CSV header
echo "timestamp,cpu_percent,memory_usage,memory_percent,network_io,block_io" > "$OUTPUT_FILE"

echo "Monitoring started. Press Ctrl+C to stop."
echo "Log file: $OUTPUT_FILE"
echo ""

# Monitor loop
while true; do
    timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    stats=$(docker stats "$CONTAINER_ID" --no-stream --format '{{.CPUPerc}},{{.MemUsage}},{{.MemPerc}},{{.NetIO}},{{.BlockIO}}')
    echo "$timestamp,$stats" >> "$OUTPUT_FILE"
    
    # Display current stats
    echo "[$timestamp] CPU: $(echo $stats | cut -d',' -f1) | Memory: $(echo $stats | cut -d',' -f2)"
    
    sleep "$INTERVAL"
done