<?php

namespace App\Controllers;

use App\Services\ChildAccountService;
use App\Services\ChoreService;
use App\Services\PermissionService;
use PDO;

/**
 * Child Account Controller
 *
 * Manages child accounts, allowances, chores, and money requests
 */
class ChildAccountController extends BaseController
{
    private ChildAccountService $childAccountService;
    private ChoreService $choreService;
    private PermissionService $permissionService;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->childAccountService = new ChildAccountService($db);
        $this->choreService = new ChoreService($db);
        $this->permissionService = new PermissionService($db);
    }

    /**
     * Display child account dashboard
     */
    public function index(int $householdId): void
    {
        $this->requireAuth();

        $settings = $this->childAccountService->getChildSettings($this->userId, $householdId);

        if (!$settings) {
            $this->error('Child account not found', 404);
            return;
        }

        // Get chores
        $chores = $this->choreService->getHouseholdChores($householdId, ['assigned_to' => $this->userId]);
        $completions = $this->choreService->getChildCompletions($this->userId, 'approved');
        $stats = $this->choreService->getChildStatistics($this->userId, $householdId);

        $this->render('child-account/index', [
            'settings' => $settings,
            'household_id' => $householdId,
            'chores' => $chores,
            'completions' => $completions,
            'stats' => $stats
        ]);
    }

    /**
     * Create money request
     */
    public function createMoneyRequest(int $householdId): void
    {
        $this->requireAuth();

        try {
            // Get parent (supervisor)
            $settings = $this->childAccountService->getChildSettings($this->userId, $householdId);

            if (!$settings) {
                $this->jsonError('Child account not found');
                return;
            }

            $requestId = $this->childAccountService->createMoneyRequest(
                $householdId,
                $this->userId,
                $settings['supervised_by'],
                (float)($_POST['amount'] ?? 0),
                $_POST['reason'] ?? '',
                $_POST['category'] ?? null
            );

            $this->jsonSuccess([
                'message' => 'Money request sent successfully',
                'request_id' => $requestId
            ]);
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }

    /**
     * Get child's money requests
     */
    public function getMoneyRequests(): void
    {
        $this->requireAuth();

        $status = $_GET['status'] ?? null;
        $requests = $this->childAccountService->getChildRequests($this->userId, $status);

        $this->jsonSuccess(['requests' => $requests]);
    }

    /**
     * Get parent's pending requests
     */
    public function getParentRequests(): void
    {
        $this->requireAuth();

        $status = $_GET['status'] ?? 'pending';
        $requests = $this->childAccountService->getParentRequests($this->userId, $status);

        $this->jsonSuccess(['requests' => $requests]);
    }

    /**
     * Approve money request (parent action)
     */
    public function approveMoneyRequest(int $requestId): void
    {
        $this->requireAuth();

        try {
            $result = $this->childAccountService->approveMoneyRequest(
                $requestId,
                $this->userId,
                $_POST['notes'] ?? null
            );

            if ($result) {
                $this->jsonSuccess(['message' => 'Money request approved']);
            } else {
                $this->jsonError('Failed to approve money request');
            }
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }

    /**
     * Reject money request (parent action)
     */
    public function rejectMoneyRequest(int $requestId): void
    {
        $this->requireAuth();

        try {
            $result = $this->childAccountService->rejectMoneyRequest(
                $requestId,
                $this->userId,
                $_POST['notes'] ?? null
            );

            if ($result) {
                $this->jsonSuccess(['message' => 'Money request rejected']);
            } else {
                $this->jsonError('Failed to reject money request');
            }
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }

    /**
     * Complete chore
     */
    public function completeChore(int $choreId): void
    {
        $this->requireAuth();

        try {
            $completionId = $this->choreService->completeChore($choreId, $this->userId, [
                'notes' => $_POST['notes'] ?? null,
                'time_taken_minutes' => isset($_POST['time_taken_minutes']) ? (int)$_POST['time_taken_minutes'] : null,
                'photo_proof' => $_FILES['photo_proof']['tmp_name'] ?? null
            ]);

            $this->jsonSuccess([
                'message' => 'Chore marked as complete',
                'completion_id' => $completionId
            ]);
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }

    /**
     * Verify chore completion (parent action)
     */
    public function verifyChore(int $completionId): void
    {
        $this->requireAuth();

        try {
            $approved = isset($_POST['approved']) && $_POST['approved'] === '1';
            $result = $this->choreService->verifyCompletion(
                $completionId,
                $this->userId,
                $approved,
                isset($_POST['quality_rating']) ? (int)$_POST['quality_rating'] : null,
                $_POST['notes'] ?? null
            );

            if ($result) {
                $this->jsonSuccess(['message' => $approved ? 'Chore approved' : 'Chore rejected']);
            } else {
                $this->jsonError('Failed to verify chore');
            }
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }

    /**
     * Get pending chore verifications (parent view)
     */
    public function getPendingVerifications(int $householdId): void
    {
        $this->requireAuth();

        // Check permission
        if (!$this->permissionService->isPartnerOrAbove($this->userId, $householdId)) {
            $this->jsonError('Access denied', 403);
            return;
        }

        $pending = $this->choreService->getPendingVerifications($householdId);

        $this->jsonSuccess(['pending_verifications' => $pending]);
    }

    /**
     * Create chore (parent action)
     */
    public function createChore(int $householdId): void
    {
        $this->requireAuth();

        // Check permission
        if (!$this->permissionService->isPartnerOrAbove($this->userId, $householdId)) {
            $this->jsonError('Access denied', 403);
            return;
        }

        try {
            $data = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'category' => $_POST['category'] ?? null,
                'assigned_to' => isset($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null,
                'reward_amount' => isset($_POST['reward_amount']) ? (float)$_POST['reward_amount'] : 0.00,
                'reward_type' => $_POST['reward_type'] ?? 'money',
                'difficulty' => $_POST['difficulty'] ?? 'easy',
                'estimated_minutes' => isset($_POST['estimated_minutes']) ? (int)$_POST['estimated_minutes'] : null,
                'frequency' => $_POST['frequency'] ?? 'once'
            ];

            $choreId = $this->choreService->createChore($householdId, $this->userId, $data);

            $this->jsonSuccess([
                'message' => 'Chore created successfully',
                'chore_id' => $choreId
            ]);
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }
}
