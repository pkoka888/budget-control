<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

class AutomationService {
    private Database $db;
    private AiRecommendations $aiRecommendations;
    private NotificationService $notificationService;
    private FinancialAnalyzer $financialAnalyzer;
    private LlmService $llmService;

    /**
     * Map automation action types to prompt templates
     */
    private array $llmActionMap = [
        'llm_prompt' => null,
        'llm_budget' => 'budget_analyzer',
        'llm_cash_flow' => 'cash_flow_forecaster',
        'llm_savings_debt' => 'savings_debt_coach',
        'career_uplift' => 'career_uplift_advisor',
        'income_strategy' => 'income_strategy',
        'resilience_plan' => 'resilience_roadmap',
        'crisis_mode' => 'crisis_mode'
    ];

    public function __construct(Database $db) {
        $this->db = $db;
        $this->aiRecommendations = new AiRecommendations($db);
        $this->notificationService = new NotificationService($db);
        $this->financialAnalyzer = new FinancialAnalyzer($db);
        $this->llmService = new LlmService($db);
    }

    /**
     * Execute automated actions for a user
     */
    public function executeAutomatedActions(int $userId): array {
        $results = [];
        $actions = $this->getDueAutomatedActions($userId);

        foreach ($actions as $action) {
            try {
                $result = $this->executeAction($action);
                $results[] = [
                    'action_id' => $action['id'],
                    'action_type' => $action['action_type'],
                    'success' => true,
                    'result' => $result
                ];

                // Update execution stats
                $this->updateActionStats($action['id'], true);

            } catch (\Exception $e) {
                $results[] = [
                    'action_id' => $action['id'],
                    'action_type' => $action['action_type'],
                    'success' => false,
                    'error' => $e->getMessage()
                ];

                // Update execution stats
                $this->updateActionStats($action['id'], false);
            }
        }

        return $results;
    }

    /**
     * Get automated actions that are due for execution
     */
    private function getDueAutomatedActions(int $userId): array {
        return $this->db->query(
            "SELECT * FROM automated_actions
             WHERE user_id = ? AND is_active = 1
             AND (next_execution_at IS NULL OR next_execution_at <= datetime('now'))
             ORDER BY next_execution_at ASC",
            [$userId]
        );
    }

    /**
     * Execute a specific automated action
     */
    private function executeAction(array $action): array {
        switch ($action['action_type']) {
            case 'budget_generation':
                return $this->generateAutoBudget($action);

            case 'subscription_check':
                return $this->checkSubscriptions($action);

            case 'debt_reminder':
                return $this->sendDebtReminders($action);

            case 'benefit_lookup':
                return $this->lookupBenefits($action);

            case 'llm_prompt':
            case 'llm_budget':
            case 'llm_cash_flow':
            case 'llm_savings_debt':
            case 'career_uplift':
            case 'income_strategy':
            case 'resilience_plan':
            case 'crisis_mode':
                return $this->executeLlmAction($action);

            default:
                throw new \Exception("Unknown action type: {$action['action_type']}");
        }
    }

    /**
     * Auto-generate budget based on spending patterns
     */
    private function generateAutoBudget(array $action): array {
        $userId = $action['user_id'];
        $actionData = json_decode($action['action_data'], true);

        // Analyze last 3 months of spending
        $spendingPatterns = $this->analyzeSpendingPatterns($userId);

        // Generate budget recommendations
        $budgetRecommendations = $this->createBudgetFromPatterns($spendingPatterns, $actionData);

        // Store as AI recommendations
        $recommendations = [];
        foreach ($budgetRecommendations as $rec) {
            $recommendations[] = [
                'type' => 'budget_suggestion',
                'title' => $rec['title'],
                'description' => $rec['description'],
                'priority' => $rec['priority']
            ];
        }

        $this->persistRecommendations($userId, $recommendations, ['budget_suggestion']);

        return [
            'recommendations_created' => count($recommendations),
            'categories_analyzed' => count($spendingPatterns)
        ];
    }

    /**
     * Check for subscription renewals and unusual charges
     */
    private function checkSubscriptions(array $action): array {
        $userId = $action['user_id'];

        // Find recurring transactions that might be subscriptions
        $potentialSubscriptions = $this->db->query(
            "SELECT description, AVG(amount) as avg_amount, COUNT(*) as frequency,
                    MAX(date) as last_date, category_id
             FROM transactions
             WHERE user_id = ? AND type = 'expense'
             AND date >= date('now', '-90 days')
             GROUP BY description
             HAVING COUNT(*) >= 3
             ORDER BY avg_amount DESC",
            [$userId]
        );

        $alerts = [];
        foreach ($potentialSubscriptions as $sub) {
            // Check for unusual amounts
            $recentAmount = $this->getRecentTransactionAmount($userId, $sub['description']);
            $variance = abs($recentAmount - $sub['avg_amount']) / $sub['avg_amount'];

            if ($variance > 0.1) { // 10% variance
                $alerts[] = [
                    'type' => 'subscription_alert',
                    'title' => "Neobvyklá platba: {$sub['description']}",
                    'description' => "Poslední platba byla " . round($variance * 100) . "% jiná než obvykle.",
                    'priority' => 'medium'
                ];
            }
        }

        $this->persistRecommendations($userId, $alerts, ['subscription_alert']);

        return [
            'subscriptions_checked' => count($potentialSubscriptions),
            'alerts_created' => count($alerts)
        ];
    }

    /**
     * Send debt payment reminders
     */
    private function sendDebtReminders(array $action): array {
        $userId = $action['user_id'];

        // Find accounts with negative balances (debts)
        $debts = $this->db->query(
            "SELECT * FROM accounts
             WHERE user_id = ? AND balance < 0 AND is_active = 1",
            [$userId]
        );

        $reminders = [];
        foreach ($debts as $debt) {
            $reminders[] = [
                'type' => 'debt_reminder',
                'title' => "Připomínka splátky: {$debt['name']}",
                'description' => "Máte dluh " . abs($debt['balance']) . " Kč na účtu {$debt['name']}.",
                'priority' => 'high'
            ];
        }

        $this->persistRecommendations($userId, $reminders, ['debt_reminder']);

        return [
            'debts_found' => count($debts),
            'reminders_created' => count($reminders)
        ];
    }

    /**
     * Look up Czech benefits the user might be eligible for
     */
    private function lookupBenefits(array $action): array {
        $userId = $action['user_id'];

        // Get user financial data to determine eligibility
        $netWorth = $this->financialAnalyzer->getNetWorth($userId);
        $monthlyIncome = $this->financialAnalyzer->getMonthlyIncome($userId);

        // Get potential benefits based on financial situation
        $potentialBenefits = $this->getPotentialBenefits($netWorth, $monthlyIncome);

        $recommendations = [];
        foreach ($potentialBenefits as $benefit) {
            $recommendations[] = [
                'type' => 'benefit_suggestion',
                'title' => $benefit['name'],
                'description' => $benefit['description'] . " Kontakt: {$benefit['contact_info']}",
                'priority' => 'medium'
            ];
        }

        $this->persistRecommendations($userId, $recommendations, ['benefit_suggestion']);

        return [
            'benefits_checked' => count($potentialBenefits),
            'recommendations_created' => count($recommendations)
        ];
    }

    /**
     * Execute LLM-driven actions (budget insights, career plans, etc.)
     */
    private function executeLlmAction(array $action): array {
        $userId = $action['user_id'];
        $actionData = $this->decodeActionData($action['action_data'] ?? null);
        $promptType = $this->resolvePromptType($action['action_type'], $actionData);
        $userContext = $actionData['user_context'] ?? [];

        $response = $this->llmService->generateInsight($userId, $promptType, $userContext);

        if (!$response['success']) {
            $error = $response['error'] ?? 'LLM insight generation failed';
            throw new \RuntimeException($error);
        }

        $title = $this->getPromptTitle($promptType);
        $recommendationType = 'llm_' . $promptType;
        $this->persistRecommendations($userId, [[
            'type' => $recommendationType,
            'title' => $title,
            'description' => trim($response['response']),
            'priority' => $this->getPromptPriority($promptType)
        ]], [$recommendationType]);

        $generatedAt = $response['generated_at'] ?? date('Y-m-d H:i:s');
        $this->notificationService->createNotification(
            $userId,
            'scenario_insight',
            $title,
            "Automatizovaný asistent připravil nový plán ({$title}).",
            [
                'prompt_type' => $promptType,
                'cached' => $response['cached'] ?? false,
                'generated_at' => $generatedAt
            ]
        );

        return [
            'prompt_type' => $promptType,
            'cached' => $response['cached'] ?? false,
            'generated_at' => $generatedAt
        ];
    }

    /**
     * Determine which prompt type should run for given action
     */
    private function resolvePromptType(string $actionType, array $actionData): string {
        if (!empty($actionData['prompt_type'])) {
            return $actionData['prompt_type'];
        }

        if (array_key_exists($actionType, $this->llmActionMap)) {
            $mapped = $this->llmActionMap[$actionType];
            if (!empty($mapped)) {
                return $mapped;
            }
        }

        throw new \InvalidArgumentException("Prompt type missing for action {$actionType}");
    }

    /**
     * Provide human-friendly titles per prompt type
     */
    private function getPromptTitle(string $promptType): string {
        return match ($promptType) {
            'budget_analyzer' => 'LLM přehled rozpočtu',
            'cash_flow_forecaster' => 'LLM cash-flow prognóza',
            'savings_debt_coach' => 'LLM kouč úspor a dluhů',
            'career_uplift_advisor' => 'LLM kariérní plán',
            'income_strategy' => 'LLM strategie příjmů',
            'resilience_roadmap' => 'LLM plán odolnosti (30-60-90)',
            'crisis_mode' => 'LLM krizový protokol',
            default => 'LLM doporučení'
        };
    }

    /**
     * Map prompt importance to priority value
     */
    private function getPromptPriority(string $promptType): string {
        return match ($promptType) {
            'crisis_mode',
            'budget_analyzer',
            'cash_flow_forecaster',
            'savings_debt_coach',
            'resilience_roadmap' => 'high',
            'career_uplift_advisor',
            'income_strategy' => 'medium',
            default => 'medium'
        };
    }

    /**
     * Decode action data safely
     */
    private function decodeActionData(?string $payload): array {
        if (empty($payload)) {
            return [];
        }

        $decoded = json_decode($payload, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Persist automation recommendations while replacing previous entries for same types
     */
    private function persistRecommendations(int $userId, array $recommendations, array $typesToReplace = []): void {
        if (empty($recommendations)) {
            return;
        }

        $uniqueTypes = array_values(array_unique(array_filter($typesToReplace)));
        if (!empty($uniqueTypes)) {
            $placeholders = implode(', ', array_fill(0, count($uniqueTypes), '?'));
            $params = array_merge([$userId], $uniqueTypes);

            $this->db->execute(
                "DELETE FROM ai_recommendations WHERE user_id = ? AND type IN ({$placeholders})",
                $params
            );
        }

        $this->aiRecommendations->appendRecommendations($userId, $recommendations);
    }

    /**
     * Analyze spending patterns for budget generation
     */
    private function analyzeSpendingPatterns(int $userId): array {
        return $this->db->query(
            "SELECT c.name, c.type, AVG(t.amount) as avg_spending,
                    COUNT(t.id) as transaction_count,
                    MAX(t.date) as last_transaction
             FROM transactions t
             JOIN categories c ON t.category_id = c.id
             WHERE t.user_id = ? AND t.type = 'expense'
             AND t.date >= date('now', '-90 days')
             AND c.is_active = 1
             GROUP BY c.id, c.name, c.type
             ORDER BY avg_spending DESC",
            [$userId]
        );
    }

    /**
     * Create budget recommendations from spending patterns
     */
    private function createBudgetFromPatterns(array $patterns, array $actionData): array {
        $recommendations = [];
        $totalSpending = array_sum(array_column($patterns, 'avg_spending'));

        foreach ($patterns as $pattern) {
            $percentage = ($pattern['avg_spending'] / $totalSpending) * 100;

            if ($percentage > 30) { // High spending category
                $recommendations[] = [
                    'title' => "Zkontrolujte rozpočet: {$pattern['name']}",
                    'description' => "Vydáváte {$percentage}% z celkových výdajů na {$pattern['name']}. Zvažte snížení.",
                    'priority' => 'high'
                ];
            } elseif ($percentage > 15) { // Moderate spending
                $recommendations[] = [
                    'title' => "Optimalizujte: {$pattern['name']}",
                    'description' => "Kategorie {$pattern['name']} tvoří {$percentage}% výdajů. Možná úspora.",
                    'priority' => 'medium'
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Get recent transaction amount for subscription checking
     */
    private function getRecentTransactionAmount(int $userId, string $description): float {
        $result = $this->db->query(
            "SELECT amount FROM transactions
             WHERE user_id = ? AND description = ? AND type = 'expense'
             ORDER BY date DESC LIMIT 1",
            [$userId, $description]
        );

        return $result[0]['amount'] ?? 0;
    }

    /**
     * Get potential Czech benefits based on financial situation
     */
    private function getPotentialBenefits(array $netWorth, float $monthlyIncome): array {
        $benefits = [];

        // Low income benefits
        if ($monthlyIncome < 15000) { // Below average Czech salary
            $benefits[] = [
                'name' => 'Příspěvek na bydlení',
                'description' => 'Můžete mít nárok na příspěvek na bydlení při nízkých příjmech.',
                'contact_info' => 'Úřad práce ČR'
            ];
        }

        // Debt relief programs
        if ($netWorth['total_liabilities'] > $netWorth['total_assets'] * 0.5) {
            $benefits[] = [
                'name' => 'Oddlužení',
                'description' => 'Při vysokém zadlužení zvažte oddlužení podle insolvenčního zákona.',
                'contact_info' => 'Insolvenční soud'
            ];
        }

        // Unemployment benefits (if income drops significantly)
        $benefits[] = [
            'name' => 'Podpora v nezaměstnanosti',
            'description' => 'Při ztrátě zaměstnání máte nárok na podporu v nezaměstnanosti.',
            'contact_info' => 'Úřad práce ČR'
        ];

        return $benefits;
    }

    /**
     * Update action execution statistics
     */
    private function updateActionStats(int $actionId, bool $success): void {
        $field = $success ? 'success_count' : 'failure_count';

        $this->db->query(
            "UPDATE automated_actions
             SET {$field} = {$field} + 1,
                 last_executed_at = datetime('now'),
                 execution_count = execution_count + 1,
                 next_execution_at = CASE
                     WHEN trigger_type = 'scheduled' THEN datetime('now', '+1 day')
                     ELSE NULL
                 END
             WHERE id = ?",
            [$actionId]
        );
    }

    /**
     * Create a new automated action
     */
    public function createAutomatedAction(int $userId, string $actionType, string $triggerType, array $triggerCondition, array $actionData): int {
        return $this->db->insert('automated_actions', [
            'user_id' => $userId,
            'action_type' => $actionType,
            'trigger_type' => $triggerType,
            'trigger_condition' => json_encode($triggerCondition),
            'action_data' => json_encode($actionData),
            'is_active' => 1,
            'next_execution_at' => $triggerType === 'scheduled' ? date('Y-m-d H:i:s', strtotime('+1 day')) : null
        ]);
    }

    /**
     * Get user's automated actions
     */
    public function getUserAutomatedActions(int $userId): array {
        return $this->db->query(
            "SELECT * FROM automated_actions
             WHERE user_id = ?
             ORDER BY created_at DESC",
            [$userId]
        );
    }
}
