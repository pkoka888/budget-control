<?php
use PHPUnit\Framework\TestCase;
use BudgetApp\Middleware\RateLimiter;

class RateLimiterTest extends TestCase
{
    private PDO $db;
    private RateLimiter $rateLimiter;

    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create rate_limits table
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS rate_limits (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                key TEXT NOT NULL,
                attempts INTEGER DEFAULT 0,
                window_start INTEGER NOT NULL,
                created_at TEXT NOT NULL
            )
        ');

        $this->db->exec('CREATE INDEX idx_key ON rate_limits(key)');
        $this->db->exec('CREATE INDEX idx_window_start ON rate_limits(window_start)');

        $this->rateLimiter = new RateLimiter($this->db);

        // Reset server variables
        $_SERVER = [];
    }

    protected function tearDown(): void
    {
        $this->db = null;
        $_SERVER = [];
    }

    public function testInitialAttemptIsAllowed(): void
    {
        $result = $this->rateLimiter->checkLimit('test_key', 5, 60);

        $this->assertTrue($result);
    }

    public function testMultipleAttemptsWithinLimit(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $result = $this->rateLimiter->checkLimit('test_key', 5, 60);
            $this->assertTrue($result, "Attempt " . ($i + 1) . " should be allowed");
        }

        // 5th attempt should still be allowed
        $result = $this->rateLimiter->checkLimit('test_key', 5, 60);
        $this->assertTrue($result);
    }

    public function testExceedingLimitReturnsFalse(): void
    {
        // Use up all 5 attempts
        for ($i = 0; $i < 5; $i++) {
            $this->rateLimiter->checkLimit('test_key', 5, 60);
        }

        // 6th attempt should be blocked
        $result = $this->rateLimiter->checkLimit('test_key', 5, 60);
        $this->assertFalse($result);
    }

    public function testDifferentKeysAreIndependent(): void
    {
        // Max out attempts for key1
        for ($i = 0; $i < 5; $i++) {
            $this->rateLimiter->checkLimit('key1', 5, 60);
        }

        // key2 should still work
        $result = $this->rateLimiter->checkLimit('key2', 5, 60);
        $this->assertTrue($result);
    }

    public function testRateLimitResetsAfterWindow(): void
    {
        // Create a rate limit record in the past
        $pastTime = time() - 61; // 61 seconds ago (outside 60s window)
        $this->db->exec("
            INSERT INTO rate_limits (key, attempts, window_start, created_at)
            VALUES ('test_key', 5, $pastTime, datetime('now'))
        ");

        // Should be allowed because window expired
        $result = $this->rateLimiter->checkLimit('test_key', 5, 60);
        $this->assertTrue($result);
    }

    public function testLoginRateLimitMethod(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';

        // Use up login attempts (5 per 15 minutes)
        for ($i = 0; $i < 5; $i++) {
            $result = $this->rateLimiter->checkLoginLimit();
            $this->assertTrue($result, "Login attempt " . ($i + 1) . " should be allowed");
        }

        // 6th attempt should fail
        $result = $this->rateLimiter->checkLoginLimit();
        $this->assertFalse($result);
    }

    public function testPasswordResetRateLimitMethod(): void
    {
        $email = 'test@example.com';

        // Use up reset attempts (3 per hour)
        for ($i = 0; $i < 3; $i++) {
            $result = $this->rateLimiter->checkPasswordResetLimit($email);
            $this->assertTrue($result, "Reset attempt " . ($i + 1) . " should be allowed");
        }

        // 4th attempt should fail
        $result = $this->rateLimiter->checkPasswordResetLimit($email);
        $this->assertFalse($result);
    }

    public function testApiRateLimitMethod(): void
    {
        $apiKey = 'test_api_key';

        // API allows 100 requests per hour
        for ($i = 0; $i < 100; $i++) {
            $result = $this->rateLimiter->checkApiLimit($apiKey);
            $this->assertTrue($result, "API request " . ($i + 1) . " should be allowed");
        }

        // 101st request should fail
        $result = $this->rateLimiter->checkApiLimit($apiKey);
        $this->assertFalse($result);
    }

    public function testRequireLoginLimitExitsOn429(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';

        // Max out attempts
        for ($i = 0; $i < 5; $i++) {
            $this->rateLimiter->checkLoginLimit();
        }

        ob_start();
        try {
            $this->rateLimiter->requireLoginLimit();
            $this->fail('Expected exit() to be called');
        } catch (\Exception $e) {
            // Exit might throw exception in testing environment
        }
        $output = ob_get_clean();

        $this->assertStringContainsString('Rate limit exceeded', $output);
        $this->assertStringContainsString('Too many login attempts', $output);
    }

    public function testRequirePasswordResetLimitExitsOn429(): void
    {
        $email = 'test@example.com';

        // Max out attempts
        for ($i = 0; $i < 3; $i++) {
            $this->rateLimiter->checkPasswordResetLimit($email);
        }

        ob_start();
        try {
            $this->rateLimiter->requirePasswordResetLimit($email);
            $this->fail('Expected exit() to be called');
        } catch (\Exception $e) {
            // Exit might throw exception in testing environment
        }
        $output = ob_get_clean();

        $this->assertStringContainsString('Rate limit exceeded', $output);
    }

    public function testRequireApiLimitExitsOn429(): void
    {
        $apiKey = 'test_key';

        // Max out attempts
        for ($i = 0; $i < 100; $i++) {
            $this->rateLimiter->checkApiLimit($apiKey);
        }

        ob_start();
        try {
            $this->rateLimiter->requireApiLimit($apiKey);
            $this->fail('Expected exit() to be called');
        } catch (\Exception $e) {
            // Exit might throw exception in testing environment
        }
        $output = ob_get_clean();

        $this->assertStringContainsString('Rate limit exceeded', $output);
    }

    public function testCleanupOldRecords(): void
    {
        // Insert old records
        $oldTime = time() - 7200; // 2 hours ago
        for ($i = 0; $i < 5; $i++) {
            $this->db->exec("
                INSERT INTO rate_limits (key, attempts, window_start, created_at)
                VALUES ('old_key_$i', 5, $oldTime, datetime('now', '-2 hours'))
            ");
        }

        // Insert recent records
        for ($i = 0; $i < 3; $i++) {
            $this->rateLimiter->checkLimit("new_key_$i", 5, 60);
        }

        // Count before cleanup
        $stmt = $this->db->query('SELECT COUNT(*) FROM rate_limits');
        $countBefore = $stmt->fetchColumn();
        $this->assertEquals(8, $countBefore);

        // Run cleanup
        $this->rateLimiter->cleanup();

        // Count after cleanup (only recent records should remain)
        $stmt = $this->db->query('SELECT COUNT(*) FROM rate_limits');
        $countAfter = $stmt->fetchColumn();
        $this->assertLessThan($countBefore, $countAfter);
    }

    public function testGetClientIpFromRemoteAddr(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';

        $reflection = new ReflectionClass($this->rateLimiter);
        $method = $reflection->getMethod('getClientIp');
        $method->setAccessible(true);

        $ip = $method->invoke($this->rateLimiter);
        $this->assertEquals('192.168.1.100', $ip);
    }

    public function testGetClientIpFromXForwardedFor(): void
    {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.1, 192.168.1.100';
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';

        $reflection = new ReflectionClass($this->rateLimiter);
        $method = $reflection->getMethod('getClientIp');
        $method->setAccessible(true);

        $ip = $method->invoke($this->rateLimiter);
        $this->assertEquals('203.0.113.1', $ip);
    }

    public function testGetClientIpFromXRealIp(): void
    {
        $_SERVER['HTTP_X_REAL_IP'] = '203.0.113.2';
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';

        $reflection = new ReflectionClass($this->rateLimiter);
        $method = $reflection->getMethod('getClientIp');
        $method->setAccessible(true);

        $ip = $method->invoke($this->rateLimiter);
        $this->assertEquals('203.0.113.2', $ip);
    }

    public function testGetClientIpDefaultsToUnknown(): void
    {
        // No IP headers set
        $_SERVER = [];

        $reflection = new ReflectionClass($this->rateLimiter);
        $method = $reflection->getMethod('getClientIp');
        $method->setAccessible(true);

        $ip = $method->invoke($this->rateLimiter);
        $this->assertEquals('unknown', $ip);
    }

    public function testFormatDurationSeconds(): void
    {
        $reflection = new ReflectionClass($this->rateLimiter);
        $method = $reflection->getMethod('formatDuration');
        $method->setAccessible(true);

        $result = $method->invoke($this->rateLimiter, 45);
        $this->assertEquals('45 seconds', $result);
    }

    public function testFormatDurationMinutes(): void
    {
        $reflection = new ReflectionClass($this->rateLimiter);
        $method = $reflection->getMethod('formatDuration');
        $method->setAccessible(true);

        $result = $method->invoke($this->rateLimiter, 180);
        $this->assertEquals('3 minutes', $result);
    }

    public function testFormatDurationHours(): void
    {
        $reflection = new ReflectionClass($this->rateLimiter);
        $method = $reflection->getMethod('formatDuration');
        $method->setAccessible(true);

        $result = $method->invoke($this->rateLimiter, 7200);
        $this->assertEquals('2 hours', $result);
    }

    public function testConcurrentRequestsFromSameIp(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.50';

        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = $this->rateLimiter->checkLoginLimit();
        }

        // First 5 should succeed
        $this->assertTrue($results[0]);
        $this->assertTrue($results[1]);
        $this->assertTrue($results[2]);
        $this->assertTrue($results[3]);
        $this->assertTrue($results[4]);

        // Rest should fail
        $this->assertFalse($results[5]);
        $this->assertFalse($results[9]);
    }

    public function testMultipleEmailsHaveIndependentLimits(): void
    {
        // Max out attempts for email1
        for ($i = 0; $i < 3; $i++) {
            $this->rateLimiter->checkPasswordResetLimit('user1@example.com');
        }

        // email2 should still work
        $result = $this->rateLimiter->checkPasswordResetLimit('user2@example.com');
        $this->assertTrue($result);

        // email1 should be blocked
        $result = $this->rateLimiter->checkPasswordResetLimit('user1@example.com');
        $this->assertFalse($result);
    }

    public function testRateLimitPersistsAcrossInstances(): void
    {
        // Create first instance and make attempts
        $limiter1 = new RateLimiter($this->db);
        for ($i = 0; $i < 3; $i++) {
            $limiter1->checkLimit('persistent_key', 5, 60);
        }

        // Create second instance - should see existing attempts
        $limiter2 = new RateLimiter($this->db);
        for ($i = 0; $i < 2; $i++) {
            $limiter2->checkLimit('persistent_key', 5, 60);
        }

        // Next attempt should be blocked (3 + 2 = 5, so 6th fails)
        $result = $limiter2->checkLimit('persistent_key', 5, 60);
        $this->assertFalse($result);
    }

    public function testZeroAttemptsAllowedAlwaysFails(): void
    {
        $result = $this->rateLimiter->checkLimit('test_key', 0, 60);
        $this->assertFalse($result);
    }

    public function testVeryLargeWindowPeriod(): void
    {
        $largeWindow = 86400 * 7; // 1 week

        for ($i = 0; $i < 10; $i++) {
            $result = $this->rateLimiter->checkLimit('large_window_key', 10, $largeWindow);
            $this->assertTrue($result);
        }

        // 11th attempt should fail
        $result = $this->rateLimiter->checkLimit('large_window_key', 10, $largeWindow);
        $this->assertFalse($result);
    }

    public function testRetryAfterHeaderIsCorrect(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';

        // Max out login attempts
        for ($i = 0; $i < 5; $i++) {
            $this->rateLimiter->checkLoginLimit();
        }

        ob_start();
        try {
            $this->rateLimiter->requireLoginLimit();
        } catch (\Exception $e) {
            // Catch exit
        }
        $output = ob_get_clean();

        // Verify response contains retry_after
        $json = json_decode($output, true);
        $this->assertArrayHasKey('retry_after', $json);
        $this->assertEquals(900, $json['retry_after']); // 15 minutes = 900 seconds
    }

    public function testSpecialCharactersInKey(): void
    {
        $specialKey = 'user@example.com:reset:192.168.1.1';

        $result = $this->rateLimiter->checkLimit($specialKey, 5, 60);
        $this->assertTrue($result);

        // Should be able to retrieve same key
        for ($i = 0; $i < 4; $i++) {
            $this->rateLimiter->checkLimit($specialKey, 5, 60);
        }

        // 6th should fail
        $result = $this->rateLimiter->checkLimit($specialKey, 5, 60);
        $this->assertFalse($result);
    }
}
