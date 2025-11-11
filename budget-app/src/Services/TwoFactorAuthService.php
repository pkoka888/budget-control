<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

/**
 * Two-Factor Authentication Service
 * Implements TOTP (Time-based One-Time Password) according to RFC 6238
 * Compatible with Google Authenticator, Authy, and other TOTP apps
 */
class TwoFactorAuthService {
    private Database $db;
    private SecurityService $securityService;

    // TOTP Configuration
    private const TOTP_PERIOD = 30; // 30 seconds
    private const TOTP_DIGITS = 6;  // 6-digit codes
    private const TOTP_ALGORITHM = 'sha1';
    private const TOTP_WINDOW = 1;  // Allow 1 period before/after for clock drift

    // Backup codes configuration
    private const BACKUP_CODES_COUNT = 10;
    private const BACKUP_CODE_LENGTH = 8;

    // Trusted device configuration
    private const TRUSTED_DEVICE_EXPIRY_DAYS = 30;

    public function __construct(Database $db) {
        $this->db = $db;
        $this->securityService = new SecurityService($db);
    }

    /**
     * Generate a new TOTP secret for a user
     */
    public function generateSecret(): string {
        // Generate 20-byte (160-bit) secret for TOTP
        $secret = random_bytes(20);
        return $this->base32Encode($secret);
    }

    /**
     * Enable 2FA for a user
     */
    public function enable2FA(int $userId, string $totpCode): array {
        // Get user
        $user = $this->db->queryOne("SELECT * FROM users WHERE id = ?", [$userId]);
        if (!$user) {
            throw new \Exception('User not found');
        }

        if ($user['two_factor_enabled']) {
            throw new \Exception('2FA is already enabled');
        }

        if (empty($user['two_factor_secret'])) {
            throw new \Exception('2FA secret not set. Call setup2FA first.');
        }

        // Verify the TOTP code
        if (!$this->verifyTOTP($user['two_factor_secret'], $totpCode)) {
            $this->logAuditEvent($userId, 'enable_failed', false, [
                'reason' => 'Invalid TOTP code'
            ]);
            throw new \Exception('Invalid verification code');
        }

        // Generate backup codes
        $backupCodes = $this->generateBackupCodes();
        $backupCodesHashed = array_map(function($code) {
            return hash('sha256', $code);
        }, $backupCodes);

        // Enable 2FA and store backup codes
        $this->db->update('users', [
            'two_factor_enabled' => 1,
            'two_factor_backup_codes' => json_encode($backupCodesHashed),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$userId]);

        // Store backup codes in separate table
        foreach ($backupCodesHashed as $codeHash) {
            $this->db->insert('two_factor_backup_codes', [
                'user_id' => $userId,
                'code_hash' => $codeHash,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        // Log audit event
        $this->logAuditEvent($userId, 'enabled', true);

        return [
            'success' => true,
            'backup_codes' => $backupCodes // Return plain codes to user (only time they're shown)
        ];
    }

    /**
     * Setup 2FA (generate secret and QR code)
     */
    public function setup2FA(int $userId, string $appName = 'Budget Control'): array {
        $user = $this->db->queryOne("SELECT * FROM users WHERE id = ?", [$userId]);
        if (!$user) {
            throw new \Exception('User not found');
        }

        // Generate new secret
        $secret = $this->generateSecret();

        // Store secret (not enabled yet)
        $this->db->update('users', [
            'two_factor_secret' => $secret,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$userId]);

        // Generate QR code data URL
        $qrCodeUrl = $this->getQRCodeURL($user['email'], $secret, $appName);

        return [
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
            'manual_entry' => $this->formatSecretForDisplay($secret)
        ];
    }

    /**
     * Disable 2FA for a user
     */
    public function disable2FA(int $userId, string $password): bool {
        $user = $this->db->queryOne("SELECT * FROM users WHERE id = ?", [$userId]);
        if (!$user) {
            throw new \Exception('User not found');
        }

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            $this->logAuditEvent($userId, 'disable_failed', false, [
                'reason' => 'Invalid password'
            ]);
            throw new \Exception('Invalid password');
        }

        // Disable 2FA
        $this->db->update('users', [
            'two_factor_enabled' => 0,
            'two_factor_secret' => null,
            'two_factor_backup_codes' => null,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$userId]);

        // Delete backup codes
        $this->db->query("DELETE FROM two_factor_backup_codes WHERE user_id = ?", [$userId]);

        // Delete trusted devices
        $this->db->query("DELETE FROM two_factor_sessions WHERE user_id = ?", [$userId]);

        // Log audit event
        $this->logAuditEvent($userId, 'disabled', true);

        return true;
    }

    /**
     * Verify a TOTP code
     */
    public function verifyTOTP(string $secret, string $code): bool {
        $timeStep = floor(time() / self::TOTP_PERIOD);

        // Check current time window
        if ($this->generateTOTP($secret, $timeStep) === $code) {
            return true;
        }

        // Check previous and next time windows (for clock drift)
        for ($i = 1; $i <= self::TOTP_WINDOW; $i++) {
            if ($this->generateTOTP($secret, $timeStep - $i) === $code ||
                $this->generateTOTP($secret, $timeStep + $i) === $code) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verify 2FA code (TOTP or backup code)
     */
    public function verify2FACode(int $userId, string $code): bool {
        $user = $this->db->queryOne("SELECT * FROM users WHERE id = ?", [$userId]);
        if (!$user || !$user['two_factor_enabled']) {
            return false;
        }

        // Try TOTP code first
        if ($this->verifyTOTP($user['two_factor_secret'], $code)) {
            $this->logAuditEvent($userId, 'verified', true, ['method' => 'totp']);
            return true;
        }

        // Try backup code
        if ($this->verifyBackupCode($userId, $code)) {
            $this->logAuditEvent($userId, 'verified', true, ['method' => 'backup_code']);
            return true;
        }

        // Log failed attempt
        $this->logAuditEvent($userId, 'failed', false);

        return false;
    }

    /**
     * Generate TOTP code for a given time step
     */
    private function generateTOTP(string $secret, int $timeStep): string {
        $secretBinary = $this->base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeStep);

        $hash = hash_hmac(self::TOTP_ALGORITHM, $time, $secretBinary, true);

        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $truncatedHash = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );

        $otp = $truncatedHash % (10 ** self::TOTP_DIGITS);

        return str_pad((string)$otp, self::TOTP_DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Generate backup codes
     */
    private function generateBackupCodes(): array {
        $codes = [];
        for ($i = 0; $i < self::BACKUP_CODES_COUNT; $i++) {
            $code = '';
            for ($j = 0; $j < self::BACKUP_CODE_LENGTH; $j++) {
                $code .= random_int(0, 9);
            }
            $codes[] = $code;
        }
        return $codes;
    }

    /**
     * Verify backup code
     */
    private function verifyBackupCode(int $userId, string $code): bool {
        $codeHash = hash('sha256', $code);

        $backupCode = $this->db->queryOne(
            "SELECT * FROM two_factor_backup_codes
             WHERE user_id = ? AND code_hash = ? AND used_at IS NULL",
            [$userId, $codeHash]
        );

        if (!$backupCode) {
            return false;
        }

        // Mark code as used
        $this->db->update(
            'two_factor_backup_codes',
            ['used_at' => date('Y-m-d H:i:s')],
            'id = ?',
            [$backupCode['id']]
        );

        $this->logAuditEvent($userId, 'backup_used', true);

        return true;
    }

    /**
     * Get QR code URL for Google Authenticator
     */
    private function getQRCodeURL(string $email, string $secret, string $appName): string {
        $label = urlencode($appName) . ':' . urlencode($email);
        $params = http_build_query([
            'secret' => $secret,
            'issuer' => $appName,
            'algorithm' => strtoupper(self::TOTP_ALGORITHM),
            'digits' => self::TOTP_DIGITS,
            'period' => self::TOTP_PERIOD
        ]);

        $otpauthUrl = "otpauth://totp/{$label}?{$params}";

        // Use Google Charts API for QR code generation
        $qrCodeUrl = 'https://chart.googleapis.com/chart?' . http_build_query([
            'chs' => '200x200',
            'chld' => 'M|0',
            'cht' => 'qr',
            'chl' => $otpauthUrl
        ]);

        return $qrCodeUrl;
    }

    /**
     * Format secret for manual entry (groups of 4)
     */
    private function formatSecretForDisplay(string $secret): string {
        return implode(' ', str_split($secret, 4));
    }

    /**
     * Base32 encode (RFC 4648)
     */
    private function base32Encode(string $data): string {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;

        for ($i = 0, $j = strlen($data); $i < $j; $i++) {
            $v = ($v << 8) | ord($data[$i]);
            $vbits += 8;

            while ($vbits >= 5) {
                $vbits -= 5;
                $output .= $alphabet[($v >> $vbits) & 0x1F];
            }
        }

        if ($vbits > 0) {
            $output .= $alphabet[($v << (5 - $vbits)) & 0x1F];
        }

        return $output;
    }

    /**
     * Base32 decode (RFC 4648)
     */
    private function base32Decode(string $data): string {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;

        for ($i = 0, $j = strlen($data); $i < $j; $i++) {
            $v = ($v << 5) | strpos($alphabet, $data[$i]);
            $vbits += 5;

            if ($vbits >= 8) {
                $vbits -= 8;
                $output .= chr(($v >> $vbits) & 0xFF);
            }
        }

        return $output;
    }

    /**
     * Create trusted device session
     */
    public function createTrustedDevice(int $userId, string $deviceFingerprint): string {
        $sessionToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + (self::TRUSTED_DEVICE_EXPIRY_DAYS * 86400));

        $this->db->insert('two_factor_sessions', [
            'user_id' => $userId,
            'session_token' => $sessionToken,
            'device_fingerprint' => $deviceFingerprint,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $sessionToken;
    }

    /**
     * Verify trusted device
     */
    public function verifyTrustedDevice(int $userId, string $sessionToken): bool {
        $session = $this->db->queryOne(
            "SELECT * FROM two_factor_sessions
             WHERE user_id = ? AND session_token = ? AND expires_at > datetime('now')",
            [$userId, $sessionToken]
        );

        if (!$session) {
            return false;
        }

        // Update last used time
        $this->db->update(
            'two_factor_sessions',
            ['last_used_at' => date('Y-m-d H:i:s')],
            'id = ?',
            [$session['id']]
        );

        return true;
    }

    /**
     * Get user's trusted devices
     */
    public function getTrustedDevices(int $userId): array {
        return $this->db->query(
            "SELECT id, device_fingerprint, ip_address, user_agent,
                    created_at, last_used_at, expires_at
             FROM two_factor_sessions
             WHERE user_id = ? AND expires_at > datetime('now')
             ORDER BY last_used_at DESC",
            [$userId]
        );
    }

    /**
     * Revoke trusted device
     */
    public function revokeTrustedDevice(int $userId, int $sessionId): bool {
        return $this->db->query(
            "DELETE FROM two_factor_sessions WHERE id = ? AND user_id = ?",
            [$sessionId, $userId]
        ) !== false;
    }

    /**
     * Log 2FA audit event
     */
    private function logAuditEvent(
        int $userId,
        string $eventType,
        bool $success = true,
        array $metadata = []
    ): void {
        $this->db->insert('two_factor_audit_log', [
            'user_id' => $userId,
            'event_type' => $eventType,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'success' => $success ? 1 : 0,
            'metadata' => !empty($metadata) ? json_encode($metadata) : null,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Also log to security service
        $this->securityService->logAuditEvent($userId, '2fa_' . $eventType, $metadata);
    }

    /**
     * Get 2FA status for user
     */
    public function get2FAStatus(int $userId): array {
        $user = $this->db->queryOne(
            "SELECT two_factor_enabled, two_factor_secret, two_factor_backup_codes
             FROM users WHERE id = ?",
            [$userId]
        );

        if (!$user) {
            throw new \Exception('User not found');
        }

        $backupCodesRemaining = 0;
        if ($user['two_factor_enabled']) {
            $backupCodesRemaining = $this->db->queryOne(
                "SELECT COUNT(*) as count FROM two_factor_backup_codes
                 WHERE user_id = ? AND used_at IS NULL",
                [$userId]
            )['count'] ?? 0;
        }

        $trustedDevices = $this->getTrustedDevices($userId);

        return [
            'enabled' => (bool)$user['two_factor_enabled'],
            'setup_complete' => !empty($user['two_factor_secret']),
            'backup_codes_remaining' => $backupCodesRemaining,
            'trusted_devices_count' => count($trustedDevices),
            'trusted_devices' => $trustedDevices
        ];
    }

    /**
     * Generate new backup codes (regenerate)
     */
    public function regenerateBackupCodes(int $userId, string $password): array {
        $user = $this->db->queryOne("SELECT * FROM users WHERE id = ?", [$userId]);
        if (!$user || !$user['two_factor_enabled']) {
            throw new \Exception('2FA is not enabled');
        }

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            throw new \Exception('Invalid password');
        }

        // Delete old backup codes
        $this->db->query("DELETE FROM two_factor_backup_codes WHERE user_id = ?", [$userId]);

        // Generate new backup codes
        $backupCodes = $this->generateBackupCodes();
        $backupCodesHashed = array_map(function($code) {
            return hash('sha256', $code);
        }, $backupCodes);

        // Store new backup codes
        foreach ($backupCodesHashed as $codeHash) {
            $this->db->insert('two_factor_backup_codes', [
                'user_id' => $userId,
                'code_hash' => $codeHash,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        $this->logAuditEvent($userId, 'backup_codes_regenerated', true);

        return $backupCodes;
    }
}
