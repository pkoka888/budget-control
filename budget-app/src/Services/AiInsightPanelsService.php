<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

class AiInsightPanelsService {
    private Database $db;
    private AnalyticsWorker $analytics;
    private LlmService $llmService;

    public function __construct(Database $db) {
        $this->db = $db;
        $this->analytics = new AnalyticsWorker($db);
        $this->llmService = new LlmService($db);
    }

    /**
     * Get all insight panels for user
     */
    public function getInsightPanels(int $userId): array {
        $panels = $this->db->query(
            "SELECT * FROM ai_insight_panels
             WHERE user_id = ? AND is_visible = 1
             ORDER BY priority DESC, created_at ASC",
            [$userId]
        );

        // Check if panels need refresh
        $panels = array_map(function($panel) {
            $panel['needs_refresh'] = $this->panelNeedsRefresh($panel);
            return $panel;
        }, $panels);

        return $panels;
    }

    /**
     * Create default insight panels for user
     */
    public function createDefaultPanels(int $userId): array {
        $defaultPanels = [
            [
                'panel_type' => 'budget_health',
                'title' => 'Budget Health Overview',
                'priority' => 'high',
                'refresh_interval' => 3600 // 1 hour
            ],
            [
                'panel_type' => 'savings_runway',
                'title' => 'Emergency Fund Status',
                'priority' => 'high',
                'refresh_interval' => 3600
            ],
            [
                'panel_type' => 'debt_tracker',
                'title' => 'Debt Management',
                'priority' => 'medium',
                'refresh_interval' => 7200 // 2 hours
            ],
            [
                'panel_type' => 'cash_flow',
                'title' => 'Cash Flow Forecast',
                'priority' => 'medium',
                'refresh_interval' => 7200
            ],
            [
                'panel_type' => 'career_uplift',
                'title' => 'Career Opportunities',
                'priority' => 'low',
                'refresh_interval' => 86400 // 24 hours
            ]
        ];

        $createdPanels = [];
        foreach ($defaultPanels as $panelData) {
            $panelId = $this->db->insert('ai_insight_panels', array_merge($panelData, [
                'user_id' => $userId,
                'is_visible' => 1,
                'last_updated' => null
            ]));

            $createdPanels[] = array_merge($panelData, ['id' => $panelId]);
        }

        return $createdPanels;
    }

    /**
     * Refresh insight panel content
     */
    public function refreshPanel(int $panelId): array {
        $panel = $this->db->queryOne(
            "SELECT * FROM ai_insight_panels WHERE id = ?",
            [$panelId]
        );

        if (!$panel) {
            return ['success' => false, 'error' => 'Panel not found'];
        }

        $content = $this->generatePanelContent($panel['user_id'], $panel['panel_type']);

        $this->db->update('ai_insight_panels',
            [
                'content' => json_encode($content),
                'last_updated' => date('Y-m-d H:i:s')
            ],
            ['id' => $panelId]
        );

        return [
            'success' => true,
            'panel_id' => $panelId,
            'content' => $content,
            'refreshed_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Generate content for specific panel type
     */
    private function generatePanelContent(int $userId, string $panelType): array {
        switch ($panelType) {
            case 'budget_health':
                return $this->generateBudgetHealthContent($userId);
            case 'savings_runway':
                return $this->generateSavingsRunwayContent($userId);
            case 'debt_tracker':
                return $this->generateDebtTrackerContent($userId);
            case 'cash_flow':
                return $this->generateCashFlowContent($userId);
            case 'career_uplift':
                return $this->generateCareerUpliftContent($userId);
            default:
                return ['error' => 'Unknown panel type'];
        }
    }

    /**
     * Generate budget health panel content
     */
    private function generateBudgetHealthContent(int $userId): array {
        $budgetHealth = $this->analytics->calculateBudgetHealth($userId);

        // Try to get AI insights
        $aiInsight = $this->llmService->generateInsight($userId, 'budget_analyzer');

        return [
            'metrics' => [
                'budget_compliance' => $budgetHealth['budget_compliance'],
                'savings_rate' => $budgetHealth['savings_rate'],
                'expense_stability' => $budgetHealth['expense_stability'],
                'health_score' => $budgetHealth['health_score']
            ],
            'status' => $this->getBudgetHealthStatus($budgetHealth),
            'ai_insight' => $aiInsight['success'] ? $aiInsight['response'] : null,
            'recommendations' => $this->generateBudgetRecommendations($budgetHealth),
            'last_calculated' => $budgetHealth['calculated_at']
        ];
    }

    /**
     * Generate savings runway panel content
     */
    private function generateSavingsRunwayContent(int $userId): array {
        $runway = $this->analytics->calculateSavingsRunway($userId);

        return [
            'metrics' => [
                'current_emergency_fund' => $runway['emergency_fund_current'],
                'target_emergency_fund' => $runway['emergency_fund_target'],
                'runway_months' => $runway['runway_months'],
                'monthly_expenses' => $runway['monthly_expenses'],
                'savings_trend' => $runway['savings_trend']
            ],
            'status' => $runway['runway_status'],
            'progress_percentage' => $runway['emergency_fund_target'] > 0
                ? min(100, ($runway['emergency_fund_current'] / $runway['emergency_fund_target']) * 100)
                : 0,
            'recommendations' => $this->generateRunwayRecommendations($runway),
            'last_calculated' => $runway['calculated_at']
        ];
    }

    /**
     * Generate debt tracker panel content
     */
    private function generateDebtTrackerContent(int $userId): array {
        $debt = $this->analytics->calculateDebtTracking($userId);

        // Try to get AI insights
        $aiInsight = $this->llmService->generateInsight($userId, 'savings_debt_coach');

        return [
            'metrics' => [
                'total_debt' => $debt['total_debt'],
                'monthly_min_payments' => $debt['monthly_min_payments'],
                'debt_to_income_ratio' => $debt['debt_to_income_ratio'],
                'debt_count' => $debt['debt_count']
            ],
            'status' => $this->getDebtStatus($debt),
            'ai_insight' => $aiInsight['success'] ? $aiInsight['response'] : null,
            'recommendations' => $this->generateDebtRecommendations($debt),
            'last_calculated' => $debt['calculated_at']
        ];
    }

    /**
     * Generate cash flow panel content
     */
    private function generateCashFlowContent(int $userId): array {
        $forecast = $this->analytics->generateCashFlowForecast($userId, 3);

        // Try to get AI insights
        $aiInsight = $this->llmService->generateInsight($userId, 'cash_flow_forecaster');

        return [
            'forecast' => $forecast['forecast'],
            'assumptions' => $forecast['assumptions'],
            'ai_insight' => $aiInsight['success'] ? $aiInsight['response'] : null,
            'risk_assessment' => $this->assessCashFlowRisks($forecast['forecast']),
            'last_calculated' => $forecast['generated_at']
        ];
    }

    /**
     * Generate career uplift panel content
     */
    private function generateCareerUpliftContent(int $userId): array {
        // Try to get AI insights
        $aiInsight = $this->llmService->generateInsight($userId, 'career_uplift_advisor');

        return [
            'ai_insight' => $aiInsight['success'] ? $aiInsight['response'] : null,
            'last_updated' => $aiInsight['generated_at'] ?? date('Y-m-d H:i:s')
        ];
    }

    /**
     * Check if panel needs refresh
     */
    private function panelNeedsRefresh(array $panel): bool {
        if (!$panel['last_updated']) return true;

        $lastUpdate = strtotime($panel['last_updated']);
        $refreshInterval = $panel['refresh_interval'] ?? 3600;

        return (time() - $lastUpdate) > $refreshInterval;
    }

    /**
     * Get budget health status
     */
    private function getBudgetHealthStatus(array $budgetHealth): string {
        $score = $budgetHealth['health_score'];
        if ($score >= 80) return 'excellent';
        if ($score >= 60) return 'good';
        if ($score >= 40) return 'fair';
        return 'poor';
    }

    /**
     * Get debt status
     */
    private function getDebtStatus(array $debt): string {
        $ratio = $debt['debt_to_income_ratio'];
        if ($ratio < 20) return 'excellent';
        if ($ratio < 30) return 'good';
        if ($ratio < 40) return 'fair';
        return 'concerning';
    }

    /**
     * Generate budget recommendations
     */
    private function generateBudgetRecommendations(array $budgetHealth): array {
        $recommendations = [];

        if ($budgetHealth['budget_compliance'] > 80) {
            $recommendations[] = 'Consider reducing expenses in over-budget categories';
        }

        if ($budgetHealth['savings_rate'] < 15) {
            $recommendations[] = 'Aim to increase savings rate to at least 15%';
        }

        if ($budgetHealth['expense_stability'] > 30) {
            $recommendations[] = 'Work on stabilizing monthly expenses';
        }

        return $recommendations;
    }

    /**
     * Generate runway recommendations
     */
    private function generateRunwayRecommendations(array $runway): array {
        $recommendations = [];

        if ($runway['runway_months'] < 3) {
            $recommendations[] = 'Build emergency fund to cover at least 3 months of expenses';
        }

        if ($runway['savings_trend'] < 0) {
            $recommendations[] = 'Savings trend is declining - review expense reduction strategies';
        }

        return $recommendations;
    }

    /**
     * Generate debt recommendations
     */
    private function generateDebtRecommendations(array $debt): array {
        $recommendations = [];

        if ($debt['debt_to_income_ratio'] > 35) {
            $recommendations[] = 'High debt-to-income ratio - consider debt consolidation';
        }

        if ($debt['total_debt'] > 0) {
            $recommendations[] = 'Focus on high-interest debt payoff using avalanche method';
        }

        return $recommendations;
    }

    /**
     * Assess cash flow risks
     */
    private function assessCashFlowRisks(array $forecast): array {
        $risks = [];

        foreach ($forecast as $month) {
            if ($month['projected_net'] < 0) {
                $risks[] = "Negative cash flow projected for {$month['month']}";
            }
        }

        $endBalance = end($forecast)['cumulative_net'];
        if ($endBalance < 0) {
            $risks[] = 'Forecast shows negative cumulative balance';
        }

        return [
            'risk_level' => count($risks) > 2 ? 'high' : (count($risks) > 0 ? 'medium' : 'low'),
            'issues' => $risks
        ];
    }

    /**
     * Update panel visibility
     */
    public function updatePanelVisibility(int $panelId, bool $isVisible): bool {
        $this->db->update('ai_insight_panels',
            ['is_visible' => $isVisible ? 1 : 0],
            ['id' => $panelId]
        );
        return true;
    }

    /**
     * Refresh all panels for user
     */
    public function refreshAllPanels(int $userId): array {
        $panels = $this->db->query(
            "SELECT id, panel_type FROM ai_insight_panels WHERE user_id = ?",
            [$userId]
        );

        $results = [];
        foreach ($panels as $panel) {
            $result = $this->refreshPanel($panel['id']);
            $results[] = $result;
        }

        return $results;
    }
}