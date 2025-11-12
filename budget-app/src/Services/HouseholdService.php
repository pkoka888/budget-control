<?php

namespace App\Services;

use PDO;

/**
 * Household Service
 *
 * Manages household/family workspaces, members, and settings
 */
class HouseholdService
{
    private PDO $db;
    private PermissionService $permissionService;
    private ActivityService $activityService;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->permissionService = new PermissionService($db);
    }

    public function setActivityService(ActivityService $activityService): void
    {
        $this->activityService = $activityService;
    }

    /**
     * Create new household
     */
    public function createHousehold(int $userId, array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO households (name, description, created_by, currency, timezone, avatar_url)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $userId,
            $data['currency'] ?? 'CZK',
            $data['timezone'] ?? 'Europe/Prague',
            $data['avatar_url'] ?? null
        ]);

        $householdId = (int)$this->db->lastInsertId();

        // Add creator as owner
        $this->addMember($householdId, $userId, 'owner', PermissionService::LEVEL_OWNER);

        // Create default settings
        $this->createDefaultSettings($householdId);

        // Log activity
        if (isset($this->activityService)) {
            $this->activityService->log($householdId, $userId, 'household_created', 'household', $householdId, 'created', "Created household: {$data['name']}");
        }

        return $householdId;
    }

    /**
     * Update household
     */
    public function updateHousehold(int $householdId, int $userId, array $data): bool
    {
        $this->permissionService->requirePermission($userId, $householdId, PermissionService::PERM_MANAGE_SETTINGS);

        $stmt = $this->db->prepare("
            UPDATE households
            SET name = ?, description = ?, currency = ?, timezone = ?, avatar_url = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        $result = $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['currency'] ?? 'CZK',
            $data['timezone'] ?? 'Europe/Prague',
            $data['avatar_url'] ?? null,
            $householdId
        ]);

        if ($result && isset($this->activityService)) {
            $this->activityService->log($householdId, $userId, 'household_updated', 'household', $householdId, 'updated', "Updated household settings");
        }

        return $result;
    }

    /**
     * Delete household
     */
    public function deleteHousehold(int $householdId, int $userId): bool
    {
        if (!$this->permissionService->isOwner($userId, $householdId)) {
            throw new \Exception("Only household owner can delete household");
        }

        $stmt = $this->db->prepare("UPDATE households SET is_active = 0 WHERE id = ?");
        return $stmt->execute([$householdId]);
    }

    /**
     * Get household details
     */
    public function getHousehold(int $householdId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM households WHERE id = ? AND is_active = 1");
        $stmt->execute([$householdId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get household with members
     */
    public function getHouseholdWithMembers(int $householdId, int $userId): ?array
    {
        $household = $this->getHousehold($householdId);

        if (!$household) {
            return null;
        }

        $household['members'] = $this->getMembers($householdId);
        $household['settings'] = $this->getSettings($householdId);

        return $household;
    }

    /**
     * Get household members
     */
    public function getMembers(int $householdId): array
    {
        $stmt = $this->db->prepare("
            SELECT hm.*, u.username, u.email, u.avatar
            FROM household_members hm
            JOIN users u ON hm.user_id = u.id
            WHERE hm.household_id = ? AND hm.is_active = 1
            ORDER BY hm.permission_level DESC, hm.joined_at ASC
        ");
        $stmt->execute([$householdId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Add member to household
     */
    public function addMember(int $householdId, int $userId, string $role, ?int $permissionLevel = null): int
    {
        if ($permissionLevel === null) {
            $permissionLevel = $this->permissionService->getPermissionLevelForRole($role);
        }

        $stmt = $this->db->prepare("
            INSERT INTO household_members (household_id, user_id, role, permission_level)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([$householdId, $userId, $role, $permissionLevel]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Update member role
     */
    public function updateMemberRole(int $householdId, int $targetUserId, int $updatingUserId, string $newRole): bool
    {
        $this->permissionService->requirePermission($updatingUserId, $householdId, PermissionService::PERM_MANAGE_MEMBERS);

        // Cannot change owner role
        $targetMember = $this->permissionService->getHouseholdMember($targetUserId, $householdId);
        if ($targetMember['role'] === 'owner' && $newRole !== 'owner') {
            throw new \Exception("Cannot demote household owner. Transfer ownership first.");
        }

        $permissionLevel = $this->permissionService->getPermissionLevelForRole($newRole);

        $stmt = $this->db->prepare("
            UPDATE household_members
            SET role = ?, permission_level = ?
            WHERE household_id = ? AND user_id = ?
        ");

        $result = $stmt->execute([$newRole, $permissionLevel, $householdId, $targetUserId]);

        if ($result && isset($this->activityService)) {
            $this->activityService->log($householdId, $updatingUserId, 'member_role_changed', 'member', $targetUserId, 'updated', "Changed member role to {$newRole}");
        }

        return $result;
    }

    /**
     * Remove member from household
     */
    public function removeMember(int $householdId, int $targetUserId, int $removingUserId): bool
    {
        $this->permissionService->requirePermission($removingUserId, $householdId, PermissionService::PERM_MANAGE_MEMBERS);

        // Cannot remove owner
        $targetMember = $this->permissionService->getHouseholdMember($targetUserId, $householdId);
        if ($targetMember['role'] === 'owner') {
            throw new \Exception("Cannot remove household owner");
        }

        $stmt = $this->db->prepare("
            UPDATE household_members
            SET is_active = 0
            WHERE household_id = ? AND user_id = ?
        ");

        $result = $stmt->execute([$householdId, $targetUserId]);

        if ($result && isset($this->activityService)) {
            $this->activityService->log($householdId, $removingUserId, 'member_removed', 'member', $targetUserId, 'deleted', "Removed member from household");
        }

        return $result;
    }

    /**
     * Get household settings
     */
    public function getSettings(int $householdId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM household_settings WHERE household_id = ?");
        $stmt->execute([$householdId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Update household settings
     */
    public function updateSettings(int $householdId, int $userId, array $settings): bool
    {
        $this->permissionService->requirePermission($userId, $householdId, PermissionService::PERM_MANAGE_SETTINGS);

        $fields = [];
        $values = [];

        foreach ($settings as $key => $value) {
            $fields[] = "{$key} = ?";
            $values[] = $value;
        }

        $values[] = $householdId;

        $sql = "UPDATE household_settings SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP WHERE household_id = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Create default settings
     */
    private function createDefaultSettings(int $householdId): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO household_settings (household_id)
            VALUES (?)
        ");
        $stmt->execute([$householdId]);
    }

    /**
     * Get household statistics
     */
    public function getStatistics(int $householdId): array
    {
        $stats = [];

        // Member count
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM household_members WHERE household_id = ? AND is_active = 1");
        $stmt->execute([$householdId]);
        $stats['member_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Shared transactions count
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM transactions WHERE household_id = ? AND visibility = 'shared'");
        $stmt->execute([$householdId]);
        $stats['shared_transactions'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Shared accounts count
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM accounts WHERE household_id = ? AND visibility = 'shared'");
        $stmt->execute([$householdId]);
        $stats['shared_accounts'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Total shared balance
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(balance), 0) as total FROM accounts WHERE household_id = ? AND visibility = 'shared'");
        $stmt->execute([$householdId]);
        $stats['total_shared_balance'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        return $stats;
    }
}
