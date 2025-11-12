<?php

namespace App\Services;

use PDO;

/**
 * Activity Service
 *
 * Tracks all household activities and provides activity feed
 */
class ActivityService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Log activity
     */
    public function log(
        int $householdId,
        int $userId,
        string $activityType,
        string $entityType,
        int $entityId,
        string $action,
        string $description,
        ?array $metadata = null,
        string $visibility = 'all',
        bool $isImportant = false
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO household_activities
            (household_id, user_id, activity_type, entity_type, entity_id, action, description, metadata_json, visibility, is_important)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $householdId,
            $userId,
            $activityType,
            $entityType,
            $entityId,
            $action,
            $description,
            $metadata ? json_encode($metadata) : null,
            $visibility,
            $isImportant ? 1 : 0
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Get household activity feed
     */
    public function getActivityFeed(int $householdId, int $limit = 50, int $offset = 0, ?array $filters = null): array
    {
        $where = ["household_id = ?"];
        $params = [$householdId];

        // Apply filters
        if (!empty($filters['activity_type'])) {
            $where[] = "activity_type = ?";
            $params[] = $filters['activity_type'];
        }

        if (!empty($filters['entity_type'])) {
            $where[] = "entity_type = ?";
            $params[] = $filters['entity_type'];
        }

        if (!empty($filters['user_id'])) {
            $where[] = "user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (isset($filters['is_important'])) {
            $where[] = "is_important = ?";
            $params[] = $filters['is_important'] ? 1 : 0;
        }

        $whereClause = implode(' AND ', $where);

        $stmt = $this->db->prepare("
            SELECT ha.*, u.username, u.avatar
            FROM household_activities ha
            JOIN users u ON ha.user_id = u.id
            WHERE {$whereClause}
            ORDER BY ha.created_at DESC
            LIMIT ? OFFSET ?
        ");

        $params[] = $limit;
        $params[] = $offset;

        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get activity count
     */
    public function getActivityCount(int $householdId, ?array $filters = null): int
    {
        $where = ["household_id = ?"];
        $params = [$householdId];

        if (!empty($filters['activity_type'])) {
            $where[] = "activity_type = ?";
            $params[] = $filters['activity_type'];
        }

        if (!empty($filters['entity_type'])) {
            $where[] = "entity_type = ?";
            $params[] = $filters['entity_type'];
        }

        $whereClause = implode(' AND ', $where);

        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM household_activities WHERE {$whereClause}");
        $stmt->execute($params);

        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    /**
     * Get activities for specific entity
     */
    public function getEntityActivities(string $entityType, int $entityId, int $limit = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT ha.*, u.username, u.avatar
            FROM household_activities ha
            JOIN users u ON ha.user_id = u.id
            WHERE ha.entity_type = ? AND ha.entity_id = ?
            ORDER BY ha.created_at DESC
            LIMIT ?
        ");

        $stmt->execute([$entityType, $entityId, $limit]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
