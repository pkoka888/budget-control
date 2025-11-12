# 📁 Budget Control - Project Structure

Complete documentation of the project file organization and architecture.

## Overview

Budget Control follows a Model-View-Controller (MVC) architecture with clear separation of concerns. The application is organized into logical modules with standardized naming conventions.

## Directory Tree

```
budget-control/
├── 📂 budget-app/              # Main application directory
│   ├── 📂 public/              # Web-accessible files (DocumentRoot)
│   │   ├── index.php          # Application entry point (237 lines)
│   │   ├── .htaccess          # Apache configuration (77 lines)
│   │   ├── health.php         # Health check endpoint
│   │   └── 📂 js/             # Frontend JavaScript controllers
│   │       ├── automation.js          # (672 lines) - Automation rules UI
│   │       ├── budget-templates.js    # (450 lines) - Budget template management
│   │       ├── goals.js               # (420 lines) - Goal tracking UI
│   │       ├── import.js              # (350 lines) - CSV/JSON import UI
│   │       ├── investments.js         # (626 lines) - Portfolio management
│   │       ├── opportunities.js       # (560 lines) - Job/course discovery
│   │       ├── recurring-transactions.js # (450 lines) - Recurring txn UI
│   │       ├── reports.js             # (396 lines) - Report generation
│   │       ├── scenario.js            # (660 lines) - Financial scenarios
│   │       ├── splits.js              # (380 lines) - Transaction splitting
│   │       └── tips.js                # (220 lines) - Financial tips UI
│   │
│   ├── 📂 src/                 # PHP source code
│   │   ├── Application.php     # (367 lines) - Main app class, routing
│   │   ├── Config.php          # (109 lines) - Configuration loader
│   │   ├── Database.php        # (120 lines) - PDO wrapper, query builder
│   │   ├── Router.php          # (65 lines) - URL routing engine
│   │   │
│   │   ├── 📂 Controllers/     # HTTP request handlers (32 files)
│   │   │   ├── BaseController.php           # Base controller with auth
│   │   │   ├── AccountController.php        # Account CRUD
│   │   │   ├── TransactionController.php    # Transaction management
│   │   │   ├── BudgetController.php         # Budget operations
│   │   │   ├── GoalController.php           # Goal tracking
│   │   │   ├── InvestmentController.php     # Portfolio management
│   │   │   ├── ReportController.php         # Analytics & reports
│   │   │   ├── ImportController.php         # CSV imports
│   │   │   ├── BankImportController.php     # Bank JSON imports
│   │   │   ├── OpportunitiesController.php  # Job/course discovery
│   │   │   ├── ScenarioPlanningController.php # Financial modeling
│   │   │   ├── AutomationController.php     # Automation rules
│   │   │   ├── AuthController.php           # Login, register, 2FA
│   │   │   ├── DashboardController.php      # Main dashboard
│   │   │   ├── NotificationController.php   # Alerts & notifications
│   │   │   ├── SettingsController.php       # User preferences
│   │   │   ├── TipsController.php           # Financial tips
│   │   │   ├── RecurringTransactionController.php
│   │   │   ├── BudgetTemplateController.php
│   │   │   └── ... (additional controllers)
│   │   │
│   │   ├── 📂 Services/        # Business logic layer
│   │   │   ├── TransactionService.php       # Transaction business logic
│   │   │   ├── BudgetService.php            # Budget calculations
│   │   │   ├── GoalService.php              # Goal progress tracking
│   │   │   ├── InvestmentService.php        # Portfolio calculations
│   │   │   ├── OpportunitiesService.php     # External API integrations
│   │   │   ├── ReportService.php            # Report generation
│   │   │   ├── NotificationService.php      # Notification delivery
│   │   │   ├── AIService.php                # AI recommendations
│   │   │   └── CsvImportService.php         # CSV parsing
│   │   │
│   │   ├── 📂 Middleware/      # Request/response filters
│   │   │   ├── AuthMiddleware.php           # Authentication check
│   │   │   ├── CsrfMiddleware.php           # CSRF protection
│   │   │   └── RateLimitMiddleware.php      # Rate limiting
│   │   │
│   │   └── 📂 Jobs/            # Background job handlers
│   │       ├── BankImportJob.php            # Async bank import
│   │       ├── NotificationJob.php          # Scheduled notifications
│   │       └── ReportGenerationJob.php      # Heavy report generation
│   │
│   ├── 📂 views/               # HTML templates (24 files, 15 dirs)
│   │   ├── layout.php          # Main layout template
│   │   ├── 404.php             # Not found page
│   │   │
│   │   ├── 📂 auth/            # Authentication views
│   │   │   ├── login.php
│   │   │   ├── register.php
│   │   │   ├── forgot-password.php
│   │   │   ├── reset-password.php
│   │   │   ├── email-verification.php
│   │   │   └── email-verified.php
│   │   │
│   │   ├── 📂 transactions/    # Transaction views
│   │   │   ├── recurring.php
│   │   │   └── splits.php
│   │   │
│   │   ├── 📂 budgets/         # Budget views
│   │   │   └── templates.php
│   │   │
│   │   ├── 📂 goals/           # Goal tracking views
│   │   │   ├── dashboard.php
│   │   │   └── list.php
│   │   │
│   │   ├── 📂 investments/     # Investment views
│   │   │   └── portfolio.php
│   │   │
│   │   ├── 📂 reports/         # Report views
│   │   │   ├── monthly.php
│   │   │   ├── yearly.php
│   │   │   ├── analytics.php
│   │   │   └── net-worth.php
│   │   │
│   │   ├── 📂 import/          # Import views
│   │   │   ├── index.php
│   │   │   └── bank-json.php   # (416 lines) Czech bank imports
│   │   │
│   │   ├── 📂 opportunities/   # Phase 3 opportunities
│   │   │   └── list.php
│   │   │
│   │   ├── 📂 scenario/        # Financial planning
│   │   │   └── planning.php
│   │   │
│   │   ├── 📂 automation/      # Automation dashboard
│   │   │   └── dashboard.php
│   │   │
│   │   ├── 📂 settings/        # User settings
│   │   │   ├── profile.php
│   │   │   └── two-factor.php
│   │   │
│   │   ├── 📂 tips/            # Financial tips
│   │   │   └── list.php
│   │   │
│   │   └── 📂 guides/          # Help & documentation
│   │
│   ├── 📂 database/            # Database files
│   │   ├── init.php            # (319 lines) Database initialization
│   │   ├── migrate.php         # (105 lines) Migration runner
│   │   ├── schema.sql          # (762 lines) Base schema - 44 tables
│   │   ├── budget.db           # (972 KB) SQLite database file
│   │   └── 📂 migrations/      # Migration files
│   │       ├── 002_add_2fa_email_verification.sql    # (72 lines)
│   │       ├── 003_add_phase3_opportunities.sql      # (154 lines)
│   │       └── 004_add_performance_indexes.sql       # (139 lines)
│   │
│   ├── 📂 uploads/             # User uploaded files
│   │   └── 📂 csv/             # CSV import staging
│   │
│   ├── composer.json           # PHP dependency definitions
│   └── phpstan.neon            # Static analysis configuration
│
├── 📂 tests/                   # E2E test suites
│   ├── password-reset.spec.js         # (380 lines) - Password reset flow
│   ├── phase2-features.spec.js        # (650 lines) - Phase 2 features
│   ├── phase3-reports.spec.js         # (509 lines) - Reports & analytics
│   ├── phase3-opportunities.spec.js   # (612 lines) - Opportunities
│   ├── phase3-scenario.spec.js        # (490 lines) - Scenario planning
│   └── phase3-complete.spec.js        # (537 lines) - Automation & investments
│
├── 📂 user-data/               # User data storage
│   └── 📂 bank-json/           # Bank JSON import files
│
├── Dockerfile                  # Docker container definition
├── docker-compose.yml          # Docker orchestration
├── .dockerignore               # Docker build exclusions
├── .env.example                # Environment configuration template
├── README.md                   # Main documentation
├── DEPLOYMENT.md               # Deployment guide
├── PROJECT_STRUCTURE.md        # This file
└── .gitignore                  # Git exclusions

## File Count Summary

- **PHP Files**: 99 files
  - Controllers: 32
  - Views: 24
  - Services: 12
  - Models/Core: 15
  - Other: 16

- **JavaScript Files**: 11 files (frontend controllers)

- **Test Files**: 6 Playwright specs (2,148 lines, ~240 tests)

- **Database Files**:
  - 1 schema file (762 lines)
  - 3 migration files (365 lines total)
  - 1 database file (972 KB)

- **Documentation**: 3 markdown files (README, DEPLOYMENT, PROJECT_STRUCTURE)

- **Configuration**: 5 files (Dockerfile, docker-compose, .htaccess, etc.)

## Code Organization Principles

### 1. MVC Architecture

```
Request Flow:
Browser → index.php → Router → Controller → Service → Database
                                     ↓
                                   View → HTML Response
```

### 2. Naming Conventions

- **Controllers**: `{Feature}Controller.php` (e.g., `TransactionController.php`)
- **Services**: `{Feature}Service.php` (e.g., `TransactionService.php`)
- **Views**: `{feature}/{action}.php` (e.g., `transactions/list.php`)
- **JavaScript**: `{feature}.js` (e.g., `transactions.js`)
- **Database Tables**: `snake_case` (e.g., `recurring_transactions`)

### 3. File Responsibilities

#### Controllers (`src/Controllers/`)
- Handle HTTP requests
- Validate input
- Call services for business logic
- Render views or return JSON
- Handle authentication/authorization

#### Services (`src/Services/`)
- Business logic
- Data transformations
- External API calls
- Complex calculations
- Reusable operations

#### Views (`views/`)
- HTML templates
- PHP for dynamic content
- Minimal logic (display only)
- Include JavaScript for interactivity

#### Middleware (`src/Middleware/`)
- Request preprocessing
- Authentication checks
- CSRF validation
- Rate limiting

### 4. Database Layer

#### Schema (`database/schema.sql`)
```sql
-- Complete base structure
-- 44 core tables
-- 78 base indexes
-- Foreign key relationships
```

#### Migrations (`database/migrations/*.sql`)
```sql
-- Versioned schema changes
-- 002_* - Phase 2 features
-- 003_* - Phase 3 features
-- 004_* - Performance optimizations
```

## Key Architectural Patterns

### 1. Front Controller Pattern

All requests go through `public/index.php`:

```php
// Auto-routing based on URL
GET  /transactions      → TransactionController@list()
POST /transactions      → TransactionController@create()
GET  /transactions/:id  → TransactionController@show($id)
```

### 2. Service Layer Pattern

Controllers delegate business logic to services:

```php
// Controller (thin)
public function create() {
    $data = $_POST;
    $this->transactionService->create($data);
}

// Service (fat)
public function create($data) {
    // Validation
    // Business rules
    // Database operations
    // Notifications
}
```

### 3. Repository Pattern

Database abstraction through PDO wrapper:

```php
// Direct queries
$db->query("SELECT * FROM transactions WHERE user_id = ?", [$userId]);

// Query builder (planned)
$db->table('transactions')->where('user_id', $userId)->get();
```

### 4. View Template Pattern

Layout inheritance:

```php
// views/layout.php - Main layout
// views/transactions/list.php - Specific page
// Automatically wrapped by Application::render()
```

## Module Breakdown

### Core Modules

#### 1. Authentication (`auth/`)
- **Files**: 7 views, 1 controller
- **Features**: Login, register, password reset, 2FA, email verification
- **Security**: bcrypt passwords, CSRF tokens, session management

#### 2. Transactions (`transactions/`)
- **Files**: 3 controllers, 3 views, 2 JS files
- **Features**: CRUD, splits, recurring, CSV import, categorization
- **Database**: `transactions`, `transaction_splits`, `recurring_transactions`

#### 3. Budgets (`budgets/`)
- **Files**: 2 controllers, 2 views, 1 JS file
- **Features**: Budget creation, templates, alerts, tracking
- **Database**: `budgets`, `budget_templates`, `budget_alerts`

#### 4. Goals (`goals/`)
- **Files**: 1 controller, 2 views, 1 JS file
- **Features**: Goal creation, milestones, progress tracking
- **Database**: `goals`, `goal_milestones`, `goal_progress_history`

#### 5. Investments (`investments/`)
- **Files**: 1 controller, 1 view, 1 JS file
- **Features**: Portfolio tracking, price updates, performance analysis
- **Database**: `investments`, `investment_accounts`, `investment_transactions`

### Phase 3 Modules

#### 6. Opportunities (`opportunities/`)
- **Files**: 1 controller, 1 view, 1 JS file (560 lines)
- **Features**: Job discovery, course recommendations, career planning
- **Database**: `opportunity_interactions`, `saved_opportunities`
- **APIs**: External job market APIs

#### 7. Scenario Planning (`scenario/`)
- **Files**: 1 controller, 1 view, 1 JS file (660 lines)
- **Features**: Financial modeling, what-if analysis, projections
- **Database**: `scenario_plans`
- **Logic**: Complex financial calculations

#### 8. Automation (`automation/`)
- **Files**: 1 controller, 1 view, 1 JS file (672 lines)
- **Features**: Auto-categorization, rule engine, scheduled actions
- **Database**: `automated_actions`, `categorization_rules`
- **Jobs**: Background processing

## Database Architecture

### Core Tables (44)

#### User Management (7 tables)
- `users` - User accounts
- `password_resets` - Password reset tokens
- `user_settings` - User preferences
- `email_verification_tokens` - Email verification
- `two_factor_sessions` - 2FA sessions
- `two_factor_backup_codes` - 2FA backup codes
- `two_factor_audit_log` - 2FA security log

#### Financial Core (12 tables)
- `accounts` - Bank accounts, cash, credit cards
- `transactions` - All financial transactions
- `transaction_splits` - Split transactions
- `recurring_transactions` - Auto-repeating transactions
- `categories` - Transaction categories
- `budgets` - Budget definitions
- `budget_templates` - Budget templates
- `budget_alerts` - Budget notifications
- `goals` - Financial goals
- `goal_milestones` - Goal checkpoints
- `goal_progress_history` - Goal tracking
- `merchants` - Transaction merchants

#### Investments (4 tables)
- `investments` - Investment holdings
- `investment_accounts` - Investment accounts
- `investment_transactions` - Buy/sell transactions
- `investment_prices` - Price history

#### Intelligence (10 tables)
- `ai_recommendations` - AI-generated advice
- `ai_insight_panels` - Dashboard insights
- `tips` - Financial tips
- `tip_bookmarks` - Saved tips
- `notifications` - User notifications
- `automated_actions` - Automation rules
- `categorization_rules` - Auto-categorization
- `llm_cache` - LLM response cache
- `llm_rate_limits` - API rate limiting
- `performance_metrics` - App performance tracking

#### Data Import (4 tables)
- `csv_imports` - CSV import tracking
- `bank_import_jobs` - Bank JSON job queue
- `job_market_feeds` - Job opportunity cache
- `job_opportunities` - Discovered jobs

#### Security & Audit (3 tables)
- `security_audit_log` - Security events
- `api_keys` - API authentication
- `api_rate_limits` - API usage tracking

#### Czech-Specific (2 tables)
- `czech_benefits` - Government benefits
- `user_benefit_applications` - Benefit tracking

#### Misc (2 tables)
- `usability_test_sessions` - UX testing
- `user_connections` - Social features

### Phase 3 Tables (3)

- `opportunity_interactions` - User engagement with opportunities
- `saved_opportunities` - Bookmarked jobs/courses
- `scenario_plans` - Saved financial scenarios

### System Tables (2)

- `schema_migrations` - Migration tracking
- `sqlite_sequence` - SQLite auto-increment

## Performance Optimizations

### Database Indexes (169 total)

#### Base Indexes (78)
- Single-column indexes on foreign keys
- Unique indexes on tokens, emails
- Indexes on frequently queried columns (date, user_id, etc.)

#### Composite Indexes (42 - from migration 004)
```sql
-- Examples
idx_transactions_user_date (user_id, date)
idx_budgets_user_active (user_id, is_active)
idx_goals_user_status (user_id, status)
```

#### Index Categories
- **User isolation**: user_id + other columns (20 indexes)
- **Date ranges**: date + other columns (8 indexes)
- **Status filtering**: is_active, status + other (12 indexes)
- **Foreign keys**: All FK columns (51 indexes)

### Code Optimizations

- **OPcache**: Bytecode caching for PHP
- **Prepared statements**: All queries use PDO prepared statements
- **Query optimization**: Composite indexes for common queries
- **Lazy loading**: Views load data only when needed
- **Asset caching**: Browser caching for static files

## Testing Architecture

### E2E Tests (6 suites, ~240 tests)

```
tests/
├── password-reset.spec.js       # Auth flows
├── phase2-features.spec.js      # Core features
├── phase3-reports.spec.js       # Analytics (60+ tests)
├── phase3-opportunities.spec.js # Opportunities (70+ tests)
├── phase3-scenario.spec.js      # Scenarios (50+ tests)
└── phase3-complete.spec.js      # Automation (60+ tests)
```

### Test Coverage
- ✅ Authentication & authorization
- ✅ Transaction CRUD operations
- ✅ Budget management
- ✅ Goal tracking
- ✅ Investment portfolio
- ✅ Reports & analytics
- ✅ Data import (CSV, Bank JSON)
- ✅ Opportunities discovery
- ✅ Scenario planning
- ✅ Automation rules
- ✅ Responsive design
- ✅ Accessibility (WCAG)
- ✅ Error handling

## Development Workflow

### Local Development

```bash
# 1. Clone repository
git clone https://github.com/yourusername/budget-control.git
cd budget-control

# 2. Set up database
php budget-app/database/init.php

# 3. Install dependencies (optional)
cd budget-app
composer install

# 4. Run with PHP built-in server (development only)
cd public
php -S localhost:8000

# 5. Or use Docker
docker-compose up -d
```

### Adding New Features

```bash
# 1. Create feature branch
git checkout -b feature/new-feature

# 2. Add controller
touch budget-app/src/Controllers/NewFeatureController.php

# 3. Add service
touch budget-app/src/Services/NewFeatureService.php

# 4. Add view
touch budget-app/views/new-feature/index.php

# 5. Add JS controller (if needed)
touch budget-app/public/js/new-feature.js

# 6. Add routes in Application.php
# $this->router->get('/new-feature', 'NewFeatureController@index');

# 7. Add migration (if database changes)
touch budget-app/database/migrations/005_add_new_feature.sql

# 8. Add tests
touch tests/new-feature.spec.js

# 9. Test and commit
git add .
git commit -m "Add new feature"
git push origin feature/new-feature
```

## Security Considerations

### File Permissions

```bash
# Recommended permissions
chmod 755 budget-app/public/        # Web accessible
chmod 750 budget-app/src/           # PHP code
chmod 750 budget-app/views/         # Templates
chmod 770 budget-app/database/      # Database dir (writable)
chmod 660 budget-app/database/*.db  # Database files
chmod 770 budget-app/uploads/       # Upload dir (writable)
```

### Protected Files

- `.env` - Never commit (use `.env.example`)
- `*.db` - Database files (in `.gitignore`)
- `uploads/*` - User files (in `.gitignore`)
- `composer.json` - Blocked by `.htaccess`

### Security Headers (in `.htaccess`)

- `X-Frame-Options: SAMEORIGIN`
- `X-XSS-Protection: 1; mode=block`
- `X-Content-Type-Options: nosniff`
- `Referrer-Policy: strict-origin-when-cross-origin`

## Future Enhancements

### Planned Reorganization (v2.0)

1. **API Layer**: Separate REST API endpoints
2. **Frontend Separation**: Migrate to React/Vue SPA
3. **Microservices**: Split into independent services
4. **Message Queue**: RabbitMQ/Redis for jobs
5. **Caching Layer**: Redis for session/cache
6. **Multi-tenancy**: Support multiple organizations

### Scalability Considerations

- **Database**: Migrate from SQLite to PostgreSQL/MySQL
- **File Storage**: Use S3/CDN for uploads
- **Load Balancing**: Multiple app servers
- **Caching**: Redis for sessions and data
- **Search**: Elasticsearch for transactions
- **Monitoring**: Prometheus + Grafana

---

**Last Updated**: 2025-11-12
**Version**: 1.0.0
**Total Lines of Code**: ~15,000+ (PHP + JS)
