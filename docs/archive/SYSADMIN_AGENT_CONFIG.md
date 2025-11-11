# 🔧 **Sysadmin Agent Configuration for Debian 13**

**Purpose**: Guide for sysadmin operations on Debian 13 with NAT firewall restrictions
**Date**: November 9, 2025
**Target**: Budget Control Application Deployment
**Access Ports**: 2222 (SSH), 8080 (HTTP), 8443 (HTTPS)

---

## 🎯 **Sysadmin Agent Responsibilities**

The sysadmin agent (using the `debian-sysadmin` skill) will handle:

1. **System Setup & Hardening**
   - OS configuration
   - Security hardening
   - Kernel parameter tuning
   - User and access management

2. **Network & Firewall Management**
   - nftables firewall configuration
   - Port forwarding and NAT
   - Security rules and rate limiting
   - DDoS protection setup

3. **Web Stack Installation**
   - PHP 8.2 with SQLite3
   - Nginx reverse proxy
   - Apache backend server
   - PHP-FPM configuration

4. **Application Deployment**
   - Code deployment
   - Database initialization
   - Environment configuration
   - Permission management

5. **Security & SSL/TLS**
   - Let's Encrypt certificate management
   - SSL/TLS configuration
   - Security header setup
   - Certificate monitoring

6. **Monitoring & Backup**
   - Log aggregation and rotation
   - Automated backups
   - Health monitoring
   - Performance tracking

7. **Maintenance & Troubleshooting**
   - Service management
   - Log analysis
   - Performance optimization
   - Incident response

---

## 📋 **Agent Activation Steps**

### **Step 1: Invoke the Sysadmin Agent**

To activate the sysadmin agent for Debian operations:

```
Invoke the 'debian-sysadmin' skill
```

This provides specialized tools for:
- System administration
- Security hardening
- Network configuration
- Service management
- Monitoring setup

### **Step 2: Provide Initial Context**

When working with the agent, provide:

```
Target Environment:
- OS: Debian 13
- Architecture: x86_64
- Access Method: SSH on port 2222
- Firewall: nftables with restricted ports (2222, 8080, 8443)
- Application: Budget Control (PHP + SQLite)

Task: Full deployment including security hardening
Configuration: NAT-protected with reverse proxy
```

### **Step 3: Follow Deployment Sequence**

The agent should follow this sequence:

1. **Fresh Installation**
   - System updates
   - Essential packages
   - Kernel hardening
   - SSH hardening

2. **Firewall Setup**
   - nftables configuration
   - Port forwarding rules
   - Rate limiting
   - DDoS protection

3. **Web Stack**
   - PHP installation
   - Nginx setup (reverse proxy)
   - Apache setup (backend)
   - PHP-FPM configuration

4. **Application Deployment**
   - Code deployment
   - Database setup
   - Configuration
   - Testing

5. **Security Hardening**
   - Fail2Ban setup
   - SSL/TLS configuration
   - AIDE file integrity
   - Audit logging

6. **Monitoring Setup**
   - Log rotation
   - Health checks
   - Backup scheduling
   - Alert configuration

---

## 🔐 **Critical Security Configurations**

### **SSH Access (Port 2222)**

**Configuration to enforce:**
```bash
# /etc/ssh/sshd_config
Port 2222
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
AllowUsers budgetapp
MaxAuthTries 3
ClientAliveInterval 300
AllowAgentForwarding no
AllowTcpForwarding no
X11Forwarding no
```

**Testing:**
```bash
ssh -p 2222 budgetapp@server.ip
```

### **Firewall Rules (nftables)**

**Essential rules to implement:**
```bash
# Allow SSH on 2222
tcp dport 2222 ct state new,established accept

# Allow HTTP on 8080
tcp dport 8080 ct state new,established accept

# Allow HTTPS on 8443
tcp dport 8443 ct state new,established accept

# Drop everything else
policy drop
```

**Verification:**
```bash
nft list ruleset
nft list chain inet filter input
```

### **NAT Configuration**

**Port forwarding to set up:**
```bash
# Forward 8080 → internal port 80 (Apache)
tcp dport 8080 dnat to 127.0.0.1:80

# Forward 8443 → internal port 443 (Apache)
tcp dport 8443 dnat to 127.0.0.1:443
```

**Verification:**
```bash
nft list chain ip nat prerouting
```

---

## 🌐 **Web Stack Architecture**

### **Service Layout**

```
Port 2222 (Public - SSH)
  └─ sshd (OpenSSH)

Port 8080 (Public - HTTP)
  └─ nftables NAT (port 8080 → 80)
  └─ Nginx (reverse proxy)
    └─ Internal port 80
    └─ Proxies to Apache port 7070

Port 8443 (Public - HTTPS)
  └─ nftables NAT (port 8443 → 443)
  └─ Nginx (reverse proxy with SSL/TLS)
    └─ Internal port 443
    └─ Proxies to Apache port 7070

Internal Port 7070 (Apache - Backend)
  └─ PHP-FPM (unix socket)
  └─ SQLite Database
  └─ Application Code
```

### **Nginx Configuration**

**File**: `/etc/nginx/sites-available/budget-control`

Key features:
- SSL/TLS termination
- Reverse proxy to Apache
- Security headers (HSTS, CSP, X-Frame-Options)
- Gzip compression
- Static file caching
- Rate limiting

### **Apache Configuration**

**File**: `/etc/apache2/sites-available/budget-control-backend.conf`

Key features:
- Internal listening only (127.0.0.1:7070)
- PHP-FPM via proxy_fcgi
- mod_rewrite for routing
- RemoteIPHeader trust from Nginx
- Security headers

### **PHP-FPM Configuration**

**File**: `/etc/php/8.2/fpm/php.ini`

Critical settings:
- `expose_php = Off`
- `display_errors = Off`
- `log_errors = On`
- `disable_functions` = unsafe functions
- `open_basedir` = restrict file access
- `session.cookie_httponly = 1`
- `session.cookie_secure = 1`

---

## 🗄️ **Database Configuration**

### **SQLite Setup**

**Location**: `/var/www/budget-control/budget-app/database/budget.sqlite`

**Permissions**:
```bash
Owner: www-data:www-data
Mode: 640 (rw-r-----)
```

**Performance Settings**:
```sql
PRAGMA journal_mode = WAL;        -- Write-Ahead Logging
PRAGMA cache_size = -64000;       -- 64MB cache
PRAGMA mmap_size = 268435456;     -- 256MB memory mapping
PRAGMA synchronous = NORMAL;      -- Balance speed/safety
PRAGMA temp_store = MEMORY;       -- Use RAM for temp
```

**Verify**:
```bash
sqlite3 database/budget.sqlite "PRAGMA journal_mode;"
sqlite3 database/budget.sqlite "PRAGMA cache_size;"
```

---

## 📊 **Monitoring & Logging**

### **Log Files to Monitor**

**SSH Access**:
```bash
/var/log/auth.log
```

**Web Server Access**:
```bash
/var/log/nginx/budget-control-access.log
/var/log/apache2/budget-control-access.log
```

**Application Errors**:
```bash
/var/log/php/error.log
/var/log/nginx/budget-control-error.log
/var/log/apache2/budget-control-error.log
```

**System Events**:
```bash
/var/log/syslog
/var/log/audit/audit.log
```

### **Health Check Commands**

**Service Status**:
```bash
sudo systemctl status nginx
sudo systemctl status apache2
sudo systemctl status php8.2-fpm
sudo systemctl status fail2ban
sudo systemctl status nftables
```

**Port Verification**:
```bash
sudo netstat -tlnp | grep LISTEN
sudo nft list ruleset
```

**Database Integrity**:
```bash
sqlite3 /var/www/budget-control/budget-app/database/budget.sqlite "PRAGMA integrity_check;"
```

**Disk Space**:
```bash
df -h
du -sh /var/www/budget-control/
du -sh /backup/
```

**Backup Status**:
```bash
ls -lh /backup/budget-control/
grep -i backup /var/log/syslog | tail -10
```

---

## 🔄 **Common Operations**

### **Restart Services**

```bash
# Restart all services
sudo systemctl restart nginx
sudo systemctl restart apache2
sudo systemctl restart php8.2-fpm

# Restart firewall
sudo systemctl restart nftables
```

### **Check Configuration**

```bash
# Nginx
sudo nginx -t

# Apache
sudo apache2ctl configtest

# nftables
sudo nft -f /etc/nftables.conf

# PHP-FPM
php -i | grep "Configuration File"
```

### **View Logs**

```bash
# Real-time Nginx access
sudo tail -f /var/log/nginx/budget-control-access.log

# Real-time errors
sudo tail -f /var/log/nginx/budget-control-error.log
sudo tail -f /var/log/php/error.log

# SSH attempts
sudo tail -f /var/log/auth.log

# System events
sudo journalctl -f
```

### **Manage Firewall**

```bash
# Test configuration
sudo nft -f /etc/nftables.conf

# List all rules
sudo nft list ruleset

# Add temporary rule (won't persist)
sudo nft add rule inet filter input tcp dport 8080 accept

# Reload rules
sudo systemctl restart nftables
```

### **Database Maintenance**

```bash
# Optimize database
sqlite3 /var/www/budget-control/budget-app/database/budget.sqlite "PRAGMA optimize;"

# Vacuum
sqlite3 /var/www/budget-control/budget-app/database/budget.sqlite "VACUUM;"

# Backup
sqlite3 /var/www/budget-control/budget-app/database/budget.sqlite ".dump" | gzip > backup.sql.gz

# Check size
ls -lh /var/www/budget-control/budget-app/database/budget.sqlite
```

---

## 🛡️ **Security Operations**

### **Monitor Failed Logins**

```bash
# Check Fail2Ban status
sudo fail2ban-client status

# Check specific jail
sudo fail2ban-client status sshd

# View bans
sudo nft list set inet f2b-sshd

# Manually ban IP
sudo fail2ban-client set sshd banip 192.168.1.100

# Unban IP
sudo fail2ban-client set sshd unbanip 192.168.1.100
```

### **SSL/TLS Certificate Management**

```bash
# Check certificate validity
openssl x509 -in /etc/letsencrypt/live/example.com/cert.pem -noout -dates

# Renew certificate
sudo certbot renew

# Test renewal
sudo certbot renew --dry-run

# View renewal history
sudo tail -f /var/log/letsencrypt/letsencrypt.log
```

### **File Integrity Checking**

```bash
# Update AIDE database
sudo aideinit
sudo mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db

# Check for changes
sudo aide --check

# View changes
sudo aide --config=/etc/aide/aide.conf --check | grep "changed"
```

### **Security Audit**

```bash
# Check open ports
sudo netstat -tlnp
sudo nft list ruleset

# Check user accounts
sudo cat /etc/passwd | grep -v nologin

# Check sudo access
sudo cat /etc/sudoers
sudo ls -la /etc/sudoers.d/

# Check SSH keys
sudo ls -la ~/.ssh/authorized_keys

# Check failed logins
sudo grep "Failed password" /var/log/auth.log | wc -l

# Check firewall blocks
sudo nft list counter inet filter input_drop
```

---

## 📈 **Performance Tuning**

### **Monitor System Performance**

```bash
# CPU and Memory
top -b -n 1 | head -10
free -h
ps aux --sort=-%cpu | head -10

# Disk I/O
iostat -x 1 5
iotop -b -n 1

# Network
netstat -s
iftop -n

# Process-specific
pmap -x /var/run/php/php8.2-fpm.pid | tail -1
```

### **Database Performance**

```bash
# Check table sizes
sqlite3 /var/www/budget-control/budget-app/database/budget.sqlite \
  "SELECT name, COUNT(*) as rows FROM (SELECT name FROM sqlite_master WHERE type='table' UNION ALL SELECT name FROM pragma_temp_master WHERE type='table') GROUP BY name;"

# Analyze indexes
sqlite3 /var/www/budget-control/budget-app/database/budget.sqlite "PRAGMA index_list(transactions);"

# Query execution plan
sqlite3 /var/www/budget-control/budget-app/database/budget.sqlite "EXPLAIN QUERY PLAN SELECT * FROM transactions WHERE user_id = 1;"

# Slow query detection
sqlite3 /var/www/budget-control/budget-app/database/budget.sqlite "PRAGMA query_only=1; -- then run queries"
```

### **Cache Management**

```bash
# Clear PHP OPcache
sudo systemctl restart php8.2-fpm

# Clear Nginx cache
sudo rm -rf /var/cache/nginx/*

# Monitor cache hit rate
grep cache_status /var/log/nginx/budget-control-access.log | sort | uniq -c
```

---

## 📋 **Deployment Checklist**

### **Pre-Deployment**

- [ ] Verify Debian 13 installation
- [ ] Update system to latest patches
- [ ] Create backup user account
- [ ] Configure SSH key-based auth
- [ ] Verify network connectivity
- [ ] Check disk space (min 10GB free)

### **During Deployment**

- [ ] Apply kernel hardening
- [ ] Configure nftables firewall
- [ ] Install web stack
- [ ] Deploy application code
- [ ] Initialize database
- [ ] Configure SSL/TLS
- [ ] Set up monitoring
- [ ] Enable backups

### **Post-Deployment**

- [ ] Test SSH access (port 2222)
- [ ] Test HTTP access (port 8080)
- [ ] Test HTTPS access (port 8443)
- [ ] Verify all services running
- [ ] Test backup process
- [ ] Test disaster recovery
- [ ] Enable monitoring alerts
- [ ] Create admin user
- [ ] Document configuration
- [ ] Brief operations team

---

## 🆘 **Emergency Procedures**

### **If System is Compromised**

1. **Isolate system**
   ```bash
   # Disable firewall rules temporarily
   sudo systemctl stop nftables
   ```

2. **Preserve evidence**
   ```bash
   # Copy logs
   sudo cp /var/log/auth.log /backup/auth.log.backup
   sudo cp /var/log/syslog /backup/syslog.backup
   ```

3. **Restore from backup**
   ```bash
   # Stop services
   sudo systemctl stop nginx apache2 php8.2-fpm

   # Restore application
   cd /var/www/budget-control
   tar -xzf /backup/app_YYYYMMDD_HHMMSS.tar.gz

   # Restore database
   gunzip /backup/db_YYYYMMDD_HHMMSS.sql.gz
   sqlite3 database/budget.sqlite < /backup/db_YYYYMMDD_HHMMSS.sql

   # Restart services
   sudo systemctl start nginx apache2 php8.2-fpm
   ```

### **If Firewall Rules Are Lost**

```bash
# Restore from backup
sudo cp /backup/nftables.conf.backup /etc/nftables.conf
sudo systemctl restart nftables

# Or manually recreate critical rules
sudo nft add rule inet filter input tcp dport 2222 accept
sudo nft add rule inet filter input tcp dport 8080 accept
sudo nft add rule inet filter input tcp dport 8443 accept
```

### **If Database is Corrupted**

```bash
# Check integrity
sqlite3 /var/www/budget-control/budget-app/database/budget.sqlite "PRAGMA integrity_check;"

# Restore from backup
cp /backup/db_YYYYMMDD_HHMMSS.sql.gz /tmp/
gunzip /tmp/db_YYYYMMDD_HHMMSS.sql.gz
sqlite3 /var/www/budget-control/budget-app/database/budget.sqlite < /tmp/db_YYYYMMDD_HHMMSS.sql

# Verify
sqlite3 /var/www/budget-control/budget-app/database/budget.sqlite "SELECT COUNT(*) FROM transactions;"
```

---

## 📞 **Support Resources**

**Key Documentation**:
- `DEBIAN_13_DEPLOYMENT_GUIDE.md` - Complete deployment walkthrough
- `INTEGRATION_TESTING_PLAN.md` - Testing procedures
- `DEPLOYMENT_GUIDE.md` - General deployment instructions

**Online Resources**:
- Debian Security: https://www.debian.org/security/
- nftables Wiki: https://wiki.nftables.org/
- Let's Encrypt: https://letsencrypt.org/
- Nginx Documentation: https://nginx.org/en/docs/
- Apache Documentation: https://httpd.apache.org/docs/

**GitHub References**:
- OVH Debian CIS: https://github.com/ovh/debian-cis
- Security Hardening: https://github.com/captainzero93/security_harden_linux
- Nginx Handbook: https://github.com/trimstray/nginx-admins-handbook

---

## ✅ **Ready for Deployment**

This configuration document provides everything needed for the sysadmin agent to deploy the Budget Control application on Debian 13 with:

✅ Secure SSH on port 2222
✅ Firewall protection with nftables
✅ HTTP/HTTPS on ports 8080/8443
✅ Reverse proxy with Nginx
✅ Backend processing with Apache
✅ Database with SQLite
✅ Complete security hardening
✅ Automated monitoring and backups

---

*Sysadmin Agent Configuration*
*Version 1.0 - November 9, 2025*
*For: Budget Control Application on Debian 13*
