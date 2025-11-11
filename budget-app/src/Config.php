<?php
namespace BudgetApp;

class Config {
    private string $basePath;
    private array $config = [];

    public function __construct(string $basePath = __DIR__ . '/..') {
        $this->basePath = rtrim($basePath, '/');
        $this->loadConfig();
    }

    private function loadConfig(): void {
        // Default configuration
        $this->config = [
            'app_name' => 'Budget Control',
            'version' => '1.0.0',
            'timezone' => 'Europe/Prague',
            'currency' => 'CZK',
            'debug' => true,
            'database' => [
                'type' => 'sqlite',
                'path' => $this->basePath . '/database/budget.db'
            ],
            'csv' => [
                'max_size' => 10 * 1024 * 1024, // 10MB
                'allowed_types' => ['text/csv', 'application/csv'],
                'upload_dir' => $this->basePath . '/uploads/csv'
            ],
            'api' => [
                'timeout' => 30,
                'max_retries' => 3
            ]
        ];

        // Load .env if exists
        $envPath = $this->basePath . '/.env';
        if (file_exists($envPath)) {
            $this->loadEnv($envPath);
        }
    }

    private function loadEnv(string $path): void {
        $lines = file($path);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] === '#') {
                continue;
            }

            if (strpos($line, '=') === false) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes
            if (in_array($value[0] ?? '', ['"', "'"])) {
                $value = substr($value, 1, -1);
            }

            $_ENV[$key] = $value;
        }
    }

    public function get(string $key, $default = null) {
        return $this->config[$key] ?? $default;
    }

    public function set(string $key, $value): void {
        $this->config[$key] = $value;
    }

    public function getBasePath(): string {
        return $this->basePath;
    }

    public function getDatabasePath(): string {
        $dbPath = $this->config['database']['path'];
        // Create directory if it doesn't exist
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dbPath;
    }

    public function getViewPath(): string {
        return $this->basePath . '/views';
    }

    public function getAssetPath(): string {
        return $this->basePath . '/public/assets';
    }

    public function getUploadPath(): string {
        $uploadDir = $this->config['csv']['upload_dir'];
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        return $uploadDir;
    }

    public function isDebug(): bool {
        return $this->config['debug'] ?? false;
    }
}
