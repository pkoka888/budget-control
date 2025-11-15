<?php
namespace BudgetApp\Controllers;

use BudgetApp\Services\CsvExporter;
use BudgetApp\Services\ExcelExporter;
use BudgetApp\Services\RecurringTransactionService;

class TransactionController extends BaseController {
    public function list(array $params = []): void {
        $userId = $this->getUserId();
        $page = (int)($this->getQueryParam('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        // Get filters
        $categoryIds = $this->getQueryParam('category'); // Can be single value or array
        $accountId = $this->getQueryParam('account');
        $type = $this->getQueryParam('type'); // income, expense
        $startDate = $this->getQueryParam('start_date');
        $endDate = $this->getQueryParam('end_date');
        $minAmount = $this->getQueryParam('min_amount');
        $maxAmount = $this->getQueryParam('max_amount');
        $search = $this->getQueryParam('search'); // description/merchant search

        // Build query
        $query = "SELECT t.*, c.name as category_name, a.name as account_name,
                         CASE WHEN ts.transaction_id IS NOT NULL THEN 1 ELSE 0 END as has_splits
                  FROM transactions t
                  LEFT JOIN categories c ON t.category_id = c.id
                  JOIN accounts a ON t.account_id = a.id
                  LEFT JOIN transaction_splits ts ON t.id = ts.transaction_id
                  WHERE a.user_id = ?";
        $queryParams = [$userId];

        // Multi-category filtering
        if ($categoryIds) {
            if (is_array($categoryIds)) {
                $placeholders = str_repeat('?,', count($categoryIds) - 1) . '?';
                $query .= " AND t.category_id IN ($placeholders)";
                $queryParams = array_merge($queryParams, $categoryIds);
            } else {
                $query .= " AND t.category_id = ?";
                $queryParams[] = $categoryIds;
            }
        }

        if ($accountId) {
            $query .= " AND t.account_id = ?";
            $queryParams[] = $accountId;
        }
        if ($type) {
            $query .= " AND t.type = ?";
            $queryParams[] = $type;
        }
        if ($startDate) {
            $query .= " AND t.date >= ?";
            $queryParams[] = $startDate;
        }
        if ($endDate) {
            $query .= " AND t.date <= ?";
            $queryParams[] = $endDate;
        }
        if ($minAmount !== null && $minAmount !== '') {
            $query .= " AND t.amount >= ?";
            $queryParams[] = (float)$minAmount;
        }
        if ($maxAmount !== null && $maxAmount !== '') {
            $query .= " AND t.amount <= ?";
            $queryParams[] = (float)$maxAmount;
        }
        if ($search) {
            $query .= " AND (t.description LIKE ? OR t.merchant LIKE ?)";
            $queryParams[] = '%' . $search . '%';
            $queryParams[] = '%' . $search . '%';
        }

        $query .= " ORDER BY t.date DESC LIMIT ? OFFSET ?";
        $queryParams[] = $limit;
        $queryParams[] = $offset;

        $transactions = $this->db->query($query, $queryParams);

        // Get total count with same filters
        $countQuery = "SELECT COUNT(DISTINCT t.id) as count FROM transactions t
                       LEFT JOIN categories c ON t.category_id = c.id
                       JOIN accounts a ON t.account_id = a.id
                       LEFT JOIN transaction_splits ts ON t.id = ts.transaction_id
                       WHERE a.user_id = ?";
        $countParams = [$userId];

        // Apply same filters to count query
        if ($categoryIds) {
            if (is_array($categoryIds)) {
                $placeholders = str_repeat('?,', count($categoryIds) - 1) . '?';
                $countQuery .= " AND t.category_id IN ($placeholders)";
                $countParams = array_merge($countParams, $categoryIds);
            } else {
                $countQuery .= " AND t.category_id = ?";
                $countParams[] = $categoryIds;
            }
        }

        if ($accountId) $countQuery .= " AND t.account_id = ?";
        if ($type) $countQuery .= " AND t.type = ?";
        if ($startDate) $countQuery .= " AND t.date >= ?";
        if ($endDate) $countQuery .= " AND t.date <= ?";
        if ($minAmount !== null && $minAmount !== '') $countQuery .= " AND t.amount >= ?";
        if ($maxAmount !== null && $maxAmount !== '') $countQuery .= " AND t.amount <= ?";
        if ($search) $countQuery .= " AND (t.description LIKE ? OR t.merchant LIKE ?)";

        // Add filter parameters to count params in same order
        if ($accountId) $countParams[] = $accountId;
        if ($type) $countParams[] = $type;
        if ($startDate) $countParams[] = $startDate;
        if ($endDate) $countParams[] = $endDate;
        if ($minAmount !== null && $minAmount !== '') $countParams[] = (float)$minAmount;
        if ($maxAmount !== null && $maxAmount !== '') $countParams[] = (float)$maxAmount;
        if ($search) {
            $countParams[] = '%' . $search . '%';
            $countParams[] = '%' . $search . '%';
        }

        $countResult = $this->db->queryOne($countQuery, $countParams);
        $totalPages = ceil($countResult['count'] / $limit);

        // Get accounts and categories for filters
        $accounts = $this->db->query(
            "SELECT id, name FROM accounts WHERE user_id = ? ORDER BY name",
            [$userId]
        );
        $categories = $this->db->query(
            "SELECT id, name FROM categories WHERE user_id = ? OR user_id IS NULL ORDER BY name",
            [$userId]
        );

        echo $this->app->render('transactions/list', [
            'transactions' => $transactions,
            'accounts' => $accounts,
            'categories' => $categories,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $countResult['count']
        ]);
    }

    public function createForm(array $params = []): void {
        $userId = $this->getUserId();

        $accounts = $this->db->query(
            "SELECT id, name FROM accounts WHERE user_id = ? ORDER BY name",
            [$userId]
        );
        $categories = $this->db->query(
            "SELECT id, name FROM categories WHERE user_id = ? OR user_id IS NULL ORDER BY name",
            [$userId]
        );

        echo $this->app->render('transactions/create', [
            'accounts' => $accounts,
            'categories' => $categories
        ]);
    }

    public function create(array $params = []): void {
        $userId = $this->getUserId();

        $accountId = (int)$_POST['account_id'];
        $categoryId = (int)($_POST['category_id'] ?? 0) ?: null;
        $date = $_POST['date'] ?? date('Y-m-d');
        $description = $_POST['description'] ?? '';
        $amount = (float)$_POST['amount'];
        $type = $_POST['type'] ?? 'expense';

        // Verify account belongs to user
        $account = $this->db->queryOne(
            "SELECT id FROM accounts WHERE id = ? AND user_id = ?",
            [$accountId, $userId]
        );
        if (!$account) {
            http_response_code(403);
            return;
        }

        $transactionId = $this->db->insert('transactions', [
            'account_id' => $accountId,
            'category_id' => $categoryId,
            'date' => $date,
            'description' => $description,
            'amount' => $amount,
            'type' => $type,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $this->setFlash('success', 'Transakce vytvořena');
        header('Location: /transactions');
        exit;
    }

    public function show(array $params = []): void {
        $transactionId = $params['id'] ?? 0;
        $userId = $this->getUserId();

        $transaction = $this->db->queryOne(
            "SELECT t.*, c.name as category_name, a.name as account_name
             FROM transactions t
             LEFT JOIN categories c ON t.category_id = c.id
             JOIN accounts a ON t.account_id = a.id
             WHERE t.id = ? AND a.user_id = ?",
            [$transactionId, $userId]
        );

        if (!$transaction) {
            http_response_code(404);
            return;
        }

        // Get splits if they exist
        $splits = $this->db->query(
            "SELECT ts.*, c.name as category_name, c.color as category_color
             FROM transaction_splits ts
             JOIN categories c ON ts.category_id = c.id
             WHERE ts.parent_transaction_id = ?
             ORDER BY ts.created_at",
            [$transactionId]
        );

        echo $this->app->render('transactions/show', [
            'transaction' => $transaction,
            'splits' => $splits
        ]);
    }

    public function update(array $params = []): void {
        $transactionId = $params['id'] ?? 0;
        $userId = $this->getUserId();

        $transaction = $this->db->queryOne(
            "SELECT t.* FROM transactions t
             JOIN accounts a ON t.account_id = a.id
             WHERE t.id = ? AND a.user_id = ?",
            [$transactionId, $userId]
        );

        if (!$transaction) {
            http_response_code(404);
            return;
        }

        $updates = [];
        if (isset($_POST['description'])) $updates['description'] = $_POST['description'];
        if (isset($_POST['amount'])) $updates['amount'] = (float)$_POST['amount'];
        if (isset($_POST['category_id'])) $updates['category_id'] = (int)$_POST['category_id'] ?: null;
        if (isset($_POST['date'])) $updates['date'] = $_POST['date'];

        $this->db->update('transactions', $updates, ['id' => $transactionId]);

        $this->setFlash('success', 'Transakce aktualizována');
        header('Location: /transactions');
        exit;
    }

    public function delete(array $params = []): void {
        $transactionId = $params['id'] ?? 0;
        $userId = $this->getUserId();

        $transaction = $this->db->queryOne(
            "SELECT t.* FROM transactions t
             JOIN accounts a ON t.account_id = a.id
             WHERE t.id = ? AND a.user_id = ?",
            [$transactionId, $userId]
        );

        if (!$transaction) {
            http_response_code(404);
            return;
        }

        $this->db->delete('transactions', ['id' => $transactionId]);

        $this->setFlash('success', 'Transakce smazána');
        header('Location: /transactions');
        exit;
    }

    public function exportCsv(array $params = []): void {
        $userId = $this->getUserId();

        // Get filters from query parameters (same as list method)
        $filters = [
            'category' => $this->getQueryParam('category'),
            'account' => $this->getQueryParam('account'),
            'type' => $this->getQueryParam('type'),
            'start_date' => $this->getQueryParam('start_date'),
            'end_date' => $this->getQueryParam('end_date'),
            'min_amount' => $this->getQueryParam('min_amount'),
            'max_amount' => $this->getQueryParam('max_amount'),
            'search' => $this->getQueryParam('search')
        ];

        $exporter = new CsvExporter($this->db);
        $exporter->exportTransactions($userId, $filters);
    }

    public function exportExcel(array $params = []): void {
        $userId = $this->getUserId();

        // Get filters from query parameters (same as list method)
        $filters = [
            'category' => $this->getQueryParam('category'),
            'account' => $this->getQueryParam('account'),
            'type' => $this->getQueryParam('type'),
            'start_date' => $this->getQueryParam('start_date'),
            'end_date' => $this->getQueryParam('end_date'),
            'min_amount' => $this->getQueryParam('min_amount'),
            'max_amount' => $this->getQueryParam('max_amount'),
            'search' => $this->getQueryParam('search')
        ];

        $exporter = new ExcelExporter($this->db);
        $exporter->exportTransactions($userId, $filters);
    }

    public function bulkAction(array $params = []): void {
        $userId = $this->getUserId();
        $operation = $_POST['operation'] ?? '';
        $transactionIds = $_POST['transaction_ids'] ?? [];
        $categoryId = $_POST['category_id'] ?? null;
        $tags = $_POST['tags'] ?? null;

        // Validate operation
        $validOperations = ['categorize', 'delete', 'tag'];
        if (!in_array($operation, $validOperations)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid operation']);
            return;
        }

        // Get transaction IDs either from explicit list or filters
        if (empty($transactionIds)) {
            $transactionIds = $this->getFilteredTransactionIds($userId);
        }

        if (empty($transactionIds)) {
            http_response_code(400);
            echo json_encode(['error' => 'No transactions selected']);
            return;
        }

        // Verify all transactions belong to user
        $placeholders = str_repeat('?,', count($transactionIds) - 1) . '?';
        $params = array_merge([$userId], $transactionIds);
        $validTransactions = $this->db->query(
            "SELECT t.id FROM transactions t
             JOIN accounts a ON t.account_id = a.id
             WHERE a.user_id = ? AND t.id IN ($placeholders)",
            $params
        );

        $validIds = array_column($validTransactions, 'id');
        $invalidIds = array_diff($transactionIds, $validIds);

        if (!empty($invalidIds)) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied to some transactions']);
            return;
        }

        // Execute bulk operation within database transaction
        $this->db->beginTransaction();

        try {
            $results = $this->executeBulkOperation($operation, $validIds, $categoryId, $tags);
            $this->db->commit();

            echo json_encode([
                'success' => true,
                'operation' => $operation,
                'results' => $results,
                'total_processed' => count($validIds)
            ]);

        } catch (\Exception $e) {
            $this->db->rollback();
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Operation failed: ' . $e->getMessage()
            ]);
        }
    }

    private function getFilteredTransactionIds(int $userId): array {
        // Build query using same filters as list method
        $query = "SELECT t.id FROM transactions t
                  LEFT JOIN categories c ON t.category_id = c.id
                  JOIN accounts a ON t.account_id = a.id
                  WHERE a.user_id = ?";
        $queryParams = [$userId];

        // Apply same filters as list method
        $categoryIds = $this->getQueryParam('category');
        $accountId = $this->getQueryParam('account');
        $type = $this->getQueryParam('type');
        $startDate = $this->getQueryParam('start_date');
        $endDate = $this->getQueryParam('end_date');
        $minAmount = $this->getQueryParam('min_amount');
        $maxAmount = $this->getQueryParam('max_amount');
        $search = $this->getQueryParam('search');

        if ($categoryIds) {
            if (is_array($categoryIds)) {
                $placeholders = str_repeat('?,', count($categoryIds) - 1) . '?';
                $query .= " AND t.category_id IN ($placeholders)";
                $queryParams = array_merge($queryParams, $categoryIds);
            } else {
                $query .= " AND t.category_id = ?";
                $queryParams[] = $categoryIds;
            }
        }

        if ($accountId) {
            $query .= " AND t.account_id = ?";
            $queryParams[] = $accountId;
        }
        if ($type) {
            $query .= " AND t.type = ?";
            $queryParams[] = $type;
        }
        if ($startDate) {
            $query .= " AND t.date >= ?";
            $queryParams[] = $startDate;
        }
        if ($endDate) {
            $query .= " AND t.date <= ?";
            $queryParams[] = $endDate;
        }
        if ($minAmount !== null && $minAmount !== '') {
            $query .= " AND t.amount >= ?";
            $queryParams[] = (float)$minAmount;
        }
        if ($maxAmount !== null && $maxAmount !== '') {
            $query .= " AND t.amount <= ?";
            $queryParams[] = (float)$maxAmount;
        }
        if ($search) {
            $query .= " AND (t.description LIKE ? OR t.merchant LIKE ?)";
            $queryParams[] = '%' . $search . '%';
            $queryParams[] = '%' . $search . '%';
        }

        $transactions = $this->db->query($query, $queryParams);
        return array_column($transactions, 'id');
    }

    private function executeBulkOperation(string $operation, array $transactionIds, ?int $categoryId, ?string $tags): array {
        $results = [
            'successful' => 0,
            'failed' => 0,
            'errors' => []
        ];

        switch ($operation) {
            case 'categorize':
                if ($categoryId === null) {
                    throw new \Exception('Category ID required for categorize operation');
                }
                foreach ($transactionIds as $id) {
                    try {
                        $this->db->update('transactions', ['category_id' => $categoryId], ['id' => $id]);
                        $results['successful']++;
                    } catch (\Exception $e) {
                        $results['failed']++;
                        $results['errors'][] = "Failed to categorize transaction $id: " . $e->getMessage();
                    }
                }
                break;

            case 'delete':
                foreach ($transactionIds as $id) {
                    try {
                        $this->db->delete('transactions', ['id' => $id]);
                        $results['successful']++;
                    } catch (\Exception $e) {
                        $results['failed']++;
                        $results['errors'][] = "Failed to delete transaction $id: " . $e->getMessage();
                    }
                }
                break;

            case 'tag':
                if ($tags === null) {
                    throw new \Exception('Tags required for tag operation');
                }
                foreach ($transactionIds as $id) {
                    try {
                        $this->db->update('transactions', ['tags' => $tags], ['id' => $id]);
                        $results['successful']++;
                    } catch (\Exception $e) {
                        $results['failed']++;
                        $results['errors'][] = "Failed to tag transaction $id: " . $e->getMessage();
                    }
                }
                break;
        }

        return $results;
    }

    public function createSplit(array $params = []): void {
        $transactionId = $params['id'] ?? 0;
        $userId = $this->getUserId();

        // Verify transaction belongs to user
        $transaction = $this->db->queryOne(
            "SELECT t.* FROM transactions t
             JOIN accounts a ON t.account_id = a.id
             WHERE t.id = ? AND a.user_id = ?",
            [$transactionId, $userId]
        );

        if (!$transaction) {
            http_response_code(404);
            echo json_encode(['error' => 'Transaction not found']);
            return;
        }

        $splits = $_POST['splits'] ?? [];
        if (empty($splits)) {
            http_response_code(400);
            echo json_encode(['error' => 'No splits provided']);
            return;
        }

        // Validate splits
        $totalSplitAmount = 0;
        foreach ($splits as $split) {
            if (!isset($split['category_id']) || !isset($split['amount'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid split data']);
                return;
            }
            $totalSplitAmount += (float)$split['amount'];
        }

        // Check if splits sum to original transaction amount
        if (abs($totalSplitAmount - (float)$transaction['amount']) > 0.01) {
            http_response_code(400);
            echo json_encode(['error' => 'Split amounts must equal original transaction amount']);
            return;
        }

        // Begin transaction
        $this->db->beginTransaction();

        try {
            // Delete existing splits
            $this->db->delete('transaction_splits', ['parent_transaction_id' => $transactionId]);

            // Insert new splits
            foreach ($splits as $split) {
                $this->db->insert('transaction_splits', [
                    'parent_transaction_id' => $transactionId,
                    'category_id' => (int)$split['category_id'],
                    'amount' => (float)$split['amount'],
                    'description' => $split['description'] ?? null
                ]);
            }

            $this->db->commit();
            echo json_encode(['success' => true, 'message' => 'Transaction split created successfully']);

        } catch (\Exception $e) {
            $this->db->rollback();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create splits: ' . $e->getMessage()]);
        }
    }

    public function updateSplit(array $params = []): void {
        $transactionId = $params['id'] ?? 0;
        $userId = $this->getUserId();

        // Verify transaction belongs to user
        $transaction = $this->db->queryOne(
            "SELECT t.* FROM transactions t
             JOIN accounts a ON t.account_id = a.id
             WHERE t.id = ? AND a.user_id = ?",
            [$transactionId, $userId]
        );

        if (!$transaction) {
            http_response_code(404);
            echo json_encode(['error' => 'Transaction not found']);
            return;
        }

        $splits = $_POST['splits'] ?? [];
        if (empty($splits)) {
            http_response_code(400);
            echo json_encode(['error' => 'No splits provided']);
            return;
        }

        // Validate splits
        $totalSplitAmount = 0;
        foreach ($splits as $split) {
            if (!isset($split['category_id']) || !isset($split['amount'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid split data']);
                return;
            }
            $totalSplitAmount += (float)$split['amount'];
        }

        // Check if splits sum to original transaction amount
        if (abs($totalSplitAmount - (float)$transaction['amount']) > 0.01) {
            http_response_code(400);
            echo json_encode(['error' => 'Split amounts must equal original transaction amount']);
            return;
        }

        // Begin transaction
        $this->db->beginTransaction();

        try {
            // Delete existing splits
            $this->db->delete('transaction_splits', ['parent_transaction_id' => $transactionId]);

            // Insert updated splits
            foreach ($splits as $split) {
                $this->db->insert('transaction_splits', [
                    'parent_transaction_id' => $transactionId,
                    'category_id' => (int)$split['category_id'],
                    'amount' => (float)$split['amount'],
                    'description' => $split['description'] ?? null
                ]);
            }

            $this->db->commit();
            echo json_encode(['success' => true, 'message' => 'Transaction split updated successfully']);

        } catch (\Exception $e) {
            $this->db->rollback();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update splits: ' . $e->getMessage()]);
        }
    }

    public function deleteSplit(array $params = []): void {
        $transactionId = $params['id'] ?? 0;
        $userId = $this->getUserId();

        // Verify transaction belongs to user
        $transaction = $this->db->queryOne(
            "SELECT t.* FROM transactions t
             JOIN accounts a ON t.account_id = a.id
             WHERE t.id = ? AND a.user_id = ?",
            [$transactionId, $userId]
        );

        if (!$transaction) {
            http_response_code(404);
            echo json_encode(['error' => 'Transaction not found']);
            return;
        }

        try {
            $this->db->delete('transaction_splits', ['parent_transaction_id' => $transactionId]);
            echo json_encode(['success' => true, 'message' => 'Transaction splits deleted successfully']);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete splits: ' . $e->getMessage()]);
        }
    }

    public function getSplits(array $params = []): void {
        $transactionId = $params['id'] ?? 0;
        $userId = $this->getUserId();

        // Verify transaction belongs to user
        $transaction = $this->db->queryOne(
            "SELECT t.* FROM transactions t
             JOIN accounts a ON t.account_id = a.id
             WHERE t.id = ? AND a.user_id = ?",
            [$transactionId, $userId]
        );

        if (!$transaction) {
            http_response_code(404);
            echo json_encode(['error' => 'Transaction not found']);
            return;
        }

        $splits = $this->db->query(
            "SELECT ts.*, c.name as category_name, c.color as category_color
             FROM transaction_splits ts
             JOIN categories c ON ts.category_id = c.id
             WHERE ts.parent_transaction_id = ?
             ORDER BY ts.created_at",
            [$transactionId]
        );

        echo json_encode([
            'transaction' => $transaction,
            'splits' => $splits
        ]);
    }

    /**
     * Detect recurring transaction patterns
     */
    public function detectRecurring(array $params = []): void {
        $userId = $this->getUserId();

        $minOccurrences = (int)($this->getQueryParam('min_occurrences', 3));
        $lookbackDays = (int)($this->getQueryParam('lookback_days', 365));

        $recurringService = new RecurringTransactionService($this->db);
        $patterns = $recurringService->detectRecurring($userId, $minOccurrences, $lookbackDays);

        echo json_encode([
            'success' => true,
            'patterns' => $patterns,
            'count' => count($patterns)
        ]);
    }

    /**
     * Create a recurring transaction from detected pattern
     */
    public function createRecurring(array $params = []): void {
        $userId = $this->getUserId();

        $data = [
            'description' => $_POST['description'] ?? '',
            'amount' => (float)($_POST['amount'] ?? 0),
            'frequency' => $_POST['frequency'] ?? '',
            'account_id' => (int)($_POST['account_id'] ?? 0),
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'type' => $_POST['type'] ?? 'expense',
            'next_due_date' => $_POST['next_due_date'] ?? null
        ];

        // Validate required fields
        if (empty($data['description']) || empty($data['frequency']) || $data['amount'] <= 0 || $data['account_id'] <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }

        // Verify account belongs to user
        $account = $this->db->queryOne(
            "SELECT id FROM accounts WHERE id = ? AND user_id = ?",
            [$data['account_id'], $userId]
        );
        if (!$account) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid account']);
            return;
        }

        try {
            $recurringService = new RecurringTransactionService($this->db);
            $id = $recurringService->createRecurringTransaction($userId, $data);

            echo json_encode([
                'success' => true,
                'id' => $id,
                'message' => 'Recurring transaction created successfully'
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get all recurring transactions for user
     */
    public function getRecurring(array $params = []): void {
        $userId = $this->getUserId();

        $recurringService = new RecurringTransactionService($this->db);
        $recurring = $recurringService->getRecurringTransactions($userId);

        echo json_encode([
            'success' => true,
            'recurring' => $recurring,
            'count' => count($recurring)
        ]);
    }

    /**
     * Update recurring transaction
     */
    public function updateRecurring(array $params = []): void {
        $userId = $this->getUserId();
        $id = (int)($params['id'] ?? 0);

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid recurring transaction ID']);
            return;
        }

        $data = [];
        $allowedFields = ['description', 'amount', 'frequency', 'account_id', 'category_id', 'next_due_date', 'is_active'];

        foreach ($allowedFields as $field) {
            if (isset($_POST[$field])) {
                $data[$field] = $_POST[$field];
            }
        }

        if (empty($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            return;
        }

        $recurringService = new RecurringTransactionService($this->db);
        $success = $recurringService->updateRecurringTransaction($userId, $id, $data);

        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Recurring transaction updated']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Recurring transaction not found']);
        }
    }

    /**
     * Delete recurring transaction
     */
    public function deleteRecurring(array $params = []): void {
        $userId = $this->getUserId();
        $id = (int)($params['id'] ?? 0);

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid recurring transaction ID']);
            return;
        }

        $recurringService = new RecurringTransactionService($this->db);
        $success = $recurringService->deleteRecurringTransaction($userId, $id);

        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Recurring transaction deleted']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Recurring transaction not found']);
        }
    }

    /**
     * Display recurring transactions management view
     */
    public function recurringView(array $params = []): void {
        $userId = $this->getUserId();

        // Get accounts and categories for form
        $accounts = $this->db->query(
            "SELECT id, name FROM accounts WHERE user_id = ? ORDER BY name",
            [$userId]
        );
        $categories = $this->db->query(
            "SELECT id, name FROM categories WHERE user_id = ? OR user_id IS NULL ORDER BY name",
            [$userId]
        );

        echo $this->app->render('transactions/recurring', [
            'accounts' => $accounts,
            'categories' => $categories
        ]);
    }

    /**
     * Display transaction splits management view
     */
    public function splitsView(array $params = []): void {
        $transactionId = $params['id'] ?? 0;
        $userId = $this->getUserId();

        // Get transaction details
        $transaction = $this->db->queryOne(
            "SELECT t.*, c.name as category_name, a.name as account_name
             FROM transactions t
             LEFT JOIN categories c ON t.category_id = c.id
             JOIN accounts a ON t.account_id = a.id
             WHERE t.id = ? AND a.user_id = ?",
            [$transactionId, $userId]
        );

        if (!$transaction) {
            http_response_code(404);
            echo $this->app->render('errors/404', [
                'message' => 'Transaction not found'
            ]);
            return;
        }

        // Get existing splits
        $splits = $this->db->query(
            "SELECT ts.*, c.name as category_name
             FROM transaction_splits ts
             JOIN categories c ON ts.category_id = c.id
             WHERE ts.parent_transaction_id = ?
             ORDER BY ts.created_at",
            [$transactionId]
        );

        // Get categories for split form
        $categories = $this->db->query(
            "SELECT id, name FROM categories WHERE user_id = ? OR user_id IS NULL ORDER BY name",
            [$userId]
        );

        echo $this->app->render('transactions/splits', [
            'transaction' => $transaction,
            'splits' => $splits,
            'categories' => $categories
        ]);
    }
}
