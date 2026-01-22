#!/bin/bash
set -e

echo "🚀 Starting Laravel Octane with Swoole..."
echo "================================================"

# Create .env file from environment variables if it doesn't exist
if [ ! -f /app/.env ]; then
    echo "📝 Creating .env file from environment variables..."
    cat > /app/.env << EOF
APP_NAME="${APP_NAME:-Laravel}"
APP_ENV="${APP_ENV:-production}"
APP_KEY="${APP_KEY:-}"
APP_DEBUG="${APP_DEBUG:-false}"
APP_URL="${APP_URL:-http://localhost}"

DB_CONNECTION="${DB_CONNECTION:-pgsql}"
DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-5432}"
DB_DATABASE="${DB_DATABASE:-kauje_db}"
DB_USERNAME="${DB_USERNAME:-postgres}"
DB_PASSWORD="${DB_PASSWORD:-postgres}"

REDIS_HOST="${REDIS_HOST:-redis}"
REDIS_PORT="${REDIS_PORT:-6379}"
REDIS_CLIENT="${REDIS_CLIENT:-phpredis}"

CACHE_STORE="${CACHE_STORE:-redis}"
SESSION_DRIVER="${SESSION_DRIVER:-redis}"
QUEUE_CONNECTION="${QUEUE_CONNECTION:-redis}"

LOG_CHANNEL="${LOG_CHANNEL:-stack}"
LOG_LEVEL="${LOG_LEVEL:-error}"
EOF
    echo "✅ .env file created"
fi

# Check if APP_KEY is already set in environment
if [ -n "$APP_KEY" ] && [[ "$APP_KEY" == base64:* ]]; then
    echo "✅ APP_KEY already set from environment"
else
    echo "⚙️  Generating application key..."
    php artisan config:clear 2>/dev/null || true
    php artisan key:generate --force
    export APP_KEY=$(grep "^APP_KEY=" /app/.env | sed 's/APP_KEY=//' | tr -d '"')
fi

# Wait for database to be ready
echo "⏳ Waiting for database connection..."
max_tries=30
counter=0
until php artisan db:monitor --databases=pgsql 2>/dev/null; do
    counter=$((counter + 1))
    if [ $counter -gt $max_tries ]; then
        echo "❌ Database connection failed after $max_tries attempts"
        exit 1
    fi
    echo "   Waiting for database... ($counter/$max_tries)"
    sleep 2
done
echo "✅ Database connected!"

# Wait for Redis to be ready
echo "⏳ Waiting for Redis connection..."
counter=0
until php -r "new Redis()->connect('${REDIS_HOST:-redis}', ${REDIS_PORT:-6379});" 2>/dev/null; do
    counter=$((counter + 1))
    if [ $counter -gt $max_tries ]; then
        echo "⚠️  Redis connection failed, continuing without Redis cache"
        break
    fi
    echo "   Waiting for Redis... ($counter/$max_tries)"
    sleep 1
done
echo "✅ Redis connected!"

# Run migrations
echo "📦 Running migrations..."
php artisan migrate --force

# Run seeder if SEED_DATABASE is set
if [ "$SEED_DATABASE" = "true" ]; then
    echo "🌱 Running database seeders..."
    php artisan db:seed --force
fi

# Clear old caches
echo "⚙️  Clearing old caches..."
php artisan config:clear
php artisan cache:clear 2>/dev/null || true
php artisan route:clear
php artisan view:clear
php artisan event:clear

# Optimize for production
echo "⚙️  Optimizing application for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Warm up frequently used data
echo "🔥 Warming up caches..."
php artisan optimize

# Create storage link if not exists
echo "🔗 Creating storage symlink..."
php artisan storage:link 2>/dev/null || true

# Calculate optimal workers based on CPU
WORKERS=${OCTANE_WORKERS:-auto}
if [ "$WORKERS" = "auto" ]; then
    CPU_CORES=$(nproc)
    WORKERS=$((CPU_CORES * 2))
    echo "   Auto-detected CPU cores: $CPU_CORES, using $WORKERS workers"
fi

TASK_WORKERS=${OCTANE_TASK_WORKERS:-auto}
if [ "$TASK_WORKERS" = "auto" ]; then
    CPU_CORES=$(nproc)
    TASK_WORKERS=$((CPU_CORES))
fi

echo "================================================"
echo "✅ Application ready!"
echo "🔥 Starting Laravel Octane with Swoole..."
echo "   Workers: $WORKERS"
echo "   Task Workers: $TASK_WORKERS"
echo "   Max Requests: ${OCTANE_MAX_REQUESTS:-500}"
echo "   Port: 8000"
echo "================================================"

# Start Laravel Octane with Swoole
exec php artisan octane:start \
    --server=swoole \
    --host=0.0.0.0 \
    --port=8000 \
    --workers=$WORKERS \
    --task-workers=$TASK_WORKERS \
    --max-requests=${OCTANE_MAX_REQUESTS:-500}
