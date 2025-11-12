<?php

namespace App\Services;

use PDO;

/**
 * Approval Service
 *
 * Manages approval workflows for transactions and financial operations
 */
class ApprovalService
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
     * Create approval request
     */
    public function createRequest(
        int $householdId,
        int $requestedBy,
        string $requestType,
        string $entityType,
        int $entityId,
        float $amount,
        string $description,
        ?string $justification = null,
        ?array $metadata = null,
        int $expiryHours = 48
    ): int {
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryHours} hours"));

        $stmt = $this->db->prepare("
            INSERT INTO approval_requests
            (household_id, requested_by, request_type, entity_type, entity_id, amount, description, justification, expires_at, metadata_json)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $householdId,
            $requestedBy,
            $requestType,
            $entityType,
            $entityId,
            $amount,
            $description,
            $justification,
            $expiresAt,
            $metadata ? json_encode($metadata) : null
        ]);

        $requestId = (int)$this->db->lastInsertId();

        // Notify approvers (partners and owners)
        $this->notifyApprovers($householdId, $requestedBy, $requestId, $description, $amount);

        // Log activity
        $this->activityService->log(
            $householdId,
            $requestedBy,
            'approval_requested',
            $entityType,
            $entityId,
            'requested',
            "Requested approval for {$description}",
            $metadata,
            'all',
            true
        );

        return $requestId;
    }

    /**
     * Approve request
     */
    public function approve(int $requestId, int $reviewedBy, ?string $notes = null): bool
    {
        $request = $this->getRequest($requestId);

        if (!$request) {
            throw new \Exception("Approval request not found");
        }

        if ($request['status'] !== 'pending') {
            throw new \Exception("Request is not pending");
        }

        // Check permissions
        $permissionService = new PermissionService($this->db);
        if (!$permissionService->hasPermission($reviewedBy, $request['household_id'], PermissionService::PERM_APPROVE)) {
            throw new \Exception("You don't have permission to approve requests");
        }

        // Cannot approve own request
        if ($reviewedBy == $request['requested_by']) {
            throw new \Exception("Cannot approve your own request");
        }

        $stmt = $this->db->prepare("
            UPDATE approval_requests
            SET status = 'approved', reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP, review_notes = ?
            WHERE id = ?
        ");

        $result = $stmt->execute(['approved', $reviewedBy, $notes, $requestId]);

        if ($result) {
            // Process the approval (e.g., create transaction)
            $this->processApproval($request);

            // Notify requester
            $this->notificationService->create(
                $request['household_id'],
                $request['requested_by'],
                'approval',
                'Request Approved',
                "Your request for {$request['description']} has been approved",
                'normal',
                ['action_url' => "/approval/{$requestId}"]
            );

            // Log activity
            $this->activityService->log(
                $request['household_id'],
                $reviewedBy,
                'approval_granted',
                $request['entity_type'],
                $request['entity_id'],
                'approved',
                "Approved: {$request['description']}",
                null,
                'all',
                true
            );
        }

        return $result;
    }

    /**
     * Reject request
     */
    public function reject(int $requestId, int $reviewedBy, ?string $notes = null): bool
    {
        $request = $this->getRequest($requestId);

        if (!$request) {
            throw new \Exception("Approval request not found");
        }

        if ($request['status'] !== 'pending') {
            throw new \Exception("Request is not pending");
        }

        // Check permissions
        $permissionService = new PermissionService($this->db);
        if (!$permissionService->hasPermission($reviewedBy, $request['household_id'], PermissionService::PERM_APPROVE)) {
            throw new \Exception("You don't have permission to reject requests");
        }

        $stmt = $this->db->prepare("
            UPDATE approval_requests
            SET status = 'rejected', reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP, review_notes = ?
            WHERE id = ?
        ");

        $result = $stmt->execute(['rejected', $reviewedBy, $notes, $requestId]);

        if ($result) {
            // Notify requester
            $this->notificationService->create(
                $request['household_id'],
                $request['requested_by'],
                'approval',
                'Request Rejected',
                "Your request for {$request['description']} was rejected. Reason: " . ($notes ?? 'No reason provided'),
                'normal',
                ['action_url' => "/approval/{$requestId}"]
            );

            // Log activity
            $this->activityService->log(
                $request['household_id'],
                $reviewedBy,
                'approval_denied',
                $request['entity_type'],
                $request['entity_id'],
                'rejected',
                "Rejected: {$request['description']}",
                null,
                'all',
                true
            );
        }

        return $result;
    }

    /**
     * Cancel request
     */
    public function cancel(int $requestId, int $userId): bool
    {
        $request = $this->getRequest($requestId);

        if (!$request) {
            throw new \Exception("Approval request not found");
        }

        if ($request['requested_by'] != $userId) {
            throw new \Exception("You can only cancel your own requests");
        }

        if ($request['status'] !== 'pending') {
            throw new \Exception("Can only cancel pending requests");
        }

        $stmt = $this->db->prepare("
            UPDATE approval_requests
            SET status = 'cancelled'
            WHERE id = ?
        ");

        return $stmt->execute([$requestId]);
    }

    /**
     * Get request
     */
    public function getRequest(int $requestId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM approval_requests WHERE id = ?");
        $stmt->execute([$requestId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get household pending requests
     */
    public function getPendingRequests(int $householdId): array
    {
        $stmt = $this->db->prepare("
            SELECT ar.*, u.username as requester_username
            FROM approval_requests ar
            JOIN users u ON ar.requested_by = u.id
            WHERE ar.household_id = ? AND ar.status = 'pending'
            ORDER BY ar.created_at DESC
        ");

        $stmt->execute([$householdId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user requests
     */
    public function getUserRequests(int $userId, ?string $status = null): array
    {
        $where = "requested_by = ?";
        $params = [$userId];

        if ($status) {
            $where .= " AND status = ?";
            $params[] = $status;
        }

        $stmt = $this->db->prepare("
            SELECT * FROM approval_requests
            WHERE {$where}
            ORDER BY created_at DESC
        ");

        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Expire old requests
     */
    public function expireOldRequests(): int
    {
        $stmt = $this->db->prepare("
            UPDATE approval_requests
            SET status = 'expired'
            WHERE status = 'pending' AND expires_at < CURRENT_TIMESTAMP
        ");

        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Notify approvers
     */
    private function notifyApprovers(int $householdId, int $requestedBy, int $requestId, string $description, float $amount): void
    {
        // Get all partners and owners
        $stmt = $this->db->prepare("
            SELECT user_id FROM household_members
            WHERE household_id = ? AND permission_level >= ? AND user_id != ? AND is_active = 1
        ");

        $stmt->execute([$householdId, PermissionService::LEVEL_PARTNER, $requestedBy]);
        $approvers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($approvers as $approver) {
            $this->notificationService->create(
                $householdId,
                $approver['user_id'],
                'approval',
                'Approval Required',
                "New approval request: {$description} (" . number_format($amount, 2) . " CZK)",
                'high',
                [
                    'action_url' => "/approval/{$requestId}",
                    'action_label' => 'Review Request'
                ]
            );
        }
    }

    /**
     * Process approved request
     */
    private function processApproval(array $request): void
    {
        // Update entity's approval status
        if ($request['entity_type'] === 'transaction') {
            $stmt = $this->db->prepare("
                UPDATE transactions
                SET requires_approval = 0, approved_by = ?, approved_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$request['reviewed_by'], $request['entity_id']]);
        }
    }
}
