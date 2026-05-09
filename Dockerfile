FROM php:8.2-fpm-alpine AS base

RUN apk add --no-cache \
    nginx \
    supervisor \
    shadow \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql pdo_sqlite mbstring json

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader

COPY . .

RUN php siro config:cache && \
    php siro optimize

RUN addgroup -g 1000 -S siro && \
    adduser -u 1000 -S siro -G siro && \
    chown -R siro:siro /app /var/lib/nginx /var/log/nginx

FROM base AS production

COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD php -r "file_put_contents('php://stdout', 'OK');" || exit 1

USER siro

CMD php siro key:generate && /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
