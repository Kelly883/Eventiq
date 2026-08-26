#!/bin/bash
set -euo pipefail

# ─── Eventiq Laravel Backend — Docker Entrypoint ─────────────────────────────
#
# This script runs at container start (not build time). It:
#   1. Validates critical environment variables
#   2. Prepares writable directories
#   3. Caches Laravel configuration/routes/views (safe to run at runtime)
#   4. Creates the storage symlink if missing
#   5. Starts services via supervisord
#
# It does NOT:
#   - Run database migrations automatically
#   - Generate APP_KEY
#   - Expose secrets in logs
#   - Run destructive database commands
# ─────────────────────────────────────────────────────────────────────────────

echo "==> Eventiq backend starting..."

# ─── 0. Set PORT for Render ──────────────────────────────────────────────────

# Render provides PORT env var (default 8000). Nginx and PHP-FPM listen on 8080
# internally; the Docker nginx template uses $NGINX_PORT which we set here.
export NGINX_PORT="${PORT:-8080}"

# ─── 1. Validate critical config ─────────────────────────────────────────────

if [ -z "${APP_KEY:-}" ]; then
    echo "FATAL: APP_KEY is not set. Generate one with 'php artisan key:generate' and set it in Render."
    exit 1
fi

if [ -z "${APP_URL:-}" ]; then
    echo "WARNING: APP_URL is not set. Some features may not work correctly."
fi

# ─── 2. Prepare writable directories ────────────────────────────────────────

echo "==> Preparing writable directories..."

mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/app/public
mkdir -p /var/www/html/storage/app/private
mkdir -p /var/www/html/bootstrap/cache

# Ensure correct permissions
chown -R www-data:www-data /var/www/html/storage 2>/dev/null || true
chown -R www-data:www-data /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage 2>/dev/null || true
chmod -R 775 /var/www/html/bootstrap/cache 2>/dev/null || true

# ─── 3. Laravel optimization (safe at runtime) ───────────────────────────────

echo "==> Caching Laravel configuration..."

# Only cache config when APP_KEY is set (required for encrypted values)
if [ -n "${APP_KEY:-}" ]; then
    php artisan config:cache --no-interaction 2>/dev/null || echo "WARNING: config:cache failed (non-fatal)"
fi

# Route caching is skipped because the application contains closure routes
# (web.php /health and api.php /events) which are incompatible with
# php artisan route:cache. This is safe - Laravel will cache routes per-request.
# php artisan route:cache --no-interaction 2>/dev/null || echo "WARNING: route:cache skipped (closure routes present)"

# View caching
php artisan view:cache --no-interaction 2>/dev/null || echo "WARNING: view:cache failed (non-fatal)"

# ─── 4. Storage symlink ─────────────────────────────────────────────────────

echo "==> Creating storage symlink..."
php artisan storage:link --force --no-interaction 2>/dev/null || echo "WARNING: storage:link failed (non-fatal, may already exist)"

# ─── 5. Fix permissions after caching ───────────────────────────────────────

# config:cache writes to bootstrap/cache as www-data or root; ensure www-data can read
chown -R www-data:www-data /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/bootstrap/cache 2>/dev/null || true

# ─── 6. Configure nginx for Render PORT ─────────────────────────────────────

# Render provides PORT env var (default 8000). Substitute into nginx config.
sed -i "s/listen 8080/listen ${NGINX_PORT}/g" /etc/nginx/conf.d/default.conf

# ─── 7. Start services ──────────────────────────────────────────────────────

echo "==> Starting services on port ${NGINX_PORT}..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
