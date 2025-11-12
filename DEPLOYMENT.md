# 🚀 Budget Control - Deployment Guide

Complete guide for deploying Budget Control in various environments.

## Table of Contents
- [Docker Deployment](#docker-deployment)
- [Traditional Server Deployment](#traditional-server-deployment)
- [Cloud Deployments](#cloud-deployments)
- [Performance Tuning](#performance-tuning)
- [Backup & Recovery](#backup--recovery)
- [Monitoring](#monitoring)
- [Troubleshooting](#troubleshooting)

## Docker Deployment

### Quick Start (Development)

```bash
# Clone repository
git clone https://github.com/yourusername/budget-control.git
cd budget-control

# Copy environment configuration
cp .env.example .env

# Edit .env as needed
nano .env

# Start containers
docker-compose up -d

# Initialize database
docker exec budget-control-app php /var/www/html/database/init.php

# Check status
docker-compose ps

# View logs
docker-compose logs -f budget-app

# Access application
open http://localhost:8080
```

### Production Docker Deployment

```bash
# Build production image
docker build -t budget-control:1.0.0 .

# Tag for registry
docker tag budget-control:1.0.0 your-registry/budget-control:1.0.0

# Push to registry
docker push your-registry/budget-control:1.0.0

# Deploy with production compose file
docker-compose -f docker-compose.yml up -d

# Health check
curl -f http://localhost:8080 || echo "Health check failed"
```

### Docker Environment Variables

```yaml
environment:
  # Application
  - APP_ENV=production
  - APP_DEBUG=false
  - APP_TIMEZONE=Europe/Prague

  # Database
  - DB_PATH=/var/www/html/database/budget.db

  # Security
  - SESSION_LIFETIME=3600
  - SESSION_SECURE=true

  # Features
  - FEATURE_2FA_ENABLED=true
  - FEATURE_AI_INSIGHTS=false
```

### Docker Volumes

```yaml
volumes:
  # Persistent database
  - ./budget-app/database:/var/www/html/database

  # File uploads
  - ./budget-app/uploads:/var/www/html/uploads

  # Bank JSON imports
  - ./user-data/bank-json:/var/www/html/user-data/bank-json

  # Development (optional - comment out in production)
  - ./budget-app/src:/var/www/html/src
  - ./budget-app/views:/var/www/html/views
```

## Traditional Server Deployment

### Ubuntu/Debian Server

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install dependencies
sudo apt install -y \
  apache2 \
  php8.4 \
  php8.4-cli \
  php8.4-sqlite3 \
  php8.4-mbstring \
  php8.4-xml \
  php8.4-curl \
  php8.4-zip \
  php8.4-gd \
  libapache2-mod-php8.4 \
  git \
  composer

# Enable Apache modules
sudo a2enmod rewrite headers expires deflate

# Clone application
cd /var/www
sudo git clone https://github.com/yourusername/budget-control.git
cd budget-control

# Install dependencies
cd budget-app
sudo composer install --no-dev --optimize-autoloader

# Set permissions
sudo chown -R www-data:www-data /var/www/budget-control
sudo chmod -R 755 /var/www/budget-control
sudo chmod -R 775 /var/www/budget-control/budget-app/database
sudo chmod -R 775 /var/www/budget-control/budget-app/uploads

# Initialize database
sudo -u www-data php /var/www/budget-control/budget-app/database/init.php

# Configure Apache VirtualHost
sudo nano /etc/apache2/sites-available/budget-control.conf
```

**Apache VirtualHost Configuration:**

```apache
<VirtualHost *:80>
    ServerName budget.yourdomain.com
    ServerAdmin admin@yourdomain.com

    DocumentRoot /var/www/budget-control/budget-app/public

    <Directory /var/www/budget-control/budget-app/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Logging
    ErrorLog ${APACHE_LOG_DIR}/budget-control-error.log
    CustomLog ${APACHE_LOG_DIR}/budget-control-access.log combined

    # Security headers (if not in .htaccess)
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set X-Content-Type-Options "nosniff"
</VirtualHost>
```

```bash
# Enable site
sudo a2ensite budget-control.conf

# Disable default site (optional)
sudo a2dissite 000-default.conf

# Test configuration
sudo apache2ctl configtest

# Restart Apache
sudo systemctl restart apache2

# Enable on boot
sudo systemctl enable apache2
```

### SSL/HTTPS Setup (Let's Encrypt)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-apache

# Obtain certificate
sudo certbot --apache -d budget.yourdomain.com

# Auto-renewal is set up automatically
sudo certbot renew --dry-run

# Verify SSL
curl -I https://budget.yourdomain.com
```

### PHP Configuration for Production

Edit `/etc/php/8.4/apache2/php.ini`:

```ini
; Error handling
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log

; Resource limits
memory_limit = 128M
max_execution_time = 30
max_input_time = 60

; File uploads
upload_max_filesize = 10M
post_max_size = 10M

; Session security
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1

; OPcache for production
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 60
opcache.fast_shutdown = 1
opcache.enable_cli = 0

; Timezone
date.timezone = Europe/Prague
```

```bash
# Create log directory
sudo mkdir -p /var/log/php
sudo chown www-data:www-data /var/log/php

# Restart Apache
sudo systemctl restart apache2
```

## Cloud Deployments

### AWS EC2

```bash
# Launch EC2 instance (Ubuntu 22.04 LTS)
# t2.micro or t3.small recommended for small deployments

# Connect via SSH
ssh -i your-key.pem ubuntu@your-instance-ip

# Follow Ubuntu/Debian deployment steps above

# Configure security group:
# - Port 80 (HTTP)
# - Port 443 (HTTPS)
# - Port 22 (SSH - restrict to your IP)

# Optional: Set up Elastic IP for static IP address
# Optional: Use RDS for database (requires migration from SQLite)
# Optional: Use S3 for file storage
```

### DigitalOcean Droplet

```bash
# Create Droplet (Ubuntu 22.04)
# $6/month Basic Droplet sufficient for small deployments

# Follow Ubuntu/Debian deployment steps

# Set up firewall
sudo ufw allow OpenSSH
sudo ufw allow 'Apache Full'
sudo ufw enable

# Optional: Use Managed Databases
# Optional: Use Spaces for file storage
```

### Heroku

```bash
# Create Heroku app
heroku create budget-control-app

# Add buildpack
heroku buildpacks:set heroku/php

# Create Procfile
echo "web: vendor/bin/heroku-php-apache2 budget-app/public/" > Procfile

# Deploy
git push heroku main

# Initialize database
heroku run php budget-app/database/init.php
```

## Performance Tuning

### OPcache Optimization

```ini
; /etc/php/8.4/mods-available/opcache.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
opcache.enable_cli=0
```

### Apache Optimization

```apache
# /etc/apache2/mods-available/mpm_prefork.conf
<IfModule mpm_prefork_module>
    StartServers             4
    MinSpareServers          2
    MaxSpareServers          6
    MaxRequestWorkers       150
    MaxConnectionsPerChild   3000
</IfModule>

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>
```

### Database Optimization

```sql
-- Run periodically to optimize database
VACUUM;
ANALYZE;

-- Check database size
SELECT page_count * page_size as size FROM pragma_page_count(), pragma_page_size();
```

### Caching Strategy

```php
// Enable OPcache reset (admin endpoint)
if (function_exists('opcache_reset')) {
    opcache_reset();
}

// Check OPcache status
opcache_get_status();
```

## Backup & Recovery

### Automated Database Backup

Create `/usr/local/bin/backup-budget-db.sh`:

```bash
#!/bin/bash
# Budget Control Database Backup Script

BACKUP_DIR="/backups/budget-control"
DB_PATH="/var/www/budget-control/budget-app/database/budget.db"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/budget_db_$TIMESTAMP.db"

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
sqlite3 $DB_PATH ".backup $BACKUP_FILE"

# Compress backup
gzip $BACKUP_FILE

# Keep only last 30 days
find $BACKUP_DIR -name "budget_db_*.db.gz" -mtime +30 -delete

echo "Backup completed: ${BACKUP_FILE}.gz"
```

```bash
# Make executable
sudo chmod +x /usr/local/bin/backup-budget-db.sh

# Add to crontab (daily at 2 AM)
sudo crontab -e
# Add: 0 2 * * * /usr/local/bin/backup-budget-db.sh
```

### Restore from Backup

```bash
# Stop Apache
sudo systemctl stop apache2

# Restore database
gunzip -c /backups/budget-control/budget_db_20251112_020000.db.gz > /var/www/budget-control/budget-app/database/budget.db

# Set permissions
sudo chown www-data:www-data /var/www/budget-control/budget-app/database/budget.db
sudo chmod 664 /var/www/budget-control/budget-app/database/budget.db

# Start Apache
sudo systemctl start apache2

# Verify
sudo -u www-data php -r "new PDO('sqlite:/var/www/budget-control/budget-app/database/budget.db');"
```

## Monitoring

### Log Monitoring

```bash
# Apache error logs
sudo tail -f /var/log/apache2/budget-control-error.log

# PHP error logs
sudo tail -f /var/log/php/error.log

# System logs
sudo journalctl -u apache2 -f
```

### Health Check Endpoint

Add to `budget-app/public/health.php`:

```php
<?php
header('Content-Type: application/json');

$health = [
    'status' => 'ok',
    'timestamp' => time(),
    'checks' => []
];

// Database check
try {
    $db = new PDO('sqlite:' . __DIR__ . '/../database/budget.db');
    $db->query("SELECT 1");
    $health['checks']['database'] = 'ok';
} catch (Exception $e) {
    $health['status'] = 'error';
    $health['checks']['database'] = 'error';
}

// Disk space check
$free = disk_free_space('/');
$total = disk_total_space('/');
$used_percent = (1 - $free / $total) * 100;

$health['checks']['disk_space'] = [
    'status' => $used_percent < 90 ? 'ok' : 'warning',
    'used_percent' => round($used_percent, 2)
];

http_response_code($health['status'] === 'ok' ? 200 : 500);
echo json_encode($health, JSON_PRETTY_PRINT);
```

### Monitoring with Uptime Robot

```bash
# Set up monitoring for:
# 1. Main application: https://budget.yourdomain.com
# 2. Health check: https://budget.yourdomain.com/health.php

# Monitor every 5 minutes
# Alert via email/SMS on downtime
```

## Troubleshooting

### Common Issues

#### 1. 500 Internal Server Error

```bash
# Check Apache logs
sudo tail -n 50 /var/log/apache2/budget-control-error.log

# Check PHP logs
sudo tail -n 50 /var/log/php/error.log

# Common causes:
# - Incorrect file permissions
# - Missing PHP extensions
# - Database not initialized
```

#### 2. Database Locked

```bash
# Check for stale locks
sudo fuser -v /var/www/budget-control/budget-app/database/budget.db

# Kill process if needed
sudo kill -9 <PID>

# Restart Apache
sudo systemctl restart apache2
```

#### 3. File Upload Fails

```bash
# Check PHP upload settings
php -i | grep upload_max_filesize
php -i | grep post_max_size

# Check directory permissions
ls -la /var/www/budget-control/budget-app/uploads/

# Fix permissions
sudo chown -R www-data:www-data /var/www/budget-control/budget-app/uploads/
sudo chmod -R 775 /var/www/budget-control/budget-app/uploads/
```

#### 4. Performance Issues

```bash
# Check OPcache status
php -i | grep opcache

# Enable OPcache if disabled
sudo phpenmod opcache
sudo systemctl restart apache2

# Check database size
du -h /var/www/budget-control/budget-app/database/budget.db

# Optimize database
sqlite3 /var/www/budget-control/budget-app/database/budget.db "VACUUM; ANALYZE;"
```

### Debug Mode

Enable in `.env` or `budget-app/src/Config.php`:

```php
'debug' => true
```

**⚠️ Never enable debug mode in production!**

### Log Collection Script

```bash
#!/bin/bash
# collect-logs.sh - Collect all relevant logs

LOG_DIR="/tmp/budget-control-logs-$(date +%Y%m%d_%H%M%S)"
mkdir -p $LOG_DIR

# Apache logs
sudo cp /var/log/apache2/budget-control-*.log $LOG_DIR/

# PHP logs
sudo cp /var/log/php/error.log $LOG_DIR/php-error.log

# System info
php -v > $LOG_DIR/php-version.txt
php -m > $LOG_DIR/php-modules.txt
apache2 -v > $LOG_DIR/apache-version.txt

# Database info
sqlite3 /var/www/budget-control/budget-app/database/budget.db ".dbinfo" > $LOG_DIR/db-info.txt

# Create archive
tar -czf budget-control-logs.tar.gz -C /tmp $(basename $LOG_DIR)

echo "Logs collected: budget-control-logs.tar.gz"
```

## Security Hardening

### File Permissions

```bash
# Secure file ownership
sudo chown -R www-data:www-data /var/www/budget-control
sudo find /var/www/budget-control -type f -exec chmod 644 {} \;
sudo find /var/www/budget-control -type d -exec chmod 755 {} \;

# Database writable
sudo chmod 664 /var/www/budget-control/budget-app/database/budget.db
sudo chmod 775 /var/www/budget-control/budget-app/database/

# Uploads writable
sudo chmod -R 775 /var/www/budget-control/budget-app/uploads/
```

### Firewall Configuration

```bash
# UFW (Ubuntu)
sudo ufw allow OpenSSH
sudo ufw allow 'Apache Full'
sudo ufw enable

# Limit SSH brute force
sudo ufw limit OpenSSH
```

### Fail2Ban

```bash
# Install
sudo apt install fail2ban

# Configure Apache jail
sudo nano /etc/fail2ban/jail.local
```

```ini
[apache-auth]
enabled = true
port = http,https
logpath = /var/log/apache2/*error.log
maxretry = 5
bantime = 3600
```

```bash
# Restart fail2ban
sudo systemctl restart fail2ban
```

## Maintenance

### Update Application

```bash
# Backup first!
/usr/local/bin/backup-budget-db.sh

# Pull updates
cd /var/www/budget-control
sudo git pull origin main

# Update dependencies
cd budget-app
sudo composer install --no-dev --optimize-autoloader

# Run migrations
sudo -u www-data php database/migrate.php

# Clear OPcache
sudo systemctl reload apache2

# Test
curl -f https://budget.yourdomain.com/health.php
```

### Regular Maintenance Tasks

```bash
# Weekly: Optimize database
sqlite3 /var/www/budget-control/budget-app/database/budget.db "VACUUM; ANALYZE;"

# Monthly: Review logs
sudo logrotate -f /etc/logrotate.d/apache2

# Monthly: Update system
sudo apt update && sudo apt upgrade -y
```

---

**Need help?** Check [GitHub Issues](https://github.com/yourusername/budget-control/issues) or the [Troubleshooting Guide](https://github.com/yourusername/budget-control/wiki/Troubleshooting).
