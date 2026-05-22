#!/bin/bash
set -e

echo "Checking JWT keys..."
php bin/console lexik:jwt:generate-keypair --skip-if-exists || true

echo "Starting PHP-FPM..."
php-fpm -F &
PHP_PID=$!

echo "Waiting for PHP-FPM to start..."
sleep 2

echo "Starting Nginx..."
nginx -g "daemon off;"

wait $PHP_PID
