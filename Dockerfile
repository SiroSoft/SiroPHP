FROM composer:2 AS composer

FROM dunglas/frankenphp:1-php8.2

# Install PHP extensions (zip + unzip required for composer to extract
# package dist archives during install).
RUN install-php-extensions pdo pdo_mysql pdo_sqlite zip \
    && apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/*

# Copy Composer from official image
COPY --from=composer /usr/bin/composer /usr/local/bin/composer

# Production OPcache config
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.enable_cli=1'; \
} > /usr/local/etc/php/conf.d/opcache.ini

# Copy the application
WORKDIR /app
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Optimize for production
RUN php siro config:cache

# Permissions
RUN chown -R www-data:www-data storage

USER www-data

EXPOSE 80

# FrankenPHP entrypoint (multi-worker, HTTP/2, HTTP/3, auto HTTPS)
CMD ["frankenphp", "run"]
