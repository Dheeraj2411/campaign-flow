#!/bin/bash
set -e

echo "==> Starting PingOS entrypoint..."

# Cache config and routes
echo "==> Caching config..."
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan icons:cache 2>/dev/null || true

# Run migrations
echo "==> Running migrations..."
php artisan migrate --force
php artisan view:cache
php artisan event:cache
php artisan icons:cache 2>/dev/null || true

# Clear and warm up
php artisan queue:restart

echo "==> PingOS is ready!"

exec "$@"
