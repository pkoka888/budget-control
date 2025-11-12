# Budget Control - Docker Configuration
# Multi-stage build for optimized production image

FROM php:8.4-apache AS base

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_sqlite \
    sqlite3 \
    pdo_mysql \
    gd \
    zip \
    intl \
    opcache

# Enable Apache modules
RUN a2enmod rewrite headers expires deflate

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure PHP for production
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=60'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'upload_max_filesize=10M'; \
    echo 'post_max_size=10M'; \
    echo 'memory_limit=128M'; \
    echo 'max_execution_time=30'; \
    echo 'date.timezone=Europe/Prague'; \
} > /usr/local/etc/php/conf.d/budget-control.ini

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY budget-app/ /var/www/html/

# Configure Apache DocumentRoot
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Create necessary directories
RUN mkdir -p /var/www/html/database \
    /var/www/html/uploads/csv \
    /var/www/html-data/bank-json \
    /var/www/html/public/uploads \
    && chmod -R 775 /var/www/html/database \
    /var/www/html/uploads \
    /var/www/html/public/uploads

# Install Composer dependencies (if composer.json exists)
RUN if [ -f composer.json ]; then \
    composer install --no-dev --optimize-autoloader --no-interaction; \
    fi

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Expose port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
