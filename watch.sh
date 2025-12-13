#!/bin/bash

# Git Presented - Development Watch Mode
# Watches for changes in source files and the configured git repository
# Automatically rebuilds and serves the site

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Load .env file if it exists
if [ -f .env ]; then
    export $(grep -v '^#' .env | xargs)
fi

# Determine the git repo to watch
GIT_REPO_PATH="${GIT_REPO_PATH:-.}"

# Resolve to absolute path
if [[ "$GIT_REPO_PATH" != /* ]]; then
    GIT_REPO_PATH="$(cd "$GIT_REPO_PATH" 2>/dev/null && pwd)"
fi

# Check if fswatch is installed
if ! command -v fswatch &> /dev/null; then
    echo -e "${RED}Error: fswatch is not installed.${NC}"
    echo ""
    echo "Install it with:"
    echo "  macOS:  brew install fswatch"
    echo "  Linux:  apt-get install fswatch  (or use inotifywait)"
    echo ""
    exit 1
fi

# Check if the git repo exists
if [ ! -d "$GIT_REPO_PATH/.git" ]; then
    echo -e "${RED}Error: Git repository not found at: $GIT_REPO_PATH${NC}"
    exit 1
fi

echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║${NC}           ${GREEN}Git Presented - Watch Mode${NC}                       ${BLUE}║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  ${YELLOW}Source files:${NC}  $(pwd)/source"
echo -e "  ${YELLOW}Git repo:${NC}      $GIT_REPO_PATH"
echo -e "  ${YELLOW}Server:${NC}        http://localhost:8000"
echo ""
echo -e "${GREEN}Starting initial build...${NC}"
echo ""

# Initial build
./vendor/bin/jigsaw build

echo ""
echo -e "${GREEN}Starting server and file watcher...${NC}"
echo -e "${YELLOW}Press Ctrl+C to stop${NC}"
echo ""

# Start the PHP server in background
php -S localhost:8000 -t build_local &
SERVER_PID=$!

# Function to cleanup on exit
cleanup() {
    echo ""
    echo -e "${YELLOW}Shutting down...${NC}"
    kill $SERVER_PID 2>/dev/null || true
    exit 0
}

trap cleanup INT TERM

# Debounce: track last build time to prevent rapid rebuilds
LAST_BUILD=0
DEBOUNCE_SECONDS=2

# Build function with debounce
do_build() {
    local CHANGED_PATH="$1"
    local NOW=$(date +%s)
    local DIFF=$((NOW - LAST_BUILD))

    # Skip if we just built
    if [ $DIFF -lt $DEBOUNCE_SECONDS ]; then
        return
    fi

    LAST_BUILD=$NOW
    echo -e "${BLUE}[$(date +%H:%M:%S)]${NC} Changed: ${YELLOW}$CHANGED_PATH${NC}"
    echo -e "${BLUE}[$(date +%H:%M:%S)]${NC} Rebuilding..."
    if ./vendor/bin/jigsaw build 2>&1 | grep -v "^WARN"; then
        echo -e "${GREEN}[$(date +%H:%M:%S)]${NC} Build complete ✓"
    else
        echo -e "${RED}[$(date +%H:%M:%S)]${NC} Build failed ✗"
    fi
}

# Watch directories:
# 1. source/ - template and asset changes
# 2. config.php, helpers.php, bootstrap.php - config changes
# 3. app/ - PHP class changes
# 4. Git repo's refs - for new commits, branch changes (more specific than .git/)

WATCH_PATHS=(
    "$(pwd)/source/_layouts"
    "$(pwd)/source/_partials"
    "$(pwd)/source/_assets"
    "$(pwd)/source/css"
    "$(pwd)/source/js"
    "$(pwd)/source/assets"
    "$(pwd)/source/index.blade.php"
    "$(pwd)/source/branches.blade.php"
    "$(pwd)/config.php"
    "$(pwd)/helpers.php"
    "$(pwd)/bootstrap.php"
    "$(pwd)/app"
)

# Only watch git refs and HEAD, not the entire .git directory
# This avoids triggering on index changes, logs, etc.
GIT_WATCH_PATHS=(
    "$GIT_REPO_PATH/.git/refs"
    "$GIT_REPO_PATH/.git/HEAD"
)

# Add git paths only if they exist
for path in "${GIT_WATCH_PATHS[@]}"; do
    if [ -e "$path" ]; then
        WATCH_PATHS+=("$path")
    fi
done

# Use fswatch with:
# -l 1: latency of 1 second (coalesce rapid changes)
# --exclude: ignore build output and cache directories
# Note: removed -o to get file paths instead of event counts
fswatch -l 1 \
    --exclude "cache" \
    --exclude "\.git/index" \
    --exclude "\.git/logs" \
    --exclude "\.git/objects" \
    "${WATCH_PATHS[@]}" | while read changed_path; do
    do_build "$changed_path"
done
