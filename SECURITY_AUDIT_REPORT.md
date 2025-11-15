# Security Audit Report - Budget Control Application
**Date:** 2025-11-15
**Auditor:** Security Expert
**Application Version:** 2.0.0
**Scope:** Production Deployment Security Assessment

---

## Executive Summary

This security audit assessed the Budget Control application for production deployment readiness. The application demonstrates **good security practices** in several areas, particularly in authentication, CSRF protection, and SQL injection prevention. However, there are **critical vulnerabilities** and **high-priority issues** that must be addressed before production deployment.

**Overall Security Rating:** ⚠️ **MODERATE RISK** - Not ready for production without fixes

---

## Critical Vulnerabilities (Must Fix Before Production)

### 1. ❌ CRITICAL: Missing Session Regeneration on Login
**File:** `/var/www/budget-control/budget-app/src/Controllers/AuthController.php`
**Lines:** 44-56

**Issue:** Session ID is not regenerated after successful login, making the application vulnerable to **session fixation attacks**.

**Current Code:**
```php
if ($user && password_verify($password, $user['password_hash'])) {
    $_SESSION['user_id'] = $user['id'];  // No session_regenerate_id() call!
    // ...
}
```

**Impact:** An attacker can fixate a victim's session ID before login and hijack their session after they authenticate.

**Recommendation:**
```php
if ($user && password_verify($password, $user['password_hash'])) {
    session_regenerate_id(true); // Add this line
    $_SESSION['user_id'] = $user['id'];
    // ...
}
```

**Also affects:** `register()` method (line 112), `resetPassword()` method (line 327)

---

### 2. ❌ CRITICAL: Insecure Password Reset URL (HTTP instead of HTTPS)
**File:** `/var/www/budget-control/budget-app/src/Controllers/AuthController.php`
**Line:** 196

**Issue:** Password reset tokens are sent over HTTP, exposing them to man-in-the-middle attacks.

**Current Code:**
```php
$resetUrl = "http://" . $_SERVER['HTTP_HOST'] . "/reset-password?token=$token";
```

**Impact:** Attackers can intercept password reset tokens in transit and take over user accounts.

**Recommendation:**
```php
$resetUrl = "https://" . $_SERVER['HTTP_HOST'] . "/reset-password?token=$token";
// Or better: Use configured APP_URL from environment
$resetUrl = ($_ENV['APP_URL'] ?? 'https://localhost') . "/reset-password?token=$token";
```

---

### 3. ❌ CRITICAL: Host Header Injection Vulnerability
**Files:**
- `/var/www/budget-control/budget-app/src/Controllers/AuthController.php:196`
- `/var/www/budget-control/budget-app/src/Services/EmailVerificationService.php:220`

**Issue:** The application uses `$_SERVER['HTTP_HOST']` directly without validation, allowing attackers to inject malicious hosts in password reset emails.

**Attack Scenario:**
```
GET /forgot-password HTTP/1.1
Host: evil.com

User receives email with: http://evil.com/reset-password?token=...
```

**Recommendation:**
```php
// Validate and sanitize host
$allowedHosts = ['yourdomain.com', 'www.yourdomain.com'];
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
if (!in_array($host, $allowedHosts)) {
    $host = 'yourdomain.com'; // Default to safe host
}
```

---

### 4. ❌ CRITICAL: Weak Password Requirements
**File:** `/var/www/budget-control/budget-app/src/Controllers/AuthController.php`
**Lines:** 79-91, 290

**Issue:** Password validation only checks for minimum length (8 characters), no complexity requirements.

**Current Validation:**
```php
if (strlen($password) < 8) {
    // Error
}
```

**Impact:** Users can create weak passwords like "12345678" or "password".

**Recommendation:**
```php
// Add password complexity validation
if (strlen($password) < 12) {
    return 'Password must be at least 12 characters';
}
if (!preg_match('/[A-Z]/', $password)) {
    return 'Password must contain at least one uppercase letter';
}
if (!preg_match('/[a-z]/', $password)) {
    return 'Password must contain at least one lowercase letter';
}
if (!preg_match('/[0-9]/', $password)) {
    return 'Password must contain at least one number';
}
if (!preg_match('/[^A-Za-z0-9]/', $password)) {
    return 'Password must contain at least one special character';
}
```

---

### 5. ❌ CRITICAL: File Upload Security Issues
**File:** `/var/www/budget-control/budget-app/src/Controllers/BankImportController.php`
**Lines:** 73-84

**Issue:** Directory traversal protection is weak and relies on string checking instead of proper path canonicalization.

**Current Code:**
```php
// Security: Prevent directory traversal
if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
    $this->json(['error' => 'Invalid filename'], 400);
}
$filepath = '/var/www/html/user-data/bank-json/' . basename($filename);
```

**Vulnerability:** Attackers might bypass with encoded characters like `%2e%2e` or null bytes.

**Recommendation:**
```php
// Proper path validation
$filename = basename($filename); // Already used, good
$allowedDir = realpath('/var/www/html/user-data/bank-json/');
$filepath = realpath($allowedDir . '/' . $filename);

if (!$filepath || strpos($filepath, $allowedDir) !== 0) {
    $this->json(['error' => 'Invalid filename'], 400);
    return;
}
```

---

### 6. ❌ CRITICAL: CSV Import File Type Validation Bypass
**File:** `/var/www/budget-control/budget-app/src/Controllers/ImportController.php`
**Lines:** 47-49

**Issue:** File type validation relies on client-controlled MIME type, which can be spoofed.

**Current Code:**
```php
if (!in_array($file['type'], ['text/csv', 'application/csv', 'text/plain'])) {
    $this->json(['error' => 'Neplatný typ souboru'], 400);
}
```

**Impact:** Attackers can upload malicious files by spoofing MIME types.

**Recommendation:**
```php
// Validate using actual file content (magic bytes)
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);

// Also check file extension
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($extension !== 'csv' || !in_array($mimeType, ['text/csv', 'text/plain', 'application/csv'])) {
    $this->json(['error' => 'Invalid file type'], 400);
}
```

---

## High Priority Security Issues

### 7. ⚠️ HIGH: XSS Vulnerabilities in Email Templates
**Files:**
- `/var/www/budget-control/budget-app/views/emails/*.php`

**Issue:** Email template variables are not escaped, potentially allowing XSS in email clients.

**Examples:**
```php
// budget-app/views/emails/budget-alert.php:12
<strong>Usage:</strong> <?php echo $percentage; ?>%

// budget-app/views/emails/weekly-summary.php:48
<?php echo number_format($category['amount'], 2); ?>
```

**Impact:** If category names or user data contain malicious HTML/JavaScript, they will be rendered in emails.

**Recommendation:**
```php
<strong>Category:</strong> <?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?>
<strong>Usage:</strong> <?php echo htmlspecialchars($percentage, ENT_QUOTES, 'UTF-8'); ?>%
```

---

### 8. ⚠️ HIGH: Insufficient Rate Limiting on Password Reset
**File:** `/var/www/budget-control/budget-app/src/Controllers/AuthController.php`
**Line:** 162

**Issue:** Password reset rate limit is 3 attempts per hour per email, but there's no global IP-based rate limiting.

**Current Implementation:**
```php
$rateLimiter->requirePasswordResetLimit($email); // Only limits by email
```

**Attack Vector:** Attackers can enumerate valid email addresses by trying different emails from the same IP.

**Recommendation:**
```php
// Add IP-based rate limiting
$ip = \BudgetApp\Middleware\RateLimiter::getClientIp();
$rateLimiter->requireLimit("password_reset:ip:$ip", 10, 3600); // 10 per hour per IP
$rateLimiter->requirePasswordResetLimit($email); // Existing email-based limit
```

---

### 9. ⚠️ HIGH: Missing HTTP Security Headers
**Files:** Docker/Nginx configuration

**Issue:** No security headers are configured in the application or Nginx.

**Missing Headers:**
- `X-Frame-Options: DENY`
- `X-Content-Type-Options: nosniff`
- `X-XSS-Protection: 1; mode=block`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: geolocation=(), microphone=(), camera=()`
- `Content-Security-Policy`

**Recommendation:** Add to Nginx configuration:
```nginx
add_header X-Frame-Options "DENY" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' cdn.tailwindcss.com cdn.jsdelivr.net; style-src 'self' 'unsafe-inline';" always;
```

---

### 10. ⚠️ HIGH: Database Files Exposed with Overly Permissive Permissions
**File:** `/var/www/budget-control/Dockerfile`
**Lines:** 74-79

**Issue:** Database directory has 777 permissions, allowing any process to read/write.

**Current Configuration:**
```dockerfile
chmod -R 777 /var/www/html/database
```

**Impact:** Any compromised process can access or modify the database.

**Recommendation:**
```dockerfile
chmod -R 750 /var/www/html/database
chown -R budgetapp:www-data /var/www/html/database
```

---

### 11. ⚠️ HIGH: API Keys Stored in Plain Text
**File:** `/var/www/budget-control/budget-app/src/Middleware/ApiAuthMiddleware.php`
**Lines:** 29-36

**Issue:** API keys are stored in plain text in the database, allowing anyone with database access to steal them.

**Current Implementation:**
```php
$keyData = $this->db->queryOne(
    "SELECT ak.*, u.id as user_id...
     WHERE ak.api_key = ?", [$apiKey]
);
```

**Recommendation:**
```php
// Store hashed API keys
// On creation:
$hashedKey = hash('sha256', $apiKey);
// Store $hashedKey in database

// On validation:
$hashedKey = hash('sha256', $apiKey);
$keyData = $this->db->queryOne(
    "SELECT ak.*, u.id as user_id...
     WHERE ak.api_key_hash = ?", [$hashedKey]
);
```

---

### 12. ⚠️ HIGH: CSRF Token Not Validated on File Uploads
**File:** `/var/www/budget-control/budget-app/src/Controllers/ImportController.php`
**Lines:** 22-33

**Issue:** The `upload()` method doesn't validate CSRF tokens.

**Current Code:**
```php
public function upload(): void {
    $this->requireAuth();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
    // No CSRF check!
}
```

**Recommendation:**
```php
public function upload(): void {
    $this->requireAuth();
    \BudgetApp\Middleware\CsrfProtection::requireToken(); // Add this
    // ... rest of code
}
```

---

## Medium Priority Security Issues

### 13. ⚡ MEDIUM: Email Enumeration via Registration
**File:** `/var/www/budget-control/budget-app/src/Controllers/AuthController.php`
**Lines:** 94-97

**Issue:** Registration endpoint reveals whether an email is already registered.

**Current Code:**
```php
$existing = $this->db->queryOne("SELECT id FROM users WHERE email = ?", [$email]);
if ($existing) {
    echo $this->app->render('auth/register', ['error' => 'E-mail je již registrován']);
    return;
}
```

**Impact:** Attackers can enumerate valid email addresses registered in the system.

**Recommendation:** Return a generic message and send email notification to existing users:
```php
if ($existing) {
    // Send email to existing user about attempted registration
    echo $this->app->render('auth/register', [
        'success' => 'If this email is not registered, you will receive a confirmation link.'
    ]);
    return;
}
```

---

### 14. ⚡ MEDIUM: SQL Injection Risk in Dynamic Table Names
**File:** `/var/www/budget-control/budget-app/src/Database.php`
**Lines:** 71-88

**Issue:** The `insert()` and `update()` methods accept table names as strings without validation.

**Current Code:**
```php
public function insert(string $table, array $data): int {
    $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
}
```

**Attack Scenario:** If `$table` comes from user input anywhere in the application:
```php
$this->db->insert($_GET['type'], $data); // Dangerous!
```

**Recommendation:**
```php
private $allowedTables = [
    'users', 'accounts', 'transactions', 'categories', 'budgets'
    // ... list all valid tables
];

public function insert(string $table, array $data): int {
    if (!in_array($table, $this->allowedTables)) {
        throw new \Exception("Invalid table name: {$table}");
    }
    // ... rest of code
}
```

---

### 15. ⚡ MEDIUM: No Account Lockout After Failed Attempts
**File:** `/var/www/budget-control/budget-app/src/Controllers/AuthController.php`
**Lines:** 36-57

**Issue:** While rate limiting exists, there's no account lockout mechanism for excessive failed login attempts.

**Current Implementation:** Rate limiting by email+IP only.

**Recommendation:** Implement account lockout after X failed attempts:
```php
// Track failed attempts per user account
$failedAttempts = $this->db->queryOne(
    "SELECT failed_attempts, locked_until FROM users WHERE email = ?",
    [$email]
);

if ($failedAttempts['locked_until'] && $failedAttempts['locked_until'] > date('Y-m-d H:i:s')) {
    echo $this->app->render('auth/login', [
        'error' => 'Account temporarily locked. Try again later.'
    ]);
    return;
}

// On failed login:
$this->db->execute(
    "UPDATE users SET failed_attempts = failed_attempts + 1 WHERE email = ?",
    [$email]
);

// Lock account after 5 failed attempts
if ($failedAttempts['failed_attempts'] >= 5) {
    $this->db->execute(
        "UPDATE users SET locked_until = ? WHERE email = ?",
        [date('Y-m-d H:i:s', time() + 900), $email] // 15 minutes
    );
}
```

---

### 16. ⚡ MEDIUM: Session Configuration Issues
**File:** `/var/www/budget-control/budget-app/public/index.php`
**Line:** 10

**Issue:** Session is started without secure configuration flags.

**Current Code:**
```php
session_start();
```

**Recommendation:**
```php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Only over HTTPS
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
session_start();
```

---

### 17. ⚡ MEDIUM: Missing Input Validation on API Endpoints
**File:** `/var/www/budget-control/budget-app/src/Controllers/ApiController.php`
**Lines:** 287-330

**Issue:** API endpoints accept JSON input without comprehensive validation.

**Example:**
```php
public function createTransaction(array $params = []): void {
    $data = json_decode(file_get_contents('php://input'), true);

    // Minimal validation, but no sanitization or type checking
    if (!isset($data['account_id'])) {
        $this->json(['error' => 'Missing required field'], 400);
    }
}
```

**Recommendation:** Implement comprehensive input validation:
```php
// Validate and sanitize all inputs
$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    $this->json(['error' => 'Invalid JSON data'], 400);
}

// Validate types
if (!isset($data['amount']) || !is_numeric($data['amount'])) {
    $this->json(['error' => 'Amount must be a number'], 400);
}

// Sanitize strings
$data['description'] = trim($data['description']);
if (strlen($data['description']) > 255) {
    $data['description'] = substr($data['description'], 0, 255);
}
```

---

### 18. ⚡ MEDIUM: Information Disclosure in Error Messages
**File:** `/var/www/budget-control/budget-app/public/index.php`
**Lines:** 16-17, 224-228

**Issue:** Debug mode is enabled by default, exposing stack traces and sensitive information.

**Current Configuration:**
```php
error_reporting(E_ALL);
ini_set('display_errors', '1');
```

**Recommendation:**
```php
// Only enable in development
if ($_ENV['APP_ENV'] === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', '/var/www/html/logs/php_errors.log');
}
```

---

## Low Priority / Best Practices

### 19. ℹ️ LOW: Missing Security Logging
**Impact:** No audit trail for security events.

**Recommendation:** Implement security event logging:
```php
// Log security events
function logSecurityEvent($event, $details) {
    error_log(json_encode([
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'details' => $details
    ]), 3, '/var/www/html/logs/security.log');
}

// Log events like:
// - Failed login attempts
// - Account lockouts
// - Password changes
// - API key usage
// - File uploads
```

---

### 20. ℹ️ LOW: No Content Security Policy
**Recommendation:** Implement CSP to prevent XSS attacks:
```php
header("Content-Security-Policy: default-src 'self'; script-src 'self' cdn.tailwindcss.com cdn.jsdelivr.net; style-src 'self' 'unsafe-inline';");
```

---

### 21. ℹ️ LOW: Password Reset Token Lifetime Too Long
**File:** `/var/www/budget-control/budget-app/src/Controllers/AuthController.php`
**Line:** 184

**Issue:** Password reset tokens expire in 15 minutes, which is reasonable but could be shorter.

**Current:** 900 seconds (15 minutes)
**Recommendation:** 600 seconds (10 minutes)

---

### 22. ℹ️ LOW: Missing Subresource Integrity (SRI) for CDN Resources
**File:** `/var/www/budget-control/budget-app/views/layout.php`
**Lines:** 14-17

**Issue:** External scripts loaded without SRI checks.

**Current:**
```html
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
```

**Recommendation:**
```html
<script src="https://cdn.tailwindcss.com"
        integrity="sha384-..."
        crossorigin="anonymous"></script>
```

---

## Positive Security Findings ✅

The application implements several good security practices:

1. ✅ **SQL Injection Protection:** Proper use of PDO prepared statements throughout
2. ✅ **CSRF Protection:** Comprehensive CSRF token implementation with constant-time comparison
3. ✅ **Password Hashing:** Uses `password_hash()` with bcrypt (should upgrade to argon2id)
4. ✅ **Rate Limiting:** Well-implemented rate limiting for login and password reset
5. ✅ **XSS Protection:** Good use of `htmlspecialchars()` in most views (135 occurrences)
6. ✅ **Authorization Checks:** Consistent ownership verification before data access
7. ✅ **Email Enumeration Protection:** Password reset uses constant response regardless of email validity
8. ✅ **API Authentication:** Proper API key authentication with rate limiting
9. ✅ **Input Validation:** Good validation on authentication and registration forms
10. ✅ **No eval() or assert():** No dangerous PHP functions detected

---

## Security Checklist for Production Deployment

### Must Fix (Critical) ❌
- [ ] Add `session_regenerate_id(true)` on login, register, and password reset
- [ ] Change HTTP to HTTPS in password reset URLs
- [ ] Validate `HTTP_HOST` header and use configured APP_URL
- [ ] Implement strong password requirements (12+ chars, complexity)
- [ ] Fix file upload path traversal vulnerabilities
- [ ] Validate CSV file uploads by content, not MIME type

### Should Fix (High Priority) ⚠️
- [ ] Escape all variables in email templates
- [ ] Add IP-based rate limiting for password reset
- [ ] Configure HTTP security headers in Nginx
- [ ] Reduce database directory permissions to 750
- [ ] Hash API keys in database
- [ ] Add CSRF validation to all file upload endpoints

### Recommended (Medium Priority) ⚡
- [ ] Implement account lockout after failed login attempts
- [ ] Configure secure session settings
- [ ] Add comprehensive input validation to API endpoints
- [ ] Disable error display in production
- [ ] Prevent email enumeration via registration
- [ ] Whitelist allowed database table names

### Best Practices (Low Priority) ℹ️
- [ ] Implement security event logging
- [ ] Add Content Security Policy headers
- [ ] Reduce password reset token lifetime to 10 minutes
- [ ] Add Subresource Integrity for CDN resources
- [ ] Upgrade password hashing to argon2id
- [ ] Implement security monitoring and alerting

---

## Docker Security Recommendations

### Current Issues:
1. Containers run as root by default
2. Overly permissive file permissions (777)
3. No resource limits defined

### Recommendations:
```yaml
# docker-compose.prod.yml
services:
  budget-control:
    user: "1000:1000"
    security_opt:
      - no-new-privileges:true
    cap_drop:
      - ALL
    cap_add:
      - CHOWN
      - SETGID
      - SETUID
    read_only: true
    tmpfs:
      - /tmp
      - /var/run
    deploy:
      resources:
        limits:
          cpus: '2'
          memory: 1G
        reservations:
          cpus: '1'
          memory: 512M
```

---

## Environment Variables Security

### Critical .env Settings:
```ini
# Production settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com  # Must be HTTPS!

# Strong session security
SESSION_LIFETIME=3600  # 1 hour
SESSION_SECURE=true
SESSION_HTTPONLY=true
SESSION_SAMESITE=Strict

# Strong password policy
PASSWORD_MIN_LENGTH=12
PASSWORD_REQUIRE_UPPERCASE=true
PASSWORD_REQUIRE_LOWERCASE=true
PASSWORD_REQUIRE_NUMBER=true
PASSWORD_REQUIRE_SPECIAL=true

# API Security
API_RATE_LIMIT=100
API_KEY_HASH_ALGO=sha256
```

---

## Compliance & Standards

### OWASP Top 10 Coverage:

| Risk | Status | Notes |
|------|--------|-------|
| A01: Broken Access Control | ✅ Good | Proper authorization checks |
| A02: Cryptographic Failures | ⚠️ Partial | Password hashing good, but API keys not hashed |
| A03: Injection | ✅ Good | PDO prepared statements used |
| A04: Insecure Design | ⚡ Fair | Missing account lockout, weak password policy |
| A05: Security Misconfiguration | ❌ Poor | Debug mode, missing headers, wrong permissions |
| A06: Vulnerable Components | ✅ Good | No eval(), modern PHP |
| A07: Authentication Failures | ❌ Poor | No session regeneration, weak passwords |
| A08: Software/Data Integrity | ⚡ Fair | No SRI for CDN resources |
| A09: Logging Failures | ❌ Poor | No security logging |
| A10: SSRF | ✅ Good | No SSRF vectors identified |

---

## Incident Response Plan

### If Compromised:
1. Immediately rotate all API keys and secrets
2. Force password reset for all users
3. Review security logs (once implemented)
4. Audit database for unauthorized changes
5. Check file uploads for malicious content
6. Restore from clean backup if necessary

---

## Conclusion

The Budget Control application has a **solid security foundation** with good practices for SQL injection prevention and CSRF protection. However, **critical vulnerabilities** related to session management, password policies, and file uploads must be addressed before production deployment.

**Priority Actions:**
1. Fix all Critical (❌) issues - **Estimated: 4-6 hours**
2. Fix all High Priority (⚠️) issues - **Estimated: 8-10 hours**
3. Implement security logging and monitoring - **Estimated: 4 hours**
4. Conduct penetration testing before launch - **Estimated: 1-2 days**

**Total Estimated Remediation Time:** 2-3 days for critical/high priority issues

---

## Contact & Support

For questions about this security audit, please contact the security team.

**Next Steps:**
1. Review this report with the development team
2. Create tickets for each vulnerability
3. Prioritize fixes based on severity
4. Re-audit after fixes are implemented
5. Conduct penetration testing before production launch

---

**Document Version:** 1.0
**Last Updated:** 2025-11-15
