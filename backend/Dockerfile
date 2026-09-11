FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    clamav \
    clamav-daemon \
    clamav-freshclam \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo pdo_pgsql gd zip bcmath

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Update ClamAV signatures
RUN freshclam

# Set up ClamAV daemon
RUN mkdir -p /var/run/clamav && chown -R www-data:www-data /var/run/clamav

# Copy application
COPY backend/ /var/www/html/
WORKDIR /var/www/html

# Install dependencies
RUN composer install --no-interaction --prefer-dist --no-progress --no-scripts

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage

# Expose ClamAV socket
EXPOSE 3310

# Start ClamAV daemon and PHP-FPM
CMD sh -c "freshclam && clamd --foreground --config-file=/etc/clamav/clamd.conf & php-fpm"
