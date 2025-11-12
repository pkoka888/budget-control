<?php
namespace BudgetApp\Controllers;

use BudgetApp\Database;
use BudgetApp\Services\ExpenseSplitService;
use BudgetApp\Auth;

/**
 * Expense Split Controller
 *
 * Handles group expense splitting and settlement operations
 */
class ExpenseSplitController {
    private Database $db;
    private ExpenseSplitService $splitService;
    private Auth $auth;

    public function __construct(Database $db, ExpenseSplitService $splitService, Auth $auth) {
        $this->db = $db;
        $this->splitService = $splitService;
        $this->auth = $auth;
    }

    /**
     * List all groups for user
     */
    public function index(): void {
        $user = $this->auth->requireAuth();

        $groups = $this->splitService->getUserGroups($user['id']);

        // Get balance summary for each group
        foreach ($groups as &$group) {
            $balance = $this->splitService->getUserGroupBalance($group['id'], $user['id']);
            $group['user_balance'] = $balance;
        }

        $this->render('expense-split/index', [
            'title' => 'Split Expenses',
            'groups' => $groups
        ]);
    }

    /**
     * Show group details
     */
    public function show(): void {
        $user = $this->auth->requireAuth();
        $groupId = (int)($_GET['id'] ?? 0);

        if (!$groupId) {
            http_response_code(400);
            echo "Group ID required";
            return;
        }

        try {
            // Verify user is member
            if (!$this->splitService->isGroupMember($groupId, $user['id'])) {
                http_response_code(403);
                echo "You are not a member of this group";
                return;
            }

            $group = $this->splitService->getGroupDetails($groupId);
            $members = $this->splitService->getGroupMembers($groupId);
            $expenses = $this->splitService->getGroupExpenses($groupId);
            $balances = $this->splitService->calculateGroupBalance($groupId);
            $settlements = $this->splitService->getGroupSettlements($groupId);
            $activity = $this->splitService->getGroupActivity($groupId, 20);

            $this->render('expense-split/show', [
                'title' => $group['name'],
                'group' => $group,
                'members' => $members,
                'expenses' => $expenses,
                'balances' => $balances,
                'settlements' => $settlements,
                'activity' => $activity,
                'current_user_id' => $user['id']
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo "Error: " . $e->getMessage();
        }
    }

    /**
     * Create new group form
     */
    public function create(): void {
        $user = $this->auth->requireAuth();

        $this->render('expense-split/create', [
            'title' => 'Create Expense Group'
        ]);
    }

    /**
     * Store new group
     */
    public function store(): void {
        $user = $this->auth->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $name = $data['name'] ?? '';
        $description = $data['description'] ?? '';
        $memberEmails = $data['member_emails'] ?? [];
        $imageUrl = $data['image_url'] ?? null;

        if (!$name) {
            http_response_code(400);
            echo json_encode(['error' => 'Group name is required']);
            return;
        }

        try {
            $group = $this->splitService->createGroup(
                $user['id'],
                $name,
                $description,
                $memberEmails,
                $imageUrl
            );

            echo json_encode([
                'success' => true,
                'message' => 'Group created successfully',
                'group' => $group
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Add expense to group
     */
    public function addExpense(): void {
        $user = $this->auth->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $groupId = (int)($data['group_id'] ?? 0);
        $totalAmount = (float)($data['total_amount'] ?? 0);
        $description = $data['description'] ?? '';
        $splitType = $data['split_type'] ?? 'equal';
        $splits = $data['splits'] ?? [];
        $date = $data['date'] ?? date('Y-m-d');
        $categoryId = $data['category_id'] ?? null;
        $notes = $data['notes'] ?? null;
        $currency = $data['currency'] ?? 'CZK';

        if (!$groupId || !$totalAmount) {
            http_response_code(400);
            echo json_encode(['error' => 'Group ID and total amount are required']);
            return;
        }

        try {
            // Verify user is member
            if (!$this->splitService->isGroupMember($groupId, $user['id'])) {
                http_response_code(403);
                echo json_encode(['error' => 'You are not a member of this group']);
                return;
            }

            $expenseId = $this->splitService->splitExpense(
                $groupId,
                $user['id'],  // paid by current user
                $totalAmount,
                $description,
                $splitType,
                $splits,
                $date,
                $categoryId,
                $notes,
                $currency
            );

            echo json_encode([
                'success' => true,
                'message' => 'Expense added and split successfully',
                'expense_id' => $expenseId
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Record settlement between users
     */
    public function settle(): void {
        $user = $this->auth->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $groupId = (int)($data['group_id'] ?? 0);
        $toUserId = (int)($data['to_user_id'] ?? 0);
        $amount = (float)($data['amount'] ?? 0);
        $paymentMethod = $data['payment_method'] ?? null;
        $referenceNumber = $data['reference_number'] ?? null;
        $notes = $data['notes'] ?? null;

        if (!$groupId || !$toUserId || !$amount) {
            http_response_code(400);
            echo json_encode(['error' => 'Group ID, recipient, and amount are required']);
            return;
        }

        try {
            // Verify user is member
            if (!$this->splitService->isGroupMember($groupId, $user['id'])) {
                http_response_code(403);
                echo json_encode(['error' => 'You are not a member of this group']);
                return;
            }

            $settlementId = $this->splitService->settleBalance(
                $groupId,
                $user['id'],  // from current user
                $toUserId,
                $amount,
                $paymentMethod,
                $referenceNumber,
                $notes
            );

            echo json_encode([
                'success' => true,
                'message' => 'Settlement recorded successfully',
                'settlement_id' => $settlementId
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Confirm settlement
     */
    public function confirmSettlement(): void {
        $user = $this->auth->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $settlementId = (int)($data['settlement_id'] ?? 0);

        if (!$settlementId) {
            http_response_code(400);
            echo json_encode(['error' => 'Settlement ID is required']);
            return;
        }

        try {
            $this->splitService->confirmSettlement($settlementId, $user['id']);

            echo json_encode([
                'success' => true,
                'message' => 'Settlement confirmed'
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get group balance summary
     */
    public function getBalance(): void {
        $user = $this->auth->requireAuth();
        $groupId = (int)($_GET['group_id'] ?? 0);

        if (!$groupId) {
            http_response_code(400);
            echo json_encode(['error' => 'Group ID is required']);
            return;
        }

        try {
            // Verify user is member
            if (!$this->splitService->isGroupMember($groupId, $user['id'])) {
                http_response_code(403);
                echo json_encode(['error' => 'You are not a member of this group']);
                return;
            }

            $balances = $this->splitService->calculateGroupBalance($groupId);

            echo json_encode([
                'group_id' => $groupId,
                'balances' => $balances
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Accept group invitation
     */
    public function acceptInvitation(): void {
        $token = $_GET['token'] ?? null;

        if (!$token) {
            http_response_code(400);
            echo "Invalid invitation token";
            return;
        }

        $user = $this->auth->requireAuth();

        try {
            $group = $this->splitService->acceptInvitation($token, $user['id']);

            $_SESSION['flash_success'] = "You've joined the group: {$group['name']}";
            header("Location: /expense-split/group?id={$group['id']}");
            exit;
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = "Error: " . $e->getMessage();
            header("Location: /expense-split");
            exit;
        }
    }

    /**
     * Invite member to group
     */
    public function inviteMember(): void {
        $user = $this->auth->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $groupId = (int)($data['group_id'] ?? 0);
        $email = $data['email'] ?? '';

        if (!$groupId || !$email) {
            http_response_code(400);
            echo json_encode(['error' => 'Group ID and email are required']);
            return;
        }

        try {
            // Verify user is admin or member
            if (!$this->splitService->isGroupMember($groupId, $user['id'])) {
                http_response_code(403);
                echo json_encode(['error' => 'You are not a member of this group']);
                return;
            }

            $token = $this->splitService->inviteMember($groupId, $email, $user['id']);

            echo json_encode([
                'success' => true,
                'message' => 'Invitation sent successfully',
                'token' => $token
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Leave group
     */
    public function leave(): void {
        $user = $this->auth->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $groupId = (int)($data['group_id'] ?? 0);

        if (!$groupId) {
            http_response_code(400);
            echo json_encode(['error' => 'Group ID is required']);
            return;
        }

        try {
            $this->splitService->leaveGroup($groupId, $user['id']);

            echo json_encode([
                'success' => true,
                'message' => 'You have left the group'
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Delete expense (admin only)
     */
    public function deleteExpense(): void {
        $user = $this->auth->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $expenseId = (int)($data['expense_id'] ?? 0);

        if (!$expenseId) {
            http_response_code(400);
            echo json_encode(['error' => 'Expense ID is required']);
            return;
        }

        try {
            // Verify user created the expense or is group admin
            $expense = $this->db->query(
                "SELECT * FROM split_expenses WHERE id = ?",
                [$expenseId]
            )[0] ?? null;

            if (!$expense) {
                http_response_code(404);
                echo json_encode(['error' => 'Expense not found']);
                return;
            }

            if ($expense['paid_by'] != $user['id']) {
                http_response_code(403);
                echo json_encode(['error' => 'Only the expense creator can delete it']);
                return;
            }

            $this->splitService->deleteExpense($expenseId);

            echo json_encode([
                'success' => true,
                'message' => 'Expense deleted successfully'
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    private function render(string $view, array $data = []): void {
        extract($data);
        $content = '';

        ob_start();
        require __DIR__ . "/../../views/{$view}.php";
        $content = ob_get_clean();

        require __DIR__ . '/../../views/layout.php';
    }
}
