# 🚀 Budget Control Application - Deployment Guide

**Status**: Application Complete & Ready
**Deployment Date**: November 9, 2025
**Environment**: PHP 7.4+ with SQLite3

---

## 📋 **Pre-Deployment Checklist**

### **System Requirements**
- [ ] PHP 7.4 or higher installed
- [ ] SQLite3 extension enabled
- [ ] Web server (Apache, Nginx, or PHP built-in)
- [ ] Composer installed (for dependency management)
- [ ] Git (optional, for version control)

### **Deployment Environment**
- [ ] Production server ready
- [ ] Domain/subdomain configured
- [ ] SSL certificate installed (recommended)
- [ ] Database backup location secured
- [ ] File permissions configured

---

## 🔧 **Installation Steps**

### **Step 1: Clone/Transfer Files**

```bash
# Clone the repository (if using Git)
git clone <repository-url> budget-control
cd budget-control/budget-app

# OR manually transfer files to your server
scp -r budget-control/ user@server:/var/www/
```

### **Step 2: Install Dependencies**

```bash
# Navigate to application directory
cd budget-app

# Install composer dependencies
composer install

# Or if composer is not available, the application works without external dependencies
# All required libraries are included
```

### **Step 3: Initialize Database**

```bash
# The database will be created automatically on first run
# But you can initialize it manually:

# Copy the schema file to database directory
cp database/schema.sql database/

# Database file will be created at: database/budget.sqlite
# Ensure database directory is writable:
chmod 755 database/
chmod 644 database/budget.sqlite (after creation)
```

### **Step 4: Configure File Permissions**

```bash
# Web server user needs write access to:
sudo chown -R www-data:www-data database/
sudo chmod -R 755 database/

# Public assets should be readable:
sudo chmod -R 644 public/assets/
```

### **Step 5: Configure Web Server**

#### **Apache Configuration**

```apache
<VirtualHost *:80>
    ServerName budget-control.example.com
    DocumentRoot /var/www/budget-control/budget-app/public

    <Directory /var/www/budget-control/budget-app/public>
        AllowOverride All
        Require all granted

        # Enable mod_rewrite
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^ index.php [QSA,L]
        </IfModule>
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/budget-control-error.log
    CustomLog ${APACHE_LOG_DIR}/budget-control-access.log combined
</VirtualHost>
```

#### **Nginx Configuration**

```nginx
server {
    listen 80;
    server_name budget-control.example.com;
    root /var/www/budget-control/budget-app/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    access_log /var/log/nginx/budget-control-access.log;
    error_log /var/log/nginx/budget-control-error.log;
}
```

#### **PHP Built-in Server (Development)**

```bash
# Navigate to public directory
cd budget-app/public

# Start built-in server
php -S localhost:8000

# Application will be available at: http://localhost:8000
```

---

## 🧪 **Post-Deployment Verification**

### **Step 1: Test Application Access**

```bash
# Verify the application loads
curl -I http://budget-control.example.com/

# Should return HTTP 200 OK
```

### **Step 2: Test API Endpoints**

```bash
# Test API authentication
curl -X GET http://budget-control.example.com/api/users \
  -H "Authorization: Bearer YOUR_API_KEY"

# Should return proper JSON response
```

### **Step 3: Test Database Connection**

```bash
# Check if database is accessible
ls -la database/budget.sqlite

# Should show the database file exists
```

### **Step 4: Test File Uploads**

```bash
# Verify CSV import functionality
# Try uploading a test CSV file through the web interface
```

### **Step 5: Test Email Notifications** (if configured)

```bash
# Check email configuration in env file
# Test by triggering a notification
```

---

## 📁 **Directory Structure**

```
budget-control/
├── budget-app/
│   ├── public/                 # Web root
│   │   ├── index.php          # Entry point
│   │   ├── assets/            # CSS, JS, images
│   │   └── uploads/           # User uploads
│   ├── src/
│   │   ├── Controllers/       # Request handlers
│   │   ├── Services/          # Business logic
│   │   ├── Middleware/        # Authentication, rate limiting
│   │   └── Database.php       # Database abstraction
│   ├── views/                 # View templates
│   ├── database/              # Database files
│   │   ├── schema.sql        # Database schema
│   │   └── budget.sqlite     # SQLite database
│   ├── docs/
│   │   ├── API.md            # API documentation
│   │   └── ...               # Additional docs
│   └── ...
└── [documentation files]
```

---

## 🔐 **Security Configuration**

### **1. Set Up Environment Variables**

Create `.env` file in budget-app directory:

```env
APP_NAME=Budget Control
APP_ENV=production
APP_DEBUG=false
APP_URL=https://budget-control.example.com

DB_CONNECTION=sqlite
DB_DATABASE=database/budget.sqlite

# API Configuration
API_RATE_LIMIT=100
API_RATE_WINDOW=900

# Email Configuration (optional)
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password

# 2FA Configuration
TWO_FACTOR_ENABLED=true
TWO_FACTOR_ISSUER=Budget Control
```

### **2. Configure SSL/TLS**

```bash
# Using Let's Encrypt (recommended)
sudo certbot certonly --webroot -w /var/www/budget-control/budget-app/public \
  -d budget-control.example.com

# Update your web server configuration to use SSL
```

### **3. Set File Permissions**

```bash
# Restrict sensitive files
chmod 600 database/budget.sqlite
chmod 600 .env

# Ensure uploads directory is writable but not executable
chmod 755 public/uploads/
find public/uploads/ -type f -exec chmod 644 {} \;
```

### **4. Configure Firewall**

```bash
# Allow HTTP and HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Block other ports
sudo ufw default deny incoming
sudo ufw default allow outgoing
```

---

## 📊 **Monitoring & Maintenance**

### **Enable Logging**

Update `src/Database.php` to enable query logging:

```php
// Uncomment logging in development
// file_put_contents('logs/queries.log', $query . "\n", FILE_APPEND);
```

### **Regular Backups**

```bash
# Create daily backups
0 2 * * * /usr/local/bin/backup-budget-app.sh

# Backup script content:
#!/bin/bash
BACKUP_DIR="/backups/budget-control"
DB_FILE="/var/www/budget-control/budget-app/database/budget.sqlite"
DATE=$(date +\%Y-\%m-\%d)

mkdir -p $BACKUP_DIR
cp $DB_FILE $BACKUP_DIR/budget-$DATE.sqlite
find $BACKUP_DIR -name "budget-*.sqlite" -mtime +30 -delete
```

### **Monitor Performance**

```bash
# Check server load
top -b -n 1 | head -5

# Check disk space
df -h

# Check database size
ls -lh database/budget.sqlite
```

### **Check Logs**

```bash
# Application logs
tail -f logs/app.log

# Web server error logs
tail -f /var/log/nginx/budget-control-error.log

# PHP error logs
tail -f /var/log/php-fpm.log
```

---

## 🚨 **Troubleshooting**

### **Issue: Database Permission Denied**

```bash
# Solution: Fix permissions
sudo chown www-data:www-data database/
sudo chmod 755 database/
sudo chmod 644 database/budget.sqlite
```

### **Issue: 404 Errors on Routes**

```bash
# Solution: Enable mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2

# Or for Nginx: Check location block has try_files directive
```

### **Issue: Slow Performance**

```bash
# Solution: Check database indexes
# Verify database/schema.sql has all indexes

# Clear cache if applicable
rm -rf cache/*

# Check PHP settings
php -i | grep memory_limit
```

### **Issue: API Authentication Failing**

```bash
# Solution: Verify API key setup
# Generate new API key in settings panel
# Check Authorization header format: "Authorization: Bearer YOUR_KEY"
```

### **Issue: Email Notifications Not Sending**

```bash
# Solution: Test mail configuration
# Check .env MAIL_* settings
# Verify server can reach mail server
telnet smtp.mailtrap.io 465
```

---

## 📈 **Performance Optimization**

### **Database Optimization**

```sql
-- Run database maintenance
VACUUM;
ANALYZE;

-- Rebuild indexes if needed
REINDEX;
```

### **PHP Configuration**

Update `php.ini`:

```ini
# Increase limits for larger datasets
max_execution_time = 300
memory_limit = 256M
upload_max_filesize = 100M
post_max_size = 100M

# Enable OPcache for performance
opcache.enable = 1
opcache.memory_consumption = 128
```

### **Web Server Optimization**

Apache:
```apache
# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript
</IfModule>

# Enable caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
</IfModule>
```

---

## 🔄 **Updating the Application**

### **Pull Latest Changes**

```bash
cd budget-control
git pull origin main

# Update dependencies
composer update

# Run database migrations (if any)
php artisan migrate
```

### **Rollback if Issues**

```bash
# Revert to previous version
git checkout HEAD~1

# Restore database backup
cp /backups/budget-control/budget-YYYY-MM-DD.sqlite database/budget.sqlite
```

---

## 📞 **Support & Documentation**

### **API Documentation**
See `docs/API.md` for complete API reference

### **User Guide**
See project documentation for user instructions

### **Admin Guide**
See deployment documentation for administration

---

## ✅ **Deployment Checklist**

- [ ] System requirements verified
- [ ] Files transferred to server
- [ ] Dependencies installed
- [ ] Database initialized
- [ ] File permissions set correctly
- [ ] Web server configured
- [ ] SSL certificate installed
- [ ] Environment variables configured
- [ ] API endpoints tested
- [ ] Database backup configured
- [ ] Logging enabled
- [ ] Monitoring configured
- [ ] Admin account created
- [ ] Application tested
- [ ] Go-live plan confirmed
- [ ] Support team trained

---

## 🎉 **Go Live!**

Once all checks pass:

```bash
# Switch to production (update DNS if applicable)
# Monitor application for 24-48 hours
# Watch error logs for any issues
# Prepare support documentation
# Notify users of launch
```

---

**Application is ready for deployment!** 🚀

All components are tested, documented, and production-ready.

**Follow these steps for a smooth deployment.**

---

*Deployment Guide Created*: November 9, 2025
*Application Status*: Ready for Production
*Support Available*: Yes
