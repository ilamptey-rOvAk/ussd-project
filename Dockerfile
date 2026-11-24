# Production Dockerfile for deploying Laravel Nalo USSD on Render
FROM php:8.3-cli-alpine

WORKDIR /var/www

# Install system dependencies & PHP extensions required by Laravel & USSD
RUN apk add --no-cache \
    sqlite-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev \
    curl-dev \
    libpng-dev \
    icu-dev \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_sqlite \
    mbstring \
    zip \
    pcntl \
    bcmath

# Copy Composer binary from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . /var/www

# Install production PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Prepare storage permissions and database directory
RUN mkdir -p /var/www/storage/framework/{sessions,views,cache} \
    && mkdir -p /var/www/database \
    && touch /var/www/database/database.sqlite \
    && chmod -R 777 /var/www/storage /var/www/bootstrap/cache /var/www/database

# Default port expected by Render is 10000 (or $PORT)
ENV PORT=10000
EXPOSE 10000

# Run migrations and start server binding to 0.0.0.0 and $PORT
CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
