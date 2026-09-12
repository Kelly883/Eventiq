# ─── Eventiq — Root Dockerfile (repo-root context) ───────────────────────────
# This Dockerfile exists for build contexts that are the repository root
# (e.g. `docker build .` or platforms that always use repo root).
#
# It simply delegates to the backend production build while using the correct
# COPY path for a repo-root context: `COPY backend/`.
#
# Preferred Render setup remains:
#   Root Directory = backend
#   Dockerfile     = Dockerfile          (i.e. backend/Dockerfile with COPY .)
#   Build Context  = backend/
#
# If your platform forces repo-root context, either:
#   - Use this file:              `docker build -f Dockerfile .`
#   - Or explicitly set context: `docker build -f backend/Dockerfile backend/`
#     (which is equivalent and also works)
# ─────────────────────────────────────────────────────────────────────────────

FROM php:8.3-fpm-bookworm

RUN apt-get update -qq && apt-get install -y -qq --no-install-recommends \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libcurl4-openssl-dev \
    libssl-dev \
    clamav clamav-daemon clamav-freshclam \
    nginx supervisor ca-certificates curl sed unzip \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_pgsql pgsql mbstring xml curl zip gd bcmath iconv intl opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN mkdir -p /var/run/clamav /var/log/clamav /var/lib/clamav \
    && chown -R www-data:www-data /var/run/clamav \
    && freshclam || true

WORKDIR /var/www/html

# Repo-root context: copy from backend/ subdirectory
COPY backend/composer.json backend/composer.lock ./

RUN composer install \
    --no-dev --prefer-dist --optimize-autoloader \
    --no-interaction --no-scripts --no-autoloader

RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y -qq nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY backend/node-tools/mjml/package.json backend/node-tools/mjml/package-lock.json* node-tools/mjml/
RUN cd node-tools/mjml && npm install --production --no-audit --no-fund

# Copy full backend source (repo-root context)
COPY backend/ .

RUN composer dump-autoload --optimize --no-dev

RUN mkdir -p \
    storage/logs storage/framework/cache/data storage/framework/sessions \
    storage/framework/views storage/app/public storage/app/private \
    bootstrap/cache /var/log/nginx /var/log/supervisor /run \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY backend/docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY backend/docker/php.ini /usr/local/etc/php/conf.d/99-eventiq.ini
COPY backend/docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-eventiq.conf
COPY backend/docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY backend/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8080 3310

ENTRYPOINT ["docker-entrypoint.sh"]
