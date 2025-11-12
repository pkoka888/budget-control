<?php

namespace App\Controllers;

use App\Services\NotificationService;
use PDO;

/**
 * Notification Controller
 *
 * Manages user notifications
 */
class NotificationController extends BaseController
{
    private NotificationService $notificationService;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->notificationService = new NotificationService($db);
    }

    /**
     * Get user notifications
     */
    public function index(): void
    {
        $this->requireAuth();

        $unreadOnly = isset($_GET['unread']) && $_GET['unread'] === '1';
        $notifications = $this->notificationService->getUserNotifications($this->userId, 50, $unreadOnly);
        $unreadCount = $this->notificationService->getUnreadCount($this->userId);

        $this->jsonSuccess([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $id): void
    {
        $this->requireAuth();

        $result = $this->notificationService->markAsRead($id, $this->userId);

        if ($result) {
            $this->jsonSuccess(['message' => 'Notification marked as read']);
        } else {
            $this->jsonError('Failed to mark notification as read');
        }
    }

    /**
     * Mark all as read
     */
    public function markAllAsRead(): void
    {
        $this->requireAuth();

        $householdId = isset($_POST['household_id']) ? (int)$_POST['household_id'] : null;
        $result = $this->notificationService->markAllAsRead($this->userId, $householdId);

        if ($result) {
            $this->jsonSuccess(['message' => 'All notifications marked as read']);
        } else {
            $this->jsonError('Failed to mark notifications as read');
        }
    }

    /**
     * Archive notification
     */
    public function archive(int $id): void
    {
        $this->requireAuth();

        $result = $this->notificationService->archive($id, $this->userId);

        if ($result) {
            $this->jsonSuccess(['message' => 'Notification archived']);
        } else {
            $this->jsonError('Failed to archive notification');
        }
    }
}
