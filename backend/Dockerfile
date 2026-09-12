# ─── Eventiq Laravel Backend — Production Dockerfile ─────────────────────────
# Single-stage build for Render deployment.
#
# PHP 8.3 FPM + Nginx + Node.js 20 LTS (MJML runtime dependency) + ClamAV
# Supervisord manages nginx + php-fpm (+ optional clamd) processes.
#
# Render configuration (recommended):
#   Root Directory: backend
#   Dockerfile:     Dockerfile        (relative to Root Directory)
#   Build Context:  backend/          (so COPY . works)
#   Port:           8080 (mapped to Render's $PORT via sed in entrypoint)
#
# Alternative (repo-root context):
#   docker build -f backend/Dockerfile .   → use root Dockerfile instead
#   docker build ./backend                 → uses this file with backend context
#   The COPY instructions below assume the build context is `backend/`.
#   Using COPY backend/ would fail with "not found" when context is backend/.
# ─────────────────────────────────────────────────────────────────────────────

FROM php:8.3-fpm-bookworm

# System dependencies for PHP extensions + runtime services
RUN apt-get update -qq && apt-get install -y -qq --no-install-recommends \
    # PHP extension build deps (also serves as runtime libs)
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libcurl4-openssl-dev \
    libssl-dev \
    # ClamAV (optional virus scanning, disabled by default via CLAMAV_ENABLED=false)
    clamav \
    clamav-daemon \
    clamav-freshclam \
    # Runtime services
    nginx \
    supervisor \
    ca-certificates \
    curl \
    sed \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions required by Eventiq
# - pdo_pgsql: PostgreSQL database
# - mbstring:  multibyte string handling (Laravel requirement)
# - xml:       XML processing (Laravel requirement)
# - curl:      HTTP client (Laravel requirement)
# - zip:       ZIP handling
# - gd:        image processing (QR codes via bacon/bacon-qr-code + simplesoftwareio/simple-qrcode)
# - bcmath:    arbitrary precision math (Paystack/Flutterwave monetary calculations)
# - iconv:     character encoding (Laravel requirement)
# - opcache:   bytecode caching (production performance)
# - intl:      internationalization
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    pgsql \
    mbstring \
    xml \
    curl \
    zip \
    gd \
    bcmath \
    iconv \
    intl \
    opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set up ClamAV (create run dir; signatures updated at runtime via entrypoint)
RUN mkdir -p /var/run/clamav /var/log/clamav /var/lib/clamav \
    && chown -R www-data:www-data /var/run/clamav \
    && freshclam || true

WORKDIR /var/www/html

# Copy only composer files first (layer caching for dependency install)
COPY composer.json composer.lock ./

# Install production dependencies only
RUN composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts \
    --no-autoloader

# Install Node.js 20 LTS for MJML email rendering (RUNTIME dependency)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y -qq nodejs \
    && rm -rf /var/lib/apt/lists/*

# Copy MJML package files and install
COPY node-tools/mjml/package.json node-tools/mjml/package-lock.json* node-tools/mjml/
RUN cd node-tools/mjml && npm install --production --no-audit --no-fund

# Copy full application source
# NOTE: Context is `backend/` — so COPY . copies backend contents to /var/www/html.
# Do NOT use `COPY backend/ /var/www/html/` — that only works when context is repo root
# and fails with "failed to calculate checksum of ref ...: /backend: not found" when
# context is backend/ (Render's Root Directory = backend).
COPY . .

# Finalize composer autoloader
RUN composer dump-autoload --optimize --no-dev

# Create required directories with correct ownership
RUN mkdir -p \
    storage/logs \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/app/public \
    storage/app/private \
    bootstrap/cache \
    /var/log/nginx \
    /var/log/supervisor \
    /run \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Copy configuration files
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/99-eventiq.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-eventiq.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

# Make scripts executable
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port (Render will override via PORT env var) + ClamAV socket
EXPOSE 8080 3310

# Set entrypoint
ENTRYPOINT ["docker-entrypoint.sh"]
