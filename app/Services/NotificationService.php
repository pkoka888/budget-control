<?php

namespace App\Services;

use PDO;

/**
 * Notification Service
 *
 * Manages real-time notifications for household members
 */
class NotificationService
{
    private PDO $db;
    private ?EmailService $emailService = null;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function setEmailService(EmailService $emailService): void
    {
        $this->emailService = $emailService;
    }

    /**
     * Create notification
     */
    public function create(
        int $householdId,
        int $userId,
        string $type,
        string $title,
        string $message,
        string $priority = 'normal',
        ?array $options = null
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO notifications
            (household_id, user_id, notification_type, title, message, priority, action_url, action_label, icon, related_entity_type, related_entity_id, metadata_json, expires_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $householdId,
            $userId,
            $type,
            $title,
            $message,
            $priority,
            $options['action_url'] ?? null,
            $options['action_label'] ?? null,
            $options['icon'] ?? null,
            $options['related_entity_type'] ?? null,
            $options['related_entity_id'] ?? null,
            isset($options['metadata']) ? json_encode($options['metadata']) : null,
            $options['expires_at'] ?? null
        ]);

        $notificationId = (int)$this->db->lastInsertId();

        // Send email if enabled
        if ($this->shouldSendEmail($userId, $type, $priority)) {
            $this->sendEmailNotification($userId, $title, $message, $options['action_url'] ?? null);
        }

        return $notificationId;
    }

    /**
     * Notify multiple users
     */
    public function notifyMembers(
        int $householdId,
        array $userIds,
        string $type,
        string $title,
        string $message,
        string $priority = 'normal',
        ?array $options = null
    ): array {
        $notificationIds = [];

        foreach ($userIds as $userId) {
            $notificationIds[] = $this->create($householdId, $userId, $type, $title, $message, $priority, $options);
        }

        return $notificationIds;
    }

    /**
     * Get user notifications
     */
    public function getUserNotifications(int $userId, int $limit = 50, bool $unreadOnly = false): array
    {
        $where = "user_id = ? AND is_archived = 0";
        $params = [$userId];

        if ($unreadOnly) {
            $where .= " AND is_read = 0";
        }

        $stmt = $this->db->prepare("
            SELECT *
            FROM notifications
            WHERE {$where}
            ORDER BY priority DESC, created_at DESC
            LIMIT ?
        ");

        $params[] = $limit;
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get unread count
     */
    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM notifications
            WHERE user_id = ? AND is_read = 0 AND is_archived = 0
        ");
        $stmt->execute([$userId]);

        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    /**
     * Mark as read
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE notifications
            SET is_read = 1, read_at = CURRENT_TIMESTAMP
            WHERE id = ? AND user_id = ?
        ");

        return $stmt->execute([$notificationId, $userId]);
    }

    /**
     * Mark all as read
     */
    public function markAllAsRead(int $userId, ?int $householdId = null): bool
    {
        $where = "user_id = ?";
        $params = [$userId];

        if ($householdId) {
            $where .= " AND household_id = ?";
            $params[] = $householdId;
        }

        $stmt = $this->db->prepare("
            UPDATE notifications
            SET is_read = 1, read_at = CURRENT_TIMESTAMP
            WHERE {$where} AND is_read = 0
        ");

        return $stmt->execute($params);
    }

    /**
     * Archive notification
     */
    public function archive(int $notificationId, int $userId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE notifications
            SET is_archived = 1
            WHERE id = ? AND user_id = ?
        ");

        return $stmt->execute([$notificationId, $userId]);
    }

    /**
     * Delete expired notifications
     */
    public function deleteExpired(): int
    {
        $stmt = $this->db->prepare("
            DELETE FROM notifications
            WHERE expires_at IS NOT NULL AND expires_at < CURRENT_TIMESTAMP
        ");

        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Check if should send email
     */
    private function shouldSendEmail(int $userId, string $type, string $priority): bool
    {
        if (!$this->emailService) {
            return false;
        }

        $stmt = $this->db->prepare("
            SELECT * FROM notification_preferences
            WHERE user_id = ? AND household_id IS NULL
        ");
        $stmt->execute([$userId]);
        $prefs = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$prefs) {
            return $priority === 'urgent'; // Default: email only urgent
        }

        // Check if email enabled for this type
        $emailMap = [
            'approval' => 'email_approvals',
            'alert' => 'email_alerts',
            'invitation' => 'email_invitations'
        ];

        $prefKey = $emailMap[$type] ?? null;

        return $prefKey && !empty($prefs[$prefKey]);
    }

    /**
     * Send email notification
     */
    private function sendEmailNotification(int $userId, string $title, string $message, ?string $actionUrl): void
    {
        if (!$this->emailService) {
            return;
        }

        // Get user email
        $stmt = $this->db->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return;
        }

        $body = $message;
        if ($actionUrl) {
            $body .= "\n\nView details: " . $actionUrl;
        }

        try {
            $this->emailService->send($user['email'], $title, $body);
        } catch (\Exception $e) {
            // Log error but don't fail
            error_log("Failed to send notification email: " . $e->getMessage());
        }
    }
}
