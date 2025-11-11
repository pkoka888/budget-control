# Budget Control

**A lightweight personal finance management application**

Budget Control is a PHP-based web application designed for personal home use to track expenses, manage budgets, and gain insights into your financial health. Built with simplicity and functionality in mind.

---

## Features

- **Multi-user Authentication** - Secure session-based login system
- **Transaction Management** - Track income and expenses with categories
- **Account Management** - Support for checking, savings, credit, investment, and cash accounts
- **Budget Tracking** - Set monthly/yearly budgets with alert thresholds
- **Czech Bank Import** - Async import of George Bank JSON statements (16,000+ transactions tested)
- **CSV Export** - Export transactions with running balance
- **Investment Tracking** - Monitor stocks, bonds, funds, crypto, and more
- **Financial Goals** - Set and track progress toward financial targets
- **Reports & Analytics** - Monthly/yearly summaries and category breakdowns
- **Dark Mode** - Toggle between light and dark themes
- **RESTful API** - Programmatic access to all features
- **Responsive Design** - Mobile-first interface built with Tailwind CSS v4

For detailed feature status, see [docs/FEATURES.md](docs/FEATURES.md).

---

## Tech Stack

- **Backend:** PHP 8.2+
- **Database:** SQLite 3 (lightweight, serverless)
- **Frontend:** Tailwind CSS v4, Vanilla JavaScript
- **Server:** Apache 2.4
- **Container:** Docker with Docker Compose
- **Testing:** Playwright for E2E tests

---

## Quick Start

### Prerequisites

- Docker & Docker Compose
- Git

### Installation

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
   cd budget-control
   ```

2. **Start with Docker Compose:**
   ```bash
   docker-compose -f budget-docker-compose.yml up -d
   ```

3. **Access the application:**
   Open your browser and navigate to:
   ```
   http://localhost:8080
   ```

4. **Create your account:**
   - Click "Register" on the login page
   - Enter your email, password, and name
   - Start tracking your finances!

### First Steps

1. **Add an account** - Create your first bank account (e.g., "Checking Account")
2. **Add categories** - Set up expense categories (or import bank data to auto-create them)
3. **Add transactions** - Manually enter transactions or import from Czech bank JSON
4. **Set budgets** - Create monthly budgets for your categories
5. **View dashboard** - Monitor your financial overview

---

## Czech Bank Import

Budget Control supports async import of Czech bank (George Bank) JSON transaction data.

### How to Import

1. Place your `.json` bank export files in the `user-data/bank-json` folder
2. Navigate to **Bank Import** page in the app
3. Click **Auto Import All** button
4. Monitor import progress (HTTP 202 Accepted pattern with job status polling)

The import system:
- Handles large datasets (tested with 16,000+ transactions)
- Detects duplicates using reference numbers
- Auto-creates accounts and categories
- Maps Czech categories to English
- Processes asynchronously to avoid timeouts

For technical details, see [budget-app/src/Controllers/BankImportController.php](budget-app/src/Controllers/BankImportController.php).

---

## Project Structure

```
budget-control/
├── budget-app/              # Main PHP application
│   ├── src/                 # Application source code
│   │   ├── Controllers/     # MVC Controllers
│   │   ├── Jobs/            # Background job processors
│   │   └── *.php            # Core classes (Database, Router, etc.)
│   ├── public/              # Web-accessible files
│   │   ├── css/             # Tailwind CSS output
│   │   ├── js/              # JavaScript files
│   │   └── index.php        # Application entry point
│   ├── views/               # Template files
│   ├── database/            # SQLite database + schema
│   ├── cli/                 # Command-line tools
│   └── tests/               # Playwright E2E tests
├── docs/                    # Project documentation
│   ├── FEATURES.md          # Feature status tracking
│   ├── API.md               # API documentation
│   └── archive/             # Historical documentation
├── user-data/               # Runtime data (gitignored)
│   └── bank-json/           # Bank import files
├── CONSTITUTION.md          # Project governance & principles
├── README.md                # This file
├── docker-compose.yml       # Docker Compose configuration
├── Dockerfile               # Docker image definition
└── composer.json            # PHP dependencies
```

---

## Development

### Running Tests

```bash
# Run all Playwright tests
npm test

# Run specific test suite
npm test tests/functionality.spec.js
```

### Building Tailwind CSS

```bash
cd budget-app
npm run build:css

# Watch mode for development
npm run watch:css
```

### Database Schema

The SQLite schema is defined in [budget-app/database/schema.sql](budget-app/database/schema.sql).

To reset the database:
```bash
rm budget-app/database/budget.db
# Database will be recreated on next application load
```

### Background Jobs

Process pending bank import jobs manually:
```bash
docker exec -it budget-control-app php cli/process-bank-imports.php
```

Or process a specific job:
```bash
docker exec -it budget-control-app php cli/process-bank-imports.php --job-id=<job_id>
```

---

## API Documentation

Budget Control provides a RESTful API (v1) for programmatic access.

### Authentication

- **POST** `/register` - Create new user account
- **POST** `/login` - Authenticate user
- **POST** `/logout` - End session

### Transactions

- **GET** `/transactions` - List transactions (with filtering)
- **POST** `/transactions` - Create new transaction
- **POST** `/transactions/edit/{id}` - Update transaction
- **POST** `/transactions/delete/{id}` - Delete transaction
- **GET** `/transactions/export-csv` - Export to CSV

### Bank Import

- **GET** `/bank-import` - View import page
- **POST** `/bank-import/import-file` - Import single file
- **POST** `/bank-import/auto-import` - Async import all files (returns 202 Accepted)
- **GET** `/bank-import/job-status?job_id=<id>` - Poll job status

For complete API documentation, see [docs/API.md](docs/API.md).

---

## Security

- **Password Hashing:** Bcrypt via PHP `password_hash()`
- **SQL Injection Protection:** Prepared statements
- **XSS Protection:** Output escaping with `htmlspecialchars()`
- **Session Security:** Secure session configuration
- **Input Validation:** Server-side validation on all inputs
- **Directory Traversal Protection:** Path sanitization

**Note:** This application is designed for trusted home network use. For public internet deployment, additional security hardening is recommended (HTTPS, rate limiting, CSRF tokens, security headers).

---

## Documentation

- **[CONSTITUTION.md](CONSTITUTION.md)** - Project governance, principles, and standards
- **[docs/FEATURES.md](docs/FEATURES.md)** - Complete feature list with status
- **[docs/API.md](docs/API.md)** - API endpoint documentation
- **[CLAUDE.md](CLAUDE.md)** - Guidelines for AI assistants working on this project

---

## Roadmap

### Phase 1: Stable Release (v1.0) - Current
- ✅ Core features implemented
- 🚧 Bug fixes and testing
- 🚧 Documentation consolidation
- 📋 Security audit
- 📋 Performance testing
- 📋 First stable release

### Phase 2: LLM Financial Tutor/Agent - Future
- Conversational interface for financial insights
- Natural language queries: "Where did I spend most last month?"
- AI-powered budget coaching
- Spending pattern recognition
- Personalized financial recommendations

See [docs/FEATURES.md](docs/FEATURES.md) for detailed roadmap.

---

## Contributing

This is primarily a personal project, but contributions are welcome!

1. Read [CONSTITUTION.md](CONSTITUTION.md) for project principles
2. Check [docs/FEATURES.md](docs/FEATURES.md) for current status
3. Follow coding standards outlined in CONSTITUTION.md
4. Write tests for new features
5. Update documentation

---

## License

[License information to be added]

---

## Acknowledgments

- Built with guidance from Claude Code (Anthropic)
- Inspired by the need for simple, effective personal finance management
- Czech bank import format based on George Bank (Česká spořitelna)

---

## Support

For issues, questions, or feature requests, please open an issue in the repository.

---

**Version:** 1.0.0-rc1
**Last Updated:** 2025-11-11
