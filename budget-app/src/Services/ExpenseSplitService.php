<?php
namespace BudgetApp\Services;

use BudgetApp\Database;
use BudgetApp\Config;

/**
 * Expense Split Service
 *
 * Handles expense splitting with friends, group management, and settlements
 */
class ExpenseSplitService {
    private Database $db;
    private Config $config;
    private EmailService $emailService;

    public function __construct(Database $db, Config $config, EmailService $emailService) {
        $this->db = $db;
        $this->config = $config;
        $this->emailService = $emailService;
    }

    /**
     * Create a new expense group
     */
    public function createGroup(int $userId, string $name, string $description = '', array $memberEmails = []): array {
        // Create group
        $this->db->query(
            "INSERT INTO expense_groups (name, description, created_by)
             VALUES (?, ?, ?)",
            [$name, $description, $userId]
        );

        $groupId = $this->db->lastInsertId();

        // Add creator as admin
        $this->db->query(
            "INSERT INTO expense_group_members (group_id, user_id, role)
             VALUES (?, ?, 'admin')",
            [$groupId, $userId]
        );

        // Send invitations
        foreach ($memberEmails as $email) {
            $this->inviteMember($groupId, $email, $userId);
        }

        // Log activity
        $this->logActivity($groupId, $userId, 'group_created', "Group '{$name}' created");

        return $this->getGroup($groupId);
    }

    /**
     * Invite member to group
     */
    public function inviteMember(int $groupId, string $email, int $invitedBy): string {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

        $this->db->query(
            "INSERT INTO expense_group_invitations
             (group_id, email, invited_by, token, expires_at)
             VALUES (?, ?, ?, ?, ?)",
            [$groupId, $email, $invitedBy, $token, $expiresAt]
        );

        // Send invitation email (implement this)
        // $this->emailService->sendGroupInvitation($email, $groupId, $token);

        return $token;
    }

    /**
     * Accept group invitation
     */
    public function acceptInvitation(string $token, int $userId): bool {
        $invitation = $this->db->query(
            "SELECT * FROM expense_group_invitations
             WHERE token = ? AND status = 'pending' AND expires_at > CURRENT_TIMESTAMP",
            [$token]
        );

        if (empty($invitation)) {
            return false;
        }

        $inv = $invitation[0];

        // Add user to group
        $this->db->query(
            "INSERT INTO expense_group_members (group_id, user_id, invited_by)
             VALUES (?, ?, ?)",
            [$inv['group_id'], $userId, $inv['invited_by']]
        );

        // Mark invitation as accepted
        $this->db->query(
            "UPDATE expense_group_invitations
             SET status = 'accepted', accepted_at = CURRENT_TIMESTAMP
             WHERE id = ?",
            [$inv['id']]
        );

        return true;
    }

    /**
     * Split an expense among group members
     */
    public function splitExpense(int $groupId, int $paidBy, float $totalAmount, string $description, string $splitType = 'equal', array $splits = [], array $metadata = []): int {
        $currency = $metadata['currency'] ?? 'CZK';
        $date = $metadata['date'] ?? date('Y-m-d');
        $categoryId = $metadata['category_id'] ?? null;

        // Create split expense
        $this->db->query(
            "INSERT INTO split_expenses
             (group_id, paid_by, total_amount, currency, split_type, description, date, category_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$groupId, $paidBy, $totalAmount, $currency, $splitType, $description, $date, $categoryId]
        );

        $splitExpenseId = $this->db->lastInsertId();

        // Get group members
        $members = $this->getGroupMembers($groupId);

        // Calculate splits based on type
        switch ($splitType) {
            case 'equal':
                $splits = $this->calculateEqualSplits($members, $totalAmount);
                break;
            case 'percentage':
                $splits = $this->calculatePercentageSplits($splits, $totalAmount);
                break;
            case 'shares':
                $splits = $this->calculateShareSplits($splits, $totalAmount);
                break;
            case 'custom':
                // Use provided splits
                break;
        }

        // Save individual splits
        foreach ($splits as $userId => $splitData) {
            $this->db->query(
                "INSERT INTO expense_splits
                 (split_expense_id, user_id, amount, percentage, shares)
                 VALUES (?, ?, ?, ?, ?)",
                [
                    $splitExpenseId,
                    $userId,
                    $splitData['amount'],
                    $splitData['percentage'] ?? null,
                    $splitData['shares'] ?? null
                ]
            );
        }

        // Log activity
        $this->logActivity($groupId, $paidBy, 'expense_added', "Added expense: {$description}");

        return $splitExpenseId;
    }

    /**
     * Calculate group balances
     */
    public function calculateGroupBalance(int $groupId): array {
        $expenses = $this->db->query(
            "SELECT se.paid_by, es.user_id, es.amount, es.is_settled
             FROM split_expenses se
             JOIN expense_splits es ON se.id = es.split_expense_id
             WHERE se.group_id = ?",
            [$groupId]
        );

        $balances = [];

        foreach ($expenses as $expense) {
            $payer = $expense['paid_by'];
            $debtor = $expense['user_id'];
            $amount = $expense['amount'];
            $settled = $expense['is_settled'];

            if (!$settled) {
                // Payer is owed money
                $balances[$payer] = ($balances[$payer] ?? 0) + $amount;

                // Debtor owes money (skip if same person)
                if ($debtor != $payer) {
                    $balances[$debtor] = ($balances[$debtor] ?? 0) - $amount;
                }
            }
        }

        // Calculate who owes whom
        return $this->simplifyDebts($balances);
    }

    /**
     * Settle balance between two users
     */
    public function settleBalance(int $groupId, int $fromUser, int $toUser, float $amount, array $metadata = []): int {
        $currency = $metadata['currency'] ?? 'CZK';
        $paymentMethod = $metadata['payment_method'] ?? null;
        $notes = $metadata['notes'] ?? null;

        // Create settlement
        $this->db->query(
            "INSERT INTO settlements
             (group_id, from_user, to_user, amount, currency, status, payment_method, notes, settled_at)
             VALUES (?, ?, ?, ?, ?, 'completed', ?, ?, CURRENT_TIMESTAMP)",
            [$groupId, $fromUser, $toUser, $amount, $currency, $paymentMethod, $notes]
        );

        $settlementId = $this->db->lastInsertId();

        // Mark relevant expense splits as settled
        $this->markExpensesAsSettled($groupId, $fromUser, $toUser, $amount);

        // Log activity
        $this->logActivity($groupId, $fromUser, 'settlement_made', "Settled {$amount} {$currency} with user {$toUser}");

        return $settlementId;
    }

    /**
     * Get group summary
     */
    public function getGroupSummary(int $groupId): array {
        $group = $this->getGroup($groupId);
        $members = $this->getGroupMembers($groupId);
        $balances = $this->calculateGroupBalance($groupId);

        // Calculate statistics
        $expenses = $this->db->query(
            "SELECT COUNT(*) as count, SUM(total_amount) as total
             FROM split_expenses
             WHERE group_id = ?",
            [$groupId]
        );

        $settlements = $this->db->query(
            "SELECT COUNT(*) as count, SUM(amount) as total
             FROM settlements
             WHERE group_id = ? AND status = 'completed'",
            [$groupId]
        );

        return [
            'group' => $group,
            'members' => $members,
            'balances' => $balances,
            'total_expenses' => $expenses[0]['count'] ?? 0,
            'total_amount' => $expenses[0]['total'] ?? 0,
            'total_settlements' => $settlements[0]['count'] ?? 0,
            'settled_amount' => $settlements[0]['total'] ?? 0
        ];
    }

    /**
     * Get user's groups
     */
    public function getUserGroups(int $userId): array {
        return $this->db->query(
            "SELECT eg.*, egm.role
             FROM expense_groups eg
             JOIN expense_group_members egm ON eg.id = egm.group_id
             WHERE egm.user_id = ? AND eg.is_active = 1
             ORDER BY eg.created_at DESC",
            [$userId]
        );
    }

    // Private helper methods

    private function getGroup(int $groupId): array {
        $result = $this->db->query(
            "SELECT * FROM expense_groups WHERE id = ?",
            [$groupId]
        );
        return $result[0] ?? [];
    }

    private function getGroupMembers(int $groupId): array {
        return $this->db->query(
            "SELECT egm.*, u.name, u.email
             FROM expense_group_members egm
             JOIN users u ON egm.user_id = u.id
             WHERE egm.group_id = ? AND egm.left_at IS NULL",
            [$groupId]
        );
    }

    private function calculateEqualSplits(array $members, float $totalAmount): array {
        $count = count($members);
        $amountPerPerson = $totalAmount / $count;

        $splits = [];
        foreach ($members as $member) {
            $splits[$member['user_id']] = [
                'amount' => round($amountPerPerson, 2),
                'percentage' => round(100 / $count, 2)
            ];
        }

        return $splits;
    }

    private function calculatePercentageSplits(array $splits, float $totalAmount): array {
        $result = [];
        foreach ($splits as $userId => $data) {
            $percentage = $data['percentage'];
            $result[$userId] = [
                'amount' => round($totalAmount * ($percentage / 100), 2),
                'percentage' => $percentage
            ];
        }
        return $result;
    }

    private function calculateShareSplits(array $splits, float $totalAmount): array {
        $totalShares = array_sum(array_column($splits, 'shares'));
        $result = [];

        foreach ($splits as $userId => $data) {
            $shares = $data['shares'];
            $result[$userId] = [
                'amount' => round($totalAmount * ($shares / $totalShares), 2),
                'shares' => $shares
            ];
        }

        return $result;
    }

    private function simplifyDebts(array $balances): array {
        // Separate creditors and debtors
        $creditors = [];
        $debtors = [];

        foreach ($balances as $userId => $balance) {
            if ($balance > 0.01) {
                $creditors[] = ['user_id' => $userId, 'amount' => $balance];
            } elseif ($balance < -0.01) {
                $debtors[] = ['user_id' => $userId, 'amount' => abs($balance)];
            }
        }

        // Sort
        usort($creditors, fn($a, $b) => $b['amount'] <=> $a['amount']);
        usort($debtors, fn($a, $b) => $b['amount'] <=> $a['amount']);

        // Calculate settlements
        $settlements = [];
        $i = 0;
        $j = 0;

        while ($i < count($creditors) && $j < count($debtors)) {
            $creditor = &$creditors[$i];
            $debtor = &$debtors[$j];

            $amount = min($creditor['amount'], $debtor['amount']);

            $settlements[] = [
                'from' => $debtor['user_id'],
                'to' => $creditor['user_id'],
                'amount' => round($amount, 2)
            ];

            $creditor['amount'] -= $amount;
            $debtor['amount'] -= $amount;

            if ($creditor['amount'] < 0.01) $i++;
            if ($debtor['amount'] < 0.01) $j++;
        }

        return $settlements;
    }

    private function markExpensesAsSettled(int $groupId, int $fromUser, int $toUser, float $amount): void {
        // Mark expense splits as settled (simplified)
        $this->db->query(
            "UPDATE expense_splits es
             JOIN split_expenses se ON es.split_expense_id = se.id
             SET es.is_settled = 1, es.settled_at = CURRENT_TIMESTAMP
             WHERE se.group_id = ? AND se.paid_by = ? AND es.user_id = ?",
            [$groupId, $toUser, $fromUser]
        );
    }

    private function logActivity(int $groupId, int $userId, string $activityType, string $description): void {
        $this->db->query(
            "INSERT INTO expense_group_activity (group_id, user_id, activity_type, description)
             VALUES (?, ?, ?, ?)",
            [$groupId, $userId, $activityType, $description]
        );
    }
}
