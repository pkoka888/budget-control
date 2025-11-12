#!/usr/bin/env php
<?php
/**
 * Family Sharing Automation Script
 *
 * Run this script via cron to automate household tasks:
 * - Process allowance payments
 * - Expire old invitations
 * - Expire old approval requests
 * - Delete old notifications
 * - Send daily/weekly digests
 *
 * Recommended cron schedule:
 * 0 0 * * * /path/to/family_sharing_automation.php daily
 * 0 */6 * * * /path/to/family_sharing_automation.php periodic
 */

require_once __DIR__ . '/../budget-app/src/bootstrap.php';

use BudgetApp\Services\ChildAccountService;
use BudgetApp\Services\InvitationService;
use BudgetApp\Services\ApprovalService;
use BudgetApp\Services\NotificationService;
use BudgetApp\Database;

try {
    $db = Database::getInstance()->getPDO();
    $command = $argv[1] ?? 'periodic';

    echo "🤖 Family Sharing Automation - " . date('Y-m-d H:i:s') . "\n";
    echo "Command: $command\n\n";

    // Initialize services
    $childAccountService = new ChildAccountService($db);
    $invitationService = new InvitationService($db);
    $approvalService = new ApprovalService($db);
    $notificationService = new NotificationService($db);

    $results = [];

    // PERIODIC TASKS (Run every 6 hours)
    if ($command === 'periodic' || $command === 'all') {
        echo "📅 Running periodic tasks...\n";

        // 1. Expire old invitations
        echo "  - Expiring old invitations...\n";
        $expired = $invitationService->expireOldInvitations();
        $results['expired_invitations'] = $expired;
        echo "    Expired: $expired invitations\n";

        // 2. Expire old approval requests
        echo "  - Expiring old approval requests...\n";
        $expired = $approvalService->expireOldRequests();
        $results['expired_approvals'] = $expired;
        echo "    Expired: $expired approval requests\n";

        // 3. Delete expired notifications
        echo "  - Cleaning up old notifications...\n";
        $deleted = $notificationService->deleteExpired();
        $results['deleted_notifications'] = $deleted;
        echo "    Deleted: $deleted notifications\n";

        echo "✅ Periodic tasks complete\n\n";
    }

    // DAILY TASKS (Run once per day at midnight)
    if ($command === 'daily' || $command === 'all') {
        echo "📅 Running daily tasks...\n";

        // 1. Process allowance payments
        echo "  - Processing allowance payments...\n";
        $processed = $childAccountService->processAllowancePayments();
        $results['allowances_processed'] = $processed;
        echo "    Processed: $processed allowances\n";

        // 2. Send daily activity digests
        echo "  - Sending daily digests...\n";
        $sent = sendDailyDigests($db);
        $results['digests_sent'] = $sent;
        echo "    Sent: $sent digests\n";

        echo "✅ Daily tasks complete\n\n";
    }

    // OUTPUT SUMMARY
    echo "📊 Summary:\n";
    foreach ($results as $task => $count) {
        echo "  " . ucwords(str_replace('_', ' ', $task)) . ": $count\n";
    }

    echo "\n✅ Automation completed successfully!\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Send daily activity digests to users
 */
function sendDailyDigests(PDO $db): int
{
    // Get users who want daily digests
    $stmt = $db->query("
        SELECT DISTINCT u.id, u.email, u.name
        FROM users u
        JOIN notification_preferences np ON u.id = np.user_id
        WHERE np.activity_digest = 'daily' OR np.email_daily_summary = 1
    ");

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $sent = 0;

    foreach ($users as $user) {
        // Get unread notifications from last 24 hours
        $stmt = $db->prepare("
            SELECT * FROM notifications
            WHERE user_id = ? AND created_at >= datetime('now', '-1 day')
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$user['id']]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($notifications)) {
            continue;
        }

        // Send digest email (would use EmailService in production)
        echo "    - Sending digest to {$user['email']} ({count($notifications)} notifications)\n";
        // $emailService->sendTemplate($user['email'], 'daily_digest', ['notifications' => $notifications]);

        $sent++;
    }

    return $sent;
}
