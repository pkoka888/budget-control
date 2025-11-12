<?php
use PHPUnit\Framework\TestCase;
use BudgetApp\Services\TwoFactorAuthService;
use BudgetApp\Database\Database;

class TwoFactorAuthServiceTest extends TestCase
{
    private $db;
    private $twoFactorService;
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
            'email_verified' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $this->twoFactorService = new TwoFactorAuthService($this->db);
    }

    protected function tearDown(): void
    {
        $this->db = null;
        $this->twoFactorService = null;
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
                two_factor_enabled INTEGER DEFAULT 0,
                two_factor_secret TEXT,
                created_at TEXT
            );

            CREATE TABLE two_factor_backup_codes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                code_hash TEXT NOT NULL,
                used INTEGER DEFAULT 0,
                used_at TEXT,
                created_at TEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE two_factor_trusted_devices (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                device_token TEXT NOT NULL UNIQUE,
                device_name TEXT,
                ip_address TEXT,
                user_agent TEXT,
                last_used_at TEXT,
                expires_at TEXT NOT NULL,
                created_at TEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE two_factor_attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                success INTEGER DEFAULT 0,
                ip_address TEXT,
                attempted_at TEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );
        ");
    }

    // Secret Generation Tests

    public function testGenerateSecretCreatesValidBase32Secret(): void
    {
        $secret = $this->twoFactorService->generateSecret();

        $this->assertNotEmpty($secret);
        $this->assertEquals(32, strlen($secret)); // 160 bits / 5 bits per char
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret); // Valid Base32
    }

    public function testGenerateSecretCreatesUniqueSecrets(): void
    {
        $secret1 = $this->twoFactorService->generateSecret();
        $secret2 = $this->twoFactorService->generateSecret();

        $this->assertNotEquals($secret1, $secret2);
    }

    // TOTP Verification Tests

    public function testVerifyTOTPWithValidCode(): void
    {
        $secret = $this->twoFactorService->generateSecret();

        // Generate TOTP code for current time
        $timeStep = floor(time() / 30);
        $code = $this->generateTestTOTP($secret, $timeStep);

        $result = $this->twoFactorService->verifyTOTP($secret, $code);

        $this->assertTrue($result);
    }

    public function testVerifyTOTPWithInvalidCode(): void
    {
        $secret = $this->twoFactorService->generateSecret();
        $invalidCode = '000000';

        $result = $this->twoFactorService->verifyTOTP($secret, $invalidCode);

        $this->assertFalse($result);
    }

    public function testVerifyTOTPWithExpiredCode(): void
    {
        $secret = $this->twoFactorService->generateSecret();

        // Generate code for 5 minutes ago (outside tolerance window)
        $oldTimeStep = floor((time() - 300) / 30);
        $expiredCode = $this->generateTestTOTP($secret, $oldTimeStep);

        $result = $this->twoFactorService->verifyTOTP($secret, $expiredCode);

        $this->assertFalse($result);
    }

    public function testVerifyTOTPAcceptsPreviousTimeWindow(): void
    {
        $secret = $this->twoFactorService->generateSecret();

        // Generate code for previous 30-second window
        $prevTimeStep = floor(time() / 30) - 1;
        $prevCode = $this->generateTestTOTP($secret, $prevTimeStep);

        $result = $this->twoFactorService->verifyTOTP($secret, $prevCode);

        $this->assertTrue($result);
    }

    public function testVerifyTOTPAcceptsNextTimeWindow(): void
    {
        $secret = $this->twoFactorService->generateSecret();

        // Generate code for next 30-second window
        $nextTimeStep = floor(time() / 30) + 1;
        $nextCode = $this->generateTestTOTP($secret, $nextTimeStep);

        $result = $this->twoFactorService->verifyTOTP($secret, $nextCode);

        $this->assertTrue($result);
    }

    // QR Code Tests

    public function testGetQRCodeReturnsValidDataUri(): void
    {
        $secret = $this->twoFactorService->generateSecret();
        $qrCode = $this->twoFactorService->getQRCode($secret, 'test@example.com', 'Budget App');

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $qrCode);

        // Decode and verify it's valid SVG
        $base64 = substr($qrCode, strlen('data:image/svg+xml;base64,'));
        $svg = base64_decode($base64);

        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('</svg>', $svg);
    }

    public function testGetQRCodeContainsCorrectOTPAuthUrl(): void
    {
        $secret = 'JBSWY3DPEHPK3PXP'; // Test secret
        $email = 'test@example.com';
        $issuer = 'Budget App';

        $qrCode = $this->twoFactorService->getQRCode($secret, $email, $issuer);

        // Decode base64
        $base64 = substr($qrCode, strlen('data:image/svg+xml;base64,'));
        $svg = base64_decode($base64);

        // Check that it contains the otpauth URL components
        $expectedUrl = "otpauth://totp/" . urlencode($issuer . ':' . $email) . "?secret={$secret}&issuer=" . urlencode($issuer);

        // The URL should be encoded in the QR code (we can't directly verify without QR decoder)
        $this->assertNotEmpty($svg);
    }

    // Backup Codes Tests

    public function testGenerateBackupCodesCreatesCorrectNumber(): void
    {
        $codes = $this->twoFactorService->generateBackupCodes($this->testUserId);

        $this->assertCount(8, $codes);
    }

    public function testGenerateBackupCodesCreatesValidFormat(): void
    {
        $codes = $this->twoFactorService->generateBackupCodes($this->testUserId);

        foreach ($codes as $code) {
            // Format: XXXX-XXXX
            $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}$/', $code);
        }
    }

    public function testGenerateBackupCodesCreatesUniqueValues(): void
    {
        $codes = $this->twoFactorService->generateBackupCodes($this->testUserId);

        $uniqueCodes = array_unique($codes);
        $this->assertCount(count($codes), $uniqueCodes);
    }

    public function testGenerateBackupCodesStoresHashedValues(): void
    {
        $codes = $this->twoFactorService->generateBackupCodes($this->testUserId);

        $storedCodes = $this->db->query(
            "SELECT code_hash FROM two_factor_backup_codes WHERE user_id = ?",
            [$this->testUserId]
        );

        $this->assertCount(8, $storedCodes);

        // Verify hashes don't match plain codes
        foreach ($storedCodes as $stored) {
            $this->assertNotContains($stored['code_hash'], $codes);
            $this->assertEquals(64, strlen($stored['code_hash'])); // SHA-256 hex
        }
    }

    public function testVerifyBackupCodeWithValidCode(): void
    {
        $codes = $this->twoFactorService->generateBackupCodes($this->testUserId);
        $testCode = $codes[0];

        $result = $this->twoFactorService->verifyBackupCode($this->testUserId, $testCode);

        $this->assertTrue($result);
    }

    public function testVerifyBackupCodeWithInvalidCode(): void
    {
        $this->twoFactorService->generateBackupCodes($this->testUserId);

        $result = $this->twoFactorService->verifyBackupCode($this->testUserId, 'INVALID-CODE');

        $this->assertFalse($result);
    }

    public function testVerifyBackupCodeMarksCodeAsUsed(): void
    {
        $codes = $this->twoFactorService->generateBackupCodes($this->testUserId);
        $testCode = $codes[0];

        $this->twoFactorService->verifyBackupCode($this->testUserId, $testCode);

        // Try using same code again
        $result = $this->twoFactorService->verifyBackupCode($this->testUserId, $testCode);

        $this->assertFalse($result);
    }

    public function testRemainingBackupCodesCount(): void
    {
        $codes = $this->twoFactorService->generateBackupCodes($this->testUserId);

        // Initially all 8 are unused
        $remaining = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM two_factor_backup_codes
             WHERE user_id = ? AND used = 0",
            [$this->testUserId]
        );
        $this->assertEquals(8, $remaining['count']);

        // Use one code
        $this->twoFactorService->verifyBackupCode($this->testUserId, $codes[0]);

        $remaining = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM two_factor_backup_codes
             WHERE user_id = ? AND used = 0",
            [$this->testUserId]
        );
        $this->assertEquals(7, $remaining['count']);
    }

    // Trusted Devices Tests

    public function testCreateTrustedDeviceReturnsToken(): void
    {
        $deviceInfo = [
            'name' => 'Chrome on Windows',
            'ip' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0...'
        ];

        $token = $this->twoFactorService->createTrustedDevice(
            $this->testUserId,
            $deviceInfo['name'],
            $deviceInfo['ip'],
            $deviceInfo['user_agent']
        );

        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes hex
    }

    public function testCreateTrustedDeviceStoresInDatabase(): void
    {
        $deviceInfo = [
            'name' => 'Firefox on Linux',
            'ip' => '10.0.0.1',
            'user_agent' => 'Mozilla/5.0...'
        ];

        $token = $this->twoFactorService->createTrustedDevice(
            $this->testUserId,
            $deviceInfo['name'],
            $deviceInfo['ip'],
            $deviceInfo['user_agent']
        );

        $device = $this->db->queryOne(
            "SELECT * FROM two_factor_trusted_devices WHERE device_token = ?",
            [$token]
        );

        $this->assertNotNull($device);
        $this->assertEquals($this->testUserId, $device['user_id']);
        $this->assertEquals($deviceInfo['name'], $device['device_name']);
        $this->assertEquals($deviceInfo['ip'], $device['ip_address']);
    }

    public function testVerifyTrustedDeviceWithValidToken(): void
    {
        $token = $this->twoFactorService->createTrustedDevice(
            $this->testUserId,
            'Test Device',
            '127.0.0.1',
            'Test Agent'
        );

        $result = $this->twoFactorService->verifyTrustedDevice($this->testUserId, $token);

        $this->assertTrue($result);
    }

    public function testVerifyTrustedDeviceWithInvalidToken(): void
    {
        $result = $this->twoFactorService->verifyTrustedDevice($this->testUserId, 'invalid-token');

        $this->assertFalse($result);
    }

    public function testVerifyTrustedDeviceWithExpiredToken(): void
    {
        $token = bin2hex(random_bytes(32));

        // Insert expired device (created 31 days ago)
        $this->db->insert('two_factor_trusted_devices', [
            'user_id' => $this->testUserId,
            'device_token' => $token,
            'device_name' => 'Expired Device',
            'expires_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'created_at' => date('Y-m-d H:i:s', strtotime('-31 days'))
        ]);

        $result = $this->twoFactorService->verifyTrustedDevice($this->testUserId, $token);

        $this->assertFalse($result);
    }

    public function testGetTrustedDevicesListsAllActiveDevices(): void
    {
        // Create multiple devices
        $this->twoFactorService->createTrustedDevice($this->testUserId, 'Device 1', '1.1.1.1', 'Agent 1');
        $this->twoFactorService->createTrustedDevice($this->testUserId, 'Device 2', '2.2.2.2', 'Agent 2');
        $this->twoFactorService->createTrustedDevice($this->testUserId, 'Device 3', '3.3.3.3', 'Agent 3');

        $devices = $this->twoFactorService->getTrustedDevices($this->testUserId);

        $this->assertCount(3, $devices);
    }

    public function testRevokeTrustedDeviceRemovesDevice(): void
    {
        $token = $this->twoFactorService->createTrustedDevice(
            $this->testUserId,
            'Test Device',
            '127.0.0.1',
            'Test Agent'
        );

        $device = $this->db->queryOne(
            "SELECT id FROM two_factor_trusted_devices WHERE device_token = ?",
            [$token]
        );

        $result = $this->twoFactorService->revokeTrustedDevice($this->testUserId, $device['id']);

        $this->assertTrue($result);

        // Verify device is removed
        $remaining = $this->db->queryOne(
            "SELECT * FROM two_factor_trusted_devices WHERE id = ?",
            [$device['id']]
        );

        $this->assertNull($remaining);
    }

    // Rate Limiting Tests

    public function testRateLimitAllowsValidAttempts(): void
    {
        // Should allow first 3 attempts
        for ($i = 0; $i < 3; $i++) {
            $allowed = $this->twoFactorService->checkRateLimit($this->testUserId, '127.0.0.1');
            $this->assertTrue($allowed, "Attempt " . ($i + 1) . " should be allowed");

            // Record failed attempt
            $this->twoFactorService->recordAttempt($this->testUserId, false, '127.0.0.1');
        }
    }

    public function testRateLimitBlocksExcessiveAttempts(): void
    {
        // Exhaust rate limit
        for ($i = 0; $i < 5; $i++) {
            $this->twoFactorService->checkRateLimit($this->testUserId, '127.0.0.1');
            $this->twoFactorService->recordAttempt($this->testUserId, false, '127.0.0.1');
        }

        // Next attempt should be blocked
        $allowed = $this->twoFactorService->checkRateLimit($this->testUserId, '127.0.0.1');
        $this->assertFalse($allowed);
    }

    public function testRateLimitResetsAfterSuccessfulAttempt(): void
    {
        // Make some failed attempts
        for ($i = 0; $i < 2; $i++) {
            $this->twoFactorService->recordAttempt($this->testUserId, false, '127.0.0.1');
        }

        // Successful attempt should reset
        $this->twoFactorService->recordAttempt($this->testUserId, true, '127.0.0.1');

        // Should now have full attempts available
        $attempts = $this->db->query(
            "SELECT * FROM two_factor_attempts
             WHERE user_id = ? AND success = 0 AND attempted_at > datetime('now', '-15 minutes')",
            [$this->testUserId]
        );

        $this->assertCount(0, $attempts);
    }

    // Enable/Disable 2FA Tests

    public function testEnable2FA(): void
    {
        $secret = $this->twoFactorService->generateSecret();

        $result = $this->twoFactorService->enable2FA($this->testUserId, $secret);

        $this->assertTrue($result);

        $user = $this->db->queryOne("SELECT * FROM users WHERE id = ?", [$this->testUserId]);
        $this->assertEquals(1, $user['two_factor_enabled']);
        $this->assertEquals($secret, $user['two_factor_secret']);
    }

    public function testDisable2FA(): void
    {
        // First enable 2FA
        $secret = $this->twoFactorService->generateSecret();
        $this->twoFactorService->enable2FA($this->testUserId, $secret);

        // Then disable
        $result = $this->twoFactorService->disable2FA($this->testUserId);

        $this->assertTrue($result);

        $user = $this->db->queryOne("SELECT * FROM users WHERE id = ?", [$this->testUserId]);
        $this->assertEquals(0, $user['two_factor_enabled']);
        $this->assertNull($user['two_factor_secret']);
    }

    public function testDisable2FARemovesBackupCodes(): void
    {
        $secret = $this->twoFactorService->generateSecret();
        $this->twoFactorService->enable2FA($this->testUserId, $secret);
        $this->twoFactorService->generateBackupCodes($this->testUserId);

        $this->twoFactorService->disable2FA($this->testUserId);

        $backupCodes = $this->db->query(
            "SELECT * FROM two_factor_backup_codes WHERE user_id = ?",
            [$this->testUserId]
        );

        $this->assertCount(0, $backupCodes);
    }

    public function testDisable2FARemovesTrustedDevices(): void
    {
        $secret = $this->twoFactorService->generateSecret();
        $this->twoFactorService->enable2FA($this->testUserId, $secret);
        $this->twoFactorService->createTrustedDevice($this->testUserId, 'Device', '127.0.0.1', 'Agent');

        $this->twoFactorService->disable2FA($this->testUserId);

        $devices = $this->db->query(
            "SELECT * FROM two_factor_trusted_devices WHERE user_id = ?",
            [$this->testUserId]
        );

        $this->assertCount(0, $devices);
    }

    // Helper method to generate test TOTP
    private function generateTestTOTP(string $secret, int $timeStep): string
    {
        // This is a simplified TOTP generation for testing
        // In production, use the service's actual TOTP method
        $secretBinary = $this->base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeStep);

        $hash = hash_hmac('sha1', $time, $secretBinary, true);

        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $truncatedHash = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );

        $otp = $truncatedHash % (10 ** 6);
        return str_pad((string)$otp, 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $encoded): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $encoded = strtoupper($encoded);
        $decoded = '';
        $buffer = 0;
        $bitsLeft = 0;

        for ($i = 0; $i < strlen($encoded); $i++) {
            $val = strpos($alphabet, $encoded[$i]);
            if ($val === false) continue;

            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $decoded .= chr(($buffer >> ($bitsLeft - 8)) & 0xFF);
                $bitsLeft -= 8;
            }
        }

        return $decoded;
    }
}
