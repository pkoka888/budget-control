<?php
namespace BudgetApp\Controllers;

use BudgetApp\Services\FinancialAnalyzer;
use BudgetApp\Services\CsvExporter;
use BudgetApp\Services\ExcelExporter;

class ReportController extends BaseController {
    private FinancialAnalyzer $analyzer;

    public function __construct($app) {
        parent::__construct($app);
        $this->analyzer = new FinancialAnalyzer($this->db);
    }

    public function monthly(array $params = []): void {
        $userId = $this->getUserId();
        $month = $this->getQueryParam('month', date('Y-m'));

        $summary = $this->analyzer->getMonthSummary($userId, $month);

        // Calculate month start and end dates for category methods
        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        $expensesByCategory = $this->analyzer->getExpensesByCategory($userId, $monthStart, $monthEnd);
        $incomeBySource = $this->analyzer->getIncomeBySource($userId, $monthStart, $monthEnd);

        echo $this->app->render('reports/monthly', [
            'month' => $month,
            'summary' => $summary,
            'expensesByCategory' => $expensesByCategory,
            'incomeBySource' => $incomeBySource
        ]);
    }

    public function yearly(array $params = []): void {
        $userId = $this->getUserId();

        // Check if new yearly report parameters are provided
        $startYear = $this->getQueryParam('start_year');
        $endYear = $this->getQueryParam('end_year');

        if ($startYear && $endYear) {
            // Use new comprehensive yearly report
            $yearlyReport = $this->getYearlyReport();

            echo $this->app->render('reports/yearly', [
                'yearlyReport' => $yearlyReport
            ]);
        } else {
            // Fallback to old single year format for backward compatibility
            $year = $this->getQueryParam('year', date('Y'));

            // Get data for each month
            $monthlyData = [];
            for ($m = 1; $m <= 12; $m++) {
                $month = sprintf('%04d-%02d', $year, $m);
                $monthlyData[$m] = $this->analyzer->getMonthSummary($userId, $month);
            }

            // Calculate yearly totals
            $yearlyTotals = [
                'total_income' => array_sum(array_column($monthlyData, 'total_income')),
                'total_expenses' => array_sum(array_column($monthlyData, 'total_expenses')),
                'net_income' => array_sum(array_column($monthlyData, 'net_income')),
            ];

            echo $this->app->render('reports/yearly', [
                'year' => $year,
                'monthlyData' => $monthlyData,
                'yearlyTotals' => $yearlyTotals
            ]);
        }
    }

    public function netWorth(array $params = []): void {
        $userId = $this->getUserId();

        $netWorth = $this->analyzer->getNetWorth($userId);

        // Get historical data (monthly)
        $query = "SELECT
                    DATE_TRUNC('month', date) as month,
                    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
                    SUM(CASE WHEN type = 'expense' THEN ABS(amount) ELSE 0 END) as expenses
                  FROM transactions
                  WHERE user_id IN (SELECT id FROM users WHERE id = ?)
                  GROUP BY DATE_TRUNC('month', date)
                  ORDER BY month DESC LIMIT 12";

        // For SQLite compatibility
        $historicalQuery = "SELECT
                            SUBSTR(date, 1, 7) as month,
                            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
                            SUM(CASE WHEN type = 'expense' THEN ABS(amount) ELSE 0 END) as expenses
                          FROM transactions t
                          JOIN accounts a ON t.account_id = a.id
                          WHERE a.user_id = ?
                          GROUP BY SUBSTR(date, 1, 7)
                          ORDER BY month DESC LIMIT 12";

        $historical = $this->db->query($historicalQuery, [$userId]);

        echo $this->app->render('reports/net-worth', [
            'netWorth' => $netWorth,
            'historical' => array_reverse($historical)
        ]);
    }

    public function analytics(array $params = []): void {
        $userId = $this->getUserId();
        $period = $this->getQueryParam('period', '30days');

        // Parse period
        if ($period === '30days') {
            $startDate = date('Y-m-d', strtotime('-30 days'));
            $label = 'Posledních 30 dní';
        } elseif ($period === '90days') {
            $startDate = date('Y-m-d', strtotime('-90 days'));
            $label = 'Posledních 90 dní';
        } else {
            $startDate = date('Y-m-d', strtotime('-1 year'));
            $label = 'Posledních 12 měsíců';
        }

        // Get spending trend
        $trend = $this->analyzer->getSpendingTrend($userId, $startDate);
        $anomalies = $this->analyzer->detectAnomalies($userId);
        $healthScore = $this->analyzer->getHealthScore($userId);

        echo $this->app->render('reports/analytics', [
            'trend' => $trend,
            'anomalies' => $anomalies,
            'healthScore' => $healthScore,
            'period' => $period,
            'label' => $label
        ]);
    }

    /**
     * Generate comprehensive monthly report with date range support
     */
    public function getMonthlyReport(array $params = []): array {
        $userId = $this->getUserId();
        $startDate = $this->getQueryParam('start_date', date('Y-m-01', strtotime('-3 months')));
        $endDate = $this->getQueryParam('end_date', date('Y-m-t'));

        // Get monthly aggregations
        $monthlyData = $this->getMonthlyAggregations($userId, $startDate, $endDate);

        // Get category breakdowns for each month
        $categoryBreakdowns = $this->getCategoryBreakdownsByMonth($userId, $startDate, $endDate);

        // Structure the data for display/export
        $report = [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'monthly_data' => $monthlyData,
            'category_breakdowns' => $categoryBreakdowns,
            'totals' => $this->calculateTotals($monthlyData)
        ];

        return $report;
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

        // Calculate net income for each month
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
                    c.id, c.name, c.color, c.icon,
                    SUM(CASE WHEN t.type = 'expense' THEN ABS(t.amount) ELSE 0 END) as expenses,
                    SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END) as income,
                    COUNT(t.id) as transaction_count
                  FROM transactions t
                  LEFT JOIN categories c ON t.category_id = c.id
                  JOIN accounts a ON t.account_id = a.id
                  WHERE a.user_id = ? AND t.date BETWEEN ? AND ?
                  GROUP BY SUBSTR(t.date, 1, 7), c.id
                  ORDER BY month ASC, expenses DESC";

        $results = $this->db->query($query, [$userId, $startDate, $endDate]);

        // Group by month
        $breakdowns = [];
        foreach ($results as $row) {
            $month = $row['month'];
            if (!isset($breakdowns[$month])) {
                $breakdowns[$month] = [];
            }

            if ($row['id']) { // Only include categorized transactions
                $breakdowns[$month][] = [
                    'category_id' => $row['id'],
                    'category_name' => $row['name'],
                    'color' => $row['color'],
                    'icon' => $row['icon'],
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
     * Calculate overall totals for the report period
     */
    private function calculateTotals(array $monthlyData): array {
        $totals = [
            'total_income' => 0,
            'total_expenses' => 0,
            'total_net_income' => 0,
            'average_monthly_income' => 0,
            'average_monthly_expenses' => 0,
            'average_savings_rate' => 0
        ];

        if (empty($monthlyData)) {
            return $totals;
        }

        $monthCount = count($monthlyData);

        foreach ($monthlyData as $month) {
            $totals['total_income'] += $month['total_income'];
            $totals['total_expenses'] += $month['total_expenses'];
            $totals['total_net_income'] += $month['net_income'];
        }

        $totals['average_monthly_income'] = round($totals['total_income'] / $monthCount, 2);
        $totals['average_monthly_expenses'] = round($totals['total_expenses'] / $monthCount, 2);
        $totals['average_savings_rate'] = round(
            array_sum(array_column($monthlyData, 'savings_rate')) / $monthCount, 2
        );

        return $totals;
    }
    /**
     * Generate comprehensive yearly report with trend analysis
     */
    public function getYearlyReport(array $params = []): array {
        $userId = $this->getUserId();
        $startYear = $this->getQueryParam('start_year', date('Y') - 2);
        $endYear = $this->getQueryParam('end_year', date('Y'));

        // Get yearly aggregations
        $yearlyData = $this->getYearlyAggregations($userId, $startYear, $endYear);

        // Get category breakdowns for each year
        $categoryTrends = $this->getCategoryTrendsByYear($userId, $startYear, $endYear);

        // Calculate year-over-year growth rates and trends
        $yearlyData = $this->calculateYearOverYearGrowth($yearlyData);

        // Structure the data for display/export
        $report = [
            'period' => [
                'start_year' => $startYear,
                'end_year' => $endYear
            ],
            'yearly_data' => $yearlyData,
            'category_trends' => $categoryTrends,
            'totals' => $this->calculateYearlyTotals($yearlyData)
        ];

        return $report;
    }

    /**
     * Get yearly aggregations for year range
     */
    private function getYearlyAggregations(int $userId, string $startYear, string $endYear): array {
        $query = "SELECT
                    SUBSTR(date, 1, 4) as year,
                    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN type = 'expense' THEN ABS(amount) ELSE 0 END) as total_expenses,
                    COUNT(*) as transaction_count
                  FROM transactions t
                  JOIN accounts a ON t.account_id = a.id
                  WHERE a.user_id = ? AND SUBSTR(date, 1, 4) BETWEEN ? AND ?
                  GROUP BY SUBSTR(date, 1, 4)
                  ORDER BY year ASC";

        $results = $this->db->query($query, [$userId, $startYear, $endYear]);

        // Calculate net income for each year
        foreach ($results as &$year) {
            $year['net_income'] = $year['total_income'] - $year['total_expenses'];
            $year['savings_rate'] = $year['total_income'] > 0
                ? round(($year['net_income'] / $year['total_income']) * 100, 2)
                : 0;
        }

        return $results;
    }

    /**
     * Get category trends across years
     */
    private function getCategoryTrendsByYear(int $userId, string $startYear, string $endYear): array {
        $query = "SELECT
                    SUBSTR(t.date, 1, 4) as year,
                    c.id, c.name, c.color, c.icon,
                    SUM(CASE WHEN t.type = 'expense' THEN ABS(t.amount) ELSE 0 END) as expenses,
                    SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END) as income,
                    COUNT(t.id) as transaction_count
                  FROM transactions t
                  LEFT JOIN categories c ON t.category_id = c.id
                  JOIN accounts a ON t.account_id = a.id
                  WHERE a.user_id = ? AND SUBSTR(t.date, 1, 4) BETWEEN ? AND ?
                  GROUP BY SUBSTR(t.date, 1, 4), c.id
                  ORDER BY year ASC, expenses DESC";

        $results = $this->db->query($query, [$userId, $startYear, $endYear]);

        // Group by year
        $trends = [];
        foreach ($results as $row) {
            $year = $row['year'];
            if (!isset($trends[$year])) {
                $trends[$year] = [];
            }

            if ($row['id']) { // Only include categorized transactions
                $trends[$year][] = [
                    'category_id' => $row['id'],
                    'category_name' => $row['name'],
                    'color' => $row['color'],
                    'icon' => $row['icon'],
                    'expenses' => $row['expenses'],
                    'income' => $row['income'],
                    'net' => $row['income'] - $row['expenses'],
                    'transaction_count' => $row['transaction_count']
                ];
            }
        }

        return $trends;
    }

    /**
     * Calculate year-over-year growth rates and trend indicators
     */
    private function calculateYearOverYearGrowth(array $yearlyData): array {
        if (empty($yearlyData)) {
            return $yearlyData;
        }

        $previousYear = null;
        foreach ($yearlyData as &$year) {
            if ($previousYear) {
                // Calculate growth rates
                $year['income_growth_rate'] = $previousYear['total_income'] > 0
                    ? round((($year['total_income'] - $previousYear['total_income']) / $previousYear['total_income']) * 100, 2)
                    : 0;

                $year['expense_growth_rate'] = $previousYear['total_expenses'] > 0
                    ? round((($year['total_expenses'] - $previousYear['total_expenses']) / $previousYear['total_expenses']) * 100, 2)
                    : 0;

                $year['net_income_growth_rate'] = $previousYear['net_income'] != 0
                    ? round((($year['net_income'] - $previousYear['net_income']) / abs($previousYear['net_income'])) * 100, 2)
                    : 0;

                // Determine trend indicators
                $year['income_trend'] = $this->getTrendIndicator($year['income_growth_rate']);
                $year['expense_trend'] = $this->getTrendIndicator($year['expense_growth_rate']);
                $year['net_income_trend'] = $this->getTrendIndicator($year['net_income_growth_rate']);
            } else {
                // First year has no previous data
                $year['income_growth_rate'] = 0;
                $year['expense_growth_rate'] = 0;
                $year['net_income_growth_rate'] = 0;
                $year['income_trend'] = 'stable';
                $year['expense_trend'] = 'stable';
                $year['net_income_trend'] = 'stable';
            }

            $previousYear = $year;
        }

        return $yearlyData;
    }

    /**
     * Get trend indicator based on growth rate
     */
    private function getTrendIndicator(float $growthRate): string {
        if ($growthRate > 5) {
            return 'increasing';
        } elseif ($growthRate < -5) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }

    /**
     * Calculate overall totals for yearly report period
     */
    private function calculateYearlyTotals(array $yearlyData): array {
        $totals = [
            'total_income' => 0,
            'total_expenses' => 0,
            'total_net_income' => 0,
            'average_yearly_income' => 0,
            'average_yearly_expenses' => 0,
            'average_savings_rate' => 0,
            'average_income_growth_rate' => 0,
            'average_expense_growth_rate' => 0,
            'average_net_income_growth_rate' => 0
        ];

        if (empty($yearlyData)) {
            return $totals;
        }

        $yearCount = count($yearlyData);

        foreach ($yearlyData as $year) {
            $totals['total_income'] += $year['total_income'];
            $totals['total_expenses'] += $year['total_expenses'];
            $totals['total_net_income'] += $year['net_income'];
        }

        $totals['average_yearly_income'] = round($totals['total_income'] / $yearCount, 2);
        $totals['average_yearly_expenses'] = round($totals['total_expenses'] / $yearCount, 2);
        $totals['average_savings_rate'] = round(
            array_sum(array_column($yearlyData, 'savings_rate')) / $yearCount, 2
        );

        // Calculate average growth rates (excluding first year which has 0 growth)
        $growthRates = array_filter(array_column($yearlyData, 'income_growth_rate'), fn($rate) => $rate != 0);
        if (!empty($growthRates)) {
            $totals['average_income_growth_rate'] = round(array_sum($growthRates) / count($growthRates), 2);
        }

        $expenseGrowthRates = array_filter(array_column($yearlyData, 'expense_growth_rate'), fn($rate) => $rate != 0);
        if (!empty($expenseGrowthRates)) {
            $totals['average_expense_growth_rate'] = round(array_sum($expenseGrowthRates) / count($expenseGrowthRates), 2);
        }

        $netIncomeGrowthRates = array_filter(array_column($yearlyData, 'net_income_growth_rate'), fn($rate) => $rate != 0);
        if (!empty($netIncomeGrowthRates)) {
            $totals['average_net_income_growth_rate'] = round(array_sum($netIncomeGrowthRates) / count($netIncomeGrowthRates), 2);
        }

        return $totals;
    }

    public function exportCsv(array $params = []): void {
        $userId = $this->getUserId();
        $reportType = $params['type'] ?? 'monthly';

        $params = [];

        // Get parameters based on report type
        switch ($reportType) {
            case 'monthly':
                $params['month'] = $this->getQueryParam('month', date('Y-m'));
                break;
            case 'yearly':
                $params['year'] = $this->getQueryParam('year', date('Y'));
                break;
            case 'monthly-report':
                $params['start_date'] = $this->getQueryParam('start_date', date('Y-m-01', strtotime('-3 months')));
                $params['end_date'] = $this->getQueryParam('end_date', date('Y-m-t'));
                break;
            case 'categories':
            case 'accounts':
                $params['start_date'] = $this->getQueryParam('start_date');
                $params['end_date'] = $this->getQueryParam('end_date');
                break;
        }

        $exporter = new CsvExporter($this->db);
        $exporter->exportReports($userId, $reportType, $params);
    }

    public function exportExcel(array $params = []): void {
        $userId = $this->getUserId();
        $reportType = $params['type'] ?? 'monthly';

        $params = [];

        // Get parameters based on report type
        switch ($reportType) {
            case 'monthly':
                $params['month'] = $this->getQueryParam('month', date('Y-m'));
                break;
            case 'yearly':
                $params['year'] = $this->getQueryParam('year', date('Y'));
                break;
            case 'monthly-report':
                $params['start_date'] = $this->getQueryParam('start_date', date('Y-m-01', strtotime('-3 months')));
                $params['end_date'] = $this->getQueryParam('end_date', date('Y-m-t'));
                break;
            case 'categories':
            case 'accounts':
                $params['start_date'] = $this->getQueryParam('start_date');
                $params['end_date'] = $this->getQueryParam('end_date');
                break;
        }

        $exporter = new ExcelExporter($this->db);
        $exporter->exportReports($userId, $reportType, $params);
    }
}
