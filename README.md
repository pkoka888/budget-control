# 💰 Budget Control

A comprehensive personal financial management application built with PHP, SQLite, and modern JavaScript. Track expenses, manage budgets, plan for the future, and discover opportunities to grow your wealth.

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-blue.svg)
![Database](https://img.shields.io/badge/database-SQLite-green.svg)

## ✨ Features

### Core Financial Management
- **🏦 Multi-Account Management** - Track multiple bank accounts, cash, and credit cards
- **💸 Transaction Tracking** - Record income, expenses, and transfers with categories
- **📊 Budget Management** - Create monthly/yearly budgets with alerts and templates
- **🎯 Financial Goals** - Set and track savings goals with milestones
- **🔄 Recurring Transactions** - Automate regular income and expenses
- **💰 Net Worth Tracking** - Monitor your financial growth over time

### Advanced Features (Phase 2)
- **📈 Investment Portfolio** - Track stocks, bonds, mutual funds, and crypto
- **📑 CSV & Bank JSON Import** - Import transactions from Czech banks
- **📉 Reports & Analytics** - Monthly/yearly reports with charts
- **🔔 Smart Notifications** - Budget alerts, bill reminders, goal milestones
- **🔐 Security** - 2FA, email verification, audit logging

### Intelligent Features (Phase 3)
- **💼 Job Opportunities** - Discover high-paying jobs and career paths
- **🎓 Learning Paths** - Find courses to increase your earning potential
- **🎯 Scenario Planning** - Model financial decisions before making them
- **🤖 Automation Rules** - Auto-categorize and process transactions
- **🧠 AI Insights** - Get personalized financial recommendations

## 🚀 Quick Start

### Prerequisites
- **PHP 8.0+** with extensions: `pdo_sqlite`, `sqlite3`, `mbstring`, `json`
- **Apache 2.4+** or **Nginx**
- **SQLite 3.x**
- **Composer** (optional, for dependencies)

### Installation

#### Option 1: Docker (Recommended)

```bash
# Clone the repository
git clone https://github.com/yourusername/budget-control.git
cd budget-control

# Start the application
docker-compose up -d

# Initialize database
docker exec budget-control-app php /var/www/html/database/init.php

# Access the application
open http://localhost:8080
```

#### Option 2: Manual Installation

```bash
# Clone the repository
git clone https://github.com/yourusername/budget-control.git
cd budget-control

# Install dependencies (optional)
cd budget-app
composer install --no-dev

# Initialize database
php database/init.php

# Configure web server to point to budget-app/public/
# Example Apache VirtualHost:
<VirtualHost *:80>
    DocumentRoot "/path/to/budget-control/budget-app/public"
    <Directory "/path/to/budget-control/budget-app/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

# Restart web server
sudo systemctl restart apache2
```

### First Run

1. Navigate to your application URL
2. You'll see a setup screen if database doesn't exist
3. Run: `php budget-app/database/init.php`
4. Refresh the page
5. Register a new account
6. Start tracking your finances!

## 📁 Project Structure

```
budget-control/
├── budget-app/
│   ├── public/               # Web root (point your server here)
│   │   ├── index.php        # Application entry point
│   │   ├── .htaccess        # Apache configuration
│   │   └── js/              # JavaScript controllers (11 files)
│   ├── src/
│   │   ├── Application.php  # Main application class
│   │   ├── Config.php       # Configuration management
│   │   ├── Database.php     # Database abstraction
│   │   ├── Router.php       # URL routing
│   │   ├── Controllers/     # 32 PHP controllers
│   │   ├── Services/        # Business logic services
│   │   ├── Middleware/      # Request middleware
│   │   └── Jobs/            # Background job handlers
│   ├── views/               # 24 view templates (15 directories)
│   ├── database/
│   │   ├── init.php         # Database initialization script
│   │   ├── migrate.php      # Migration runner
│   │   ├── schema.sql       # Base schema (44 tables)
│   │   └── migrations/      # Migration files (3 files)
│   ├── composer.json        # PHP dependencies
│   └── phpstan.neon         # Static analysis config
├── tests/                   # E2E tests (6 Playwright specs)
├── Dockerfile               # Docker container configuration
├── docker-compose.yml       # Docker orchestration
├── .env.example             # Environment configuration template
└── README.md                # This file

## Database Statistics
- 59 tables (54 core + 3 Phase 3 + 2 system)
- 169 indexes (optimized for performance)
- 3 applied migrations
- ~972 KB initial size
```

## 🗄️ Database Schema

### Core Tables (54)
- **Users & Auth**: `users`, `password_resets`, `user_settings`, `email_verification_tokens`, `two_factor_*`
- **Accounts**: `accounts`, `transactions`, `transaction_splits`, `recurring_transactions`
- **Budgeting**: `budgets`, `budget_templates`, `budget_alerts`, `categories`
- **Goals**: `goals`, `goal_milestones`, `goal_progress_history`
- **Investments**: `investments`, `investment_accounts`, `investment_transactions`, `investment_prices`
- **Data Import**: `csv_imports`, `bank_import_jobs`, `categorization_rules`
- **Intelligence**: `ai_recommendations`, `tips`, `notifications`, `automated_actions`
- **Tracking**: `security_audit_log`, `performance_metrics`, `llm_cache`

### Phase 3 Tables (3)
- `opportunity_interactions` - Track user engagement with opportunities
- `saved_opportunities` - Bookmarked jobs, courses, events
- `scenario_plans` - Saved financial scenarios

## 🛠️ Technology Stack

### Backend
- **PHP 8.4** - Modern PHP with type safety
- **SQLite** - Lightweight, serverless database
- **PDO** - Secure database access layer
- **PSR-4 Autoloading** - Modern class loading

### Frontend
- **Vanilla JavaScript (ES6+)** - No framework dependencies
- **Chart.js** - Beautiful data visualizations
- **Tailwind CSS** - Utility-first styling (via CDN)
- **Responsive Design** - Mobile-first approach

### DevOps
- **Docker** - Containerized deployment
- **Apache** - Production web server
- **Playwright** - E2E testing (240+ tests)
- **PHPStan** - Static analysis

## 🧪 Testing

```bash
# Run E2E tests (requires Playwright)
npm install
npx playwright test

# Run specific test suite
npx playwright test tests/phase3-reports.spec.js

# Run with UI
npx playwright test --ui

# Static analysis
vendor/bin/phpstan analyse

# Database integrity check
php budget-app/database/init.php
```

## 🔧 Configuration

### Environment Variables

Copy `.env.example` to `.env` and customize:

```env
APP_NAME="Budget Control"
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Europe/Prague
APP_CURRENCY=CZK

DB_TYPE=sqlite
DB_PATH=/var/www/html/database/budget.db

FEATURE_2FA_ENABLED=true
FEATURE_AI_INSIGHTS=false
```

### PHP Configuration

Recommended `php.ini` settings:

```ini
upload_max_filesize = 10M
post_max_size = 10M
memory_limit = 128M
max_execution_time = 30
date.timezone = Europe/Prague

; OPcache (for production)
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 10000
```

## 📊 Performance

- **Page Load**: < 200ms (with OPcache)
- **Database Queries**: Optimized with 169 indexes
- **Memory Usage**: ~10MB per request
- **Concurrent Users**: 100+ (depends on hardware)
- **Database Size**: Scales efficiently (< 10MB for 10K transactions)

## 🔐 Security Features

- **Password Hashing**: bcrypt with cost factor 12
- **CSRF Protection**: Token validation on all state changes
- **SQL Injection**: Prepared statements everywhere
- **XSS Prevention**: Output escaping with htmlspecialchars
- **Session Security**: HTTP-only, secure cookies
- **2FA Support**: TOTP-based two-factor authentication
- **Audit Logging**: Track all security-relevant events
- **Rate Limiting**: API request throttling

## 📱 Browser Support

- ✅ Chrome/Edge 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

## 🌍 Localization

Currently supports:
- 🇨🇿 **Czech** (primary) - Full support for Czech banks and benefits
- 🇬🇧 **English** - Interface translations

Czech-specific features:
- Bank JSON import (Česká spořitelna, Fio, ČSOB, Komerční banka)
- Czech benefits tracking
- CZK currency formatting

## 🚢 Deployment

### Production Checklist

- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Enable OPcache in `php.ini`
- [ ] Set up regular database backups
- [ ] Configure HTTPS with valid SSL certificate
- [ ] Set `session.cookie_secure=true`
- [ ] Review and set file upload limits
- [ ] Configure email settings for notifications
- [ ] Set up monitoring (error logs, performance)
- [ ] Test disaster recovery procedures

### Docker Production Deployment

```bash
# Build production image
docker build -t budget-control:latest .

# Run with production settings
docker-compose -f docker-compose.yml up -d

# Backup database
docker exec budget-control-app cp /var/www/html/database/budget.db /backups/

# View logs
docker logs -f budget-control-app
```

### Traditional Server Deployment

```bash
# Update application
git pull origin main

# Update dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php budget-app/database/migrate.php

# Clear OPcache
sudo systemctl reload php8.4-fpm

# Test
curl https://yourdomain.com/health
```

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Setup

```bash
# Clone your fork
git clone https://github.com/yourusername/budget-control.git
cd budget-control

# Install development dependencies
composer install
npm install

# Run tests
npx playwright test

# Static analysis
vendor/bin/phpstan analyse
```

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- **Chart.js** - Data visualization
- **Tailwind CSS** - Styling framework
- **Playwright** - E2E testing
- **PHPSpreadsheet** - Excel export
- **TCPDF** - PDF generation

## 📞 Support

- 📧 Email: support@budgetcontrol.local
- 🐛 Issues: [GitHub Issues](https://github.com/yourusername/budget-control/issues)
- 💬 Discussions: [GitHub Discussions](https://github.com/yourusername/budget-control/discussions)

## 🗺️ Roadmap

### v1.1 (Q1 2025)
- [ ] Mobile app (React Native)
- [ ] Multi-currency support enhancement
- [ ] Expense splitting with friends
- [ ] Receipt OCR scanning

### v1.2 (Q2 2025)
- [ ] Bank API direct connection
- [ ] Investment performance analysis
- [ ] Tax report generation
- [ ] Shared family budgets

### v2.0 (Q3 2025)
- [ ] AI-powered financial advisor
- [ ] Predictive analytics
- [ ] Cryptocurrency integration
- [ ] Open Banking API support

---

**Made with ❤️ for better financial health**

*Last Updated: 2025-11-12 | Version: 1.0.0*
