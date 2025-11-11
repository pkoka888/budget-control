<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

/**
 * Email Verification Service
 * Handles email verification for new user registrations and email changes
 */
class EmailVerificationService {
    private Database $db;
    private SecurityService $securityService;

    // Token configuration
    private const TOKEN_EXPIRY_HOURS = 24;
    private const TOKEN_LENGTH = 32; // bytes

    // Email rate limiting
    private const MAX_EMAILS_PER_HOUR = 3;

    public function __construct(Database $db) {
        $this->db = $db;
        $this->securityService = new SecurityService($db);
    }

    /**
     * Send verification email to user
     */
    public function sendVerificationEmail(int $userId): array {
        $user = $this->db->queryOne("SELECT * FROM users WHERE id = ?", [$userId]);
        if (!$user) {
            throw new \Exception('User not found');
        }

        if ($user['email_verified']) {
            throw new \Exception('Email is already verified');
        }

        // Check rate limiting
        if (!$this->checkRateLimit($userId)) {
            throw new \Exception('Too many verification emails sent. Please try again later.');
        }

        // Generate verification token
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        $expiresAt = date('Y-m-d H:i:s', time() + (self::TOKEN_EXPIRY_HOURS * 3600));

        // Store token
        $this->db->insert('email_verification_tokens', [
            'user_id' => $userId,
            'token' => hash('sha256', $token), // Store hashed token
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Update user record
        $this->db->update('users', [
            'email_verification_token' => hash('sha256', $token),
            'email_verification_sent_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$userId]);

        // Send email
        $verificationUrl = $this->getVerificationUrl($token);
        $emailSent = $this->sendEmail($user['email'], $user['name'], $verificationUrl);

        if (!$emailSent) {
            throw new \Exception('Failed to send verification email');
        }

        // Log audit event
        $this->securityService->logAuditEvent($userId, 'email_verification_sent', [
            'email' => $user['email']
        ]);

        return [
            'success' => true,
            'message' => 'Verification email sent',
            'expires_in_hours' => self::TOKEN_EXPIRY_HOURS
        ];
    }

    /**
     * Verify email with token
     */
    public function verifyEmail(string $token): array {
        $tokenHash = hash('sha256', $token);

        // Find verification token
        $verification = $this->db->queryOne(
            "SELECT * FROM email_verification_tokens
             WHERE token = ? AND expires_at > datetime('now') AND verified_at IS NULL",
            [$tokenHash]
        );

        if (!$verification) {
            return [
                'success' => false,
                'error' => 'Invalid or expired verification token'
            ];
        }

        // Get user
        $user = $this->db->queryOne(
            "SELECT * FROM users WHERE id = ?",
            [$verification['user_id']]
        );

        if (!$user) {
            return [
                'success' => false,
                'error' => 'User not found'
            ];
        }

        // Mark email as verified
        $this->db->update('users', [
            'email_verified' => 1,
            'email_verification_token' => null,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$user['id']]);

        // Mark token as used
        $this->db->update('email_verification_tokens', [
            'verified_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$verification['id']]);

        // Log audit event
        $this->securityService->logAuditEvent($user['id'], 'email_verified', [
            'email' => $user['email']
        ]);

        return [
            'success' => true,
            'message' => 'Email verified successfully',
            'user_id' => $user['id'],
            'email' => $user['email']
        ];
    }

    /**
     * Resend verification email
     */
    public function resendVerificationEmail(int $userId): array {
        // Invalidate old tokens
        $this->db->query(
            "UPDATE email_verification_tokens
             SET expires_at = datetime('now')
             WHERE user_id = ? AND verified_at IS NULL",
            [$userId]
        );

        // Send new verification email
        return $this->sendVerificationEmail($userId);
    }

    /**
     * Check if user's email is verified
     */
    public function isEmailVerified(int $userId): bool {
        $user = $this->db->queryOne(
            "SELECT email_verified FROM users WHERE id = ?",
            [$userId]
        );

        return $user && $user['email_verified'] == 1;
    }

    /**
     * Get verification status for user
     */
    public function getVerificationStatus(int $userId): array {
        $user = $this->db->queryOne(
            "SELECT email_verified, email_verification_sent_at, email FROM users WHERE id = ?",
            [$userId]
        );

        if (!$user) {
            throw new \Exception('User not found');
        }

        $pendingToken = null;
        if (!$user['email_verified']) {
            $pendingToken = $this->db->queryOne(
                "SELECT expires_at FROM email_verification_tokens
                 WHERE user_id = ? AND expires_at > datetime('now') AND verified_at IS NULL
                 ORDER BY created_at DESC LIMIT 1",
                [$userId]
            );
        }

        return [
            'verified' => (bool)$user['email_verified'],
            'email' => $user['email'],
            'verification_sent_at' => $user['email_verification_sent_at'],
            'pending_token_expires_at' => $pendingToken ? $pendingToken['expires_at'] : null,
            'can_resend' => $this->checkRateLimit($userId)
        ];
    }

    /**
     * Check rate limit for sending verification emails
     */
    private function checkRateLimit(int $userId): bool {
        $oneHourAgo = date('Y-m-d H:i:s', time() - 3600);

        $emailCount = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM email_verification_tokens
             WHERE user_id = ? AND created_at > ?",
            [$userId, $oneHourAgo]
        );

        return ($emailCount['count'] ?? 0) < self::MAX_EMAILS_PER_HOUR;
    }

    /**
     * Get verification URL
     */
    private function getVerificationUrl(string $token): string {
        $baseUrl = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

        return "{$protocol}://{$baseUrl}/verify-email?token={$token}";
    }

    /**
     * Send verification email
     * In production, this should use a proper email service (SendGrid, SES, etc.)
     */
    private function sendEmail(string $email, string $name, string $verificationUrl): bool {
        $subject = 'Ověření e-mailu - Budget Control';

        $htmlBody = $this->getEmailTemplate($name, $verificationUrl);
        $textBody = $this->getEmailTextTemplate($name, $verificationUrl);

        $headers = [
            'From: Budget Control <noreply@budget-control.app>',
            'Reply-To: support@budget-control.app',
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'X-Mailer: PHP/' . phpversion()
        ];

        // In development/testing, log the email instead of sending
        if (defined('TESTING') || ($_ENV['APP_ENV'] ?? 'development') === 'development') {
            error_log("=== Email Verification ===");
            error_log("To: $email");
            error_log("Subject: $subject");
            error_log("Verification URL: $verificationUrl");
            error_log("========================");
            return true;
        }

        // Send email using PHP mail() function
        // In production, replace this with a proper email service
        return mail(
            $email,
            $subject,
            $htmlBody,
            implode("\r\n", $headers)
        );
    }

    /**
     * Get HTML email template
     */
    private function getEmailTemplate(string $name, string $verificationUrl): string {
        return <<<HTML
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ověření e-mailu</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 30px;
            border: 1px solid #e0e0e0;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2563eb;
            margin: 0;
        }
        .content {
            background-color: white;
            padding: 25px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #2563eb;
            color: white !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #1d4ed8;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-top: 20px;
        }
        .warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Budget Control</h1>
            <p>Finanční přehled pod kontrolou</p>
        </div>

        <div class="content">
            <h2>Vítejte, {$name}!</h2>
            <p>Děkujeme za registraci v aplikaci Budget Control. Pro dokončení registrace prosím ověřte svou e-mailovou adresu kliknutím na tlačítko níže:</p>

            <div style="text-align: center;">
                <a href="{$verificationUrl}" class="button">Ověřit e-mail</a>
            </div>

            <p>Nebo zkopírujte tento odkaz do prohlížeče:</p>
            <p style="word-break: break-all; color: #666; font-size: 14px;">{$verificationUrl}</p>

            <div class="warning">
                <strong>⚠️ Bezpečnostní upozornění:</strong>
                <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                    <li>Tento odkaz vyprší za 24 hodin</li>
                    <li>Pokud jste se neregistrovali, tento e-mail ignorujte</li>
                    <li>Nikdy nesdílejte tento odkaz s nikým dalším</li>
                </ul>
            </div>
        </div>

        <div class="footer">
            <p>Tento e-mail byl odeslán automaticky, prosím neodpovídejte na něj.</p>
            <p>Máte otázky? Kontaktujte nás na <a href="mailto:support@budget-control.app">support@budget-control.app</a></p>
            <p>&copy; 2025 Budget Control. Všechna práva vyhrazena.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Get plain text email template
     */
    private function getEmailTextTemplate(string $name, string $verificationUrl): string {
        return <<<TEXT
Budget Control - Ověření e-mailu

Vítejte, {$name}!

Děkujeme za registraci v aplikaci Budget Control. Pro dokončení registrace prosím ověřte svou e-mailovou adresu kliknutím na odkaz níže:

{$verificationUrl}

BEZPEČNOSTNÍ UPOZORNĚNÍ:
- Tento odkaz vyprší za 24 hodin
- Pokud jste se neregistrovali, tento e-mail ignorujte
- Nikdy nesdílejte tento odkaz s nikým dalším

---
Tento e-mail byl odeslán automaticky, prosím neodpovídejte na něj.
Máte otázky? Kontaktujte nás na support@budget-control.app

© 2025 Budget Control. Všechna práva vyhrazena.
TEXT;
    }

    /**
     * Clean up expired tokens
     */
    public function cleanupExpiredTokens(): int {
        $result = $this->db->query(
            "DELETE FROM email_verification_tokens WHERE expires_at < datetime('now')"
        );

        return $this->db->getAffectedRows();
    }

    /**
     * Require email verification (middleware helper)
     */
    public function requireVerifiedEmail(int $userId): void {
        if (!$this->isEmailVerified($userId)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Email verification required',
                'message' => 'Please verify your email address to continue.',
                'verification_status' => $this->getVerificationStatus($userId)
            ]);
            exit;
        }
    }
}
