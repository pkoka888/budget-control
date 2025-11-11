<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

class CsvExporter {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Export transactions to CSV with filtering support
     */
    public function exportTransactions(
        int $userId,
        array $filters = [],
        bool $streamOutput = true
    ): ?string {
        // Build query with same filtering logic as TransactionController
        $query = "SELECT t.*, c.name as category_name, a.name as account_name
                  FROM transactions t
                  LEFT JOIN categories c ON t.category_id = c.id
                  JOIN accounts a ON t.account_id = a.id
                  WHERE a.user_id = ?";
        $queryParams = [$userId];

        // Apply filters
        if (!empty($filters['category'])) {
            $categoryIds = $filters['category'];
            if (is_array($categoryIds)) {
                $placeholders = str_repeat('?,', count($categoryIds) - 1) . '?';
                $query .= " AND t.category_id IN ($placeholders)";
                $queryParams = array_merge($queryParams, $categoryIds);
            } else {
                $query .= " AND t.category_id = ?";
                $queryParams[] = $categoryIds;
            }
        }

        if (!empty($filters['account'])) {
            $query .= " AND t.account_id = ?";
            $queryParams[] = $filters['account'];
        }

        if (!empty($filters['type'])) {
            $query .= " AND t.type = ?";
            $queryParams[] = $filters['type'];
        }

        if (!empty($filters['start_date'])) {
            $query .= " AND t.date >= ?";
            $queryParams[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $query .= " AND t.date <= ?";
            $queryParams[] = $filters['end_date'];
        }

        if (isset($filters['min_amount']) && $filters['min_amount'] !== '') {
            $query .= " AND t.amount >= ?";
            $queryParams[] = (float)$filters['min_amount'];
        }

        if (isset($filters['max_amount']) && $filters['max_amount'] !== '') {
            $query .= " AND t.amount <= ?";
            $queryParams[] = (float)$filters['max_amount'];
        }

        if (!empty($filters['search'])) {
            $query .= " AND (t.description LIKE ? OR t.merchant LIKE ?)";
            $queryParams[] = '%' . $filters['search'] . '%';
            $queryParams[] = '%' . $filters['search'] . '%';
        }

        $query .= " ORDER BY t.date DESC";

        $transactions = $this->db->query($query, $queryParams);

        // Set headers for CSV download
        $filename = 'transactions_' . date('Y-m-d_H-i-s') . '.csv';

        if ($streamOutput) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w');
        } else {
            $output = fopen('php://temp', 'w+');
        }

        // Write CSV headers
        fputcsv($output, [
            'Date',
            'Account',
            'Category',
            'Type',
            'Description',
            'Amount',
            'Merchant'
        ]);

        // Write transaction data
        foreach ($transactions as $transaction) {
            fputcsv($output, [
                $transaction['date'],
                $transaction['account_name'],
                $transaction['category_name'] ?? '',
                ucfirst($transaction['type']),
                $transaction['description'],
                number_format($transaction['amount'], 2, '.', ''),
                $transaction['merchant'] ?? ''
            ]);
        }

        if (!$streamOutput) {
            rewind($output);
            $csvContent = stream_get_contents($output);
            fclose($output);
            return is_string($csvContent) ? $csvContent : '';
        }

        fclose($output);
        exit;
    }

    /**
     * Export reports data to CSV
     */
    public function exportReports(
        int $userId,
        string $reportType,
        array $params = [],
        bool $streamOutput = true
    ): ?string {
        $data = [];

        switch ($reportType) {
            case 'monthly':
                $data = $this->getMonthlyReportData($userId, $params['month'] ?? date('Y-m'));
                break;
            case 'monthly-report':
                $data = $this->getMonthlyReportRangeData($userId, $params);
                break;
            case 'yearly':
                $data = $this->getYearlyReportData($userId, $params['year'] ?? date('Y'));
                break;
            case 'categories':
                $data = $this->getCategoriesReportData($userId, $params);
                break;
            case 'accounts':
                $data = $this->getAccountsReportData($userId);
                break;
            default:
                throw new \Exception("Unknown report type: {$reportType}");
        }

        // Set headers for CSV download
        $filename = $reportType . '_report_' . date('Y-m-d_H-i-s') . '.csv';

        if ($streamOutput) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w');
        } else {
            $output = fopen('php://temp', 'w+');
        }

        // Write headers based on data structure
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
        }

        // Write data rows
        foreach ($data as $row) {
            fputcsv($output, array_values($row));
        }

        if (!$streamOutput) {
            rewind($output);
            $csvContent = stream_get_contents($output);
            fclose($output);
            return is_string($csvContent) ? $csvContent : '';
        }

        fclose($output);
        exit;
    }

    /**
     * Get monthly report data
     */
    private function getMonthlyReportData(int $userId, string $month): array {
        $analyzer = new FinancialAnalyzer($this->db);
        $summary = $analyzer->getMonthSummary($userId, $month);

        // Calculate month start and end dates for category methods
        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        $expensesByCategory = $analyzer->getExpensesByCategory($userId, $monthStart, $monthEnd);
        $incomeBySource = $analyzer->getIncomeBySource($userId, $monthStart, $monthEnd);

        $data = [];

        // Summary row
        $data[] = [
            'Type' => 'Summary',
            'Category' => 'Total Income',
            'Amount' => number_format($summary['total_income'], 2, '.', ''),
            'Month' => $month
        ];
        $data[] = [
            'Type' => 'Summary',
            'Category' => 'Total Expenses',
            'Amount' => number_format($summary['total_expenses'], 2, '.', ''),
            'Month' => $month
        ];
        $data[] = [
            'Type' => 'Summary',
            'Category' => 'Net Income',
            'Amount' => number_format($summary['net_income'], 2, '.', ''),
            'Month' => $month
        ];

        // Expenses by category
        foreach ($expensesByCategory as $category) {
            $data[] = [
                'Type' => 'Expenses by Category',
                'Category' => $category['name'],
                'Amount' => number_format($category['amount'], 2, '.', ''),
                'Month' => $month
            ];
        }

        // Income by source
        foreach ($incomeBySource as $source) {
            $data[] = [
                'Type' => 'Income by Source',
                'Category' => $source['name'],
                'Amount' => number_format($source['amount'], 2, '.', ''),
                'Month' => $month
            ];
        }

        return $data;
    }

    /**
     * Get yearly report data
     */
    private function getYearlyReportData(int $userId, string $year): array {
        $data = [];

        for ($m = 1; $m <= 12; $m++) {
            $month = sprintf('%04d-%02d', $year, $m);
            $analyzer = new FinancialAnalyzer($this->db);
            $summary = $analyzer->getMonthSummary($userId, $month);

            $data[] = [
                'Month' => $month,
                'Total Income' => number_format($summary['total_income'], 2, '.', ''),
                'Total Expenses' => number_format($summary['total_expenses'], 2, '.', ''),
                'Net Income' => number_format($summary['net_income'], 2, '.', '')
            ];
        }

        return $data;
    }

    /**
     * Get categories report data
     */
    private function getCategoriesReportData(int $userId, array $params = []): array {
        $query = "SELECT c.name, c.description,
                         SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END) as total_expenses,
                         SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END) as total_income,
                         COUNT(t.id) as transaction_count
                  FROM categories c
                  LEFT JOIN transactions t ON c.id = t.category_id
                  LEFT JOIN accounts a ON t.account_id = a.id
                  WHERE c.user_id = ? OR c.user_id IS NULL";

        $queryParams = [$userId];

        if (!empty($params['start_date'])) {
            $query .= " AND t.date >= ?";
            $queryParams[] = $params['start_date'];
        }

        if (!empty($params['end_date'])) {
            $query .= " AND t.date <= ?";
            $queryParams[] = $params['end_date'];
        }

        $query .= " GROUP BY c.id, c.name, c.description ORDER BY c.name";

        $categories = $this->db->query($query, $queryParams);

        return array_map(function($category) {
            return [
                'Category Name' => $category['name'],
                'Description' => $category['description'] ?? '',
                'Total Expenses' => number_format($category['total_expenses'], 2, '.', ''),
                'Total Income' => number_format($category['total_income'], 2, '.', ''),
                'Transaction Count' => $category['transaction_count']
            ];
        }, $categories);
    }

    /**
     * Get monthly report data for date range
     */
    private function getMonthlyReportRangeData(int $userId, array $params = []): array {
        $startDate = $params['start_date'] ?? date('Y-m-01', strtotime('-3 months'));
        $endDate = $params['end_date'] ?? date('Y-m-t');

        $data = [];

        // Get monthly aggregations
        $monthlyQuery = "SELECT
                        SUBSTR(date, 1, 7) as month,
                        SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                        SUM(CASE WHEN type = 'expense' THEN ABS(amount) ELSE 0 END) as total_expenses,
                        COUNT(*) as transaction_count
                      FROM transactions t
                      JOIN accounts a ON t.account_id = a.id
                      WHERE a.user_id = ? AND t.date BETWEEN ? AND ?
                      GROUP BY SUBSTR(date, 1, 7)
                      ORDER BY month ASC";

        $monthlyData = $this->db->query($monthlyQuery, [$userId, $startDate, $endDate]);

        // Add monthly summary rows
        foreach ($monthlyData as $month) {
            $netIncome = $month['total_income'] - $month['total_expenses'];
            $savingsRate = $month['total_income'] > 0 ? ($netIncome / $month['total_income']) * 100 : 0;

            $data[] = [
                'Type' => 'Monthly Summary',
                'Month' => $month['month'],
                'Category' => 'Total Income',
                'Amount' => number_format($month['total_income'], 2, '.', ''),
                'Transaction Count' => $month['transaction_count']
            ];
            $data[] = [
                'Type' => 'Monthly Summary',
                'Month' => $month['month'],
                'Category' => 'Total Expenses',
                'Amount' => number_format($month['total_expenses'], 2, '.', ''),
                'Transaction Count' => $month['transaction_count']
            ];
            $data[] = [
                'Type' => 'Monthly Summary',
                'Month' => $month['month'],
                'Category' => 'Net Income',
                'Amount' => number_format($netIncome, 2, '.', ''),
                'Transaction Count' => $month['transaction_count']
            ];
            $data[] = [
                'Type' => 'Monthly Summary',
                'Month' => $month['month'],
                'Category' => 'Savings Rate',
                'Amount' => number_format($savingsRate, 2, '.', ''),
                'Transaction Count' => $month['transaction_count']
            ];
        }

        // Get category breakdowns
        $categoryQuery = "SELECT
                        SUBSTR(t.date, 1, 7) as month,
                        c.name as category_name,
                        SUM(CASE WHEN t.type = 'expense' THEN ABS(t.amount) ELSE 0 END) as expenses,
                        SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END) as income,
                        COUNT(t.id) as transaction_count
                      FROM transactions t
                      LEFT JOIN categories c ON t.category_id = c.id
                      JOIN accounts a ON t.account_id = a.id
                      WHERE a.user_id = ? AND t.date BETWEEN ? AND ?
                      GROUP BY SUBSTR(t.date, 1, 7), c.id, c.name
                      ORDER BY month ASC, expenses DESC";

        $categoryData = $this->db->query($categoryQuery, [$userId, $startDate, $endDate]);

        // Add category breakdown rows
        foreach ($categoryData as $category) {
            if ($category['category_name']) {
                $data[] = [
                    'Type' => 'Category Breakdown',
                    'Month' => $category['month'],
                    'Category' => $category['category_name'],
                    'Amount' => number_format($category['expenses'] + $category['income'], 2, '.', ''),
                    'Transaction Count' => $category['transaction_count']
                ];
            }
        }

        return $data;
    }

    /**
     * Get accounts report data
     */
    private function getAccountsReportData(int $userId): array {
        $accounts = $this->db->query(
            "SELECT a.name, a.type, a.balance, a.currency,
                    SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END) as total_expenses,
                    COUNT(t.id) as transaction_count
             FROM accounts a
             LEFT JOIN transactions t ON a.id = t.account_id
             WHERE a.user_id = ?
             GROUP BY a.id, a.name, a.type, a.balance, a.currency
             ORDER BY a.name",
            [$userId]
        );

        return array_map(function($account) {
            return [
                'Account Name' => $account['name'],
                'Type' => $account['type'],
                'Balance' => number_format($account['balance'], 2, '.', ''),
                'Currency' => $account['currency'],
                'Total Income' => number_format($account['total_income'], 2, '.', ''),
                'Total Expenses' => number_format($account['total_expenses'], 2, '.', ''),
                'Transaction Count' => $account['transaction_count']
            ];
        }, $accounts);
    }
}