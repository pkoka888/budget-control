<?php
/**
 * Health Check Endpoint
 *
 * Provides comprehensive application health status for monitoring and deployment verification.
 * Returns JSON with overall status and individual component checks.
 *
 * HTTP Status Codes:
 * - 200: All systems operational
 * - 500: One or more critical components failing
 * - 503: Service unavailable (critical failure)
 */

header('Content-Type: application/json');

// Initialize health status
$health = [
    'status' => 'ok',
    'timestamp' => time(),
    'datetime' => date('Y-m-d H:i:s'),
    'checks' => [],
    'version' => '1.0.0',
    'environment' => getenv('APP_ENV') ?: 'production'
];

$hasErrors = false;
$hasCriticalErrors = false;

// =============================================================================
// CHECK 1: Database Connection
// =============================================================================
try {
    $dbPath = __DIR__ . '/../database/budget.db';

    if (!file_exists($dbPath)) {
        throw new Exception("Database file not found: $dbPath");
    }

    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Test query
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check database integrity
    $integrity = $db->query("PRAGMA integrity_check")->fetch(PDO::FETCH_ASSOC);

    $health['checks']['database'] = [
        'status' => 'ok',
        'message' => 'Database operational',
        'users_count' => (int)$result['count'],
        'integrity' => $integrity['integrity_check'],
        'size_mb' => round(filesize($dbPath) / 1024 / 1024, 2)
    ];

} catch (Exception $e) {
    $hasErrors = true;
    $hasCriticalErrors = true;
    $health['checks']['database'] = [
        'status' => 'error',
        'message' => 'Database connection failed',
        'error' => $e->getMessage()
    ];
}

// =============================================================================
// CHECK 2: Database Writability
// =============================================================================
try {
    $dbDir = __DIR__ . '/../database/';

    if (!is_writable($dbDir)) {
        throw new Exception("Database directory not writable");
    }

    if (!is_writable($dbPath)) {
        throw new Exception("Database file not writable");
    }

    // Test write operation
    $testFile = $dbDir . '.health_check_' . time();
    if (file_put_contents($testFile, 'test') === false) {
        throw new Exception("Cannot write to database directory");
    }
    unlink($testFile);

    $health['checks']['database_writability'] = [
        'status' => 'ok',
        'message' => 'Database directory writable',
        'permissions' => substr(sprintf('%o', fileperms($dbDir)), -4)
    ];

} catch (Exception $e) {
    $hasErrors = true;
    $hasCriticalErrors = true;
    $health['checks']['database_writability'] = [
        'status' => 'error',
        'message' => 'Database not writable',
        'error' => $e->getMessage()
    ];
}

// =============================================================================
// CHECK 3: Disk Space
// =============================================================================
try {
    $path = __DIR__ . '/..';
    $free = disk_free_space($path);
    $total = disk_total_space($path);
    $usedPercent = (1 - $free / $total) * 100;

    $status = 'ok';
    $message = 'Disk space healthy';

    if ($usedPercent >= 95) {
        $status = 'critical';
        $message = 'Disk space critically low';
        $hasCriticalErrors = true;
        $hasErrors = true;
    } elseif ($usedPercent >= 90) {
        $status = 'warning';
        $message = 'Disk space running low';
        $hasErrors = true;
    } elseif ($usedPercent >= 80) {
        $status = 'warning';
        $message = 'Disk space usage high';
    }

    $health['checks']['disk_space'] = [
        'status' => $status,
        'message' => $message,
        'used_percent' => round($usedPercent, 2),
        'free_gb' => round($free / 1024 / 1024 / 1024, 2),
        'total_gb' => round($total / 1024 / 1024 / 1024, 2)
    ];

} catch (Exception $e) {
    $hasErrors = true;
    $health['checks']['disk_space'] = [
        'status' => 'error',
        'message' => 'Cannot check disk space',
        'error' => $e->getMessage()
    ];
}

// =============================================================================
// CHECK 4: Required Directories Writable
// =============================================================================
$requiredDirs = [
    'uploads' => __DIR__ . '/../uploads',
    'database' => __DIR__ . '/../database',
    'user-data' => __DIR__ . '/../user-data'
];

$dirCheck = [];
$dirErrors = false;

foreach ($requiredDirs as $name => $path) {
    if (!is_dir($path)) {
        $dirCheck[$name] = [
            'status' => 'error',
            'message' => 'Directory does not exist',
            'path' => $path
        ];
        $dirErrors = true;
    } elseif (!is_writable($path)) {
        $dirCheck[$name] = [
            'status' => 'warning',
            'message' => 'Directory not writable',
            'path' => $path,
            'permissions' => substr(sprintf('%o', fileperms($path)), -4)
        ];
        $dirErrors = true;
    } else {
        $dirCheck[$name] = [
            'status' => 'ok',
            'message' => 'Writable',
            'permissions' => substr(sprintf('%o', fileperms($path)), -4)
        ];
    }
}

$health['checks']['writable_directories'] = [
    'status' => $dirErrors ? 'warning' : 'ok',
    'message' => $dirErrors ? 'Some directories have issues' : 'All directories writable',
    'directories' => $dirCheck
];

if ($dirErrors) {
    $hasErrors = true;
}

// =============================================================================
// CHECK 5: PHP Configuration
// =============================================================================
$phpCheck = [
    'status' => 'ok',
    'message' => 'PHP configuration healthy',
    'version' => PHP_VERSION,
    'extensions' => [
        'pdo_sqlite' => extension_loaded('pdo_sqlite'),
        'json' => extension_loaded('json'),
        'mbstring' => extension_loaded('mbstring'),
        'curl' => extension_loaded('curl'),
        'gd' => extension_loaded('gd')
    ],
    'settings' => [
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time')
    ]
];

// Check required extensions
$requiredExtensions = ['pdo_sqlite', 'json', 'mbstring'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

if (!empty($missingExtensions)) {
    $phpCheck['status'] = 'error';
    $phpCheck['message'] = 'Missing required PHP extensions';
    $phpCheck['missing_extensions'] = $missingExtensions;
    $hasCriticalErrors = true;
    $hasErrors = true;
}

$health['checks']['php'] = $phpCheck;

// =============================================================================
// CHECK 6: Session Directory
// =============================================================================
try {
    $sessionPath = session_save_path() ?: sys_get_temp_dir();

    if (!is_writable($sessionPath)) {
        throw new Exception("Session directory not writable: $sessionPath");
    }

    $health['checks']['sessions'] = [
        'status' => 'ok',
        'message' => 'Session directory writable',
        'path' => $sessionPath,
        'handler' => ini_get('session.save_handler')
    ];

} catch (Exception $e) {
    $hasErrors = true;
    $health['checks']['sessions'] = [
        'status' => 'warning',
        'message' => 'Session configuration issue',
        'error' => $e->getMessage()
    ];
}

// =============================================================================
// CHECK 7: Application Files
// =============================================================================
$criticalFiles = [
    'index.php' => __DIR__ . '/index.php',
    'config' => __DIR__ . '/../src/Config.php',
    'database_class' => __DIR__ . '/../src/Database.php',
    'router' => __DIR__ . '/../src/Router.php',
    'env' => __DIR__ . '/../.env'
];

$fileCheck = [];
$fileErrors = false;

foreach ($criticalFiles as $name => $path) {
    if (!file_exists($path)) {
        $fileCheck[$name] = [
            'status' => 'error',
            'message' => 'File not found',
            'path' => $path
        ];
        $fileErrors = true;
        if ($name !== 'env') { // .env is optional
            $hasCriticalErrors = true;
        }
    } else {
        $fileCheck[$name] = [
            'status' => 'ok',
            'message' => 'Found',
            'size' => filesize($path)
        ];
    }
}

$health['checks']['application_files'] = [
    'status' => $fileErrors ? 'error' : 'ok',
    'message' => $fileErrors ? 'Some critical files missing' : 'All critical files present',
    'files' => $fileCheck
];

if ($fileErrors) {
    $hasErrors = true;
}

// =============================================================================
// CHECK 8: Memory Usage
// =============================================================================
$memoryUsage = memory_get_usage(true);
$memoryLimit = ini_get('memory_limit');

// Convert memory limit to bytes
$memoryLimitBytes = $memoryLimit;
if (preg_match('/^(\d+)(.)$/', $memoryLimit, $matches)) {
    $memoryLimitBytes = $matches[1];
    switch ($matches[2]) {
        case 'G': $memoryLimitBytes *= 1024;
        case 'M': $memoryLimitBytes *= 1024;
        case 'K': $memoryLimitBytes *= 1024;
    }
}

$memoryUsedPercent = ($memoryUsage / $memoryLimitBytes) * 100;

$health['checks']['memory'] = [
    'status' => $memoryUsedPercent > 90 ? 'warning' : 'ok',
    'message' => $memoryUsedPercent > 90 ? 'High memory usage' : 'Memory usage normal',
    'used_mb' => round($memoryUsage / 1024 / 1024, 2),
    'limit' => $memoryLimit,
    'used_percent' => round($memoryUsedPercent, 2)
];

// =============================================================================
// OVERALL STATUS
// =============================================================================
if ($hasCriticalErrors) {
    $health['status'] = 'critical';
    $health['message'] = 'Critical system failures detected';
    http_response_code(503); // Service Unavailable
} elseif ($hasErrors) {
    $health['status'] = 'degraded';
    $health['message'] = 'System operational with warnings';
    http_response_code(200); // Still operational but degraded
} else {
    $health['status'] = 'healthy';
    $health['message'] = 'All systems operational';
    http_response_code(200);
}

// =============================================================================
// RESPONSE METADATA
// =============================================================================
$health['metadata'] = [
    'hostname' => gethostname(),
    'php_sapi' => php_sapi_name(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'
];

// Output JSON response
echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
exit;
