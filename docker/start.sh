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

CACHE_STORE="${CACHE_STORE:-redis}"
SESSION_DRIVER="${SESSION_DRIVER:-redis}"
QUEUE_CONNECTION="${QUEUE_CONNECTION:-redis}"

LOG_CHANNEL="${LOG_CHANNEL:-stack}"
LOG_LEVEL="${LOG_LEVEL:-error}"
EOF
    echo "✅ .env file created"
fi

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ] || ! grep -q "APP_KEY=base64" /app/.env; then
    echo "⚙️  Generating application key..."
    php artisan key:generate --force
fi

# Wait for database to be ready (additional safety check)
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

# Run migrations
echo "📦 Running migrations..."
php artisan migrate --force

# Run seeder if SEED_DATABASE is set (useful for fresh deployments)
if [ "$SEED_DATABASE" = "true" ]; then
    echo "🌱 Running database seeders..."
    php artisan db:seed --force
fi

# Clear and cache config for production
echo "⚙️  Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Create storage link if not exists
echo "🔗 Creating storage symlink..."
php artisan storage:link 2>/dev/null || true

echo "================================================"
echo "✅ Application ready!"
echo "🔥 Starting Laravel Octane with Swoole..."
echo "   Workers: ${OCTANE_WORKERS:-4}"
echo "   Max Requests: ${OCTANE_MAX_REQUESTS:-1000}"
echo "   Port: 8000"
echo "================================================"

# Start Laravel Octane with Swoole
exec php artisan octane:start \
    --server=swoole \
    --host=0.0.0.0 \
    --port=8000 \
    --workers=${OCTANE_WORKERS:-4} \
    --max-requests=${OCTANE_MAX_REQUESTS:-1000}
