#!/usr/bin/env bash
# Exit on error
set -o errexit

echo "📦 Installing composer production dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "⚙️ Caching configuration, routes, and views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "🗄️ Running database migrations..."
php artisan migrate --force --no-interaction

echo "✅ Build completed successfully!"
