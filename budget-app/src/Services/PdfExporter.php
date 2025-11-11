<?php
namespace BudgetApp\Services;

use BudgetApp\Database;
use TCPDF;

class PdfExporter {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Export transactions to PDF with filtering support
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

        // Create PDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('Budget Control App');
        $pdf->SetAuthor('Budget Control');
        $pdf->SetTitle('Transaction Report');
        $pdf->SetSubject('Filtered Transaction Export');

        // Set margins
        $pdf->SetMargins(15, 25, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);

        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 15);

        // Add a page
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', '', 10);

        // Title
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Transaction Report', 0, 1, 'C');
        $pdf->Ln(5);

        // Filters summary
        $pdf->SetFont('helvetica', '', 9);
        $filterText = 'Generated on: ' . date('Y-m-d H:i:s');
        if (!empty($filters['start_date']) || !empty($filters['end_date'])) {
            $filterText .= ' | Date range: ' . ($filters['start_date'] ?? 'All') . ' to ' . ($filters['end_date'] ?? 'All');
        }
        if (!empty($filters['type'])) {
            $filterText .= ' | Type: ' . ucfirst($filters['type']);
        }
        $pdf->Cell(0, 6, $filterText, 0, 1);
        $pdf->Ln(5);

        // Table headers
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);

        $headerWidths = [25, 30, 35, 20, 50, 25];
        $headers = ['Date', 'Account', 'Category', 'Type', 'Description', 'Amount'];

        foreach ($headers as $i => $header) {
            $pdf->Cell($headerWidths[$i], 8, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();

        // Table data
        $pdf->SetFont('helvetica', '', 8);
        $totalAmount = 0;

        foreach ($transactions as $transaction) {
            $amount = (float)$transaction['amount'];
            $totalAmount += $amount;

            $pdf->Cell($headerWidths[0], 6, $transaction['date'], 1, 0, 'L');
            $pdf->Cell($headerWidths[1], 6, $transaction['account_name'], 1, 0, 'L');
            $pdf->Cell($headerWidths[2], 6, $transaction['category_name'] ?? '', 1, 0, 'L');
            $pdf->Cell($headerWidths[3], 6, ucfirst($transaction['type']), 1, 0, 'C');
            $pdf->Cell($headerWidths[4], 6, substr($transaction['description'], 0, 30), 1, 0, 'L');
            $pdf->Cell($headerWidths[5], 6, number_format($amount, 2), 1, 0, 'R');
            $pdf->Ln();
        }

        // Total row
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(250, 250, 250);
        $pdf->Cell(array_sum($headerWidths) - $headerWidths[5], 8, 'TOTAL', 1, 0, 'R', true);
        $pdf->Cell($headerWidths[5], 8, number_format($totalAmount, 2), 1, 0, 'R', true);
        $pdf->Ln();

        // Summary statistics
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 8, 'Summary Statistics', 0, 1);
        $pdf->SetFont('helvetica', '', 9);

        $incomeCount = count(array_filter($transactions, fn($t) => $t['type'] === 'income'));
        $expenseCount = count(array_filter($transactions, fn($t) => $t['type'] === 'expense'));
        $incomeTotal = array_sum(array_column(array_filter($transactions, fn($t) => $t['type'] === 'income'), 'amount'));
        $expenseTotal = array_sum(array_column(array_filter($transactions, fn($t) => $t['type'] === 'expense'), 'amount'));

        $pdf->Cell(50, 6, "Total Transactions: " . count($transactions), 0, 1);
        $pdf->Cell(50, 6, "Income Transactions: $incomeCount", 0, 1);
        $pdf->Cell(50, 6, "Expense Transactions: $expenseCount", 0, 1);
        $pdf->Cell(50, 6, "Total Income: " . number_format($incomeTotal, 2), 0, 1);
        $pdf->Cell(50, 6, "Total Expenses: " . number_format($expenseTotal, 2), 0, 1);
        $pdf->Cell(50, 6, "Net Amount: " . number_format($incomeTotal - $expenseTotal, 2), 0, 1);

        $this->outputPdf($pdf, 'transactions_' . date('Y-m-d_H-i-s') . '.pdf', $streamOutput);
    }

    /**
     * Export reports data to PDF with charts
     */
    public function exportReports(
        int $userId,
        string $reportType,
        array $params = [],
        bool $streamOutput = true
    ): void {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('Budget Control App');
        $pdf->SetAuthor('Budget Control');
        $pdf->SetTitle(ucfirst($reportType) . ' Report');
        $pdf->SetSubject('Financial Report Export');

        // Set margins
        $pdf->SetMargins(15, 25, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(TRUE, 15);

        $pdf->AddPage();

        switch ($reportType) {
            case 'monthly':
                $this->createMonthlyReportPdf($pdf, $userId, $params['month'] ?? date('Y-m'));
                break;
            case 'monthly-report':
                $this->createMonthlyRangeReportPdf($pdf, $userId, $params);
                break;
            case 'yearly':
                $this->createYearlyReportPdf($pdf, $userId, $params['year'] ?? date('Y'));
                break;
            case 'categories':
                $this->createCategoriesReportPdf($pdf, $userId, $params);
                break;
            case 'accounts':
                $this->createAccountsReportPdf($pdf, $userId);
                break;
            default:
                throw new \Exception("Unknown report type: {$reportType}");
        }

        $filename = $reportType . '_report_' . date('Y-m-d_H-i-s') . '.pdf';
        $this->outputPdf($pdf, $filename, $streamOutput);
    }

    /**
     * Create monthly report PDF
     */
    private function createMonthlyReportPdf(TCPDF $pdf, int $userId, string $month): void {
        $analyzer = new FinancialAnalyzer($this->db);
        $summary = $analyzer->getMonthSummary($userId, $month);

        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        $expensesByCategory = $analyzer->getExpensesByCategory($userId, $monthStart, $monthEnd);
        $incomeBySource = $analyzer->getIncomeBySource($userId, $monthStart, $monthEnd);

        // Title
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell(0, 15, 'Monthly Financial Report', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 8, date('F Y', strtotime($month)), 0, 1, 'C');
        $pdf->Ln(10);

        // Summary section
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Financial Summary', 0, 1);
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetFillColor(240, 248, 255);

        $summaryData = [
            ['Total Income', number_format($summary['total_income'], 2)],
            ['Total Expenses', number_format($summary['total_expenses'], 2)],
            ['Net Income', number_format($summary['net_income'], 2)]
        ];

        foreach ($summaryData as $row) {
            $pdf->Cell(60, 8, $row[0], 1, 0, 'L', true);
            $pdf->Cell(40, 8, $row[1], 1, 0, 'R', true);
            $pdf->Ln();
        }

        $pdf->Ln(10);

        // Expenses by Category
        if (!empty($expensesByCategory)) {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Expenses by Category', 0, 1);
            $pdf->Ln(5);

            $this->addCategoryTable($pdf, $expensesByCategory, 'expenses');
            $this->addExpenseChart($pdf, $expensesByCategory);
        }

        // Income by Source
        if (!empty($incomeBySource)) {
            $pdf->Ln(15);
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Income by Source', 0, 1);
            $pdf->Ln(5);

            $this->addCategoryTable($pdf, $incomeBySource, 'income');
        }
    }

    /**
     * Create yearly report PDF
     */
    private function createYearlyReportPdf(TCPDF $pdf, int $userId, string $year): void {
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell(0, 15, 'Yearly Financial Report', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 8, $year, 0, 1, 'C');
        $pdf->Ln(10);

        // Monthly breakdown table
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Monthly Breakdown', 0, 1);
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);

        $headers = ['Month', 'Income', 'Expenses', 'Net Income', 'Savings Rate'];
        $widths = [25, 30, 30, 30, 25];

        foreach ($headers as $i => $header) {
            $pdf->Cell($widths[$i], 8, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetFont('helvetica', '', 8);
        $yearlyTotals = ['income' => 0, 'expenses' => 0, 'net' => 0];

        for ($m = 1; $m <= 12; $m++) {
            $month = sprintf('%04d-%02d', $year, $m);
            $analyzer = new FinancialAnalyzer($this->db);
            $summary = $analyzer->getMonthSummary($userId, $month);

            $netIncome = $summary['total_income'] - $summary['total_expenses'];
            $savingsRate = $summary['total_income'] > 0 ? ($netIncome / $summary['total_income']) * 100 : 0;

            $yearlyTotals['income'] += $summary['total_income'];
            $yearlyTotals['expenses'] += $summary['total_expenses'];
            $yearlyTotals['net'] += $netIncome;

            $pdf->Cell($widths[0], 6, date('M', strtotime($month)), 1, 0, 'C');
            $pdf->Cell($widths[1], 6, number_format($summary['total_income'], 0), 1, 0, 'R');
            $pdf->Cell($widths[2], 6, number_format($summary['total_expenses'], 0), 1, 0, 'R');
            $pdf->Cell($widths[3], 6, number_format($netIncome, 0), 1, 0, 'R');
            $pdf->Cell($widths[4], 6, number_format($savingsRate, 1) . '%', 1, 0, 'R');
            $pdf->Ln();
        }

        // Yearly totals
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(250, 250, 250);
        $pdf->Cell($widths[0], 8, 'TOTAL', 1, 0, 'C', true);
        $pdf->Cell($widths[1], 8, number_format($yearlyTotals['income'], 0), 1, 0, 'R', true);
        $pdf->Cell($widths[2], 8, number_format($yearlyTotals['expenses'], 0), 1, 0, 'R', true);
        $pdf->Cell($widths[3], 8, number_format($yearlyTotals['net'], 0), 1, 0, 'R', true);
        $pdf->Cell($widths[4], 8, number_format($yearlyTotals['income'] > 0 ? ($yearlyTotals['net'] / $yearlyTotals['income']) * 100 : 0, 1) . '%', 1, 0, 'R', true);
    }

    /**
     * Add category table to PDF
     */
    private function addCategoryTable(TCPDF $pdf, array $data, string $type): void {
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);

        $headers = ['Category', 'Amount', 'Percentage'];
        $widths = [80, 30, 25];

        foreach ($headers as $i => $header) {
            $pdf->Cell($widths[$i], 8, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetFont('helvetica', '', 8);
        $total = array_sum(array_column($data, 'amount'));

        foreach ($data as $item) {
            $percentage = $total > 0 ? ($item['amount'] / $total) * 100 : 0;

            $pdf->Cell($widths[0], 6, $item['name'], 1, 0, 'L');
            $pdf->Cell($widths[1], 6, number_format($item['amount'], 2), 1, 0, 'R');
            $pdf->Cell($widths[2], 6, number_format($percentage, 1) . '%', 1, 0, 'R');
            $pdf->Ln();
        }
    }

    /**
     * Add simple expense chart to PDF
     */
    private function addExpenseChart(TCPDF $pdf, array $expenses): void {
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Expense Distribution Chart', 0, 1);
        $pdf->Ln(5);

        // Simple bar chart representation
        $chartWidth = 150;
        $chartHeight = 80;
        $maxAmount = max(array_column($expenses, 'amount'));

        $pdf->SetFillColor(100, 149, 237); // Cornflower blue

        foreach (array_slice($expenses, 0, 8) as $expense) { // Top 8 categories
            $barWidth = $maxAmount > 0 ? ($expense['amount'] / $maxAmount) * $chartWidth : 0;

            $pdf->Cell(50, 6, substr($expense['name'], 0, 20), 0, 0, 'L');
            $pdf->Cell($barWidth, 6, '', 1, 0, 'L', true);
            $pdf->Cell(20, 6, number_format($expense['amount'], 0), 0, 0, 'R');
            $pdf->Ln();
        }
    }

    /**
     * Create categories report PDF
     */
    private function createCategoriesReportPdf(TCPDF $pdf, int $userId, array $params = []): void {
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

        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell(0, 15, 'Categories Report', 0, 1, 'C');
        $pdf->Ln(10);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(240, 240, 240);

        $headers = ['Category', 'Expenses', 'Income', 'Net', 'Transactions'];
        $widths = [60, 25, 25, 25, 20];

        foreach ($headers as $i => $header) {
            $pdf->Cell($widths[$i], 8, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetFont('helvetica', '', 9);

        foreach ($categories as $category) {
            $net = $category['total_income'] - $category['total_expenses'];

            $pdf->Cell($widths[0], 6, $category['name'], 1, 0, 'L');
            $pdf->Cell($widths[1], 6, number_format($category['total_expenses'], 2), 1, 0, 'R');
            $pdf->Cell($widths[2], 6, number_format($category['total_income'], 2), 1, 0, 'R');
            $pdf->Cell($widths[3], 6, number_format($net, 2), 1, 0, 'R');
            $pdf->Cell($widths[4], 6, $category['transaction_count'], 1, 0, 'C');
            $pdf->Ln();
        }
    }

    /**
     * Create accounts report PDF
     */
    private function createAccountsReportPdf(TCPDF $pdf, int $userId): void {
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

        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell(0, 15, 'Accounts Report', 0, 1, 'C');
        $pdf->Ln(10);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(240, 240, 240);

        $headers = ['Account', 'Type', 'Balance', 'Income', 'Expenses', 'Net Flow'];
        $widths = [50, 20, 25, 25, 25, 25];

        foreach ($headers as $i => $header) {
            $pdf->Cell($widths[$i], 8, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetFont('helvetica', '', 9);

        foreach ($accounts as $account) {
            $netFlow = $account['total_income'] - $account['total_expenses'];

            $pdf->Cell($widths[0], 6, $account['name'], 1, 0, 'L');
            $pdf->Cell($widths[1], 6, $account['type'], 1, 0, 'C');
            $pdf->Cell($widths[2], 6, number_format($account['balance'], 2), 1, 0, 'R');
            $pdf->Cell($widths[3], 6, number_format($account['total_income'], 2), 1, 0, 'R');
            $pdf->Cell($widths[4], 6, number_format($account['total_expenses'], 2), 1, 0, 'R');
            $pdf->Cell($widths[5], 6, number_format($netFlow, 2), 1, 0, 'R');
            $pdf->Ln();
        }
    }

    /**
     * Create monthly range report PDF
     */
    private function createMonthlyRangeReportPdf(TCPDF $pdf, int $userId, array $params): void {
        $startDate = $params['start_date'] ?? date('Y-m-01', strtotime('-3 months'));
        $endDate = $params['end_date'] ?? date('Y-m-t');

        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell(0, 15, 'Monthly Range Report', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 8, date('M Y', strtotime($startDate)) . ' - ' . date('M Y', strtotime($endDate)), 0, 1, 'C');
        $pdf->Ln(10);

        // Get monthly data
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

        // Monthly summary table
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Monthly Summary', 0, 1);
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);

        $headers = ['Month', 'Income', 'Expenses', 'Net Income', 'Savings Rate'];
        $widths = [25, 30, 30, 30, 25];

        foreach ($headers as $i => $header) {
            $pdf->Cell($widths[$i], 8, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetFont('helvetica', '', 8);

        foreach ($monthlyData as $month) {
            $netIncome = $month['total_income'] - $month['total_expenses'];
            $savingsRate = $month['total_income'] > 0 ? ($netIncome / $month['total_income']) * 100 : 0;

            $pdf->Cell($widths[0], 6, date('M Y', strtotime($month['month'] . '-01')), 1, 0, 'C');
            $pdf->Cell($widths[1], 6, number_format($month['total_income'], 2), 1, 0, 'R');
            $pdf->Cell($widths[2], 6, number_format($month['total_expenses'], 2), 1, 0, 'R');
            $pdf->Cell($widths[3], 6, number_format($netIncome, 2), 1, 0, 'R');
            $pdf->Cell($widths[4], 6, number_format($savingsRate, 1) . '%', 1, 0, 'R');
            $pdf->Ln();
        }
    }

    /**
     * Output PDF file
     */
    private function outputPdf(TCPDF $pdf, string $filename, bool $streamOutput = true): void {
        if ($streamOutput) {
            // Set headers for download
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            $pdf->Output($filename, 'D');
            exit;
        } else {
            // Save to file (for testing)
            $pdf->Output($filename, 'F');
        }
    }
}