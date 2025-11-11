<?php
namespace BudgetApp\Controllers;

use BudgetApp\Services\NotificationService;

class NotificationController extends BaseController {
    private NotificationService $notificationService;

    public function __construct($app) {
        parent::__construct($app);
        $this->notificationService = new NotificationService($this->db);
    }

    /**
     * Get user notifications
     */
    public function list(array $params = []): void {
        $userId = $this->getUserId();
        $limit = (int)($this->getQueryParam('limit', 50));
        $offset = (int)($this->getQueryParam('offset', 0));
        $unreadOnly = $this->getQueryParam('unread_only', 'false') === 'true';

        try {
            $notifications = $this->notificationService->getNotifications($userId, $limit, $offset);

            if ($unreadOnly) {
                $notifications = array_filter($notifications, fn($n) => !$n['is_read']);
            }

            $this->json([
                'success' => true,
                'notifications' => array_values($notifications),
                'total' => count($notifications)
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to get notifications: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markRead(array $params = []): void {
        $notificationId = $params['id'] ?? 0;
        $userId = $this->getUserId();

        if (!$notificationId) {
            $this->json(['error' => 'Notification ID is required'], 400);
            return;
        }

        try {
            $success = $this->notificationService->markAsRead($userId, $notificationId);

            if ($success) {
                $this->json(['success' => true, 'message' => 'Notification marked as read']);
            } else {
                $this->json(['error' => 'Notification not found'], 404);
            }

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to mark notification as read: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllRead(array $params = []): void {
        $userId = $this->getUserId();

        try {
            $success = $this->notificationService->markAllAsRead($userId);

            $this->json([
                'success' => $success,
                'message' => 'All notifications marked as read'
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to mark all notifications as read: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete notification
     */
    public function delete(array $params = []): void {
        $notificationId = $params['id'] ?? 0;
        $userId = $this->getUserId();

        if (!$notificationId) {
            $this->json(['error' => 'Notification ID is required'], 400);
            return;
        }

        try {
            $success = $this->notificationService->deleteNotification($userId, $notificationId);

            if ($success) {
                $this->json(['success' => true, 'message' => 'Notification deleted']);
            } else {
                $this->json(['error' => 'Notification not found'], 404);
            }

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to delete notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate and send weekly digest
     */
    public function sendWeeklyDigest(array $params = []): void {
        $userId = $this->getUserId();

        try {
            $success = $this->notificationService->sendWeeklyDigest($userId);

            $this->json([
                'success' => $success,
                'message' => 'Weekly digest sent successfully'
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to send weekly digest: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification preferences
     */
    public function getPreferences(array $params = []): void {
        $userId = $this->getUserId();

        try {
            $preferences = $this->notificationService->getNotificationPreferences($userId);

            $this->json([
                'success' => true,
                'preferences' => $preferences
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to get notification preferences: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences(array $params = []): void {
        $userId = $this->getUserId();

        $preferences = json_decode(file_get_contents('php://input'), true);

        if (!$preferences) {
            $this->json(['error' => 'Invalid preferences data'], 400);
            return;
        }

        try {
            $success = $this->notificationService->updateNotificationPreferences($userId, $preferences);

            $this->json([
                'success' => $success,
                'message' => 'Notification preferences updated successfully'
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to update notification preferences: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create contextual action notification
     */
    public function createContextualAction(array $params = []): void {
        $userId = $this->getUserId();

        $actionData = json_decode(file_get_contents('php://input'), true);

        if (!$actionData || !isset($actionData['context_type'], $actionData['context_data'])) {
            $this->json(['error' => 'Invalid action data'], 400);
            return;
        }

        try {
            $notificationId = $this->notificationService->createContextualAction(
                $userId,
                $actionData['context_type'],
                $actionData['context_data']
            );

            $this->json([
                'success' => true,
                'notification_id' => $notificationId,
                'message' => 'Contextual action notification created'
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to create contextual action: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification statistics
     */
    public function getStats(array $params = []): void {
        $userId = $this->getUserId();

        try {
            $stats = $this->db->queryOne(
                "SELECT
                    COUNT(*) as total_notifications,
                    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count,
                    COUNT(DISTINCT DATE(created_at)) as active_days
                 FROM notifications
                 WHERE user_id = ?",
                [$userId]
            );

            // Get notifications by type
            $typeStats = $this->db->query(
                "SELECT type, COUNT(*) as count
                 FROM notifications
                 WHERE user_id = ?
                 GROUP BY type",
                [$userId]
            );

            $stats['by_type'] = array_column($typeStats, 'count', 'type');

            $this->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to get notification stats: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk actions on notifications
     */
    public function bulkAction(array $params = []): void {
        $userId = $this->getUserId();

        $actionData = json_decode(file_get_contents('php://input'), true);

        if (!$actionData || !isset($actionData['action'], $actionData['notification_ids'])) {
            $this->json(['error' => 'Invalid bulk action data'], 400);
            return;
        }

        $action = $actionData['action'];
        $notificationIds = $actionData['notification_ids'];

        try {
            $successCount = 0;

            foreach ($notificationIds as $notificationId) {
                switch ($action) {
                    case 'mark_read':
                        if ($this->notificationService->markAsRead($userId, $notificationId)) {
                            $successCount++;
                        }
                        break;

                    case 'delete':
                        if ($this->notificationService->deleteNotification($userId, $notificationId)) {
                            $successCount++;
                        }
                        break;
                }
            }

            $this->json([
                'success' => true,
                'message' => "Successfully processed {$successCount} notifications",
                'processed_count' => $successCount
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to process bulk action: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test notification creation (for development)
     */
    public function testNotification(array $params = []): void {
        $userId = $this->getUserId();

        $testData = json_decode(file_get_contents('php://input'), true) ?: [
            'type' => 'test',
            'title' => 'Test Notification',
            'message' => 'This is a test notification to verify the system is working.'
        ];

        try {
            $notificationId = $this->notificationService->createNotification(
                $userId,
                $testData['type'],
                $testData['title'],
                $testData['message'],
                $testData['metadata'] ?? []
            );

            $this->json([
                'success' => true,
                'notification_id' => $notificationId,
                'message' => 'Test notification created successfully'
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to create test notification: ' . $e->getMessage()
            ], 500);
        }
    }
}