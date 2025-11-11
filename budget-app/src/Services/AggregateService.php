<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

class AggregateService {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Build aggregate tables for recurring income, expenses, and balances
     */
    public function buildAggregates(int $userId, string $startDate = null, string $endDate = null): array {
        $startDate = $startDate ?? date('Y-m-d', strtotime('-1 year'));
        $endDate = $endDate ?? date('Y-m-d');

        $this->db->beginTransaction();

        try {
            // Build recurring income aggregates
            $recurringIncome = $this->buildRecurringIncomeAggregates($userId, $startDate, $endDate);

            // Build recurring expenses aggregates
            $recurringExpenses = $this->buildRecurringExpensesAggregates($userId, $startDate, $endDate);

            // Build balance aggregates
            $balances = $this->buildBalanceAggregates($userId, $startDate, $endDate);

            // Build monthly summaries
            $monthlySummaries = $this->buildMonthlySummaries($userId, $startDate, $endDate);

            // Build category spending trends
            $categoryTrends = $this->buildCategoryTrends($userId, $startDate, $endDate);

            $this->db->commit();

            return [
                'success' => true,
                'recurring_income' => $recurringIncome,
                'recurring_expenses' => $recurringExpenses,
                'balances' => $balances,
                'monthly_summaries' => $monthlySummaries,
                'category_trends' => $categoryTrends,
                'period' => ['start' => $startDate, 'end' => $endDate]
            ];

        } catch (\Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Build recurring income aggregates
     */
    private function buildRecurringIncomeAggregates(int $userId, string $startDate, string $endDate): array {
        // Find transactions that appear regularly (income patterns)
        $incomeTransactions = $this->db->query(
            "SELECT
                t.description,
                t.merchant_name,
                t.amount,
                DATE_FORMAT(t.date, '%Y-%m') as month_year,
                COUNT(*) as frequency,
                AVG(t.amount) as avg_amount,
                MIN(t.date) as first_occurrence,
                MAX(t.date) as last_occurrence
             FROM transactions t
             JOIN categories c ON t.category_id = c.id
             WHERE t.user_id = ?
               AND t.date BETWEEN ? AND ?
               AND t.amount > 0
               AND c.type = 'income'
             GROUP BY t.description, t.merchant_name, DATE_FORMAT(t.date, '%Y-%m')
             HAVING frequency >= 2
             ORDER BY avg_amount DESC, frequency DESC",
            [$userId, $startDate, $endDate]
        );

        $recurringIncome = [];

        foreach ($incomeTransactions as $transaction) {
            $key = md5($transaction['description'] . ($transaction['merchant_name'] ?? ''));

            if (!isset($recurringIncome[$key])) {
                // Check if this is truly recurring by analyzing the pattern
                $isRecurring = $this->analyzeRecurrencePattern($userId, $transaction, $startDate, $endDate);

                if ($isRecurring) {
                    $recurringIncome[$key] = [
                        'description' => $transaction['description'],
                        'merchant_name' => $transaction['merchant_name'],
                        'avg_amount' => round($transaction['avg_amount'], 2),
                        'frequency' => $transaction['frequency'],
                        'first_occurrence' => $transaction['first_occurrence'],
                        'last_occurrence' => $transaction['last_occurrence'],
                        'recurrence_type' => $this->determineRecurrenceType($transaction),
                        'total_annual' => $this->calculateAnnualTotal($transaction),
                        'consistency_score' => $this->calculateConsistencyScore($userId, $transaction, $startDate, $endDate)
                    ];
                }
            }
        }

        return array_values($recurringIncome);
    }

    /**
     * Build recurring expenses aggregates
     */
    private function buildRecurringExpensesAggregates(int $userId, string $startDate, string $endDate): array {
        // Find transactions that appear regularly (expense patterns)
        $expenseTransactions = $this->db->query(
            "SELECT
                t.description,
                t.merchant_name,
                t.amount,
                DATE_FORMAT(t.date, '%Y-%m') as month_year,
                COUNT(*) as frequency,
                AVG(ABS(t.amount)) as avg_amount,
                MIN(t.date) as first_occurrence,
                MAX(t.date) as last_occurrence,
                c.name as category_name
             FROM transactions t
             JOIN categories c ON t.category_id = c.id
             WHERE t.user_id = ?
               AND t.date BETWEEN ? AND ?
               AND t.amount < 0
               AND c.type = 'expense'
             GROUP BY t.description, t.merchant_name, DATE_FORMAT(t.date, '%Y-%m'), c.name
             HAVING frequency >= 2
             ORDER BY avg_amount DESC, frequency DESC",
            [$userId, $startDate, $endDate]
        );

        $recurringExpenses = [];

        foreach ($expenseTransactions as $transaction) {
            $key = md5($transaction['description'] . ($transaction['merchant_name'] ?? '') . $transaction['category_name']);

            if (!isset($recurringExpenses[$key])) {
                $isRecurring = $this->analyzeRecurrencePattern($userId, $transaction, $startDate, $endDate);

                if ($isRecurring) {
                    $recurringExpenses[$key] = [
                        'description' => $transaction['description'],
                        'merchant_name' => $transaction['merchant_name'],
                        'category_name' => $transaction['category_name'],
                        'avg_amount' => round($transaction['avg_amount'], 2),
                        'frequency' => $transaction['frequency'],
                        'first_occurrence' => $transaction['first_occurrence'],
                        'last_occurrence' => $transaction['last_occurrence'],
                        'recurrence_type' => $this->determineRecurrenceType($transaction),
                        'total_annual' => $this->calculateAnnualTotal($transaction),
                        'consistency_score' => $this->calculateConsistencyScore($userId, $transaction, $startDate, $endDate),
                        'budget_impact' => $this->calculateBudgetImpact($userId, $transaction)
                    ];
                }
            }
        }

        return array_values($recurringExpenses);
    }

    /**
     * Build balance aggregates
     */
    private function buildBalanceAggregates(int $userId, string $startDate, string $endDate): array {
        // Get account balances over time
        $balances = $this->db->query(
            "SELECT
                a.name as account_name,
                a.type as account_type,
                a.currency,
                DATE_FORMAT(t.date, '%Y-%m-%d') as date,
                SUM(CASE WHEN t.amount > 0 THEN t.amount ELSE 0 END) as inflows,
                SUM(CASE WHEN t.amount < 0 THEN ABS(t.amount) ELSE 0 END) as outflows,
                (
                    SELECT SUM(CASE WHEN t2.amount > 0 THEN t2.amount ELSE -t2.amount END)
                    FROM transactions t2
                    WHERE t2.account_id = a.id AND t2.date <= t.date
                ) as running_balance
             FROM accounts a
             LEFT JOIN transactions t ON a.id = t.account_id AND t.date BETWEEN ? AND ?
             WHERE a.user_id = ?
             GROUP BY a.id, a.name, a.type, a.currency, DATE_FORMAT(t.date, '%Y-%m-%d')
             ORDER BY a.name, t.date",
            [$startDate, $endDate, $userId]
        );

        // Aggregate by month for summary view
        $monthlyBalances = [];
        foreach ($balances as $balance) {
            $monthKey = date('Y-m', strtotime($balance['date']));

            if (!isset($monthlyBalances[$monthKey])) {
                $monthlyBalances[$monthKey] = [
                    'period' => $monthKey,
                    'accounts' => []
                ];
            }

            $accountKey = $balance['account_name'];
            if (!isset($monthlyBalances[$monthKey]['accounts'][$accountKey])) {
                $monthlyBalances[$monthKey]['accounts'][$accountKey] = [
                    'name' => $balance['account_name'],
                    'type' => $balance['account_type'],
                    'currency' => $balance['currency'],
                    'monthly_inflows' => 0,
                    'monthly_outflows' => 0,
                    'avg_balance' => 0,
                    'min_balance' => null,
                    'max_balance' => null,
                    'balances' => []
                ];
            }

            $monthlyBalances[$monthKey]['accounts'][$accountKey]['monthly_inflows'] += $balance['inflows'];
            $monthlyBalances[$monthKey]['accounts'][$accountKey]['monthly_outflows'] += $balance['outflows'];
            $monthlyBalances[$monthKey]['accounts'][$accountKey]['balances'][] = $balance['running_balance'];
        }

        // Calculate balance statistics
        foreach ($monthlyBalances as &$monthData) {
            foreach ($monthData['accounts'] as &$account) {
                if (!empty($account['balances'])) {
                    $account['avg_balance'] = round(array_sum($account['balances']) / count($account['balances']), 2);
                    $account['min_balance'] = round(min($account['balances']), 2);
                    $account['max_balance'] = round(max($account['balances']), 2);
                }
                unset($account['balances']); // Remove raw balances to reduce data size
            }
            $monthData['accounts'] = array_values($monthData['accounts']);
        }

        return array_values($monthlyBalances);
    }

    /**
     * Build monthly summaries
     */
    private function buildMonthlySummaries(int $userId, string $startDate, string $endDate): array {
        $summaries = $this->db->query(
            "SELECT
                DATE_FORMAT(t.date, '%Y-%m') as period,
                COUNT(*) as transaction_count,
                SUM(CASE WHEN t.amount > 0 THEN t.amount ELSE 0 END) as total_income,
                SUM(CASE WHEN t.amount < 0 THEN ABS(t.amount) ELSE 0 END) as total_expenses,
                (SUM(CASE WHEN t.amount > 0 THEN t.amount ELSE 0 END) -
                 SUM(CASE WHEN t.amount < 0 THEN ABS(t.amount) ELSE 0 END)) as net_flow,
                AVG(CASE WHEN t.amount > 0 THEN t.amount END) as avg_income_transaction,
                AVG(CASE WHEN t.amount < 0 THEN ABS(t.amount) END) as avg_expense_transaction,
                COUNT(DISTINCT c.id) as unique_categories_used,
                COUNT(DISTINCT t.merchant_id) as unique_merchants
             FROM transactions t
             LEFT JOIN categories c ON t.category_id = c.id
             WHERE t.user_id = ? AND t.date BETWEEN ? AND ?
             GROUP BY DATE_FORMAT(t.date, '%Y-%m')
             ORDER BY period",
            [$userId, $startDate, $endDate]
        );

        // Enhance with budget comparisons
        foreach ($summaries as &$summary) {
            $budgetData = $this->getBudgetComparison($userId, $summary['period']);
            $summary = array_merge($summary, $budgetData);
        }

        return $summaries;
    }

    /**
     * Build category spending trends
     */
    private function buildCategoryTrends(int $userId, string $startDate, string $endDate): array {
        $trends = $this->db->query(
            "SELECT
                c.name as category_name,
                c.type as category_type,
                DATE_FORMAT(t.date, '%Y-%m') as period,
                SUM(ABS(t.amount)) as total_amount,
                COUNT(*) as transaction_count,
                AVG(ABS(t.amount)) as avg_transaction,
                MIN(ABS(t.amount)) as min_transaction,
                MAX(ABS(t.amount)) as max_transaction
             FROM transactions t
             JOIN categories c ON t.category_id = c.id
             WHERE t.user_id = ? AND t.date BETWEEN ? AND ?
             GROUP BY c.id, c.name, c.type, DATE_FORMAT(t.date, '%Y-%m')
             ORDER BY c.name, period",
            [$userId, $startDate, $endDate]
        );

        // Group by category and calculate trends
        $categoryTrends = [];
        foreach ($trends as $trend) {
            $categoryKey = $trend['category_name'];

            if (!isset($categoryTrends[$categoryKey])) {
                $categoryTrends[$categoryKey] = [
                    'category_name' => $trend['category_name'],
                    'category_type' => $trend['category_type'],
                    'monthly_data' => [],
                    'trend_analysis' => []
                ];
            }

            $categoryTrends[$categoryKey]['monthly_data'][] = [
                'period' => $trend['period'],
                'total_amount' => round($trend['total_amount'], 2),
                'transaction_count' => $trend['transaction_count'],
                'avg_transaction' => round($trend['avg_transaction'], 2)
            ];
        }

        // Calculate trend analysis for each category
        foreach ($categoryTrends as &$category) {
            $category['trend_analysis'] = $this->analyzeCategoryTrend($category['monthly_data']);
        }

        return array_values($categoryTrends);
    }

    /**
     * Analyze if a transaction pattern is truly recurring
     */
    private function analyzeRecurrencePattern(int $userId, array $transaction, string $startDate, string $endDate): bool {
        // Get all occurrences of this transaction
        $occurrences = $this->db->query(
            "SELECT date FROM transactions
             WHERE user_id = ? AND description = ? AND merchant_name <=> ?
             AND date BETWEEN ? AND ?
             ORDER BY date",
            [
                $userId,
                $transaction['description'],
                $transaction['merchant_name'],
                $startDate,
                $endDate
            ]
        );

        if (count($occurrences) < 3) {
            return false; // Need at least 3 occurrences to be considered recurring
        }

        // Calculate intervals between transactions
        $intervals = [];
        for ($i = 1; $i < count($occurrences); $i++) {
            $interval = (strtotime($occurrences[$i]['date']) - strtotime($occurrences[$i-1]['date'])) / (60*60*24);
            $intervals[] = $interval;
        }

        // Check if intervals are consistent (within 30% of average)
        $avgInterval = array_sum($intervals) / count($intervals);
        $consistentCount = 0;

        foreach ($intervals as $interval) {
            if (abs($interval - $avgInterval) / $avgInterval <= 0.3) {
                $consistentCount++;
            }
        }

        // At least 70% of intervals should be consistent
        return ($consistentCount / count($intervals)) >= 0.7;
    }

    /**
     * Determine recurrence type (monthly, weekly, etc.)
     */
    private function determineRecurrenceType(array $transaction): string {
        // This is a simplified version - in practice, you'd analyze the intervals
        // For now, assume monthly if it appears multiple times per month
        return 'monthly'; // Could be 'weekly', 'bi-weekly', 'quarterly', etc.
    }

    /**
     * Calculate annual total for recurring item
     */
    private function calculateAnnualTotal(array $transaction): float {
        // Simplified calculation - assumes monthly recurrence
        return round($transaction['avg_amount'] * 12, 2);
    }

    /**
     * Calculate consistency score (0-100)
     */
    private function calculateConsistencyScore(int $userId, array $transaction, string $startDate, string $endDate): int {
        // Simplified scoring based on frequency and regularity
        $monthsDiff = $this->monthsBetween($startDate, $endDate);
        $expectedOccurrences = max(1, $monthsDiff); // Assume monthly
        $actualOccurrences = $transaction['frequency'];

        $score = min(100, ($actualOccurrences / $expectedOccurrences) * 100);
        return (int)round($score);
    }

    /**
     * Calculate budget impact
     */
    private function calculateBudgetImpact(int $userId, array $transaction): array {
        // Get current budget for this category and period
        $currentMonth = date('Y-m');
        $budget = $this->db->queryOne(
            "SELECT amount, spent FROM budgets
             WHERE user_id = ? AND month = ? AND category_id = (
                 SELECT id FROM categories WHERE name = ? AND user_id = ?
             )",
            [$userId, $currentMonth, $transaction['category_name'], $userId]
        );

        if (!$budget) {
            return ['budget_status' => 'no_budget', 'impact_percentage' => 0];
        }

        $monthlyAmount = $transaction['avg_amount'];
        $impactPercentage = $budget['amount'] > 0 ? ($monthlyAmount / $budget['amount']) * 100 : 0;

        $status = 'within_budget';
        if ($impactPercentage > 100) {
            $status = 'over_budget';
        } elseif ($impactPercentage > 80) {
            $status = 'nearing_budget';
        }

        return [
            'budget_status' => $status,
            'impact_percentage' => round($impactPercentage, 1),
            'monthly_budget' => round($budget['amount'], 2),
            'current_spent' => round($budget['spent'], 2)
        ];
    }

    /**
     * Get budget comparison data
     */
    private function getBudgetComparison(int $userId, string $period): array {
        $budgets = $this->db->query(
            "SELECT
                c.name as category_name,
                b.amount as budgeted,
                COALESCE(SUM(ABS(t.amount)), 0) as actual
             FROM budgets b
             JOIN categories c ON b.category_id = c.id
             LEFT JOIN transactions t ON t.category_id = c.id
                AND t.user_id = b.user_id
                AND DATE_FORMAT(t.date, '%Y-%m') = b.month
                AND t.amount < 0
             WHERE b.user_id = ? AND b.month = ?
             GROUP BY c.id, c.name, b.amount",
            [$userId, $period]
        );

        $totalBudgeted = 0;
        $totalActual = 0;
        $overBudgetCategories = 0;

        foreach ($budgets as $budget) {
            $totalBudgeted += $budget['budgeted'];
            $totalActual += $budget['actual'];
            if ($budget['actual'] > $budget['budgeted']) {
                $overBudgetCategories++;
            }
        }

        return [
            'budget_comparison' => [
                'total_budgeted' => round($totalBudgeted, 2),
                'total_actual' => round($totalActual, 2),
                'budget_variance' => round($totalActual - $totalBudgeted, 2),
                'budget_variance_percentage' => $totalBudgeted > 0 ? round((($totalActual - $totalBudgeted) / $totalBudgeted) * 100, 1) : 0,
                'over_budget_categories' => $overBudgetCategories
            ]
        ];
    }

    /**
     * Analyze category trend
     */
    private function analyzeCategoryTrend(array $monthlyData): array {
        if (count($monthlyData) < 2) {
            return ['trend' => 'insufficient_data', 'change_percentage' => 0];
        }

        // Sort by period
        usort($monthlyData, function($a, $b) {
            return strcmp($a['period'], $b['period']);
        });

        $firstAmount = $monthlyData[0]['total_amount'];
        $lastAmount = end($monthlyData)['total_amount'];

        $changePercentage = $firstAmount > 0 ? (($lastAmount - $firstAmount) / $firstAmount) * 100 : 0;

        $trend = 'stable';
        if ($changePercentage > 10) {
            $trend = 'increasing';
        } elseif ($changePercentage < -10) {
            $trend = 'decreasing';
        }

        return [
            'trend' => $trend,
            'change_percentage' => round($changePercentage, 1),
            'periods_analyzed' => count($monthlyData),
            'avg_monthly_amount' => round(array_sum(array_column($monthlyData, 'total_amount')) / count($monthlyData), 2)
        ];
    }

    /**
     * Calculate months between two dates
     */
    private function monthsBetween(string $startDate, string $endDate): int {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $interval = $start->diff($end);
        return $interval->y * 12 + $interval->m + 1; // +1 to include both months
    }

    /**
     * Get aggregate summary for dashboard
     */
    public function getAggregateSummary(int $userId): array {
        $currentMonth = date('Y-m');
        $lastMonth = date('Y-m', strtotime('-1 month'));

        $summary = $this->db->queryOne(
            "SELECT
                COUNT(DISTINCT CASE WHEN DATE_FORMAT(date, '%Y-%m') = ? THEN id END) as current_month_transactions,
                COUNT(DISTINCT CASE WHEN DATE_FORMAT(date, '%Y-%m') = ? THEN id END) as last_month_transactions,
                SUM(CASE WHEN DATE_FORMAT(date, '%Y-%m') = ? AND amount > 0 THEN amount END) as current_income,
                SUM(CASE WHEN DATE_FORMAT(date, '%Y-%m') = ? AND amount < 0 THEN ABS(amount) END) as current_expenses,
                SUM(CASE WHEN DATE_FORMAT(date, '%Y-%m') = ? AND amount > 0 THEN amount END) as last_income,
                SUM(CASE WHEN DATE_FORMAT(date, '%Y-%m') = ? AND amount < 0 THEN ABS(amount) END) as last_expenses
             FROM transactions
             WHERE user_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 2 MONTH)",
            [$currentMonth, $lastMonth, $currentMonth, $currentMonth, $lastMonth, $lastMonth, $userId]
        );

        return [
            'current_month' => [
                'transactions' => (int)($summary['current_month_transactions'] ?? 0),
                'income' => round($summary['current_income'] ?? 0, 2),
                'expenses' => round($summary['current_expenses'] ?? 0, 2),
                'net' => round(($summary['current_income'] ?? 0) - ($summary['current_expenses'] ?? 0), 2)
            ],
            'last_month' => [
                'transactions' => (int)($summary['last_month_transactions'] ?? 0),
                'income' => round($summary['last_income'] ?? 0, 2),
                'expenses' => round($summary['last_expenses'] ?? 0, 2),
                'net' => round(($summary['last_income'] ?? 0) - ($summary['last_expenses'] ?? 0), 2)
            ],
            'changes' => [
                'transactions_change' => $this->calculatePercentageChange(
                    $summary['last_month_transactions'] ?? 0,
                    $summary['current_month_transactions'] ?? 0
                ),
                'income_change' => $this->calculatePercentageChange(
                    $summary['last_income'] ?? 0,
                    $summary['current_income'] ?? 0
                ),
                'expenses_change' => $this->calculatePercentageChange(
                    $summary['last_expenses'] ?? 0,
                    $summary['current_expenses'] ?? 0
                )
            ]
        ];
    }

    /**
     * Calculate percentage change
     */
    private function calculatePercentageChange(float $oldValue, float $newValue): float {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }
        return round((($newValue - $oldValue) / $oldValue) * 100, 1);
    }
}