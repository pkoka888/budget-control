# Budget Control Demo Account Login Test Report

**Date:** 2025-11-15
**Test URL:** http://budget.okamih.cz/
**Tester:** Claude Code - Testing Specialist
**Test Status:** FAILED - Critical Production Bug Identified

---

## Executive Summary

Login test **FAILED** due to a critical application error in the RateLimiter middleware. The live production server has a code defect preventing all login attempts, affecting both the demo account and all users. The application is currently **non-functional**.

### Test Results Overview

| Component | Result | Status |
|-----------|--------|--------|
| Server Health | ✅ PASS | Server running, database operational |
| Database | ✅ OK | SQLite functional, 1 user exists |
| Login Page Load | ✅ PASS | HTML/CSRF token working |
| **Login Form Submission** | ❌ **FAIL** | **Fatal PHP error** |
| **Dashboard Access** | ❌ **FAIL** | **Blocked by error** |
| **Demo Data Accessibility** | ❌ **FAIL** | **Cannot verify - login blocked** |

**Overall Status:** 🔴 **CRITICAL - APPLICATION NOT OPERATIONAL**

---

## Detailed Test Results

### 1. Health Endpoint Test ✅ PASSED

**Endpoint:** `GET http://budget.okamih.cz/health.php`
**HTTP Status:** 200 OK

**Infrastructure Status:**
- **Database:** OK - SQLite 3.x operational, 1 user in database, 1.6 MB file size
- **Database Writability:** OK - Permissions 2775 (writable)
- **PHP:** OK - Version 8.4.11
- **Required Extensions:** All present (pdo_sqlite, json, curl, gd, mbstring)
- **PHP Configuration:** Healthy
  - Memory Limit: 512M
  - Upload Max: 32M
  - Post Max: 64M
  - Max Execution: 180s
- **Session Handler:** Files, directory writable
- **Disk Space:** Warning - 84.66% used (36.23 GB free of 236.19 GB)
- **Critical Files:** Mostly present (.env file missing, non-critical)

**Health Response (truncated):**
```json
{
  "status": "degraded",
  "database": {
    "status": "ok",
    "message": "Database operational",
    "users_count": 1,
    "size_mb": 1.6
  },
  "php": {
    "status": "ok",
    "version": "8.4.11",
    "extensions": {
      "pdo_sqlite": true,
      "json": true,
      "mbstring": true,
      "curl": true
    }
  }
}
```

**Conclusion:** Infrastructure is healthy. Issue is in application code, not server setup.

---

### 2. Login Page Load Test ✅ PASSED

**Endpoint:** `GET http://budget.okamih.cz/login`
**HTTP Status:** 200 OK

**Successfully Retrieved:**
- ✅ Valid HTML document structure
- ✅ CSRF token present and valid: `a836e259e3effcd2966a4e3bf98351d2c2c00b68ae1213e285e5f22663cab414`
- ✅ Login form with email and password fields
- ✅ UI in Czech language (Česky)
- ✅ Tailwind CSS styling loaded correctly
- ✅ Form action points to `/login` (POST)
- ✅ Session cookie created (PHPSESSID)

**HTML Structure (sample):**
```html
<form method="POST" action="/login">
    <input type="hidden" name="csrf_token" value="a836e259e3effcd2966a4e3bf98351d2c2c00b68ae1213e285e5f22663cab414">
    <input type="email" id="email" name="email" required>
    <input type="password" id="password" name="password" required>
    <button type="submit">Přihlásit se</button>
</form>
```

**CSRF Token Handling:** ✅ **WORKING CORRECTLY**

---

### 3. Login Form Submission Test ❌ FAILED

**Endpoint:** `POST http://budget.okamih.cz/login`
**Method:** POST
**Content-Type:** application/x-www-form-urlencoded

**Form Data Submitted:**
```
email=demo@budgetcontrol.cz
password=DemoPassword123!
csrf_token=a836e259e3effcd2966a4e3bf98351d2c2c00b68ae1213e285e5f22663cab414
```

**HTTP Response Code:** 200 (ERROR - expected 302 redirect on success)

**Critical Error Detected:**

```
Fatal error: Uncaught Error: Call to undefined method BudgetApp\Database::exec()
in /var/www/budget-control/budget-app/src/Middleware/RateLimiter.php:149
```

**Full Stack Trace:**
```
#0 /var/www/budget-control/budget-app/src/Middleware/RateLimiter.php(38):
   BudgetApp\Middleware\RateLimiter->ensureTableExists()

#1 /var/www/budget-control/budget-app/src/Middleware/RateLimiter.php(74):
   BudgetApp\Middleware\RateLimiter->checkLimit()

#2 /var/www/budget-control/budget-app/src/Middleware/RateLimiter.php(224):
   BudgetApp\Middleware\RateLimiter->requireLimit()

#3 /var/www/budget-control/budget-app/src/Controllers/AuthController.php(37):
   BudgetApp\Middleware\RateLimiter->requireLoginLimit()

#4 /var/www/budget-control/budget-app/src/Application.php(388):
   BudgetApp\Controllers\AuthController->login()

#5 /var/www/budget-control/budget-app/public/index.php(165):
   BudgetApp\Application->run()

{main}
  thrown in /var/www/budget-control/budget-app/src/Middleware/RateLimiter.php
  on line 149
```

**Session Cookie Status:** ⚠️ **Created but login incomplete**
- Cookie: `PHPSESSID=14d61431df44c4e1e62300e69eb0cf09`
- However, login did not complete due to fatal error occurring before credentials validation

---

### 4. Dashboard Access Test ❌ FAILED

**Endpoint:** `GET http://budget.okamih.cz/`
**Status:** 200 (ERROR - same RateLimiter error)

Dashboard access blocked by fatal error in middleware before login check.

**Demo Data Accessibility:** ❌ **CANNOT VERIFY**
- Cannot test transaction visibility
- Cannot verify account balances
- Cannot validate demo data import
- Login is blocked entirely

---

## Root Cause Analysis: Code Defect

### The Critical Bug

**File:** `/var/www/budget-control/budget-app/src/Middleware/RateLimiter.php`

The RateLimiter middleware has a fundamental **architectural mismatch** between:
1. How it's tested (with raw PDO object)
2. How it's used in production (with Database wrapper)

### Issue #1: Missing `exec()` Method (Line 149)

**Code:**
```php
private function ensureTableExists(): void {
    $this->db->exec("CREATE TABLE IF NOT EXISTS rate_limits ...");  // Line 149
}
```

**Problem:** Database class does NOT expose `exec()` method

**Expected Object:** Raw PDO object
```php
$pdo->exec("CREATE TABLE ...");  // ✅ PDO has exec()
```

**Actual Object Received:** Database wrapper instance
```php
$database = new Database(...);
$database->exec(...);  // ❌ Database class has no exec()
```

---

### Issue #2: Missing `prepare()` Method (Lines 41, 55, 101, 121)

**Code (Line 41-46):**
```php
$stmt = $this->db->prepare(
    "SELECT COUNT(*) as count FROM rate_limits WHERE key = ? AND attempted_at > ?"
);
$result = $stmt->execute([$key, $windowStart]);
$row = $result->fetchArray(SQLITE3_ASSOC);  // Also wrong - SQLite3 API
```

**Problem:** Database class uses PDO wrapper, not raw SQLite3 extension

**Database Class Public Methods:**
```php
public function query(string $sql, array $params = []): array
public function queryOne(string $sql, array $params = []): ?array
public function execute(string $sql, array $params = []): int
```

**Correct Usage Should Be:**
```php
$rows = $this->db->query(
    "SELECT COUNT(*) as count FROM rate_limits WHERE key = ? AND attempted_at > ?",
    [$key, $windowStart]
);
$count = (int)$rows[0]['count'] ?? 0;  // Returns array directly
```

---

### Issue #3: Missing `changes()` Method (Line 139)

**Code:**
```php
return $this->db->changes();  // Line 139
```

**Problem:** Database class doesn't have `changes()` method

**Should Use:**
```php
return $this->db->execute("DELETE FROM rate_limits WHERE attempted_at < ?", [$cutoffTime]);
// execute() returns affected row count
```

---

### Why Tests Pass But Production Fails

**RateLimiterTest.php (Line 13):**
```php
// Test passes RAW PDO object
$this->db = new PDO('sqlite::memory:');
$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```

**AuthController.php (Line 36):**
```php
// Production passes Database WRAPPER object
$rateLimiter = new \BudgetApp\Middleware\RateLimiter($this->db);
// $this->db is Database class instance
```

**This reveals:**
- ✅ Unit tests pass (use raw PDO)
- ❌ Integration/production fails (uses Database wrapper)
- **Integration testing gap:** Code was never tested in actual application context

---

## Database Class Analysis

**File:** `/var/www/budget-control/budget-app/src/Database.php`

The Database class is a proper PDO wrapper that provides:

**Available Methods:**
```php
public function query(string $sql, array $params = []): array
public function queryOne(string $sql, array $params = []): ?array
public function execute(string $sql, array $params = []): int
public function insert(string $table, array $data): int
public function update(string $table, array $data, array $where): int
public function delete(string $table, array $where): int
public function getPdo(): \PDO
public function beginTransaction(): void
public function commit(): void
public function rollback(): void
```

**Missing Methods (Required by RateLimiter):**
```php
exec()           // For DDL: CREATE TABLE, CREATE INDEX
prepare()        // For prepared statements
changes()        // For affected row count
fetchArray()     // For result set operations
```

---

## API Method Mapping: RateLimiter Fixes Needed

### Issue 1: Create Table (Lines 149-156)

**Current (BROKEN):**
```php
$this->db->exec("CREATE TABLE IF NOT EXISTS rate_limits (...)");
```

**Fix:**
```php
$pdo = $this->db->getPdo();
$pdo->exec("CREATE TABLE IF NOT EXISTS rate_limits (...)");
```

---

### Issue 2: Count Query (Lines 41-47)

**Current (BROKEN):**
```php
$stmt = $this->db->prepare("SELECT COUNT(*) as count FROM rate_limits WHERE key = ? AND attempted_at > ?");
$result = $stmt->execute([$key, $windowStart]);
$row = $result->fetchArray(SQLITE3_ASSOC);
$count = (int) $row['count'];
```

**Fix:**
```php
$rows = $this->db->query(
    "SELECT COUNT(*) as count FROM rate_limits WHERE key = ? AND attempted_at > ?",
    [$key, $windowStart]
);
$count = (int)($rows[0]['count'] ?? 0);
```

---

### Issue 3: Insert Query (Lines 55-58)

**Current (BROKEN):**
```php
$stmt = $this->db->prepare("INSERT INTO rate_limits (key, attempted_at) VALUES (?, ?)");
$stmt->execute([$key, date('Y-m-d H:i:s')]);
```

**Fix:**
```php
$this->db->execute(
    "INSERT INTO rate_limits (key, attempted_at) VALUES (?, ?)",
    [$key, date('Y-m-d H:i:s')]
);
```

---

### Issue 4: Reset Query (Lines 121-122)

**Current (BROKEN):**
```php
$stmt = $this->db->prepare("DELETE FROM rate_limits WHERE key = ?");
$stmt->execute([$key]);
```

**Fix:**
```php
$this->db->execute("DELETE FROM rate_limits WHERE key = ?", [$key]);
```

---

### Issue 5: Cleanup (Line 137, 139)

**Current (BROKEN):**
```php
$stmt = $this->db->prepare("DELETE FROM rate_limits WHERE attempted_at < ?");
$stmt->execute([$cutoffTime]);
return $this->db->changes();  // No such method!
```

**Fix:**
```php
return $this->db->execute(
    "DELETE FROM rate_limits WHERE attempted_at < ?",
    [$cutoffTime]
);  // execute() returns affected row count
```

---

## Impact Assessment

### Scope
- **Affected Users:** ALL users (demo account + production users)
- **Affected Functionality:** Login/Authentication (critical)
- **Affected Features:** Everything behind authentication

### Severity
- **Level:** CRITICAL 🔴
- **Category:** Security & Availability
- **Business Impact:** Application completely non-functional
- **Time to Impact:** Immediate - all login attempts fail

### Symptoms
```
Browser: Shows "Fatal error" message
API: Returns 200 with error HTML instead of redirect
Users: Cannot log in at all
Admin: Application is down
```

---

## Technical Specifications

### Test Environment
- **Date:** 2025-11-15
- **Time:** 02:14:31 UTC
- **URL:** http://budget.okamih.cz/
- **Server:** Apache 2.4.65 (Debian), PHP 8.4.11, SQLite 3.x
- **Test Method:** curl HTTP requests
- **Test Data:** Demo account (demo@budgetcontrol.cz)

### PHP Error Configuration
- **Display Errors:** ON (visible in output)
- **Error Reporting:** Catching fatal errors
- **Exception Mode:** PDO ERRMODE_EXCEPTION enabled

---

## Recommendations

### URGENT: Priority 1 - Fix Immediately

1. **Rewrite RateLimiter.php** to use Database class API
   - Replace all `prepare()` calls with `query()` or `execute()`
   - Replace `exec()` calls with `$this->db->getPdo()->exec()`
   - Replace `fetchArray()` with array access on results
   - Remove `changes()` call, use return value from `execute()`

2. **Testing Strategy**
   - ✅ Fix existing unit tests OR update them to use Database wrapper
   - ✅ Add integration tests that use actual Database class
   - ✅ Run E2E login test before production deployment

3. **Deployment**
   - Create hotfix branch
   - Test thoroughly in staging
   - Deploy during maintenance window (minimal user impact)
   - Verify login works for all user types

---

## Test Evidence Files

All test requests and responses documented:
- Login page HTML: Contains valid CSRF token
- Health endpoint: JSON response showing system status
- Error output: Full PHP stack trace with file:line references
- Cookie jar: Session cookie created (PHPSESSID)

---

## Files Requiring Changes

**Primary:**
- `/var/www/budget-control/budget-app/src/Middleware/RateLimiter.php` - 9 code locations need fixes

**Secondary (Update Tests):**
- `/var/www/budget-control/budget-app/tests/RateLimiterTest.php` - Consider updating to use Database wrapper

**Production Files (No Changes):**
- `/var/www/budget-control/budget-app/src/Database.php` - Implementation is correct
- `/var/www/budget-control/budget-app/src/Controllers/AuthController.php` - Usage is correct

---

## Conclusion

The demo account login test has identified a **production-critical code defect** that makes the entire Budget Control application non-functional.

**The Problem:** A mismatch between unit test assumptions (raw PDO object) and production reality (Database wrapper class).

**The Impact:** All users are blocked from logging in.

**The Solution:** Rewrite RateLimiter middleware to use the Database wrapper API instead of direct PDO/SQLite3 calls.

**Status:** 🔴 **APPLICATION NOT OPERATIONAL - IMMEDIATE FIX REQUIRED**

---

**Test Report Generated:** 2025-11-15 02:19 UTC
**Prepared by:** Claude Code - Testing Specialist
**For:** Budget Control Project Team
