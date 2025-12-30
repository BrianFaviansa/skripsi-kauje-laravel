#!/bin/bash
# ===========================================
# Kauje - Initial VPS Deployment Script
# ===========================================
# Usage: ./deploy.sh
# This script will:
# 1. Clone both repositories (Laravel & Next.js)
# 2. Setup environment files
# 3. Build and start Docker containers
# ===========================================

set -e

# ============================================
# CONFIGURATION - UPDATE THESE VALUES
# ============================================
GITHUB_USERNAME="BrianFaviansa"
LARAVEL_REPO="skripsi-kauje-laravel"
NEXTJS_REPO="skripsi-kauje-nextjs"
DEPLOY_DIR="$HOME/skripsi"

# Clone method: "ssh" or "https"
CLONE_METHOD="ssh"
# ============================================

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Build clone URL based on method
if [ "$CLONE_METHOD" = "ssh" ]; then
    LARAVEL_URL="git@github.com:${GITHUB_USERNAME}/${LARAVEL_REPO}.git"
    NEXTJS_URL="git@github.com:${GITHUB_USERNAME}/${NEXTJS_REPO}.git"
else
    LARAVEL_URL="https://github.com/${GITHUB_USERNAME}/${LARAVEL_REPO}.git"
    NEXTJS_URL="https://github.com/${GITHUB_USERNAME}/${NEXTJS_REPO}.git"
fi

echo -e "${GREEN}=========================================="
echo "    Kauje - VPS Deployment Script"
echo -e "==========================================${NC}"
echo ""

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo -e "${RED}❌ Docker not found. Please install Docker first.${NC}"
    exit 1
fi

if ! command -v docker compose &> /dev/null; then
    echo -e "${RED}❌ Docker Compose not found. Please install Docker Compose first.${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Docker and Docker Compose found${NC}"

# Create deployment directory
echo ""
echo -e "${YELLOW}📁 Creating deployment directory: ${DEPLOY_DIR}${NC}"
mkdir -p "$DEPLOY_DIR"
cd "$DEPLOY_DIR"

# Clone or pull Laravel repository
echo ""
echo -e "${YELLOW}📦 Setting up Laravel repository...${NC}"
if [ -d "$LARAVEL_REPO" ]; then
    echo "   Repository exists, pulling latest changes..."
    cd "$LARAVEL_REPO"
    git pull origin main || git pull origin master
    cd ..
else
    echo "   Cloning repository (${CLONE_METHOD})..."
    git clone "$LARAVEL_URL"
fi
echo -e "${GREEN}✅ Laravel repository ready${NC}"

# Clone or pull Next.js repository
echo ""
echo -e "${YELLOW}📦 Setting up Next.js repository...${NC}"
if [ -d "$NEXTJS_REPO" ]; then
    echo "   Repository exists, pulling latest changes..."
    cd "$NEXTJS_REPO"
    git pull origin main || git pull origin master
    cd ..
else
    echo "   Cloning repository (${CLONE_METHOD})..."
    git clone "$NEXTJS_URL"
fi
echo -e "${GREEN}✅ Next.js repository ready${NC}"

# Create docker-compose.yml if not exists
if [ ! -f "docker-compose.yml" ]; then
    echo ""
    echo -e "${YELLOW}📝 Creating docker-compose.yml...${NC}"
    cat > docker-compose.yml << 'EOF'
services:
  # PostgreSQL Database (Shared)
  db:
    image: postgres:16-alpine
    container_name: kauje-db
    restart: unless-stopped
    environment:
      POSTGRES_USER: ${DB_USERNAME:-postgres}
      POSTGRES_PASSWORD: ${DB_PASSWORD:-postgres}
      POSTGRES_DB: ${DB_DATABASE:-kauje_db}
    volumes:
      - postgres_data:/var/lib/postgresql/data
    ports:
      - "${DB_PORT:-5432}:5432"
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U postgres"]
      interval: 5s
      timeout: 5s
      retries: 10
    networks:
      - kauje-network

  # Redis for Laravel
  redis:
    image: redis:7-alpine
    container_name: kauje-redis
    restart: unless-stopped
    command: redis-server --appendonly yes --maxmemory 128mb --maxmemory-policy allkeys-lru
    volumes:
      - redis_data:/data
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 5s
      retries: 5
    networks:
      - kauje-network

  # Laravel Application
  laravel:
    build:
      context: ./skripsi-kauje-laravel
      dockerfile: Dockerfile
      target: production
    container_name: kauje-laravel
    restart: unless-stopped
    ports:
      - "${LARAVEL_PORT:-8000}:8000"
    environment:
      - APP_NAME=${APP_NAME:-Kauje Laravel}
      - APP_ENV=${APP_ENV:-production}
      - APP_DEBUG=${APP_DEBUG:-false}
      - APP_KEY=${APP_KEY}
      - APP_URL=${APP_URL:-http://localhost:8000}
      - DB_CONNECTION=pgsql
      - DB_HOST=db
      - DB_PORT=5432
      - DB_DATABASE=${DB_DATABASE:-kauje_db}
      - DB_USERNAME=${DB_USERNAME:-postgres}
      - DB_PASSWORD=${DB_PASSWORD:-postgres}
      - REDIS_HOST=redis
      - REDIS_PORT=6379
      - CACHE_STORE=redis
      - SESSION_DRIVER=redis
      - QUEUE_CONNECTION=redis
      - OCTANE_SERVER=swoole
      - OCTANE_WORKERS=${OCTANE_WORKERS:-auto}
      - OCTANE_MAX_REQUESTS=${OCTANE_MAX_REQUESTS:-1000}
      - LOG_CHANNEL=stack
      - LOG_LEVEL=${LOG_LEVEL:-error}
      - SEED_DATABASE=${SEED_DATABASE:-true}
    volumes:
      - laravel_storage:/app/storage/app
      - laravel_logs:/app/storage/logs
    depends_on:
      db:
        condition: service_healthy
      redis:
        condition: service_healthy
    networks:
      - kauje-network
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:8000/up"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 90s

  # Next.js Application
  nextjs:
    build:
      context: ./skripsi-kauje-nextjs
      dockerfile: Dockerfile
    container_name: kauje-nextjs
    restart: unless-stopped
    ports:
      - "${NEXTJS_PORT:-3000}:3000"
    environment:
      - DATABASE_URL=postgresql://${DB_USERNAME:-postgres}:${DB_PASSWORD:-postgres}@db:5432/${DB_DATABASE:-kauje_db}?schema=public
      - JWT_SECRET=${JWT_SECRET:-your-super-secret-jwt-key}
      - NODE_ENV=production
    depends_on:
      db:
        condition: service_healthy
      laravel:
        condition: service_healthy
    networks:
      - kauje-network
    healthcheck:
      test: ["CMD", "wget", "-q", "--spider", "http://localhost:3000"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 30s

volumes:
  postgres_data:
  redis_data:
  laravel_storage:
  laravel_logs:

networks:
  kauje-network:
    driver: bridge
EOF
    echo -e "${GREEN}✅ docker-compose.yml created${NC}"
fi

# Create .env if not exists
if [ ! -f ".env" ]; then
    echo ""
    echo -e "${YELLOW}📝 Creating .env file...${NC}"
    cat > .env << 'EOF'
# Database
DB_DATABASE=kauje_db
DB_USERNAME=postgres
DB_PASSWORD=postgres123
DB_PORT=5432

# Ports
LARAVEL_PORT=8000
NEXTJS_PORT=3000

# Laravel
APP_NAME=Kauje
APP_ENV=production
APP_DEBUG=false
APP_KEY=
APP_URL=http://localhost:8000
OCTANE_WORKERS=auto
OCTANE_MAX_REQUESTS=1000
LOG_LEVEL=error
SEED_DATABASE=true

# Next.js
JWT_SECRET=your-super-secret-jwt-key-change-this
EOF
    echo -e "${GREEN}✅ .env created${NC}"
    echo ""
    echo -e "${YELLOW}⚠️  IMPORTANT: Edit .env file with your secure passwords!${NC}"
    echo -e "${YELLOW}   nano .env${NC}"
    echo ""
    read -p "Press Enter after editing .env to continue..."
fi

# Build and start containers
echo ""
echo -e "${YELLOW}🔨 Building Docker images...${NC}"
docker compose build

echo ""
echo -e "${YELLOW}🚀 Starting containers...${NC}"
docker compose up -d

# Wait for services to be healthy
echo ""
echo -e "${YELLOW}⏳ Waiting for services to be ready...${NC}"
sleep 30

# Check status
echo ""
echo -e "${GREEN}=========================================="
echo "    Deployment Complete!"
echo -e "==========================================${NC}"
echo ""
docker compose ps
echo ""
echo -e "${GREEN}🎉 Services are running!${NC}"
echo ""
echo "Access your applications:"
echo "  - Laravel:  http://$(hostname -I | awk '{print $1}'):8000"
echo "  - Next.js:  http://$(hostname -I | awk '{print $1}'):3000"
echo ""
echo "Useful commands:"
echo "  - View logs:     docker compose logs -f"
echo "  - Stop:          docker compose down"
echo "  - Update:        ./update.sh"
echo ""
