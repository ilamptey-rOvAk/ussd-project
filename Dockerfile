# Production Dockerfile for deploying Laravel Nalo USSD on Render
FROM php:8.4-cli-alpine

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

# Prepare directories and permissions
RUN mkdir -p storage/framework/{sessions,views,cache} database bootstrap/cache \
    && chmod -R 777 storage bootstrap/cache database

# Install production dependencies without running scripts that require runtime env
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --ignore-platform-reqs

# Run package discovery now that autoload and extensions are present
RUN php artisan package:discover --ansi

# Make entrypoint script executable
RUN chmod +x /var/www/docker-entrypoint.sh

# Default port expected by Render is 10000 (or $PORT)
ENV PORT=10000
EXPOSE 10000

ENTRYPOINT ["/var/www/docker-entrypoint.sh"]
