<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

class AnalyticsWorker {
    private Database $db;
    private FinancialAnalyzer $analyzer;

    public function __construct(Database $db) {
        $this->db = $db;
        $this->analyzer = new FinancialAnalyzer($db);
    }

    /**
     * Calculate budget health metrics
     */
    public function calculateBudgetHealth(int $userId): array {
        $currentMonth = date('Y-m');
        $monthSummary = $this->analyzer->getMonthSummary($userId, $currentMonth);
        $budgetAnalysis = $this->analyzer->getBudgetAnalysis($userId, $currentMonth);
        $healthScore = $this->analyzer->getHealthScore($userId);

        // Calculate budget compliance
        $totalBudgeted = array_sum(array_column($budgetAnalysis, 'budget_amount'));
        $totalSpent = array_sum(array_column($budgetAnalysis, 'actual_amount'));
        $overBudgetCategories = count(array_filter($budgetAnalysis, fn($b) => $b['is_over_budget']));

        $budgetCompliance = $totalBudgeted > 0 ? (($totalBudgeted - $totalSpent) / $totalBudgeted) * 100 : 100;

        // Calculate expense stability (coefficient of variation)
        $monthlyExpenses = $this->getMonthlyExpenses($userId, 6);
        $expenseStability = $this->calculateStability($monthlyExpenses);

        return [
            'user_id' => $userId,
            'month' => $currentMonth,
            'budget_compliance' => round(max(0, $budgetCompliance), 2),
            'over_budget_categories' => $overBudgetCategories,
            'expense_stability' => round($expenseStability, 2),
            'health_score' => $healthScore['overall_score'],
            'savings_rate' => $monthSummary['savings_rate'],
            'debt_ratio' => $healthScore['debt_ratio'],
            'calculated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Calculate savings runway analysis
     */
    public function calculateSavingsRunway(int $userId): array {
        $currentMonth = date('Y-m');
        $monthSummary = $this->analyzer->getMonthSummary($userId, $currentMonth);
        $netWorth = $this->analyzer->getNetWorth($userId);

        // Get emergency fund goal (3-6 months of expenses)
        $monthlyExpenses = $monthSummary['total_expenses'];
        $emergencyFundTarget = $monthlyExpenses * 6; // 6 months target

        // Calculate current emergency fund (savings + checking - debts)
        $liquidAssets = $this->getLiquidAssets($userId);
        $emergencyFund = max(0, $liquidAssets - $netWorth['total_liabilities']);

        // Calculate runway in months
        $runwayMonths = $monthlyExpenses > 0 ? $emergencyFund / $monthlyExpenses : 0;

        // Calculate savings rate trend
        $savingsTrend = $this->calculateSavingsTrend($userId, 6);

        return [
            'user_id' => $userId,
            'emergency_fund_current' => round($emergencyFund, 2),
            'emergency_fund_target' => round($emergencyFundTarget, 2),
            'runway_months' => round($runwayMonths, 1),
            'monthly_expenses' => round($monthlyExpenses, 2),
            'savings_trend' => round($savingsTrend, 2),
            'runway_status' => $this->getRunwayStatus($runwayMonths),
            'calculated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Calculate debt tracking metrics
     */
    public function calculateDebtTracking(int $userId): array {
        $netWorth = $this->analyzer->getNetWorth($userId);

        // Get debt details
        $debts = $this->db->query(
            "SELECT a.name, a.balance, a.type,
                    CASE WHEN a.type = 'credit_card' THEN 0.25 ELSE 0.05 END as estimated_rate
             FROM accounts a
             WHERE a.user_id = ? AND a.type IN ('credit_card', 'loan', 'mortgage') AND a.balance < 0",
            [$userId]
        );

        $totalDebt = abs(array_sum(array_column($debts, 'balance')));
        $totalAssets = $netWorth['total_assets'];

        // Calculate debt-to-income ratio
        $currentMonth = date('Y-m');
        $monthSummary = $this->analyzer->getMonthSummary($userId, $currentMonth);
        $monthlyIncome = $monthSummary['total_income'];
        $dtiRatio = $monthlyIncome > 0 ? ($totalDebt / ($monthlyIncome * 12)) * 100 : 0;

        // Calculate minimum payments
        $minPayments = 0;
        foreach ($debts as $debt) {
            $balance = abs($debt['balance']);
            if ($debt['type'] === 'credit_card') {
                $minPayments += max($balance * 0.03, 500); // 3% or CZK 500 minimum
            } else {
                $minPayments += $balance * 0.01; // 1% for loans
            }
        }

        // Calculate debt freedom date (avalanche method)
        $debtFreedomDate = $this->calculateDebtFreedomDate($debts, $monthlyIncome - $monthSummary['total_expenses']);

        return [
            'user_id' => $userId,
            'total_debt' => round($totalDebt, 2),
            'debt_to_income_ratio' => round($dtiRatio, 2),
            'monthly_min_payments' => round($minPayments, 2),
            'debt_to_asset_ratio' => $totalAssets > 0 ? round(($totalDebt / $totalAssets) * 100, 2) : 0,
            'debt_freedom_date' => $debtFreedomDate,
            'debt_count' => count($debts),
            'calculated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Generate cash-flow forecast
     */
    public function generateCashFlowForecast(int $userId, int $months = 6): array {
        $currentMonth = date('Y-m');
        $forecast = [];

        // Get historical data for trends
        $historicalData = $this->getHistoricalFinancials($userId, 6);

        for ($i = 0; $i < $months; $i++) {
            $forecastMonth = date('Y-m', strtotime("+{$i} months", strtotime($currentMonth . '-01')));

            // Project income (use average with slight growth)
            $avgIncome = array_sum(array_column($historicalData, 'income')) / count($historicalData);
            $projectedIncome = $avgIncome * (1 + ($i * 0.02)); // 2% monthly growth assumption

            // Project expenses (use average with inflation)
            $avgExpenses = array_sum(array_column($historicalData, 'expenses')) / count($historicalData);
            $projectedExpenses = $avgExpenses * (1 + ($i * 0.01)); // 1% monthly inflation

            // Calculate projected balance
            $projectedNet = $projectedIncome - $projectedExpenses;
            $cumulativeNet = array_sum(array_column(array_slice($forecast, 0, $i), 'net')) + $projectedNet;

            $forecast[] = [
                'month' => $forecastMonth,
                'projected_income' => round($projectedIncome, 2),
                'projected_expenses' => round($projectedExpenses, 2),
                'projected_net' => round($projectedNet, 2),
                'cumulative_net' => round($cumulativeNet, 2),
                'confidence' => max(50, 90 - ($i * 10)) // Confidence decreases over time
            ];
        }

        return [
            'user_id' => $userId,
            'forecast_period_months' => $months,
            'forecast' => $forecast,
            'assumptions' => [
                'income_growth_rate' => 2.0, // percent per month
                'expense_inflation_rate' => 1.0, // percent per month
                'base_month' => $currentMonth
            ],
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get monthly expenses for stability calculation
     */
    private function getMonthlyExpenses(int $userId, int $months): array {
        $expenses = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $summary = $this->analyzer->getMonthSummary($userId, $month);
            $expenses[] = $summary['total_expenses'];
        }
        return $expenses;
    }

    /**
     * Calculate coefficient of variation for stability
     */
    private function calculateStability(array $values): float {
        if (empty($values)) return 0;

        $mean = array_sum($values) / count($values);
        if ($mean == 0) return 0;

        $variance = 0;
        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }
        $variance /= count($values);
        $stdDev = sqrt($variance);

        return ($stdDev / $mean) * 100; // Coefficient of variation as percentage
    }

    /**
     * Get liquid assets (checking + savings)
     */
    private function getLiquidAssets(int $userId): float {
        $result = $this->db->queryOne(
            "SELECT SUM(balance) as total FROM accounts
             WHERE user_id = ? AND type IN ('checking', 'savings')",
            [$userId]
        );
        return $result['total'] ?? 0;
    }

    /**
     * Calculate savings trend over months
     */
    private function calculateSavingsTrend(int $userId, int $months): float {
        $savingsRates = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $summary = $this->analyzer->getMonthSummary($userId, $month);
            $savingsRates[] = $summary['savings_rate'];
        }

        if (count($savingsRates) < 2) return 0;

        // Simple linear trend
        $n = count($savingsRates);
        $sumX = $n * ($n - 1) / 2;
        $sumY = array_sum($savingsRates);
        $sumXY = 0;
        $sumXX = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $i * $savingsRates[$i];
            $sumXX += $i * $i;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumXX - $sumX * $sumX);
        return $slope; // Monthly change in savings rate percentage
    }

    /**
     * Get runway status based on months
     */
    private function getRunwayStatus(float $months): string {
        if ($months >= 6) return 'excellent';
        if ($months >= 3) return 'good';
        if ($months >= 1) return 'caution';
        return 'critical';
    }

    /**
     * Get historical financial data
     */
    private function getHistoricalFinancials(int $userId, int $months): array {
        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $summary = $this->analyzer->getMonthSummary($userId, $month);
            $data[] = [
                'month' => $month,
                'income' => $summary['total_income'],
                'expenses' => $summary['total_expenses'],
                'net' => $summary['net_income']
            ];
        }
        return $data;
    }

    /**
     * Calculate debt freedom date using avalanche method
     */
    private function calculateDebtFreedomDate(array $debts, float $extraPayment): ?string {
        if (empty($debts) || $extraPayment <= 0) return null;

        // Sort debts by interest rate (avalanche method)
        usort($debts, fn($a, $b) => $b['estimated_rate'] <=> $a['estimated_rate']);

        $totalMonths = 0;
        $remainingExtra = $extraPayment;

        foreach ($debts as $debt) {
            $balance = abs($debt['balance']);
            $monthlyPayment = $balance * 0.01; // Assume 1% minimum payment

            if ($remainingExtra > 0) {
                $monthlyPayment += $remainingExtra;
                $remainingExtra = 0;
            }

            if ($monthlyPayment > 0) {
                $monthsForDebt = ceil($balance / $monthlyPayment);
                $totalMonths += $monthsForDebt;
            }
        }

        return date('Y-m-d', strtotime("+{$totalMonths} months"));
    }
}