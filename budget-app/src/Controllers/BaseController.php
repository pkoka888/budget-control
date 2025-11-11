<?php
namespace BudgetApp\Controllers;

use BudgetApp\Application;
use BudgetApp\Database;

abstract class BaseController {
    protected Application $app;
    protected Database $db;

    public function __construct(Application $app) {
        $this->app = $app;
        $this->db = $app->getDatabase();
        $this->requireAuth();
    }

    protected function requireAuth(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }

    protected function getUserId(): int {
        return $_SESSION['user_id'] ?? 0;
    }

    protected function render(string $template, array $data = []): string {
        $data['user_id'] = $this->getUserId();
        $data['user'] = $this->getUser();
        return $this->app->render($template, $data);
    }

    protected function getUser(): ?array {
        $userId = $this->getUserId();
        if (!$userId) {
            return null;
        }

        return $this->db->queryOne(
            "SELECT id, name, email, currency, timezone FROM users WHERE id = ?",
            [$userId]
        );
    }

    protected function json(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function redirect(string $path): void {
        header("Location: {$path}");
        exit;
    }

    protected function validate(array $data, array $rules): array {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            foreach (explode('|', $fieldRules) as $rule) {
                if ($rule === 'required' && empty($value)) {
                    $errors[$field] = "Pole {$field} je povinné";
                } elseif (strpos($rule, 'min:') === 0) {
                    $min = substr($rule, 4);
                    if (strlen($value) < $min) {
                        $errors[$field] = "Minimální délka je {$min} znaků";
                    }
                } elseif (strpos($rule, 'max:') === 0) {
                    $max = substr($rule, 4);
                    if (strlen($value) > $max) {
                        $errors[$field] = "Maximální délka je {$max} znaků";
                    }
                } elseif ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "Zadejte platný e-mail";
                } elseif ($rule === 'numeric' && !is_numeric($value)) {
                    $errors[$field] = "Pole musí být číslo";
                }
            }
        }

        return $errors;
    }

    protected function getQueryParam(string $key, $default = null) {
        return $_GET[$key] ?? $default;
    }

    protected function getPostParam(string $key, $default = null) {
        return $_POST[$key] ?? $default;
    }

    protected function setFlash(string $type, string $message): void {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    protected function getFlash(): ?array {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}
