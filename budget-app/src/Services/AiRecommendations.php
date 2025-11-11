<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

class AiRecommendations {
    private Database $db;
    private string $openaiApiKey;
    private string $openaiModel = 'gpt-3.5-turbo';

    public function __construct(Database $db, string $apiKey = '') {
        $this->db = $db;
        $this->openaiApiKey = $apiKey ?: $_ENV['OPENAI_API_KEY'] ?? '';
    }

    /**
     * Generate AI recommendations based on financial data
     */
    public function generateRecommendations(int $userId, int $limit = 5): array {
        if (empty($this->openaiApiKey)) {
            return $this->getLocalRecommendations($userId, $limit);
        }

        // Get user's financial data
        $analyzer = new FinancialAnalyzer($this->db);
        $thisMonth = $analyzer->getMonthSummary($userId, date('Y-m'));
        $netWorth = $analyzer->getNetWorth($userId);
        $anomalies = $analyzer->detectAnomalies($userId);
        $categories = $analyzer->getExpensesByCategory($userId, date('Y-m-01'), date('Y-m-t'));

        // Build prompt for AI
        $prompt = $this->buildPrompt($thisMonth, $netWorth, $anomalies, $categories);

        try {
            $recommendations = $this->callOpenAI($prompt);
            $this->storeRecommendations($userId, $recommendations);
            return $recommendations;
        } catch (\Exception $e) {
            return $this->getLocalRecommendations($userId, $limit);
        }
    }

    /**
     * Fallback: Generate recommendations locally
     */
    private function getLocalRecommendations(int $userId, int $limit): array {
        $analyzer = new FinancialAnalyzer($this->db);
        $thisMonth = $analyzer->getMonthSummary($userId, date('Y-m'));
        $netWorth = $analyzer->getNetWorth($userId);
        $categories = $analyzer->getExpensesByCategory($userId, date('Y-m-01'), date('Y-m-t'));

        $recommendations = [];

        // Rule 1: High spending in specific category
        if (!empty($categories)) {
            foreach ($categories as $cat) {
                if ($cat['total'] > $thisMonth['total_expenses'] * 0.3) {
                    $recommendations[] = [
                        'type' => 'spending_alert',
                        'title' => "Vysoké výdaje: {$cat['name']}",
                        'description' => "Vydali jste {$cat['total']} Kč na {$cat['name']}, což je {$cat['count']} transakcí.",
                        'priority' => 'high',
                        'category' => $cat['name']
                    ];
                }
            }
        }

        // Rule 2: Low savings rate
        if ($thisMonth['savings_rate'] < 10) {
            $targetSavings = $thisMonth['total_income'] * 0.20;
            $shortfall = $targetSavings - $thisMonth['net_income'];
            $recommendations[] = [
                'type' => 'savings_goal',
                'title' => 'Zvýšete svou míru spořeníl',
                'description' => "Abyste dosáhli cíle 20% spořeníl, musíte ušetřit dalších " . round($shortfall) . " Kč měsíčně.",
                'priority' => 'medium'
            ];
        }

        // Rule 3: Debt ratio
        if ($netWorth['total_liabilities'] > $netWorth['total_assets'] * 0.3) {
            $recommendations[] = [
                'type' => 'debt_reduction',
                'title' => 'Splácejte své dluhy',
                'description' => "Máte " . round($netWorth['total_liabilities']) . " Kč v závazcích. Zaměřte se na splácení.",
                'priority' => 'high'
            ];
        }

        // Rule 4: Budget overspending
        $budgetAnalysis = $analyzer->getBudgetAnalysis($userId, date('Y-m'));
        $overBudgetCount = count(array_filter($budgetAnalysis, fn($b) => $b['is_over_budget']));

        if ($overBudgetCount > 0) {
            $recommendations[] = [
                'type' => 'budget_alert',
                'title' => 'Překročili jste rozpočet',
                'description' => "{$overBudgetCount} kategorií překročilo plánovaný rozpočet.",
                'priority' => 'medium'
            ];
        }

        // Rule 5: Merchant spending
        $topMerchants = $this->db->query(
            "SELECT description, SUM(amount) as total, COUNT(*) as count
             FROM transactions
             WHERE user_id = ? AND type = 'expense' AND date >= DATE('now', '-30 days')
             GROUP BY description
             ORDER BY total DESC
             LIMIT 3",
            [$userId]
        );

        if ($topMerchants) {
            foreach ($topMerchants as $merchant) {
                if ($merchant['count'] > 10) {
                    $recommendations[] = [
                        'type' => 'merchant_alert',
                        'title' => "Časté výdaje: {$merchant['description']}",
                        'description' => "Navštívili jste {$merchant['description']} {$merchant['count']}x, celkem " . round($merchant['total']) . " Kč.",
                        'priority' => 'low'
                    ];
                }
            }
        }

        return array_slice($recommendations, 0, $limit);
    }

    /**
     * Build AI prompt for recommendations
     */
    private function buildPrompt(array $month, array $netWorth, array $anomalies, array $categories): string {
        $categoryList = implode(", ", array_map(fn($c) => "{$c['name']}: {$c['total']} Kč", $categories));

        return <<<PROMPT
Jako finanční poradce analyzujte následující údaje a poskytněte 3-5 konkrétních, účinných doporučení:

MĚSÍČNÍ SOUHRN:
- Příjmy: {$month['total_income']} Kč
- Výdaje: {$month['total_expenses']} Kč
- Čistý příjem: {$month['net_income']} Kč
- Míra úspor: {$month['savings_rate']}%

ČISTÁ HODNOTA:
- Celková aktiva: {$netWorth['total_assets']} Kč
- Celkové závazky: {$netWorth['total_liabilities']} Kč
- Čistá hodnota: {$netWorth['net_worth']} Kč

VÝDAJE PO KATEGORIÍCH:
{$categoryList}

ANOMÁLIE:
- Průměrné denní výdaje: {$anomalies['average_daily_spending']} Kč
- Detekováno anomálií: {$anomalies['anomaly_threshold']} Kč

Poskytněte konkrétní, actionable doporučení v češtině. Odpověď formátujte jako JSON array s poli: title, description, priority.
PROMPT;
    }

    /**
     * Call OpenAI API
     */
    private function callOpenAI(string $prompt): array {
        $url = 'https://api.openai.com/v1/chat/completions';

        $data = [
            'model' => $this->openaiModel,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Jste finanční poradce. Poskytujte konkrétní, praktická doporučení pro zlepšení osobních financí.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 1000
        ];

        $options = [
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->openaiApiKey
                ],
                'content' => json_encode($data),
                'timeout' => 30
            ]
        ];

        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);

        if (!$response) {
            throw new \Exception('OpenAI API call failed');
        }

        $result = json_decode($response, true);

        if (!isset($result['choices'][0]['message']['content'])) {
            throw new \Exception('Invalid OpenAI response');
        }

        $content = $result['choices'][0]['message']['content'];

        // Try to parse JSON from response
        if (preg_match('/\[.*\]/s', $content, $matches)) {
            return json_decode($matches[0], true) ?? [];
        }

        return [];
    }

    /**
     * Append recommendations without clearing existing ones
     */
    public function appendRecommendations(int $userId, array $recommendations): void {
        $this->saveRecommendations($userId, $recommendations, false);
    }

    /**
     * Replace all existing recommendations for the user
     */
    public function replaceRecommendations(int $userId, array $recommendations): void {
        $this->saveRecommendations($userId, $recommendations, true);
    }

    /**
     * Store recommendations in database (legacy internal use)
     */
    private function storeRecommendations(int $userId, array $recommendations): void {
        $this->saveRecommendations($userId, $recommendations, true);
    }

    /**
     * Persist recommendations with optional replacement
     */
    private function saveRecommendations(int $userId, array $recommendations, bool $replaceExisting): void {
        if (empty($recommendations)) {
            return;
        }

        if ($replaceExisting) {
            $this->db->delete('ai_recommendations', ['user_id' => $userId]);
        }

        foreach ($recommendations as $rec) {
            $this->db->insert('ai_recommendations', [
                'user_id' => $userId,
                'type' => $rec['type'] ?? 'general',
                'title' => $rec['title'] ?? '',
                'description' => $rec['description'] ?? '',
                'priority' => $rec['priority'] ?? 'medium',
                'is_dismissed' => 0
            ]);
        }
    }

    /**
     * Get stored recommendations
     */
    public function getStoredRecommendations(int $userId): array {
        return $this->db->query(
            "SELECT * FROM ai_recommendations
             WHERE user_id = ? AND is_dismissed = 0
             ORDER BY
                CASE WHEN priority = 'high' THEN 1
                     WHEN priority = 'medium' THEN 2
                     ELSE 3 END,
                created_at DESC",
            [$userId]
        );
    }

    /**
     * Dismiss recommendation
     */
    public function dismissRecommendation(int $recommendationId): void {
        $this->db->update('ai_recommendations', [
            'is_dismissed' => 1
        ], ['id' => $recommendationId]);
    }

    /**
     * Submit feedback for a recommendation
     */
    public function submitFeedback(int $userId, int $recommendationId, string $feedbackType, ?int $rating = null, ?string $comment = null, ?string $implementedAt = null): void {
        $data = [
            'user_id' => $userId,
            'recommendation_id' => $recommendationId,
            'feedback_type' => $feedbackType,
            'rating' => $rating,
            'comment' => $comment
        ];

        if ($implementedAt) {
            $data['implemented_at'] = $implementedAt;
        }

        $this->db->insert('recommendation_feedback', $data);

        // Update recommendation history with feedback
        $this->updateRecommendationHistory($recommendationId);
    }

    /**
     * Get feedback statistics for recommendations
     */
    public function getFeedbackStats(int $userId): array {
        $stats = $this->db->query(
            "SELECT
                COUNT(*) as total_feedback,
                AVG(rating) as average_rating,
                SUM(CASE WHEN feedback_type = 'helpful' THEN 1 ELSE 0 END) as helpful_count,
                SUM(CASE WHEN feedback_type = 'implemented' THEN 1 ELSE 0 END) as implemented_count
             FROM recommendation_feedback rf
             JOIN ai_recommendations ar ON rf.recommendation_id = ar.id
             WHERE ar.user_id = ?",
            [$userId]
        );

        return $stats[0] ?? [
            'total_feedback' => 0,
            'average_rating' => 0,
            'helpful_count' => 0,
            'implemented_count' => 0
        ];
    }

    /**
     * Store recommendation in history for tracking
     */
    private function storeRecommendationHistory(int $userId, array $recommendations, array $contextData = [], string $promptVersion = '1.0', string $aiModel = 'gpt-3.5-turbo', array $responseMetadata = []): void {
        foreach ($recommendations as $rec) {
            $this->db->insert('recommendation_history', [
                'user_id' => $userId,
                'recommendation_id' => $rec['id'] ?? null,
                'type' => $rec['type'] ?? 'general',
                'title' => $rec['title'] ?? '',
                'description' => $rec['description'] ?? '',
                'priority' => $rec['priority'] ?? 'medium',
                'context_data' => json_encode($contextData),
                'prompt_version' => $promptVersion,
                'ai_model' => $aiModel,
                'response_metadata' => json_encode($responseMetadata)
            ]);
        }
    }

    /**
     * Update recommendation history with latest feedback
     */
    private function updateRecommendationHistory(int $recommendationId): void {
        $feedbackStats = $this->db->query(
            "SELECT
                COUNT(*) as feedback_count,
                AVG(rating) as average_rating
             FROM recommendation_feedback
             WHERE recommendation_id = ? AND rating IS NOT NULL",
            [$recommendationId]
        );

        if (!empty($feedbackStats)) {
            $stats = $feedbackStats[0];
            $this->db->update('recommendation_history', [
                'feedback_count' => $stats['feedback_count'],
                'average_rating' => round($stats['average_rating'], 2)
            ], ['recommendation_id' => $recommendationId]);
        }
    }

    /**
     * Get recommendation history with feedback
     */
    public function getRecommendationHistory(int $userId, int $limit = 50): array {
        return $this->db->query(
            "SELECT rh.*, rf.feedback_type, rf.rating, rf.comment, rf.implemented_at
             FROM recommendation_history rh
             LEFT JOIN recommendation_feedback rf ON rh.recommendation_id = rf.recommendation_id
             WHERE rh.user_id = ?
             ORDER BY rh.created_at DESC
             LIMIT ?",
            [$userId, $limit]
        );
    }

    /**
     * Mark recommendation as implemented
     */
    public function markAsImplemented(int $recommendationId): void {
        $this->db->update('recommendation_history', [
            'is_implemented' => 1,
            'implemented_at' => date('Y-m-d H:i:s')
        ], ['recommendation_id' => $recommendationId]);
    }
}
