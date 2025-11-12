# 🚀 Budget Control - Parallel Development Roadmap

## Overview

This document outlines the complete development roadmap with parallel execution strategies, agent assignments, and task dependencies for Budget Control v1.1 and v2.0.

---

## 📋 Immediate Tasks (Optional Enhancements)

**Timeline**: 1-2 weeks
**Parallel Execution**: All 4 tasks can run simultaneously
**Team Size**: 4 developers (or 1 developer, 1-2 days per task)

### Task 1: Add Logo/Branding to Views
**Priority**: Low
**Effort**: 4-8 hours
**Agent**: Frontend UI Specialist
**Can Run in Parallel**: ✅ Yes (independent)

#### Subtasks:
1. **Design Phase** (2 hours)
   - Create logo design (SVG format recommended)
   - Define color scheme and brand guidelines
   - Design favicon (ICO, PNG formats)

2. **Implementation** (2 hours)
   - Add logo to `views/layout.php` header
   - Update `views/auth/*.php` pages with branding
   - Add favicon to `public/`
   - Update meta tags with branding

3. **Testing** (1 hour)
   - Test logo display across all pages
   - Test responsive behavior (mobile, tablet, desktop)
   - Browser compatibility check

#### Files to Modify:
```
budget-app/views/layout.php
budget-app/views/auth/login.php
budget-app/views/auth/register.php
budget-app/public/favicon.ico
budget-app/public/assets/logo.svg
budget-app/public/assets/logo-dark.svg
```

#### Agent Command:
```bash
# Use frontend-ui agent
Task: Add logo and branding to Budget Control application
- Create logo design (SVG)
- Update layout.php with header logo
- Add favicon to public directory
- Ensure responsive design
- Test across all pages
```

---

### Task 2: Configure Email for Notifications

**Priority**: Medium
**Effort**: 6-10 hours
**Agent**: Backend Developer + DevOps
**Can Run in Parallel**: ✅ Yes (independent)

#### Subtasks:
1. **Email Service Setup** (2 hours)
   - Choose provider (SendGrid, Mailgun, AWS SES, or SMTP)
   - Configure API keys/credentials
   - Update `.env` with email settings

2. **Email Service Implementation** (3 hours)
   - Create `src/Services/EmailService.php`
   - Implement email templates (HTML + plain text)
   - Add queue support for async sending

3. **Notification Integration** (2 hours)
   - Update `NotificationService.php` to use EmailService
   - Add email preferences to user settings
   - Implement opt-in/opt-out functionality

4. **Testing** (1 hour)
   - Test email delivery
   - Test email templates rendering
   - Test unsubscribe links

#### Files to Create:
```
budget-app/src/Services/EmailService.php
budget-app/views/emails/layout.php
budget-app/views/emails/budget-alert.php
budget-app/views/emails/goal-milestone.php
budget-app/views/emails/bill-reminder.php
budget-app/views/emails/weekly-summary.php
```

#### Files to Modify:
```
.env.example
budget-app/src/Services/NotificationService.php
budget-app/database/migrations/005_add_email_preferences.sql
```

#### Implementation Plan:
```php
// src/Services/EmailService.php
class EmailService {
    public function send($to, $subject, $template, $data) {
        // Use configured provider (SMTP, SendGrid, etc.)
    }

    public function sendBudgetAlert($user, $budget, $amount) {
        // Send budget exceeded alert
    }

    public function sendGoalMilestone($user, $goal, $milestone) {
        // Send goal milestone notification
    }
}
```

#### Agent Command:
```bash
# Use backend developer agent
Task: Implement email notification system
- Create EmailService with configurable providers
- Add email templates for all notification types
- Integrate with NotificationService
- Add user email preferences
- Test with real email provider
```

---

### Task 3: Set Up Automated Backups

**Priority**: High
**Effort**: 4-6 hours
**Agent**: DevOps Specialist
**Can Run in Parallel**: ✅ Yes (independent)

#### Subtasks:
1. **Backup Script Enhancement** (2 hours)
   - Enhance existing script from DEPLOYMENT.md
   - Add rotation policy (keep 7 daily, 4 weekly, 12 monthly)
   - Add compression with encryption
   - Add backup verification

2. **Storage Setup** (1 hour)
   - Configure local backup storage
   - Optional: Configure S3/cloud storage
   - Set up backup monitoring

3. **Automation** (1 hour)
   - Set up cron jobs
   - Add email notifications for backup status
   - Create restore script

4. **Testing** (1 hour)
   - Test backup creation
   - Test restore process
   - Test rotation policy

#### Files to Create:
```
scripts/backup-database.sh
scripts/backup-uploads.sh
scripts/restore-database.sh
scripts/backup-to-cloud.sh
scripts/verify-backup.sh
scripts/backup-rotation.sh
```

#### Enhanced Backup Script:
```bash
#!/bin/bash
# Complete backup solution with encryption and cloud sync

BACKUP_DIR="/backups/budget-control"
DB_PATH="/var/www/budget-control/budget-app/database/budget.db"
UPLOADS_DIR="/var/www/budget-control/budget-app/uploads"
ENCRYPTION_KEY="/root/.backup-key"
S3_BUCKET="s3://my-backups/budget-control"

# Create backup with timestamp
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_NAME="budget_${TIMESTAMP}"

# Backup database
sqlite3 $DB_PATH ".backup ${BACKUP_DIR}/${BACKUP_NAME}.db"

# Backup uploads
tar -czf ${BACKUP_DIR}/${BACKUP_NAME}_uploads.tar.gz $UPLOADS_DIR

# Encrypt backups
openssl enc -aes-256-cbc -salt -in ${BACKUP_DIR}/${BACKUP_NAME}.db \
    -out ${BACKUP_DIR}/${BACKUP_NAME}.db.enc -pass file:$ENCRYPTION_KEY

# Sync to cloud (optional)
aws s3 sync $BACKUP_DIR $S3_BUCKET --delete

# Verify backup integrity
sqlite3 ${BACKUP_DIR}/${BACKUP_NAME}.db "PRAGMA integrity_check;"

# Rotation: Keep 7 daily, 4 weekly, 12 monthly
find $BACKUP_DIR -name "budget_*.db.enc" -mtime +7 -delete

# Send notification
echo "Backup completed: ${BACKUP_NAME}" | mail -s "Budget Control Backup" admin@example.com
```

#### Agent Command:
```bash
# Use devops agent
Task: Implement automated backup system
- Create comprehensive backup script with encryption
- Set up rotation policy (7 daily, 4 weekly, 12 monthly)
- Configure cron jobs for automation
- Add cloud sync (S3/DigitalOcean Spaces)
- Create restore script
- Add email notifications
- Test full backup and restore cycle
```

---

### Task 4: Enable HTTPS (Let's Encrypt)

**Priority**: High (for production)
**Effort**: 2-4 hours
**Agent**: DevOps Specialist
**Can Run in Parallel**: ✅ Yes (independent)
**Prerequisite**: Domain name pointed to server

#### Subtasks:
1. **DNS Configuration** (30 min)
   - Point domain to server IP
   - Verify DNS propagation

2. **Certbot Installation** (30 min)
   - Install Certbot
   - Configure Apache plugin

3. **SSL Certificate** (30 min)
   - Obtain certificate via Certbot
   - Configure Apache SSL VirtualHost
   - Test HTTPS

4. **Security Hardening** (1 hour)
   - Force HTTPS redirect
   - Configure HSTS header
   - Set up auto-renewal
   - Test SSL configuration (SSL Labs)

#### Implementation Commands:
```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-apache

# Obtain certificate
sudo certbot --apache -d budget.yourdomain.com

# Test auto-renewal
sudo certbot renew --dry-run

# Force HTTPS redirect (Apache)
# Add to VirtualHost:
<VirtualHost *:80>
    ServerName budget.yourdomain.com
    Redirect permanent / https://budget.yourdomain.com/
</VirtualHost>

# Add HSTS header
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"

# Update .env
SESSION_SECURE=true
```

#### Files to Modify:
```
/etc/apache2/sites-available/budget-control-ssl.conf (auto-created by Certbot)
budget-app/public/.htaccess (add HSTS header)
.env (set SESSION_SECURE=true)
```

#### Agent Command:
```bash
# Use devops agent
Task: Enable HTTPS with Let's Encrypt
- Install Certbot with Apache plugin
- Obtain SSL certificate for domain
- Configure Apache SSL VirtualHost
- Force HTTPS redirect
- Add HSTS header
- Set up auto-renewal cron job
- Test SSL configuration (SSL Labs A+ rating)
```

---

## 🎯 Immediate Tasks - Parallel Execution Plan

### Timeline: 1-2 weeks with 4 parallel teams

```
Week 1:
├── Team 1 (Frontend): Logo/Branding (Days 1-2)
├── Team 2 (Backend): Email Notifications (Days 1-3)
├── Team 3 (DevOps): Automated Backups (Days 1-2)
└── Team 4 (DevOps): HTTPS Setup (Day 1)

Week 2:
├── Team 1: Testing & Polish (Day 3)
├── Team 2: Testing & Email Templates (Days 4-5)
├── Team 3: Testing & Cloud Sync (Day 3)
└── Team 4: Security Hardening (Day 2)
```

### Single Developer Timeline: 1 week
```
Day 1: HTTPS Setup (4 hours) → Logo/Branding (4 hours)
Day 2: Automated Backups (6 hours)
Day 3-4: Email Notifications (12 hours)
Day 5: Testing & Integration (8 hours)
```

---

## 📱 Short-term (v1.1) - Q1 2025

**Timeline**: 8-12 weeks
**Parallel Execution**: 4 major workstreams
**Team Size**: 6-8 developers

### Task 1: Mobile App (React Native)

**Priority**: High
**Effort**: 6-8 weeks
**Team**: 2 mobile developers
**Agent**: Mobile App Developer
**Can Run in Parallel**: ✅ Yes (separate codebase)

#### Phase 1: Setup & Architecture (Week 1-2)
```
Subtasks (can run in parallel):
├── Developer 1: Project setup, navigation, authentication
└── Developer 2: API client, state management (Redux/MobX)
```

**Tasks:**
1. Initialize React Native project (Expo or bare workflow)
2. Set up folder structure and navigation (React Navigation)
3. Configure state management (Redux Toolkit)
4. Create API client with authentication
5. Set up environment configuration
6. Configure build system (iOS + Android)

**Files to Create:**
```
mobile/
├── App.js
├── package.json
├── app.json
├── src/
│   ├── navigation/
│   ├── screens/
│   │   ├── Auth/
│   │   ├── Dashboard/
│   │   ├── Transactions/
│   │   ├── Budgets/
│   │   └── Reports/
│   ├── components/
│   ├── services/
│   │   └── api.js
│   ├── store/
│   │   ├── slices/
│   │   └── index.js
│   └── utils/
└── assets/
```

#### Phase 2: Core Features (Week 3-5)
```
Parallel workstreams:
├── Developer 1: Transactions, Accounts, Categories
└── Developer 2: Budgets, Goals, Reports
```

**Features:**
- Dashboard with account overview
- Transaction list with filters
- Add/edit transactions
- Budget tracking
- Goal progress
- Basic reports

#### Phase 3: Advanced Features (Week 6-7)
```
Parallel workstreams:
├── Developer 1: Offline support, sync
└── Developer 2: Push notifications, widgets
```

**Features:**
- Offline mode with sync
- Push notifications
- Home screen widgets
- Face ID / Touch ID
- Receipt camera capture
- Export functionality

#### Phase 4: Polish & Deploy (Week 8)
```
Tasks:
├── Testing (both developers)
├── Performance optimization
├── App Store submission (Developer 1)
└── Google Play submission (Developer 2)
```

#### Backend API Requirements:
```php
// Create REST API endpoints in budget-app/src/Controllers/Api/
ApiController.php
├── /api/v1/auth/login (POST)
├── /api/v1/auth/refresh (POST)
├── /api/v1/transactions (GET, POST)
├── /api/v1/transactions/:id (GET, PUT, DELETE)
├── /api/v1/accounts (GET, POST)
├── /api/v1/budgets (GET, POST)
├── /api/v1/goals (GET, POST)
├── /api/v1/reports/monthly (GET)
└── /api/v1/sync (POST) - for offline sync
```

#### Agent Command:
```bash
# Use mobile app developer agent
Task: Create React Native mobile app for Budget Control
- Set up React Native project with Expo
- Implement authentication with JWT
- Create screens: Dashboard, Transactions, Budgets, Goals, Reports
- Implement offline mode with local storage
- Add push notifications
- Add biometric authentication
- Test on iOS and Android
- Submit to App Store and Google Play
```

---

### Task 2: Multi-currency Enhancements

**Priority**: Medium
**Effort**: 3-4 weeks
**Team**: 2 backend developers
**Agent**: Backend Developer + Finance Expert
**Can Run in Parallel**: ✅ Yes (independent feature)

#### Phase 1: Database & Core (Week 1)
```
Parallel workstreams:
├── Developer 1: Database schema, exchange rate service
└── Developer 2: Currency conversion logic, API integration
```

**Tasks:**
1. Add currency columns to relevant tables
2. Create exchange_rates table
3. Implement exchange rate API integration (e.g., fixer.io, exchangerate-api)
4. Create currency conversion service
5. Add default currency per account

**Database Changes:**
```sql
-- Migration: 006_add_multi_currency.sql

-- Add currency to transactions
ALTER TABLE transactions ADD COLUMN currency TEXT DEFAULT 'CZK';
ALTER TABLE transactions ADD COLUMN exchange_rate REAL DEFAULT 1.0;
ALTER TABLE transactions ADD COLUMN original_amount REAL;
ALTER TABLE transactions ADD COLUMN original_currency TEXT;

-- Add currency to accounts
ALTER TABLE accounts ADD COLUMN currency TEXT DEFAULT 'CZK';

-- Exchange rates table
CREATE TABLE exchange_rates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    from_currency TEXT NOT NULL,
    to_currency TEXT NOT NULL,
    rate REAL NOT NULL,
    fetched_at DATETIME NOT NULL,
    source TEXT, -- 'api', 'manual'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(from_currency, to_currency, fetched_at)
);

CREATE INDEX idx_exchange_rates_currencies ON exchange_rates(from_currency, to_currency);
CREATE INDEX idx_exchange_rates_date ON exchange_rates(fetched_at DESC);

-- User preferred currencies
CREATE TABLE user_currency_preferences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    base_currency TEXT DEFAULT 'CZK',
    display_currencies TEXT, -- JSON array of preferred currencies
    auto_convert INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Service Implementation:**
```php
// src/Services/CurrencyService.php
class CurrencyService {
    private $apiKey;
    private $db;

    public function getExchangeRate($from, $to, $date = null) {
        // Check cache first
        // If not found, fetch from API
        // Store in database
    }

    public function convert($amount, $from, $to, $date = null) {
        $rate = $this->getExchangeRate($from, $to, $date);
        return $amount * $rate;
    }

    public function getSupportedCurrencies() {
        return ['CZK', 'EUR', 'USD', 'GBP', 'PLN', 'CHF', ...];
    }

    public function updateRates() {
        // Fetch latest rates from API
        // Update exchange_rates table
    }
}
```

#### Phase 2: UI Integration (Week 2)
```
Parallel workstreams:
├── Developer 1: Transaction forms, account settings
└── Developer 2: Reports, budget conversion
```

**Tasks:**
1. Add currency selector to transaction forms
2. Add currency selector to account creation
3. Show original currency + converted amount
4. Update reports to handle multiple currencies
5. Add currency preferences to user settings

#### Phase 3: Reporting & Analytics (Week 3)
```
Tasks:
├── Developer 1: Multi-currency reports
└── Developer 2: Exchange gain/loss tracking
```

**Features:**
- Convert all transactions to base currency for reports
- Show currency breakdown in dashboard
- Track exchange rate gains/losses
- Historical exchange rate charts

#### Phase 4: Testing & Optimization (Week 4)
```
Tasks:
├── Developer 1: Unit tests
├── Developer 2: Integration tests
└── Both: Performance optimization
```

#### Agent Command:
```bash
# Use backend developer + finance expert agents
Task: Implement multi-currency support
- Add currency fields to transactions and accounts
- Create exchange_rates table
- Integrate with exchange rate API (fixer.io or similar)
- Implement CurrencyService for conversions
- Update all forms to support currency selection
- Modify reports to convert to base currency
- Add currency preferences to user settings
- Track exchange rate gains/losses
- Test with multiple currencies
```

---

### Task 3: Expense Splitting with Friends

**Priority**: Medium
**Effort**: 4-5 weeks
**Team**: 2-3 developers
**Agent**: Full-stack Developer
**Can Run in Parallel**: ✅ Yes (independent feature)

#### Phase 1: Architecture & Database (Week 1)
```
Tasks:
├── Developer 1: Database schema design
├── Developer 2: User connection system
└── Developer 3: Sharing logic
```

**Database Schema:**
```sql
-- Migration: 007_add_expense_splitting.sql

-- Groups for shared expenses
CREATE TABLE expense_groups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    created_by INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Group members
CREATE TABLE expense_group_members (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    group_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    role TEXT DEFAULT 'member', -- 'admin', 'member'
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(group_id) REFERENCES expense_groups(id) ON DELETE CASCADE,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(group_id, user_id)
);

-- Split expenses
CREATE TABLE split_expenses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    group_id INTEGER NOT NULL,
    transaction_id INTEGER NOT NULL,
    paid_by INTEGER NOT NULL, -- user who paid
    total_amount REAL NOT NULL,
    currency TEXT DEFAULT 'CZK',
    split_type TEXT DEFAULT 'equal', -- 'equal', 'percentage', 'custom', 'shares'
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(group_id) REFERENCES expense_groups(id) ON DELETE CASCADE,
    FOREIGN KEY(transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY(paid_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Individual splits
CREATE TABLE expense_splits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    split_expense_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    amount REAL NOT NULL,
    percentage REAL,
    shares INTEGER,
    settled INTEGER DEFAULT 0,
    settled_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(split_expense_id) REFERENCES split_expenses(id) ON DELETE CASCADE,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Settlement transactions
CREATE TABLE settlements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    split_expense_id INTEGER NOT NULL,
    from_user INTEGER NOT NULL,
    to_user INTEGER NOT NULL,
    amount REAL NOT NULL,
    currency TEXT DEFAULT 'CZK',
    status TEXT DEFAULT 'pending', -- 'pending', 'completed', 'cancelled'
    payment_method TEXT, -- 'cash', 'bank_transfer', 'paypal', etc.
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    settled_at DATETIME,
    FOREIGN KEY(split_expense_id) REFERENCES split_expenses(id) ON DELETE CASCADE,
    FOREIGN KEY(from_user) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(to_user) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_settlements_from_user ON settlements(from_user);
CREATE INDEX idx_settlements_to_user ON settlements(to_user);
CREATE INDEX idx_settlements_status ON settlements(status);
```

#### Phase 2: Core Features (Week 2-3)
```
Parallel workstreams:
├── Developer 1: Group management, invitations
├── Developer 2: Expense splitting logic
└── Developer 3: Settlement tracking
```

**Features:**
1. Create/manage expense groups
2. Invite users via email
3. Add expenses to group
4. Choose split method (equal, percentage, custom amounts, shares)
5. Track who owes whom
6. Mark settlements as complete
7. Expense history per group

**Service Implementation:**
```php
// src/Services/ExpenseSplitService.php
class ExpenseSplitService {
    public function createGroup($name, $memberEmails) {
        // Create group
        // Send invitations
    }

    public function splitExpense($groupId, $transactionId, $splitType, $splits) {
        // Create split_expense record
        // Calculate individual splits
        // Create expense_splits records
        // Send notifications to members
    }

    public function calculateBalance($groupId, $userId) {
        // Calculate total owed by user
        // Calculate total owed to user
        // Return net balance
    }

    public function settleUp($fromUserId, $toUserId, $amount) {
        // Create settlement record
        // Mark relevant expenses as settled
        // Send notification
    }

    public function getGroupSummary($groupId) {
        // Total expenses
        // Per-person breakdown
        // Outstanding balances
    }
}
```

#### Phase 3: UI Development (Week 4)
```
Parallel workstreams:
├── Developer 1: Group management UI
├── Developer 2: Expense split UI
└── Developer 3: Settlement UI
```

**Views to Create:**
```
views/groups/
├── list.php           # List all groups
├── create.php         # Create new group
├── detail.php         # Group details with expenses
└── invite.php         # Invite members

views/split-expenses/
├── add.php            # Add split expense
├── detail.php         # Expense split details
└── settle.php         # Settle balances
```

**JavaScript:**
```javascript
// public/js/expense-splitting.js
class ExpenseSplitUI {
    constructor() {
        this.splitType = 'equal';
        this.members = [];
    }

    addExpense(groupId, amount, description) {
        // Show split options
        // Calculate splits
        // Submit to backend
    }

    calculateSplit(amount, splitType, customData) {
        // Equal: amount / memberCount
        // Percentage: amount * (percentage / 100)
        // Custom: use provided amounts
        // Shares: amount * (shares / totalShares)
    }

    settleBalance(fromUser, toUser, amount) {
        // Record settlement
        // Update balances
    }

    showGroupBalance(groupId) {
        // Fetch balance data
        // Display who owes whom
        // Simplify debts (minimize transactions)
    }
}
```

#### Phase 4: Advanced Features & Testing (Week 5)
```
Tasks:
├── Developer 1: Debt simplification algorithm
├── Developer 2: Email notifications, reminders
└── Developer 3: Testing & bug fixes
```

**Advanced Features:**
- Debt simplification (minimize number of transactions)
- Recurring group expenses
- Email notifications for new expenses
- Payment reminders
- Group expense reports
- Export group summary

#### Agent Command:
```bash
# Use full-stack developer agent
Task: Implement expense splitting with friends
- Create database schema (groups, members, splits, settlements)
- Implement group management system
- Add expense splitting logic (equal, percentage, custom, shares)
- Create settlement tracking system
- Build UI for group management and expense splitting
- Implement debt simplification algorithm
- Add email notifications
- Create group expense reports
- Test with multiple users and scenarios
```

---

### Task 4: Receipt OCR Scanning

**Priority**: Medium
**Effort**: 3-4 weeks
**Team**: 2 developers
**Agent**: Backend Developer + ML Specialist
**Can Run in Parallel**: ✅ Yes (independent feature)

#### Phase 1: OCR Integration (Week 1)
```
Parallel workstreams:
├── Developer 1: Image upload & processing
└── Developer 2: OCR service integration
```

**OCR Service Options:**
1. **Google Cloud Vision API** (recommended)
2. **AWS Textract**
3. **Azure Computer Vision**
4. **Tesseract OCR** (open-source, self-hosted)

**Tasks:**
1. Set up image upload endpoint
2. Integrate OCR service
3. Extract text from receipt
4. Parse receipt data (amount, date, merchant, items)

**Implementation:**
```php
// src/Services/ReceiptOcrService.php
class ReceiptOcrService {
    private $ocrProvider; // 'google', 'aws', 'azure', 'tesseract'

    public function processReceipt($imagePath) {
        // 1. Preprocess image (rotate, enhance)
        // 2. Send to OCR service
        // 3. Extract text
        // 4. Parse receipt data
        // 5. Return structured data
    }

    public function extractReceiptData($ocrText) {
        // Parse total amount (regex patterns)
        // Extract date (various formats)
        // Identify merchant name
        // Extract line items
        // Detect currency

        return [
            'total' => 1234.56,
            'currency' => 'CZK',
            'date' => '2025-11-12',
            'merchant' => 'Tesco',
            'items' => [
                ['name' => 'Milk', 'price' => 25.90],
                ['name' => 'Bread', 'price' => 18.50],
            ],
            'tax' => 123.45,
            'confidence' => 0.92
        ];
    }

    public function preprocessImage($imagePath) {
        // Rotate to correct orientation
        // Enhance contrast
        // Remove noise
        // Crop to receipt area
    }
}
```

**Database Schema:**
```sql
-- Migration: 008_add_receipt_ocr.sql

CREATE TABLE receipt_scans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    transaction_id INTEGER,
    image_path TEXT NOT NULL,
    ocr_text TEXT,
    parsed_data TEXT, -- JSON
    confidence_score REAL,
    status TEXT DEFAULT 'processing', -- 'processing', 'completed', 'failed', 'manual_review'
    error_message TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(transaction_id) REFERENCES transactions(id) ON DELETE SET NULL
);

CREATE INDEX idx_receipt_scans_user ON receipt_scans(user_id);
CREATE INDEX idx_receipt_scans_status ON receipt_scans(status);
```

#### Phase 2: Data Parsing & ML (Week 2)
```
Parallel workstreams:
├── Developer 1: Receipt parsing logic
└── Developer 2: ML model for categorization
```

**Tasks:**
1. Implement regex patterns for common receipt formats
2. Train ML model to categorize expenses from merchant/items
3. Add confidence scoring
4. Implement manual review workflow for low confidence

**Parsing Logic:**
```php
class ReceiptParser {
    public function parseAmount($text) {
        // Patterns for different currencies and formats
        $patterns = [
            '/TOTAL:?\s*(\d+[.,]\d{2})/i',
            '/CELKEM:?\s*(\d+[.,]\d{2})/i',
            '/(\d+[.,]\d{2})\s*Kč/i',
        ];
        // Try each pattern
        // Return best match with confidence
    }

    public function parseDate($text) {
        // Patterns for DD.MM.YYYY, DD/MM/YYYY, etc.
        // Convert to standard format
    }

    public function parseMerchant($text) {
        // Look for business names
        // Use database of known merchants
        // Return best match
    }

    public function categorizeExpense($merchant, $items) {
        // Use ML model or rules
        // Grocery store → Food & Groceries
        // Gas station → Transportation
        // Restaurant → Dining Out
    }
}
```

#### Phase 3: UI Development (Week 3)
```
Parallel workstreams:
├── Developer 1: Camera capture, upload UI
└── Developer 2: Review & edit UI
```

**Features:**
1. Camera capture (mobile)
2. File upload (desktop)
3. Preview scanned data
4. Edit/confirm transaction details
5. Manual review queue for low confidence scans

**Views:**
```
views/receipts/
├── scan.php           # Camera/upload interface
├── review.php         # Review scanned data
├── list.php           # List all scanned receipts
└── manual-review.php  # Queue for manual review
```

**JavaScript:**
```javascript
// public/js/receipt-scanner.js
class ReceiptScanner {
    constructor() {
        this.camera = null;
        this.capturedImage = null;
    }

    async captureReceipt() {
        // Access camera
        // Capture image
        // Upload to server
        // Show processing status
    }

    async uploadReceipt(file) {
        const formData = new FormData();
        formData.append('receipt', file);

        const response = await fetch('/api/receipts/scan', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        this.showReview(result);
    }

    showReview(data) {
        // Display extracted data
        // Allow editing
        // Confirm to create transaction
    }
}
```

#### Phase 4: Integration & Testing (Week 4)
```
Tasks:
├── Developer 1: Mobile integration
├── Developer 2: Testing & optimization
└── Both: Performance tuning
```

**Tasks:**
1. Test with various receipt types
2. Optimize image processing
3. Add batch upload
4. Integrate with mobile app
5. Add receipt storage/retrieval

#### Agent Command:
```bash
# Use backend + ML specialist agents
Task: Implement receipt OCR scanning
- Integrate OCR service (Google Cloud Vision or AWS Textract)
- Create image upload and processing pipeline
- Implement receipt data parsing (amount, date, merchant, items)
- Add ML-based expense categorization
- Create UI for camera capture and file upload
- Build review/edit interface
- Add manual review queue for low confidence
- Test with various receipt formats
- Optimize for performance and accuracy
```

---

## 🎯 v1.1 Parallel Execution Plan

### Timeline: 8-12 weeks with 8 developers

```
Weeks 1-8:
├── Team 1-2 (Mobile): React Native app (8 weeks)
├── Team 3-4 (Backend): Multi-currency (4 weeks) → Receipt OCR (4 weeks)
└── Team 5-7 (Full-stack): Expense Splitting (5 weeks) → Testing (3 weeks)

Week-by-Week:
Week 1-2:
  ├── Mobile: Setup & Architecture
  ├── Currency: Database & Core
  └── Splitting: Architecture & Database

Week 3-4:
  ├── Mobile: Core Features
  ├── Currency: UI Integration
  └── Splitting: Core Features

Week 5-6:
  ├── Mobile: Advanced Features
  ├── Currency: Reporting & Testing
  └── Splitting: UI Development

Week 7-8:
  ├── Mobile: Polish & Deploy
  ├── OCR: Integration & Parsing
  └── Splitting: Testing & Advanced Features

Week 9-12:
  ├── OCR: UI & Testing
  └── Integration Testing & Bug Fixes
```

### Dependencies:
- Mobile app requires REST API endpoints (can be developed in parallel)
- Multi-currency needed before expense splitting (currency conversion in settlements)
- Receipt OCR independent of other features

---

## 🚀 Long-term (v2.0) - Q3 2025

**Timeline**: 16-24 weeks
**Team Size**: 10-15 developers
**Complexity**: High

### Task 1: AI-Powered Financial Advisor

**Priority**: High
**Effort**: 8-10 weeks
**Team**: 3-4 developers + 1 data scientist
**Agent**: AI/ML Specialist + Backend Developer

#### Phase 1: AI Foundation (Week 1-3)
```
Parallel workstreams:
├── Developer 1: LLM integration (OpenAI, Claude)
├── Developer 2: Training data collection
├── Developer 3: Prompt engineering
└── Data Scientist: Model fine-tuning
```

**Features:**
- Integrate with LLM (GPT-4, Claude 3)
- Create financial knowledge base
- Build context-aware prompts
- Implement conversation memory

#### Phase 2: Financial Analysis (Week 4-6)
```
Parallel workstreams:
├── Team A: Spending analysis & insights
├── Team B: Savings recommendations
└── Team C: Debt management advice
```

#### Phase 3: Personalization (Week 7-8)
```
Tasks:
├── User preference learning
├── Adaptive recommendations
└── Multi-language support
```

#### Phase 4: UI & Testing (Week 9-10)
```
Tasks:
├── Chat interface
├── Voice assistant integration
└── Testing & refinement
```

---

### Task 2: Predictive Analytics

**Priority**: High
**Effort**: 6-8 weeks
**Team**: 2-3 data scientists + 2 developers

#### Phase 1: Data Collection & Modeling (Week 1-3)
```
Parallel workstreams:
├── Data Scientist 1: Spending prediction model
├── Data Scientist 2: Income forecasting
└── Developer: Data pipeline
```

#### Phase 2: ML Models (Week 4-6)
```
Models to build:
├── Expense prediction (time series)
├── Category classification (ML)
├── Anomaly detection (outlier detection)
└── Goal achievement probability
```

#### Phase 3: Integration & Visualization (Week 7-8)
```
Tasks:
├── Backend API for predictions
├── Dashboard widgets
└── Alerting system
```

---

### Task 3: Open Banking API Integration

**Priority**: Medium
**Effort**: 8-12 weeks
**Team**: 3-4 backend developers + 1 security expert

#### Phase 1: API Research & Setup (Week 1-2)
```
Tasks:
├── Research Open Banking providers (EU PSD2, UK Open Banking)
├── Register as TPP (Third Party Provider)
├── Set up OAuth2 flows
└── Security audit
```

#### Phase 2: Bank Connection (Week 3-6)
```
Parallel workstreams:
├── Developer 1: Account aggregation
├── Developer 2: Transaction sync
├── Developer 3: Payment initiation
└── Security Expert: Security review
```

#### Phase 3: UI & User Experience (Week 7-10)
```
Tasks:
├── Bank connection flow
├── Account linking UI
├── Real-time sync
└── Error handling & retry logic
```

#### Phase 4: Testing & Compliance (Week 11-12)
```
Tasks:
├── Security testing
├── GDPR compliance
├── PSD2 compliance audit
└── Load testing
```

---

### Task 4: Real-time Collaboration

**Priority**: Low
**Effort**: 6-8 weeks
**Team**: 3 full-stack developers

#### Phase 1: WebSocket Infrastructure (Week 1-2)
```
Tasks:
├── Set up WebSocket server
├── Implement presence system
├── Create collaboration protocol
└── Security & authentication
```

#### Phase 2: Shared Features (Week 3-5)
```
Parallel workstreams:
├── Developer 1: Shared budgets
├── Developer 2: Collaborative goals
└── Developer 3: Family accounts
```

#### Phase 3: Real-time Updates (Week 6-7)
```
Tasks:
├── Live transaction updates
├── Shared expense notifications
├── Collaborative editing
└── Conflict resolution
```

#### Phase 4: UI & Testing (Week 8)
```
Tasks:
├── Real-time UI updates
├── Presence indicators
└── Testing with multiple users
```

---

## 🎯 v2.0 Parallel Execution Plan

### Timeline: 16-24 weeks with 15 developers

```
Quarters 1-2 (16-24 weeks):

Week 1-10: AI Financial Advisor
  ├── Weeks 1-3: AI Foundation (4 devs)
  ├── Weeks 4-6: Financial Analysis (4 devs)
  ├── Weeks 7-8: Personalization (4 devs)
  └── Weeks 9-10: UI & Testing (4 devs)

Week 1-8: Predictive Analytics (in parallel)
  ├── Weeks 1-3: Data & Modeling (3 devs)
  ├── Weeks 4-6: ML Models (3 devs)
  └── Weeks 7-8: Integration (3 devs)

Week 5-16: Open Banking Integration (starts after AI foundation)
  ├── Weeks 5-6: Research & Setup (4 devs)
  ├── Weeks 7-12: Bank Connection (4 devs)
  ├── Weeks 13-16: UI & Testing (4 devs)
  └── Weeks 17-18: Compliance (4 devs)

Week 11-18: Real-time Collaboration (after predictive analytics)
  ├── Weeks 11-12: Infrastructure (3 devs)
  ├── Weeks 13-15: Shared Features (3 devs)
  ├── Weeks 16-17: Real-time Updates (3 devs)
  └── Week 18: Testing (3 devs)

Week 19-24: Integration, Polish, Launch
  └── All teams: Integration testing, bug fixes, documentation
```

### Critical Path:
1. AI Financial Advisor (foundation for recommendations)
2. Predictive Analytics (requires historical data)
3. Open Banking (requires security audit)
4. Real-time Collaboration (builds on existing features)

---

## 📊 Resource Requirements Summary

### Immediate Tasks (1-2 weeks)
- **Developers**: 1-4 (can be done by 1 developer sequentially)
- **Budget**: $500-2,000 (mostly email service, domain, SSL)
- **Infrastructure**: Existing + email provider

### v1.1 (8-12 weeks)
- **Developers**: 6-8
- **Budget**: $20,000-40,000
- **Infrastructure**:
  - Mobile app development tools
  - OCR API subscription (Google/AWS)
  - Additional server capacity

### v2.0 (16-24 weeks)
- **Developers**: 10-15
- **Data Scientists**: 2-3
- **Budget**: $80,000-150,000
- **Infrastructure**:
  - LLM API subscription (OpenAI/Claude)
  - ML training infrastructure
  - WebSocket server
  - Open Banking TPP registration

---

## 🎯 Recommended Execution Strategy

### For Solo Developer:
```
Immediate (2 weeks):
  Day 1-2: HTTPS + Logo
  Day 3-4: Backups
  Day 5-10: Email notifications

v1.1 (20-30 weeks):
  Weeks 1-8: Mobile app
  Weeks 9-12: Multi-currency
  Weeks 13-17: Expense splitting
  Weeks 18-20: Receipt OCR
```

### For Small Team (3-5 devs):
```
Immediate (1 week):
  Parallel execution of all 4 tasks

v1.1 (10-12 weeks):
  2 devs: Mobile app
  2 devs: Multi-currency → Expense splitting
  1 dev: Receipt OCR

v2.0 (18-24 weeks):
  3 devs: AI Advisor + Predictive
  2 devs: Open Banking
```

### For Full Team (10+ devs):
```
All roadmap items executed in parallel
Timeline compressed to 6-12 months
```

---

**Last Updated**: 2025-11-12
**Version**: 1.0
**Status**: Ready for execution
