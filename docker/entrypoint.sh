#!/bin/sh

set -e

# Wait for database to be ready
echo "Waiting for database to be ready..."
sleep 5

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Seed admin user
echo "Seeding admin user..."
php artisan db:seed --class=AdminUserSeeder --force

# Clear and cache configuration
echo "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

echo "Starting application..."
exec "$@"
