<?php

namespace App\Controllers;

use App\Services\ApprovalService;
use App\Services\PermissionService;
use PDO;

/**
 * Approval Controller
 *
 * Manages approval workflows for transactions and requests
 */
class ApprovalController extends BaseController
{
    private ApprovalService $approvalService;
    private PermissionService $permissionService;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->approvalService = new ApprovalService($db);
        $this->permissionService = new PermissionService($db);
    }

    /**
     * Display pending approvals for household
     */
    public function index(int $householdId): void
    {
        $this->requireAuth();

        // Check permission
        if (!$this->permissionService->hasPermission($this->userId, $householdId, PermissionService::PERM_APPROVE)) {
            $this->error('Access denied', 403);
            return;
        }

        $pendingRequests = $this->approvalService->getPendingRequests($householdId);

        $this->render('approval/index', [
            'household_id' => $householdId,
            'pending_requests' => $pendingRequests
        ]);
    }

    /**
     * Show specific approval request
     */
    public function show(int $id): void
    {
        $this->requireAuth();

        $request = $this->approvalService->getRequest($id);

        if (!$request) {
            $this->error('Approval request not found', 404);
            return;
        }

        // Check access
        if (!$this->permissionService->hasPermission($this->userId, $request['household_id'], PermissionService::PERM_VIEW)) {
            $this->error('Access denied', 403);
            return;
        }

        $this->render('approval/show', ['request' => $request]);
    }

    /**
     * Approve request
     */
    public function approve(int $id): void
    {
        $this->requireAuth();

        try {
            $result = $this->approvalService->approve(
                $id,
                $this->userId,
                $_POST['notes'] ?? null
            );

            if ($result) {
                $this->jsonSuccess(['message' => 'Request approved successfully']);
            } else {
                $this->jsonError('Failed to approve request');
            }
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }

    /**
     * Reject request
     */
    public function reject(int $id): void
    {
        $this->requireAuth();

        try {
            $result = $this->approvalService->reject(
                $id,
                $this->userId,
                $_POST['notes'] ?? null
            );

            if ($result) {
                $this->jsonSuccess(['message' => 'Request rejected']);
            } else {
                $this->jsonError('Failed to reject request');
            }
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }

    /**
     * Cancel request
     */
    public function cancel(int $id): void
    {
        $this->requireAuth();

        try {
            $result = $this->approvalService->cancel($id, $this->userId);

            if ($result) {
                $this->jsonSuccess(['message' => 'Request cancelled']);
            } else {
                $this->jsonError('Failed to cancel request');
            }
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }

    /**
     * Get user's requests
     */
    public function userRequests(): void
    {
        $this->requireAuth();

        $status = $_GET['status'] ?? null;
        $requests = $this->approvalService->getUserRequests($this->userId, $status);

        $this->jsonSuccess(['requests' => $requests]);
    }
}
