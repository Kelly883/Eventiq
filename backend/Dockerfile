FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    zip \
    curl \
    libpq-dev \
    icu-dev \
    gmp-dev \
    libxml2-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    supervisor \
    nginx \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd \
    mbstring \
    xml \
    bcmath \
    intl \
    pdo \
    pdo_pgsql \
    pgsql \
    pcntl \
    exif \
    fileinfo \
    && docker-php-ext-enable \
    gd \
    mbstring \
    xml \
    bcmath \
    intl \
    pdo \
    pdo_pgsql \
    pcntl \
    exif \
    fileinfo

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install Node.js LTS (required for MJML email rendering — see app/Services/MjmlRenderer.php)
ENV NODE_VERSION=20.18.1
RUN curl -fsSL https://nodejs.org/dist/v${NODE_VERSION}/node-v${NODE_VERSION}-linux-x64.tar.xz -o /tmp/node.tar.xz && \
    tar -xJf /tmp/node.tar.xz -C /usr/local --strip-components=1 && \
    rm /tmp/node.tar.xz && \
    node --version && npm --version

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install Node dependencies for MJML
RUN cd node-tools/mjml && npm install --production && cd /var/www/html

# Install PHP dependencies (production only)
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

# Create required directories
RUN mkdir -p storage/framework/{sessions,views,cache/data,cache/locks} \
    storage/logs \
    bootstrap/cache

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache

# Symlink public storage
RUN php artisan storage:link

# Copy configurations
COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create nginx user (matches nginx.conf `user nginx;`)
RUN addgroup -g 1000 nginx 2>/dev/null || true && \
    adduser -u 1000 -D -G nginx nginx 2>/dev/null || true

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:${PORT:-80}/health || exit 1

# Copy entrypoint
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]
