<?php
namespace BudgetApp\Services;

use BudgetApp\Database;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ExcelExporter {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Export transactions to Excel with filtering support
     */
    public function exportTransactions(
        int $userId,
        array $filters = [],
        bool $streamOutput = true
    ): void {
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

        // Create new spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Transactions');

        // Set headers
        $headers = ['Date', 'Account', 'Category', 'Type', 'Description', 'Amount', 'Merchant'];
        foreach (range('A', 'G') as $index => $column) {
            $sheet->setCellValue($column . '1', $headers[$index]);
        }

        // Style headers
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E3F2FD']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'BDBDBD']
                ]
            ]
        ];
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

        // Add data
        $row = 2;
        foreach ($transactions as $transaction) {
            $sheet->setCellValue('A' . $row, $transaction['date']);
            $sheet->setCellValue('B' . $row, $transaction['account_name']);
            $sheet->setCellValue('C' . $row, $transaction['category_name'] ?? '');
            $sheet->setCellValue('D' . $row, ucfirst($transaction['type']));
            $sheet->setCellValue('E' . $row, $transaction['description']);
            $sheet->setCellValue('F' . $row, $transaction['amount']);
            $sheet->setCellValue('G' . $row, $transaction['merchant'] ?? '');

            // Format amount column
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD);

            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Add borders to data
        if ($row > 2) {
            $dataRange = 'A2:G' . ($row - 1);
            $sheet->getStyle($dataRange)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E0E0E0']
                    ]
                ]
            ]);
        }

        $this->outputExcel($spreadsheet, 'transactions_' . date('Y-m-d_H-i-s') . '.xlsx', $streamOutput);
    }

    /**
     * Export reports data to Excel with multiple worksheets
     */
    public function exportReports(
        int $userId,
        string $reportType,
        array $params = [],
        bool $streamOutput = true
    ): void {
        $spreadsheet = new Spreadsheet();

        switch ($reportType) {
            case 'monthly':
                $this->createMonthlyReportSheets($spreadsheet, $userId, $params['month'] ?? date('Y-m'));
                break;
            case 'monthly-report':
                $this->createMonthlyRangeReportSheets($spreadsheet, $userId, $params);
                break;
            case 'yearly':
                $this->createYearlyReportSheets($spreadsheet, $userId, $params['year'] ?? date('Y'));
                break;
            case 'categories':
                $this->createCategoriesReportSheet($spreadsheet, $userId, $params);
                break;
            case 'accounts':
                $this->createAccountsReportSheet($spreadsheet, $userId);
                break;
            default:
                throw new \Exception("Unknown report type: {$reportType}");
        }

        $filename = $reportType . '_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        $this->outputExcel($spreadsheet, $filename, $streamOutput);
    }

    /**
     * Create monthly report with multiple worksheets
     */
    private function createMonthlyReportSheets(Spreadsheet $spreadsheet, int $userId, string $month): void {
        $analyzer = new FinancialAnalyzer($this->db);
        $summary = $analyzer->getMonthSummary($userId, $month);

        // Calculate month start and end dates for category methods
        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        $expensesByCategory = $analyzer->getExpensesByCategory($userId, $monthStart, $monthEnd);
        $incomeBySource = $analyzer->getIncomeBySource($userId, $monthStart, $monthEnd);

        // Summary sheet
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Summary');
        $this->populateSummarySheet($summarySheet, $summary, $month);

        // Expenses by Category sheet
        $expensesSheet = $spreadsheet->createSheet();
        $expensesSheet->setTitle('Expenses by Category');
        $this->populateCategorySheet($expensesSheet, $expensesByCategory, 'Expenses by Category - ' . $month);

        // Income by Source sheet
        $incomeSheet = $spreadsheet->createSheet();
        $incomeSheet->setTitle('Income by Source');
        $this->populateCategorySheet($incomeSheet, $incomeBySource, 'Income by Source - ' . $month);
    }

    /**
     * Create monthly range report with multiple worksheets
     */
    private function createMonthlyRangeReportSheets(Spreadsheet $spreadsheet, int $userId, array $params): void {
        $startDate = $params['start_date'] ?? date('Y-m-01', strtotime('-3 months'));
        $endDate = $params['end_date'] ?? date('Y-m-t');

        // Get monthly aggregations
        $monthlyData = $this->getMonthlyAggregations($userId, $startDate, $endDate);
        $categoryBreakdowns = $this->getCategoryBreakdownsByMonth($userId, $startDate, $endDate);

        // Monthly Summary sheet
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Monthly Summary');
        $this->populateMonthlySummarySheet($summarySheet, $monthlyData);

        // Category Breakdowns sheet
        $categorySheet = $spreadsheet->createSheet();
        $categorySheet->setTitle('Category Breakdowns');
        $this->populateCategoryBreakdownsSheet($categorySheet, $categoryBreakdowns);
    }

    /**
     * Create yearly report with multiple worksheets
     */
    private function createYearlyReportSheets(Spreadsheet $spreadsheet, int $userId, string $year): void {
        // Monthly breakdown sheet
        $monthlySheet = $spreadsheet->getActiveSheet();
        $monthlySheet->setTitle('Monthly Breakdown');

        $monthlySheet->setCellValue('A1', 'Yearly Report - ' . $year);
        $monthlySheet->mergeCells('A1:E1');
        $monthlySheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Headers
        $headers = ['Month', 'Total Income', 'Total Expenses', 'Net Income', 'Savings Rate (%)'];
        foreach (range('A', 'E') as $index => $column) {
            $monthlySheet->setCellValue($column . '3', $headers[$index]);
        }

        // Style headers
        $monthlySheet->getStyle('A3:E3')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']]
        ]);

        $row = 4;
        for ($m = 1; $m <= 12; $m++) {
            $month = sprintf('%04d-%02d', $year, $m);
            $analyzer = new FinancialAnalyzer($this->db);
            $summary = $analyzer->getMonthSummary($userId, $month);

            $netIncome = $summary['total_income'] - $summary['total_expenses'];
            $savingsRate = $summary['total_income'] > 0 ? ($netIncome / $summary['total_income']) * 100 : 0;

            $monthlySheet->setCellValue('A' . $row, $month);
            $monthlySheet->setCellValue('B' . $row, $summary['total_income']);
            $monthlySheet->setCellValue('C' . $row, $summary['total_expenses']);
            $monthlySheet->setCellValue('D' . $row, $netIncome);
            $monthlySheet->setCellValue('E' . $row, round($savingsRate, 2));

            // Format currency columns
            $monthlySheet->getStyle('B' . $row . ':D' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD);

            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'E') as $column) {
            $monthlySheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * Create categories report sheet
     */
    private function createCategoriesReportSheet(Spreadsheet $spreadsheet, int $userId, array $params = []): void {
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

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Categories Report');

        // Headers
        $headers = ['Category Name', 'Description', 'Total Expenses', 'Total Income', 'Net Amount', 'Transaction Count'];
        foreach (range('A', 'F') as $index => $column) {
            $sheet->setCellValue($column . '1', $headers[$index]);
        }

        // Style headers
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']]
        ]);

        $row = 2;
        foreach ($categories as $category) {
            $netAmount = $category['total_income'] - $category['total_expenses'];

            $sheet->setCellValue('A' . $row, $category['name']);
            $sheet->setCellValue('B' . $row, $category['description'] ?? '');
            $sheet->setCellValue('C' . $row, $category['total_expenses']);
            $sheet->setCellValue('D' . $row, $category['total_income']);
            $sheet->setCellValue('E' . $row, $netAmount);
            $sheet->setCellValue('F' . $row, $category['transaction_count']);

            // Format currency columns
            $sheet->getStyle('C' . $row . ':E' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD);

            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * Create accounts report sheet
     */
    private function createAccountsReportSheet(Spreadsheet $spreadsheet, int $userId): void {
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

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Accounts Report');

        // Headers
        $headers = ['Account Name', 'Type', 'Balance', 'Currency', 'Total Income', 'Total Expenses', 'Net Flow', 'Transaction Count'];
        foreach (range('A', 'H') as $index => $column) {
            $sheet->setCellValue($column . '1', $headers[$index]);
        }

        // Style headers
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']]
        ]);

        $row = 2;
        foreach ($accounts as $account) {
            $netFlow = $account['total_income'] - $account['total_expenses'];

            $sheet->setCellValue('A' . $row, $account['name']);
            $sheet->setCellValue('B' . $row, $account['type']);
            $sheet->setCellValue('C' . $row, $account['balance']);
            $sheet->setCellValue('D' . $row, $account['currency']);
            $sheet->setCellValue('E' . $row, $account['total_income']);
            $sheet->setCellValue('F' . $row, $account['total_expenses']);
            $sheet->setCellValue('G' . $row, $netFlow);
            $sheet->setCellValue('H' . $row, $account['transaction_count']);

            // Format currency columns
            $sheet->getStyle('C' . $row . ':G' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD);

            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * Helper methods for populating sheets
     */
    private function populateSummarySheet($sheet, array $summary, string $month): void {
        $sheet->setCellValue('A1', 'Monthly Summary - ' . $month);
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $data = [
            ['Total Income', $summary['total_income']],
            ['Total Expenses', $summary['total_expenses']],
            ['Net Income', $summary['net_income']]
        ];

        $row = 3;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $item[0]);
            $sheet->setCellValue('B' . $row, $item[1]);
            $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD);
            $row++;
        }

        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
    }

    private function populateCategorySheet($sheet, array $data, string $title): void {
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A1:C1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Headers
        $sheet->setCellValue('A3', 'Category');
        $sheet->setCellValue('B3', 'Amount');
        $sheet->setCellValue('C3', 'Percentage');

        $sheet->getStyle('A3:C3')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']]
        ]);

        $total = array_sum(array_column($data, 'amount'));
        $row = 4;
        foreach ($data as $item) {
            $percentage = $total > 0 ? ($item['amount'] / $total) * 100 : 0;

            $sheet->setCellValue('A' . $row, $item['name']);
            $sheet->setCellValue('B' . $row, $item['amount']);
            $sheet->setCellValue('C' . $row, round($percentage, 2) . '%');

            $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD);

            $row++;
        }

        foreach (range('A', 'C') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    private function populateMonthlySummarySheet($sheet, array $monthlyData): void {
        $sheet->setCellValue('A1', 'Monthly Summary Report');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Headers
        $headers = ['Month', 'Total Income', 'Total Expenses', 'Net Income', 'Savings Rate (%)', 'Transaction Count'];
        foreach (range('A', 'F') as $index => $column) {
            $sheet->setCellValue($column . '3', $headers[$index]);
        }

        $sheet->getStyle('A3:F3')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']]
        ]);

        $row = 4;
        foreach ($monthlyData as $month) {
            $sheet->setCellValue('A' . $row, $month['month']);
            $sheet->setCellValue('B' . $row, $month['total_income']);
            $sheet->setCellValue('C' . $row, $month['total_expenses']);
            $sheet->setCellValue('D' . $row, $month['net_income']);
            $sheet->setCellValue('E' . $row, $month['savings_rate']);
            $sheet->setCellValue('F' . $row, $month['transaction_count']);

            $sheet->getStyle('B' . $row . ':D' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD);

            $row++;
        }

        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    private function populateCategoryBreakdownsSheet($sheet, array $categoryBreakdowns): void {
        $sheet->setCellValue('A1', 'Category Breakdowns by Month');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Headers
        $headers = ['Month', 'Category', 'Expenses', 'Income', 'Net Amount'];
        foreach (range('A', 'E') as $index => $column) {
            $sheet->setCellValue($column . '3', $headers[$index]);
        }

        $sheet->getStyle('A3:E3')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']]
        ]);

        $row = 4;
        foreach ($categoryBreakdowns as $month => $categories) {
            foreach ($categories as $category) {
                $sheet->setCellValue('A' . $row, $month);
                $sheet->setCellValue('B' . $row, $category['category_name']);
                $sheet->setCellValue('C' . $row, $category['expenses']);
                $sheet->setCellValue('D' . $row, $category['income']);
                $sheet->setCellValue('E' . $row, $category['net']);

                $sheet->getStyle('C' . $row . ':E' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD);

                $row++;
            }
        }

        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * Get monthly aggregations for date range
     */
    private function getMonthlyAggregations(int $userId, string $startDate, string $endDate): array {
        $query = "SELECT
                    SUBSTR(date, 1, 7) as month,
                    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN type = 'expense' THEN ABS(amount) ELSE 0 END) as total_expenses,
                    COUNT(*) as transaction_count
                  FROM transactions t
                  JOIN accounts a ON t.account_id = a.id
                  WHERE a.user_id = ? AND t.date BETWEEN ? AND ?
                  GROUP BY SUBSTR(date, 1, 7)
                  ORDER BY month ASC";

        $results = $this->db->query($query, [$userId, $startDate, $endDate]);

        // Calculate net income and savings rate for each month
        foreach ($results as &$month) {
            $month['net_income'] = $month['total_income'] - $month['total_expenses'];
            $month['savings_rate'] = $month['total_income'] > 0
                ? round(($month['net_income'] / $month['total_income']) * 100, 2)
                : 0;
        }

        return $results;
    }

    /**
     * Get category breakdowns for each month in the date range
     */
    private function getCategoryBreakdownsByMonth(int $userId, string $startDate, string $endDate): array {
        $query = "SELECT
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

        $results = $this->db->query($query, [$userId, $startDate, $endDate]);

        // Group by month
        $breakdowns = [];
        foreach ($results as $row) {
            $month = $row['month'];
            if (!isset($breakdowns[$month])) {
                $breakdowns[$month] = [];
            }

            if ($row['category_name']) { // Only include categorized transactions
                $breakdowns[$month][] = [
                    'category_name' => $row['category_name'],
                    'expenses' => $row['expenses'],
                    'income' => $row['income'],
                    'net' => $row['income'] - $row['expenses'],
                    'transaction_count' => $row['transaction_count']
                ];
            }
        }

        return $breakdowns;
    }

    /**
     * Output Excel file
     */
    private function outputExcel(Spreadsheet $spreadsheet, string $filename, bool $streamOutput = true): void {
        if ($streamOutput) {
            // Set headers for download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        } else {
            // Save to file (for testing)
            $writer = new Xlsx($spreadsheet);
            $writer->save($filename);
        }
    }
}