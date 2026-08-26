# ─── Eventiq Laravel Backend — Production Dockerfile ─────────────────────────
# Multi-stage build for Render deployment.
#
# Stage 1 (build): Install PHP extensions, Composer, Node.js, dependencies
# Stage 2 (runtime): PHP-FPM + Nginx + Node.js (MJML runtime dependency)
#
# Render configuration:
#   Root Directory: backend
#   Dockerfile:     Dockerfile
#   Port:           8080 (mapped to Render's $PORT via sed in entrypoint)
# ─────────────────────────────────────────────────────────────────────────────

# ══════════════════════════════════════════════════════════════════════════════
# Stage 1: Build dependencies
# ══════════════════════════════════════════════════════════════════════════════
FROM php:8.2-fpm-bookworm AS build

# System dependencies for building PHP extensions
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
    unzip \
    git \
    curl \
    ca-certificates \
    gnupg \
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
# - fileinfo:  file type detection (Laravel requirement)
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
COPY . .

# Finalize composer autoloader
RUN composer dump-autoload --optimize --no-dev

# ══════════════════════════════════════════════════════════════════════════════
# Stage 2: Runtime image
# ══════════════════════════════════════════════════════════════════════════════
FROM php:8.2-fpm-bookworm AS runtime

# Runtime system dependencies
RUN apt-get update -qq && apt-get install -y -qq --no-install-recommends \
    nginx \
    supervisor \
    libzip5 \
    libpng16-16 \
    libjpeg62-turbo \
    libfreetype6 \
    libonig5 \
    libxml2 \
    libpq5 \
    libcurl4 \
    libssl3 \
    libicu72 \
    ca-certificates \
    curl \
    sed \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions in runtime (same as build stage)
COPY --from=build /usr/local/lib/php/extensions/no-debug-non-zts-*/ /usr/local/lib/php/extensions/no-debug-non-zts-*/
COPY --from=build /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

# Install Node.js 20 LTS for MJML rendering
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y -qq nodejs \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy application from build stage
COPY --from=build --chown=www-data:www-data /var/www/html .

# Create www-data user if not exists (some PHP images don't have it)
RUN id -u www-data >/dev/null 2>&1 || \
    useradd -r -u 33 -g www-data -d /var/www/html -s /sbin/nologin www-data

# Create required directories
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

# Expose port (Render will override via PORT env var)
EXPOSE 8080

# Set entrypoint
ENTRYPOINT ["docker-entrypoint.sh"]
