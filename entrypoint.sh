#!/bin/sh

echo "Fixing permissions..."
mkdir -p /var/www/storage/app/public
php artisan storage:link >/dev/null 2>&1 || true
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

echo "Starting PHP-FPM..."
php-fpm -D

echo "Starting Nginx..."
nginx -g "daemon off;"
