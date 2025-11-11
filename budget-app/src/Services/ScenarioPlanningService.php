<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

class ScenarioPlanningService {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Generate financial scenarios based on different assumptions
     */
    public function generateScenarios(int $userId, array $parameters = []): array {
        $baseFinancials = $this->getBaseFinancialData($userId);

        $scenarios = [
            'conservative' => $this->generateConservativeScenario($baseFinancials, $parameters),
            'moderate' => $this->generateModerateScenario($baseFinancials, $parameters),
            'optimistic' => $this->generateOptimisticScenario($baseFinancials, $parameters),
            'crisis' => $this->generateCrisisScenario($baseFinancials, $parameters)
        ];

        return $scenarios;
    }

    /**
     * Get base financial data for scenario planning
     */
    private function getBaseFinancialData(int $userId): array {
        // Get current financial metrics
        $metrics = $this->db->queryOne(
            "SELECT * FROM financial_metrics WHERE user_id = ? ORDER BY calculated_at DESC LIMIT 1",
            [$userId]
        );

        // Get current accounts
        $accounts = $this->db->query(
            "SELECT * FROM accounts WHERE user_id = ? AND is_active = 1",
            [$userId]
        );

        // Get active goals
        $goals = $this->db->query(
            "SELECT * FROM goals WHERE user_id = ? AND is_active = 1",
            [$userId]
        );

        // Get recurring transactions
        $recurring = $this->db->query(
            "SELECT * FROM recurring_transactions WHERE user_id = ? AND is_active = 1",
            [$userId]
        );

        return [
            'metrics' => $metrics ?: [],
            'accounts' => $accounts,
            'goals' => $goals,
            'recurring' => $recurring
        ];
    }

    /**
     * Generate conservative scenario (worst case assumptions)
     */
    private function generateConservativeScenario(array $baseData, array $parameters): array {
        $months = $parameters['months'] ?? 12;
        $scenario = [
            'name' => 'Conservative Scenario',
            'description' => 'Worst-case assumptions with income reduction and expense increases',
            'assumptions' => [
                'income_reduction' => 0.15, // 15% income reduction
                'expense_increase' => 0.10, // 10% expense increase
                'investment_returns' => 0.02, // 2% annual returns
                'inflation' => 0.04 // 4% inflation
            ],
            'projections' => []
        ];

        $currentBalance = $this->calculateTotalBalance($baseData['accounts']);
        $monthlyIncome = ($baseData['metrics']['total_income'] ?? 0) * (1 - $scenario['assumptions']['income_reduction']);
        $monthlyExpenses = ($baseData['metrics']['total_expenses'] ?? 0) * (1 + $scenario['assumptions']['expense_increase']);

        for ($month = 1; $month <= $months; $month++) {
            $monthlySavings = $monthlyIncome - $monthlyExpenses;
            $currentBalance += $monthlySavings;

            // Apply investment returns (monthly)
            $investmentReturn = $currentBalance * ($scenario['assumptions']['investment_returns'] / 12);
            $currentBalance += $investmentReturn;

            $scenario['projections'][] = [
                'month' => $month,
                'balance' => round($currentBalance, 2),
                'monthly_income' => round($monthlyIncome, 2),
                'monthly_expenses' => round($monthlyExpenses, 2),
                'monthly_savings' => round($monthlySavings, 2),
                'investment_returns' => round($investmentReturn, 2)
            ];
        }

        return $scenario;
    }

    /**
     * Generate moderate scenario (balanced assumptions)
     */
    private function generateModerateScenario(array $baseData, array $parameters): array {
        $months = $parameters['months'] ?? 12;
        $scenario = [
            'name' => 'Moderate Scenario',
            'description' => 'Balanced assumptions with steady growth',
            'assumptions' => [
                'income_growth' => 0.03, // 3% income growth
                'expense_inflation' => 0.025, // 2.5% expense inflation
                'investment_returns' => 0.06, // 6% annual returns
                'inflation' => 0.025 // 2.5% inflation
            ],
            'projections' => []
        ];

        $currentBalance = $this->calculateTotalBalance($baseData['accounts']);
        $monthlyIncome = $baseData['metrics']['total_income'] ?? 0;
        $monthlyExpenses = $baseData['metrics']['total_expenses'] ?? 0;

        for ($month = 1; $month <= $months; $month++) {
            // Apply income growth
            $monthlyIncome *= (1 + $scenario['assumptions']['income_growth'] / 12);
            // Apply expense inflation
            $monthlyExpenses *= (1 + $scenario['assumptions']['expense_inflation'] / 12);

            $monthlySavings = $monthlyIncome - $monthlyExpenses;
            $currentBalance += $monthlySavings;

            // Apply investment returns (monthly)
            $investmentReturn = $currentBalance * ($scenario['assumptions']['investment_returns'] / 12);
            $currentBalance += $investmentReturn;

            $scenario['projections'][] = [
                'month' => $month,
                'balance' => round($currentBalance, 2),
                'monthly_income' => round($monthlyIncome, 2),
                'monthly_expenses' => round($monthlyExpenses, 2),
                'monthly_savings' => round($monthlySavings, 2),
                'investment_returns' => round($investmentReturn, 2)
            ];
        }

        return $scenario;
    }

    /**
     * Generate optimistic scenario (best case assumptions)
     */
    private function generateOptimisticScenario(array $baseData, array $parameters): array {
        $months = $parameters['months'] ?? 12;
        $scenario = [
            'name' => 'Optimistic Scenario',
            'description' => 'Best-case assumptions with strong growth and cost control',
            'assumptions' => [
                'income_growth' => 0.08, // 8% income growth
                'expense_reduction' => 0.05, // 5% expense reduction
                'investment_returns' => 0.10, // 10% annual returns
                'inflation' => 0.02 // 2% inflation
            ],
            'projections' => []
        ];

        $currentBalance = $this->calculateTotalBalance($baseData['accounts']);
        $monthlyIncome = $baseData['metrics']['total_income'] ?? 0;
        $monthlyExpenses = $baseData['metrics']['total_expenses'] ?? 0;

        for ($month = 1; $month <= $months; $month++) {
            // Apply income growth
            $monthlyIncome *= (1 + $scenario['assumptions']['income_growth'] / 12);
            // Apply expense reduction
            $monthlyExpenses *= (1 - $scenario['assumptions']['expense_reduction'] / 12);

            $monthlySavings = $monthlyIncome - $monthlyExpenses;
            $currentBalance += $monthlySavings;

            // Apply investment returns (monthly)
            $investmentReturn = $currentBalance * ($scenario['assumptions']['investment_returns'] / 12);
            $currentBalance += $investmentReturn;

            $scenario['projections'][] = [
                'month' => $month,
                'balance' => round($currentBalance, 2),
                'monthly_income' => round($monthlyIncome, 2),
                'monthly_expenses' => round($monthlyExpenses, 2),
                'monthly_savings' => round($monthlySavings, 2),
                'investment_returns' => round($investmentReturn, 2)
            ];
        }

        return $scenario;
    }

    /**
     * Generate crisis scenario (emergency situation)
     */
    private function generateCrisisScenario(array $baseData, array $parameters): array {
        $months = $parameters['months'] ?? 6; // Shorter timeframe for crisis
        $scenario = [
            'name' => 'Crisis Scenario',
            'description' => 'Emergency situation with job loss and emergency expenses',
            'assumptions' => [
                'income_reduction' => 0.80, // 80% income reduction (job loss)
                'emergency_expenses' => 50000, // One-time emergency expense (CZK)
                'expense_cuts' => 0.20, // 20% expense reduction through cuts
                'investment_returns' => -0.05, // -5% annual returns (market downturn)
                'emergency_fund_usage' => true
            ],
            'projections' => []
        ];

        $currentBalance = $this->calculateTotalBalance($baseData['accounts']);
        $monthlyIncome = ($baseData['metrics']['total_income'] ?? 0) * (1 - $scenario['assumptions']['income_reduction']);
        $monthlyExpenses = ($baseData['metrics']['total_expenses'] ?? 0) * (1 - $scenario['assumptions']['expense_cuts']);

        // Add emergency expense in month 1
        $emergencyExpense = $scenario['assumptions']['emergency_expenses'];

        for ($month = 1; $month <= $months; $month++) {
            $monthlySavings = $monthlyIncome - $monthlyExpenses;

            // Apply emergency expense in first month
            if ($month === 1) {
                $monthlySavings -= $emergencyExpense;
            }

            $currentBalance += $monthlySavings;

            // Apply investment returns/losses (monthly)
            $investmentReturn = $currentBalance * ($scenario['assumptions']['investment_returns'] / 12);
            $currentBalance += $investmentReturn;

            $scenario['projections'][] = [
                'month' => $month,
                'balance' => round($currentBalance, 2),
                'monthly_income' => round($monthlyIncome, 2),
                'monthly_expenses' => round($monthlyExpenses, 2),
                'monthly_savings' => round($monthlySavings, 2),
                'investment_returns' => round($investmentReturn, 2),
                'emergency_expense' => $month === 1 ? $emergencyExpense : 0
            ];
        }

        return $scenario;
    }

    /**
     * Calculate total balance across all accounts
     */
    private function calculateTotalBalance(array $accounts): float {
        $total = 0;
        foreach ($accounts as $account) {
            $total += $account['balance'];
        }
        return $total;
    }

    /**
     * Generate goal achievement scenarios
     */
    public function generateGoalScenarios(int $userId, int $goalId): array {
        $goal = $this->db->queryOne(
            "SELECT * FROM goals WHERE id = ? AND user_id = ? AND is_active = 1",
            [$goalId, $userId]
        );

        if (!$goal) {
            return ['error' => 'Goal not found'];
        }

        $scenarios = [
            'current_pace' => $this->calculateGoalScenario($goal, 'current'),
            'accelerated' => $this->calculateGoalScenario($goal, 'accelerated'),
            'conservative' => $this->calculateGoalScenario($goal, 'conservative'),
            'with_windfall' => $this->calculateGoalScenario($goal, 'windfall')
        ];

        return $scenarios;
    }

    /**
     * Calculate goal achievement scenario
     */
    private function calculateGoalScenario(array $goal, string $type): array {
        $remaining = $goal['target_amount'] - $goal['current_amount'];
        $monthlyContribution = 1000; // Default assumption

        switch ($type) {
            case 'current':
                $scenario = [
                    'name' => 'Current Pace',
                    'description' => 'Continue at current monthly contribution rate',
                    'monthly_contribution' => $monthlyContribution,
                    'months_to_complete' => ceil($remaining / $monthlyContribution),
                    'completion_date' => date('Y-m-d', strtotime("+".ceil($remaining / $monthlyContribution)." months"))
                ];
                break;

            case 'accelerated':
                $monthlyContribution *= 1.5;
                $scenario = [
                    'name' => 'Accelerated Pace',
                    'description' => 'Increase monthly contributions by 50%',
                    'monthly_contribution' => $monthlyContribution,
                    'months_to_complete' => ceil($remaining / $monthlyContribution),
                    'completion_date' => date('Y-m-d', strtotime("+".ceil($remaining / $monthlyContribution)." months"))
                ];
                break;

            case 'conservative':
                $monthlyContribution *= 0.7;
                $scenario = [
                    'name' => 'Conservative Pace',
                    'description' => 'Reduce monthly contributions by 30%',
                    'monthly_contribution' => $monthlyContribution,
                    'months_to_complete' => ceil($remaining / $monthlyContribution),
                    'completion_date' => date('Y-m-d', strtotime("+".ceil($remaining / $monthlyContribution)." months"))
                ];
                break;

            case 'windfall':
                $windfallAmount = min($remaining * 0.5, 50000); // Assume 50k CZK windfall or half remaining
                $remainingAfterWindfall = $remaining - $windfallAmount;
                $scenario = [
                    'name' => 'With Windfall',
                    'description' => 'Includes one-time windfall contribution',
                    'monthly_contribution' => $monthlyContribution,
                    'windfall_amount' => $windfallAmount,
                    'months_to_complete' => ceil($remainingAfterWindfall / $monthlyContribution),
                    'completion_date' => date('Y-m-d', strtotime("+".ceil($remainingAfterWindfall / $monthlyContribution)." months"))
                ];
                break;
        }

        return $scenario;
    }

    /**
     * Generate retirement planning scenarios
     */
    public function generateRetirementScenarios(int $userId, array $parameters = []): array {
        $currentAge = $parameters['current_age'] ?? 35;
        $retirementAge = $parameters['retirement_age'] ?? 65;
        $currentSavings = $parameters['current_savings'] ?? 0;
        $monthlyContribution = $parameters['monthly_contribution'] ?? 5000;
        $expectedReturn = $parameters['expected_return'] ?? 0.06; // 6% annual

        $yearsToRetirement = $retirementAge - $currentAge;
        $monthsToRetirement = $yearsToRetirement * 12;

        $scenarios = [
            'conservative' => $this->calculateRetirementProjection($currentSavings, $monthlyContribution, $monthsToRetirement, 0.03, 0.02),
            'moderate' => $this->calculateRetirementProjection($currentSavings, $monthlyContribution, $monthsToRetirement, 0.06, 0.025),
            'aggressive' => $this->calculateRetirementProjection($currentSavings, $monthlyContribution, $monthsToRetirement, 0.08, 0.03)
        ];

        return $scenarios;
    }

    /**
     * Calculate retirement projection
     */
    private function calculateRetirementProjection(float $currentSavings, float $monthlyContribution, int $months, float $returnRate, float $inflationRate): array {
        $balance = $currentSavings;
        $totalContributions = 0;

        for ($month = 1; $month <= $months; $month++) {
            $balance += $monthlyContribution;
            $totalContributions += $monthlyContribution;

            // Apply monthly investment return
            $monthlyReturn = $balance * ($returnRate / 12);
            $balance += $monthlyReturn;

            // Adjust for inflation (reduce purchasing power)
            $balance *= (1 - $inflationRate / 12);
        }

        return [
            'final_balance' => round($balance, 2),
            'total_contributions' => round($totalContributions, 2),
            'investment_gains' => round($balance - $currentSavings - $totalContributions, 2),
            'monthly_pension' => round($balance * 0.04 / 12, 2), // 4% safe withdrawal rate
            'assumptions' => [
                'annual_return' => $returnRate * 100 . '%',
                'annual_inflation' => $inflationRate * 100 . '%'
            ]
        ];
    }
}