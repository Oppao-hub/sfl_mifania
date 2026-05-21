#!/bin/bash
set -e

echo "Checking JWT keys..."
php bin/console lexik:jwt:generate-keypair --skip-if-exists || true

echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction || true

echo "Loading fixtures..."
php bin/console doctrine:fixtures:load --append --no-interaction || true

echo "Starting PHP-FPM..."
php-fpm -F &
PHP_PID=$!

echo "Waiting for PHP-FPM to start..."
sleep 2

echo "Starting Nginx..."
nginx -g "daemon off;"

wait $PHP_PID
