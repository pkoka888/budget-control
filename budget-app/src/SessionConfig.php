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
                error_log("[SECURITY] Session hijacking attempt detected - Fingerprint mismatch");
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
            'budget_control_salt_v1'  // Random salt
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
