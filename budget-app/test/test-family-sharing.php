#!/usr/bin/env php
<?php
/**
 * Family Sharing Feature Test Suite
 * Tests all critical family sharing functionality
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration
$dbPath = __DIR__ . '/../database/budget.db';
$baseUrl = 'http://localhost:8000'; // Adjust if needed

// Test Results
$tests = [];
$passed = 0;
$failed = 0;

// Colors for output
$GREEN = "\033[32m";
$RED = "\033[31m";
$YELLOW = "\033[33m";
$BLUE = "\033[34m";
$RESET = "\033[0m";

echo "{$BLUE}========================================{$RESET}\n";
echo "{$BLUE}Family Sharing Feature Test Suite{$RESET}\n";
echo "{$BLUE}========================================{$RESET}\n\n";

// Test Database Connection
function testDatabaseConnection($dbPath) {
    global $GREEN, $RED, $tests, $passed, $failed;

    try {
        $db = new PDO("sqlite:$dbPath");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $tests[] = [
            'name' => 'Database Connection',
            'status' => 'PASS',
            'message' => 'Successfully connected to database'
        ];
        $passed++;
        echo "{$GREEN}✓{$RESET} Database Connection\n";
        return $db;
    } catch (Exception $e) {
        $tests[] = [
            'name' => 'Database Connection',
            'status' => 'FAIL',
            'message' => $e->getMessage()
        ];
        $failed++;
        echo "{$RED}✗{$RESET} Database Connection: {$e->getMessage()}\n";
        return null;
    }
}

// Test Table Existence
function testTableExists($db, $tableName) {
    global $GREEN, $RED, $tests, $passed, $failed;

    try {
        $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$tableName'");
        $result = $stmt->fetch();

        if ($result) {
            $tests[] = [
                'name' => "Table Exists: $tableName",
                'status' => 'PASS',
                'message' => "Table '$tableName' exists"
            ];
            $passed++;
            echo "{$GREEN}✓{$RESET} Table Exists: $tableName\n";
            return true;
        } else {
            $tests[] = [
                'name' => "Table Exists: $tableName",
                'status' => 'FAIL',
                'message' => "Table '$tableName' does not exist"
            ];
            $failed++;
            echo "{$RED}✗{$RESET} Table Exists: $tableName (NOT FOUND)\n";
            return false;
        }
    } catch (Exception $e) {
        $tests[] = [
            'name' => "Table Exists: $tableName",
            'status' => 'FAIL',
            'message' => $e->getMessage()
        ];
        $failed++;
        echo "{$RED}✗{$RESET} Table Exists: $tableName ({$e->getMessage()})\n";
        return false;
    }
}

// Test Column Existence
function testColumnExists($db, $tableName, $columnName) {
    global $GREEN, $RED, $YELLOW, $tests, $passed, $failed;

    try {
        $stmt = $db->query("PRAGMA table_info($tableName)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($columns as $col) {
            if ($col['name'] === $columnName) {
                $tests[] = [
                    'name' => "Column Exists: $tableName.$columnName",
                    'status' => 'PASS',
                    'message' => "Column exists with type: {$col['type']}"
                ];
                $passed++;
                echo "{$GREEN}✓{$RESET} Column Exists: $tableName.$columnName ({$col['type']})\n";
                return true;
            }
        }

        $tests[] = [
            'name' => "Column Exists: $tableName.$columnName",
            'status' => 'FAIL',
            'message' => "Column does not exist"
        ];
        $failed++;
        echo "{$RED}✗{$RESET} Column Exists: $tableName.$columnName (NOT FOUND)\n";
        return false;
    } catch (Exception $e) {
        $tests[] = [
            'name' => "Column Exists: $tableName.$columnName",
            'status' => 'FAIL',
            'message' => $e->getMessage()
        ];
        $failed++;
        echo "{$RED}✗{$RESET} Column Exists: $tableName.$columnName ({$e->getMessage()})\n";
        return false;
    }
}

// Test View File Existence
function testViewExists($filePath) {
    global $GREEN, $RED, $tests, $passed, $failed;

    $fullPath = __DIR__ . '/../' . $filePath;

    if (file_exists($fullPath)) {
        $tests[] = [
            'name' => "View Exists: $filePath",
            'status' => 'PASS',
            'message' => 'File exists'
        ];
        $passed++;
        echo "{$GREEN}✓{$RESET} View Exists: $filePath\n";
        return true;
    } else {
        $tests[] = [
            'name' => "View Exists: $filePath",
            'status' => 'FAIL',
            'message' => 'File not found'
        ];
        $failed++;
        echo "{$RED}✗{$RESET} View Exists: $filePath (NOT FOUND)\n";
        return false;
    }
}

// Test Service File Existence
function testServiceExists($filePath) {
    global $GREEN, $RED, $tests, $passed, $failed;

    $fullPath = __DIR__ . '/../' . $filePath;

    if (file_exists($fullPath)) {
        // Try to parse PHP file for syntax errors
        $output = shell_exec("php -l \"$fullPath\" 2>&1");

        if (strpos($output, 'No syntax errors') !== false) {
            $tests[] = [
                'name' => "Service Exists: $filePath",
                'status' => 'PASS',
                'message' => 'File exists and has valid syntax'
            ];
            $passed++;
            echo "{$GREEN}✓{$RESET} Service Exists: $filePath\n";
            return true;
        } else {
            $tests[] = [
                'name' => "Service Exists: $filePath",
                'status' => 'FAIL',
                'message' => "Syntax error: $output"
            ];
            $failed++;
            echo "{$RED}✗{$RESET} Service Exists: $filePath (SYNTAX ERROR)\n";
            return false;
        }
    } else {
        $tests[] = [
            'name' => "Service Exists: $filePath",
            'status' => 'FAIL',
            'message' => 'File not found'
        ];
        $failed++;
        echo "{$RED}✗{$RESET} Service Exists: $filePath (NOT FOUND)\n";
        return false;
    }
}

// ====================
// RUN TESTS
// ====================

echo "\n{$BLUE}=== Database Tests ==={$RESET}\n";
$db = testDatabaseConnection($dbPath);

if ($db) {
    // Test core tables
    echo "\n{$BLUE}=== Table Existence Tests ==={$RESET}\n";
    testTableExists($db, 'households');
    testTableExists($db, 'household_members');
    testTableExists($db, 'household_invitations');
    testTableExists($db, 'household_settings');
    testTableExists($db, 'household_activities');
    testTableExists($db, 'approval_requests');
    testTableExists($db, 'notifications');
    testTableExists($db, 'audit_logs');
    testTableExists($db, 'child_account_settings');
    testTableExists($db, 'child_allowances');
    testTableExists($db, 'chores');
    testTableExists($db, 'chore_completions');

    // Test critical columns
    echo "\n{$BLUE}=== Column Existence Tests ==={$RESET}\n";
    testColumnExists($db, 'transactions', 'household_id');
    testColumnExists($db, 'transactions', 'visibility');
    testColumnExists($db, 'budgets', 'household_id');
    testColumnExists($db, 'budgets', 'visibility');
    testColumnExists($db, 'goals', 'household_id');
    testColumnExists($db, 'goals', 'visibility');
    testColumnExists($db, 'household_members', 'role');
    testColumnExists($db, 'household_members', 'permission_level');

    // Test data integrity
    echo "\n{$BLUE}=== Data Integrity Tests ==={$RESET}\n";

    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM households");
        $result = $stmt->fetch();
        echo "{$GREEN}✓{$RESET} Households table accessible (rows: {$result['count']})\n";
        $passed++;
    } catch (Exception $e) {
        echo "{$RED}✗{$RESET} Households table access failed: {$e->getMessage()}\n";
        $failed++;
    }
}

// Test view files
echo "\n{$BLUE}=== View File Tests ==={$RESET}\n";
testViewExists('views/household/show.php');
testViewExists('views/invitation/accept.php');
testViewExists('views/approval/index.php');
testViewExists('views/activity/index.php');
testViewExists('views/child-account/index.php');
testViewExists('views/chores/index.php');
testViewExists('views/chores/my-chores.php');
testViewExists('views/partials/notification-bell.php');
testViewExists('views/partials/visibility-toggle.php');

// Test service files
echo "\n{$BLUE}=== Service File Tests ==={$RESET}\n";
testServiceExists('src/Services/PermissionService.php');
testServiceExists('src/Services/HouseholdService.php');
testServiceExists('src/Services/InvitationService.php');
testServiceExists('src/Services/ActivityService.php');
testServiceExists('src/Services/NotificationService.php');
testServiceExists('src/Services/ApprovalService.php');
testServiceExists('src/Services/CommentService.php');
testServiceExists('src/Services/ChildAccountService.php');
testServiceExists('src/Services/ChoreService.php');

// Test controller files
echo "\n{$BLUE}=== Controller File Tests ==={$RESET}\n";
testServiceExists('src/Controllers/HouseholdController.php');
testServiceExists('src/Controllers/NotificationController.php');
testServiceExists('src/Controllers/ApprovalController.php');
testServiceExists('src/Controllers/ChildAccountController.php');

// Test JavaScript files
echo "\n{$BLUE}=== JavaScript File Tests ==={$RESET}\n";
testViewExists('public/js/household.js');
testViewExists('public/js/approvals.js');
testViewExists('public/js/chores.js');
testViewExists('public/js/child-account.js');

// Test automation files
echo "\n{$BLUE}=== Automation File Tests ==={$RESET}\n";
testViewExists('../cron/family_sharing_automation.php');

// ====================
// SUMMARY
// ====================

echo "\n{$BLUE}========================================{$RESET}\n";
echo "{$BLUE}Test Summary{$RESET}\n";
echo "{$BLUE}========================================{$RESET}\n";
echo "{$GREEN}Passed:{$RESET} $passed\n";
echo "{$RED}Failed:{$RESET} $failed\n";
echo "Total: " . ($passed + $failed) . "\n";

$successRate = $passed + $failed > 0 ? round(($passed / ($passed + $failed)) * 100, 2) : 0;
echo "\nSuccess Rate: ";

if ($successRate >= 90) {
    echo "{$GREEN}$successRate%{$RESET} ✓\n";
} elseif ($successRate >= 70) {
    echo "{$YELLOW}$successRate%{$RESET} ⚠\n";
} else {
    echo "{$RED}$successRate%{$RESET} ✗\n";
}

echo "\n";

// Exit with appropriate code
exit($failed > 0 ? 1 : 0);
