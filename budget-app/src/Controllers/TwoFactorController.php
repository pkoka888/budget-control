<?php
namespace BudgetApp\Controllers;

use BudgetApp\Services\TwoFactorAuthService;
use BudgetApp\Middleware\CsrfProtection;

class TwoFactorController extends BaseController {
    private TwoFactorAuthService $twoFactorService;

    public function __construct() {
        parent::__construct();
        $this->twoFactorService = new TwoFactorAuthService($this->db);
    }

    /**
     * Show 2FA settings page
     */
    public function settings(): void {
        $this->requireAuth();

        $status = $this->twoFactorService->get2FAStatus($this->userId);

        echo $this->app->render('settings/two-factor', [
            'status' => $status
        ]);
    }

    /**
     * Setup 2FA - Generate QR code
     */
    public function setup(): void {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }

        CsrfProtection::requireToken();

        try {
            $setupData = $this->twoFactorService->setup2FA($this->userId);

            $this->jsonResponse([
                'success' => true,
                'data' => $setupData
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Enable 2FA - Verify TOTP code
     */
    public function enable(): void {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }

        CsrfProtection::requireToken();

        $data = $this->getJsonInput();
        $totpCode = $data['totp_code'] ?? '';

        if (empty($totpCode)) {
            $this->jsonResponse(['error' => 'TOTP code is required'], 400);
            return;
        }

        try {
            $result = $this->twoFactorService->enable2FA($this->userId, $totpCode);

            $this->jsonResponse([
                'success' => true,
                'backup_codes' => $result['backup_codes'],
                'message' => 'Two-factor authentication enabled successfully'
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Disable 2FA
     */
    public function disable(): void {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }

        CsrfProtection::requireToken();

        $data = $this->getJsonInput();
        $password = $data['password'] ?? '';

        if (empty($password)) {
            $this->jsonResponse(['error' => 'Password is required'], 400);
            return;
        }

        try {
            $this->twoFactorService->disable2FA($this->userId, $password);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Two-factor authentication disabled successfully'
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Verify 2FA code during login
     */
    public function verify(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }

        CsrfProtection::requireToken();

        $data = $this->getJsonInput();
        $userId = $data['user_id'] ?? $_SESSION['2fa_user_id'] ?? null;
        $code = $data['code'] ?? '';
        $trustDevice = $data['trust_device'] ?? false;

        if (!$userId || empty($code)) {
            $this->jsonResponse(['error' => 'Invalid request'], 400);
            return;
        }

        try {
            $verified = $this->twoFactorService->verify2FACode($userId, $code);

            if (!$verified) {
                $this->jsonResponse(['error' => 'Invalid verification code'], 400);
                return;
            }

            // Set user session
            $_SESSION['user_id'] = $userId;
            unset($_SESSION['2fa_user_id']);

            // Create trusted device if requested
            $deviceToken = null;
            if ($trustDevice) {
                $deviceFingerprint = $this->getDeviceFingerprint();
                $deviceToken = $this->twoFactorService->createTrustedDevice($userId, $deviceFingerprint);
                setcookie('2fa_trust', $deviceToken, time() + (30 * 86400), '/', '', true, true);
            }

            $this->jsonResponse([
                'success' => true,
                'message' => 'Verification successful',
                'trusted_device_token' => $deviceToken
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get trusted devices
     */
    public function getTrustedDevices(): void {
        $this->requireAuth();

        try {
            $devices = $this->twoFactorService->getTrustedDevices($this->userId);

            $this->jsonResponse([
                'success' => true,
                'devices' => $devices
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Revoke trusted device
     */
    public function revokeTrustedDevice(): void {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }

        CsrfProtection::requireToken();

        $data = $this->getJsonInput();
        $sessionId = $data['session_id'] ?? null;

        if (!$sessionId) {
            $this->jsonResponse(['error' => 'Session ID is required'], 400);
            return;
        }

        try {
            $this->twoFactorService->revokeTrustedDevice($this->userId, $sessionId);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Trusted device revoked successfully'
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Regenerate backup codes
     */
    public function regenerateBackupCodes(): void {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }

        CsrfProtection::requireToken();

        $data = $this->getJsonInput();
        $password = $data['password'] ?? '';

        if (empty($password)) {
            $this->jsonResponse(['error' => 'Password is required'], 400);
            return;
        }

        try {
            $backupCodes = $this->twoFactorService->regenerateBackupCodes($this->userId, $password);

            $this->jsonResponse([
                'success' => true,
                'backup_codes' => $backupCodes,
                'message' => 'Backup codes regenerated successfully'
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get device fingerprint
     */
    private function getDeviceFingerprint(): string {
        $components = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            $_SERVER['HTTP_ACCEPT_ENCODING'] ?? ''
        ];

        return hash('sha256', implode('|', $components));
    }
}
