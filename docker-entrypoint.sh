#!/bin/sh
set -e

# Generate JWT secret at runtime if not provided via environment
if [ -z "${JWT_SECRET}" ] && [ ! -f /app/.env ]; then
    echo "JWT_SECRET not set. Generating..."
    php /app/siro key:generate 2>/dev/null || true
fi

# If JWT_SECRET is set via env, ensure .env has it for framework
if [ -n "${JWT_SECRET}" ]; then
    echo "JWT_SECRET=${JWT_SECRET}" > /app/.env
    echo "APP_ENV=${APP_ENV:-production}" >> /app/.env
fi

# Re-cache config with runtime env values
php /app/siro config:cache 2>/dev/null || true
php /app/siro env:cache 2>/dev/null || true

# Execute the main command
exec "$@"
