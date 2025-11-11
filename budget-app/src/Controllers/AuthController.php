<?php
namespace BudgetApp\Controllers;

use BudgetApp\Application;

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

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $user = $this->db->queryOne(
            "SELECT id, password_hash FROM users WHERE email = ?",
            [$email]
        );

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
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
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $_SESSION['user_id'] = $userId;
        header('Location: /');
        exit;
    }

    public function logout(): void {
        session_destroy();
        header('Location: /login');
        exit;
    }
}
