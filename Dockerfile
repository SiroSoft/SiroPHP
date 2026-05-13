FROM php:8.2-cli-alpine

RUN apk add --no-cache git zip unzip \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite \
    && docker-php-ext-enable opcache

# Production OPcache config
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.enable_cli=1'; \
} > /usr/local/etc/php/conf.d/opcache.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY . .

RUN php siro key:generate \
    && php siro config:cache

RUN chown -R www-data:www-data storage /app/public

USER www-data

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
