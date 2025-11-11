<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

class FinancialAnalyzer {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Calculate monthly financial summary
     */
    public function getMonthSummary(int $userId, string $month): array {
        // Parse month (YYYY-MM)
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        // Income
        $income = $this->db->queryOne(
            "SELECT SUM(amount) as total FROM transactions
             WHERE user_id = ? AND type = 'income' AND date BETWEEN ? AND ?",
            [$userId, $monthStart, $monthEnd]
        );

        // Expenses
        $expenses = $this->db->queryOne(
            "SELECT SUM(amount) as total FROM transactions
             WHERE user_id = ? AND type = 'expense' AND date BETWEEN ? AND ?",
            [$userId, $monthStart, $monthEnd]
        );

        $totalIncome = $income['total'] ?? 0;
        $totalExpenses = $expenses['total'] ?? 0;

        // Calculate metrics
        $netIncome = $totalIncome - $totalExpenses;
        $savingsRate = $totalIncome > 0 ? ($netIncome / $totalIncome) * 100 : 0;

        return [
            'month' => $month,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_income' => $netIncome,
            'savings_rate' => round($savingsRate, 2),
            'transaction_count' => $this->getTransactionCount($userId, $monthStart, $monthEnd)
        ];
    }

    /**
     * Get expenses by category for period
     */
    public function getExpensesByCategory(int $userId, string $startDate, string $endDate): array {
        $results = $this->db->query(
            "SELECT
                c.id, c.name, c.color, c.icon,
                SUM(t.amount) as total,
                COUNT(t.id) as count
             FROM transactions t
             LEFT JOIN categories c ON t.category_id = c.id
             WHERE t.user_id = ? AND t.type = 'expense'
                   AND t.date BETWEEN ? AND ?
             GROUP BY c.id
             ORDER BY total DESC",
            [$userId, $startDate, $endDate]
        );

        // Calculate total and percentages
        $totalExpenses = array_sum(array_column($results, 'total'));

        if ($totalExpenses > 0) {
            foreach ($results as &$result) {
                $result['percentage'] = ($result['total'] / $totalExpenses) * 100;
            }
        } else {
            foreach ($results as &$result) {
                $result['percentage'] = 0;
            }
        }

        return $results;
    }

    /**
     * Get income by source for period
     */
    public function getIncomeBySource(int $userId, string $startDate, string $endDate): array {
        $results = $this->db->query(
            "SELECT
                t.description as source,
                SUM(t.amount) as total,
                COUNT(t.id) as count
             FROM transactions t
             WHERE t.user_id = ? AND t.type = 'income'
                   AND t.date BETWEEN ? AND ?
             GROUP BY t.description
             ORDER BY total DESC",
            [$userId, $startDate, $endDate]
        );

        return $results;
    }

    /**
     * Get spending trend (daily average)
     */
    public function getSpendingTrend(int $userId, int $days = 30): array {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        $endDate = date('Y-m-d');

        $results = $this->db->query(
            "SELECT
                DATE(date) as day,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expenses,
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as net
             FROM transactions
             WHERE user_id = ? AND date BETWEEN ? AND ?
             GROUP BY DATE(date)
             ORDER BY date ASC",
            [$userId, $startDate, $endDate]
        );

        return $results;
    }

    /**
     * Calculate net worth
     */
    public function getNetWorth(int $userId): array {
        // Assets
        $assets = $this->db->queryOne(
            "SELECT SUM(balance) as total FROM accounts
             WHERE user_id = ? AND type IN ('checking', 'savings', 'investment', 'crypto')",
            [$userId]
        );

        // Liabilities
        $liabilities = $this->db->queryOne(
            "SELECT SUM(balance) as total FROM accounts
             WHERE user_id = ? AND type IN ('credit_card', 'loan', 'mortgage')",
            [$userId]
        );

        $totalAssets = $assets['total'] ?? 0;
        $totalLiabilities = abs($liabilities['total'] ?? 0);

        return [
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'net_worth' => $totalAssets - $totalLiabilities,
            'assets_by_type' => $this->getAssetBreakdown($userId),
            'liabilities_by_type' => $this->getLiabilityBreakdown($userId)
        ];
    }

    private function getAssetBreakdown(int $userId): array {
        return $this->db->query(
            "SELECT type, SUM(balance) as total, COUNT(*) as count
             FROM accounts
             WHERE user_id = ? AND type IN ('checking', 'savings', 'investment', 'crypto')
             GROUP BY type",
            [$userId]
        );
    }

    private function getLiabilityBreakdown(int $userId): array {
        return $this->db->query(
            "SELECT type, ABS(SUM(balance)) as total, COUNT(*) as count
             FROM accounts
             WHERE user_id = ? AND type IN ('credit_card', 'loan', 'mortgage')
             GROUP BY type",
            [$userId]
        );
    }

    /**
     * Get budget vs actual
     */
    public function getBudgetAnalysis(int $userId, string $month): array {
        $budgets = $this->db->query(
            "SELECT
                b.id, b.category_id, c.name, c.color, c.icon,
                b.amount as budget_amount,
                COALESCE(SUM(t.amount), 0) as actual_amount
             FROM budgets b
             LEFT JOIN categories c ON b.category_id = c.id
             LEFT JOIN transactions t ON t.category_id = b.category_id
                AND t.user_id = b.user_id
                AND t.type = 'expense'
                AND STRFTIME('%Y-%m', t.date) = ?
             WHERE b.user_id = ? AND b.month = ?
             GROUP BY b.id",
            [$month, $userId, $month]
        );

        // Calculate percentages
        foreach ($budgets as &$budget) {
            $budget['percentage'] = $budget['budget_amount'] > 0
                ? round(($budget['actual_amount'] / $budget['budget_amount']) * 100, 2)
                : 0;
            $budget['remaining'] = $budget['budget_amount'] - $budget['actual_amount'];
            $budget['is_over_budget'] = $budget['remaining'] < 0;
        }

        return $budgets;
    }

    /**
     * Detect spending anomalies
     */
    public function detectAnomalies(int $userId, int $threshold = 150): array {
        // Get average daily spending
        $avg = $this->db->queryOne(
            "SELECT AVG(daily_total) as avg_daily FROM (
                SELECT DATE(date) as day, SUM(amount) as daily_total
                FROM transactions
                WHERE user_id = ? AND type = 'expense'
                GROUP BY DATE(date)
                LIMIT 90
            )",
            [$userId]
        );

        $avgDaily = $avg['avg_daily'] ?? 0;
        $anomalyThreshold = ($avgDaily * $threshold) / 100;

        $anomalies = $this->db->query(
            "SELECT DATE(date) as day, SUM(amount) as daily_total, COUNT(*) as count
             FROM transactions
             WHERE user_id = ? AND type = 'expense'
             GROUP BY DATE(date)
             HAVING daily_total > ?
             ORDER BY daily_total DESC
             LIMIT 10",
            [$userId, $anomalyThreshold]
        );

        return [
            'average_daily_spending' => round($avgDaily, 2),
            'anomaly_threshold' => round($anomalyThreshold, 2),
            'anomalies' => $anomalies
        ];
    }

    /**
     * Calculate financial health score (0-100)
     */
    public function getHealthScore(int $userId): array {
        $netWorth = $this->getNetWorth($userId);
        $thisMonth = $this->getMonthSummary($userId, date('Y-m'));
        $savingsGoal = 20; // 20% savings rate target

        // Factors
        $debtRatio = $netWorth['total_assets'] > 0
            ? ($netWorth['total_liabilities'] / $netWorth['total_assets']) * 100
            : 0;

        $savingsScore = min($thisMonth['savings_rate'] / $savingsGoal * 100, 100);
        $debtScore = max(100 - $debtRatio, 0);

        $overallScore = (
            ($savingsScore * 0.4) +
            ($debtScore * 0.4) +
            (min($netWorth['net_worth'] > 0 ? 100 : 50, 100) * 0.2)
        );

        return [
            'overall_score' => round($overallScore, 2),
            'savings_score' => round($savingsScore, 2),
            'debt_score' => round($debtScore, 2),
            'net_worth_score' => round(min($netWorth['net_worth'] > 0 ? 100 : 50, 100), 2),
            'debt_ratio' => round($debtRatio, 2),
            'recommendations' => $this->generateRecommendations($thisMonth, $netWorth)
        ];
    }

    private function generateRecommendations(array $monthData, array $netWorth): array {
        $recommendations = [];

        if ($monthData['savings_rate'] < 10) {
            $recommendations[] = 'Vaše sazba úspor je nižší než doporučených 20%. Zvažte snížení diskrečních výdajů.';
        }

        if ($netWorth['total_liabilities'] > $netWorth['total_assets']) {
            $recommendations[] = 'Vaše závazky překračují aktiva. Zaměřte se na splácení dluhů.';
        }

        if ($monthData['total_expenses'] > $monthData['total_income']) {
            $recommendations[] = 'Tento měsíc jste utratili více, než jste vydělali. Zkontrolujte své výdaje.';
        }

        return $recommendations;
    }

    /**
     * Get monthly income average
     */
    public function getMonthlyIncome(int $userId, int $months = 3): float {
        $result = $this->db->queryOne(
            "SELECT AVG(monthly_total) as avg_income FROM (
                SELECT STRFTIME('%Y-%m', date) as month, SUM(amount) as monthly_total
                FROM transactions
                WHERE user_id = ? AND type = 'income'
                AND date >= date('now', '-{$months} months')
                GROUP BY STRFTIME('%Y-%m', date)
            )",
            [$userId]
        );

        return $result['avg_income'] ?? 0;
    }
    private function getTransactionCount(int $userId, string $startDate, string $endDate): int {
        $result = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM transactions
             WHERE user_id = ? AND date BETWEEN ? AND ?",
            [$userId, $startDate, $endDate]
        );

        return $result['count'] ?? 0;
    }
}
