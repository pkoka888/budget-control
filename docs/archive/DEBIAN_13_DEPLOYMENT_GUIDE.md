# 🐧 **Budget Control - Debian 13 Production Deployment Guide**

**Target OS**: Debian 13
**Date**: November 9, 2025
**Status**: Production Ready
**Security**: High Priority
**Accessibility**: NAT/Firewall Protected

---

## 📋 **Deployment Architecture**

```
┌─────────────────────────────────────────────────────────────┐
│                    EXTERNAL INTERNET                         │
└──────────────────────────┬──────────────────────────────────┘
                           │
                    Public IP Address
                           │
        ┌──────────────────┴──────────────────┐
        │                                     │
   Port 2222 (SSH)                  Ports 8080/8443 (HTTP/HTTPS)
        │                                     │
        └──────────────────┬──────────────────┘
                           │
        ┌──────────────────┴──────────────────┐
        │    nftables Firewall (Drop All)     │
        │  Only Allow: 2222, 8080, 8443       │
        └──────────────────┬──────────────────┘
                           │
        ┌──────────────────┴──────────────────┐
        │                                     │
    SSH Port 22             Reverse Proxy Port 80/443
    (internal)              Nginx (listens on 8080/8443)
        │                            │
        │                   ┌────────┴────────┐
        │                   │                 │
    sshd              Apache Backend        PHP-FPM
                    (Port 7070, internal)  (Port 9000)
                           │
                      SQLite DB
```

---

## 🔧 **Part 1: Fresh Debian 13 Installation**

### **Step 1.1: Minimal System Setup**

```bash
# Update system to latest
sudo apt update
sudo apt upgrade -y

# Install essential packages
sudo apt install -y \
    curl \
    wget \
    git \
    vim \
    htop \
    net-tools \
    netcat-traditional \
    ntp \
    ufw \
    fail2ban \
    aide \
    auditd

# Set timezone
sudo timedatectl set-timezone UTC

# Enable NTP service
sudo systemctl enable systemd-timesyncd
sudo systemctl start systemd-timesyncd
```

### **Step 1.2: Kernel Hardening**

Edit `/etc/sysctl.conf` and add:

```bash
# IP forwarding (needed for NAT)
net.ipv4.ip_forward = 1

# Prevent source routing
net.ipv4.conf.all.accept_source_route = 0
net.ipv4.conf.default.accept_source_route = 0

# Enable reverse path filtering
net.ipv4.conf.all.rp_filter = 1
net.ipv4.conf.default.rp_filter = 1

# Ignore ICMP redirects
net.ipv4.conf.all.accept_redirects = 0
net.ipv4.conf.default.accept_redirects = 0

# Enable TCP SYN cookies
net.ipv4.tcp_syncookies = 1

# Log suspicious packets
net.ipv4.conf.all.log_martians = 1

# Restrict access to kernel parameters
kernel.kptr_restrict = 2
kernel.dmesg_restrict = 1

# Restrict access to kernel logs
kernel.printk = 3 3 3 3

# Panic on out-of-memory
vm.panic_on_oom = 1

# Restrict access to SysRq
kernel.sysrq = 0

# Restrict module loading
kernel.modules_disabled = 1
```

Apply changes:
```bash
sudo sysctl -p
```

### **Step 1.3: Secure SSH Configuration**

Edit `/etc/ssh/sshd_config`:

```bash
# Change port to 2222
Port 2222

# Disable root login
PermitRootLogin no

# Use public key authentication only
PubkeyAuthentication yes
PasswordAuthentication no
PermitEmptyPasswords no

# Restrict users (optional)
AllowUsers budgetapp
AllowGroups budgetapp

# Disable X11 forwarding
X11Forwarding no

# Set timeouts
ClientAliveInterval 300
ClientAliveCountMax 2
LoginGraceTime 30

# Limit authentication attempts
MaxAuthTries 3
MaxSessions 2

# Disable dangerous features
AllowAgentForwarding no
AllowTcpForwarding no
PermitTunnel no

# Logging
SyslogFacility AUTH
LogLevel VERBOSE
```

Restart SSH:
```bash
sudo systemctl restart sshd
```

**Important**: Verify SSH login works before logging out, as you could be locked out!

### **Step 1.4: Create Application User**

```bash
# Create non-root user for application
sudo useradd -m -s /bin/false -d /var/www/budget-control budgetapp

# Create group
sudo groupadd budgetapp
sudo usermod -a -G budgetapp budgetapp

# Add sudo access for web server management
echo "budgetapp ALL=(ALL) NOPASSWD: /bin/systemctl restart nginx,/bin/systemctl restart php-fpm,/bin/systemctl restart apache2" | sudo tee -a /etc/sudoers.d/budgetapp

# Set proper permissions
sudo chmod 0440 /etc/sudoers.d/budgetapp
```

### **Step 1.5: Configure Automatic Security Updates**

```bash
# Install unattended upgrades
sudo apt install -y unattended-upgrades apt-listchanges

# Configure auto-updates
sudo dpkg-reconfigure -plow unattended-upgrades

# Edit /etc/apt/apt.conf.d/50unattended-upgrades:
APT::Periodic::Update-Package-Lists "1";
APT::Periodic::Download-Upgradeable-Packages "1";
APT::Periodic::AutocleanInterval "7";
APT::Periodic::Unattended-Upgrade "1";
APT::Periodic::Reboot "false";
APT::Periodic::RebootWithoutAuth "false";
```

---

## 🌍 **Part 2: Network & Firewall Configuration**

### **Step 2.1: nftables Firewall Setup**

Debian 13 uses **nftables** (modern replacement for iptables).

**Create firewall configuration** at `/etc/nftables.conf`:

```bash
#!/usr/sbin/nft -f

# Clear existing rules
flush ruleset

# Define variables
define WAN_INTERFACE = "eth0"
define SSH_PORT = 2222
define HTTP_PORT = 8080
define HTTPS_PORT = 8443

# Filter table
table inet filter {
    # Chain: input (incoming traffic)
    chain input {
        type filter hook input priority 0; policy drop;

        # Accept loopback
        iif lo accept

        # Accept established/related connections
        ct state established,related accept

        # Accept SSH on custom port
        tcp dport $SSH_PORT ct state new,established accept

        # Accept HTTP on port 8080
        tcp dport $HTTP_PORT ct state new,established accept

        # Accept HTTPS on port 8443
        tcp dport $HTTPS_PORT ct state new,established accept

        # ICMP for diagnostics (rate limited)
        ip protocol icmp limit rate 4/second accept
        ip6 nexthdr icmpv6 limit rate 4/second accept

        # Log dropped packets (optional, useful for debugging)
        log prefix "[NFTABLES] INPUT DROP: " flags all limit rate 1/second drop
        drop
    }

    # Chain: forward (packet forwarding)
    chain forward {
        type filter hook forward priority 0; policy drop;
        ct state established,related accept
    }

    # Chain: output (outgoing traffic)
    chain output {
        type filter hook output priority 0; policy accept;
    }
}

# NAT table for port mapping
table ip nat {
    # Prerouting chain (incoming connections)
    chain prerouting {
        type nat hook prerouting priority -100; policy accept;

        # Forward port 8080 to internal port 80 (HTTP)
        iif $WAN_INTERFACE tcp dport $HTTP_PORT dnat to 127.0.0.1:80

        # Forward port 8443 to internal port 443 (HTTPS)
        iif $WAN_INTERFACE tcp dport $HTTPS_PORT dnat to 127.0.0.1:443
    }

    # Postrouting chain (outgoing connections)
    chain postrouting {
        type nat hook postrouting priority 100; policy accept;
    }
}
```

**Enable nftables:**

```bash
# Test configuration for syntax errors
sudo nft -f /etc/nftables.conf

# Enable and start service
sudo systemctl enable nftables
sudo systemctl start nftables

# Verify rules loaded
sudo nft list ruleset
```

### **Step 2.2: Verify Firewall Rules**

```bash
# List all rules
sudo nft list ruleset

# Check specific chains
sudo nft list chain inet filter input
sudo nft list chain ip nat prerouting

# Check open ports
sudo netstat -tlnp | grep LISTEN

# Check nftables status
sudo systemctl status nftables
```

### **Step 2.3: Rate Limiting (Optional Enhancement)**

Add DDoS protection by modifying `/etc/nftables.conf`:

```bash
table inet filter {
    # Define rate limits
    set ssh_ratelimit {
        type ipv4_addr
        flags timeout
        timeout 1m
    }

    set http_ratelimit {
        type ipv4_addr
        flags timeout
        timeout 10s
    }

    chain input {
        # ... existing rules ...

        # SSH rate limiting (5 connections per minute)
        tcp dport 2222 ct state new add @ssh_ratelimit { ip saddr } limit rate 5/minute accept

        # HTTP rate limiting (100 connections per 10 seconds)
        tcp dport 8080,8443 ct state new add @http_ratelimit { ip saddr } limit rate 100/10s accept
    }
}
```

---

## 📦 **Part 3: Install Web Stack**

### **Step 3.1: Install PHP and SQLite**

```bash
# Add PHP repository (for PHP 8.2)
sudo apt install -y lsb-release apt-transport-https ca-certificates

# Install PHP 8.2 with required extensions
sudo apt install -y \
    php8.2 \
    php8.2-fpm \
    php8.2-cli \
    php8.2-sqlite3 \
    php8.2-mbstring \
    php8.2-curl \
    php8.2-zip \
    php8.2-gd \
    php8.2-json \
    php8.2-xml \
    libsqlite3-dev \
    sqlite3

# Verify PHP installation
php -v
php -m | grep sqlite3

# Enable PHP-FPM
sudo systemctl enable php8.2-fpm
sudo systemctl start php8.2-fpm
```

**Configure PHP for security** - Edit `/etc/php/8.2/fpm/php.ini`:

```ini
# Security settings
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log
allow_url_fopen = Off
allow_url_include = Off

# Disable dangerous functions
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source

# Limits
max_execution_time = 30
max_input_time = 60
memory_limit = 256M
post_max_size = 20M
upload_max_filesize = 20M

# Session security
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = Strict
session.use_strict_mode = 1
session.name = BUDGETAPPSESSID

# File upload
upload_tmp_dir = /var/lib/php/sessions
```

Create log directory:
```bash
sudo mkdir -p /var/log/php
sudo chown www-data:www-data /var/log/php
sudo chmod 755 /var/log/php
```

Restart PHP-FPM:
```bash
sudo systemctl restart php8.2-fpm
```

### **Step 3.2: Install Nginx (Reverse Proxy)**

```bash
# Install Nginx
sudo apt install -y nginx

# Enable and start
sudo systemctl enable nginx
sudo systemctl start nginx
```

**Configure Nginx reverse proxy** - Create `/etc/nginx/sites-available/budget-control`:

```nginx
# Redirect HTTP to HTTPS
server {
    listen 8080;
    server_name _;

    return 301 https://$host:8443$request_uri;
}

# HTTPS with reverse proxy
server {
    listen 8443 ssl http2;
    server_name example.com;

    # SSL certificates (we'll setup Let's Encrypt later)
    ssl_certificate /etc/letsencrypt/live/example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/example.com/privkey.pem;

    # SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384';
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    ssl_session_tickets off;
    ssl_stapling on;
    ssl_stapling_verify on;

    # Security headers
    add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self' https:; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';" always;

    # Logging
    access_log /var/log/nginx/budget-control-access.log combined;
    error_log /var/log/nginx/budget-control-error.log warn;

    # Performance
    client_max_body_size 20M;
    proxy_buffer_size 4k;
    proxy_buffers 8 4k;
    proxy_busy_buffers_size 8k;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1000;
    gzip_types text/plain text/css application/json application/javascript text/xml;

    # Reverse proxy to Apache backend
    location / {
        proxy_pass http://127.0.0.1:7070;

        # Essential headers for backend
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
        proxy_set_header X-Forwarded-Host $server_name;
        proxy_set_header X-Forwarded-Port $server_port;

        # WebSocket support
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";

        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }

    # Static file caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2)$ {
        proxy_pass http://127.0.0.1:7070;
        proxy_cache_valid 200 1d;
        expires 1d;
        add_header Cache-Control "public, immutable";
    }
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/budget-control /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

# Test configuration
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
```

### **Step 3.3: Install Apache (Backend)**

```bash
# Install Apache with PHP-FPM support
sudo apt install -y \
    apache2 \
    apache2-suexec-pristine \
    libapache2-mod-fcgid \
    libapache2-mod-proxy-fcgi

# Enable required modules
sudo a2enmod proxy
sudo a2enmod proxy_fcgi
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod ssl
sudo a2enmod deflate

# Enable and start
sudo systemctl enable apache2
sudo systemctl start apache2
```

**Configure Apache backend** - Edit `/etc/apache2/ports.conf`:

```apache
# Change to listen on internal port only
Listen 127.0.0.1:7070
```

Create virtual host at `/etc/apache2/sites-available/budget-control-backend.conf`:

```apache
<VirtualHost 127.0.0.1:7070>
    ServerName budget-control.internal
    DocumentRoot /var/www/budget-control/public

    # Trust proxy headers from Nginx
    RemoteIPHeader X-Forwarded-For
    RemoteIPTrustedProxy 127.0.0.1

    # Error logging
    ErrorLog ${APACHE_LOG_DIR}/budget-control-error.log
    CustomLog ${APACHE_LOG_DIR}/budget-control-access.log combined

    # PHP-FPM proxy
    <FilesMatch "\.php$">
        SetHandler "proxy:unix:/run/php/php8.2-fpm.sock|fcgi://localhost"
    </FilesMatch>

    <Directory /var/www/budget-control/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted

        # Enable mod_rewrite for routing
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^ index.php [QSA,L]
        </IfModule>
    </Directory>

    # Security headers
    <IfModule mod_headers.c>
        Header always set X-Frame-Options "SAMEORIGIN"
        Header always set X-Content-Type-Options "nosniff"
        Header always set X-XSS-Protection "1; mode=block"
    </IfModule>

    # Compression
    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
    </IfModule>
</VirtualHost>
```

Enable site and disable default:
```bash
sudo a2ensite budget-control-backend
sudo a2dissite 000-default

# Test configuration
sudo apache2ctl configtest

# Restart Apache
sudo systemctl restart apache2
```

---

## 📁 **Part 4: Deploy Application**

### **Step 4.1: Clone/Transfer Application Code**

```bash
# Navigate to web root
cd /var/www

# Clone from Git (or upload files)
sudo git clone https://github.com/yourusername/budget-control.git

# Navigate to app
cd budget-control/budget-app

# Set proper permissions
sudo chown -R budgetapp:budgetapp .
sudo chmod -R 755 .
sudo chmod -R 755 public/
sudo chmod 755 database/
```

### **Step 4.2: Initialize Database**

```bash
# Create database
sudo sqlite3 database/budget.sqlite < database/schema.sql

# Set permissions
sudo chown www-data:www-data database/budget.sqlite
sudo chmod 640 database/budget.sqlite

# Verify
sqlite3 database/budget.sqlite ".tables"
```

**Apply performance settings**:
```bash
sqlite3 database/budget.sqlite << 'EOF'
PRAGMA journal_mode = WAL;
PRAGMA cache_size = -64000;
PRAGMA mmap_size = 268435456;
PRAGMA synchronous = NORMAL;
PRAGMA temp_store = MEMORY;
EOF
```

### **Step 4.3: Configure Application**

Create `.env` file at `/var/www/budget-control/budget-app/.env`:

```env
APP_NAME="Budget Control"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com:8443

# Database
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/budget-control/budget-app/database/budget.sqlite

# Security
APP_KEY=your-secret-key-here-generate-with-random-32-chars
ENCRYPTION_KEY=your-encryption-key-32-chars

# API
API_RATE_LIMIT=100
API_RATE_WINDOW=900

# Session
SESSION_LIFETIME=120
SESSION_COOKIE_SECURE=true
SESSION_COOKIE_HTTP_ONLY=true
SESSION_COOKIE_SAME_SITE=Strict

# 2FA
TWO_FACTOR_ENABLED=true
TWO_FACTOR_ISSUER="Budget Control"

# Email (optional)
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Budget Control"

# Logging
LOG_LEVEL=error
LOG_PATH=/var/log/budget-control/
```

Set permissions:
```bash
sudo chown budgetapp:budgetapp .env
sudo chmod 600 .env
```

Create log directory:
```bash
sudo mkdir -p /var/log/budget-control
sudo chown www-data:www-data /var/log/budget-control
sudo chmod 755 /var/log/budget-control
```

### **Step 4.4: Install Dependencies (if using Composer)**

```bash
# Install Composer
cd /var/www/budget-control/budget-app
sudo -u www-data composer install --no-dev --optimize-autoloader
```

---

## 🔐 **Part 5: SSL/TLS Certificate Setup**

### **Step 5.1: Install Certbot**

```bash
# Install certbot
sudo apt install -y certbot python3-certbot-nginx

# Verify installation
certbot --version
```

### **Step 5.2: Obtain Let's Encrypt Certificate**

**For public domain:**
```bash
# Automatic renewal with Nginx
sudo certbot --nginx -d example.com -d www.example.com

# Or manual certificate only
sudo certbot certonly --nginx -d example.com -d www.example.com
```

**For private/behind NAT (DNS validation):**
```bash
# Using Cloudflare DNS (requires API token)
export CF_Token="your-cloudflare-api-token"

sudo certbot certonly --dns-cloudflare \
  -d example.com \
  -d www.example.com \
  -d *.example.com

# Store API credentials at /etc/letsencrypt/cloudflare.ini
# chmod 600 /etc/letsencrypt/cloudflare.ini
```

**For private network (manual):**
```bash
# Generate self-signed certificate
sudo openssl req -x509 -nodes -days 365 \
  -newkey rsa:2048 \
  -keyout /etc/ssl/private/budget-control.key \
  -out /etc/ssl/certs/budget-control.crt \
  -subj "/C=US/ST=State/L=City/O=Organization/CN=budget-control.local"
```

### **Step 5.3: Verify Certificate Auto-Renewal**

```bash
# Test renewal
sudo certbot renew --dry-run

# Check timer
sudo systemctl list-timers | grep certbot

# Check renewal log
sudo tail -f /var/log/letsencrypt/letsencrypt.log
```

---

## 🛡️ **Part 6: Security Hardening**

### **Step 6.1: Configure Fail2Ban**

```bash
# Install Fail2Ban
sudo apt install -y fail2ban

# Create local configuration
sudo cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local
```

Edit `/etc/fail2ban/jail.local`:

```ini
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3
destemail = admin@example.com
sendername = Fail2Ban
action = %(action_mwl)s

# SSH protection
[sshd]
enabled = true
port = 2222
filter = sshd
logpath = /var/log/auth.log
maxretry = 3
bantime = 86400

# Apache protection
[apache-auth]
enabled = true
port = 7070
logpath = /var/log/apache2/budget-control-error.log

[apache-limit-req]
enabled = true
port = 7070
logpath = /var/log/apache2/budget-control-error.log

# Nginx protection
[nginx-http-auth]
enabled = true
port = 8080,8443
filter = nginx-http-auth
logpath = /var/log/nginx/budget-control-error.log
maxretry = 5

[nginx-limit-req]
enabled = true
port = 8080,8443
filter = nginx-limit-req
logpath = /var/log/nginx/budget-control-error.log
maxretry = 10
```

Enable and start:
```bash
sudo systemctl enable fail2ban
sudo systemctl start fail2ban

# Check status
sudo fail2ban-client status
```

### **Step 6.2: File Integrity Monitoring (AIDE)**

```bash
# Install AIDE
sudo apt install -y aide aide-common

# Initialize database
sudo aideinit
sudo mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db

# Check integrity
sudo aide --check

# Create scheduled check
echo "0 3 * * * /usr/bin/aide --check | mail -s 'AIDE Report' admin@example.com" | sudo crontab -
```

### **Step 6.3: Audit Logging**

```bash
# Install auditd
sudo apt install -y auditd

# Start service
sudo systemctl enable auditd
sudo systemctl start auditd

# Add audit rules - Edit /etc/audit/rules.d/audit.rules
-w /var/www/budget-control/database/ -p wa -k database_changes
-w /var/www/budget-control/ -p wa -k app_changes
-w /etc/nginx/ -p wa -k nginx_config
-w /etc/apache2/ -p wa -k apache_config
-a always,exit -F arch=b64 -S execve -F uid=0 -k root_exec

# Apply rules
sudo systemctl restart auditd

# Check logs
sudo ausearch -k database_changes
```

---

## 📊 **Part 7: Monitoring & Logging**

### **Step 7.1: Configure Log Rotation**

Create `/etc/logrotate.d/budget-control`:

```bash
/var/log/budget-control/*.log
/var/log/nginx/budget-control-*.log
/var/log/apache2/budget-control-*.log
{
    daily
    rotate 30
    compress
    delaycompress
    notifempty
    create 640 www-data www-data
    sharedscripts
    postrotate
        systemctl reload nginx > /dev/null 2>&1 || true
        systemctl reload apache2 > /dev/null 2>&1 || true
    endscript
}
```

### **Step 7.2: Set Up Monitoring**

```bash
# Install monitoring tools
sudo apt install -y logwatch htop iotop nethogs

# Configure logwatch
echo "Output = mail" | sudo tee -a /etc/logwatch/conf/logwatch.conf
echo "Detail = High" | sudo tee -a /etc/logwatch/conf/logwatch.conf
echo "MailTo = admin@example.com" | sudo tee -a /etc/logwatch/conf/logwatch.conf

# Schedule daily report (crontab)
echo "0 6 * * * /usr/sbin/logwatch" | sudo crontab -
```

### **Step 7.3: Health Check Script**

Create `/usr/local/bin/budget-control-health-check.sh`:

```bash
#!/bin/bash
# Health check script

echo "=== Budget Control Health Check ===" > /tmp/health_check.log
echo "Check time: $(date)" >> /tmp/health_check.log

# Check services
echo "" >> /tmp/health_check.log
echo "Service Status:" >> /tmp/health_check.log
systemctl is-active nginx >> /tmp/health_check.log
systemctl is-active apache2 >> /tmp/health_check.log
systemctl is-active php8.2-fpm >> /tmp/health_check.log

# Check firewall
echo "" >> /tmp/health_check.log
echo "Firewall Status:" >> /tmp/health_check.log
sudo nft list chain inet filter input | head -5 >> /tmp/health_check.log

# Check database
echo "" >> /tmp/health_check.log
echo "Database Check:" >> /tmp/health_check.log
sqlite3 /var/www/budget-control/budget-app/database/budget.sqlite "SELECT COUNT(*) FROM sqlite_master;" >> /tmp/health_check.log

# Check disk space
echo "" >> /tmp/health_check.log
echo "Disk Space:" >> /tmp/health_check.log
df -h / >> /tmp/health_check.log

# Check memory
echo "" >> /tmp/health_check.log
echo "Memory Usage:" >> /tmp/health_check.log
free -h >> /tmp/health_check.log

# Check SSL certificate
echo "" >> /tmp/health_check.log
echo "SSL Certificate Expiration:" >> /tmp/health_check.log
openssl x509 -in /etc/letsencrypt/live/example.com/cert.pem -noout -dates >> /tmp/health_check.log 2>&1

# Send report
cat /tmp/health_check.log | mail -s "Budget Control Health Check - $(date +%Y-%m-%d)" admin@example.com
```

Make executable and schedule:
```bash
sudo chmod +x /usr/local/bin/budget-control-health-check.sh

# Run daily at 7 AM
echo "0 7 * * * /usr/local/bin/budget-control-health-check.sh" | sudo crontab -
```

---

## 💾 **Part 8: Backup Strategy**

### **Step 8.1: Create Backup Script**

Create `/usr/local/bin/budget-control-backup.sh`:

```bash
#!/bin/bash

BACKUP_DIR="/backup/budget-control"
DATE=$(date +%Y%m%d_%H%M%S)
APP_DIR="/var/www/budget-control/budget-app"

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
sqlite3 $APP_DIR/database/budget.sqlite ".dump" | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup configuration
tar -czf $BACKUP_DIR/config_$DATE.tar.gz \
    /etc/nginx/sites-available/budget-control \
    /etc/apache2/sites-available/budget-control-backend.conf \
    /etc/letsencrypt/live/example.com/

# Backup application
tar -czf $BACKUP_DIR/app_$DATE.tar.gz $APP_DIR

# Verify backups
echo "Backup verification:" > $BACKUP_DIR/backup_$DATE.log
gzip -t $BACKUP_DIR/db_$DATE.sql.gz && echo "Database backup: OK" >> $BACKUP_DIR/backup_$DATE.log
tar -tzf $BACKUP_DIR/config_$DATE.tar.gz > /dev/null && echo "Config backup: OK" >> $BACKUP_DIR/backup_$DATE.log
tar -tzf $BACKUP_DIR/app_$DATE.tar.gz > /dev/null && echo "App backup: OK" >> $BACKUP_DIR/backup_$DATE.log

# Remove old backups (keep 30 days)
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete
find $BACKUP_DIR -name "*.log" -mtime +30 -delete

# Compress backup log
gzip $BACKUP_DIR/backup_$DATE.log

# Send notification
cat $BACKUP_DIR/backup_$DATE.log.gz | mail -s "Budget Control Backup $DATE" admin@example.com
```

Make executable:
```bash
sudo chmod +x /usr/local/bin/budget-control-backup.sh
```

### **Step 8.2: Schedule Backups**

```bash
# Daily backup at 2 AM
echo "0 2 * * * /usr/local/bin/budget-control-backup.sh" | sudo crontab -

# Verify cron
sudo crontab -l | grep backup
```

---

## ✅ **Part 9: Deployment Verification**

### **Step 9.1: Test Application**

```bash
# Test via curl
curl -I http://localhost:8080/
curl -I https://localhost:8443/ --insecure

# Test from external (if public)
curl -I http://your-domain.com:8080/
curl -I https://your-domain.com:8443/

# Check response time
curl -w "Total time: %{time_total}s\n" https://localhost:8443/
```

### **Step 9.2: Verify Services**

```bash
# Check all services running
sudo systemctl status nginx
sudo systemctl status apache2
sudo systemctl status php8.2-fpm
sudo systemctl status fail2ban
sudo systemctl status nftables

# Check open ports
sudo netstat -tlnp | grep LISTEN

# Check firewall rules
sudo nft list ruleset | grep -E "port|2222|8080|8443"
```

### **Step 9.3: Performance Test**

```bash
# Install Apache Bench
sudo apt install -y apache2-utils

# Load test
ab -n 100 -c 10 https://localhost:8443/

# Expected results:
# - Requests per second: 50+
# - Failed requests: 0
# - Time per request: <100ms
```

---

## 🚀 **Part 10: Post-Deployment**

### **Step 10.1: Create Admin User**

Via command line or through application interface:
```bash
# Via PHP CLI
php /var/www/budget-control/budget-app/create_user.php \
    --email=admin@example.com \
    --password=secure_password \
    --role=admin
```

### **Step 10.2: Verify Security Settings**

```bash
# Check SSH configuration
sudo sshd -T | grep -E "permitroot|passwordauth|port"

# Verify firewall blocking
curl http://localhost:9999 2>&1 | grep -i "connection refused"

# Check file permissions
ls -la /var/www/budget-control/budget-app/.env
ls -la /var/www/budget-control/budget-app/database/budget.sqlite
```

### **Step 10.3: Enable Monitoring Alerts**

Set up notifications for:
- Failed login attempts (Fail2Ban)
- SSL certificate expiration (Certbot)
- Disk space warnings (monitoring script)
- Application errors (log monitoring)
- Database backup failures

---

## 📋 **Troubleshooting**

### **Issue: Cannot connect to port 8080**
```bash
# Check firewall rules
sudo nft list ruleset | grep 8080

# Check Nginx listening
sudo netstat -tlnp | grep 8080

# Test NAT rule
sudo nft list chain ip nat prerouting
```

### **Issue: SSL certificate not loading**
```bash
# Check certificate exists
ls -la /etc/letsencrypt/live/example.com/

# Verify certificate validity
openssl x509 -in /etc/letsencrypt/live/example.com/cert.pem -noout -text

# Check Nginx error log
sudo tail -f /var/log/nginx/error.log
```

### **Issue: Slow response times**
```bash
# Check system resources
htop

# Check database performance
sqlite3 /var/www/budget-control/budget-app/database/budget.sqlite "PRAGMA optimize;"

# Check query logs
tail -f /var/log/php/error.log
```

### **Issue: Backup fails**
```bash
# Check backup directory permissions
ls -la /backup/budget-control/

# Test backup script manually
sudo /usr/local/bin/budget-control-backup.sh

# Check cron logs
grep CRON /var/log/syslog
```

---

## 🎯 **Maintenance Schedule**

**Daily:**
- Monitor error logs
- Check system health
- Verify services running

**Weekly:**
- Review firewall logs
- Check failed login attempts
- Verify backups completed

**Monthly:**
- Update system packages
- Review SSL certificate status
- Test disaster recovery

**Quarterly:**
- Security audit
- Performance analysis
- Update documentation

---

## ✨ **Final Checklist**

- [ ] System updated to latest packages
- [ ] SSH configured on port 2222
- [ ] nftables firewall configured
- [ ] Nginx reverse proxy running on 8080/8443
- [ ] Apache backend running on port 7070
- [ ] PHP-FPM configured and running
- [ ] SQLite database initialized
- [ ] Application deployed and accessible
- [ ] SSL/TLS certificate installed
- [ ] Fail2Ban protecting SSH and web services
- [ ] Backups running on schedule
- [ ] Monitoring and alerting configured
- [ ] Admin user created
- [ ] Health checks passing
- [ ] Documentation updated

**Status: ✅ READY FOR PRODUCTION DEPLOYMENT**

---

*Debian 13 Deployment Guide - Version 1.0*
*Date: November 9, 2025*
*For: Budget Control Application*
