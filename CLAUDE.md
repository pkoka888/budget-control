# CLAUDE.md - AI Assistant Guidelines for Budget Control

**Version:** 1.0
**Last Updated:** 2025-11-11
**Purpose:** Guidelines for AI assistants (Claude Code, GPT, etc.) working on this project

---

## Overview

This document provides comprehensive guidelines for AI assistants working on the Budget Control project. It defines code standards, project structure, workflows, and specialized agent roles.

**IMPORTANT:** Always read `CONSTITUTION.md` first - it takes precedence over this document.

---

## Core Principles

### 1. Constitution First
- **ALWAYS** read and follow `CONSTITUTION.md`
- In case of conflict: CONSTITUTION.md > CLAUDE.md > other docs
- Constitution defines the "why," this document defines the "how"

### 2. Single Source of Truth
- Check `docs/FEATURES.md` for current feature status
- Don't assume features exist - verify first
- Update FEATURES.md when implementing new features

### 3. Functional Increments
- Every change must result in working functionality
- No half-finished features in commits
- Test before marking complete

### 4. User-Centric Focus
- This is a personal home finance app
- Designed for clarity and simplicity
- "Like sitting with a senior finance expert"

---

## Project Structure

```
budget-control/
├── CONSTITUTION.md          # Project governance (READ FIRST)
├── README.md                # Project overview
├── CLAUDE.md                # This file - AI guidelines
├── docs/                    # Documentation
│   ├── FEATURES.md          # Feature status (CHECK OFTEN)
│   ├── API.md               # API documentation
│   └── archive/             # Historical docs
├── budget-app/              # Main application
│   ├── src/                 # PHP source code
│   │   ├── Controllers/     # HTTP request handlers
│   │   ├── Jobs/            # Background job processors
│   │   ├── Database.php     # Database abstraction
│   │   ├── Router.php       # URL routing
│   │   └── Application.php  # App bootstrap
│   ├── public/              # Web root
│   │   ├── index.php        # Entry point
│   │   ├── css/             # Compiled Tailwind
│   │   └── js/              # JavaScript
│   ├── views/               # PHP templates
│   ├── database/            # SQLite + schema
│   ├── cli/                 # CLI tools
│   └── tests/               # Playwright E2E tests
├── agents/                  # AI agent definitions
└── user-data/               # Runtime data (gitignored)
```

---

## Technology Stack

### Backend
- **PHP 8.2+** - Use modern PHP features (typed properties, match expressions, etc.)
- **SQLite 3** - Lightweight, serverless database
- **Composer** - Dependency management (minimal dependencies)

### Frontend
- **Tailwind CSS v4** - Utility-first CSS framework
- **Vanilla JavaScript** - No frameworks (keep it simple)
- **Alpine.js** (optional) - For interactive components if needed

### Infrastructure
- **Apache 2.4** - Web server
- **Docker** - Containerization
- **Playwright** - E2E testing

---

## Code Standards

### PHP Standards

#### File Organization
```php
<?php
namespace BudgetApp\Controllers;

use BudgetApp\Database;

class TransactionController extends BaseController {
    // Properties first
    private Database $db;

    // Constructor
    public function __construct(Database $db) {
        parent::__construct($db);
    }

    // Public methods
    public function index(): void {
        // Implementation
    }

    // Private methods
    private function helper(): void {
        // Implementation
    }
}
```

#### Security Requirements
```php
// ✅ GOOD: Prepared statements
$stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
$user = $stmt->execute([$email]);

// ❌ BAD: SQL injection vulnerability
$sql = "SELECT * FROM users WHERE email = '$email'";

// ✅ GOOD: Output escaping
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// ❌ BAD: XSS vulnerability
echo $userInput;

// ✅ GOOD: Password hashing
$hash = password_hash($password, PASSWORD_BCRYPT);

// ❌ BAD: Plain text passwords
$password = $userInput;
```

#### Error Handling
```php
try {
    $result = $this->db->query("SELECT * FROM transactions");
    // Process result
} catch (\Exception $e) {
    error_log("Transaction query failed: " . $e->getMessage());
    $this->json(['error' => 'Database error occurred'], 500);
}
```

#### HTTP Status Codes
```php
// 200 OK - Immediate success
$this->json(['data' => $result], 200);

// 202 Accepted - Async job started
$this->json(['job_id' => $jobId], 202);

// 400 Bad Request - Invalid input
$this->json(['error' => 'Invalid input'], 400);

// 401 Unauthorized - Not authenticated
$this->json(['error' => 'Login required'], 401);

// 404 Not Found - Resource doesn't exist
$this->json(['error' => 'Not found'], 404);

// 500 Internal Server Error - Server failure
$this->json(['error' => 'Server error'], 500);
```

### Database Standards

#### Always Use Prepared Statements
```php
// ✅ GOOD
$transactions = $this->db->query(
    "SELECT * FROM transactions WHERE user_id = ? AND date >= ?",
    [$userId, $startDate]
);

// ❌ BAD
$transactions = $this->db->query(
    "SELECT * FROM transactions WHERE user_id = $userId AND date >= '$startDate'"
);
```

#### Index Foreign Keys
```sql
CREATE INDEX idx_transactions_user_id ON transactions(user_id);
CREATE INDEX idx_transactions_account_id ON transactions(account_id);
CREATE INDEX idx_transactions_category_id ON transactions(category_id);
```

### Frontend Standards

#### Tailwind CSS
```html
<!-- ✅ GOOD: Utility classes -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Title</h2>
</div>

<!-- ❌ BAD: Custom CSS -->
<div class="custom-card">
    <h2 class="custom-title">Title</h2>
</div>
```

#### JavaScript
```javascript
// ✅ GOOD: Vanilla JS, clear and simple
document.getElementById('submitBtn').addEventListener('click', async () => {
    const response = await fetch('/api/endpoint', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });
    const result = await response.json();
});

// ❌ BAD: jQuery or unnecessary frameworks
$('#submitBtn').click(() => {
    $.post('/api/endpoint', data, (result) => {});
});
```

---

## Workflows

### Feature Development Workflow

1. **Check FEATURES.md** - Verify feature status
2. **Plan Implementation** - Define acceptance criteria
3. **Write Code** - Follow standards above
4. **Write Tests** - Playwright E2E tests
5. **Update Documentation** - README, FEATURES.md, API.md
6. **Mark Complete** - Update FEATURES.md status

### Bug Fix Workflow

1. **Reproduce** - Verify bug exists
2. **Root Cause** - Identify why it's happening
3. **Fix** - Implement solution
4. **Test** - Verify fix + regression test
5. **Document** - Note in CHANGELOG if significant

### Code Review Checklist

Before committing:
- [ ] Code follows standards in this document
- [ ] Security vulnerabilities checked (SQL injection, XSS, etc.)
- [ ] Tests pass
- [ ] Documentation updated
- [ ] No debug code left in
- [ ] Backwards compatible or migration provided

---

## Specialized AI Agents

This project uses specialized AI agents for specific tasks. Each agent has expertise in a particular domain.

### Available Agents

#### 1. **Finance Expert Agent** (`agents/finance-expert.md`)
**Role:** Personal home budget expert and financial coach
**Expertise:**
- Analyzing spending patterns
- Budget recommendations
- Financial goal setting
- Debt management strategies
- Investment basics
**When to Use:** User asks for financial advice, budget analysis, spending insights

#### 2. **Developer Agent** (`agents/developer.md`)
**Role:** PHP/JavaScript developer for feature implementation
**Expertise:**
- PHP 8.2+ development
- SQLite database design
- Tailwind CSS styling
- JavaScript implementation
- Security best practices
**When to Use:** Implementing new features, fixing bugs, code refactoring

#### 3. **Database Agent** (`agents/database.md`)
**Role:** Database design and optimization specialist
**Expertise:**
- SQLite schema design
- Query optimization
- Index management
- Data migration
- Performance tuning
**When to Use:** Database changes, performance issues, schema migrations

#### 4. **Testing Agent** (`agents/testing.md`)
**Role:** E2E testing and quality assurance
**Expertise:**
- Playwright test authoring
- Test coverage analysis
- Bug reproduction
- Regression testing
- Accessibility testing
**When to Use:** Writing tests, debugging test failures, QA

#### 5. **Documentation Agent** (`agents/documentation.md`)
**Role:** Technical writing and documentation
**Expertise:**
- API documentation
- User guides
- Architecture diagrams
- Code comments
- README updates
**When to Use:** Documentation updates, writing guides, explaining features

### How to Invoke Agents

When you (AI assistant) encounter a task that requires specialized expertise:

```
# Example: User asks for budget advice
→ Invoke Finance Expert Agent
→ Agent analyzes user's transaction data
→ Agent provides personalized recommendations
→ Return advice to user

# Example: User requests new feature
→ Invoke Developer Agent
→ Agent implements feature following standards
→ Agent writes tests
→ Agent updates documentation
→ Return completed feature

# Example: Performance issue
→ Invoke Database Agent
→ Agent analyzes queries
→ Agent optimizes indexes
→ Agent tests performance
→ Return optimization results
```

---

## Common Tasks

### Adding a New Feature

1. **Read CONSTITUTION.md** - Understand principles
2. **Check docs/FEATURES.md** - Verify feature not already implemented
3. **Plan the feature:**
   - Define acceptance criteria
   - Identify affected files
   - Design database changes (if any)
4. **Implement:**
   - Create/modify controller
   - Update routes
   - Create views
   - Add database migrations (if needed)
5. **Test:**
   - Write Playwright E2E test
   - Manual testing
6. **Document:**
   - Update API.md (if API endpoint)
   - Update FEATURES.md status
   - Update README.md (if user-facing)
7. **Security Review:**
   - Check for SQL injection
   - Check for XSS
   - Validate all inputs
   - Escape all outputs

### Fixing a Bug

1. **Reproduce:**
   - Write failing test
   - Document steps to reproduce
2. **Analyze:**
   - Identify root cause
   - Check related code
3. **Fix:**
   - Implement solution
   - Verify fix doesn't break anything else
4. **Test:**
   - Ensure original test now passes
   - Run full test suite
5. **Document:**
   - Update FEATURES.md if feature was marked broken
   - Add to CHANGELOG if significant

### Async Job Pattern (HTTP 202)

For long-running operations:

```php
// Controller: Start job
public function autoImportAll(): void {
    $jobId = bin2hex(random_bytes(16));

    $this->db->insert('bank_import_jobs', [
        'job_id' => $jobId,
        'user_id' => $this->getUserId(),
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    // Execute job (move to background worker in production)
    $job = new BankImportJob($this->db, $jobId, $this->getUserId());
    $job->execute();

    // Return 202 Accepted immediately
    $this->json([
        'job_id' => $jobId,
        'status' => 'accepted',
        'message' => 'Job queued'
    ], 202);
}

// Status endpoint
public function jobStatus(): void {
    $jobId = $_GET['job_id'] ?? null;
    $job = $this->db->queryOne(
        "SELECT * FROM bank_import_jobs WHERE job_id = ?",
        [$jobId]
    );

    $this->json([
        'job_id' => $job['job_id'],
        'status' => $job['status'],
        'progress' => [
            'processed' => $job['processed_files'],
            'total' => $job['total_files'],
        ]
    ]);
}
```

---

## Testing

### Writing Playwright Tests

```javascript
// tests/feature.spec.js
const { test, expect } = require('@playwright/test');

test.describe('Feature Name', () => {
    test.beforeEach(async ({ page }) => {
        // Register and login
        await page.goto('http://localhost:8080/register');
        await page.fill('input[name="email"]', `test-${Date.now()}@example.com`);
        await page.fill('input[name="password"]', 'test123');
        await page.fill('input[name="name"]', 'Test User');
        await page.click('button[type="submit"]');
        await page.waitForURL(/dashboard/);
    });

    test('should do something', async ({ page }) => {
        // Test implementation
        await page.goto('http://localhost:8080/transactions');
        await expect(page.locator('h1')).toContainText('Transactions');
    });
});
```

### Running Tests

```bash
# All tests
npm test

# Specific test file
npm test tests/transactions.spec.js

# Headed mode (see browser)
npm test -- --headed

# Debug mode
npm test -- --debug
```

---

## Security Checklist

Before any commit:
- [ ] All user input validated
- [ ] All database queries use prepared statements
- [ ] All output escaped with `htmlspecialchars()`
- [ ] Passwords hashed with `password_hash()`
- [ ] File uploads validated (type, size, content)
- [ ] Directory traversal prevented (`basename()`, path checks)
- [ ] CSRF tokens on forms (if implementing)
- [ ] Session security configured
- [ ] No secrets in code (use environment variables)

---

## Performance Guidelines

### Database
- Use indexes on foreign keys
- Limit query results (pagination)
- Avoid N+1 queries
- Use transactions for multiple writes

### Frontend
- Minimize CSS/JS file sizes
- Use CDN for third-party libraries
- Lazy load images
- Cache static assets

---

## Deployment

### Docker Development

```bash
# Start
docker-compose -f budget-docker-compose.yml up -d

# Logs
docker-compose -f budget-docker-compose.yml logs -f

# Stop
docker-compose -f budget-docker-compose.yml down

# Rebuild
docker-compose -f budget-docker-compose.yml build --no-cache
```

### Database Reset

```bash
rm budget-app/database/budget.db
# Database recreated automatically on next request
```

---

## Communication Style

When working with the user:
- Be concise and clear
- Use technical terms but explain them
- Ask clarifying questions when requirements are unclear
- Provide options when multiple solutions exist
- Show code examples for complex explanations
- Reference file locations (e.g., `BankImportController.php:142`)

---

## When in Doubt

1. Check `CONSTITUTION.md` first
2. Check `docs/FEATURES.md` for current status
3. Check this file (`CLAUDE.md`) for coding standards
4. Check existing code for patterns
5. Ask the user for clarification

---

## Future Vision

Remember the long-term goal: **LLM Financial Tutor/Agent**

This means:
- Keep data structures clean and analyzable
- Document business logic clearly
- Design APIs for programmatic access
- Think about how an LLM would query/analyze data

Example future interaction:
```
User: "Where did I spend the most last month?"
Agent: [Analyzes transactions from database]
Agent: "You spent 15,432 CZK on Groceries last month (42% of expenses).
       Your top 3 merchants were Albert (4,200 CZK), Tesco (3,800 CZK),
       and Lidl (2,900 CZK). This is 8% higher than your 3-month average."
```

---

## Resources

- **CONSTITUTION.md** - Project governance
- **docs/FEATURES.md** - Feature status
- **docs/API.md** - API documentation
- **PHP Manual** - https://www.php.net/manual/en/
- **Tailwind CSS Docs** - https://tailwindcss.com/docs
- **Playwright Docs** - https://playwright.dev/

---

**Remember:** Simplicity, security, and user value above all else.

---

**Last Updated:** 2025-11-11
**Version:** 1.0
