<?php
namespace BudgetApp\Controllers;

use BudgetApp\Application;
use BudgetApp\SessionConfig;

class AuthController {
    private Application $app;
    private $db;

    public function __construct(Application $app) {
        $this->app = $app;
        $this->db = $app->getDatabase();
    }

    public function loginForm(): void {
        if (isset($_SESSION['user_id'])) {
            header('Location: /');
            exit;
        }
        echo $this->app->render('auth/login');
    }

    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        // CSRF protection
        \BudgetApp\Middleware\CsrfProtection::requireToken();

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // Rate limiting - 5 attempts per 15 minutes per email
        $rateLimiter = new \BudgetApp\Middleware\RateLimiter($this->db);
        $rateLimiter->requireLoginLimit($email);

        $user = $this->db->queryOne(
            "SELECT id, password_hash FROM users WHERE email = ?",
            [$email]
        );

        if ($user && password_verify($password, $user['password_hash'])) {
            // Regenerate session ID to prevent session fixation attacks
            SessionConfig::regenerate();

            $_SESSION['user_id'] = $user['id'];

            // Reset rate limit on successful login
            $ip = \BudgetApp\Middleware\RateLimiter::getClientIp();
            $rateLimiter->reset("login:$email:$ip");

            // Regenerate CSRF token after login
            \BudgetApp\Middleware\CsrfProtection::regenerateToken();

            header('Location: /');
            exit;
        }

        echo $this->app->render('auth/login', ['error' => 'Neplatné přihlašovací údaje']);
    }

    public function registerForm(): void {
        if (isset($_SESSION['user_id'])) {
            header('Location: /');
            exit;
        }
        echo $this->app->render('auth/register');
    }

    public function register(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        // CSRF protection
        \BudgetApp\Middleware\CsrfProtection::requireToken();

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $name = $_POST['name'] ?? '';

        // Validate
        if (empty($email) || empty($password) || empty($name)) {
            echo $this->app->render('auth/register', ['error' => 'Všechna pole jsou povinná']);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo $this->app->render('auth/register', ['error' => 'Neplatný e-mail']);
            return;
        }

        // Strong password validation
        if (strlen($password) < 12) {
            echo $this->app->render('auth/register', ['error' => 'Heslo musí mít alespoň 12 znaků']);
            return;
        }

        if (!preg_match('/[A-Z]/', $password)) {
            echo $this->app->render('auth/register', ['error' => 'Heslo musí obsahovat alespoň jedno velké písmeno']);
            return;
        }

        if (!preg_match('/[a-z]/', $password)) {
            echo $this->app->render('auth/register', ['error' => 'Heslo musí obsahovat alespoň jedno malé písmeno']);
            return;
        }

        if (!preg_match('/[0-9]/', $password)) {
            echo $this->app->render('auth/register', ['error' => 'Heslo musí obsahovat alespoň jedno číslo']);
            return;
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            echo $this->app->render('auth/register', ['error' => 'Heslo musí obsahovat alespoň jeden speciální znak']);
            return;
        }

        // Check if email already exists
        $existing = $this->db->queryOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            echo $this->app->render('auth/register', ['error' => 'E-mail je již registrován']);
            return;
        }

        // Create user
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $userId = $this->db->insert('users', [
            'name' => $name,
            'email' => $email,
            'password_hash' => $hashedPassword,
            'currency' => 'CZK',
            'timezone' => 'Europe/Prague',
            'email_verified' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Regenerate session ID to prevent session fixation attacks
        SessionConfig::regenerate();

        $_SESSION['user_id'] = $userId;

        // Send verification email
        try {
            $emailService = new \BudgetApp\Services\EmailVerificationService($this->db);
            $emailService->sendVerificationEmail($userId);
        } catch (\Exception $e) {
            // Log error but don't fail registration
            error_log("Failed to send verification email: " . $e->getMessage());
        }

        // Redirect to email verification page
        header('Location: /email-verification');
        exit;
    }

    public function logout(): void {
        SessionConfig::destroy();
        header('Location: /login');
        exit;
    }

    /**
     * Show forgot password form
     */
    public function forgotPasswordForm(): void {
        if (isset($_SESSION['user_id'])) {
            header('Location: /');
            exit;
        }
        echo $this->app->render('auth/forgot-password');
    }

    /**
     * Handle forgot password request
     * Generates a secure token and sends reset email
     */
    public function forgotPassword(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        // CSRF protection
        \BudgetApp\Middleware\CsrfProtection::requireToken();

        $email = $_POST['email'] ?? '';

        // Rate limiting - 3 attempts per hour per email
        $rateLimiter = new \BudgetApp\Middleware\RateLimiter($this->db);
        $rateLimiter->requirePasswordResetLimit($email);

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo $this->app->render('auth/forgot-password', [
                'error' => 'Neplatný e-mail'
            ]);
            return;
        }

        // Find user
        $user = $this->db->queryOne(
            "SELECT id, name, email FROM users WHERE email = ?",
            [$email]
        );

        // Always show success message to prevent email enumeration
        $successMessage = 'Pokud je tento e-mail registrován, obdržíte odkaz pro obnovení hesla.';

        if ($user) {
            // Generate secure reset token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', time() + 900); // 15 minutes

            // Store token in database
            $this->ensurePasswordResetsTable();
            $this->db->insert('password_resets', [
                'user_id' => $user['id'],
                'token' => hash('sha256', $token), // Store hashed token
                'expires_at' => $expiry,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Send email (using PHP mail function)
            $resetUrl = "http://" . $_SERVER['HTTP_HOST'] . "/reset-password?token=$token";
            $subject = "Obnovení hesla - Budget Control";

            // Escape user-controlled data to prevent XSS
            $safeName = \BudgetApp\Helpers\ValidationHelper::escapeEmail($user['name']);

            $message = "Dobrý den {$safeName},\n\n";
            $message .= "Obdrželi jste tento e-mail, protože byla požádána obnova hesla pro váš účet.\n\n";
            $message .= "Klikněte na následující odkaz pro obnovení hesla:\n";
            $message .= "$resetUrl\n\n";
            $message .= "Tento odkaz vyprší za 15 minut.\n\n";
            $message .= "Pokud jste obnovu hesla nepožadovali, můžete tento e-mail ignorovat.\n\n";
            $message .= "S pozdravem,\nBudget Control";

            $headers = "From: no-reply@budgetcontrol.local\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            mail($user['email'], $subject, $message, $headers);
        }

        echo $this->app->render('auth/forgot-password', [
            'success' => $successMessage
        ]);
    }

    /**
     * Show reset password form
     */
    public function resetPasswordForm(): void {
        if (isset($_SESSION['user_id'])) {
            header('Location: /');
            exit;
        }

        $token = $_GET['token'] ?? '';
        if (empty($token)) {
            echo $this->app->render('auth/reset-password', [
                'error' => 'Neplatný odkaz pro obnovení hesla'
            ]);
            return;
        }

        // Verify token exists and is not expired
        $this->ensurePasswordResetsTable();
        $hashedToken = hash('sha256', $token);
        $reset = $this->db->queryOne(
            "SELECT pr.*, u.email FROM password_resets pr
             JOIN users u ON pr.user_id = u.id
             WHERE pr.token = ? AND pr.expires_at > ? AND pr.used_at IS NULL",
            [$hashedToken, date('Y-m-d H:i:s')]
        );

        if (!$reset) {
            echo $this->app->render('auth/reset-password', [
                'error' => 'Odkaz pro obnovení hesla vypršel nebo je neplatný'
            ]);
            return;
        }

        echo $this->app->render('auth/reset-password', [
            'token' => $token,
            'email' => $reset['email']
        ]);
    }

    /**
     * Handle password reset
     */
    public function resetPassword(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        // CSRF protection
        \BudgetApp\Middleware\CsrfProtection::requireToken();

        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        // Validate
        if (empty($token) || empty($password)) {
            echo $this->app->render('auth/reset-password', [
                'error' => 'Všechna pole jsou povinná',
                'token' => $token
            ]);
            return;
        }

        if ($password !== $passwordConfirm) {
            echo $this->app->render('auth/reset-password', [
                'error' => 'Hesla se neshodují',
                'token' => $token
            ]);
            return;
        }

        // Strong password validation (same as registration)
        if (strlen($password) < 12) {
            echo $this->app->render('auth/reset-password', [
                'error' => 'Heslo musí mít alespoň 12 znaků',
                'token' => $token
            ]);
            return;
        }

        if (!preg_match('/[A-Z]/', $password)) {
            echo $this->app->render('auth/reset-password', [
                'error' => 'Heslo musí obsahovat alespoň jedno velké písmeno',
                'token' => $token
            ]);
            return;
        }

        if (!preg_match('/[a-z]/', $password)) {
            echo $this->app->render('auth/reset-password', [
                'error' => 'Heslo musí obsahovat alespoň jedno malé písmeno',
                'token' => $token
            ]);
            return;
        }

        if (!preg_match('/[0-9]/', $password)) {
            echo $this->app->render('auth/reset-password', [
                'error' => 'Heslo musí obsahovat alespoň jedno číslo',
                'token' => $token
            ]);
            return;
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            echo $this->app->render('auth/reset-password', [
                'error' => 'Heslo musí obsahovat alespoň jeden speciální znak',
                'token' => $token
            ]);
            return;
        }

        // Verify token
        $this->ensurePasswordResetsTable();
        $hashedToken = hash('sha256', $token);
        $reset = $this->db->queryOne(
            "SELECT * FROM password_resets
             WHERE token = ? AND expires_at > ? AND used_at IS NULL",
            [$hashedToken, date('Y-m-d H:i:s')]
        );

        if (!$reset) {
            echo $this->app->render('auth/reset-password', [
                'error' => 'Odkaz pro obnovení hesla vypršel nebo je neplatný'
            ]);
            return;
        }

        // Update password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $this->db->update('users', [
            'password_hash' => $hashedPassword,
            'updated_at' => date('Y-m-d H:i:s')
        ], "id = ?", [$reset['user_id']]);

        // Mark token as used
        $this->db->update('password_resets', [
            'used_at' => date('Y-m-d H:i:s')
        ], "id = ?", [$reset['id']]);

        // Regenerate session ID to prevent session fixation attacks
        SessionConfig::regenerate();

        // Log user in automatically
        $_SESSION['user_id'] = $reset['user_id'];

        // Regenerate CSRF token after password change
        \BudgetApp\Middleware\CsrfProtection::regenerateToken();

        header('Location: /?message=password_reset_success');
        exit;
    }

    /**
     * Ensure password_resets table exists
     */
    private function ensurePasswordResetsTable(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS password_resets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                token TEXT NOT NULL UNIQUE,
                expires_at TEXT NOT NULL,
                used_at TEXT,
                created_at TEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");

        $this->db->exec("
            CREATE INDEX IF NOT EXISTS idx_password_resets_token
            ON password_resets(token)
        ");
    }
}
