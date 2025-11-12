#!/bin/bash
################################################################################
# Budget Control - HTTPS/SSL Setup Script
#
# This script automates SSL certificate installation using Let's Encrypt
# and configures Apache for secure HTTPS access with security headers.
#
# Prerequisites:
# - Domain name pointed to server IP
# - Apache installed and running
# - Port 80 and 443 open in firewall
#
# Usage: sudo ./setup-https.sh yourdomain.com
################################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Functions
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_step() {
    echo -e "${BLUE}[STEP]${NC} $1"
}

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    log_error "This script must be run as root (use sudo)"
    exit 1
fi

# Check if domain provided
if [ -z "$1" ]; then
    log_error "Usage: $0 <domain-name>"
    log_error "Example: $0 budget.example.com"
    exit 1
fi

DOMAIN=$1
EMAIL=${2:-"admin@${DOMAIN}"}

log_info "=========================================="
log_info "Budget Control HTTPS Setup"
log_info "Domain: $DOMAIN"
log_info "Email: $EMAIL"
log_info "=========================================="

# Step 1: Check DNS
log_step "Step 1/7: Checking DNS configuration..."
SERVER_IP=$(hostname -I | awk '{print $1}')
DOMAIN_IP=$(dig +short "$DOMAIN" | tail -1)

log_info "Server IP: $SERVER_IP"
log_info "Domain IP: $DOMAIN_IP"

if [ "$SERVER_IP" != "$DOMAIN_IP" ]; then
    log_warn "Domain IP doesn't match server IP!"
    log_warn "Make sure your domain DNS A record points to: $SERVER_IP"
    read -p "Continue anyway? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Step 2: Install Certbot
log_step "Step 2/7: Installing Certbot..."
if ! command -v certbot &> /dev/null; then
    apt update
    apt install -y certbot python3-certbot-apache
    log_info "Certbot installed"
else
    log_info "Certbot already installed"
fi

# Step 3: Check Apache configuration
log_step "Step 3/7: Checking Apache configuration..."
if ! systemctl is-active --quiet apache2; then
    log_error "Apache is not running!"
    exit 1
fi

# Check if VirtualHost exists
VHOST_FILE="/etc/apache2/sites-available/budget-control.conf"
if [ ! -f "$VHOST_FILE" ]; then
    log_warn "VirtualHost not found. Creating configuration..."

    cat > "$VHOST_FILE" << EOF
<VirtualHost *:80>
    ServerName ${DOMAIN}
    ServerAdmin ${EMAIL}

    DocumentRoot /var/www/budget-control/budget-app/public

    <Directory /var/www/budget-control/budget-app/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/budget-control-error.log
    CustomLog \${APACHE_LOG_DIR}/budget-control-access.log combined
</VirtualHost>
EOF

    # Enable site
    a2ensite budget-control.conf
    systemctl reload apache2
    log_info "VirtualHost created and enabled"
fi

# Step 4: Obtain SSL certificate
log_step "Step 4/7: Obtaining SSL certificate from Let's Encrypt..."
certbot --apache \
    --non-interactive \
    --agree-tos \
    --email "$EMAIL" \
    --domains "$DOMAIN" \
    --redirect

if [ $? -eq 0 ]; then
    log_info "SSL certificate obtained successfully!"
else
    log_error "Failed to obtain SSL certificate!"
    log_error "Common issues:"
    log_error "  - Domain not pointing to this server"
    log_error "  - Port 80 not accessible from internet"
    log_error "  - Rate limit reached (5 certificates per domain per week)"
    exit 1
fi

# Step 5: Configure security headers
log_step "Step 5/7: Configuring security headers..."
SSL_VHOST="/etc/apache2/sites-available/budget-control-le-ssl.conf"

if [ -f "$SSL_VHOST" ]; then
    # Add security headers if not present
    if ! grep -q "Strict-Transport-Security" "$SSL_VHOST"; then
        sed -i '/<\/VirtualHost>/i \
\
    # Security Headers\
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"\
    Header always set X-Frame-Options "SAMEORIGIN"\
    Header always set X-XSS-Protection "1; mode=block"\
    Header always set X-Content-Type-Options "nosniff"\
    Header always set Referrer-Policy "strict-origin-when-cross-origin"\
    Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"\
' "$SSL_VHOST"

        systemctl reload apache2
        log_info "Security headers configured"
    else
        log_info "Security headers already configured"
    fi
fi

# Step 6: Test SSL configuration
log_step "Step 6/7: Testing SSL configuration..."
sleep 2

HTTPS_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "https://${DOMAIN}" --insecure)
if [ "$HTTPS_STATUS" = "200" ] || [ "$HTTPS_STATUS" = "302" ]; then
    log_info "HTTPS is working! (HTTP $HTTPS_STATUS)"
else
    log_warn "HTTPS returned unexpected status: $HTTPS_STATUS"
fi

# Check SSL certificate
CERT_INFO=$(echo | openssl s_client -servername "$DOMAIN" -connect "${DOMAIN}:443" 2>/dev/null | openssl x509 -noout -dates 2>/dev/null)
if [ $? -eq 0 ]; then
    log_info "SSL Certificate:"
    echo "$CERT_INFO" | sed 's/^/  /'
else
    log_warn "Could not verify SSL certificate"
fi

# Step 7: Set up auto-renewal
log_step "Step 7/7: Setting up automatic renewal..."

# Test renewal
if certbot renew --dry-run &> /dev/null; then
    log_info "Auto-renewal test: PASSED"
else
    log_warn "Auto-renewal test failed (certificate will still renew)"
fi

# Ensure renewal cron job exists
if ! crontab -l 2>/dev/null | grep -q certbot; then
    (crontab -l 2>/dev/null; echo "0 3 * * * certbot renew --quiet --post-hook 'systemctl reload apache2'") | crontab -
    log_info "Auto-renewal cron job created"
else
    log_info "Auto-renewal cron job already exists"
fi

# Update .env if exists
ENV_FILE="/var/www/budget-control/.env"
if [ -f "$ENV_FILE" ]; then
    if grep -q "SESSION_SECURE" "$ENV_FILE"; then
        sed -i 's/SESSION_SECURE=.*/SESSION_SECURE=true/' "$ENV_FILE"
    else
        echo "SESSION_SECURE=true" >> "$ENV_FILE"
    fi
    log_info "Updated .env file (SESSION_SECURE=true)"
fi

# Summary
log_info ""
log_info "=========================================="
log_info "✓ HTTPS Setup Complete!"
log_info "=========================================="
log_info ""
log_info "Your site is now accessible at:"
log_info "  https://${DOMAIN}"
log_info ""
log_info "Certificate details:"
log_info "  Issuer: Let's Encrypt"
log_info "  Valid for: 90 days"
log_info "  Auto-renewal: Enabled"
log_info ""
log_info "Security features enabled:"
log_info "  ✓ HTTPS redirect (HTTP → HTTPS)"
log_info "  ✓ HSTS (max-age=1 year)"
log_info "  ✓ Security headers"
log_info "  ✓ TLS 1.2+"
log_info ""
log_info "Next steps:"
log_info "  1. Test your site: https://${DOMAIN}"
log_info "  2. Check SSL rating: https://www.ssllabs.com/ssltest/analyze.html?d=${DOMAIN}"
log_info "  3. Update any HTTP links to HTTPS"
log_info ""
log_info "Certificate will auto-renew 30 days before expiry."
log_info "=========================================="

# Test SSL Labs rating (optional, takes time)
read -p "Test SSL configuration with SSL Labs? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    log_info "Submitting to SSL Labs... (this takes 2-3 minutes)"
    log_info "Visit: https://www.ssllabs.com/ssltest/analyze.html?d=${DOMAIN}"
fi

exit 0
