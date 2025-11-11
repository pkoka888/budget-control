<?php
namespace BudgetApp\Services;

class BudgetAnalyticsService {
    private $db;

    // Performance score ranges
    private const SCORE_RANGES = [
        'excellent' => ['min' => 90, 'max' => 100, 'label' => 'Excellent'],
        'good' => ['min' => 75, 'max' => 89, 'label' => 'Good'],
        'fair' => ['min' => 60, 'max' => 74, 'label' => 'Fair'],
        'poor' => ['min' => 0, 'max' => 59, 'label' => 'Poor']
    ];

    // Trend analysis periods
    private const TREND_PERIODS = [
        'weekly' => 7,
        'monthly' => 30,
        'quarterly' => 90
    ];

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Get comprehensive budget analytics for a user
     */
    public function getBudgetAnalytics(int $userId, string $month = null): array {
        $month = $month ?? date('Y-m');

        $analytics = [
            'performance_score' => $this->calculatePerformanceScore($userId, $month),
            'spending_patterns' => $this->analyzeSpendingPatterns($userId, $month),
            'trend_analysis' => $this->analyzeTrends($userId, $month),
            'template_comparison' => $this->compareWithTemplate($userId, $month),
            'recommendations' => $this->generateRecommendations($userId, $month),
            'monthly_summary' => $this->getMonthlySummary($userId, $month)
        ];

        return $analytics;
    }

    /**
     * Calculate overall budget performance score
     */
    public function calculatePerformanceScore(int $userId, string $month): array {
        $budgets = $this->getBudgetsWithSpending($userId, $month);

        if (empty($budgets)) {
            return [
                'score' => 0,
                'grade' => 'no_data',
                'label' => 'No Budget Data',
                'factors' => []
            ];
        }

        $totalBudget = 0;
        $totalSpent = 0;
        $onTrackCount = 0;
        $factors = [];

        foreach ($budgets as $budget) {
            $totalBudget += $budget['amount'];
            $totalSpent += $budget['spent'];

            $percentage = $budget['amount'] > 0 ? ($budget['spent'] / $budget['amount']) * 100 : 0;

            if ($percentage <= 90) {
                $onTrackCount++;
                $factors[] = [
                    'category' => $budget['category_name'],
                    'status' => 'on_track',
                    'percentage' => $percentage
                ];
            } elseif ($percentage <= 100) {
                $factors[] = [
                    'category' => $budget['category_name'],
                    'status' => 'warning',
                    'percentage' => $percentage
                ];
            } else {
                $factors[] = [
                    'category' => $budget['category_name'],
                    'status' => 'over_budget',
                    'percentage' => $percentage
                ];
            }
        }

        // Calculate score based on multiple factors
        $budgetAdherence = count($budgets) > 0 ? ($onTrackCount / count($budgets)) * 100 : 0;
        $spendingEfficiency = $totalBudget > 0 ? min(100, ($totalBudget / max($totalSpent, 1)) * 100) : 0;

        $score = ($budgetAdherence * 0.6) + ($spendingEfficiency * 0.4);

        // Determine grade
        $grade = 'poor';
        $label = 'Poor';
        foreach (self::SCORE_RANGES as $rangeGrade => $range) {
            if ($score >= $range['min'] && $score <= $range['max']) {
                $grade = $rangeGrade;
                $label = $range['label'];
                break;
            }
        }

        return [
            'score' => round($score, 1),
            'grade' => $grade,
            'label' => $label,
            'factors' => $factors,
            'summary' => [
                'total_budget' => $totalBudget,
                'total_spent' => $totalSpent,
                'budget_adherence' => round($budgetAdherence, 1),
                'spending_efficiency' => round($spendingEfficiency, 1)
            ]
        ];
    }

    /**
     * Analyze spending patterns
     */
    public function analyzeSpendingPatterns(int $userId, string $month): array {
        $transactions = $this->getMonthlyTransactions($userId, $month);

        if (empty($transactions)) {
            return ['patterns' => [], 'insights' => []];
        }

        $patterns = [
            'daily_spending' => $this->analyzeDailySpending($transactions),
            'category_distribution' => $this->analyzeCategoryDistribution($transactions),
            'spending_velocity' => $this->analyzeSpendingVelocity($transactions),
            'unusual_transactions' => $this->detectUnusualTransactions($userId, $transactions)
        ];

        $insights = $this->generatePatternInsights($patterns);

        return [
            'patterns' => $patterns,
            'insights' => $insights
        ];
    }

    /**
     * Analyze spending trends over time
     */
    public function analyzeTrends(int $userId, string $currentMonth): array {
        $trends = [];

        // Get data for the last 6 months
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months", strtotime($currentMonth . '-01')));
            $data = $this->getMonthlySummary($userId, $month);

            $trends[] = [
                'month' => $month,
                'income' => $data['income'],
                'expenses' => $data['expenses'],
                'savings' => $data['savings'],
                'budget_performance' => $data['budget_performance']
            ];
        }

        // Calculate trend directions
        $trendAnalysis = [
            'income_trend' => $this->calculateTrendDirection(array_column($trends, 'income')),
            'expense_trend' => $this->calculateTrendDirection(array_column($trends, 'expenses')),
            'savings_trend' => $this->calculateTrendDirection(array_column($trends, 'savings')),
            'performance_trend' => $this->calculateTrendDirection(array_column($trends, 'budget_performance'))
        ];

        return [
            'monthly_data' => $trends,
            'trends' => $trendAnalysis,
            'forecast' => $this->generateForecast($trends)
        ];
    }

    /**
     * Compare current budget performance with template benchmarks
     */
    public function compareWithTemplate(int $userId, string $month): array {
        $userBudgets = $this->getBudgetsWithSpending($userId, $month);

        if (empty($userBudgets)) {
            return ['comparison' => null, 'insights' => []];
        }

        // Get user's preferred template
        $preferredTemplate = $this->getUserPreferredTemplate($userId);

        if (!$preferredTemplate) {
            return ['comparison' => null, 'insights' => ['No template preference found for comparison']];
        }

        $templateService = new BudgetTemplateService($this->db);
        $template = $templateService->getTemplate($preferredTemplate['template_id'], $userId);

        if (!$template) {
            return ['comparison' => null, 'insights' => ['Template not found']];
        }

        $comparison = [];
        $insights = [];

        foreach ($userBudgets as $budget) {
            $templateCategory = $this->findTemplateCategory($template['categories'], $budget['category_name']);

            if ($templateCategory) {
                $userPercentage = $budget['amount'] > 0 ? ($budget['spent'] / $budget['amount']) * 100 : 0;
                $templatePercentage = $templateCategory['suggested_percentage'];

                $variance = $userPercentage - $templatePercentage;

                $comparison[] = [
                    'category' => $budget['category_name'],
                    'user_percentage' => round($userPercentage, 1),
                    'template_percentage' => $templatePercentage,
                    'variance' => round($variance, 1),
                    'status' => $this->getVarianceStatus($variance)
                ];

                if (abs($variance) > 15) {
                    $insights[] = "Spending on {$budget['category_name']} is " .
                        (abs($variance) > 15 ? 'significantly ' : '') .
                        ($variance > 0 ? 'higher' : 'lower') . " than template recommendation";
                }
            }
        }

        return [
            'comparison' => $comparison,
            'template_name' => $template['name'],
            'insights' => $insights
        ];
    }

    /**
     * Generate personalized recommendations
     */
    public function generateRecommendations(int $userId, string $month): array {
        $recommendations = [];
        $analytics = $this->getBudgetAnalytics($userId, $month);

        // Performance-based recommendations
        $performance = $analytics['performance_score'];
        if ($performance['score'] < 75) {
            $recommendations[] = [
                'type' => 'performance',
                'priority' => 'high',
                'title' => 'Improve Budget Performance',
                'description' => 'Your budget performance score is ' . $performance['score'] . '%. Focus on categories that are over budget.',
                'actions' => ['Review spending patterns', 'Adjust budget limits', 'Set spending alerts']
            ];
        }

        // Trend-based recommendations
        $trends = $analytics['trend_analysis']['trends'];
        if ($trends['expense_trend'] === 'increasing') {
            $recommendations[] = [
                'type' => 'trend',
                'priority' => 'medium',
                'title' => 'Monitor Rising Expenses',
                'description' => 'Your expenses have been trending upward. Review recent spending to identify areas for cost reduction.',
                'actions' => ['Analyze expense categories', 'Find cost-saving opportunities', 'Set expense limits']
            ];
        }

        // Template comparison recommendations
        $comparison = $analytics['template_comparison'];
        if ($comparison['comparison']) {
            foreach ($comparison['comparison'] as $item) {
                if ($item['status'] === 'significantly_over') {
                    $recommendations[] = [
                        'type' => 'comparison',
                        'priority' => 'medium',
                        'title' => 'Reduce ' . $item['category'] . ' Spending',
                        'description' => 'You\'re spending ' . abs($item['variance']) . '% more than recommended on ' . $item['category'] . '.',
                        'actions' => ['Review ' . $item['category'] . ' expenses', 'Find alternatives', 'Set stricter budget']
                    ];
                }
            }
        }

        // Pattern-based recommendations
        $patterns = $analytics['spending_patterns'];
        if (!empty($patterns['patterns']['unusual_transactions'])) {
            $recommendations[] = [
                'type' => 'pattern',
                'priority' => 'low',
                'title' => 'Review Unusual Transactions',
                'description' => 'Some transactions appear unusual for your spending patterns. Please verify they are correct.',
                'actions' => ['Review flagged transactions', 'Categorize properly', 'Update spending patterns']
            ];
        }

        return $recommendations;
    }

    /**
     * Get monthly summary data
     */
    private function getMonthlySummary(int $userId, string $month): array {
        // Get income
        $income = $this->db->queryOne(
            "SELECT COALESCE(SUM(amount), 0) as total FROM transactions
             WHERE user_id = ? AND type = 'income' AND SUBSTR(date, 1, 7) = ?",
            [$userId, $month]
        )['total'] ?? 0;

        // Get expenses
        $expenses = $this->db->queryOne(
            "SELECT COALESCE(SUM(ABS(amount)), 0) as total FROM transactions
             WHERE user_id = ? AND type = 'expense' AND SUBSTR(date, 1, 7) = ?",
            [$userId, $month]
        )['total'] ?? 0;

        // Get budget performance
        $budgets = $this->getBudgetsWithSpending($userId, $month);
        $budgetPerformance = 0;

        if (!empty($budgets)) {
            $onTrackCount = 0;
            foreach ($budgets as $budget) {
                $percentage = $budget['amount'] > 0 ? ($budget['spent'] / $budget['amount']) * 100 : 0;
                if ($percentage <= 100) {
                    $onTrackCount++;
                }
            }
            $budgetPerformance = (count($budgets) > 0) ? ($onTrackCount / count($budgets)) * 100 : 0;
        }

        return [
            'income' => $income,
            'expenses' => $expenses,
            'savings' => $income - $expenses,
            'budget_performance' => round($budgetPerformance, 1)
        ];
    }

    /**
     * Get budgets with current spending
     */
    private function getBudgetsWithSpending(int $userId, string $month): array {
        $budgets = $this->db->query(
            "SELECT b.*, c.name as category_name FROM budgets b
             LEFT JOIN categories c ON b.category_id = c.id
             WHERE b.user_id = ? AND b.month = ?
             ORDER BY c.name",
            [$userId, $month]
        );

        foreach ($budgets as &$budget) {
            $spent = $this->db->queryOne(
                "SELECT COALESCE(SUM(ABS(amount)), 0) as total FROM transactions
                 WHERE category_id = ? AND type = 'expense' AND SUBSTR(date, 1, 7) = ?",
                [$budget['category_id'], $month]
            );
            $budget['spent'] = $spent['total'] ?? 0;
        }

        return $budgets;
    }

    /**
     * Get monthly transactions
     */
    private function getMonthlyTransactions(int $userId, string $month): array {
        return $this->db->query(
            "SELECT t.*, c.name as category_name FROM transactions t
             LEFT JOIN categories c ON t.category_id = c.id
             WHERE t.user_id = ? AND SUBSTR(t.date, 1, 7) = ?
             ORDER BY t.date DESC, t.id DESC",
            [$userId, $month]
        );
    }

    /**
     * Analyze daily spending patterns
     */
    private function analyzeDailySpending(array $transactions): array {
        $dailySpending = [];
        $expenseTransactions = array_filter($transactions, fn($t) => $t['type'] === 'expense');

        foreach ($expenseTransactions as $transaction) {
            $day = date('Y-m-d', strtotime($transaction['date']));
            if (!isset($dailySpending[$day])) {
                $dailySpending[$day] = 0;
            }
            $dailySpending[$day] += abs($transaction['amount']);
        }

        return [
            'daily_totals' => $dailySpending,
            'average_daily' => count($dailySpending) > 0 ? array_sum($dailySpending) / count($dailySpending) : 0,
            'max_daily' => !empty($dailySpending) ? max($dailySpending) : 0,
            'spending_days' => count($dailySpending)
        ];
    }

    /**
     * Analyze category distribution
     */
    private function analyzeCategoryDistribution(array $transactions): array {
        $categoryTotals = [];
        $totalExpenses = 0;

        foreach ($transactions as $transaction) {
            if ($transaction['type'] === 'expense') {
                $category = $transaction['category_name'] ?? 'Uncategorized';
                $amount = abs($transaction['amount']);
                $categoryTotals[$category] = ($categoryTotals[$category] ?? 0) + $amount;
                $totalExpenses += $amount;
            }
        }

        $distribution = [];
        foreach ($categoryTotals as $category => $amount) {
            $distribution[$category] = [
                'amount' => $amount,
                'percentage' => $totalExpenses > 0 ? ($amount / $totalExpenses) * 100 : 0
            ];
        }

        // Sort by amount descending
        arsort($distribution);

        return [
            'categories' => $distribution,
            'total_expenses' => $totalExpenses,
            'top_category' => !empty($distribution) ? array_key_first($distribution) : null
        ];
    }

    /**
     * Analyze spending velocity (transactions per day)
     */
    private function analyzeSpendingVelocity(array $transactions): array {
        $expenseTransactions = array_filter($transactions, fn($t) => $t['type'] === 'expense');
        $daysInMonth = date('t', strtotime($transactions[0]['date'] ?? 'now'));

        return [
            'total_transactions' => count($expenseTransactions),
            'transactions_per_day' => $daysInMonth > 0 ? count($expenseTransactions) / $daysInMonth : 0,
            'average_transaction' => count($expenseTransactions) > 0 ?
                array_sum(array_column($expenseTransactions, 'amount')) / count($expenseTransactions) : 0
        ];
    }

    /**
     * Detect unusual transactions
     */
    private function detectUnusualTransactions(int $userId, array $transactions): array {
        // This is a simplified implementation - in a real system you'd use statistical methods
        $unusual = [];
        $expenseTransactions = array_filter($transactions, fn($t) => $t['type'] === 'expense');

        if (count($expenseTransactions) < 5) {
            return $unusual; // Not enough data for analysis
        }

        $amounts = array_column($expenseTransactions, 'amount');
        $average = array_sum($amounts) / count($amounts);
        $stdDev = $this->calculateStandardDeviation($amounts);

        $threshold = $average + (2 * $stdDev); // 2 standard deviations

        foreach ($expenseTransactions as $transaction) {
            if (abs($transaction['amount']) > $threshold) {
                $unusual[] = [
                    'id' => $transaction['id'],
                    'description' => $transaction['description'],
                    'amount' => $transaction['amount'],
                    'date' => $transaction['date'],
                    'category' => $transaction['category_name']
                ];
            }
        }

        return $unusual;
    }

    /**
     * Generate insights from spending patterns
     */
    private function generatePatternInsights(array $patterns): array {
        $insights = [];

        // Daily spending insights
        $daily = $patterns['daily_spending'];
        if ($daily['spending_days'] > 0) {
            $insights[] = "You spent money on {$daily['spending_days']} days this month";

            if ($daily['max_daily'] > $daily['average_daily'] * 2) {
                $insights[] = "You had a high-spending day that was more than double your daily average";
            }
        }

        // Category insights
        $categories = $patterns['category_distribution'];
        if (!empty($categories['categories'])) {
            $topCategory = $categories['top_category'];
            $topPercentage = $categories['categories'][$topCategory]['percentage'];
            $insights[] = "{$topCategory} accounts for {$topPercentage}% of your expenses";
        }

        // Velocity insights
        $velocity = $patterns['spending_velocity'];
        if ($velocity['transactions_per_day'] > 3) {
            $insights[] = "You make more than 3 transactions per day on average";
        }

        return $insights;
    }

    /**
     * Calculate trend direction
     */
    private function calculateTrendDirection(array $values): string {
        if (count($values) < 3) {
            return 'insufficient_data';
        }

        $recent = array_slice($values, -3); // Last 3 values
        $earlier = array_slice($values, 0, 3); // First 3 values

        $recentAvg = array_sum($recent) / count($recent);
        $earlierAvg = array_sum($earlier) / count($earlier);

        $change = (($recentAvg - $earlierAvg) / max($earlierAvg, 1)) * 100;

        if ($change > 5) return 'increasing';
        if ($change < -5) return 'decreasing';
        return 'stable';
    }

    /**
     * Generate simple forecast
     */
    private function generateForecast(array $monthlyData): array {
        if (count($monthlyData) < 3) {
            return ['available' => false];
        }

        $expenses = array_column($monthlyData, 'expenses');
        $slope = $this->calculateLinearTrend($expenses);

        $lastExpense = end($expenses);
        $nextMonthPrediction = $lastExpense + $slope;

        return [
            'available' => true,
            'next_month_expense_prediction' => max(0, $nextMonthPrediction),
            'trend_slope' => $slope,
            'confidence' => 'low' // Simplified confidence
        ];
    }

    /**
     * Calculate linear trend slope
     */
    private function calculateLinearTrend(array $values): float {
        $n = count($values);
        if ($n < 2) return 0;

        $x = range(0, $n - 1);
        $xMean = array_sum($x) / $n;
        $yMean = array_sum($values) / $n;

        $numerator = 0;
        $denominator = 0;

        for ($i = 0; $i < $n; $i++) {
            $numerator += ($x[$i] - $xMean) * ($values[$i] - $yMean);
            $denominator += pow($x[$i] - $xMean, 2);
        }

        return $denominator != 0 ? $numerator / $denominator : 0;
    }

    /**
     * Get user's preferred template
     */
    private function getUserPreferredTemplate(int $userId): ?array {
        return $this->db->queryOne(
            "SELECT * FROM user_template_preferences
             WHERE user_id = ?
             ORDER BY last_used_at DESC
             LIMIT 1",
            [$userId]
        );
    }

    /**
     * Find template category by name
     */
    private function findTemplateCategory(array $templateCategories, string $categoryName): ?array {
        foreach ($templateCategories as $category) {
            if (strtolower($category['category_name']) === strtolower($categoryName)) {
                return $category;
            }
        }
        return null;
    }

    /**
     * Get variance status
     */
    private function getVarianceStatus(float $variance): string {
        if ($variance > 20) return 'significantly_over';
        if ($variance > 10) return 'moderately_over';
        if ($variance < -20) return 'significantly_under';
        if ($variance < -10) return 'moderately_under';
        return 'aligned';
    }

    /**
     * Calculate standard deviation
     */
    private function calculateStandardDeviation(array $values): float {
        $mean = array_sum($values) / count($values);
        $variance = 0;

        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }

        return sqrt($variance / count($values));
    }
}