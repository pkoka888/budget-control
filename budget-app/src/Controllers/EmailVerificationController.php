<?php
namespace BudgetApp\Controllers;

use BudgetApp\Services\EmailVerificationService;
use BudgetApp\Middleware\CsrfProtection;

class EmailVerificationController extends BaseController {
    private EmailVerificationService $emailService;

    public function __construct() {
        parent::__construct();
        $this->emailService = new EmailVerificationService($this->db);
    }

    /**
     * Show email verification page
     */
    public function showVerificationPage(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $status = $this->emailService->getVerificationStatus($_SESSION['user_id']);

        if ($status['verified']) {
            header('Location: /');
            exit;
        }

        echo $this->app->render('auth/email-verification', [
            'status' => $status
        ]);
    }

    /**
     * Verify email with token
     */
    public function verifyEmail(): void {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            echo $this->app->render('auth/email-verified', [
                'success' => false,
                'error' => 'Invalid or missing verification token'
            ]);
            return;
        }

        try {
            $result = $this->emailService->verifyEmail($token);

            if ($result['success']) {
                // Auto-login if not already logged in
                if (!isset($_SESSION['user_id'])) {
                    $_SESSION['user_id'] = $result['user_id'];
                }

                echo $this->app->render('auth/email-verified', [
                    'success' => true,
                    'message' => 'Email verified successfully! You can now access all features.'
                ]);
            } else {
                echo $this->app->render('auth/email-verified', [
                    'success' => false,
                    'error' => $result['error']
                ]);
            }
        } catch (\Exception $e) {
            echo $this->app->render('auth/email-verified', [
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Resend verification email
     */
    public function resendVerificationEmail(): void {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['error' => 'Not authenticated'], 401);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }

        CsrfProtection::requireToken();

        try {
            $result = $this->emailService->resendVerificationEmail($_SESSION['user_id']);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Verification email sent successfully',
                'expires_in_hours' => $result['expires_in_hours']
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get verification status
     */
    public function getStatus(): void {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['error' => 'Not authenticated'], 401);
            return;
        }

        try {
            $status = $this->emailService->getVerificationStatus($_SESSION['user_id']);

            $this->jsonResponse([
                'success' => true,
                'status' => $status
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}
