# Production Dockerfile for deploying Laravel Nalo USSD on Render
FROM php:8.3-cli-alpine

WORKDIR /var/www

# Install system dependencies & PHP extensions required by Laravel & USSD
RUN apk add --no-cache \
    sqlite-dev \
    libzip-dev \
    libxml2-dev \
    zip \
    unzip \
    oniguruma-dev \
    curl-dev \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_sqlite \
    mbstring \
    zip \
    pcntl \
    bcmath \
    dom \
    xml \
    xmlwriter \
    simplexml

# Copy Composer binary from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . /var/www

# Ensure environment file exists for artisan and prepare directories
RUN cp -n .env.example .env 2>/dev/null || true \
    && mkdir -p storage/framework/{sessions,views,cache} database \
    && touch database/database.sqlite \
    && chmod -R 777 storage bootstrap/cache database

# Install production dependencies without running scripts that require runtime env
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --ignore-platform-reqs

# Run package discovery now that autoload and extensions are present
RUN php artisan package:discover --ansi

# Default port expected by Render is 10000 (or $PORT)
ENV PORT=10000
EXPOSE 10000

# Run migrations and start server binding to 0.0.0.0 and $PORT
CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
