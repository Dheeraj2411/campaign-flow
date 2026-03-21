FROM php:8.4-fpm

# Install system dependencies + Node 20 from NodeSource
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    supervisor \
    redis-tools \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd sockets opcache

# Copy PHP configs
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/php.ini /usr/local/etc/php/conf.d/pingos.ini

# Install Redis PHP extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

# Install PHP dependencies
RUN composer install --no-interaction --no-dev --optimize-autoloader \
    && rm -f bootstrap/cache/packages.php bootstrap/cache/services.php

# Install Node dependencies and build
RUN npm install && npm run build

# Set permissions
RUN chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    /var/www/html/public \
    && chmod +x /var/www/html/docker/entrypoint.sh

# Supervisor logs
RUN mkdir -p /var/log/supervisor \
    && chown -R www-data:www-data /var/log/supervisor

EXPOSE 9000

# Simple PHP healthcheck — no extra binary needed
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD php -r "echo 'ok';" || exit 1

CMD ["php-fpm"]