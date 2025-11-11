<?php
namespace BudgetApp\Controllers;

use BudgetApp\Services\ScenarioPlanningService;

class ScenarioPlanningController extends BaseController {
    private ScenarioPlanningService $scenarioService;

    public function __construct($app) {
        parent::__construct($app);
        $this->scenarioService = new ScenarioPlanningService($this->db);
    }

    /**
     * Generate financial scenarios
     */
    public function generateScenarios(array $params = []): void {
        $userId = $this->getUserId();

        $parameters = [
            'months' => (int)($this->getQueryParam('months', 12)),
            'scenario_type' => $this->getQueryParam('type', 'all') // conservative, moderate, optimistic, crisis, all
        ];

        try {
            $scenarios = $this->scenarioService->generateScenarios($userId, $parameters);

            // Filter scenarios if specific type requested
            if ($parameters['scenario_type'] !== 'all' && isset($scenarios[$parameters['scenario_type']])) {
                $scenarios = [$parameters['scenario_type'] => $scenarios[$parameters['scenario_type']]];
            }

            $this->json([
                'success' => true,
                'scenarios' => $scenarios,
                'parameters' => $parameters
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to generate scenarios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate goal achievement scenarios
     */
    public function generateGoalScenarios(array $params = []): void {
        $goalId = $params['goal_id'] ?? 0;
        $userId = $this->getUserId();

        if (!$goalId) {
            $this->json(['error' => 'Goal ID is required'], 400);
            return;
        }

        try {
            $scenarios = $this->scenarioService->generateGoalScenarios($userId, $goalId);

            if (isset($scenarios['error'])) {
                $this->json(['error' => $scenarios['error']], 404);
                return;
            }

            $this->json([
                'success' => true,
                'goal_id' => $goalId,
                'scenarios' => $scenarios
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to generate goal scenarios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate retirement planning scenarios
     */
    public function generateRetirementScenarios(array $params = []): void {
        $userId = $this->getUserId();

        $parameters = [
            'current_age' => (int)($this->getQueryParam('current_age', 35)),
            'retirement_age' => (int)($this->getQueryParam('retirement_age', 65)),
            'current_savings' => (float)($this->getQueryParam('current_savings', 0)),
            'monthly_contribution' => (float)($this->getQueryParam('monthly_contribution', 5000)),
            'expected_return' => (float)($this->getQueryParam('expected_return', 0.06))
        ];

        try {
            $scenarios = $this->scenarioService->generateRetirementScenarios($userId, $parameters);

            $this->json([
                'success' => true,
                'scenarios' => $scenarios,
                'parameters' => $parameters
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to generate retirement scenarios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get scenario comparison
     */
    public function compareScenarios(array $params = []): void {
        $userId = $this->getUserId();

        $parameters = [
            'months' => (int)($this->getQueryParam('months', 12)),
            'compare_types' => explode(',', $this->getQueryParam('types', 'conservative,moderate,optimistic'))
        ];

        try {
            $allScenarios = $this->scenarioService->generateScenarios($userId, $parameters);

            $comparison = [
                'timeframe_months' => $parameters['months'],
                'scenarios' => [],
                'insights' => []
            ];

            foreach ($parameters['compare_types'] as $type) {
                if (isset($allScenarios[$type])) {
                    $scenario = $allScenarios[$type];
                    $finalBalance = end($scenario['projections'])['balance'];

                    $comparison['scenarios'][$type] = [
                        'name' => $scenario['name'],
                        'final_balance' => $finalBalance,
                        'total_savings' => array_sum(array_column($scenario['projections'], 'monthly_savings')),
                        'total_returns' => array_sum(array_column($scenario['projections'], 'investment_returns')),
                        'assumptions' => $scenario['assumptions']
                    ];
                }
            }

            // Generate insights
            $comparison['insights'] = $this->generateComparisonInsights($comparison);

            $this->json([
                'success' => true,
                'comparison' => $comparison
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to compare scenarios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate insights from scenario comparison
     */
    private function generateComparisonInsights(array $comparison): array {
        $insights = [];
        $scenarios = $comparison['scenarios'];

        if (count($scenarios) < 2) {
            return $insights;
        }

        // Find best and worst case scenarios
        $balances = array_column($scenarios, 'final_balance');
        $maxBalance = max($balances);
        $minBalance = min($balances);

        $bestScenario = array_search($maxBalance, $balances);
        $worstScenario = array_search($minBalance, $balances);

        $insights[] = [
            'type' => 'summary',
            'title' => 'Scenario Range',
            'description' => sprintf(
                'Your financial position could range from %s CZK (%s) to %s CZK (%s) over %d months.',
                number_format($minBalance, 0, ',', ' '),
                $scenarios[$worstScenario]['name'],
                number_format($maxBalance, 0, ',', ' '),
                $scenarios[$bestScenario]['name'],
                $comparison['timeframe_months']
            )
        ];

        // Calculate potential difference
        $difference = $maxBalance - $minBalance;
        $percentage = $minBalance > 0 ? ($difference / $minBalance) * 100 : 0;

        $insights[] = [
            'type' => 'opportunity',
            'title' => 'Potential Impact',
            'description' => sprintf(
                'The difference between best and worst case scenarios is %s CZK (%.1f%%).',
                number_format($difference, 0, ',', ' '),
                $percentage
            )
        ];

        // Risk assessment
        if (isset($scenarios['crisis']) && $scenarios['crisis']['final_balance'] < 0) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Crisis Risk',
                'description' => 'Crisis scenario shows negative balance. Consider building emergency fund and reducing debt.'
            ];
        }

        // Savings rate analysis
        $avgSavings = array_sum(array_column($scenarios, 'total_savings')) / count($scenarios);
        if ($avgSavings > 0) {
            $insights[] = [
                'type' => 'positive',
                'title' => 'Savings Potential',
                'description' => sprintf(
                    'Average monthly savings across scenarios: %s CZK.',
                    number_format($avgSavings / $comparison['timeframe_months'], 0, ',', ' ')
                )
            ];
        }

        return $insights;
    }

    /**
     * Save scenario as goal
     */
    public function saveScenarioAsGoal(array $params = []): void {
        $userId = $this->getUserId();

        $scenarioData = json_decode(file_get_contents('php://input'), true);

        if (!$scenarioData || !isset($scenarioData['scenario_type'], $scenarioData['target_amount'])) {
            $this->json(['error' => 'Invalid scenario data'], 400);
            return;
        }

        try {
            // Create goal based on scenario
            $goalId = $this->db->insert('goals', [
                'user_id' => $userId,
                'name' => $scenarioData['name'] ?? 'Scenario-based Goal',
                'description' => $scenarioData['description'] ?? 'Goal created from scenario planning',
                'goal_type' => $scenarioData['goal_type'] ?? 'savings',
                'target_amount' => $scenarioData['target_amount'],
                'current_amount' => $scenarioData['current_amount'] ?? 0,
                'target_date' => $scenarioData['target_date'] ?? null,
                'category' => $scenarioData['category'] ?? 'planning',
                'priority' => $scenarioData['priority'] ?? 'medium',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $this->json([
                'success' => true,
                'goal_id' => $goalId,
                'message' => 'Scenario saved as goal successfully'
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to save scenario as goal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get scenario templates
     */
    public function getScenarioTemplates(array $params = []): void {
        $templates = [
            [
                'id' => 'emergency_fund',
                'name' => 'Emergency Fund Build',
                'description' => 'Build 3-6 months of expenses as emergency fund',
                'default_months' => 12,
                'category' => 'safety'
            ],
            [
                'id' => 'debt_payoff',
                'name' => 'Debt Elimination',
                'description' => 'Pay off high-interest debt while building savings',
                'default_months' => 24,
                'category' => 'debt'
            ],
            [
                'id' => 'retirement_savings',
                'name' => 'Retirement Planning',
                'description' => 'Accelerate retirement savings with different contribution levels',
                'default_months' => 240, // 20 years
                'category' => 'retirement'
            ],
            [
                'id' => 'investment_growth',
                'name' => 'Investment Portfolio Growth',
                'description' => 'Project investment portfolio growth under different market conditions',
                'default_months' => 60,
                'category' => 'investment'
            ],
            [
                'id' => 'major_purchase',
                'name' => 'Major Purchase',
                'description' => 'Save for car, home down payment, or other major purchase',
                'default_months' => 36,
                'category' => 'purchase'
            ]
        ];

        $this->json([
            'success' => true,
            'templates' => $templates
        ]);
    }
}