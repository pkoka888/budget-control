<?php
namespace BudgetApp\Controllers;

use BudgetApp\Services\InvestmentService;

class InvestmentController extends BaseController {
    private InvestmentService $investmentService;

    public function __construct($app) {
        parent::__construct($app);
        $this->investmentService = new InvestmentService($this->db);
    }

    /**
     * Get portfolio dashboard
     */
    public function portfolio(array $params = []): void {
        $userId = $this->getUserId();
        $portfolio = $this->investmentService->getPortfolioSummary($userId);

        echo $this->app->render('investments/portfolio', $portfolio);
    }

    /**
     * List investments (legacy method for backward compatibility)
     */
    public function list(array $params = []): void {
        $userId = $this->getUserId();

        $investments = $this->db->query(
            "SELECT i.*, ia.name as account_name
             FROM investments i
             LEFT JOIN investment_accounts ia ON i.account_id = ia.id
             WHERE i.user_id = ? AND i.is_active = 1
             ORDER BY i.asset_type, i.symbol",
            [$userId]
        );

        // Calculate current value and gains
        foreach ($investments as &$investment) {
            $investment['current_value'] = $investment['quantity'] * $investment['current_price'];
            $investment['purchase_value'] = $investment['quantity'] * $investment['purchase_price'];
            $investment['gain'] = $investment['current_value'] - $investment['purchase_value'];
            $investment['gain_percentage'] = $investment['purchase_value'] > 0
                ? ($investment['gain'] / $investment['purchase_value']) * 100
                : 0;
        }

        $totalInvested = array_sum(array_column($investments, 'purchase_value'));
        $totalCurrent = array_sum(array_column($investments, 'current_value'));

        echo $this->app->render('investments/list', [
            'investments' => $investments,
            'totalInvested' => $totalInvested,
            'totalCurrent' => $totalCurrent
        ]);
    }

    /**
     * Create new investment
     */
    public function create(array $params = []): void {
        $userId = $this->getUserId();

        // Validate input
        $errors = $this->validate($_POST, [
            'symbol' => 'required|max:10',
            'name' => 'required|max:100',
            'asset_type' => 'required',
            'quantity' => 'required|numeric',
            'purchase_price' => 'required|numeric',
            'account_id' => 'required|numeric'
        ]);

        if (!empty($errors)) {
            $this->json(['errors' => $errors], 400);
            return;
        }

        // Verify account belongs to user
        $account = $this->db->queryOne(
            "SELECT id FROM investment_accounts WHERE id = ? AND user_id = ?",
            [$_POST['account_id'], $userId]
        );

        if (!$account) {
            $this->json(['error' => 'Invalid investment account'], 403);
            return;
        }

        try {
            $investmentId = $this->db->insert('investments', [
                'user_id' => $userId,
                'account_id' => $_POST['account_id'],
                'symbol' => strtoupper($_POST['symbol']),
                'name' => $_POST['name'],
                'asset_type' => $_POST['asset_type'],
                'quantity' => (float)$_POST['quantity'],
                'purchase_price' => (float)$_POST['purchase_price'],
                'current_price' => (float)($_POST['current_price'] ?? $_POST['purchase_price']),
                'currency' => $_POST['currency'] ?? 'CZK',
                'exchange' => $_POST['exchange'] ?? 'NASDAQ',
                'sector' => $_POST['sector'] ?? null,
                'notes' => $_POST['notes'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $this->json([
                'success' => true,
                'id' => $investmentId,
                'message' => 'Investment created successfully'
            ]);
        } catch (\Exception $e) {
            $this->json(['error' => 'Failed to create investment: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update investment
     */
    public function update(array $params = []): void {
        $investmentId = $params['id'] ?? 0;
        $userId = $this->getUserId();

        $investment = $this->db->queryOne(
            "SELECT * FROM investments WHERE id = ? AND user_id = ?",
            [$investmentId, $userId]
        );

        if (!$investment) {
            $this->json(['error' => 'Investment not found'], 404);
            return;
        }

        $updates = [];
        $allowedFields = ['name', 'current_price', 'quantity', 'sector', 'notes', 'is_active'];

        foreach ($allowedFields as $field) {
            if (isset($_POST[$field])) {
                if (in_array($field, ['current_price', 'quantity'])) {
                    $updates[$field] = (float)$_POST[$field];
                } else {
                    $updates[$field] = $_POST[$field];
                }
            }
        }

        if (!empty($updates)) {
            $updates['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update('investments', $updates, ['id' => $investmentId]);
        }

        $this->json(['success' => true, 'message' => 'Investment updated successfully']);
    }

    /**
     * Delete investment
     */
    public function delete(array $params = []): void {
        $investmentId = $params['id'] ?? 0;
        $userId = $this->getUserId();

        $investment = $this->db->queryOne(
            "SELECT * FROM investments WHERE id = ? AND user_id = ?",
            [$investmentId, $userId]
        );

        if (!$investment) {
            $this->json(['error' => 'Investment not found'], 404);
            return;
        }

        // Soft delete by setting is_active to false
        $this->db->update('investments', [
            'is_active' => 0,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $investmentId]);

        $this->json(['success' => true, 'message' => 'Investment removed successfully']);
    }

    /**
     * Record investment transaction
     */
    public function recordTransaction(array $params = []): void {
        $userId = $this->getUserId();

        try {
            $transactionId = $this->investmentService->recordTransaction($userId, $_POST);
            $this->json([
                'success' => true,
                'id' => $transactionId,
                'message' => 'Transaction recorded successfully'
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            $this->json(['error' => 'Failed to record transaction: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get investment transactions
     */
    public function getTransactions(array $params = []): void {
        $userId = $this->getUserId();

        $filters = [];
        if (isset($_GET['investment_id'])) $filters['investment_id'] = (int)$_GET['investment_id'];
        if (isset($_GET['type'])) $filters['transaction_type'] = $_GET['type'];
        if (isset($_GET['start_date'])) $filters['start_date'] = $_GET['start_date'];
        if (isset($_GET['end_date'])) $filters['end_date'] = $_GET['end_date'];

        $page = (int)($this->getQueryParam('page', 1));
        $limit = (int)($this->getQueryParam('limit', 20));

        $result = $this->investmentService->getTransactions($userId, $filters, $page, $limit);

        $this->json($result);
    }

    /**
     * Get portfolio performance
     */
    public function getPerformance(array $params = []): void {
        $userId = $this->getUserId();
        $period = $this->getQueryParam('period', '1Y');

        $performance = $this->investmentService->getPerformance($userId, $period);
        $this->json($performance);
    }

    /**
     * Update investment prices
     */
    public function updatePrices(array $params = []): void {
        $userId = $this->getUserId();

        if (!isset($_POST['prices']) || !is_array($_POST['prices'])) {
            $this->json(['error' => 'Prices data required'], 400);
            return;
        }

        $result = $this->investmentService->updatePrices($userId, $_POST['prices']);
        $this->json($result);
    }

    /**
     * Get diversification analysis
     */
    public function getDiversification(array $params = []): void {
        $userId = $this->getUserId();
        $analysis = $this->investmentService->getDiversificationAnalysis($userId);
        $this->json($analysis);
    }

    /**
     * Get investment accounts
     */
    public function getAccounts(array $params = []): void {
        $userId = $this->getUserId();

        $accounts = $this->db->query(
            "SELECT * FROM investment_accounts WHERE user_id = ? AND is_active = 1 ORDER BY name",
            [$userId]
        );

        $this->json(['accounts' => $accounts]);
    }

    /**
     * Create investment account
     */
    public function createAccount(array $params = []): void {
        $userId = $this->getUserId();

        $errors = $this->validate($_POST, [
            'name' => 'required|max:100',
            'account_type' => 'required'
        ]);

        if (!empty($errors)) {
            $this->json(['errors' => $errors], 400);
            return;
        }

        try {
            $accountId = $this->db->insert('investment_accounts', [
                'user_id' => $userId,
                'name' => $_POST['name'],
                'account_type' => $_POST['account_type'],
                'broker' => $_POST['broker'] ?? null,
                'account_number' => $_POST['account_number'] ?? null,
                'currency' => $_POST['currency'] ?? 'CZK',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $this->json([
                'success' => true,
                'id' => $accountId,
                'message' => 'Investment account created successfully'
            ]);
        } catch (\Exception $e) {
            $this->json(['error' => 'Failed to create account: ' . $e->getMessage()], 500);
        }
    }
    /**
     * Get transaction summary with performance metrics
     */
    public function getTransactionSummary(array $params = []): void {
        $userId = $this->getUserId();

        $filters = [];
        if (isset($_GET['start_date'])) $filters['start_date'] = $_GET['start_date'];
        if (isset($_GET['end_date'])) $filters['end_date'] = $_GET['end_date'];
        if (isset($_GET['investment_id'])) $filters['investment_id'] = (int)$_GET['investment_id'];
        if (isset($_GET['account_id'])) $filters['account_id'] = (int)$_GET['account_id'];

        $summary = $this->investmentService->getTransactionSummary($userId, $filters);
        $this->json($summary);
    }

    /**
     * Export transactions to CSV
     */
    public function exportTransactions(array $params = []): void {
        $userId = $this->getUserId();

        $filters = [];
        if (isset($_GET['investment_id'])) $filters['investment_id'] = (int)$_GET['investment_id'];
        if (isset($_GET['type'])) $filters['transaction_type'] = $_GET['type'];
        if (isset($_GET['start_date'])) $filters['start_date'] = $_GET['start_date'];
        if (isset($_GET['end_date'])) $filters['end_date'] = $_GET['end_date'];
        if (isset($_GET['account_id'])) $filters['account_id'] = (int)$_GET['account_id'];
        if (isset($_GET['asset_type'])) $filters['asset_type'] = $_GET['asset_type'];
        if (isset($_GET['search'])) $filters['search'] = $_GET['search'];

        $csv = $this->investmentService->exportTransactions($userId, $filters);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="investment_transactions_' . date('Y-m-d') . '.csv"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $csv;
    }

    /**
     * Get current asset allocation
     */
    public function getCurrentAssetAllocation(array $params = []): void {
        $userId = $this->getUserId();
        $allocation = $this->investmentService->getCurrentAssetAllocation($userId);
        $this->json($allocation);
    }

    /**
     * Get ideal allocation by risk profile
     */
    public function getIdealAllocationByRisk(array $params = []): void {
        $userId = $this->getUserId();
        $riskProfile = $params['riskProfile'] ?? '';
        $allocation = $this->investmentService->getIdealAllocationByRisk($userId, $riskProfile);
        $this->json($allocation);
    }

    /**
     * Get rebalancing advice
     */
    public function getRebalancingAdvice(array $params = []): void {
        $userId = $this->getUserId();
        $riskProfile = $params['riskProfile'] ?? '';
        $advice = $this->investmentService->getRebalancingAdvice($userId, $riskProfile);
        $this->json($advice);
    }

    /**
     * Compare allocations
     */
    public function compareAllocations(array $params = []): void {
        $userId = $this->getUserId();
        $riskProfile = $params['riskProfile'] ?? '';
        $comparison = $this->investmentService->compareAllocations($userId, $riskProfile);
        $this->json($comparison);
    }
}
