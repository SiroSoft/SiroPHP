FROM php:8.2-fpm-alpine AS base

RUN apk add --no-cache \
    nginx \
    supervisor \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql pdo_sqlite mbstring json

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader

COPY . .

RUN php siro config:cache && \
    php siro optimize

# Generate JWT secret at runtime (not build time), so each container gets a unique key
# This is handled via CMD/entrypoint below

FROM base AS production

COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

EXPOSE 8080

CMD php siro key:generate && /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
