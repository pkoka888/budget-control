#!/bin/bash
################################################################################
# Budget Control - Complete Backup Solution
#
# Features:
# - Database backup with SQLite integrity check
# - Uploads directory backup
# - Encryption with AES-256
# - Compression
# - Rotation policy (7 daily, 4 weekly, 12 monthly)
# - Cloud sync (S3 or DigitalOcean Spaces)
# - Email notifications
# - Backup verification
#
# Usage: ./backup-database.sh [--cloud] [--no-encrypt]
################################################################################

set -e  # Exit on error

# Configuration
BACKUP_BASE="/backups/budget-control"
DB_PATH="/var/www/budget-control/budget-app/database/budget.db"
UPLOADS_DIR="/var/www/budget-control/budget-app/uploads"
BANK_JSON_DIR="/var/www/budget-control/user-data/bank-json"
ENCRYPTION_KEY="/root/.budget-backup-key"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
DATE_ONLY=$(date +%Y%m%d)

# Backup directories
DAILY_DIR="${BACKUP_BASE}/daily"
WEEKLY_DIR="${BACKUP_BASE}/weekly"
MONTHLY_DIR="${BACKUP_BASE}/monthly"

# Email settings
ADMIN_EMAIL="${BACKUP_ADMIN_EMAIL:-admin@example.com}"
APP_NAME="Budget Control"

# Cloud sync settings (S3 or DigitalOcean Spaces)
ENABLE_CLOUD_SYNC=${ENABLE_CLOUD_SYNC:-false}
S3_BUCKET="${S3_BUCKET:-s3://my-backups/budget-control}"

# Parse command line arguments
CLOUD_SYNC=false
ENCRYPT=true

for arg in "$@"; do
    case $arg in
        --cloud)
            CLOUD_SYNC=true
            shift
            ;;
        --no-encrypt)
            ENCRYPT=false
            shift
            ;;
    esac
done

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Logging functions
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Create backup directories
log_info "Creating backup directories..."
mkdir -p "$DAILY_DIR" "$WEEKLY_DIR" "$MONTHLY_DIR"

# Start backup
log_info "=========================================="
log_info "Budget Control Backup Started"
log_info "Timestamp: $TIMESTAMP"
log_info "=========================================="

# Generate encryption key if it doesn't exist
if [ "$ENCRYPT" = true ] && [ ! -f "$ENCRYPTION_KEY" ]; then
    log_warn "Encryption key not found. Generating new key..."
    openssl rand -base64 32 > "$ENCRYPTION_KEY"
    chmod 600 "$ENCRYPTION_KEY"
    log_info "Encryption key created at: $ENCRYPTION_KEY"
    log_warn "IMPORTANT: Save this key in a secure location!"
fi

# Determine backup directory based on date
DAY_OF_WEEK=$(date +%u)  # 1-7 (Monday-Sunday)
DAY_OF_MONTH=$(date +%d)

if [ "$DAY_OF_MONTH" = "01" ]; then
    BACKUP_DIR="$MONTHLY_DIR"
    BACKUP_TYPE="monthly"
elif [ "$DAY_OF_WEEK" = "7" ]; then  # Sunday
    BACKUP_DIR="$WEEKLY_DIR"
    BACKUP_TYPE="weekly"
else
    BACKUP_DIR="$DAILY_DIR"
    BACKUP_TYPE="daily"
fi

BACKUP_NAME="budget_${BACKUP_TYPE}_${TIMESTAMP}"
log_info "Backup type: $BACKUP_TYPE"
log_info "Backup name: $BACKUP_NAME"

# Check if database exists
if [ ! -f "$DB_PATH" ]; then
    log_error "Database not found at: $DB_PATH"
    exit 1
fi

# Check database integrity before backup
log_info "Checking database integrity..."
INTEGRITY_CHECK=$(sqlite3 "$DB_PATH" "PRAGMA integrity_check;" 2>&1)
if [ "$INTEGRITY_CHECK" != "ok" ]; then
    log_error "Database integrity check failed!"
    log_error "$INTEGRITY_CHECK"
    echo "Database integrity check failed at $(date)" | mail -s "CRITICAL: Budget Control Backup Failed" "$ADMIN_EMAIL"
    exit 1
fi
log_info "Database integrity: OK"

# Backup database using SQLite .backup command (safest method)
log_info "Backing up database..."
TMP_DB="${BACKUP_DIR}/${BACKUP_NAME}.db"
sqlite3 "$DB_PATH" ".backup '${TMP_DB}'"

if [ $? -eq 0 ]; then
    log_info "Database backup successful: $(du -h "$TMP_DB" | cut -f1)"
else
    log_error "Database backup failed!"
    exit 1
fi

# Backup uploads directory
if [ -d "$UPLOADS_DIR" ]; then
    log_info "Backing up uploads directory..."
    tar -czf "${BACKUP_DIR}/${BACKUP_NAME}_uploads.tar.gz" -C "$(dirname "$UPLOADS_DIR")" "$(basename "$UPLOADS_DIR")" 2>/dev/null
    log_info "Uploads backup successful: $(du -h "${BACKUP_DIR}/${BACKUP_NAME}_uploads.tar.gz" | cut -f1)"
fi

# Backup bank JSON directory (if exists)
if [ -d "$BANK_JSON_DIR" ]; then
    log_info "Backing up bank JSON directory..."
    tar -czf "${BACKUP_DIR}/${BACKUP_NAME}_bank-json.tar.gz" -C "$(dirname "$BANK_JSON_DIR")" "$(basename "$BANK_JSON_DIR")" 2>/dev/null
    log_info "Bank JSON backup successful: $(du -h "${BACKUP_DIR}/${BACKUP_NAME}_bank-json.tar.gz" | cut -f1)"
fi

# Encrypt backups
if [ "$ENCRYPT" = true ]; then
    log_info "Encrypting backups..."

    # Encrypt database
    openssl enc -aes-256-cbc -salt -pbkdf2 \
        -in "$TMP_DB" \
        -out "${TMP_DB}.enc" \
        -pass file:"$ENCRYPTION_KEY"
    rm "$TMP_DB"
    log_info "Database encrypted: ${BACKUP_NAME}.db.enc"

    # Encrypt uploads
    if [ -f "${BACKUP_DIR}/${BACKUP_NAME}_uploads.tar.gz" ]; then
        openssl enc -aes-256-cbc -salt -pbkdf2 \
            -in "${BACKUP_DIR}/${BACKUP_NAME}_uploads.tar.gz" \
            -out "${BACKUP_DIR}/${BACKUP_NAME}_uploads.tar.gz.enc" \
            -pass file:"$ENCRYPTION_KEY"
        rm "${BACKUP_DIR}/${BACKUP_NAME}_uploads.tar.gz"
        log_info "Uploads encrypted"
    fi

    # Encrypt bank JSON
    if [ -f "${BACKUP_DIR}/${BACKUP_NAME}_bank-json.tar.gz" ]; then
        openssl enc -aes-256-cbc -salt -pbkdf2 \
            -in "${BACKUP_DIR}/${BACKUP_NAME}_bank-json.tar.gz" \
            -out "${BACKUP_DIR}/${BACKUP_NAME}_bank-json.tar.gz.enc" \
            -pass file:"$ENCRYPTION_KEY"
        rm "${BACKUP_DIR}/${BACKUP_NAME}_bank-json.tar.gz"
        log_info "Bank JSON encrypted"
    fi
fi

# Verify backup
log_info "Verifying backup..."
if [ "$ENCRYPT" = true ]; then
    BACKUP_FILE="${TMP_DB}.enc"
else
    BACKUP_FILE="$TMP_DB"
fi

if [ -f "$BACKUP_FILE" ] && [ -s "$BACKUP_FILE" ]; then
    BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
    log_info "Backup verified: $BACKUP_SIZE"
else
    log_error "Backup verification failed!"
    exit 1
fi

# Cloud sync
if [ "$CLOUD_SYNC" = true ] || [ "$ENABLE_CLOUD_SYNC" = true ]; then
    log_info "Syncing to cloud storage..."

    if command -v aws &> /dev/null; then
        # AWS S3
        aws s3 sync "$BACKUP_DIR" "$S3_BUCKET/$BACKUP_TYPE/" --delete
        log_info "Cloud sync completed (S3)"
    elif command -v s3cmd &> /dev/null; then
        # s3cmd (works with DigitalOcean Spaces)
        s3cmd sync "$BACKUP_DIR/" "$S3_BUCKET/$BACKUP_TYPE/" --delete-removed
        log_info "Cloud sync completed (s3cmd)"
    else
        log_warn "Cloud sync requested but no S3 client found (aws or s3cmd)"
    fi
fi

# Rotation: Keep 7 daily, 4 weekly, 12 monthly
log_info "Applying rotation policy..."

# Daily backups: keep last 7
if [ -d "$DAILY_DIR" ]; then
    find "$DAILY_DIR" -name "budget_daily_*" -type f -mtime +7 -delete
    DAILY_COUNT=$(find "$DAILY_DIR" -name "budget_daily_*" -type f | wc -l)
    log_info "Daily backups retained: $DAILY_COUNT"
fi

# Weekly backups: keep last 4
if [ -d "$WEEKLY_DIR" ]; then
    find "$WEEKLY_DIR" -name "budget_weekly_*" -type f -mtime +28 -delete
    WEEKLY_COUNT=$(find "$WEEKLY_DIR" -name "budget_weekly_*" -type f | wc -l)
    log_info "Weekly backups retained: $WEEKLY_COUNT"
fi

# Monthly backups: keep last 12
if [ -d "$MONTHLY_DIR" ]; then
    find "$MONTHLY_DIR" -name "budget_monthly_*" -type f -mtime +365 -delete
    MONTHLY_COUNT=$(find "$MONTHLY_DIR" -name "budget_monthly_*" -type f | wc -l)
    log_info "Monthly backups retained: $MONTHLY_COUNT"
fi

# Calculate total backup size
TOTAL_SIZE=$(du -sh "$BACKUP_BASE" | cut -f1)

# Send email notification
log_info "Sending notification email..."
EMAIL_BODY="Budget Control Backup Completed Successfully

Backup Details:
- Type: $BACKUP_TYPE
- Timestamp: $TIMESTAMP
- Database Size: $BACKUP_SIZE
- Total Backup Size: $TOTAL_SIZE
- Encryption: $([ "$ENCRYPT" = true ] && echo "Enabled" || echo "Disabled")
- Cloud Sync: $([ "$CLOUD_SYNC" = true ] && echo "Enabled" || echo "Disabled")

Retention:
- Daily backups: $DAILY_COUNT (last 7 days)
- Weekly backups: $WEEKLY_COUNT (last 4 weeks)
- Monthly backups: $MONTHLY_COUNT (last 12 months)

Backup Location: $BACKUP_DIR
"

echo "$EMAIL_BODY" | mail -s "✓ Budget Control Backup Successful ($BACKUP_TYPE)" "$ADMIN_EMAIL" 2>/dev/null || log_warn "Email notification failed (mail command not available)"

# Summary
log_info "=========================================="
log_info "Backup completed successfully!"
log_info "Type: $BACKUP_TYPE"
log_info "Location: $BACKUP_DIR"
log_info "Size: $BACKUP_SIZE"
log_info "Total: $TOTAL_SIZE"
log_info "=========================================="

exit 0
