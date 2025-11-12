#!/bin/bash
################################################################################
# Budget Control - Database Restore Script
#
# Restores database from encrypted backup
#
# Usage: ./restore-database.sh <backup-file>
# Example: ./restore-database.sh /backups/budget-control/daily/budget_daily_20251112_103000.db.enc
################################################################################

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check root
if [ "$EUID" -ne 0 ]; then
    log_error "This script must be run as root (use sudo)"
    exit 1
fi

# Check arguments
if [ -z "$1" ]; then
    log_error "Usage: $0 <backup-file>"
    log_error "Example: $0 /backups/budget-control/daily/budget_daily_20251112_103000.db.enc"
    exit 1
fi

BACKUP_FILE="$1"
DB_PATH="/var/www/budget-control/budget-app/database/budget.db"
ENCRYPTION_KEY="/root/.budget-backup-key"
TEMP_DIR="/tmp/budget-restore-$$"

# Check if backup file exists
if [ ! -f "$BACKUP_FILE" ]; then
    log_error "Backup file not found: $BACKUP_FILE"
    exit 1
fi

# Check if encryption key exists
if [ ! -f "$ENCRYPTION_KEY" ]; then
    log_error "Encryption key not found: $ENCRYPTION_KEY"
    log_error "Cannot decrypt backup without the encryption key!"
    exit 1
fi

log_info "=========================================="
log_info "Budget Control Database Restore"
log_info "=========================================="
log_info "Backup file: $BACKUP_FILE"
log_info "Target: $DB_PATH"
log_info ""

# Warning
log_warn "⚠️  WARNING: This will overwrite the current database!"
log_warn "Current database will be backed up to: ${DB_PATH}.before-restore"
echo ""
read -p "Are you sure you want to continue? (yes/NO): " -r
echo
if [ "$REPLY" != "yes" ]; then
    log_info "Restore cancelled."
    exit 0
fi

# Create temporary directory
mkdir -p "$TEMP_DIR"

# Stop Apache to prevent database access during restore
log_info "Stopping Apache..."
systemctl stop apache2

# Backup current database
if [ -f "$DB_PATH" ]; then
    log_info "Backing up current database..."
    cp "$DB_PATH" "${DB_PATH}.before-restore"
    log_info "Current database saved to: ${DB_PATH}.before-restore"
fi

# Decrypt backup
log_info "Decrypting backup..."
if [[ "$BACKUP_FILE" == *.enc ]]; then
    DECRYPTED_FILE="${TEMP_DIR}/restored.db"
    openssl enc -aes-256-cbc -d -pbkdf2 \
        -in "$BACKUP_FILE" \
        -out "$DECRYPTED_FILE" \
        -pass file:"$ENCRYPTION_KEY"

    if [ $? -ne 0 ]; then
        log_error "Decryption failed! Wrong encryption key?"
        systemctl start apache2
        rm -rf "$TEMP_DIR"
        exit 1
    fi
else
    # Not encrypted, use directly
    DECRYPTED_FILE="$BACKUP_FILE"
fi

# Verify database integrity
log_info "Verifying backup integrity..."
INTEGRITY=$(sqlite3 "$DECRYPTED_FILE" "PRAGMA integrity_check;" 2>&1)
if [ "$INTEGRITY" != "ok" ]; then
    log_error "Backup database is corrupted!"
    log_error "$INTEGRITY"
    systemctl start apache2
    rm -rf "$TEMP_DIR"
    exit 1
fi
log_info "Backup integrity: OK"

# Get table count
TABLE_COUNT=$(sqlite3 "$DECRYPTED_FILE" "SELECT COUNT(*) FROM sqlite_master WHERE type='table';" 2>/dev/null)
log_info "Tables in backup: $TABLE_COUNT"

# Restore database
log_info "Restoring database..."
cp "$DECRYPTED_FILE" "$DB_PATH"
chown www-data:www-data "$DB_PATH"
chmod 664 "$DB_PATH"

# Verify restored database
log_info "Verifying restored database..."
RESTORED_INTEGRITY=$(sqlite3 "$DB_PATH" "PRAGMA integrity_check;")
if [ "$RESTORED_INTEGRITY" != "ok" ]; then
    log_error "Restored database verification failed!"
    log_error "Restoring previous database..."
    mv "${DB_PATH}.before-restore" "$DB_PATH"
    systemctl start apache2
    rm -rf "$TEMP_DIR"
    exit 1
fi

# Cleanup
rm -rf "$TEMP_DIR"

# Start Apache
log_info "Starting Apache..."
systemctl start apache2

# Success
log_info ""
log_info "=========================================="
log_info "✓ Database Restore Complete!"
log_info "=========================================="
log_info "Database restored from: $BACKUP_FILE"
log_info "Tables: $TABLE_COUNT"
log_info "Previous database saved: ${DB_PATH}.before-restore"
log_info ""
log_info "Application is now running with restored data."
log_info "=========================================="

exit 0
