#!/usr/bin/env php
<?php
/**
 * Route Accessibility Test
 * Tests that all family sharing routes are registered correctly
 */

require_once __DIR__ . '/../src/Router.php';
require_once __DIR__ . '/../src/Application.php';
require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Database.php';

use BudgetApp\Application;

$GREEN = "\033[32m";
$RED = "\033[31m";
$BLUE = "\033[34m";
$RESET = "\033[0m";

echo "{$BLUE}========================================{$RESET}\n";
echo "{$BLUE}Route Accessibility Test{$RESET}\n";
echo "{$BLUE}========================================{$RESET}\n\n";

try {
    $app = new Application(__DIR__ . '/..');
    $router = $app->getRouter();

    $passed = 0;
    $failed = 0;

    // Test routes (method, path, description)
    $testRoutes = [
        // Household routes
        ['GET', '/household', 'Household list'],
        ['GET', '/household/1', 'Household detail'],
        ['POST', '/household/store', 'Create household'],
        ['POST', '/household/1/invite', 'Invite member'],

        // Invitation routes
        ['GET', '/invitation/accept/test-token', 'Accept invitation form'],
        ['POST', '/invitation/accept', 'Process invitation'],

        // Activity routes
        ['GET', '/activity/1', 'Activity feed'],

        // Notification routes
        ['GET', '/notifications', 'Notifications list'],
        ['GET', '/notifications/unread', 'Unread notifications'],
        ['POST', '/notifications/1/read', 'Mark notification as read'],

        // Approval routes
        ['GET', '/approval/household/1', 'Approval list'],
        ['POST', '/approval/1/approve', 'Approve request'],
        ['POST', '/approval/1/reject', 'Reject request'],

        // Child account routes
        ['GET', '/child-account/1', 'Child dashboard'],
        ['POST', '/child-account/1/money-request', 'Create money request'],
        ['POST', '/child-account/chore/1/complete', 'Complete chore'],

        // Chore routes
        ['GET', '/chores/household/1', 'Chore list'],
        ['POST', '/chores/store', 'Create chore'],
        ['POST', '/chores/completion/1/verify', 'Verify chore'],

        // Comment routes
        ['GET', '/comments/transaction/1', 'Get comments'],
        ['POST', '/comments/store', 'Create comment'],
    ];

    echo "{$BLUE}Testing Family Sharing Routes:{$RESET}\n\n";

    foreach ($testRoutes as $test) {
        list($method, $path, $description) = $test;

        $result = $router->match($method, $path);

        if ($result !== null) {
            echo "{$GREEN}✓{$RESET} {$method} {$path} - {$description}\n";
            $passed++;
        } else {
            echo "{$RED}✗{$RESET} {$method} {$path} - {$description} (NOT REGISTERED)\n";
            $failed++;
        }
    }

    echo "\n{$BLUE}========================================{$RESET}\n";
    echo "{$GREEN}Passed:{$RESET} $passed\n";
    echo "{$RED}Failed:{$RESET} $failed\n";
    echo "Total: " . ($passed + $failed) . "\n\n";

    if ($failed === 0) {
        echo "{$GREEN}All routes registered successfully!{$RESET}\n";
        exit(0);
    } else {
        echo "{$RED}Some routes failed registration{$RESET}\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "{$RED}Error: {$e->getMessage()}{$RESET}\n";
    exit(1);
}
