#!/usr/bin/env bash
set -e

echo "=== EventIQ Laravel Bootstrap ==="

cd /var/www/html

# Set default PORT if not provided by Render
export PORT="${PORT:-80}"

# Verify critical environment variables
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "FATAL: APP_KEY is not set. Generate one with: php artisan key:generate --show"
    exit 1
fi

# Prepare writable directories
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/cache/locks
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Set permissions (php-fpm runs as www-data, nginx runs as www-data)
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Ensure storage link exists (for public uploads/QR codes)
# This must run at startup since storage/ is not persisted in the image
if [ ! -L public/storage ]; then
    php artisan storage:link 2>/dev/null || true
fi

# Clear stale cached configuration that might reference old environment
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

# Cache configuration and routes for production performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Update nginx config with the Render-provided PORT
NGINX_CONF="/etc/nginx/nginx.conf"
if [ -f "$NGINX_CONF" ]; then
    sed -i "s/listen 80;/listen ${PORT};/" "$NGINX_CONF"
fi

echo "=== Starting services (PHP-FPM + Nginx on port ${PORT}) ==="

exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
