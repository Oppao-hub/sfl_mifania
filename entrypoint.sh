#!/bin/bash
set -e

echo "Checking JWT keys..."
php bin/console lexik:jwt:generate-keypair --skip-if-exists || true

echo "Clearing and warming up cache..."
php bin/console cache:clear --env=prod --no-debug || true

echo "Fixing directory permissions for www-data..."
mkdir -p /app/var/cache /app/var/log
chown -R www-data:www-data /app/var
chmod -R 777 /app/var

echo "Starting PHP-FPM..."
php-fpm -F &
PHP_PID=$!

echo "Waiting for PHP-FPM to start..."
sleep 2

echo "Starting Nginx..."
nginx -g "daemon off;"

wait $PHP_PID
