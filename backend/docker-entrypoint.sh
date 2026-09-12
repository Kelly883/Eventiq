#!/bin/bash
set -euo pipefail

# ─── Eventiq Laravel Backend — Docker Entrypoint ─────────────────────────────
#
# This script runs at container start (not build time). It:
#   1. Validates critical environment variables
#   2. Prepares writable directories
#   3. Caches Laravel configuration/routes/views (safe to run at runtime)
#   4. Runs pending Laravel database migrations
#   5. Creates the storage symlink if missing
#   6. Configures nginx for Render's PORT
#   7. Optionally enables ClamAV
#   8. Starts services via supervisord
#
# It does NOT:
#   - Generate APP_KEY
#   - Expose secrets in logs
#   - Run destructive database commands
# ─────────────────────────────────────────────────────────────────────────────

echo "==> Eventiq backend starting..."

# ─── 0. Set PORT for Render ──────────────────────────────────────────────────

# Render provides PORT env var.
# Nginx listens on the Render-provided port.
export NGINX_PORT="${PORT:-8080}"

# ─── 1. Validate critical config ─────────────────────────────────────────────

if [ -z "${APP_KEY:-}" ]; then
    echo "FATAL: APP_KEY is not set. Generate one with 'php artisan key:generate' and set it in Render."
    exit 1
fi

if [ -z "${APP_URL:-}" ]; then
    echo "WARNING: APP_URL is not set. Some features may not work correctly."
fi

# ─── 2. Prepare writable directories ─────────────────────────────────────────

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

# ─── 3. Laravel configuration cache ──────────────────────────────────────────

echo "==> Caching Laravel configuration..."

# APP_KEY has already been validated above.
php artisan config:cache --no-interaction

# ─── 3b. Database migrations ─────────────────────────────────────────────────

echo "==> Running database migrations..."

# --force is required because this is a production environment.
# Do NOT suppress errors here. If migrations fail, the container should stop
# rather than start the application with an incomplete database schema.
php artisan migrate --force --no-interaction

echo "==> Database migrations completed."

# ─── 3c. Route caching ────────────────────────────────────────────────────────

# Route caching is intentionally skipped because the application contains
# closure routes which are incompatible with php artisan route:cache.
#
# php artisan route:cache --no-interaction

# ─── 3d. View caching ─────────────────────────────────────────────────────────

echo "==> Caching Laravel views..."

php artisan view:cache --no-interaction 2>/dev/null || {
    echo "WARNING: view:cache failed (non-fatal)"
}

# ─── 4. Storage symlink ───────────────────────────────────────────────────────

echo "==> Creating storage symlink..."

php artisan storage:link --force --no-interaction 2>/dev/null || {
    echo "WARNING: storage:link failed (non-fatal, may already exist)"
}

# ─── 5. Fix permissions after Laravel caching ─────────────────────────────────

echo "==> Fixing Laravel storage permissions..."

chown -R www-data:www-data /var/www/html/storage 2>/dev/null || true
chown -R www-data:www-data /var/www/html/bootstrap/cache 2>/dev/null || true

chmod -R 775 /var/www/html/storage 2>/dev/null || true
chmod -R 775 /var/www/html/bootstrap/cache 2>/dev/null || true

# ─── 6. Configure nginx for Render PORT ───────────────────────────────────────

echo "==> Configuring nginx for port ${NGINX_PORT}..."

# Render provides PORT dynamically.
# The nginx template initially contains "listen 8080".
sed -i "s/listen 8080/listen ${NGINX_PORT}/g" \
    /etc/nginx/conf.d/default.conf

# ─── 6b. ClamAV (optional) ────────────────────────────────────────────────────

# ClamAV is disabled by default.
# Set CLAMAV_ENABLED=true in Render only if you intentionally want ClamAV.
if [ "${CLAMAV_ENABLED:-false}" = "true" ]; then

    echo "==> ClamAV enabled — updating signatures..."

    mkdir -p /var/run/clamav
    mkdir -p /var/log/clamav

    chown -R www-data:www-data /var/run/clamav 2>/dev/null || true
    chown -R www-data:www-data /var/log/clamav 2>/dev/null || true

    # Update virus definitions.
    # Limit displayed output so logs do not become excessively large.
    freshclam --stdout 2>&1 | head -20 || {
        echo "WARNING: freshclam failed (non-fatal, will retry at runtime)"
    }

    # Enable clamd in supervisord.
    sed -i 's/autostart=false/autostart=true/' \
        /etc/supervisor/conf.d/supervisord.conf || true

    echo "==> ClamAV daemon will be started by supervisord."

else

    echo "==> ClamAV disabled (set CLAMAV_ENABLED=true to enable)"

fi

# ─── 7. Start services ────────────────────────────────────────────────────────

echo "==> Starting services on port ${NGINX_PORT}..."

exec /usr/bin/supervisord \
    -c /etc/supervisor/conf.d/supervisord.conf
