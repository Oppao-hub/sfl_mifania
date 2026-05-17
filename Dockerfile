# Use PHP-FPM as the base image
FROM php:8.4-fpm

# Install system dependencies and MySQL extensions
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql intl zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# 6. Install production dependencies and skip automated scripts
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Set permissions
RUN chown -R www-data:www-data /var/www/html/var

# Use the entrypoint script
COPY entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose port 9000 for Nginx to communicate with PHP-FPM
EXPOSE 9000

CMD ["entrypoint.sh"]
