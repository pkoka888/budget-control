# System Administration Handoff Request

**Date:** 2025-11-15
**From:** Claude Code (AI Assistant - user: claude)
**To:** System Administrator (root/sudo access)
**Priority:** 🔴 HIGH - Site is currently broken
**Status:** ⏳ PENDING IMPLEMENTATION

---

## Current Problem

### Site Status: ❌ BROKEN
- **URL:** http://budget.okamih.cz/
- **Issue:** Returns PHP Fatal Error
- **Error:** `Failed to open stream: No such file or directory in /var/www/html/public/index.php`

### Root Cause
Apache is configured to proxy to Docker (port 8080), but:
1. Docker container is NOT running
2. Application is deployed as traditional Apache/PHP-FPM
3. Apache DocumentRoot points to wrong directory

### Current Apache Configuration
```apache
<VirtualHost *:80>
    ServerName budget.okamih.cz
    ServerAdmin pavel.kaspar@okamih.cz

    # This is WRONG - Docker not running
    ProxyPass / http://127.0.0.1:8080/
    ProxyPassReverse / http://127.0.0.1:8080/
    ProxyPreserveHost On

    ErrorLog /budget.okamih.cz-error.log
    CustomLog /budget.okamih.cz-access.log combined
</VirtualHost>
```

---

## Requested Actions

### Option A: Traditional Apache/PHP-FPM Deployment (RECOMMENDED - Simpler)

**Estimated Time:** 5-10 minutes

1. **Update Apache VirtualHost Configuration**

Create/Edit: `/etc/apache2/sites-available/budget.okamih.cz.conf`

```apache
<VirtualHost *:80>
    ServerName budget.okamih.cz
    ServerAdmin pavel.kaspar@okamih.cz

    DocumentRoot /var/www/budget-control/budget-app/public

    <Directory /var/www/budget-control/budget-app/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted

        # URL rewriting for single-entry point
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php?/$1 [QSA,L]
    </Directory>

    # Protect sensitive directories
    <DirectoryMatch "^/var/www/budget-control/budget-app/(database|src|vendor|tests|uploads|storage|views|cli)">
        Require all denied
    </DirectoryMatch>

    # Allow uploads directory access (for uploaded files)
    <Directory /var/www/budget-control/budget-app/uploads>
        Options -Indexes
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/budget.okamih.cz-error.log
    CustomLog ${APACHE_LOG_DIR}/budget.okamih.cz-access.log combined

    # Optional: Redirect to HTTPS (if SSL configured)
    # RewriteEngine on
    # RewriteCond %{SERVER_NAME} =budget.okamih.cz
    # RewriteRule ^ https://%{SERVER_NAME}%{REQUEST_URI} [END,NE,R=permanent]
</VirtualHost>
```

2. **Enable Required Apache Modules**
```bash
sudo a2enmod rewrite
sudo a2enmod headers
```

3. **Set Proper Permissions**
```bash
# Application files
sudo chown -R claude:www-data /var/www/budget-control/budget-app/

# Writable directories (database, uploads, storage, logs)
sudo chmod 775 /var/www/budget-control/budget-app/database/
sudo chmod 664 /var/www/budget-control/budget-app/database/*.db
sudo chmod 775 /var/www/budget-control/budget-app/uploads/
sudo chmod 775 /var/www/budget-control/budget-app/storage/ 2>/dev/null || true
sudo chmod 775 /var/www/budget-control/budget-app/user-data/

# Ensure www-data can write
sudo find /var/www/budget-control/budget-app/database -type d -exec chmod 775 {} \;
sudo find /var/www/budget-control/budget-app/uploads -type d -exec chmod 775 {} \;
```

4. **Reload Apache**
```bash
sudo apachectl configtest
sudo systemctl reload apache2
```

5. **Test the Site**
```bash
curl -I http://budget.okamih.cz/
# Should return: HTTP/1.1 200 OK
```

---

### Option B: Docker Deployment (Original Plan - More Complex)

**Estimated Time:** 20-30 minutes

1. **Start Docker Container**
```bash
cd /var/www/budget-control/
sudo docker-compose up -d
```

2. **Check if Container is Running**
```bash
sudo docker ps | grep budget
# Should show container listening on port 8080
```

3. **Keep Apache Proxy Configuration** (already configured correctly)

4. **Test**
```bash
curl http://127.0.0.1:8080/
# Should return application HTML
curl -I http://budget.okamih.cz/
# Should return: HTTP/1.1 200 OK
```

---

## Additional Tasks (Optional but Recommended)

### 1. Grant Docker Access to Claude User
If choosing Docker deployment:
```bash
sudo usermod -aG docker claude
# Claude will need to logout/login for this to take effect
```

### 2. Enable HTTPS with Let's Encrypt
```bash
sudo apt-get install certbot python3-certbot-apache
sudo certbot --apache -d budget.okamih.cz
```

### 3. Set Up Automated Backups (Critical)
```bash
# Make backup script executable
sudo chmod +x /var/www/budget-control/scripts/backup-database.sh

# Add to crontab (run daily at 2 AM)
sudo crontab -e
# Add line:
0 2 * * * /var/www/budget-control/scripts/backup-database.sh --cloud
```

### 4. Configure PHP-FPM for Better Performance
Edit: `/etc/php/8.4/fpm/pool.d/www.conf`
```ini
; Increase process limits for production
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500
```

Then restart:
```bash
sudo systemctl restart php8.4-fpm
```

---

## Verification Checklist

After implementation, verify:

- [ ] Site loads without errors: http://budget.okamih.cz/
- [ ] Can access login page: http://budget.okamih.cz/login
- [ ] Database is accessible (no permission errors)
- [ ] File uploads work (test with CSV import)
- [ ] No PHP errors in Apache error log:
  ```bash
  sudo tail -f /var/log/apache2/budget.okamih.cz-error.log
  ```
- [ ] Sessions work (can login and stay logged in)
- [ ] Static assets load (CSS, JS)

---

## Post-Implementation

**When complete, please:**

1. Create file: `HANDOFF-SYSADMIN-2025-11-15-IMPLEMENTED.md`
2. Include:
   - Which option you chose (A or B)
   - Any issues encountered
   - Current site status
   - Any additional changes made

3. Notify Claude Code user (claude) so AI assistant can verify and proceed with testing

---

## Contact Information

**For Questions:**
- System Admin: pavel.kaspar@okamih.cz
- AI Assistant: Claude Code (user: claude, SSH access only)

**Logs to Check:**
- Apache Error: `/var/log/apache2/budget.okamih.cz-error.log`
- Apache Access: `/var/log/apache2/budget.okamih.cz-access.log`
- PHP-FPM: `/var/log/php8.4-fpm.log`

---

## Critical Files Locations

```
/var/www/budget-control/
├── budget-app/
│   ├── public/          # Web root (DocumentRoot should point here)
│   │   └── index.php    # Application entry point
│   ├── database/        # SQLite database (needs 775 permissions)
│   │   └── budget.db    # Main database file (1.6 MB)
│   ├── uploads/         # User uploads (needs 775 permissions)
│   ├── src/             # Application code (protect from web access)
│   └── .env             # Configuration (protect from web access)
├── docker-compose.yml   # If using Docker
└── scripts/
    └── backup-database.sh  # Automated backup script
```

---

**Priority:** 🔴 HIGH
**Impact:** Site is currently non-functional
**Recommended Option:** A (Traditional Apache/PHP-FPM)
**Estimated Downtime:** 5-10 minutes

---

**END OF HANDOFF REQUEST**
