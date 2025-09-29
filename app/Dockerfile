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
    && chmod -R 755 var/

# Copy FrankenPHP configuration
COPY docker/frankenphp/Caddyfile /etc/frankenphp/Caddyfile

# Run final composer scripts
RUN composer run-script post-install-cmd

# Set user
USER www-data

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
    CMD wget --no-verbose --tries=1 --spider http://localhost:8000/health || exit 1

EXPOSE 8000
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]