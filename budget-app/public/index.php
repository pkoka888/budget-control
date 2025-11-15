<?php
/**
 * Budget Control Application - Entry Point
 *
 * This is the main entry point for the Budget Control application.
 * All HTTP requests are routed through this file.
 */

// Set timezone
date_default_timezone_set('Europe/Prague');

// Error reporting (will be overridden by Config debug setting)
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Autoload composer dependencies
$autoloadPath = BASE_PATH . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    // Fallback to manual autoloading if composer not installed
    spl_autoload_register(function ($class) {
        $prefix = 'BudgetApp\\';
        $baseDir = BASE_PATH . '/src/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    });
}

// Configure and start secure session
use BudgetApp\SessionConfig;
SessionConfig::start();

// Check if database exists
$dbPath = BASE_PATH . '/database/budget.db';
if (!file_exists($dbPath)) {
    // Show setup message
    http_response_code(503);
    ?>
    <!DOCTYPE html>
    <html lang="cs">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Setup Required - Budget Control</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0;
                padding: 20px;
            }
            .setup-box {
                background: white;
                border-radius: 12px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                padding: 40px;
                max-width: 600px;
                width: 100%;
            }
            h1 {
                color: #2d3748;
                margin: 0 0 10px 0;
                font-size: 32px;
            }
            .subtitle {
                color: #718096;
                margin: 0 0 30px 0;
                font-size: 16px;
            }
            .step {
                background: #f7fafc;
                border-left: 4px solid #667eea;
                padding: 15px 20px;
                margin: 15px 0;
                border-radius: 4px;
            }
            .step h3 {
                margin: 0 0 10px 0;
                color: #2d3748;
                font-size: 18px;
            }
            .step code {
                background: #2d3748;
                color: #48bb78;
                padding: 8px 12px;
                border-radius: 4px;
                display: block;
                font-family: 'Monaco', 'Courier New', monospace;
                font-size: 14px;
                margin: 10px 0;
            }
            .warning {
                background: #fef5e7;
                border-left: 4px solid #f39c12;
                padding: 15px 20px;
                margin: 20px 0;
                border-radius: 4px;
            }
            .icon {
                font-size: 48px;
                margin-bottom: 20px;
            }
        </style>
    </head>
    <body>
        <div class="setup-box">
            <div class="icon">🚀</div>
            <h1>Database Setup Required</h1>
            <p class="subtitle">Your Budget Control application needs to be initialized</p>

            <div class="warning">
                <strong>⚠️ Database not found</strong><br>
                The application database doesn't exist yet. Please run the initialization script to set up your database.
            </div>

            <div class="step">
                <h3>Step 1: Initialize Database</h3>
                <p>Run the database initialization script from your project root:</p>
                <code>php budget-app/database/init.php</code>
            </div>

            <div class="step">
                <h3>Step 2: Refresh This Page</h3>
                <p>Once the database is initialized, refresh this page to start using Budget Control.</p>
            </div>

            <div class="step">
                <h3>Alternative: Run All Setup</h3>
                <p>If you have a setup script available:</p>
                <code>bash setup.sh</code>
            </div>

            <p style="color: #718096; margin-top: 30px; font-size: 14px;">
                📖 Need help? Check the documentation or README.md for detailed setup instructions.
            </p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Initialize application
try {
    $app = new \BudgetApp\Application(BASE_PATH);

    // Handle request
    $app->run();

} catch (\Exception $e) {
    // Log error in production
    if (!ini_get('display_errors')) {
        error_log($e->getMessage());
    }

    // Display error
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="cs">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Application Error - Budget Control</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: #f7fafc;
                padding: 40px 20px;
                margin: 0;
            }
            .error-box {
                background: white;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                padding: 30px;
                max-width: 700px;
                margin: 0 auto;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            h1 {
                color: #e53e3e;
                margin: 0 0 20px 0;
            }
            .error-message {
                background: #fff5f5;
                border: 1px solid #feb2b2;
                border-radius: 4px;
                padding: 15px;
                color: #742a2a;
                font-family: monospace;
                white-space: pre-wrap;
                word-wrap: break-word;
            }
            .help {
                margin-top: 20px;
                color: #718096;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h1>⚠️ Application Error</h1>
            <p>An error occurred while processing your request:</p>
            <div class="error-message"><?php echo htmlspecialchars($e->getMessage()); ?></div>
            <?php if (ini_get('display_errors')): ?>
                <div class="error-message" style="margin-top: 15px; font-size: 12px;">
                    <strong>Stack Trace:</strong>
                    <?php echo htmlspecialchars($e->getTraceAsString()); ?>
                </div>
            <?php endif; ?>
            <div class="help">
                If this problem persists, please check your application logs or contact support.
            </div>
        </div>
    </body>
    </html>
    <?php
}
