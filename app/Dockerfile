FROM dunglas/frankenphp:1.9-php8.4-alpine

# Install dependencies
RUN apk add --no-cache postgresql-dev \
    && docker-php-ext-install pdo_pgsql opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files first for layer caching
COPY composer.json composer.lock symfony.lock ./

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-scripts --classmap-authoritative

# Copy application code
COPY . .

# Create required directories and set permissions
RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var/ \
    && chmod -R 775 var/ \
    && mkdir -p .phpunit \
    && chown -R www-data:www-data .phpunit \
    && chmod -R 775 .phpunit \
    && chown -R www-data:www-data .

# Copy FrankenPHP configuration
COPY docker/frankenphp/Caddyfile /etc/frankenphp/Caddyfile

# Run final composer scripts
RUN composer run-script post-install-cmd

# Set user
USER www-data

# Health check - check if FrankenPHP process is running
HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD pgrep frankenphp > /dev/null || exit 1

EXPOSE 8000
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]