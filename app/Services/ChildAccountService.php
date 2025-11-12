<?php

namespace App\Services;

use PDO;

/**
 * Child Account Service
 *
 * Manages child accounts, allowances, spending limits, and money requests
 */
class ChildAccountService
{
    private PDO $db;
    private NotificationService $notificationService;
    private ActivityService $activityService;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->notificationService = new NotificationService($db);
        $this->activityService = new ActivityService($db);
    }

    /**
     * Create child account settings
     */
    public function createChildSettings(int $householdMemberId, int $userId, int $householdId, int $supervisedBy, array $settings = []): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO child_account_settings
            (household_member_id, user_id, household_id, supervised_by, daily_limit, weekly_limit, monthly_limit, per_transaction_limit, requires_approval_above)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $householdMemberId,
            $userId,
            $householdId,
            $supervisedBy,
            $settings['daily_limit'] ?? 10.00,
            $settings['weekly_limit'] ?? 50.00,
            $settings['monthly_limit'] ?? 200.00,
            $settings['per_transaction_limit'] ?? 20.00,
            $settings['requires_approval_above'] ?? 10.00
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Update child settings
     */
    public function updateChildSettings(int $userId, int $householdId, int $supervisedBy, array $settings): bool
    {
        $fields = [];
        $values = [];

        foreach ($settings as $key => $value) {
            $fields[] = "{$key} = ?";
            $values[] = $value;
        }

        $values[] = $userId;
        $values[] = $householdId;

        $sql = "UPDATE child_account_settings SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND household_id = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Get child settings
     */
    public function getChildSettings(int $userId, int $householdId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM child_account_settings WHERE user_id = ? AND household_id = ?");
        $stmt->execute([$userId, $householdId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Add balance to child account
     */
    public function addBalance(int $userId, int $householdId, float $amount, string $source = 'allowance'): bool
    {
        $stmt = $this->db->prepare("
            UPDATE child_account_settings
            SET current_balance = current_balance + ?
            WHERE user_id = ? AND household_id = ?
        ");

        return $stmt->execute([$amount, $userId, $householdId]);
    }

    /**
     * Deduct balance from child account
     */
    public function deductBalance(int $userId, int $householdId, float $amount): bool
    {
        $stmt = $this->db->prepare("
            UPDATE child_account_settings
            SET current_balance = current_balance - ?
            WHERE user_id = ? AND household_id = ? AND current_balance >= ?
        ");

        return $stmt->execute([$amount, $userId, $householdId, $amount]);
    }

    /**
     * Create allowance
     */
    public function createAllowance(int $householdId, int $childUserId, int $parentUserId, array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO allowances
            (household_id, child_user_id, parent_user_id, amount, frequency, day_of_payment, next_payment_date, requires_chores, min_chores_required, auto_split_savings, savings_percentage)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $householdId,
            $childUserId,
            $parentUserId,
            $data['amount'],
            $data['frequency'],
            $data['day_of_payment'] ?? null,
            $this->calculateNextPaymentDate($data['frequency'], $data['day_of_payment'] ?? null),
            $data['requires_chores'] ?? 0,
            $data['min_chores_required'] ?? 0,
            $data['auto_split_savings'] ?? 0,
            $data['savings_percentage'] ?? 0.00
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Process allowance payments
     */
    public function processAllowancePayments(): int
    {
        // Get due allowances
        $stmt = $this->db->prepare("
            SELECT * FROM allowances
            WHERE is_active = 1 AND next_payment_date <= DATE('now')
        ");
        $stmt->execute();
        $allowances = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $processed = 0;

        foreach ($allowances as $allowance) {
            // Check chore requirement
            if ($allowance['requires_chores']) {
                $choresCompleted = $this->getCompletedChoresCount($allowance['child_user_id'], $allowance['household_id']);
                if ($choresCompleted < $allowance['min_chores_required']) {
                    // Skip payment
                    $this->createAllowancePayment($allowance, 'skipped', 'Insufficient chores completed');
                    continue;
                }
            }

            // Process payment
            $this->addBalance($allowance['child_user_id'], $allowance['household_id'], $allowance['amount'], 'allowance');

            // Create payment record
            $this->createAllowancePayment($allowance, 'completed');

            // Update next payment date
            $nextDate = $this->calculateNextPaymentDate($allowance['frequency'], $allowance['day_of_payment']);
            $stmt = $this->db->prepare("
                UPDATE allowances
                SET next_payment_date = ?, last_payment_date = DATE('now')
                WHERE id = ?
            ");
            $stmt->execute([$nextDate, $allowance['id']]);

            // Notify child
            $this->notificationService->create(
                $allowance['household_id'],
                $allowance['child_user_id'],
                'activity',
                'Allowance Received',
                "You received your {$allowance['frequency']} allowance of " . number_format($allowance['amount'], 2) . " CZK",
                'normal',
                ['icon' => '💰']
            );

            $processed++;
        }

        return $processed;
    }

    /**
     * Create money request
     */
    public function createMoneyRequest(int $householdId, int $requestedBy, int $requestedFrom, float $amount, string $reason, ?string $category = null): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO money_requests (household_id, requested_by, requested_from, amount, reason, category)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([$householdId, $requestedBy, $requestedFrom, $amount, $reason, $category]);

        $requestId = (int)$this->db->lastInsertId();

        // Notify parent
        $stmt = $this->db->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$requestedBy]);
        $child = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->notificationService->create(
            $householdId,
            $requestedFrom,
            'approval',
            'Money Request',
            "{$child['username']} requested " . number_format($amount, 2) . " CZK for: {$reason}",
            'high',
            ['action_url' => "/child-account/money-request/{$requestId}", 'action_label' => 'Review']
        );

        return $requestId;
    }

    /**
     * Approve money request
     */
    public function approveMoneyRequest(int $requestId, int $parentUserId, ?string $notes = null): bool
    {
        $request = $this->getMoneyRequest($requestId);

        if (!$request || $request['status'] !== 'pending') {
            throw new \Exception("Invalid or already processed request");
        }

        // Add balance
        $this->addBalance($request['requested_by'], $request['household_id'], $request['amount'], 'money_request');

        // Update request
        $stmt = $this->db->prepare("
            UPDATE money_requests
            SET status = 'approved', reviewed_at = CURRENT_TIMESTAMP, review_notes = ?
            WHERE id = ?
        ");

        $result = $stmt->execute([$notes, $requestId]);

        if ($result) {
            // Notify child
            $this->notificationService->create(
                $request['household_id'],
                $request['requested_by'],
                'approval',
                'Money Request Approved',
                "Your request for " . number_format($request['amount'], 2) . " CZK has been approved",
                'normal',
                ['icon' => '✅']
            );
        }

        return $result;
    }

    /**
     * Reject money request
     */
    public function rejectMoneyRequest(int $requestId, int $parentUserId, ?string $notes = null): bool
    {
        $request = $this->getMoneyRequest($requestId);

        if (!$request || $request['status'] !== 'pending') {
            throw new \Exception("Invalid or already processed request");
        }

        $stmt = $this->db->prepare("
            UPDATE money_requests
            SET status = 'rejected', reviewed_at = CURRENT_TIMESTAMP, review_notes = ?
            WHERE id = ?
        ");

        $result = $stmt->execute([$notes, $requestId]);

        if ($result) {
            // Notify child
            $this->notificationService->create(
                $request['household_id'],
                $request['requested_by'],
                'approval',
                'Money Request Denied',
                "Your request for " . number_format($request['amount'], 2) . " CZK was denied. " . ($notes ?? ''),
                'normal',
                ['icon' => '❌']
            );
        }

        return $result;
    }

    /**
     * Get money request
     */
    private function getMoneyRequest(int $requestId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM money_requests WHERE id = ?");
        $stmt->execute([$requestId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get child's money requests
     */
    public function getChildRequests(int $childUserId, ?string $status = null): array
    {
        $where = "requested_by = ?";
        $params = [$childUserId];

        if ($status) {
            $where .= " AND status = ?";
            $params[] = $status;
        }

        $stmt = $this->db->prepare("SELECT * FROM money_requests WHERE {$where} ORDER BY created_at DESC");
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get parent's pending requests
     */
    public function getParentRequests(int $parentUserId, string $status = 'pending'): array
    {
        $stmt = $this->db->prepare("
            SELECT mr.*, u.username as child_username
            FROM money_requests mr
            JOIN users u ON mr.requested_by = u.id
            WHERE mr.requested_from = ? AND mr.status = ?
            ORDER BY mr.created_at DESC
        ");

        $stmt->execute([$parentUserId, $status]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calculate next payment date
     */
    private function calculateNextPaymentDate(string $frequency, ?int $dayOf = null): string
    {
        $now = new \DateTime();

        switch ($frequency) {
            case 'daily':
                return $now->modify('+1 day')->format('Y-m-d');

            case 'weekly':
                $targetDay = $dayOf ?? 1; // Default Monday
                $now->modify("next " . ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][$targetDay]);
                return $now->format('Y-m-d');

            case 'biweekly':
                return $now->modify('+14 days')->format('Y-m-d');

            case 'monthly':
                $targetDay = $dayOf ?? 1;
                $now->modify('first day of next month')->modify('+' . ($targetDay - 1) . ' days');
                return $now->format('Y-m-d');

            default:
                return $now->modify('+7 days')->format('Y-m-d');
        }
    }

    /**
     * Create allowance payment record
     */
    private function createAllowancePayment(array $allowance, string $status, ?string $skipReason = null): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO allowance_payments
            (allowance_id, household_id, child_user_id, parent_user_id, amount, status, skip_reason, scheduled_date, paid_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, DATE('now'), CURRENT_TIMESTAMP)
        ");

        $stmt->execute([
            $allowance['id'],
            $allowance['household_id'],
            $allowance['child_user_id'],
            $allowance['parent_user_id'],
            $allowance['amount'],
            $status,
            $skipReason
        ]);
    }

    /**
     * Get completed chores count for period
     */
    private function getCompletedChoresCount(int $userId, int $householdId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM chore_completions
            WHERE completed_by = ? AND household_id = ? AND status = 'approved'
                  AND completion_date >= DATE('now', 'start of month')
        ");

        $stmt->execute([$userId, $householdId]);

        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }
}
