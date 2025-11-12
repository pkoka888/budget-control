# Docker Deployment Guide - Budget Control

Complete guide for deploying Budget Control using Docker with Debian 13.

---

## 🐳 **Quick Start**

### **Prerequisites**

```bash
# Install Docker (if not already installed)
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Install Docker Compose
sudo apt-get install docker-compose-plugin

# Verify installation
docker --version
docker compose version
```

### **1. Configure Environment**

```bash
# Copy environment template
cp .env.example .env

# Edit configuration
nano .env
```

**Minimum required settings:**
```bash
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Budget Control"
```

### **2. Deploy**

```bash
# Automated deployment (recommended)
./deploy.sh

# Or manual deployment
docker-compose up -d
```

### **3. Access Application**

```
http://localhost:8080
```

---

## 📦 **What's Included**

### **Technology Stack:**
- **OS:** Debian 13 (Trixie)
- **Web Server:** Nginx
- **PHP:** 8.3-FPM with extensions
- **Database:** SQLite 3
- **Process Manager:** Supervisor
- **Cron:** Automated tasks

### **Docker Setup:**
```
budget-control/
├── Dockerfile              # Main application container
├── docker-compose.yml      # Development setup
├── docker-compose.prod.yml # Production with HTTPS
├── docker/
│   ├── nginx/             # Nginx configuration
│   ├── php/               # PHP-FPM configuration
│   ├── supervisor/        # Process management
│   └── cron/              # Scheduled tasks
└── deploy.sh              # Automated deployment
```

---

## 🚀 **Deployment Options**

### **Option 1: Development (Quick Test)**

```bash
# Start services
docker-compose up -d

# View logs
docker-compose logs -f

# Stop services
docker-compose down
```

**Access:** http://localhost:8080

### **Option 2: Production (Automated)**

```bash
# Run deployment script
./deploy.sh
```

**Features:**
- ✅ Pre-deployment checks
- ✅ Automatic database backup
- ✅ Migration application
- ✅ Health checks
- ✅ Automatic rollback on failure
- ✅ Deployment logs

### **Option 3: Production with HTTPS**

```bash
# Edit SSL configuration
nano docker-compose.prod.yml

# Update domain and email
# Replace example.com with your domain
# Replace admin@example.com with your email

# Deploy
docker-compose -f docker-compose.prod.yml up -d
```

**Features:**
- ✅ Nginx reverse proxy
- ✅ Let's Encrypt SSL certificates
- ✅ Automatic renewal
- ✅ Database backups
- ✅ Production logging

---

## 🔧 **Configuration**

### **Environment Variables**

See `.env.example` for all available options.

**Critical settings:**
```bash
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-domain.com

# Database
DB_PATH=/var/www/html/database/budget.db

# Email (required for invitations)
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password

# Family Sharing
INVITATION_EXPIRY_DAYS=7
CHILD_APPROVAL_THRESHOLD=100
```

### **Ports**

Default ports:
- **Development:** 8080:80
- **Production:** 80:80, 443:443

To change port:
```yaml
# In docker-compose.yml
ports:
  - "3000:80"  # Change 3000 to desired port
```

### **Volumes**

Persistent data is stored in:
```
./budget-app/database    # SQLite database
./budget-app/logs        # Application logs
./budget-app/uploads     # File uploads
./budget-app/user-data   # User data
./backups                # Database backups
```

---

## 📋 **Common Commands**

### **Container Management**

```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# Restart services
docker-compose restart

# View running containers
docker-compose ps

# Remove all containers and volumes
docker-compose down -v
```

### **Logs**

```bash
# View all logs
docker-compose logs

# Follow logs in real-time
docker-compose logs -f

# View specific service logs
docker-compose logs -f budget-control

# Last 100 lines
docker-compose logs --tail=100
```

### **Database Operations**

```bash
# Backup database
docker-compose exec budget-control \
  sqlite3 /var/www/html/database/budget.db \
  ".backup /var/www/html/database/backup_$(date +%Y%m%d).db"

# Restore database
docker-compose exec budget-control \
  sqlite3 /var/www/html/database/budget.db \
  ".restore /var/www/html/database/backup_20250112.db"

# Access database CLI
docker-compose exec budget-control \
  sqlite3 /var/www/html/database/budget.db
```

### **Shell Access**

```bash
# Access container shell
docker-compose exec budget-control bash

# Run PHP commands
docker-compose exec budget-control php -v

# Run as budgetapp user
docker-compose exec -u budgetapp budget-control bash
```

### **Updates**

```bash
# Pull latest code
git pull origin main

# Rebuild and deploy
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# Or use deployment script
./deploy.sh
```

---

## 🔒 **Security**

### **Best Practices**

1. **Environment Files:**
   ```bash
   # Protect .env file
   chmod 600 .env

   # Never commit .env to git
   echo ".env" >> .gitignore
   ```

2. **Database Permissions:**
   ```bash
   # Ensure proper permissions
   chmod 644 budget-app/database/budget.db
   chown 1000:1000 budget-app/database/budget.db
   ```

3. **SSL Certificates:**
   ```bash
   # Use Let's Encrypt for free SSL
   # Included in docker-compose.prod.yml
   ```

4. **Firewall:**
   ```bash
   # Allow only necessary ports
   sudo ufw allow 80/tcp
   sudo ufw allow 443/tcp
   sudo ufw enable
   ```

### **Production Checklist**

- [ ] Set `APP_DEBUG=false` in .env
- [ ] Set `APP_ENV=production` in .env
- [ ] Configure real email service (not Gmail)
- [ ] Set up SSL certificates
- [ ] Enable firewall
- [ ] Configure backups
- [ ] Set up monitoring
- [ ] Review security headers
- [ ] Enable rate limiting

---

## 📊 **Monitoring**

### **Health Check**

```bash
# Check application health
curl http://localhost:8080/health

# Response: "healthy"
```

### **Resource Usage**

```bash
# Container stats
docker stats budget-control-app

# Disk usage
docker system df

# Cleanup unused resources
docker system prune -a
```

### **Application Logs**

```bash
# PHP errors
docker-compose exec budget-control tail -f /var/log/php_errors.log

# Nginx access
docker-compose exec budget-control tail -f /var/log/nginx/access.log

# Nginx errors
docker-compose exec budget-control tail -f /var/log/nginx/error.log

# Cron jobs
docker-compose exec budget-control tail -f /var/www/html/logs/cron.log
```

---

## 🐛 **Troubleshooting**

### **Container Won't Start**

```bash
# Check logs
docker-compose logs budget-control

# Check configuration
docker-compose config

# Rebuild from scratch
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
```

### **Permission Denied Errors**

```bash
# Fix ownership
docker-compose exec budget-control \
  chown -R budgetapp:budgetapp /var/www/html

# Fix permissions
docker-compose exec budget-control \
  chmod -R 755 /var/www/html/public
docker-compose exec budget-control \
  chmod -R 777 /var/www/html/database
```

### **Database Locked**

```bash
# Check for processes
docker-compose exec budget-control \
  fuser /var/www/html/database/budget.db

# Restart services
docker-compose restart
```

### **Email Not Sending**

```bash
# Test email configuration
docker-compose exec budget-control php -r "
  require '/var/www/html/vendor/autoload.php';
  use BudgetApp\Services\EmailService;
  use BudgetApp\Database;
  use BudgetApp\Config;
  \$service = new EmailService(
    new Database('/var/www/html/database/budget.db'),
    new Config('/var/www/html')
  );
  echo \$service->send(
    'test@example.com',
    'Test',
    '<h1>Test</h1>',
    'Test'
  ) ? 'SUCCESS' : 'FAILED';
"
```

### **502 Bad Gateway**

```bash
# Check PHP-FPM status
docker-compose exec budget-control supervisorctl status php-fpm

# Restart PHP-FPM
docker-compose exec budget-control supervisorctl restart php-fpm

# Check socket
docker-compose exec budget-control ls -la /run/php/
```

---

## 📦 **Backups**

### **Automatic Backups (Production)**

Included in `docker-compose.prod.yml`:
- Daily database backups
- 7-day retention
- Stored in `./backups/`

### **Manual Backup**

```bash
# Full backup
./backup.sh

# Database only
docker-compose exec budget-control \
  cp /var/www/html/database/budget.db \
  /var/www/html/database/backup_$(date +%Y%m%d).db

# Copy to host
docker cp budget-control-app:/var/www/html/database/budget.db \
  ./backups/backup_$(date +%Y%m%d).db
```

### **Restore from Backup**

```bash
# Stop application
docker-compose down

# Restore database
cp ./backups/backup_20250112.db \
   ./budget-app/database/budget.db

# Start application
docker-compose up -d
```

---

## 🔄 **Updates & Maintenance**

### **Update Application**

```bash
# 1. Backup current state
./deploy.sh

# 2. Pull latest code
git pull origin main

# 3. Rebuild and deploy
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### **Update Dependencies**

```bash
# Update Composer packages
docker-compose exec budget-control composer update

# Clear opcache
docker-compose exec budget-control \
  killall -USR2 php-fpm8.3
```

### **Clean Up**

```bash
# Remove old images
docker image prune -a

# Remove old volumes
docker volume prune

# Remove old backups
find ./backups -name "budget_*.db" -mtime +30 -delete
```

---

## 🎯 **Performance Tuning**

### **PHP-FPM**

Edit `docker/php/php-fpm.conf`:
```ini
pm.max_children = 50     # Increase for more traffic
pm.start_servers = 10    # More initial workers
pm.min_spare_servers = 10
pm.max_spare_servers = 35
```

### **Nginx**

Edit `docker/nginx/nginx.conf`:
```nginx
worker_processes auto;        # Use all CPU cores
worker_connections 2048;      # Increase connections
```

### **PHP**

Edit `docker/php/php.ini`:
```ini
memory_limit = 256M          # More memory
opcache.memory_consumption = 256  # Bigger opcache
```

After changes:
```bash
docker-compose restart
```

---

## 📞 **Support**

### **Get Help**

```bash
# Check system status
docker-compose ps
docker-compose logs --tail=100

# View deployment log
cat deploy.log

# Run health check
curl -v http://localhost:8080/health
```

### **Report Issues**

Include in your report:
1. Docker version: `docker --version`
2. Docker Compose version: `docker compose version`
3. Error logs: `docker-compose logs`
4. Configuration: `docker-compose config`
5. System info: `uname -a`

---

**Status:** ✅ Ready for Production
**Version:** 2.0.0 (Family Sharing)
**Last Updated:** 2025-11-12

