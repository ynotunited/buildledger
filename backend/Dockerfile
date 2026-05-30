FROM php:8.2-fpm-alpine

# System dependencies
RUN apk add --no-cache \
    $PHPIZE_DEPS bash git curl libpng-dev libzip-dev zip unzip oniguruma-dev \
    postgresql-dev nginx supervisor

# Redis extension for distributed throttling, sessions, and queues
RUN pecl install redis \
    && docker-php-ext-enable redis

# PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql mbstring zip exif pcntl bcmath gd opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first for layer caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application
COPY . .

# Finish composer install
RUN composer dump-autoload --optimize

# Permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Nginx config
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Supervisor config
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
