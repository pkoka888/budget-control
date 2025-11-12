<?php

namespace App\Controllers;

use App\Services\HouseholdService;
use App\Services\PermissionService;
use App\Services\InvitationService;
use PDO;

/**
 * Household Controller
 *
 * Manages household/family workspace operations
 */
class HouseholdController extends BaseController
{
    private HouseholdService $householdService;
    private PermissionService $permissionService;
    private InvitationService $invitationService;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->householdService = new HouseholdService($db);
        $this->permissionService = new PermissionService($db);
        $this->invitationService = new InvitationService($db);
    }

    /**
     * Display household dashboard
     */
    public function index(): void
    {
        $this->requireAuth();

        $households = $this->permissionService->getUserHouseholds($this->userId);
        $primaryHousehold = $this->permissionService->getPrimaryHousehold($this->userId);

        $this->render('household/index', [
            'households' => $households,
            'primaryHousehold' => $primaryHousehold
        ]);
    }

    /**
     * Display specific household
     */
    public function show(int $id): void
    {
        $this->requireAuth();

        // Check access
        if (!$this->permissionService->hasPermission($this->userId, $id, PermissionService::PERM_VIEW)) {
            $this->error('Access denied', 403);
            return;
        }

        $household = $this->householdService->getHouseholdWithMembers($id, $this->userId);

        if (!$household) {
            $this->error('Household not found', 404);
            return;
        }

        $stats = $this->householdService->getStatistics($id);
        $invitations = $this->invitationService->getHouseholdInvitations($id, 'pending');

        $this->render('household/show', [
            'household' => $household,
            'stats' => $stats,
            'invitations' => $invitations
        ]);
    }

    /**
     * Create household form
     */
    public function create(): void
    {
        $this->requireAuth();
        $this->render('household/create');
    }

    /**
     * Store new household
     */
    public function store(): void
    {
        $this->requireAuth();

        $data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'currency' => $_POST['currency'] ?? 'CZK',
            'timezone' => $_POST['timezone'] ?? 'Europe/Prague'
        ];

        // Validate
        if (empty($data['name'])) {
            $this->jsonError('Household name is required');
            return;
        }

        try {
            $householdId = $this->householdService->createHousehold($this->userId, $data);

            $this->jsonSuccess([
                'message' => 'Household created successfully',
                'household_id' => $householdId
            ]);
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }

    /**
     * Update household
     */
    public function update(int $id): void
    {
        $this->requireAuth();

        $data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'currency' => $_POST['currency'] ?? 'CZK',
            'timezone' => $_POST['timezone'] ?? 'Europe/Prague'
        ];

        try {
            $result = $this->householdService->updateHousehold($id, $this->userId, $data);

            if ($result) {
                $this->jsonSuccess(['message' => 'Household updated successfully']);
            } else {
                $this->jsonError('Failed to update household');
            }
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }

    /**
     * Invite member
     */
    public function inviteMember(int $id): void
    {
        $this->requireAuth();

        try {
            $this->permissionService->requirePermission($this->userId, $id, PermissionService::PERM_MANAGE_MEMBERS);

            $invitation = $this->invitationService->createInvitation(
                $id,
                $this->userId,
                $_POST['email'] ?? '',
                $_POST['role'] ?? 'viewer',
                $_POST['message'] ?? null
            );

            $this->jsonSuccess([
                'message' => 'Invitation sent successfully',
                'invitation' => $invitation
            ]);
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }

    /**
     * Update member role
     */
    public function updateMemberRole(int $id, int $memberId): void
    {
        $this->requireAuth();

        try {
            $result = $this->householdService->updateMemberRole(
                $id,
                $memberId,
                $this->userId,
                $_POST['role'] ?? 'viewer'
            );

            if ($result) {
                $this->jsonSuccess(['message' => 'Member role updated successfully']);
            } else {
                $this->jsonError('Failed to update member role');
            }
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }

    /**
     * Remove member
     */
    public function removeMember(int $id, int $memberId): void
    {
        $this->requireAuth();

        try {
            $result = $this->householdService->removeMember($id, $memberId, $this->userId);

            if ($result) {
                $this->jsonSuccess(['message' => 'Member removed successfully']);
            } else {
                $this->jsonError('Failed to remove member');
            }
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }

    /**
     * Update household settings
     */
    public function updateSettings(int $id): void
    {
        $this->requireAuth();

        $settings = [
            'default_visibility' => $_POST['default_visibility'] ?? 'private',
            'allow_member_invites' => (int)($_POST['allow_member_invites'] ?? 0),
            'require_approval_threshold' => (float)($_POST['require_approval_threshold'] ?? 1000.00),
            'notify_new_transactions' => (int)($_POST['notify_new_transactions'] ?? 1),
            'notify_budget_alerts' => (int)($_POST['notify_budget_alerts'] ?? 1),
            'allow_child_accounts' => (int)($_POST['allow_child_accounts'] ?? 1),
            'child_requires_approval' => (int)($_POST['child_requires_approval'] ?? 1)
        ];

        try {
            $result = $this->householdService->updateSettings($id, $this->userId, $settings);

            if ($result) {
                $this->jsonSuccess(['message' => 'Settings updated successfully']);
            } else {
                $this->jsonError('Failed to update settings');
            }
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }

    /**
     * Delete household
     */
    public function delete(int $id): void
    {
        $this->requireAuth();

        try {
            $result = $this->householdService->deleteHousehold($id, $this->userId);

            if ($result) {
                $this->jsonSuccess(['message' => 'Household deleted successfully']);
            } else {
                $this->jsonError('Failed to delete household');
            }
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }
}
