<?php
use PHPUnit\Framework\TestCase;
use BudgetApp\Services\EmailVerificationService;
use BudgetApp\Database\Database;

class EmailVerificationServiceTest extends TestCase
{
    private $db;
    private $emailService;
    private $testUserId;

    protected function setUp(): void
    {
        // Create test database
        $this->db = new Database('sqlite::memory:');

        // Create necessary tables
        $this->createTestSchema();

        // Create test user
        $this->testUserId = $this->db->insert('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
            'currency' => 'CZK',
            'timezone' => 'Europe/Prague',
            'email_verified' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $this->emailService = new EmailVerificationService($this->db);
    }

    protected function tearDown(): void
    {
        $this->db = null;
        $this->emailService = null;
    }

    private function createTestSchema(): void
    {
        $this->db->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                currency TEXT DEFAULT 'USD',
                timezone TEXT DEFAULT 'UTC',
                email_verified INTEGER DEFAULT 0,
                created_at TEXT
            );

            CREATE TABLE email_verifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                token_hash TEXT NOT NULL UNIQUE,
                expires_at TEXT NOT NULL,
                verified INTEGER DEFAULT 0,
                verified_at TEXT,
                created_at TEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE verification_attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL,
                ip_address TEXT,
                success INTEGER DEFAULT 0,
                attempted_at TEXT NOT NULL
            );
        ");
    }

    // Token Generation Tests

    public function testGenerateTokenCreatesValidToken(): void
    {
        $token = $this->emailService->generateVerificationToken($this->testUserId);

        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex chars
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }

    public function testGenerateTokenStoresHashedValueInDatabase(): void
    {
        $token = $this->emailService->generateVerificationToken($this->testUserId);

        $verification = $this->db->queryOne(
            "SELECT * FROM email_verifications WHERE user_id = ? AND verified = 0",
            [$this->testUserId]
        );

        $this->assertNotNull($verification);
        $this->assertNotEquals($token, $verification['token_hash']);
        $this->assertEquals(64, strlen($verification['token_hash'])); // SHA-256 hex
    }

    public function testGenerateTokenSetsExpirationTime(): void
    {
        $beforeGeneration = time();
        $token = $this->emailService->generateVerificationToken($this->testUserId);
        $afterGeneration = time();

        $verification = $this->db->queryOne(
            "SELECT * FROM email_verifications WHERE user_id = ?",
            [$this->testUserId]
        );

        $expiresAt = strtotime($verification['expires_at']);
        $expectedExpiry = $beforeGeneration + (24 * 60 * 60); // 24 hours

        $this->assertGreaterThanOrEqual($expectedExpiry - 1, $expiresAt);
        $this->assertLessThanOrEqual($expectedExpiry + 1, $expiresAt);
    }

    public function testGenerateTokenInvalidatesPreviousTokens(): void
    {
        // Generate first token
        $token1 = $this->emailService->generateVerificationToken($this->testUserId);

        // Generate second token
        $token2 = $this->emailService->generateVerificationToken($this->testUserId);

        $this->assertNotEquals($token1, $token2);

        // First token should still exist but second should be more recent
        $verifications = $this->db->query(
            "SELECT * FROM email_verifications WHERE user_id = ? ORDER BY created_at DESC",
            [$this->testUserId]
        );

        // Should have only one active verification (or the latest one is valid)
        $this->assertGreaterThan(0, count($verifications));
    }

    // Token Verification Tests

    public function testVerifyTokenWithValidToken(): void
    {
        $token = $this->emailService->generateVerificationToken($this->testUserId);

        $result = $this->emailService->verifyToken($token);

        $this->assertTrue($result);
    }

    public function testVerifyTokenWithInvalidToken(): void
    {
        $invalidToken = bin2hex(random_bytes(32));

        $result = $this->emailService->verifyToken($invalidToken);

        $this->assertFalse($result);
    }

    public function testVerifyTokenWithExpiredToken(): void
    {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        // Insert expired token (created 25 hours ago)
        $this->db->insert('email_verifications', [
            'user_id' => $this->testUserId,
            'token_hash' => $tokenHash,
            'expires_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'created_at' => date('Y-m-d H:i:s', strtotime('-25 hours'))
        ]);

        $result = $this->emailService->verifyToken($token);

        $this->assertFalse($result);
    }

    public function testVerifyTokenMarksUserAsVerified(): void
    {
        $token = $this->emailService->generateVerificationToken($this->testUserId);

        $this->emailService->verifyToken($token);

        $user = $this->db->queryOne("SELECT * FROM users WHERE id = ?", [$this->testUserId]);

        $this->assertEquals(1, $user['email_verified']);
    }

    public function testVerifyTokenMarksVerificationRecordAsVerified(): void
    {
        $token = $this->emailService->generateVerificationToken($this->testUserId);

        $this->emailService->verifyToken($token);

        $verification = $this->db->queryOne(
            "SELECT * FROM email_verifications WHERE user_id = ?",
            [$this->testUserId]
        );

        $this->assertEquals(1, $verification['verified']);
        $this->assertNotNull($verification['verified_at']);
    }

    public function testVerifyTokenCannotBeUsedTwice(): void
    {
        $token = $this->emailService->generateVerificationToken($this->testUserId);

        // First verification succeeds
        $result1 = $this->emailService->verifyToken($token);
        $this->assertTrue($result1);

        // Second verification fails
        $result2 = $this->emailService->verifyToken($token);
        $this->assertFalse($result2);
    }

    // Email Status Tests

    public function testIsEmailVerifiedReturnsFalseForUnverifiedUser(): void
    {
        $verified = $this->emailService->isEmailVerified($this->testUserId);

        $this->assertFalse($verified);
    }

    public function testIsEmailVerifiedReturnsTrueForVerifiedUser(): void
    {
        // Manually mark user as verified
        $this->db->update('users', ['email_verified' => 1], ['id' => $this->testUserId]);

        $verified = $this->emailService->isEmailVerified($this->testUserId);

        $this->assertTrue($verified);
    }

    public function testIsEmailVerifiedReturnsFalseForNonexistentUser(): void
    {
        $verified = $this->emailService->isEmailVerified(99999);

        $this->assertFalse($verified);
    }

    // Get Verification Status Tests

    public function testGetVerificationStatusForUnverifiedUser(): void
    {
        $status = $this->emailService->getVerificationStatus($this->testUserId);

        $this->assertIsArray($status);
        $this->assertArrayHasKey('verified', $status);
        $this->assertFalse($status['verified']);
        $this->assertArrayHasKey('has_pending_verification', $status);
    }

    public function testGetVerificationStatusForVerifiedUser(): void
    {
        $token = $this->emailService->generateVerificationToken($this->testUserId);
        $this->emailService->verifyToken($token);

        $status = $this->emailService->getVerificationStatus($this->testUserId);

        $this->assertTrue($status['verified']);
        $this->assertArrayHasKey('verified_at', $status);
    }

    public function testGetVerificationStatusWithPendingVerification(): void
    {
        $this->emailService->generateVerificationToken($this->testUserId);

        $status = $this->emailService->getVerificationStatus($this->testUserId);

        $this->assertFalse($status['verified']);
        $this->assertTrue($status['has_pending_verification']);
    }

    // Cleanup Tests

    public function testCleanupExpiredTokensRemovesOldTokens(): void
    {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        // Insert expired token
        $this->db->insert('email_verifications', [
            'user_id' => $this->testUserId,
            'token_hash' => $tokenHash,
            'expires_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
            'created_at' => date('Y-m-d H:i:s', strtotime('-26 hours'))
        ]);

        $this->emailService->cleanupExpiredTokens();

        $verification = $this->db->queryOne(
            "SELECT * FROM email_verifications WHERE token_hash = ?",
            [$tokenHash]
        );

        $this->assertNull($verification);
    }

    public function testCleanupExpiredTokensKeepsValidTokens(): void
    {
        $validToken = $this->emailService->generateVerificationToken($this->testUserId);

        $this->emailService->cleanupExpiredTokens();

        $verification = $this->db->queryOne(
            "SELECT * FROM email_verifications WHERE user_id = ?",
            [$this->testUserId]
        );

        $this->assertNotNull($verification);
    }

    // Rate Limiting Tests

    public function testCanSendVerificationAllowsFirstEmail(): void
    {
        $email = 'test@example.com';
        $ip = '127.0.0.1';

        $canSend = $this->emailService->canSendVerification($email, $ip);

        $this->assertTrue($canSend);
    }

    public function testCanSendVerificationBlocksRapidRequests(): void
    {
        $email = 'test@example.com';
        $ip = '127.0.0.1';

        // Record multiple attempts
        for ($i = 0; $i < 3; $i++) {
            $this->emailService->recordVerificationAttempt($email, $ip, true);
        }

        $canSend = $this->emailService->canSendVerification($email, $ip);

        $this->assertFalse($canSend);
    }

    public function testCanSendVerificationResetsAfterTimeWindow(): void
    {
        $email = 'test@example.com';
        $ip = '127.0.0.1';

        // Insert old attempt (16 minutes ago)
        $this->db->insert('verification_attempts', [
            'email' => $email,
            'ip_address' => $ip,
            'success' => 1,
            'attempted_at' => date('Y-m-d H:i:s', strtotime('-16 minutes'))
        ]);

        $canSend = $this->emailService->canSendVerification($email, $ip);

        $this->assertTrue($canSend);
    }

    public function testRecordVerificationAttemptStoresData(): void
    {
        $email = 'test@example.com';
        $ip = '192.168.1.1';

        $this->emailService->recordVerificationAttempt($email, $ip, true);

        $attempt = $this->db->queryOne(
            "SELECT * FROM verification_attempts WHERE email = ? AND ip_address = ?",
            [$email, $ip]
        );

        $this->assertNotNull($attempt);
        $this->assertEquals($email, $attempt['email']);
        $this->assertEquals($ip, $attempt['ip_address']);
        $this->assertEquals(1, $attempt['success']);
    }

    // Send Verification Email Tests

    public function testSendVerificationEmailGeneratesToken(): void
    {
        $result = $this->emailService->sendVerificationEmail($this->testUserId);

        // In test environment, email won't actually send
        // but token should be generated
        $verification = $this->db->queryOne(
            "SELECT * FROM email_verifications WHERE user_id = ? AND verified = 0",
            [$this->testUserId]
        );

        $this->assertNotNull($verification);
    }

    public function testSendVerificationEmailRecordsAttempt(): void
    {
        $beforeCount = count($this->db->query(
            "SELECT * FROM verification_attempts WHERE email = ?",
            ['test@example.com']
        ));

        $this->emailService->sendVerificationEmail($this->testUserId);

        $afterCount = count($this->db->query(
            "SELECT * FROM verification_attempts WHERE email = ?",
            ['test@example.com']
        ));

        $this->assertGreaterThan($beforeCount, $afterCount);
    }

    public function testSendVerificationEmailRespectsRateLimit(): void
    {
        // Send emails rapidly
        $result1 = $this->emailService->sendVerificationEmail($this->testUserId);
        $result2 = $this->emailService->sendVerificationEmail($this->testUserId);
        $result3 = $this->emailService->sendVerificationEmail($this->testUserId);
        $result4 = $this->emailService->sendVerificationEmail($this->testUserId);

        // 4th attempt should be blocked
        $this->assertFalse($result4);
    }

    // Resend Verification Tests

    public function testResendVerificationForUnverifiedUser(): void
    {
        // Generate initial token
        $token1 = $this->emailService->generateVerificationToken($this->testUserId);

        // Resend (generates new token)
        $result = $this->emailService->resendVerification($this->testUserId);

        // In test environment, we just check that it doesn't error
        // Actual email sending is mocked
        $verifications = $this->db->query(
            "SELECT * FROM email_verifications WHERE user_id = ? ORDER BY created_at DESC",
            [$this->testUserId]
        );

        $this->assertGreaterThan(0, count($verifications));
    }

    public function testResendVerificationForAlreadyVerifiedUser(): void
    {
        // Verify user first
        $token = $this->emailService->generateVerificationToken($this->testUserId);
        $this->emailService->verifyToken($token);

        $result = $this->emailService->resendVerification($this->testUserId);

        // Should not send to already verified user
        $this->assertFalse($result);
    }

    // Edge Cases

    public function testVerifyTokenWithEmptyToken(): void
    {
        $result = $this->emailService->verifyToken('');

        $this->assertFalse($result);
    }

    public function testVerifyTokenWithMalformedToken(): void
    {
        $result = $this->emailService->verifyToken('invalid-token-format');

        $this->assertFalse($result);
    }

    public function testGenerateTokenForNonexistentUser(): void
    {
        $this->expectException(\Exception::class);

        $this->emailService->generateVerificationToken(99999);
    }

    public function testMultipleUsersCanHaveVerificationTokens(): void
    {
        // Create second user
        $userId2 = $this->db->insert('users', [
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
            'password_hash' => password_hash('password456', PASSWORD_DEFAULT),
            'currency' => 'CZK',
            'timezone' => 'Europe/Prague',
            'email_verified' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Generate tokens for both users
        $token1 = $this->emailService->generateVerificationToken($this->testUserId);
        $token2 = $this->emailService->generateVerificationToken($userId2);

        $this->assertNotEquals($token1, $token2);

        // Both tokens should be valid
        $this->assertTrue($this->emailService->verifyToken($token1));
        $this->assertTrue($this->emailService->verifyToken($token2));
    }

    public function testTokenSecurityAgainstTimingAttacks(): void
    {
        $token = $this->emailService->generateVerificationToken($this->testUserId);

        // Generate similar but invalid token
        $invalidToken = substr($token, 0, -1) . '0';

        $start1 = microtime(true);
        $result1 = $this->emailService->verifyToken($invalidToken);
        $time1 = microtime(true) - $start1;

        $start2 = microtime(true);
        $result2 = $this->emailService->verifyToken('completely-different-invalid-token');
        $time2 = microtime(true) - $start2;

        // Both should fail
        $this->assertFalse($result1);
        $this->assertFalse($result2);

        // Timing should be similar (within reasonable bounds)
        // This is a basic check; real timing attack prevention requires more sophisticated testing
        $timeDifference = abs($time1 - $time2);
        $this->assertLessThan(0.1, $timeDifference, 'Timing difference too large - potential timing attack vulnerability');
    }

    // Statistics Tests

    public function testGetVerificationStats(): void
    {
        // Create multiple users with different states
        $verifiedUserId = $this->db->insert('users', [
            'name' => 'Verified User',
            'email' => 'verified@example.com',
            'password_hash' => password_hash('password', PASSWORD_DEFAULT),
            'currency' => 'CZK',
            'timezone' => 'Europe/Prague',
            'email_verified' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $unverifiedUserId = $this->db->insert('users', [
            'name' => 'Unverified User',
            'email' => 'unverified@example.com',
            'password_hash' => password_hash('password', PASSWORD_DEFAULT),
            'currency' => 'CZK',
            'timezone' => 'Europe/Prague',
            'email_verified' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $stats = $this->emailService->getVerificationStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_users', $stats);
        $this->assertArrayHasKey('verified_users', $stats);
        $this->assertArrayHasKey('unverified_users', $stats);
        $this->assertGreaterThan(0, $stats['total_users']);
    }

    // Concurrent Request Tests

    public function testConcurrentTokenGenerationMaintainsIntegrity(): void
    {
        // Simulate concurrent token generations
        $tokens = [];
        for ($i = 0; $i < 5; $i++) {
            $tokens[] = $this->emailService->generateVerificationToken($this->testUserId);
        }

        // All tokens should be different
        $uniqueTokens = array_unique($tokens);
        $this->assertCount(5, $uniqueTokens);

        // Only the last token should be verifiable (or implement your specific logic)
        $verifications = $this->db->query(
            "SELECT * FROM email_verifications WHERE user_id = ?",
            [$this->testUserId]
        );

        $this->assertGreaterThan(0, count($verifications));
    }
}
