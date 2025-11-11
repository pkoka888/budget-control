<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

class McpFinancialAdapter {
    private Database $db;
    private AnalyticsWorker $analytics;
    private array $userContext;

    public function __construct(Database $db, array $userContext = []) {
        $this->db = $db;
        $this->analytics = new AnalyticsWorker($db);
        $this->userContext = $userContext;
    }

    /**
     * Summarize financial stats for LLM consumption
     */
    public function getFinancialStats(int $userId): array {
        $budgetHealth = $this->analytics->calculateBudgetHealth($userId);
        $savingsRunway = $this->analytics->calculateSavingsRunway($userId);
        $debtTracking = $this->analytics->calculateDebtTracking($userId);
        $cashFlow = $this->analytics->generateCashFlowForecast($userId, 3);

        // Get recent transactions for context
        $recentTransactions = $this->getRecentTransactions($userId, 10);

        // Get top spending categories
        $analyzer = new FinancialAnalyzer($this->db);
        $expensesByCategory = $analyzer->getExpensesByCategory($userId,
            date('Y-m-01', strtotime('-1 month')),
            date('Y-m-t')
        );

        return [
            'user_context' => $this->userContext,
            'financial_stats' => [
                'budget_health' => $budgetHealth,
                'savings_runway' => $savingsRunway,
                'debt_tracking' => $debtTracking,
                'cash_flow_forecast' => $cashFlow,
                'recent_transactions' => $recentTransactions,
                'top_expense_categories' => array_slice($expensesByCategory, 0, 5)
            ],
            'generated_at' => date('Y-m-d H:i:s'),
            'data_hash' => $this->generateDataHash($userId)
        ];
    }

    /**
     * Get transaction timelines for cash flow analysis
     */
    public function getTransactionTimelines(int $userId, int $days = 90): array {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        $endDate = date('Y-m-d');

        $transactions = $this->db->query(
            "SELECT
                DATE(t.date) as date,
                t.type,
                SUM(t.amount) as amount,
                COUNT(*) as transaction_count,
                GROUP_CONCAT(DISTINCT COALESCE(c.name, 'Uncategorized')) as categories
             FROM transactions t
             LEFT JOIN categories c ON t.category_id = c.id
             WHERE t.user_id = ? AND t.date BETWEEN ? AND ?
             GROUP BY DATE(t.date), t.type
             ORDER BY t.date ASC",
            [$userId, $startDate, $endDate]
        );

        // Group by date
        $timeline = [];
        foreach ($transactions as $transaction) {
            $date = $transaction['date'];
            if (!isset($timeline[$date])) {
                $timeline[$date] = [
                    'date' => $date,
                    'income' => 0,
                    'expenses' => 0,
                    'net' => 0,
                    'transaction_count' => 0,
                    'categories' => []
                ];
            }

            if ($transaction['type'] === 'income') {
                $timeline[$date]['income'] += $transaction['amount'];
            } else {
                $timeline[$date]['expenses'] += $transaction['amount'];
            }

            $timeline[$date]['transaction_count'] += $transaction['transaction_count'];
            $timeline[$date]['categories'] = array_unique(array_merge(
                $timeline[$date]['categories'],
                explode(',', $transaction['categories'])
            ));
        }

        // Calculate net for each day
        foreach ($timeline as &$day) {
            $day['net'] = $day['income'] - $day['expenses'];
        }

        return array_values($timeline);
    }

    /**
     * Get recurring bills and obligations
     */
    public function getRecurringBills(int $userId): array {
        // Get recurring transactions
        $recurring = $this->db->query(
            "SELECT
                description,
                amount,
                frequency,
                type,
                next_due_date,
                category_id,
                c.name as category_name
             FROM recurring_transactions rt
             LEFT JOIN categories c ON rt.category_id = c.id
             WHERE rt.user_id = ? AND rt.is_active = 1
             ORDER BY rt.next_due_date ASC",
            [$userId]
        );

        // Get subscription-like patterns from transaction history
        $subscriptions = $this->detectSubscriptions($userId);

        return [
            'recurring_transactions' => $recurring,
            'detected_subscriptions' => $subscriptions,
            'monthly_total' => array_sum(array_column($recurring, 'amount'))
        ];
    }

    /**
     * Get debt list with details
     */
    public function getDebtList(int $userId): array {
        $debts = $this->db->query(
            "SELECT
                a.id,
                a.name,
                ABS(a.balance) as balance,
                a.type,
                a.description,
                CASE
                    WHEN a.type = 'credit_card' THEN 0.25
                    WHEN a.type = 'loan' THEN 0.05
                    ELSE 0.03
                END as estimated_interest_rate,
                CASE
                    WHEN a.type = 'credit_card' THEN ABS(a.balance) * 0.03
                    WHEN a.type = 'loan' THEN ABS(a.balance) * 0.01
                    ELSE ABS(a.balance) * 0.005
                END as minimum_payment
             FROM accounts a
             WHERE a.user_id = ? AND a.type IN ('credit_card', 'loan', 'mortgage')
                   AND a.balance < 0
             ORDER BY balance ASC", // Smallest balance first (snowball method)
            [$userId]
        );

        $totalDebt = array_sum(array_column($debts, 'balance'));
        $totalMinPayment = array_sum(array_column($debts, 'minimum_payment'));

        return [
            'debts' => $debts,
            'total_debt' => $totalDebt,
            'total_minimum_payment' => $totalMinPayment,
            'debt_count' => count($debts)
        ];
    }

    /**
     * Get user skills and career context
     */
    public function getUserSkillsContext(int $userId): array {
        // Get user settings for career context
        $settings = $this->db->query(
            "SELECT setting_key, setting_value
             FROM user_settings
             WHERE user_id = ? AND category IN ('profile', 'preferences')",
            [$userId]
        );

        $context = [];
        foreach ($settings as $setting) {
            $context[$setting['setting_key']] = $setting['setting_value'];
        }

        return [
            'skills' => $context['skills'] ?? 'AI-assisted coding, software development',
            'current_role' => $context['current_role'] ?? 'IT technician',
            'target_regions' => $context['target_regions'] ?? 'Czech Republic, EU',
            'relocation_willingness' => $context['relocation_willingness'] ?? 'partial',
            'remote_work_preference' => $context['remote_work_preference'] ?? 'flexible',
            'salary_expectations' => $context['salary_expectations'] ?? 'market rate',
            'certifications' => $context['certifications'] ?? 'none specified'
        ];
    }

    /**
     * Get market data context (simplified for now)
     */
    public function getMarketDataContext(): array {
        return [
            'tech_salary_ranges' => [
                'czech_republic' => [
                    'junior' => '800000-1200000', // CZK per year
                    'mid' => '1200000-1800000',
                    'senior' => '1800000-2500000'
                ],
                'eu_average' => [
                    'junior' => '45000-65000', // EUR per year
                    'mid' => '65000-85000',
                    'senior' => '85000-120000'
                ]
            ],
            'high_demand_roles' => [
                'AI Engineer',
                'Full Stack Developer',
                'DevOps Engineer',
                'Data Scientist',
                'Cloud Architect'
            ],
            'remote_friendly_roles' => [
                'Software Developer',
                'Data Analyst',
                'Technical Writer',
                'UX Designer',
                'Project Manager'
            ]
        ];
    }

    /**
     * Get budget status summary
     */
    public function getBudgetStatus(int $userId): array {
        $currentMonth = date('Y-m');
        $analyzer = new FinancialAnalyzer($this->db);
        $monthSummary = $analyzer->getMonthSummary($userId, $currentMonth);
        $budgetAnalysis = $analyzer->getBudgetAnalysis($userId, $currentMonth);

        return [
            'current_month' => $currentMonth,
            'income' => $monthSummary['total_income'],
            'expenses' => $monthSummary['total_expenses'],
            'net_income' => $monthSummary['net_income'],
            'savings_rate' => $monthSummary['savings_rate'],
            'budget_compliance' => [
                'total_budgeted' => array_sum(array_column($budgetAnalysis, 'budget_amount')),
                'total_spent' => array_sum(array_column($budgetAnalysis, 'actual_amount')),
                'over_budget_count' => count(array_filter($budgetAnalysis, fn($b) => $b['is_over_budget']))
            ]
        ];
    }

    /**
     * Get goals and crisis urgency context
     */
    public function getGoalsAndUrgency(int $userId): array {
        $goals = $this->db->query(
            "SELECT
                name,
                goal_type,
                target_amount,
                current_amount,
                target_date,
                priority,
                is_active
             FROM goals
             WHERE user_id = ? AND is_active = 1
             ORDER BY priority DESC, target_date ASC",
            [$userId]
        );

        // Determine crisis urgency based on financial health
        $budgetHealth = $this->analytics->calculateBudgetHealth($userId);
        $savingsRunway = $this->analytics->calculateSavingsRunway($userId);

        $urgency = 'low';
        if ($savingsRunway['runway_months'] < 1) {
            $urgency = 'critical';
        } elseif ($savingsRunway['runway_months'] < 3) {
            $urgency = 'high';
        } elseif ($budgetHealth['health_score'] < 50) {
            $urgency = 'medium';
        }

        return [
            'goals' => $goals,
            'crisis_urgency' => $urgency,
            'financial_health_score' => $budgetHealth['health_score'],
            'emergency_runway_months' => $savingsRunway['runway_months']
        ];
    }

    /**
     * Get recent transactions for context
     */
    private function getRecentTransactions(int $userId, int $limit = 10): array {
        return $this->db->query(
            "SELECT
                t.date,
                t.description,
                t.amount,
                t.type,
                COALESCE(c.name, 'Uncategorized') as category,
                COALESCE(m.name, t.description) as merchant
             FROM transactions t
             LEFT JOIN categories c ON t.category_id = c.id
             LEFT JOIN merchants m ON t.merchant_id = m.id
             WHERE t.user_id = ?
             ORDER BY t.date DESC
             LIMIT ?",
            [$userId, $limit]
        );
    }

    /**
     * Detect subscription patterns
     */
    private function detectSubscriptions(int $userId): array {
        // Find recurring transactions with similar amounts and descriptions
        $candidates = $this->db->query(
            "SELECT
                description,
                ROUND(amount, -1) as rounded_amount, -- Round to nearest 10
                COUNT(*) as frequency,
                AVG(JULIANDAY('now') - JULIANDAY(date)) as avg_days_between,
                MIN(date) as first_seen,
                MAX(date) as last_seen
             FROM transactions
             WHERE user_id = ? AND type = 'expense' AND amount > 100
             GROUP BY description, ROUND(amount, -1)
             HAVING COUNT(*) > 2 AND AVG(JULIANDAY('now') - JULIANDAY(date)) < 90
             ORDER BY frequency DESC
             LIMIT 10",
            [$userId]
        );

        $subscriptions = [];
        foreach ($candidates as $candidate) {
            $avgInterval = $candidate['avg_days_between'];
            $frequency = 'unknown';

            if ($avgInterval <= 7) $frequency = 'weekly';
            elseif ($avgInterval <= 14) $frequency = 'bi-weekly';
            elseif ($avgInterval <= 31) $frequency = 'monthly';
            elseif ($avgInterval <= 93) $frequency = 'quarterly';
            elseif ($avgInterval <= 183) $frequency = 'semi-annual';
            elseif ($avgInterval <= 367) $frequency = 'annual';

            $subscriptions[] = [
                'description' => $candidate['description'],
                'amount' => $candidate['rounded_amount'],
                'frequency' => $frequency,
                'estimated_monthly' => $this->estimateMonthlyCost($candidate),
                'confidence' => min(90, $candidate['frequency'] * 10)
            ];
        }

        return $subscriptions;
    }

    /**
     * Estimate monthly cost for subscription
     */
    private function estimateMonthlyCost(array $subscription): float {
        $amount = $subscription['rounded_amount'];
        $avgDays = $subscription['avg_days_between'];

        if ($avgDays <= 7) return $amount * 4.33; // Weekly
        if ($avgDays <= 14) return $amount * 2.17; // Bi-weekly
        if ($avgDays <= 31) return $amount; // Monthly
        if ($avgDays <= 93) return $amount / 3; // Quarterly
        if ($avgDays <= 183) return $amount / 6; // Semi-annual
        return $amount / 12; // Annual
    }

    /**
     * Generate data hash for caching
     */
    private function generateDataHash(int $userId): string {
        $lastTransaction = $this->db->queryOne(
            "SELECT MAX(updated_at) as last_update FROM transactions WHERE user_id = ?",
            [$userId]
        );

        $lastUpdate = $lastTransaction['last_update'] ?? date('Y-m-d H:i:s');
        return md5($userId . $lastUpdate);
    }
}