# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Budget Control is a personal finance management application built with PHP and SQLite. The application features CSV/JSON import, AI-powered categorization, budget tracking, investment portfolio management, and financial analytics.

**Tech Stack:**
- Backend: PHP 8.0+ (no framework, lightweight MVC)
- Database: SQLite 3.x
- Frontend: Vanilla JavaScript with Chart.js and D3.js
- CSS: Tailwind CSS
- Testing: Playwright for E2E tests
- Deployment: Docker with Apache

**Primary Language:** The codebase documentation and UI are in Czech (Česky).

## Common Commands

### Development Server

```bash
# Using PHP built-in server (development only)
cd budget-app
php -S localhost:8000 -t public/

# Using Docker (recommended)
docker-compose up --build
# Access at: http://localhost:8080
docker-compose down
```

### Database Operations

```bash
# Initialize database (auto-created on first run)
cd budget-app
sqlite3 database/budget.db < database/schema.sql
sqlite3 database/budget.db < database/seeds.sql

# Access database directly
sqlite3 budget-app/database/budget.db

# From Docker container
docker-compose exec budget-app sqlite3 /var/www/html/database/budget.db
```

### Testing

```bash
# Run all E2E tests (requires app running on port 8080)
npm test

# Run tests with UI
npm run test:ui

# Run tests in headed mode (visible browser)
npm run test:headed

# Debug mode
npm run test:debug

# Specific test types
npm run test:accessibility   # Accessibility tests
npm run test:api            # API tests
npm run test:performance    # Performance tests
npm run test:mobile         # Mobile browser tests
npm run test:cross-browser  # All browsers

# Generate and view report
npm run report
```

### Frontend Build

```bash
# Build Tailwind CSS (in budget-app directory)
cd budget-app
npm run build:css

# Watch mode for development
npm run build:css:watch
```

### Background Jobs

```bash
# Process bank imports queue
php budget-app/cli/process-bank-imports.php
```

## Architecture

### MVC Structure Without Framework

The application uses a custom lightweight MVC pattern:

**Application.php** - Main application class that:
- Initializes Database, Router, and Config
- Defines all routes in `setupRoutes()` method
- Provides dependency injection for controllers

**Router.php** - Simple pattern-matching router supporting:
- Dynamic parameters (`:id`, `:period`, etc.)
- GET, POST, PUT, DELETE methods
- Controller@method syntax for routing

**Database.php** - PDO wrapper for SQLite:
- Prepared statements for SQL injection protection
- Helper methods: `query()`, `execute()`, `fetchAll()`, `fetchOne()`
- Automatic connection management

### Request Flow

```
public/index.php
  → Application::__construct()
  → Router::dispatch($method, $uri)
  → Controller action
  → Service layer (business logic)
  → Database queries
  → View rendering
```

### Key Directories

```
budget-app/
├── src/
│   ├── Application.php       # App bootstrap and route definitions
│   ├── Router.php            # Request routing
│   ├── Database.php          # Database abstraction
│   ├── Config.php            # Environment config
│   ├── Controllers/          # ~20 controllers
│   ├── Services/             # ~25 service classes (business logic)
│   ├── Middleware/           # Request middleware
│   └── Jobs/                 # Background job classes
├── views/                    # PHP templates (16+ subdirectories)
├── public/                   # Web root
│   ├── index.php            # Entry point
│   └── assets/              # CSS, JS, images
├── database/
│   ├── schema.sql           # 19 tables with indexes
│   └── seeds.sql            # Initial data (9 educational tips)
├── tests/                   # Playwright E2E tests
└── cli/                     # Command-line scripts
```

## Core Components

### Controllers (src/Controllers/)

Controllers follow naming convention `{Resource}Controller`:
- **DashboardController** - Main dashboard with financial overview
- **TransactionController** - Transaction CRUD, bulk actions, recurring detection, splits
- **AccountController** - Account management (checking, savings, investment, loan)
- **BudgetController** - Budget CRUD, alerts, templates, analytics
- **ImportController** - CSV import
- **BankImportController** - JSON bank import with queue processing
- **InvestmentController** - Investment portfolio tracking
- **GoalsController** - Financial goals
- **ReportController** - Financial reports and analytics
- **ApiController** - REST API endpoints (~40 endpoints)
- **OpportunitiesController** - Financial opportunities detection
- **NotificationController** - User notifications
- **CareerController** - Career and income planning

### Services (src/Services/)

Business logic is separated into service classes:
- **CsvImporter** - Parse and import CSV files (supports Czech bank formats)
- **FinancialAnalyzer** - Calculate metrics, net worth, health score, anomalies
- **AiRecommendations** - OpenAI integration for personalized recommendations
- **CategorizationService** - Auto-categorize transactions using rules and ML
- **BudgetAnalyticsService** - Budget performance analysis
- **BudgetAlertService** - Budget threshold alerts
- **AutomationService** - Transaction automation rules
- **GoalService** - Goal tracking and projections
- **CareerService** - Career planning and salary optimization
- **ExcelExporter** / **CsvExporter** - Data export functionality
- **AggregateService** - Aggregate financial data across accounts

### Database Schema (19 Tables)

Core tables:
- `users` - User accounts
- `accounts` - Bank accounts (types: checking, savings, investment, loan)
- `transactions` - All transactions with categories and tags
- `categories` - Expense/income categories
- `merchants` - Merchant data for categorization learning
- `csv_imports` - Import history and metadata

Advanced features:
- `budgets` - Budget definitions and tracking
- `budget_alerts` - Alert configurations and history
- `investments` - Investment portfolio holdings
- `goals` - Financial goals and progress
- `recurring_transactions` - Recurring payment detection
- `transaction_splits` - Split transactions across categories
- `categorization_rules` - Auto-categorization rules
- `automation_rules` - Transaction automation
- `ai_recommendations` - AI-generated insights
- `exchange_rates` - Multi-currency support
- `notifications` - User notification queue

## Adding New Features

### Create a New Route

1. Add route in `src/Application.php::setupRoutes()`:
```php
$this->router->get('/my-feature', 'MyFeatureController@index');
$this->router->post('/my-feature/:id', 'MyFeatureController@update');
```

2. Create controller in `src/Controllers/MyFeatureController.php`:
```php
<?php
namespace BudgetApp\Controllers;

class MyFeatureController extends BaseController {
    public function index() {
        $data = ['items' => []];
        echo $this->render('my-feature/index', $data);
    }
}
```

3. Create view in `views/my-feature/index.php`

### Add a New Service

Create in `src/Services/`:
```php
<?php
namespace BudgetApp\Services;

class MyService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function doSomething() {
        return $this->db->query("SELECT * FROM table");
    }
}
```

Use in controller:
```php
$service = new \BudgetApp\Services\MyService($this->app->getDatabase());
$result = $service->doSomething();
```

### Add Database Table

1. Add SQL to `database/schema.sql`
2. Create migration logic if needed (manual for now)
3. Update seeds if needed in `database/seeds.sql`

## CSV Import Format

The CsvImporter service supports multiple date formats:
- Czech: `DD.MM.YYYY` (e.g., "31.12.2024")
- ISO: `YYYY-MM-DD` (e.g., "2024-12-31")
- US: `MM/DD/YYYY` (e.g., "12/31/2024")

Expected CSV columns:
- Date
- Amount (supports both comma and dot as decimal separator)
- Description/Merchant
- Account (optional)
- Category (optional, auto-categorized if missing)

## Security Considerations

- **Authentication**: Session-based, check for `$_SESSION['user_id']`
- **Password Hashing**: Uses PHP's `password_hash()` / `password_verify()`
- **SQL Injection**: All queries use PDO prepared statements
- **XSS Protection**: All output uses `htmlspecialchars()`
- **File Upload**: CSV/JSON only, max 10MB, stored in `uploads/` directory
- **CSRF Protection**: Needs implementation for production

## API Endpoints

The ApiController provides ~40 REST endpoints under `/api/*`:
- `/api/dashboard/summary` - Dashboard metrics
- `/api/transactions` - CRUD operations
- `/api/budgets/alerts` - Budget alerts
- `/api/analytics/*` - Various analytics endpoints
- `/api/recommendations` - AI recommendations
- `/api/goals/*` - Goal tracking
- `/api/investments/*` - Investment portfolio

Most return JSON and expect `Content-Type: application/json`.

## Environment Configuration

The `.env` file (based on `.env.example`) controls:
- `APP_NAME` - Application name
- `APP_DEBUG` - Debug mode (true/false)
- `DATABASE_PATH` - SQLite file path
- `CURRENCY` - Default currency (CZK)
- `TIMEZONE` - Application timezone (Europe/Prague)
- `OPENAI_API_KEY` - Optional, for AI recommendations

## Playwright Testing Structure

Tests are organized by concern:
- `budget-app.spec.js` - Core application flows
- `functionality.spec.js` - Feature testing
- `accessibility.spec.js` - a11y testing with @axe-core/playwright
- `api.spec.js` - API endpoint testing
- `performance.spec.js` - Performance benchmarks
- `settings.spec.js` - Settings and configuration

Global setup/teardown in `tests/global-setup.js` and `tests/global-teardown.js`.

## Code Style

- **PHP**: PSR-4 autoloading, `BudgetApp\` namespace
- **Indentation**: Spaces (check existing files for consistency)
- **Comments**: Czech is acceptable for business logic, English for technical docs
- **SQL**: Use prepared statements, never string concatenation
- **Views**: Plain PHP templates, avoid complex logic

## Common Patterns

**Rendering Views:**
```php
echo $this->render('view-name', ['data' => $value]);
```

**Database Queries:**
```php
$db = $this->app->getDatabase();
$results = $db->query("SELECT * FROM table WHERE id = ?", [$id]);
```

**JSON Response:**
```php
header('Content-Type: application/json');
echo json_encode(['success' => true, 'data' => $data]);
```

**Session Check:**
```php
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}
```
