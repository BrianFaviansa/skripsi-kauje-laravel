# ============================================
# Stage 1: Base image with PHP extensions
# ============================================
FROM php:8.3-cli AS base

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    libcurl4-openssl-dev \
    libssl-dev \
    libicu-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    pcntl \
    zip \
    gd \
    intl \
    opcache \
    sockets

# Install Swoole with optimizations
RUN pecl install swoole && docker-php-ext-enable swoole

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# ============================================
# Stage 2: Composer dependencies
# ============================================
FROM composer:2 AS composer

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Copy modules composer files for merge plugin
COPY Modules/ ./Modules/

# Install dependencies (no dev for production)
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

# ============================================
# Stage 3: Production image
# ============================================
FROM base AS production

# Labels for image metadata
LABEL maintainer="Kauje Team"
LABEL version="1.0"
LABEL description="Laravel application with Octane and Swoole"

# Set working directory
WORKDIR /app

# Configure PHP OPcache for maximum production performance
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.enable_cli=1'; \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.interned_strings_buffer=64'; \
    echo 'opcache.max_accelerated_files=65535'; \
    echo 'opcache.max_wasted_percentage=10'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.save_comments=1'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.jit=1255'; \
    echo 'opcache.jit_buffer_size=128M'; \
    } > /usr/local/etc/php/conf.d/opcache-production.ini

# Configure PHP for high-performance production
RUN { \
    echo 'memory_limit=512M'; \
    echo 'max_execution_time=60'; \
    echo 'upload_max_filesize=50M'; \
    echo 'post_max_size=50M'; \
    echo 'expose_php=Off'; \
    echo 'display_errors=Off'; \
    echo 'log_errors=On'; \
    echo 'error_log=/app/storage/logs/php_errors.log'; \
    echo 'realpath_cache_size=4096K'; \
    echo 'realpath_cache_ttl=600'; \
    echo 'output_buffering=4096'; \
    echo 'implicit_flush=Off'; \
    } > /usr/local/etc/php/conf.d/php-production.ini

# Copy application code
COPY . .

# Copy vendor from composer stage
COPY --from=composer /app/vendor ./vendor

# Copy composer binary for autoload generation
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Generate optimized autoload
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# Create necessary directories
RUN mkdir -p storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Copy startup script
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Expose port
EXPOSE 8000

# Healthcheck to monitor container health
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost:8000/up || exit 1

# Start command
ENTRYPOINT ["/usr/local/bin/start.sh"]
