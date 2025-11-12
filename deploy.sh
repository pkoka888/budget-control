#!/bin/bash
################################################################################
# Budget Control - Production Deployment Script
# Automated deployment with health checks and rollback capability
################################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKUP_DIR="$PROJECT_DIR/backups"
DEPLOY_LOG="$PROJECT_DIR/deploy.log"
MAX_BACKUPS=5

# Functions
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$DEPLOY_LOG"
}

success() {
    echo -e "${GREEN}✓ $1${NC}" | tee -a "$DEPLOY_LOG"
}

error() {
    echo -e "${RED}✗ $1${NC}" | tee -a "$DEPLOY_LOG"
}

warning() {
    echo -e "${YELLOW}⚠ $1${NC}" | tee -a "$DEPLOY_LOG"
}

# Check prerequisites
check_prerequisites() {
    log "Checking prerequisites..."

    if ! command -v docker &> /dev/null; then
        error "Docker is not installed"
        exit 1
    fi

    if ! command -v docker-compose &> /dev/null; then
        error "Docker Compose is not installed"
        exit 1
    fi

    if [ ! -f ".env" ]; then
        error ".env file not found. Please create it from .env.example"
        exit 1
    fi

    success "Prerequisites check passed"
}

# Create backup
backup_database() {
    log "Creating database backup..."

    mkdir -p "$BACKUP_DIR"

    if [ -f "budget-app/database/budget.db" ]; then
        BACKUP_FILE="$BACKUP_DIR/budget_$(date +%Y%m%d_%H%M%S).db"
        cp "budget-app/database/budget.db" "$BACKUP_FILE"
        success "Database backed up to: $BACKUP_FILE"

        # Keep only the last N backups
        cd "$BACKUP_DIR"
        ls -t budget_*.db | tail -n +$((MAX_BACKUPS + 1)) | xargs -r rm
        cd "$PROJECT_DIR"
    else
        warning "No database found to backup"
    fi
}

# Apply database migrations
apply_migrations() {
    log "Applying database migrations..."

    if [ -f "budget-app/database/apply_migrations.php" ]; then
        docker-compose exec -T budget-control php /var/www/html/database/apply_migrations.php
        success "Migrations applied successfully"
    else
        warning "Migration script not found, skipping"
    fi
}

# Build Docker images
build_images() {
    log "Building Docker images..."

    docker-compose build --no-cache
    success "Docker images built successfully"
}

# Start services
start_services() {
    log "Starting services..."

    docker-compose up -d
    success "Services started"
}

# Health check
health_check() {
    log "Performing health check..."

    MAX_ATTEMPTS=30
    ATTEMPT=0

    while [ $ATTEMPT -lt $MAX_ATTEMPTS ]; do
        if curl -f http://localhost:8080/health &> /dev/null; then
            success "Application is healthy"
            return 0
        fi

        ATTEMPT=$((ATTEMPT + 1))
        sleep 2
    done

    error "Health check failed after $MAX_ATTEMPTS attempts"
    return 1
}

# Rollback
rollback() {
    error "Deployment failed! Rolling back..."

    docker-compose down

    # Restore latest backup
    LATEST_BACKUP=$(ls -t "$BACKUP_DIR"/budget_*.db 2>/dev/null | head -n1)
    if [ -n "$LATEST_BACKUP" ]; then
        cp "$LATEST_BACKUP" "budget-app/database/budget.db"
        success "Database restored from: $LATEST_BACKUP"
    fi

    error "Rollback completed. Please check logs for errors."
    exit 1
}

# Show logs
show_logs() {
    log "Recent application logs:"
    docker-compose logs --tail=50 budget-control
}

# Main deployment process
main() {
    log "=========================================="
    log "Budget Control - Production Deployment"
    log "=========================================="

    # Check prerequisites
    check_prerequisites

    # Create backup
    backup_database

    # Build and deploy
    build_images || rollback
    start_services || rollback

    # Wait for services to be ready
    log "Waiting for services to be ready..."
    sleep 10

    # Apply migrations
    apply_migrations || rollback

    # Health check
    if ! health_check; then
        rollback
    fi

    # Success!
    success "=========================================="
    success "Deployment completed successfully!"
    success "=========================================="
    log ""
    log "Application is now running at: http://localhost:8080"
    log "Check logs with: docker-compose logs -f budget-control"
    log "Stop application: docker-compose down"
    log ""
}

# Run deployment
main "$@"
