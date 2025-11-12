<?php

namespace App\Services;

use PDO;

/**
 * Chore Service
 *
 * Manages household chores, completions, and rewards
 */
class ChoreService
{
    private PDO $db;
    private NotificationService $notificationService;
    private ActivityService $activityService;
    private ChildAccountService $childAccountService;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->notificationService = new NotificationService($db);
        $this->activityService = new ActivityService($db);
        $this->childAccountService = new ChildAccountService($db);
    }

    /**
     * Create chore
     */
    public function createChore(int $householdId, int $createdBy, array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO chores
            (household_id, title, description, category, assigned_to, created_by, reward_amount, reward_type, difficulty, estimated_minutes, frequency, recurring_days, is_template)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $householdId,
            $data['title'],
            $data['description'] ?? null,
            $data['category'] ?? null,
            $data['assigned_to'] ?? null,
            $createdBy,
            $data['reward_amount'] ?? 0.00,
            $data['reward_type'] ?? 'money',
            $data['difficulty'] ?? 'easy',
            $data['estimated_minutes'] ?? null,
            $data['frequency'] ?? 'once',
            isset($data['recurring_days']) ? json_encode($data['recurring_days']) : null,
            $data['is_template'] ?? 0
        ]);

        $choreId = (int)$this->db->lastInsertId();

        // Notify assigned child
        if (!empty($data['assigned_to'])) {
            $this->notificationService->create(
                $householdId,
                $data['assigned_to'],
                'activity',
                'New Chore Assigned',
                "You've been assigned: {$data['title']}",
                'normal',
                ['action_url' => "/chores/{$choreId}", 'icon' => '📋']
            );
        }

        // Log activity
        $this->activityService->log(
            $householdId,
            $createdBy,
            'chore_created',
            'chore',
            $choreId,
            'created',
            "Created chore: {$data['title']}"
        );

        return $choreId;
    }

    /**
     * Update chore
     */
    public function updateChore(int $choreId, int $userId, array $data): bool
    {
        $chore = $this->getChore($choreId);

        if (!$chore || $chore['created_by'] != $userId) {
            throw new \Exception("You can only update your own chores");
        }

        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            if ($key === 'recurring_days' && is_array($value)) {
                $value = json_encode($value);
            }
            $fields[] = "{$key} = ?";
            $values[] = $value;
        }

        $values[] = $choreId;

        $sql = "UPDATE chores SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Delete chore
     */
    public function deleteChore(int $choreId, int $userId): bool
    {
        $chore = $this->getChore($choreId);

        if (!$chore || $chore['created_by'] != $userId) {
            throw new \Exception("You can only delete your own chores");
        }

        $stmt = $this->db->prepare("UPDATE chores SET is_active = 0 WHERE id = ?");
        return $stmt->execute([$choreId]);
    }

    /**
     * Get chore
     */
    public function getChore(int $choreId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM chores WHERE id = ?");
        $stmt->execute([$choreId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get household chores
     */
    public function getHouseholdChores(int $householdId, ?array $filters = null): array
    {
        $where = ["household_id = ?", "is_active = 1"];
        $params = [$householdId];

        if (!empty($filters['assigned_to'])) {
            $where[] = "assigned_to = ?";
            $params[] = $filters['assigned_to'];
        }

        if (!empty($filters['category'])) {
            $where[] = "category = ?";
            $params[] = $filters['category'];
        }

        if (isset($filters['is_template'])) {
            $where[] = "is_template = ?";
            $params[] = $filters['is_template'] ? 1 : 0;
        }

        $whereClause = implode(' AND ', $where);

        $stmt = $this->db->prepare("
            SELECT c.*, u.username as assigned_to_username
            FROM chores c
            LEFT JOIN users u ON c.assigned_to = u.id
            WHERE {$whereClause}
            ORDER BY c.created_at DESC
        ");

        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Complete chore
     */
    public function completeChore(int $choreId, int $userId, array $data = []): int
    {
        $chore = $this->getChore($choreId);

        if (!$chore) {
            throw new \Exception("Chore not found");
        }

        // Check if assigned to user
        if ($chore['assigned_to'] && $chore['assigned_to'] != $userId) {
            throw new \Exception("This chore is not assigned to you");
        }

        $stmt = $this->db->prepare("
            INSERT INTO chore_completions
            (chore_id, household_id, completed_by, completion_date, photo_proof, notes, time_taken_minutes, reward_amount)
            VALUES (?, ?, ?, DATE('now'), ?, ?, ?, ?)
        ");

        $stmt->execute([
            $choreId,
            $chore['household_id'],
            $userId,
            $data['photo_proof'] ?? null,
            $data['notes'] ?? null,
            $data['time_taken_minutes'] ?? null,
            $chore['reward_amount']
        ]);

        $completionId = (int)$this->db->lastInsertId();

        // Notify parent for verification
        $this->notificationService->create(
            $chore['household_id'],
            $chore['created_by'],
            'approval',
            'Chore Completed',
            "Chore '{$chore['title']}' has been marked complete",
            'normal',
            ['action_url' => "/chores/completion/{$completionId}", 'action_label' => 'Verify']
        );

        // Log activity
        $this->activityService->log(
            $chore['household_id'],
            $userId,
            'chore_completed',
            'chore',
            $choreId,
            'completed',
            "Completed chore: {$chore['title']}"
        );

        return $completionId;
    }

    /**
     * Verify chore completion
     */
    public function verifyCompletion(int $completionId, int $verifiedBy, bool $approved, ?int $qualityRating = null, ?string $notes = null): bool
    {
        $completion = $this->getCompletion($completionId);

        if (!$completion || $completion['status'] !== 'pending') {
            throw new \Exception("Invalid or already verified completion");
        }

        $chore = $this->getChore($completion['chore_id']);

        // Check if verifier is the chore creator or household admin
        if ($chore['created_by'] != $verifiedBy) {
            $permissionService = new PermissionService($this->db);
            if (!$permissionService->isPartnerOrAbove($verifiedBy, $chore['household_id'])) {
                throw new \Exception("You don't have permission to verify this chore");
            }
        }

        $status = $approved ? 'approved' : 'rejected';

        $stmt = $this->db->prepare("
            UPDATE chore_completions
            SET status = ?, verified_by = ?, verified_at = CURRENT_TIMESTAMP, quality_rating = ?, verification_notes = ?
            WHERE id = ?
        ");

        $result = $stmt->execute([$status, $verifiedBy, $qualityRating, $notes, $completionId]);

        if ($result && $approved && $completion['reward_amount'] > 0) {
            // Pay reward
            $this->payReward($completion);
        }

        // Notify child
        $message = $approved
            ? "Your chore '{$chore['title']}' has been approved!"
            : "Your chore '{$chore['title']}' was not approved. " . ($notes ?? '');

        $this->notificationService->create(
            $chore['household_id'],
            $completion['completed_by'],
            'activity',
            $approved ? 'Chore Approved' : 'Chore Rejected',
            $message,
            'normal',
            ['icon' => $approved ? '✅' : '❌']
        );

        return $result;
    }

    /**
     * Get completion
     */
    public function getCompletion(int $completionId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM chore_completions WHERE id = ?");
        $stmt->execute([$completionId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get chore completions
     */
    public function getCompletions(int $choreId): array
    {
        $stmt = $this->db->prepare("
            SELECT cc.*, u.username as completed_by_username, v.username as verified_by_username
            FROM chore_completions cc
            JOIN users u ON cc.completed_by = u.id
            LEFT JOIN users v ON cc.verified_by = v.id
            WHERE cc.chore_id = ?
            ORDER BY cc.completion_date DESC
        ");

        $stmt->execute([$choreId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get child's completions
     */
    public function getChildCompletions(int $userId, ?string $status = null): array
    {
        $where = "completed_by = ?";
        $params = [$userId];

        if ($status) {
            $where .= " AND status = ?";
            $params[] = $status;
        }

        $stmt = $this->db->prepare("
            SELECT cc.*, c.title as chore_title, c.reward_amount
            FROM chore_completions cc
            JOIN chores c ON cc.chore_id = c.id
            WHERE {$where}
            ORDER BY cc.completion_date DESC
        ");

        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get pending verifications for household
     */
    public function getPendingVerifications(int $householdId): array
    {
        $stmt = $this->db->prepare("
            SELECT cc.*, c.title as chore_title, u.username as completed_by_username
            FROM chore_completions cc
            JOIN chores c ON cc.chore_id = c.id
            JOIN users u ON cc.completed_by = u.id
            WHERE cc.household_id = ? AND cc.status = 'pending'
            ORDER BY cc.completion_time DESC
        ");

        $stmt->execute([$householdId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Pay reward for completed chore
     */
    private function payReward(array $completion): bool
    {
        if ($completion['reward_amount'] <= 0 || $completion['reward_paid']) {
            return false;
        }

        // Add to child's balance
        $this->childAccountService->addBalance(
            $completion['completed_by'],
            $completion['household_id'],
            $completion['reward_amount'],
            'chore_reward'
        );

        // Mark as paid
        $stmt = $this->db->prepare("
            UPDATE chore_completions
            SET reward_paid = 1, reward_paid_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        return $stmt->execute([$completion['id']]);
    }

    /**
     * Get chore statistics for child
     */
    public function getChildStatistics(int $userId, int $householdId): array
    {
        $stats = [];

        // Total completions
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM chore_completions
            WHERE completed_by = ? AND household_id = ?
        ");
        $stmt->execute([$userId, $householdId]);
        $stats['total_completions'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Approved completions
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM chore_completions
            WHERE completed_by = ? AND household_id = ? AND status = 'approved'
        ");
        $stmt->execute([$userId, $householdId]);
        $stats['approved_completions'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Total rewards earned
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(reward_amount), 0) as total
            FROM chore_completions
            WHERE completed_by = ? AND household_id = ? AND status = 'approved' AND reward_paid = 1
        ");
        $stmt->execute([$userId, $householdId]);
        $stats['total_rewards_earned'] = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Average quality rating
        $stmt = $this->db->prepare("
            SELECT AVG(quality_rating) as avg_rating
            FROM chore_completions
            WHERE completed_by = ? AND household_id = ? AND quality_rating IS NOT NULL
        ");
        $stmt->execute([$userId, $householdId]);
        $stats['avg_quality_rating'] = round((float)$stmt->fetch(PDO::FETCH_ASSOC)['avg_rating'], 2);

        return $stats;
    }
}
