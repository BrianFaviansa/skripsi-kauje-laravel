#!/bin/bash
set -e

echo "🚀 Starting Laravel Octane with FrankenPHP..."
echo "================================================"

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ]; then
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
echo "🔥 Starting Laravel Octane with FrankenPHP..."
echo "   Workers: ${OCTANE_WORKERS:-4}"
echo "   Max Requests: ${OCTANE_MAX_REQUESTS:-1000}"
echo "   Port: 8000"
echo "================================================"

# Start Laravel Octane with FrankenPHP
exec php artisan octane:frankenphp \
    --host=0.0.0.0 \
    --port=8000 \
    --workers=${OCTANE_WORKERS:-4} \
    --max-requests=${OCTANE_MAX_REQUESTS:-1000}
