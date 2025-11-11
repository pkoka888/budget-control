# ✅ **Complete Deployment Checklist - Budget Control Application**

**Project**: Budget Control Application
**Environment**: Debian 13 Linux Server
**Date**: November 9, 2025
**Status**: Ready for Production Deployment

---

## 📋 **PRE-DEPLOYMENT PHASE (1-2 days before)**

### **Team Preparation**

- [ ] All stakeholders briefed on deployment timeline
- [ ] Deployment window scheduled and communicated
- [ ] Rollback procedure documented and rehearsed
- [ ] Support team available during deployment
- [ ] Post-deployment monitoring plan confirmed

### **Code & Documentation Review**

- [ ] Code review completed and approved
- [ ] All 25 features verified working
- [ ] Integration tests passed (see INTEGRATION_TESTING_PLAN.md)
- [ ] API documentation complete (579 lines)
- [ ] Deployment documentation finalized
- [ ] Sysadmin configuration ready (SYSADMIN_AGENT_CONFIG.md)

### **Infrastructure Preparation**

- [ ] Fresh Debian 13 server provisioned
- [ ] Network connectivity verified
- [ ] Domain name configured (or internal hostname)
- [ ] Public IP address assigned (if applicable)
- [ ] Reverse DNS configured (if applicable)
- [ ] Backup storage allocated (minimum 100GB)

### **Backup & Recovery**

- [ ] Current system backup created (if applicable)
- [ ] Backup verification completed
- [ ] Disaster recovery plan documented
- [ ] Recovery procedure tested
- [ ] Contact list for emergency situations

---

## 🔧 **DEPLOYMENT PHASE (Actual Deployment)**

### **Phase 1: Initial Server Setup (1-2 hours)**

**Sysadmin Agent Tasks:**

- [ ] Boot fresh Debian 13 system
- [ ] Update all packages to latest
  ```bash
  sudo apt update && sudo apt upgrade -y
  ```
- [ ] Install essential packages
  ```bash
  sudo apt install -y curl wget git vim htop net-tools ntp ufw fail2ban aide auditd
  ```
- [ ] Configure timezone to UTC
- [ ] Enable NTP synchronization
- [ ] Apply kernel hardening parameters
  - [ ] IP forwarding enabled
  - [ ] TCP SYN cookies enabled
  - [ ] Source routing disabled
  - [ ] Reverse path filtering enabled
  - [ ] Sysctl parameters applied

**Verification:**
```bash
[ ] System time correct: timedatectl
[ ] NTP synced: chronyc tracking
[ ] Kernel params: sysctl -p
```

### **Phase 2: SSH Security Configuration (30 minutes)**

**Sysadmin Agent Tasks:**

- [ ] Create budgetapp user account
  ```bash
  sudo useradd -m -s /bin/false -d /var/www/budget-control budgetapp
  ```
- [ ] Configure SSH on port 2222
  - [ ] Edit /etc/ssh/sshd_config
  - [ ] Disable PermitRootLogin
  - [ ] Disable PasswordAuthentication
  - [ ] Set MaxAuthTries = 3
  - [ ] Configure AllowUsers = budgetapp
  ```bash
  sudo systemctl restart sshd
  ```
- [ ] Test SSH access (BEFORE logging out!)
  ```bash
  ssh -p 2222 budgetapp@localhost
  ```
- [ ] Configure automatic security updates
  ```bash
  sudo apt install -y unattended-upgrades
  sudo dpkg-reconfigure -plow unattended-upgrades
  ```

**Verification:**
```bash
[ ] SSH accessible on port 2222: ssh -p 2222 budgetapp@server
[ ] Root login blocked: ssh -u root (should fail)
[ ] Key-based auth only: ssh -o PasswordAuthentication=yes budgetapp@server (should fail)
[ ] Auto-updates enabled: systemctl list-timers | grep apt
```

### **Phase 3: Firewall Configuration (1 hour)**

**Sysadmin Agent Tasks:**

- [ ] Install nftables
  ```bash
  sudo apt install -y nftables
  ```
- [ ] Create /etc/nftables.conf
  - [ ] Default deny policy
  - [ ] Allow SSH on 2222
  - [ ] Allow HTTP on 8080
  - [ ] Allow HTTPS on 8443
  - [ ] Enable connection tracking
  - [ ] Configure logging
  - [ ] Set up NAT for port forwarding
- [ ] Test nftables configuration
  ```bash
  sudo nft -f /etc/nftables.conf
  ```
- [ ] Enable and start nftables
  ```bash
  sudo systemctl enable nftables
  sudo systemctl start nftables
  ```
- [ ] Configure fail2ban
  ```bash
  sudo apt install -y fail2ban
  ```
- [ ] Create /etc/fail2ban/jail.local
  - [ ] SSH protection on port 2222
  - [ ] HTTP/HTTPS protection
  - [ ] Reasonable bantime (3600 seconds)
  - [ ] Email alerts configured
- [ ] Start fail2ban
  ```bash
  sudo systemctl enable fail2ban
  sudo systemctl start fail2ban
  ```

**Verification:**
```bash
[ ] Firewall active: sudo nft list ruleset | head -20
[ ] SSH port open: nmap -p 2222 localhost
[ ] HTTP port open: nmap -p 8080 localhost
[ ] HTTPS port open: nmap -p 8443 localhost
[ ] Other ports closed: nmap -p 1-10000 localhost | grep -v "filtered"
[ ] Fail2Ban running: sudo fail2ban-client status
[ ] NAT rules present: sudo nft list chain ip nat prerouting
```

### **Phase 4: Web Stack Installation (2-3 hours)**

**Sysadmin Agent Tasks:**

#### **4.1: PHP & SQLite Installation**

- [ ] Install PHP 8.2 and extensions
  ```bash
  sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-sqlite3 php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd php8.2-json php8.2-xml
  ```
- [ ] Enable PHP-FPM
  ```bash
  sudo systemctl enable php8.2-fpm
  sudo systemctl start php8.2-fpm
  ```
- [ ] Configure /etc/php/8.2/fpm/php.ini
  - [ ] expose_php = Off
  - [ ] display_errors = Off
  - [ ] log_errors = On
  - [ ] disable_functions set
  - [ ] session.cookie_httponly = 1
  - [ ] session.cookie_secure = 1
  - [ ] Restart PHP-FPM
  ```bash
  sudo systemctl restart php8.2-fpm
  ```

**Verification:**
```bash
[ ] PHP version: php -v
[ ] SQLite3 extension: php -m | grep sqlite3
[ ] PHP-FPM running: systemctl status php8.2-fpm
[ ] Configuration loaded: php -i | grep php.ini
```

#### **4.2: Nginx Reverse Proxy Installation**

- [ ] Install Nginx
  ```bash
  sudo apt install -y nginx
  ```
- [ ] Create /etc/nginx/sites-available/budget-control
  - [ ] Listen on 8080 (HTTP redirect)
  - [ ] Listen on 8443 (HTTPS)
  - [ ] SSL certificates configured
  - [ ] Reverse proxy to Apache backend
  - [ ] Security headers set
  - [ ] Gzip compression enabled
  - [ ] Static file caching configured
- [ ] Enable site
  ```bash
  sudo ln -s /etc/nginx/sites-available/budget-control /etc/nginx/sites-enabled/
  sudo rm /etc/nginx/sites-enabled/default
  ```
- [ ] Test and restart
  ```bash
  sudo nginx -t
  sudo systemctl enable nginx
  sudo systemctl restart nginx
  ```

**Verification:**
```bash
[ ] Nginx running: systemctl status nginx
[ ] Configuration valid: nginx -t
[ ] Listening on ports: netstat -tlnp | grep nginx
[ ] Can reach site: curl http://localhost:8080
[ ] Proxy headers present: curl -I http://localhost:8080
```

#### **4.3: Apache Backend Installation**

- [ ] Install Apache with PHP-FPM support
  ```bash
  sudo apt install -y apache2 libapache2-mod-proxy-fcgi
  ```
- [ ] Enable required modules
  ```bash
  sudo a2enmod proxy proxy_fcgi rewrite headers ssl deflate
  ```
- [ ] Edit /etc/apache2/ports.conf
  - [ ] Change to Listen 127.0.0.1:7070
  - [ ] Internal only
- [ ] Create /etc/apache2/sites-available/budget-control-backend.conf
  - [ ] VirtualHost on 127.0.0.1:7070
  - [ ] DocumentRoot set correctly
  - [ ] PHP-FPM proxy configured
  - [ ] mod_rewrite enabled
  - [ ] RemoteIPHeader trust configured
- [ ] Enable site and disable default
  ```bash
  sudo a2ensite budget-control-backend
  sudo a2dissite 000-default
  ```
- [ ] Test and restart
  ```bash
  sudo apache2ctl configtest
  sudo systemctl enable apache2
  sudo systemctl restart apache2
  ```

**Verification:**
```bash
[ ] Apache running: systemctl status apache2
[ ] Configuration valid: apache2ctl configtest
[ ] Listening on port 7070: netstat -tlnp | grep apache
[ ] Internal only: netstat -tlnp | grep 7070 | grep 127.0.0.1
[ ] PHP-FPM connected: curl http://127.0.0.1:7070/info.php | grep PHP
```

### **Phase 5: Application Deployment (1-2 hours)**

**Sysadmin Agent Tasks:**

- [ ] Create web root directory
  ```bash
  sudo mkdir -p /var/www/budget-control
  sudo chown -R budgetapp:budgetapp /var/www/budget-control
  ```
- [ ] Deploy application code
  ```bash
  # Either git clone or scp transfer
  cd /var/www/budget-control
  git clone <repo-url> .
  # OR
  scp -r budget-control/* user@server:/var/www/budget-control/
  ```
- [ ] Set correct permissions
  ```bash
  sudo chown -R www-data:www-data /var/www/budget-control/
  sudo chmod -R 755 /var/www/budget-control/
  sudo chmod 755 /var/www/budget-control/database/
  ```
- [ ] Initialize database
  ```bash
  sudo sqlite3 /var/www/budget-control/database/budget.sqlite < /var/www/budget-control/database/schema.sql
  sudo chown www-data:www-data /var/www/budget-control/database/budget.sqlite
  sudo chmod 640 /var/www/budget-control/database/budget.sqlite
  ```
- [ ] Apply database performance settings
  ```bash
  sqlite3 /var/www/budget-control/database/budget.sqlite << 'EOF'
  PRAGMA journal_mode = WAL;
  PRAGMA cache_size = -64000;
  PRAGMA mmap_size = 268435456;
  PRAGMA synchronous = NORMAL;
  PRAGMA temp_store = MEMORY;
  EOF
  ```
- [ ] Create and configure .env file
  ```bash
  sudo cp /var/www/budget-control/.env.example /var/www/budget-control/.env
  # Edit .env with production values
  sudo chown www-data:www-data /var/www/budget-control/.env
  sudo chmod 600 /var/www/budget-control/.env
  ```
- [ ] Create log directory
  ```bash
  sudo mkdir -p /var/log/budget-control
  sudo chown www-data:www-data /var/log/budget-control
  sudo chmod 755 /var/log/budget-control
  ```

**Verification:**
```bash
[ ] Code deployed: ls -la /var/www/budget-control/
[ ] Database exists: ls -la /var/www/budget-control/database/budget.sqlite
[ ] Database schema valid: sqlite3 /var/www/budget-control/database/budget.sqlite ".tables"
[ ] Permissions correct: ls -la /var/www/budget-control/public/
[ ] Environment file: ls -la /var/www/budget-control/.env
[ ] Log directory: ls -la /var/log/budget-control/
```

### **Phase 6: SSL/TLS Certificate Configuration (30 minutes - 1 hour)**

**Sysadmin Agent Tasks:**

- [ ] Install Certbot
  ```bash
  sudo apt install -y certbot python3-certbot-nginx
  ```
- [ ] Obtain Let's Encrypt certificate
  ```bash
  # For public domain
  sudo certbot --nginx -d example.com -d www.example.com

  # For private network (DNS validation)
  sudo certbot certonly --manual --preferred-challenges dns -d example.com

  # For behind NAT (self-signed)
  sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/ssl/private/budget-control.key \
    -out /etc/ssl/certs/budget-control.crt
  ```
- [ ] Update Nginx configuration with certificate paths
- [ ] Test SSL configuration
  ```bash
  sudo nginx -t
  sudo openssl s_client -connect localhost:8443
  ```
- [ ] Enable auto-renewal
  ```bash
  sudo certbot renew --dry-run
  ```

**Verification:**
```bash
[ ] Certificate installed: ls -la /etc/letsencrypt/live/example.com/
[ ] Key exists: ls -la /etc/ssl/private/
[ ] HTTPS accessible: curl -k https://localhost:8443
[ ] Auto-renewal configured: systemctl list-timers | grep certbot
[ ] Certificate valid: openssl x509 -in /etc/ssl/certs/budget-control.crt -noout -dates
```

### **Phase 7: Security Hardening (1-2 hours)**

**Sysadmin Agent Tasks:**

- [ ] Configure File Integrity Monitoring (AIDE)
  ```bash
  sudo apt install -y aide aide-common
  sudo aideinit
  sudo mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db
  ```
- [ ] Configure Audit Logging (auditd)
  ```bash
  sudo systemctl start auditd
  # Add audit rules to /etc/audit/rules.d/audit.rules
  sudo systemctl restart auditd
  ```
- [ ] Create log rotation configuration
  ```bash
  # Create /etc/logrotate.d/budget-control
  ```
- [ ] Enable security updates
  ```bash
  sudo apt install -y unattended-upgrades apt-listchanges
  ```

**Verification:**
```bash
[ ] AIDE database created: ls -la /var/lib/aide/aide.db
[ ] Auditd running: systemctl status auditd
[ ] Log rotation configured: cat /etc/logrotate.d/budget-control
[ ] Security updates enabled: systemctl list-timers | grep apt
```

### **Phase 8: Monitoring & Backup Setup (1 hour)**

**Sysadmin Agent Tasks:**

- [ ] Create backup directory
  ```bash
  sudo mkdir -p /backup/budget-control
  sudo chown root:root /backup/budget-control
  sudo chmod 700 /backup/budget-control
  ```
- [ ] Create backup script (/usr/local/bin/budget-control-backup.sh)
- [ ] Test backup script
  ```bash
  sudo /usr/local/bin/budget-control-backup.sh
  ```
- [ ] Schedule backup via cron
  ```bash
  # 0 2 * * * /usr/local/bin/budget-control-backup.sh
  ```
- [ ] Create health check script (/usr/local/bin/budget-control-health-check.sh)
- [ ] Schedule health check
  ```bash
  # 0 7 * * * /usr/local/bin/budget-control-health-check.sh
  ```
- [ ] Configure logwatch
  ```bash
  sudo apt install -y logwatch
  # Configure /etc/logwatch/conf/logwatch.conf
  ```

**Verification:**
```bash
[ ] Backup directory exists: ls -la /backup/budget-control/
[ ] Backup script created: ls -la /usr/local/bin/budget-control-backup.sh
[ ] Test backup successful: ls -la /backup/budget-control/ | grep db_
[ ] Cron job scheduled: sudo crontab -l | grep backup
[ ] Health check created: ls -la /usr/local/bin/budget-control-health-check.sh
[ ] Logwatch configured: cat /etc/logwatch/conf/logwatch.conf
```

---

## ✅ **POST-DEPLOYMENT TESTING (2-4 hours)**

### **Phase 1: Basic Connectivity Tests**

- [ ] SSH access test
  ```bash
  [ ] SSH on port 2222: ssh -p 2222 budgetapp@server
  [ ] Key auth only: Should not accept password
  [ ] Root login blocked: Should reject root login
  ```
- [ ] HTTP access test
  ```bash
  [ ] HTTP on 8080: curl http://localhost:8080/
  [ ] HTTP response: Should return homepage
  [ ] Response time: <500ms acceptable
  ```
- [ ] HTTPS access test
  ```bash
  [ ] HTTPS on 8443: curl -k https://localhost:8443/
  [ ] SSL certificate: Should be valid
  [ ] Security headers: HSTS, X-Frame-Options, CSP present
  ```

### **Phase 2: Firewall Verification**

- [ ] Port 2222 accessible
  ```bash
  [ ] SSH connection: ssh -p 2222 should connect
  [ ] From external: nmap -p 2222 should show open
  ```
- [ ] Port 8080 accessible
  ```bash
  [ ] HTTP connection: curl http://server:8080
  [ ] From external: nmap -p 8080 should show open
  ```
- [ ] Port 8443 accessible
  ```bash
  [ ] HTTPS connection: curl -k https://server:8443
  [ ] From external: nmap -p 8443 should show open
  ```
- [ ] Other ports blocked
  ```bash
  [ ] Random port blocked: curl http://server:9999 should timeout
  [ ] nftables rules: sudo nft list ruleset shows drop policy
  ```

### **Phase 3: Application Functionality Tests**

**See INTEGRATION_TESTING_PLAN.md for detailed test procedures**

- [ ] **User Authentication**
  - [ ] User login works
  - [ ] Password reset works
  - [ ] Session management works
  - [ ] 2FA works
  - [ ] API key generation works

- [ ] **Transaction Management** (Critical Path)
  - [ ] Create transaction: Works
  - [ ] List transactions: Returns data
  - [ ] Filter transactions: Works
  - [ ] Update transaction: Works
  - [ ] Delete transaction: Works
  - [ ] Transaction splits: Calculated correctly

- [ ] **Reports & Analytics**
  - [ ] Monthly report: Generates
  - [ ] Yearly report: Generates
  - [ ] Spending analysis: Works
  - [ ] Budget status: Calculated

- [ ] **Data Management**
  - [ ] Export to CSV: Works
  - [ ] Export to Excel: Works
  - [ ] Export to PDF: Works
  - [ ] Data import: Works
  - [ ] Backup creation: Works

### **Phase 4: Performance Testing**

- [ ] Response times acceptable
  ```bash
  [ ] Homepage: <500ms
  [ ] Transaction list: <1s
  [ ] Reports: <2s
  [ ] API endpoints: <200ms
  ```
- [ ] Concurrent user handling
  ```bash
  [ ] 10 concurrent users: Responsive
  [ ] 50 concurrent users: Acceptable
  [ ] 100 concurrent users: Degrades gracefully
  ```
- [ ] Database performance
  ```bash
  [ ] Query time: Acceptable
  [ ] No timeout errors: Yes
  [ ] No deadlocks: Confirmed
  ```
- [ ] Resource usage
  ```bash
  [ ] CPU: <70% under normal load
  [ ] Memory: <80% utilization
  [ ] Disk I/O: Normal levels
  ```

### **Phase 5: Security Testing**

- [ ] **Authentication**
  - [ ] Login required for protected pages
  - [ ] Invalid credentials rejected
  - [ ] Session timeout works
  - [ ] 2FA enforced for sensitive operations

- [ ] **Authorization**
  - [ ] Users cannot access other users' data
  - [ ] API key scopes enforced
  - [ ] Admin functions protected
  - [ ] Permission levels respected

- [ ] **Data Protection**
  - [ ] No sensitive data in logs
  - [ ] Database file not web-accessible
  - [ ] .env file not readable via web
  - [ ] SSL/TLS active on port 8443

- [ ] **Input Validation**
  - [ ] SQL injection attempts blocked
  - [ ] XSS attempts blocked
  - [ ] CSRF tokens validated
  - [ ] File upload restrictions enforced

### **Phase 6: Backup & Recovery Testing**

- [ ] Backup creation
  ```bash
  [ ] Database backup created
  [ ] Configuration backup created
  [ ] Application backup created
  [ ] All backups verified: tar -tzf works
  ```
- [ ] Backup restoration
  ```bash
  [ ] Restore database from backup
  [ ] Verify data integrity
  [ ] Restore configuration
  [ ] All services restart successfully
  ```
- [ ] Disaster recovery
  ```bash
  [ ] Full system restore possible
  [ ] No data loss in restore
  [ ] Application functional after restore
  [ ] All features working
  ```

---

## 📊 **MONITORING & ALERTING SETUP**

### **Essential Monitoring**

- [ ] **System Health**
  - [ ] CPU usage alerts (threshold: 80%)
  - [ ] Memory usage alerts (threshold: 85%)
  - [ ] Disk space alerts (threshold: 90%)
  - [ ] Process down alerts

- [ ] **Security Monitoring**
  - [ ] Failed SSH login alerts
  - [ ] Firewall block alerts
  - [ ] Failed 2FA attempts alerts
  - [ ] API rate limit breaches

- [ ] **Application Monitoring**
  - [ ] Error rate alerts
  - [ ] Response time degradation alerts
  - [ ] Database connection alerts
  - [ ] File integrity changes

- [ ] **Certificate & Maintenance**
  - [ ] SSL certificate expiration alerts (30 days)
  - [ ] Backup failure alerts
  - [ ] Log rotation failure alerts
  - [ ] System update available alerts

### **Alert Configuration**

- [ ] Email alerts configured
- [ ] Alert recipients: admin@example.com
- [ ] Alert frequency: Reasonable (avoid alert fatigue)
- [ ] Alert escalation: Define if not resolved

---

## 🎯 **GO-LIVE SIGN-OFF**

### **Technical Sign-Off**

- [ ] All services running and healthy
- [ ] All tests passed
- [ ] Performance acceptable
- [ ] Security hardening complete
- [ ] Backup & recovery verified
- [ ] Monitoring active and alerting
- [ ] Documentation complete

### **Business Sign-Off**

- [ ] All features working as expected
- [ ] User acceptance testing passed
- [ ] Performance meets requirements
- [ ] Security requirements met
- [ ] Compliance verified
- [ ] Team trained on operations
- [ ] Go-live approval obtained

### **Final Checklist**

- [ ] DNS pointing to production server
- [ ] SSL certificate valid
- [ ] Admin accounts created
- [ ] Initial users invited
- [ ] Support team briefed
- [ ] Emergency contact list distributed
- [ ] Change log documented

---

## 📈 **PRODUCTION OPERATION SCHEDULE**

### **Daily (Automated)**

- [ ] System health checks run
- [ ] Logs analyzed for errors
- [ ] Backups created
- [ ] Security updates checked

### **Weekly (Manual)**

- [ ] Review security logs
- [ ] Check failed login attempts
- [ ] Monitor disk usage
- [ ] Review application errors

### **Monthly (Scheduled)**

- [ ] Full system backup verification
- [ ] Security patches applied
- [ ] Performance analysis
- [ ] Capacity planning
- [ ] SSL certificate check

### **Quarterly (Planned)**

- [ ] Security audit
- [ ] Disaster recovery drill
- [ ] Performance optimization
- [ ] Documentation updates
- [ ] Team training refresh

---

## 🆘 **Emergency Contacts**

```
Primary Admin: [Name] - [Phone] - [Email]
Secondary Admin: [Name] - [Phone] - [Email]
Infrastructure Team: [Contact Info]
Security Team: [Contact Info]
Database Admin: [Contact Info]
```

---

## 📝 **Post-Deployment Notes**

```
[Space for deployment notes and issues encountered]

Date Deployed: _____________
Deployed By: _____________
Issues Encountered: _____________
Resolution: _____________
Time to Full Production: _____________
```

---

## ✨ **DEPLOYMENT COMPLETE CHECKLIST**

- [ ] All items in this checklist completed
- [ ] All tests passed
- [ ] All documentation updated
- [ ] All team members trained
- [ ] All monitoring active
- [ ] All backups verified
- [ ] Application stable in production
- [ ] Go-live approved by stakeholders

**Status: ✅ PRODUCTION DEPLOYMENT SUCCESSFUL**

---

*Deployment Checklist - Budget Control Application*
*Version 1.0 - November 9, 2025*
*For: Debian 13 Production Deployment*

