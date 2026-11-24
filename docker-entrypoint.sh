#!/bin/sh
set -e

# Remove any static .env file so Render's injected environment variables take full precedence
rm -f /var/www/.env

# Ensure database directory and file exist with correct permissions
mkdir -p /var/www/database
touch /var/www/database/database.sqlite
chmod -R 777 /var/www/storage /var/www/bootstrap/cache /var/www/database

# Run database migrations
php artisan migrate --force --no-interaction

# Cache configuration using the runtime environment variables injected by Render (APP_KEY, etc.)
php artisan config:cache
php artisan route:cache

# Start the server with --no-reload so all container environment variables are preserved
echo "🚀 Starting Nalo USSD service on port ${PORT:-10000}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}" --no-reload
