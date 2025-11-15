# Handoff Request: Secure Session Configuration

**Date:** 2025-11-15
**From:** Windows Orchestrator (Claude Code AI)
**To:** Debian Server (claude user - Code changes only)
**Priority:** 🟡 MEDIUM - Security hardening
**Status:** ⏳ PENDING IMPLEMENTATION

---

## Current Situation

### ✅ What's Already Secure
- **Session regeneration on login** - Already implemented in AuthController.php:
  - `session_regenerate_id(true)` called after successful login
  - Prevents session fixation attacks
- **CSRF token regeneration** - Called after login/logout
- **Session-based authentication** - Working correctly

### ❌ What's Missing
- **No secure session configuration** - `index.php` just calls `session_start()` without any security settings
- **No session timeout** - Sessions never expire (security risk)
- **No HTTPOnly cookie flag** - JavaScript can access session cookies (XSS risk)
- **No Secure flag** - Cookies sent over HTTP (will be needed for HTTPS)
- **No SameSite attribute** - Vulnerable to CSRF if CSRF protection fails

---

## Current Code

**File:** `budget-app/public/index.php` (line 9)
```php
// Start session
session_start();
```

That's it. No security configuration at all.

---

## Requested Action

### Task 1: Add Secure Session Configuration (30 min)

**Edit:** `budget-app/public/index.php`

**Replace this:**
```php
// Start session
session_start();
```

**With this:**
```php
// Configure secure session settings
// Must be set BEFORE session_start()

// Session cookie security
ini_set('session.cookie_httponly', '1');  // Prevent JavaScript access (XSS protection)
ini_set('session.cookie_samesite', 'Strict');  // CSRF protection (alternative: 'Lax')
ini_set('session.use_strict_mode', '1');  // Reject uninitialized session IDs

// Session cookie secure flag (set to 1 when HTTPS is enabled)
// For now, keep at 0 since site runs on HTTP
// TODO: Set to 1 after SSL/HTTPS is configured
ini_set('session.cookie_secure', '0');

// Session ID security
ini_set('session.use_only_cookies', '1');  // Don't allow session ID in URL
ini_set('session.use_trans_sid', '0');  // Disable transparent session ID

// Session lifetime (1 hour = 3600 seconds)
ini_set('session.gc_maxlifetime', '3600');
ini_set('session.cookie_lifetime', '0');  // Delete cookie when browser closes

// Session name (custom name, don't use default PHPSESSID)
ini_set('session.name', 'BUDGET_SESSION');

// Session entropy (cryptographically secure session IDs)
// PHP 7.1+ automatically uses /dev/urandom, but set explicitly for clarity
ini_set('session.entropy_file', '/dev/urandom');
ini_set('session.entropy_length', '32');

// Start session with configured settings
session_start();

// Implement session timeout check
// Check if session has been idle too long
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 3600)) {
    // Session idle for more than 1 hour
    session_unset();     // Clear session data
    session_destroy();   // Destroy session
    session_start();     // Start new session
}
// Update last activity time
$_SESSION['LAST_ACTIVITY'] = time();

// Optional: Validate session fingerprint (prevents session hijacking)
// Create fingerprint on first session
if (!isset($_SESSION['FINGERPRINT'])) {
    $_SESSION['FINGERPRINT'] = md5(
        $_SERVER['HTTP_USER_AGENT'] ?? '' .
        $_SERVER['REMOTE_ADDR'] ?? ''
    );
}
// Verify fingerprint on subsequent requests
else {
    $currentFingerprint = md5(
        $_SERVER['HTTP_USER_AGENT'] ?? '' .
        $_SERVER['REMOTE_ADDR'] ?? ''
    );
    if ($_SESSION['FINGERPRINT'] !== $currentFingerprint) {
        // Possible session hijacking attempt
        session_unset();
        session_destroy();
        session_start();
        // Optionally log this event
    }
}
```

### Alternative: Create SessionConfig Class (Better Approach)

Instead of putting all config in `index.php`, create a dedicated class.

**Create:** `src/SessionConfig.php`

```php
<?php
namespace BudgetApp;

/**
 * Session Configuration
 *
 * Provides secure session management with timeout and fingerprinting
 */
class SessionConfig
{
    // Session timeout in seconds (1 hour)
    private const SESSION_TIMEOUT = 3600;

    // Whether to use session fingerprinting (prevents session hijacking)
    private const USE_FINGERPRINTING = true;

    /**
     * Initialize secure session
     * Must be called before any session operations
     */
    public static function start(): void
    {
        // Only configure if session not already started
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        self::configure();
        session_start();
        self::validateSession();
    }

    /**
     * Configure session security settings
     * Must be called BEFORE session_start()
     */
    private static function configure(): void
    {
        // Cookie security flags
        ini_set('session.cookie_httponly', '1');  // Prevent JavaScript access
        ini_set('session.cookie_samesite', 'Strict');  // CSRF protection
        ini_set('session.use_strict_mode', '1');  // Reject uninitialized session IDs

        // Secure flag (HTTPS only) - set to 0 for HTTP, 1 for HTTPS
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                   || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        ini_set('session.cookie_secure', $isHttps ? '1' : '0');

        // Session ID security
        ini_set('session.use_only_cookies', '1');  // No session ID in URL
        ini_set('session.use_trans_sid', '0');  // Disable transparent session ID

        // Session lifetime
        ini_set('session.gc_maxlifetime', (string)self::SESSION_TIMEOUT);
        ini_set('session.cookie_lifetime', '0');  // Delete on browser close

        // Custom session name (don't use default PHPSESSID)
        ini_set('session.name', 'BUDGET_SESSION');

        // Cryptographically secure session IDs
        ini_set('session.entropy_file', '/dev/urandom');
        ini_set('session.entropy_length', '32');
    }

    /**
     * Validate session timeout and fingerprint
     */
    private static function validateSession(): void
    {
        // Check session timeout
        if (isset($_SESSION['LAST_ACTIVITY'])) {
            $elapsed = time() - $_SESSION['LAST_ACTIVITY'];
            if ($elapsed > self::SESSION_TIMEOUT) {
                // Session expired
                self::destroy();
                session_start();  // Start fresh session
            }
        }

        // Update last activity timestamp
        $_SESSION['LAST_ACTIVITY'] = time();

        // Session fingerprinting (optional but recommended)
        if (self::USE_FINGERPRINTING) {
            self::validateFingerprint();
        }
    }

    /**
     * Validate session fingerprint to prevent session hijacking
     */
    private static function validateFingerprint(): void
    {
        $currentFingerprint = self::generateFingerprint();

        if (!isset($_SESSION['FINGERPRINT'])) {
            // First request - create fingerprint
            $_SESSION['FINGERPRINT'] = $currentFingerprint;
        } else {
            // Verify fingerprint matches
            if ($_SESSION['FINGERPRINT'] !== $currentFingerprint) {
                // Possible session hijacking - destroy session
                self::destroy();
                session_start();

                // Optionally log this event for security monitoring
                error_log("Session hijacking attempt detected - Fingerprint mismatch");
            }
        }
    }

    /**
     * Generate session fingerprint based on user agent and IP
     *
     * Note: IP-based fingerprinting can cause issues with:
     * - Mobile users switching between WiFi and cellular
     * - Users behind load balancers with changing IPs
     *
     * Consider making this configurable based on security requirements
     */
    private static function generateFingerprint(): string
    {
        return hash('sha256',
            ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') .
            ($_SERVER['REMOTE_ADDR'] ?? 'unknown') .
            'salt_here_change_this_to_random_value'  // Add random salt
        );
    }

    /**
     * Destroy current session
     */
    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];

            // Delete session cookie
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }

            session_destroy();
        }
    }

    /**
     * Regenerate session ID (call after login/privilege escalation)
     */
    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            // Reset fingerprint for new session ID
            if (self::USE_FINGERPRINTING) {
                $_SESSION['FINGERPRINT'] = self::generateFingerprint();
            }
        }
    }

    /**
     * Get session timeout in seconds
     */
    public static function getTimeout(): int
    {
        return self::SESSION_TIMEOUT;
    }
}
```

**Then update index.php to use it:**

```php
<?php
// ... existing code ...

// Autoload composer dependencies
require_once BASE_PATH . '/vendor/autoload.php';

// Configure and start secure session
use BudgetApp\SessionConfig;
SessionConfig::start();

// ... rest of index.php ...
```

### Task 2: Update AuthController to Use SessionConfig (15 min)

**Edit:** `src/Controllers/AuthController.php`

Find lines that call `session_regenerate_id(true)` and replace with:

```php
// Before (current code):
session_regenerate_id(true);

// After (using SessionConfig):
\BudgetApp\SessionConfig::regenerate();
```

This ensures fingerprint is updated after session regeneration.

### Task 3: Update Auth.php Logout Method (10 min)

**Edit:** `src/Auth.php`

Find the logout method and ensure it properly destroys session:

```php
// If using SessionConfig class:
public function logout(): void {
    \BudgetApp\SessionConfig::destroy();
}
```

---

## Implementation Strategy

**Recommended: Use SessionConfig Class Approach** (cleaner, more maintainable)

1. **Create SessionConfig.php** (30 min)
   - Create `src/SessionConfig.php`
   - Copy code from handoff above
   - Test syntax: `php -l src/SessionConfig.php`

2. **Update index.php** (5 min)
   - Replace `session_start()` with `SessionConfig::start()`

3. **Update AuthController** (10 min)
   - Replace `session_regenerate_id()` with `SessionConfig::regenerate()`

4. **Update Auth::logout()** (5 min)
   - Use `SessionConfig::destroy()`

5. **Test Session Functionality** (20 min)
   - Test login/logout
   - Test session timeout (change SESSION_TIMEOUT to 60 seconds for testing)
   - Test session survives page refresh
   - Test session expires after timeout

**Total Time: 1 hour 10 min**

---

## Testing Checklist

### Functional Tests (Should Work)
- [ ] Login works correctly
- [ ] Stay logged in after page refresh (within timeout)
- [ ] Can navigate between pages while logged in
- [ ] Logout works correctly
- [ ] Session cookies have correct security flags (check browser dev tools)

### Security Tests
- [ ] Session cookie has `HttpOnly` flag (check browser dev tools → Application → Cookies)
- [ ] Session cookie has `SameSite=Strict` attribute
- [ ] Session expires after 1 hour of inactivity
  - Login, wait 61 minutes (or change timeout to 60 seconds for testing), refresh page
  - Should be logged out
- [ ] Session survives browser refresh (within timeout)
- [ ] Logout properly destroys session
- [ ] Cannot reuse old session ID after logout

### Browser Dev Tools Check
1. Open browser dev tools (F12)
2. Go to Application → Cookies
3. Find `BUDGET_SESSION` cookie
4. Verify flags:
   - ✅ HttpOnly: Yes
   - ✅ Secure: No (will be Yes after HTTPS)
   - ✅ SameSite: Strict
   - ✅ Path: /
   - ✅ Expires: Session (when browser closes)

---

## Configuration Options

### Session Timeout
Default: 3600 seconds (1 hour)

Adjust based on security vs usability:
- **High security**: 900 (15 min)
- **Balanced**: 3600 (1 hour) - recommended
- **User-friendly**: 7200 (2 hours)

Change in `SessionConfig::SESSION_TIMEOUT`

### Session Fingerprinting
Default: Enabled

**Pros:**
- Prevents session hijacking
- Detects if session cookie is stolen

**Cons:**
- Can cause logout for mobile users switching networks
- Issues with users behind load balancers

Disable by setting:
```php
private const USE_FINGERPRINTING = false;
```

### Secure Cookie Flag
Currently: Auto-detect (based on HTTPS)

After HTTPS is enabled:
- Will automatically set to `1`
- Cookies only sent over HTTPS

---

## Edge Cases

### 1. AJAX Requests During Session Timeout
If session expires while user is on page:
- Next AJAX request will fail (401 Unauthorized)
- Frontend should detect and redirect to login

Consider adding:
```javascript
// In main JS file
fetch('/api/endpoint', {
    // ...
}).catch(error => {
    if (error.status === 401) {
        window.location.href = '/login';
    }
});
```

### 2. Remember Me Functionality
Current implementation doesn't have "Remember Me":
- Sessions expire when browser closes
- For "Remember Me", would need to:
  - Set `session.cookie_lifetime` to longer value
  - Store additional token in database
  - (NOT IMPLEMENTED YET - future enhancement)

### 3. Multiple Tabs
Session is shared across tabs:
- Timeout applies to ALL tabs
- Activity in one tab resets timeout for all tabs
- This is expected behavior

---

## Future Enhancements

### 1. When HTTPS is Enabled
Update SessionConfig.php to force HTTPS:
```php
ini_set('session.cookie_secure', '1');
```

### 2. Session Storage
Consider moving from file-based to database or Redis:
```php
ini_set('session.save_handler', 'redis');
ini_set('session.save_path', 'tcp://127.0.0.1:6379');
```
(Requires Redis installation - not in scope for now)

### 3. Security Logging
Log session security events:
- Failed fingerprint validation
- Session hijacking attempts
- Unusual session patterns

Add to SessionConfig::validateFingerprint():
```php
error_log("[SECURITY] Session hijacking attempt - User: " . ($_SESSION['user_id'] ?? 'unknown'));
```

---

## Git Workflow

```bash
cd /var/www/budget-control
git checkout -b fix/session-security

# After creating SessionConfig:
git add src/SessionConfig.php
git commit -m "Add SessionConfig class for secure session management

- HTTPOnly, Secure, SameSite cookie flags
- Session timeout (1 hour inactivity)
- Session fingerprinting (prevents hijacking)
- Cryptographically secure session IDs
- Proper session destruction on logout"

# After updating index.php:
git add public/index.php
git commit -m "Use SessionConfig for secure session initialization"

# After updating controllers:
git add src/Controllers/AuthController.php src/Auth.php
git commit -m "Update auth logic to use SessionConfig

- Use SessionConfig::regenerate() after login
- Use SessionConfig::destroy() on logout
- Ensures session security across auth operations"

# When complete:
git push origin fix/session-security
```

---

## Verification

After implementation:

- [ ] `SessionConfig.php` exists and has no syntax errors
- [ ] `index.php` uses `SessionConfig::start()`
- [ ] `AuthController` uses `SessionConfig::regenerate()`
- [ ] `Auth::logout()` uses `SessionConfig::destroy()`
- [ ] Browser shows session cookie with security flags
- [ ] Login/logout functionality works
- [ ] Session times out after inactivity
- [ ] No PHP errors in Apache error log

---

## Completion Report

When done, create: `HANDOFF-DEBIAN-2025-11-15-session-security-COMPLETED.md`

Include:
- ✅ SessionConfig class created
- ✅ index.php updated
- ✅ AuthController updated
- ✅ Auth.php updated
- 📋 Cookie security flags verified (screenshot from browser dev tools)
- ✅ Testing results
- 📊 Git commit hashes
- ⚠️ Any issues encountered

---

**Priority:** 🟡 MEDIUM
**Impact:** Hardens session security against hijacking and XSS
**Estimated Time:** 1 hour 10 min
**Complexity:** Low-Medium

---

**END OF HANDOFF REQUEST**
