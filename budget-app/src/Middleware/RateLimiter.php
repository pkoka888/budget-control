<?php

namespace BudgetApp\Middleware;

use BudgetApp\Database;

/**
 * Rate Limiter Middleware
 *
 * Protects against brute force attacks and API abuse by:
 * - Limiting requests per IP address or user
 * - Configurable time windows and attempt limits
 * - Clean up of old rate limit records
 */
class RateLimiter
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Check if a rate limit has been exceeded
     *
     * @param string $key Unique identifier (e.g., "login:user@example.com" or "api:192.168.1.1")
     * @param int $maxAttempts Maximum number of attempts allowed
     * @param int $windowSeconds Time window in seconds
     * @return bool True if within limit, false if exceeded
     */
    public function checkLimit(string $key, int $maxAttempts, int $windowSeconds): bool
    {
        // Calculate window start time
        $windowStart = date('Y-m-d H:i:s', time() - $windowSeconds);

        // Ensure rate_limits table exists
        $this->ensureTableExists();

        // Count recent attempts
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as count FROM rate_limits
             WHERE key = ? AND attempted_at > ?"
        );
        $result = $stmt->execute([$key, $windowStart]);
        $row = $result->fetchArray(SQLITE3_ASSOC);
        $count = (int) $row['count'];

        // Check if limit exceeded
        if ($count >= $maxAttempts) {
            return false;
        }

        // Log this attempt
        $stmt = $this->db->prepare(
            "INSERT INTO rate_limits (key, attempted_at) VALUES (?, ?)"
        );
        $stmt->execute([$key, date('Y-m-d H:i:s')]);

        return true;
    }

    /**
     * Require that rate limit is not exceeded
     * Terminates execution with 429 if limit exceeded
     *
     * @param string $key Unique identifier
     * @param int $maxAttempts Maximum number of attempts
     * @param int $windowSeconds Time window in seconds
     * @return void
     */
    public function requireLimit(string $key, int $maxAttempts, int $windowSeconds): void
    {
        if (!$this->checkLimit($key, $maxAttempts, $windowSeconds)) {
            $retryAfter = $windowSeconds;

            http_response_code(429);
            header('Content-Type: application/json');
            header("Retry-After: $retryAfter");
            echo json_encode([
                'error' => 'Rate limit exceeded',
                'message' => "Too many attempts. Please try again in " . $this->formatDuration($windowSeconds) . ".",
                'retry_after' => $retryAfter
            ]);
            exit;
        }
    }

    /**
     * Get remaining attempts for a key
     *
     * @param string $key Unique identifier
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $windowSeconds Time window in seconds
     * @return int Number of remaining attempts
     */
    public function getRemainingAttempts(string $key, int $maxAttempts, int $windowSeconds): int
    {
        $windowStart = date('Y-m-d H:i:s', time() - $windowSeconds);

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as count FROM rate_limits
             WHERE key = ? AND attempted_at > ?"
        );
        $result = $stmt->execute([$key, $windowStart]);
        $row = $result->fetchArray(SQLITE3_ASSOC);
        $count = (int) $row['count'];

        return max(0, $maxAttempts - $count);
    }

    /**
     * Reset rate limit for a specific key
     * Useful after successful login
     *
     * @param string $key Unique identifier
     * @return void
     */
    public function reset(string $key): void
    {
        $stmt = $this->db->prepare("DELETE FROM rate_limits WHERE key = ?");
        $stmt->execute([$key]);
    }

    /**
     * Clean up old rate limit records
     * Should be called periodically (e.g., via cron)
     *
     * @param int $olderThanSeconds Delete records older than this
     * @return int Number of records deleted
     */
    public function cleanup(int $olderThanSeconds = 86400): int
    {
        $cutoffTime = date('Y-m-d H:i:s', time() - $olderThanSeconds);

        $stmt = $this->db->prepare("DELETE FROM rate_limits WHERE attempted_at < ?");
        $stmt->execute([$cutoffTime]);

        return $this->db->changes();
    }

    /**
     * Ensure rate_limits table exists
     *
     * @return void
     */
    private function ensureTableExists(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS rate_limits (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                key TEXT NOT NULL,
                attempted_at TEXT NOT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Create index for performance
        $this->db->exec("
            CREATE INDEX IF NOT EXISTS idx_rate_limits_key_time
            ON rate_limits(key, attempted_at)
        ");
    }

    /**
     * Format duration in human-readable format
     *
     * @param int $seconds Duration in seconds
     * @return string Formatted duration
     */
    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "$seconds seconds";
        } elseif ($seconds < 3600) {
            $minutes = ceil($seconds / 60);
            return "$minutes " . ($minutes == 1 ? 'minute' : 'minutes');
        } else {
            $hours = ceil($seconds / 3600);
            return "$hours " . ($hours == 1 ? 'hour' : 'hours');
        }
    }

    /**
     * Get client IP address
     * Checks proxy headers as well
     *
     * @return string IP address
     */
    public static function getClientIp(): string
    {
        // Check for proxy headers
        $headers = [
            'HTTP_CF_CONNECTING_IP',  // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Handle comma-separated IPs (X-Forwarded-For can contain multiple IPs)
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                return $ip;
            }
        }

        return 'unknown';
    }

    /**
     * Preset: Login rate limiting (5 attempts per 15 minutes)
     *
     * @param string $identifier Email or username
     * @return void
     */
    public function requireLoginLimit(string $identifier): void
    {
        $ip = self::getClientIp();
        $this->requireLimit("login:$identifier:$ip", 5, 900); // 900 seconds = 15 minutes
    }

    /**
     * Preset: API rate limiting (100 requests per hour)
     *
     * @param string $identifier API key or user ID
     * @return void
     */
    public function requireApiLimit(string $identifier): void
    {
        $this->requireLimit("api:$identifier", 100, 3600); // 3600 seconds = 1 hour
    }

    /**
     * Preset: Password reset limiting (3 attempts per hour)
     *
     * @param string $email Email address
     * @return void
     */
    public function requirePasswordResetLimit(string $email): void
    {
        $ip = self::getClientIp();
        $this->requireLimit("password_reset:$email:$ip", 3, 3600);
    }
}
