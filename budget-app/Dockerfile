# Budget Control - PHP Application Dockerfile
FROM node:20-alpine AS css-builder

# Set working directory
WORKDIR /build

# Copy package.json and postcss/tailwind configs
COPY package.json postcss.config.js tailwind.config.js ./

# Copy CSS source and views (needed for Tailwind content scanning)
COPY src/input.css ./src/input.css
COPY views ./views

# Install Node dependencies
RUN npm install

# Build CSS
RUN npm run build:css

# ===== PHP Stage =====
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip pdo_sqlite

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock* ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy application code
COPY . .

# Copy compiled CSS from builder stage
COPY --from=css-builder /build/public/assets/css/tailwind.css ./public/assets/css/tailwind.css

# Create necessary directories
RUN mkdir -p database uploads/csv storage/logs storage/cache user-data/bank-json \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 database uploads/csv storage user-data

# Configure Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && a2enmod rewrite \
    && echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
