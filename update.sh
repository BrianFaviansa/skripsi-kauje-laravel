#!/bin/bash
# ===========================================
# Kauje - Update VPS Deployment Script
# ===========================================
# Usage: ./update.sh [options]
# Options:
#   --rebuild     Force rebuild Docker images
#   --laravel     Update Laravel only
#   --nextjs      Update Next.js only
#   --no-restart  Pull only, don't restart containers
# ===========================================

set -e

# Configuration
LARAVEL_REPO="skripsi-kauje-laravel"
NEXTJS_REPO="skripsi-kauje-nextjs"
DEPLOY_DIR="$HOME/skripsi"

# Parse arguments
REBUILD=false
LARAVEL_ONLY=false
NEXTJS_ONLY=false
NO_RESTART=false

for arg in "$@"; do
    case $arg in
        --rebuild)
            REBUILD=true
            shift
            ;;
        --laravel)
            LARAVEL_ONLY=true
            shift
            ;;
        --nextjs)
            NEXTJS_ONLY=true
            shift
            ;;
        --no-restart)
            NO_RESTART=true
            shift
            ;;
    esac
done

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}=========================================="
echo "    Kauje - Update Script"
echo -e "==========================================${NC}"
echo ""

cd "$DEPLOY_DIR"

# Function to update a repository
update_repo() {
    local repo_name=$1
    echo -e "${YELLOW}📦 Updating ${repo_name}...${NC}"
    
    if [ ! -d "$repo_name" ]; then
        echo -e "${RED}❌ Repository ${repo_name} not found!${NC}"
        return 1
    fi
    
    cd "$repo_name"
    
    # Stash any local changes
    git stash 2>/dev/null || true
    
    # Get current branch
    BRANCH=$(git rev-parse --abbrev-ref HEAD)
    
    # Fetch and pull
    git fetch origin
    
    # Check if there are updates
    LOCAL=$(git rev-parse HEAD)
    REMOTE=$(git rev-parse origin/$BRANCH)
    
    if [ "$LOCAL" = "$REMOTE" ]; then
        echo -e "${GREEN}   ✅ Already up to date${NC}"
        cd ..
        return 0
    fi
    
    # Pull changes
    git pull origin $BRANCH
    echo -e "${GREEN}   ✅ Updated to latest${NC}"
    
    cd ..
    return 2  # Return 2 to indicate changes were pulled
}

CHANGES_DETECTED=false

# Update Laravel
if [ "$NEXTJS_ONLY" = false ]; then
    update_repo "$LARAVEL_REPO"
    if [ $? -eq 2 ]; then
        CHANGES_DETECTED=true
        LARAVEL_CHANGED=true
    fi
fi

# Update Next.js
if [ "$LARAVEL_ONLY" = false ]; then
    update_repo "$NEXTJS_REPO"
    if [ $? -eq 2 ]; then
        CHANGES_DETECTED=true
        NEXTJS_CHANGED=true
    fi
fi

echo ""

# Handle container restart/rebuild
if [ "$NO_RESTART" = true ]; then
    echo -e "${YELLOW}⏭️  Skipping container restart (--no-restart)${NC}"
elif [ "$REBUILD" = true ]; then
    echo -e "${YELLOW}🔨 Force rebuilding all containers...${NC}"
    docker compose build --no-cache
    docker compose up -d
    echo -e "${GREEN}✅ All containers rebuilt and restarted${NC}"
elif [ "$CHANGES_DETECTED" = true ]; then
    echo -e "${YELLOW}🔄 Changes detected, rebuilding affected containers...${NC}"
    
    if [ "$LARAVEL_CHANGED" = true ] && [ "$NEXTJS_CHANGED" = true ]; then
        docker compose build laravel nextjs
        docker compose up -d laravel nextjs
    elif [ "$LARAVEL_CHANGED" = true ]; then
        docker compose build laravel
        docker compose up -d laravel
    elif [ "$NEXTJS_CHANGED" = true ]; then
        docker compose build nextjs
        docker compose up -d nextjs
    fi
    
    echo -e "${GREEN}✅ Containers updated${NC}"
else
    echo -e "${GREEN}✅ No changes detected, containers unchanged${NC}"
fi

echo ""
echo -e "${GREEN}=========================================="
echo "    Update Complete!"
echo -e "==========================================${NC}"
echo ""
docker compose ps
echo ""
