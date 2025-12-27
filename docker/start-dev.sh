#!/bin/bash
set -e

echo "🚀 Starting Laravel Octane (Development Mode)..."

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "⚙️  Generating application key..."
    php artisan key:generate --force
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
    echo "Waiting for database... ($counter/$max_tries)"
    sleep 2
done
echo "✅ Database connected!"

# Run migrations
echo "📦 Running migrations..."
php artisan migrate --force

# Clear config cache for development
echo "⚙️  Clearing caches for development..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Create storage link if not exists
php artisan storage:link 2>/dev/null || true

echo "✅ Application ready!"
echo "🔥 Starting Laravel Octane with FrankenPHP (Watch Mode)..."

# Start Laravel Octane with FrankenPHP in watch mode for development
exec php artisan octane:frankenphp \
    --host=0.0.0.0 \
    --port=8000 \
    --workers=1 \
    --max-requests=500 \
    --watch
