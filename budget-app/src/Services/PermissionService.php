<?php

namespace App\Services;

use PDO;

/**
 * Permission Service
 *
 * Core RBAC (Role-Based Access Control) service for household permissions
 * Implements security-first approach with permission level checks
 *
 * Permission Levels:
 * - Owner (100): Full household control
 * - Partner (75): Can manage shared finances
 * - Viewer (50): Read-only access
 * - Child (25): Limited access with restrictions
 */
class PermissionService
{
    private PDO $db;

    // Permission levels
    const LEVEL_CHILD = 25;
    const LEVEL_VIEWER = 50;
    const LEVEL_PARTNER = 75;
    const LEVEL_OWNER = 100;

    // Permission constants
    const PERM_VIEW = 'view';
    const PERM_CREATE = 'create';
    const PERM_UPDATE = 'update';
    const PERM_DELETE = 'delete';
    const PERM_APPROVE = 'approve';
    const PERM_MANAGE_MEMBERS = 'manage_members';
    const PERM_MANAGE_SETTINGS = 'manage_settings';
    const PERM_EXPORT_DATA = 'export_data';
    const PERM_VIEW_REPORTS = 'view_reports';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Check if user has permission in household
     */
    public function hasPermission(int $userId, int $householdId, string $permission, ?string $resourceType = null): bool
    {
        $member = $this->getHouseholdMember($userId, $householdId);

        if (!$member || !$member['is_active']) {
            return false;
        }

        // Check base permission level
        switch ($permission) {
            case self::PERM_VIEW:
                return $member['permission_level'] >= self::LEVEL_CHILD;

            case self::PERM_CREATE:
            case self::PERM_UPDATE:
                return $member['permission_level'] >= self::LEVEL_PARTNER;

            case self::PERM_DELETE:
                return $member['permission_level'] >= self::LEVEL_PARTNER;

            case self::PERM_APPROVE:
                return $member['permission_level'] >= self::LEVEL_PARTNER;

            case self::PERM_MANAGE_MEMBERS:
                return $member['permission_level'] >= self::LEVEL_OWNER;

            case self::PERM_MANAGE_SETTINGS:
                return $member['permission_level'] >= self::LEVEL_OWNER;

            case self::PERM_EXPORT_DATA:
                return $member['permission_level'] >= self::LEVEL_PARTNER;

            case self::PERM_VIEW_REPORTS:
                return $member['permission_level'] >= self::LEVEL_VIEWER;

            default:
                return false;
        }
    }

    /**
     * Check if user can access specific entity
     */
    public function canAccessEntity(int $userId, string $entityType, int $entityId, string $action = self::PERM_VIEW): bool
    {
        // Get entity details
        $entity = $this->getEntity($entityType, $entityId);

        if (!$entity) {
            return false;
        }

        // Owner can always access their own data
        if ($entity['user_id'] == $userId) {
            return true;
        }

        // Private data is only accessible by owner
        if ($entity['visibility'] === 'private') {
            return false;
        }

        // Shared data - check household membership and permission
        if (!empty($entity['household_id'])) {
            return $this->hasPermission($userId, $entity['household_id'], $action, $entityType);
        }

        return false;
    }

    /**
     * Get household member record
     */
    public function getHouseholdMember(int $userId, int $householdId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM household_members
            WHERE user_id = ? AND household_id = ?
        ");
        $stmt->execute([$userId, $householdId]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        return $member ?: null;
    }

    /**
     * Get all households for user
     */
    public function getUserHouseholds(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT h.*, hm.role, hm.permission_level, hm.joined_at
            FROM households h
            JOIN household_members hm ON h.id = hm.household_id
            WHERE hm.user_id = ? AND hm.is_active = 1 AND h.is_active = 1
            ORDER BY h.created_at DESC
        ");
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user's primary household
     */
    public function getPrimaryHousehold(int $userId): ?array
    {
        $households = $this->getUserHouseholds($userId);

        // Return household where user is owner, or first household
        foreach ($households as $household) {
            if ($household['role'] === 'owner') {
                return $household;
            }
        }

        return !empty($households) ? $households[0] : null;
    }

    /**
     * Check if user is owner of household
     */
    public function isOwner(int $userId, int $householdId): bool
    {
        $member = $this->getHouseholdMember($userId, $householdId);
        return $member && $member['role'] === 'owner';
    }

    /**
     * Check if user is at least partner level
     */
    public function isPartnerOrAbove(int $userId, int $householdId): bool
    {
        $member = $this->getHouseholdMember($userId, $householdId);
        return $member && $member['permission_level'] >= self::LEVEL_PARTNER;
    }

    /**
     * Check if user is child account
     */
    public function isChild(int $userId, int $householdId): bool
    {
        $member = $this->getHouseholdMember($userId, $householdId);
        return $member && $member['role'] === 'child';
    }

    /**
     * Get permission level for role
     */
    public function getPermissionLevelForRole(string $role): int
    {
        return match($role) {
            'owner' => self::LEVEL_OWNER,
            'partner' => self::LEVEL_PARTNER,
            'viewer' => self::LEVEL_VIEWER,
            'child' => self::LEVEL_CHILD,
            default => 0
        };
    }

    /**
     * Filter query results based on permissions
     */
    public function filterAccessibleData(int $userId, string $entityType, array $items): array
    {
        return array_filter($items, function($item) use ($userId, $entityType) {
            return $this->canAccessEntity($userId, $entityType, $item['id']);
        });
    }

    /**
     * Build SQL WHERE clause for permission-filtered queries
     */
    public function buildPermissionWhereClause(int $userId, string $tableAlias = ''): string
    {
        $prefix = $tableAlias ? $tableAlias . '.' : '';

        return "({$prefix}user_id = {$userId} OR
                ({$prefix}visibility = 'shared' AND {$prefix}household_id IN (
                    SELECT household_id FROM household_members
                    WHERE user_id = {$userId} AND is_active = 1
                )))";
    }

    /**
     * Require specific permission or throw exception
     */
    public function requirePermission(int $userId, int $householdId, string $permission): void
    {
        if (!$this->hasPermission($userId, $householdId, $permission)) {
            throw new \Exception("Permission denied: {$permission} required");
        }
    }

    /**
     * Require entity access or throw exception
     */
    public function requireEntityAccess(int $userId, string $entityType, int $entityId, string $action = self::PERM_VIEW): void
    {
        if (!$this->canAccessEntity($userId, $entityType, $entityId, $action)) {
            throw new \Exception("Access denied to {$entityType} #{$entityId}");
        }
    }

    /**
     * Check if child account needs approval for transaction
     */
    public function childRequiresApproval(int $userId, int $householdId, float $amount, ?int $categoryId = null): bool
    {
        if (!$this->isChild($userId, $householdId)) {
            return false;
        }

        $stmt = $this->db->prepare("
            SELECT requires_approval_above, requires_approval_categories
            FROM child_account_settings
            WHERE user_id = ? AND household_id = ?
        ");
        $stmt->execute([$userId, $householdId]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$settings) {
            return true; // Default to requiring approval
        }

        // Check amount threshold
        if ($amount >= $settings['requires_approval_above']) {
            return true;
        }

        // Check category restrictions
        if ($categoryId && $settings['requires_approval_categories']) {
            $categories = json_decode($settings['requires_approval_categories'], true);
            if (in_array($categoryId, $categories)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check child account spending limits
     */
    public function checkChildSpendingLimit(int $userId, int $householdId, float $amount): array
    {
        $stmt = $this->db->prepare("
            SELECT daily_limit, weekly_limit, monthly_limit, per_transaction_limit, current_balance
            FROM child_account_settings
            WHERE user_id = ? AND household_id = ?
        ");
        $stmt->execute([$userId, $householdId]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$settings) {
            return ['allowed' => false, 'reason' => 'No child account settings found'];
        }

        // Check per-transaction limit
        if ($amount > $settings['per_transaction_limit']) {
            return ['allowed' => false, 'reason' => 'Exceeds per-transaction limit'];
        }

        // Check balance
        if ($amount > $settings['current_balance']) {
            return ['allowed' => false, 'reason' => 'Insufficient balance'];
        }

        // Check daily limit
        $dailySpent = $this->getChildSpending($userId, 'day');
        if ($dailySpent + $amount > $settings['daily_limit']) {
            return ['allowed' => false, 'reason' => 'Exceeds daily limit'];
        }

        // Check weekly limit
        $weeklySpent = $this->getChildSpending($userId, 'week');
        if ($weeklySpent + $amount > $settings['weekly_limit']) {
            return ['allowed' => false, 'reason' => 'Exceeds weekly limit'];
        }

        // Check monthly limit
        $monthlySpent = $this->getChildSpending($userId, 'month');
        if ($monthlySpent + $amount > $settings['monthly_limit']) {
            return ['allowed' => false, 'reason' => 'Exceeds monthly limit'];
        }

        return ['allowed' => true, 'remaining_balance' => $settings['current_balance'] - $amount];
    }

    /**
     * Get child spending for period
     */
    private function getChildSpending(int $userId, string $period): float
    {
        $dateCondition = match($period) {
            'day' => "DATE(date) = DATE('now')",
            'week' => "DATE(date) >= DATE('now', '-7 days')",
            'month' => "DATE(date) >= DATE('now', 'start of month')",
            default => "1=0"
        };

        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(ABS(amount)), 0) as total
            FROM transactions
            WHERE user_id = ? AND type = 'expense' AND {$dateCondition}
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['total'] ?? 0.0;
    }

    /**
     * Get entity from database
     */
    private function getEntity(string $entityType, int $entityId): ?array
    {
        $table = $this->getTableForEntityType($entityType);

        if (!$table) {
            return null;
        }

        $stmt = $this->db->prepare("
            SELECT id, user_id, household_id,
                   COALESCE(visibility, 'private') as visibility
            FROM {$table}
            WHERE id = ?
        ");
        $stmt->execute([$entityId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Map entity type to database table
     */
    private function getTableForEntityType(string $entityType): ?string
    {
        return match($entityType) {
            'transaction' => 'transactions',
            'account' => 'accounts',
            'budget' => 'budgets',
            'goal' => 'goals',
            'category' => 'categories',
            'bill' => 'recurring_bills',
            'investment' => 'investment_accounts',
            'receipt' => 'receipt_scans',
            'subscription' => 'subscriptions',
            default => null
        };
    }
}
