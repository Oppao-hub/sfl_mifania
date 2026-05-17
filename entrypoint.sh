#!/bin/bash
set -e

echo "Clearing Symfony cache..."
php bin/console cache:clear --env=prod

echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --env=prod

echo "Starting PHP-FPM..."
exec php-fpm
