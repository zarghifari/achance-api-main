FROM php:8.1-fpm AS base

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip intl mbstring \
    && docker-php-ext-install pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Install phpredis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Install OPcache extension for better performance
RUN docker-php-ext-install opcache

# Install Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Set Composer environment variables
ENV COMPOSER_ALLOW_SUPERUSER=1

# Development stage
FROM base AS development

# Copy PHP configuration for development
COPY docker/php/php.ini /usr/local/etc/php/php.ini

# Set working directory
WORKDIR /var/www

# Copy only composer files first for better caching
COPY --chown=www-data:www-data src/composer.json src/composer.lock ./

# Install PHP dependencies (including dev dependencies for development)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Copy the rest of the application code
COPY --chown=www-data:www-data src/. .

# Set proper permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www

# Production stage
FROM base AS production

# Copy optimized PHP configuration for production
COPY docker/php/php.ini /usr/local/etc/php/php.ini

# Set working directory
WORKDIR /var/www

# Copy only composer files first
COPY --chown=www-data:www-data src/composer.json src/composer.lock ./

# Install only production dependencies
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --classmap-authoritative --no-scripts

# Copy the rest of the application code
COPY --chown=www-data:www-data src/. .

# Run additional optimizations for production
RUN composer run post-autoload-dump || true && \
    php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Set proper permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www

# Common settings for both stages
EXPOSE 9000
CMD ["php-fpm"]
