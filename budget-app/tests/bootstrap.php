<?php
// Test bootstrap file for Budget App
// This file sets up the testing environment

// Define test environment
define('TESTING', true);

// Include autoloader if using Composer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Include application classes manually if no autoloader
// Adjust paths as needed for your project structure
$includePaths = [
    __DIR__ . '/../src/',
    __DIR__ . '/../src/Controllers/',
    __DIR__ . '/../src/Services/',
    __DIR__ . '/../src/Middleware/',
    __DIR__ . '/../'
];

set_include_path(get_include_path() . PATH_SEPARATOR . implode(PATH_SEPARATOR, $includePaths));

// Error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set up test database connection if needed
// You might want to use an in-memory SQLite database for tests
define('TEST_DB_PATH', __DIR__ . '/test_database.db');

// Clean up any existing test database
if (file_exists(TEST_DB_PATH)) {
    unlink(TEST_DB_PATH);
}

// Timezone for consistent testing
date_default_timezone_set('Europe/Prague');

// Custom test helpers
function createTestDatabase(): PDO {
    $pdo = new PDO('sqlite:' . TEST_DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create basic schema for testing
    $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
    $pdo->exec($schema);

    return $pdo;
}

function cleanupTestDatabase(): void {
    if (file_exists(TEST_DB_PATH)) {
        unlink(TEST_DB_PATH);
    }
}

// Mock data helpers
function createMockUser(): array {
    return [
        'id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
        'currency' => 'CZK',
        'timezone' => 'Europe/Prague'
    ];
}

function createMockTransaction(): array {
    return [
        'id' => 1,
        'user_id' => 1,
        'account_id' => 1,
        'category_id' => 1,
        'type' => 'expense',
        'description' => 'Test transaction',
        'amount' => -100.00,
        'currency' => 'CZK',
        'date' => '2024-01-15'
    ];
}

function createMockAccount(): array {
    return [
        'id' => 1,
        'user_id' => 1,
        'name' => 'Test Account',
        'type' => 'checking',
        'currency' => 'CZK',
        'balance' => 5000.00
    ];
}

// JSON test data helpers
function createMockCsobJson(): array {
    return [
        'ucet' => [
            'cisloUctu' => '1234567890',
            'nazevUctu' => 'Osobní účet',
            'mena' => 'CZK',
            'zustatek' => 15000.50,
            'dostupnyZustatek' => 14500.50,
            'typUctu' => 'běžný',
            'iban' => 'CZ1234567890123456789012',
            'bic' => 'KOMBCZPP'
        ],
        'transakce' => [
            [
                'id' => 'tx001',
                'datum' => '2024-01-15',
                'castka' => -500.00,
                'mena' => 'CZK',
                'typ' => 'výběr',
                'popis' => 'Výběr hotovosti ATM Praha',
                'referencniCislo' => 'REF001',
                'zustatekPo' => 14500.50
            ]
        ]
    ];
}

function createMockCeskaSporitelnaJson(): array {
    return [
        'account' => [
            'number' => '9876543210',
            'name' => 'Spořicí účet',
            'currency' => 'CZK',
            'balance' => 25000.00,
            'availableBalance' => 25000.00,
            'type' => 'spořicí'
        ],
        'transactions' => [
            [
                'id' => 'cs001',
                'date' => '2024-01-20',
                'amount' => -1200.00,
                'description' => 'Nákup Tesco Palackého',
                'reference' => 'TES001',
                'balanceAfter' => 23800.00
            ]
        ]
    ];
}

function createMockKomercniBankaJson(): array {
    return [
        'accountNumber' => '555666777',
        'accountName' => 'Firemní účet',
        'currency' => 'CZK',
        'balance' => 50000.00,
        'availableBalance' => 48000.00,
        'accountType' => 'firemní',
        'transactionList' => [
            [
                'transactionId' => 'kb001',
                'date' => '2024-01-25',
                'amount' => 15000.00,
                'description' => 'Faktura za služby - Klient XYZ',
                'referenceNumber' => 'INV001',
                'balanceAfter' => 65000.00
            ]
        ]
    ];
}

// Performance testing helpers
function startTimer(): float {
    return microtime(true);
}

function endTimer(float $startTime): float {
    return microtime(true) - $startTime;
}

function assertPerformance(float $duration, float $maxDuration = 1.0): void {
    if ($duration > $maxDuration) {
        throw new \Exception("Performance test failed: {$duration}s exceeded {$maxDuration}s limit");
    }
}

// Memory usage testing
function getMemoryUsage(): int {
    return memory_get_peak_usage(true);
}

function assertMemoryUsage(int $before, int $after, int $maxIncrease = 1048576): void {
    $increase = $after - $before;
    if ($increase > $maxIncrease) {
        throw new \Exception("Memory usage test failed: {$increase} bytes exceeded {$maxIncrease} bytes limit");
    }
}