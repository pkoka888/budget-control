<?php
// Budget Control - Main Entry Point
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Europe/Prague');

// Define paths
define('BASE_PATH', dirname(__DIR__));
define('SRC_PATH', BASE_PATH . '/src');

// Load environment variables
$env_file = BASE_PATH . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;

        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value, '\'"');
    }
}

// Autoloader
spl_autoload_register(function ($class) {
    $file = SRC_PATH . '/' . str_replace('\\', '/', str_replace('BudgetApp\\', '', $class)) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Start application
try {
    $app = new \BudgetApp\Application(BASE_PATH);
    $app->run();
} catch (\Exception $e) {
    http_response_code(500);
    echo '<h1>Error</h1>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    if (getenv('DEBUG')) {
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }
}
