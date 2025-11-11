<?php

namespace BudgetApp\Middleware;

/**
 * CSRF Protection Middleware
 *
 * Protects against Cross-Site Request Forgery attacks by:
 * - Generating secure tokens for forms
 * - Validating tokens on state-changing requests
 * - Using constant-time comparison to prevent timing attacks
 */
class CsrfProtection
{
    private const TOKEN_LENGTH = 32;
    private const SESSION_KEY = 'csrf_token';

    /**
     * Generate a new CSRF token and store it in the session
     *
     * @return string The generated token
     */
    public static function generateToken(): string
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Generate cryptographically secure random token
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));

        // Store in session
        $_SESSION[self::SESSION_KEY] = $token;

        return $token;
    }

    /**
     * Get the current CSRF token from session
     * If none exists, generate a new one
     *
     * @return string The current token
     */
    public static function getToken(): string
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Return existing token or generate new one
        if (!isset($_SESSION[self::SESSION_KEY])) {
            return self::generateToken();
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Validate a CSRF token against the session token
     * Uses constant-time comparison to prevent timing attacks
     *
     * @param string $token The token to validate
     * @return bool True if valid, false otherwise
     */
    public static function validateToken(string $token): bool
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if session token exists
        if (!isset($_SESSION[self::SESSION_KEY])) {
            return false;
        }

        // Use constant-time comparison to prevent timing attacks
        return hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    /**
     * Require a valid CSRF token
     * Terminates execution with 403 if token is invalid
     *
     * Checks for token in:
     * 1. POST data (csrf_token field)
     * 2. HTTP header (X-CSRF-Token)
     *
     * @return void
     */
    public static function requireToken(): void
    {
        // Only check on state-changing methods
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return;
        }

        // Get token from POST data or header
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        // Validate token
        if (!self::validateToken($token)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Invalid or missing CSRF token',
                'message' => 'Your session may have expired. Please refresh the page and try again.'
            ]);
            exit;
        }
    }

    /**
     * Generate HTML hidden input field with CSRF token
     * Convenient helper for forms
     *
     * @return string HTML input field
     */
    public static function field(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Get token as meta tag for JavaScript usage
     * For AJAX requests
     *
     * @return string HTML meta tag
     */
    public static function metaTag(): string
    {
        $token = self::getToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Regenerate CSRF token
     * Call this after login/logout to prevent session fixation
     *
     * @return string The new token
     */
    public static function regenerateToken(): string
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Clear old token
        unset($_SESSION[self::SESSION_KEY]);

        // Generate new token
        return self::generateToken();
    }
}
