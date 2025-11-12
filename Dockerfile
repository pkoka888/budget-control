# Budget Control - Production Dockerfile
# Based on Debian 13 (Trixie) with PHP 8.3, Nginx, and SQLite

FROM debian:trixie-slim

LABEL maintainer="Budget Control Team"
LABEL description="Budget Control - Personal Finance Management with Family Sharing"
LABEL version="2.0.0"

# Set environment variables
ENV DEBIAN_FRONTEND=noninteractive \
    PHP_VERSION=8.3 \
    APP_ENV=production \
    APP_DEBUG=false \
    TZ=Europe/Prague

# Install system dependencies and PHP 8.3
RUN apt-get update && apt-get install -y \
    nginx \
    php8.3-fpm \
    php8.3-cli \
    php8.3-common \
    php8.3-sqlite3 \
    php8.3-curl \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-zip \
    php8.3-gd \
    php8.3-intl \
    php8.3-bcmath \
    php8.3-opcache \
    sqlite3 \
    cron \
    supervisor \
    curl \
    wget \
    unzip \
    git \
    tzdata \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Set timezone
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Create application user and directories
RUN useradd -m -s /bin/bash -u 1000 budgetapp && \
    mkdir -p /var/www/html \
             /var/www/html/database \
             /var/www/html/logs \
             /var/www/html/sessions \
             /var/www/html/uploads/csv \
             /var/www/html/uploads/chores \
             /var/www/html/user-data/bank-json \
             /run/php \
             /var/log/supervisor && \
    chown -R budgetapp:budgetapp /var/www/html && \
    chown -R budgetapp:budgetapp /run/php

# Configure PHP-FPM
COPY docker/php/php-fpm.conf /etc/php/8.3/fpm/pool.d/www.conf
COPY docker/php/php.ini /etc/php/8.3/fpm/conf.d/99-custom.ini

# Configure Nginx
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/sites-available/default
RUN rm -f /etc/nginx/sites-enabled/default && \
    ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Copy application code
COPY --chown=budgetapp:budgetapp budget-app/ /var/www/html/

# Set proper permissions
RUN chmod -R 755 /var/www/html/public && \
    chmod -R 777 /var/www/html/database && \
    chmod -R 777 /var/www/html/logs && \
    chmod -R 777 /var/www/html/sessions && \
    chmod -R 777 /var/www/html/uploads && \
    chmod -R 777 /var/www/html/user-data

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install PHP dependencies (if composer.json exists)
WORKDIR /var/www/html
RUN if [ -f composer.json ]; then \
        composer install --no-dev --optimize-autoloader --no-interaction; \
    fi

# Configure cron for automation tasks
COPY docker/cron/budget-control /etc/cron.d/budget-control
RUN chmod 0644 /etc/cron.d/budget-control && \
    crontab -u budgetapp /etc/cron.d/budget-control

# Configure Supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# Expose HTTP port
EXPOSE 80

# Start services via Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
