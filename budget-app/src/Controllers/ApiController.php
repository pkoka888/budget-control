<?php
namespace BudgetApp\Controllers;

use BudgetApp\Services\CsvImporter;
use BudgetApp\Services\FinancialAnalyzer;
use BudgetApp\Services\AiRecommendations;
use BudgetApp\Middleware\ApiAuthMiddleware;

/**
 * API Controller for Budget Control Application
 *
 * Handles RESTful API endpoints for managing financial data including
 * transactions, accounts, budgets, reports, and analytics.
 *
 * @package BudgetApp\Controllers
 */
class ApiController {
    /**
     * @var mixed Database connection instance
     */
    private $db;

    /**
     * @var ApiAuthMiddleware Authentication middleware instance
     */
    private $authMiddleware;

    /**
     * @var array|null Authenticated user data from middleware
     */
    private $authData;

    /**
     * Constructor for ApiController
     *
     * @param mixed $app Application instance (optional)
     */
    public function __construct($app = null) {
        $this->db = $app ? $app->getDatabase() : null;
        $this->authMiddleware = new ApiAuthMiddleware($this->db);
    }

    /**
     * Authenticate the current request
     *
     * @return bool True if authentication successful, false otherwise
     */
    private function authenticate(): bool {
        $this->authData = $this->authMiddleware->authenticate();
        return $this->authData !== null;
    }

    /**
     * Require authentication for the current request
     * Terminates execution with 401 error if not authenticated
     *
     * @return void
     */
    private function requireAuth(): void {
        if (!$this->authenticate()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }
    }

    /**
     * Require specific permission for the current request
     *
     * @param string $permission Permission name to check
     * @return void
     * @throws Exception If permission is denied
     */
    private function requirePermission(string $permission): void {
        $this->authMiddleware->requirePermission($this->authData, $permission);
    }

    /**
     * Send JSON response with appropriate headers
     *
     * @param mixed $data Response data to encode as JSON
     * @param int $statusCode HTTP status code (default: 200)
     * @return void
     */
    private function json($data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        header('X-API-Version: v1');
        header('X-Rate-Limit-Remaining: ' . max(0, $this->authData['rate_limit'] - $this->getCurrentHourRequests()));
        header('X-Rate-Limit-Limit: ' . $this->authData['rate_limit']);
        header('X-Rate-Limit-Reset: ' . strtotime('+1 hour', strtotime(date('Y-m-d H:00:00'))));
        echo json_encode($data);
        exit;
    }

    /**
     * Get the number of requests made in the current hour for rate limiting
     *
     * @return int Number of requests in current hour
     */
    private function getCurrentHourRequests(): int {
        if (!$this->authData) return 0;

        $currentHour = date('Y-m-d H:00:00');
        $rateLimit = $this->db->queryOne(
            "SELECT request_count FROM api_rate_limits
             WHERE api_key_id = ? AND hour_window = ?",
            [$this->authData['api_key_id'], $currentHour]
        );

        return $rateLimit ? (int)$rateLimit['request_count'] : 0;
    }

    /**
     * Get the authenticated user's ID
     *
     * @return int User ID or 0 if not authenticated
     */
    private function getUserId(): int {
        return $this->authData['user_id'] ?? 0;
    }

    /**
     * Get API documentation
     *
     * Returns comprehensive API documentation including endpoints,
     * authentication, response formats, and examples.
     *
     * @param array $params Route parameters (optional version)
     * @return void
     */
    public function getDocumentation(array $params = []): void {
        $version = $params['version'] ?? 'v1';
        $docs = [
            'version' => $version,
            'base_url' => '/api/' . $version,
            'authentication' => [
                'type' => 'API Key',
                'header' => 'X-API-Key or Authorization: Bearer <api_key>',
                'description' => 'Include your API key in requests'
            ],
            'endpoints' => [
                'transactions' => [
                    'GET /api/v1/transactions' => 'List transactions',
                    'GET /api/v1/transactions/{id}' => 'Get transaction details',
                    'POST /api/v1/transactions' => 'Create transaction',
                    'PUT /api/v1/transactions/{id}' => 'Update transaction',
                    'DELETE /api/v1/transactions/{id}' => 'Delete transaction'
                ],
                'accounts' => [
                    'GET /api/v1/accounts' => 'List accounts',
                    'GET /api/v1/accounts/{id}' => 'Get account details',
                    'POST /api/v1/accounts' => 'Create account',
                    'PUT /api/v1/accounts/{id}' => 'Update account',
                    'DELETE /api/v1/accounts/{id}' => 'Delete account'
                ],
                'budgets' => [
                    'GET /api/v1/budgets' => 'List budgets',
                    'GET /api/v1/budgets/{id}' => 'Get budget details',
                    'POST /api/v1/budgets' => 'Create budget',
                    'PUT /api/v1/budgets/{id}' => 'Update budget',
                    'DELETE /api/v1/budgets/{id}' => 'Delete budget'
                ],
                'reports' => [
                    'GET /api/v1/reports/summary' => 'Get financial summary',
                    'GET /api/v1/reports/transactions' => 'Transaction reports',
                    'GET /api/v1/reports/budgets' => 'Budget reports'
                ],
                'analytics' => [
                    'GET /api/v1/analytics/{period}' => 'Get analytics data'
                ]
            ],
            'response_format' => [
                'success' => [
                    'status' => 'success',
                    'data' => 'response_data'
                ],
                'error' => [
                    'status' => 'error',
                    'message' => 'error_description'
                ]
            ],
            'rate_limits' => '1000 requests per hour per API key'
        ];

        $this->json($docs);
    }

    // Transaction Endpoints

    /**
     * Get list of transactions for authenticated user
     *
     * Supports filtering by account, date range, pagination.
     * Returns transactions with account and category names joined.
     *
     * @param array $params Route parameters (unused)
     * @return void
     */
    public function getTransactions(array $params = []): void {
        $this->requireAuth();

        $userId = $this->getUserId();
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);
        $accountId = $_GET['account_id'] ?? null;
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;

        $query = "SELECT t.*, a.name as account_name, c.name as category_name
                  FROM transactions t
                  LEFT JOIN accounts a ON t.account_id = a.id
                  LEFT JOIN categories c ON t.category_id = c.id
                  WHERE t.user_id = ?";
        $params = [$userId];

        if ($accountId) {
            $query .= " AND t.account_id = ?";
            $params[] = $accountId;
        }

        if ($startDate) {
            $query .= " AND t.date >= ?";
            $params[] = $startDate;
        }

        if ($endDate) {
            $query .= " AND t.date <= ?";
            $params[] = $endDate;
        }

        $query .= " ORDER BY t.date DESC, t.id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $transactions = $this->db->query($query, $params);

        $this->json(['transactions' => $transactions]);
    }

    /**
     * Get single transaction details
     *
     * Returns transaction with account and category information.
     * Requires authentication and ownership verification.
     *
     * @param array $params Route parameters containing transaction ID
     * @return void
     */
    public function getTransaction(array $params = []): void {
        $this->requireAuth();

        $userId = $this->getUserId();
        $id = $params['id'] ?? 0;

        $transaction = $this->db->queryOne(
            "SELECT t.*, a.name as account_name, c.name as category_name
             FROM transactions t
             LEFT JOIN accounts a ON t.account_id = a.id
             LEFT JOIN categories c ON t.category_id = c.id
             WHERE t.id = ? AND t.user_id = ?",
            [$id, $userId]
        );

        if (!$transaction) {
            $this->json(['error' => 'Transaction not found'], 404);
        }

        $this->json(['transaction' => $transaction]);
    }

    /**
     * Create a new transaction
     *
     * Validates input data, checks account ownership, and creates transaction.
     * Requires write permission.
     *
     * @param array $params Route parameters (unused)
     * @return void
     */
    public function createTransaction(array $params = []): void {
        $this->requireAuth();
        $this->requirePermission('write');

        $userId = $this->getUserId();
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            $this->json(['error' => 'Invalid JSON data'], 400);
        }

        // Validate required fields
        $required = ['account_id', 'type', 'description', 'amount', 'date'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                $this->json(['error' => "Missing required field: {$field}"], 400);
            }
        }

        // Validate account ownership
        $account = $this->db->queryOne(
            "SELECT id FROM accounts WHERE id = ? AND user_id = ?",
            [$data['account_id'], $userId]
        );

        if (!$account) {
            $this->json(['error' => 'Invalid account'], 400);
        }

        $this->db->execute(
            "INSERT INTO transactions (user_id, account_id, category_id, type, description, amount, currency, date, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $userId,
                $data['account_id'],
                $data['category_id'] ?? null,
                $data['type'],
                $data['description'],
                $data['amount'],
                $data['currency'] ?? 'CZK',
                $data['date'],
                $data['notes'] ?? null
            ]
        );

        $transactionId = $this->db->lastInsertId();

        $this->json(['transaction_id' => $transactionId, 'message' => 'Transaction created'], 201);
    }

    /**
     * Update an existing transaction
     *
     * Allows partial updates of transaction fields.
     * Requires write permission and ownership verification.
     *
     * @param array $params Route parameters containing transaction ID
     * @return void
     */
    public function updateTransaction(array $params = []): void {
        $this->requireAuth();
        $this->requirePermission('write');

        $userId = $this->getUserId();
        $id = $params['id'] ?? 0;
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            $this->json(['error' => 'Invalid JSON data'], 400);
        }

        // Check if transaction exists and belongs to user
        $transaction = $this->db->queryOne(
            "SELECT id FROM transactions WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );

        if (!$transaction) {
            $this->json(['error' => 'Transaction not found'], 404);
        }

        $updateFields = [];
        $params = [];

        $allowedFields = ['account_id', 'category_id', 'type', 'description', 'amount', 'currency', 'date', 'notes'];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($updateFields)) {
            $this->json(['error' => 'No fields to update'], 400);
        }

        $params[] = $id;
        $params[] = $userId;

        $this->db->execute(
            "UPDATE transactions SET " . implode(', ', $updateFields) . " WHERE id = ? AND user_id = ?",
            $params
        );

        $this->json(['message' => 'Transaction updated']);
    }

    /**
     * Delete a transaction
     *
     * Permanently removes a transaction.
     * Requires write permission and ownership verification.
     *
     * @param array $params Route parameters containing transaction ID
     * @return void
     */
    public function deleteTransaction(array $params = []): void {
        $this->requireAuth();
        $this->requirePermission('write');

        $userId = $this->getUserId();
        $id = $params['id'] ?? 0;

        $result = $this->db->execute(
            "DELETE FROM transactions WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );

        if ($result === false) {
            $this->json(['error' => 'Transaction not found'], 404);
        }

        $this->json(['message' => 'Transaction deleted']);
    }

    // Account Endpoints

    /**
     * Get list of user accounts
     *
     * Returns all active accounts for the authenticated user.
     * Supports filtering by account type.
     *
     * @param array $params Route parameters (unused)
     * @return void
     */
    public function getAccounts(array $params = []): void {
        $this->requireAuth();

        $userId = $this->getUserId();
        $type = $_GET['type'] ?? null;

        $query = "SELECT * FROM accounts WHERE user_id = ? AND is_active = 1";
        $params = [$userId];

        if ($type) {
            $query .= " AND type = ?";
            $params[] = $type;
        }

        $query .= " ORDER BY name";

        $accounts = $this->db->query($query, $params);

        $this->json(['accounts' => $accounts]);
    }

    /**
     * Get single account details
     *
     * Returns account information for the specified account ID.
     * Requires authentication and ownership verification.
     *
     * @param array $params Route parameters containing account ID
     * @return void
     */
    public function getAccount(array $params = []): void {
        $this->requireAuth();

        $userId = $this->getUserId();
        $id = $params['id'] ?? 0;

        $account = $this->db->queryOne(
            "SELECT * FROM accounts WHERE id = ? AND user_id = ? AND is_active = 1",
            [$id, $userId]
        );

        if (!$account) {
            $this->json(['error' => 'Account not found'], 404);
        }

        $this->json(['account' => $account]);
    }

    /**
     * Create a new account
     *
     * Validates input and creates a new account for the user.
     * Requires write permission.
     *
     * @param array $params Route parameters (unused)
     * @return void
     */
    public function createAccount(array $params = []): void {
        $this->requireAuth();
        $this->requirePermission('write');

        $userId = $this->getUserId();
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            $this->json(['error' => 'Invalid JSON data'], 400);
        }

        $required = ['name', 'type'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                $this->json(['error' => "Missing required field: {$field}"], 400);
            }
        }

        $this->db->execute(
            "INSERT INTO accounts (user_id, name, type, currency, balance, initial_balance, opening_date, description)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $userId,
                $data['name'],
                $data['type'],
                $data['currency'] ?? 'CZK',
                $data['balance'] ?? 0,
                $data['initial_balance'] ?? 0,
                $data['opening_date'] ?? null,
                $data['description'] ?? null
            ]
        );

        $accountId = $this->db->lastInsertId();

        $this->json(['account_id' => $accountId, 'message' => 'Account created'], 201);
    }

    /**
     * Update an existing account
     *
     * Allows partial updates of account fields.
     * Requires write permission and ownership verification.
     *
     * @param array $params Route parameters containing account ID
     * @return void
     */
    public function updateAccount(array $params = []): void {
        $this->requireAuth();
        $this->requirePermission('write');

        $userId = $this->getUserId();
        $id = $params['id'] ?? 0;
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            $this->json(['error' => 'Invalid JSON data'], 400);
        }

        $account = $this->db->queryOne(
            "SELECT id FROM accounts WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );

        if (!$account) {
            $this->json(['error' => 'Account not found'], 404);
        }

        $updateFields = [];
        $params = [];

        $allowedFields = ['name', 'type', 'currency', 'balance', 'opening_date', 'description'];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($updateFields)) {
            $this->json(['error' => 'No fields to update'], 400);
        }

        $params[] = $id;
        $params[] = $userId;

        $this->db->execute(
            "UPDATE accounts SET " . implode(', ', $updateFields) . " WHERE id = ? AND user_id = ?",
            $params
        );

        $this->json(['message' => 'Account updated']);
    }

    /**
     * Delete an account (soft delete)
     *
     * Marks account as inactive. Cannot delete if account has transactions.
     * Requires write permission and ownership verification.
     *
     * @param array $params Route parameters containing account ID
     * @return void
     */
    public function deleteAccount(array $params = []): void {
        $this->requireAuth();
        $this->requirePermission('write');

        $userId = $this->getUserId();
        $id = $params['id'] ?? 0;

        // Check if account has transactions
        $transactionCount = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM transactions WHERE account_id = ?",
            [$id]
        );

        if ($transactionCount['count'] > 0) {
            $this->json(['error' => 'Cannot delete account with existing transactions'], 400);
        }

        $result = $this->db->execute(
            "UPDATE accounts SET is_active = 0 WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );

        if ($result === false) {
            $this->json(['error' => 'Account not found'], 404);
        }

        $this->json(['message' => 'Account deleted']);
    }

    // Budget Endpoints

    /**
     * Get list of budgets for a specific month
     *
     * Returns budgets with category names joined.
     * Defaults to current month if not specified.
     *
     * @param array $params Route parameters (unused)
     * @return void
     */
    public function getBudgets(array $params = []): void {
        $this->requireAuth();

        $userId = $this->getUserId();
        $month = $_GET['month'] ?? date('Y-m');

        $budgets = $this->db->query(
            "SELECT b.*, c.name as category_name
             FROM budgets b
             JOIN categories c ON b.category_id = c.id
             WHERE b.user_id = ? AND b.month = ? AND b.is_active = 1
             ORDER BY c.name",
            [$userId, $month]
        );

        $this->json(['budgets' => $budgets]);
    }

    /**
     * Get single budget details
     *
     * Returns budget with category name joined.
     * Requires authentication and ownership verification.
     *
     * @param array $params Route parameters containing budget ID
     * @return void
     */
    public function getBudget(array $params = []): void {
        $this->requireAuth();

        $userId = $this->getUserId();
        $id = $params['id'] ?? 0;

        $budget = $this->db->queryOne(
            "SELECT b.*, c.name as category_name
             FROM budgets b
             JOIN categories c ON b.category_id = c.id
             WHERE b.id = ? AND b.user_id = ? AND b.is_active = 1",
            [$id, $userId]
        );

        if (!$budget) {
            $this->json(['error' => 'Budget not found'], 404);
        }

        $this->json(['budget' => $budget]);
    }

    /**
     * Create a new budget
     *
     * Validates category ownership and prevents duplicate budgets
     * for the same category and month. Requires write permission.
     *
     * @param array $params Route parameters (unused)
     * @return void
     */
    public function createBudget(array $params = []): void {
        $this->requireAuth();
        $this->requirePermission('write');

        $userId = $this->getUserId();
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            $this->json(['error' => 'Invalid JSON data'], 400);
        }

        $required = ['category_id', 'month', 'amount'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                $this->json(['error' => "Missing required field: {$field}"], 400);
            }
        }

        // Check if category belongs to user
        $category = $this->db->queryOne(
            "SELECT id FROM categories WHERE id = ? AND user_id = ?",
            [$data['category_id'], $userId]
        );

        if (!$category) {
            $this->json(['error' => 'Invalid category'], 400);
        }

        // Check for existing budget
        $existing = $this->db->queryOne(
            "SELECT id FROM budgets WHERE user_id = ? AND category_id = ? AND month = ?",
            [$userId, $data['category_id'], $data['month']]
        );

        if ($existing) {
            $this->json(['error' => 'Budget already exists for this category and month'], 400);
        }

        $this->db->execute(
            "INSERT INTO budgets (user_id, category_id, month, amount, notes)
             VALUES (?, ?, ?, ?, ?)",
            [
                $userId,
                $data['category_id'],
                $data['month'],
                $data['amount'],
                $data['notes'] ?? null
            ]
        );

        $budgetId = $this->db->lastInsertId();

        $this->json(['budget_id' => $budgetId, 'message' => 'Budget created'], 201);
    }

    /**
     * Update an existing budget
     *
     * Allows updating budget amount and notes.
     * Requires write permission and ownership verification.
     *
     * @param array $params Route parameters containing budget ID
     * @return void
     */
    public function updateBudget(array $params = []): void {
        $this->requireAuth();
        $this->requirePermission('write');

        $userId = $this->getUserId();
        $id = $params['id'] ?? 0;
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            $this->json(['error' => 'Invalid JSON data'], 400);
        }

        $budget = $this->db->queryOne(
            "SELECT id FROM budgets WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );

        if (!$budget) {
            $this->json(['error' => 'Budget not found'], 404);
        }

        $updateFields = [];
        $params = [];

        $allowedFields = ['amount', 'notes'];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($updateFields)) {
            $this->json(['error' => 'No fields to update'], 400);
        }

        $params[] = $id;
        $params[] = $userId;

        $this->db->execute(
            "UPDATE budgets SET " . implode(', ', $updateFields) . " WHERE id = ? AND user_id = ?",
            $params
        );

        $this->json(['message' => 'Budget updated']);
    }

    /**
     * Delete a budget (soft delete)
     *
     * Marks budget as inactive.
     * Requires write permission and ownership verification.
     *
     * @param array $params Route parameters containing budget ID
     * @return void
     */
    public function deleteBudget(array $params = []): void {
        $this->requireAuth();
        $this->requirePermission('write');

        $userId = $this->getUserId();
        $id = $params['id'] ?? 0;

        $result = $this->db->execute(
            "UPDATE budgets SET is_active = 0 WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );

        if ($result === false) {
            $this->json(['error' => 'Budget not found'], 404);
        }

        $this->json(['message' => 'Budget deleted']);
    }

    // Report Endpoints

    /**
     * Get financial summary report for a month
     *
     * Returns income, expenses, net amount, and budget vs actual data.
     * Defaults to current month if not specified.
     *
     * @param array $params Route parameters (unused)
     * @return void
     */
    public function getReportsSummary(array $params = []): void {
        $this->requireAuth();

        $userId = $this->getUserId();
        $month = $_GET['month'] ?? date('Y-m');

        // Get income and expenses for the month
        $income = $this->db->queryOne(
            "SELECT COALESCE(SUM(amount), 0) as total
             FROM transactions
             WHERE user_id = ? AND type = 'income' AND strftime('%Y-%m', date) = ?",
            [$userId, $month]
        );

        $expenses = $this->db->queryOne(
            "SELECT COALESCE(SUM(amount), 0) as total
             FROM transactions
             WHERE user_id = ? AND type = 'expense' AND strftime('%Y-%m', date) = ?",
            [$userId, $month]
        );

        // Get budget vs actual
        $budgetVsActual = $this->db->query(
            "SELECT c.name as category, b.amount as budgeted, COALESCE(SUM(t.amount), 0) as spent
             FROM budgets b
             JOIN categories c ON b.category_id = c.id
             LEFT JOIN transactions t ON t.category_id = c.id
                AND t.user_id = b.user_id
                AND t.type = 'expense'
                AND strftime('%Y-%m', t.date) = b.month
             WHERE b.user_id = ? AND b.month = ? AND b.is_active = 1
             GROUP BY c.id, c.name, b.amount",
            [$userId, $month]
        );

        $this->json([
            'summary' => [
                'month' => $month,
                'income' => (float)$income['total'],
                'expenses' => (float)$expenses['total'],
                'net' => (float)$income['total'] - (float)$expenses['total']
            ],
            'budget_vs_actual' => $budgetVsActual
        ]);
    }

    /**
     * Get transaction reports grouped by category, account, or date
     *
     * Returns aggregated transaction data with totals and counts.
     * Supports date range filtering and different grouping options.
     *
     * @param array $params Route parameters (unused)
     * @return void
     */
    public function getReportsTransactions(array $params = []): void {
        $this->requireAuth();

        $userId = $this->getUserId();
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        $groupBy = $_GET['group_by'] ?? 'category'; // category, account, date

        $query = "SELECT ";
        $groupField = "";

        switch ($groupBy) {
            case 'category':
                $query .= "c.name as group_name, c.id as group_id";
                $groupField = "c.id, c.name";
                break;
            case 'account':
                $query .= "a.name as group_name, a.id as group_id";
                $groupField = "a.id, a.name";
                break;
            case 'date':
                $query .= "t.date as group_name, t.date as group_id";
                $groupField = "t.date";
                break;
            default:
                $this->json(['error' => 'Invalid group_by parameter'], 400);
        }

        $query .= ", t.type, SUM(t.amount) as total_amount, COUNT(*) as transaction_count
                   FROM transactions t
                   LEFT JOIN categories c ON t.category_id = c.id
                   LEFT JOIN accounts a ON t.account_id = a.id
                   WHERE t.user_id = ? AND t.date BETWEEN ? AND ?
                   GROUP BY {$groupField}, t.type
                   ORDER BY total_amount DESC";

        $transactions = $this->db->query($query, [$userId, $startDate, $endDate]);

        $this->json(['transactions' => $transactions]);
    }

    /**
     * Get budget performance report
     *
     * Returns budget vs actual spending with status indicators.
     * Defaults to current month if not specified.
     *
     * @param array $params Route parameters (unused)
     * @return void
     */
    public function getReportsBudgets(array $params = []): void {
        $this->requireAuth();

        $userId = $this->getUserId();
        $month = $_GET['month'] ?? date('Y-m');

        $budgets = $this->db->query(
            "SELECT c.name as category_name, b.amount as budgeted,
                    COALESCE(SUM(t.amount), 0) as spent,
                    (b.amount - COALESCE(SUM(t.amount), 0)) as remaining,
                    CASE WHEN COALESCE(SUM(t.amount), 0) > b.amount THEN 'over' ELSE 'under' END as status
             FROM budgets b
             JOIN categories c ON b.category_id = c.id
             LEFT JOIN transactions t ON t.category_id = c.id
                AND t.user_id = b.user_id
                AND t.type = 'expense'
                AND strftime('%Y-%m', t.date) = b.month
             WHERE b.user_id = ? AND b.month = ? AND b.is_active = 1
             GROUP BY b.id, c.name, b.amount
             ORDER BY b.amount DESC",
            [$userId, $month]
        );

        $this->json(['budgets' => $budgets]);
    }

    // Legacy methods (keeping for backward compatibility with existing frontend)

    /**
     * Categorize transaction based on description (legacy method)
     *
     * Simple categorization logic for backward compatibility.
     * In production, this would use ML/AI for better categorization.
     *
     * @param array $params Route parameters (unused)
     * @return void
     */
    public function categorizeTransaction(array $params = []): void {
        $this->requireAuth();

        $description = $_POST['description'] ?? '';

        if (empty($description)) {
            $this->json(['error' => 'Description is required'], 400);
            return;
        }

        // Simple categorization logic - in real implementation, use ML/AI
        $categoryId = 1; // Default category

        $category = $this->db->queryOne(
            "SELECT id, name FROM categories WHERE id = ?",
            [$categoryId]
        );

        $this->json(['category_id' => $categoryId, 'category_name' => $category['name'] ?? 'Uncategorized']);
    }

    /**
     * Get financial recommendations (legacy method)
     *
     * Returns basic financial recommendations.
     * In production, this would use AI service for personalized recommendations.
     *
     * @param array $params Route parameters (unused)
     * @return void
     */
    public function getRecommendations(array $params = []): void {
        $this->requireAuth();

        $userId = $this->getUserId();

        // Simple recommendations - in real implementation, use AI service
        $recommendations = [
            [
                'title' => 'Review Monthly Budget',
                'description' => 'Check your spending against your budget limits',
                'priority' => 'medium'
            ],
            [
                'title' => 'Save for Emergency Fund',
                'description' => 'Aim to save 3-6 months of expenses',
                'priority' => 'high'
            ]
        ];

        $this->json(['recommendations' => $recommendations]);
    }

    /**
     * Get financial analytics data
     *
     * Returns spending trends, summary statistics, and basic analytics.
     * Supports different time periods (30days, 90days, 1year).
     *
     * @param array $params Route parameters containing period
     * @return void
     */
    public function getAnalytics(array $params = []): void {
        $this->requireAuth();

        $userId = $this->getUserId();
        $period = $params['period'] ?? '30days';

        if ($period === '30days') {
            $startDate = date('Y-m-d', strtotime('-30 days'));
        } elseif ($period === '90days') {
            $startDate = date('Y-m-d', strtotime('-90 days'));
        } else {
            $startDate = date('Y-m-d', strtotime('-1 year'));
        }

        // Simple analytics - spending trend
        $trend = $this->db->query(
            "SELECT DATE(date) as date, SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expenses,
                    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income
             FROM transactions
             WHERE user_id = ? AND date >= ?
             GROUP BY DATE(date)
             ORDER BY date",
            [$userId, $startDate]
        );

        $summary = [
            'total_income' => 0,
            'total_expenses' => 0,
            'net_amount' => 0
        ];

        foreach ($trend as $day) {
            $summary['total_income'] += (float)$day['income'];
            $summary['total_expenses'] += (float)$day['expenses'];
        }
        $summary['net_amount'] = $summary['total_income'] - $summary['total_expenses'];

        $this->json([
            'trend' => $trend,
            'summary' => $summary,
            'anomalies' => [], // Placeholder for anomaly detection
            'healthScore' => 75 // Placeholder for health score
        ]);
    }

    // API Key Management Endpoints

    /**
     * List API keys for authenticated user
     *
     * Returns all active API keys for the user with basic information.
     * Requires admin permission.
     *
     * @param array $params Route parameters (unused)
     * @return void
     */
    public function getApiKeys(array $params = []): void {
        $this->requireAuth();
        $this->requirePermission('admin');

        $userId = $this->getUserId();

        $apiKeys = $this->db->query(
            "SELECT id, name, permissions, rate_limit, last_used_at, created_at
             FROM api_keys
             WHERE user_id = ? AND is_active = 1
             ORDER BY created_at DESC",
            [$userId]
        );

        $this->json(['api_keys' => $apiKeys]);
    }

    /**
     * Create a new API key
     *
     * Creates a new API key with specified permissions and rate limit.
     * Requires admin permission.
     *
     * @param array $params Route parameters (unused)
     * @return void
     */
    public function createApiKey(array $params = []): void {
        $this->requireAuth();
        $this->requirePermission('admin');

        $userId = $this->getUserId();
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            $this->json(['error' => 'Invalid JSON data'], 400);
        }

        // Validate required fields
        $required = ['name'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                $this->json(['error' => "Missing required field: {$field}"], 400);
            }
        }

        // Validate permissions
        $permissions = $data['permissions'] ?? 'read';
        $validPermissions = ['read', 'write', 'admin'];
        $permissionList = explode(',', $permissions);
        foreach ($permissionList as $perm) {
            if (!in_array(trim($perm), $validPermissions)) {
                $this->json(['error' => 'Invalid permission: ' . $perm], 400);
            }
        }

        // Generate API key
        $apiKey = 'bk_' . bin2hex(random_bytes(32));
        $rateLimit = (int)($data['rate_limit'] ?? 1000);

        $this->db->execute(
            "INSERT INTO api_keys (user_id, name, api_key, permissions, rate_limit, is_active, created_at)
             VALUES (?, ?, ?, ?, ?, 1, datetime('now'))",
            [$userId, $data['name'], $apiKey, $permissions, $rateLimit]
        );

        $keyId = $this->db->lastInsertId();

        $this->json([
            'api_key_id' => $keyId,
            'api_key' => $apiKey,
            'name' => $data['name'],
            'permissions' => $permissions,
            'rate_limit' => $rateLimit,
            'message' => 'API key created successfully'
        ], 201);
    }

    /**
     * Delete an API key
     *
     * Deactivates an API key permanently.
     * Requires admin permission and key ownership.
     *
     * @param array $params Route parameters containing key ID
     * @return void
     */
    public function deleteApiKey(array $params = []): void {
        $this->requireAuth();
        $this->requirePermission('admin');

        $userId = $this->getUserId();
        $keyId = $params['id'] ?? 0;

        // Verify key ownership
        $key = $this->db->queryOne(
            "SELECT id FROM api_keys WHERE id = ? AND user_id = ? AND is_active = 1",
            [$keyId, $userId]
        );

        if (!$key) {
            $this->json(['error' => 'API key not found'], 404);
        }

        $this->db->execute(
            "UPDATE api_keys SET is_active = 0 WHERE id = ? AND user_id = ?",
            [$keyId, $userId]
        );

        $this->json(['message' => 'API key deleted successfully']);
    }

    /**
     * Rotate an API key
     *
     * Generates a new API key and deactivates the old one.
     * Requires admin permission and key ownership.
     *
     * @param array $params Route parameters containing key ID
     * @return void
     */
    public function rotateApiKey(array $params = []): void {
        $this->requireAuth();
        $this->requirePermission('admin');

        $userId = $this->getUserId();
        $keyId = $params['id'] ?? 0;

        // Verify key ownership
        $key = $this->db->queryOne(
            "SELECT id FROM api_keys WHERE id = ? AND user_id = ? AND is_active = 1",
            [$keyId, $userId]
        );

        if (!$key) {
            $this->json(['error' => 'API key not found'], 404);
        }

        $newApiKey = $this->authMiddleware->rotateKey($keyId);

        if (!$newApiKey) {
            $this->json(['error' => 'Failed to rotate API key'], 500);
        }

        $this->json([
            'new_api_key' => $newApiKey,
            'message' => 'API key rotated successfully'
        ]);
    }
}
