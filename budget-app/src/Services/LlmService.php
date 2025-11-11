<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

class LlmService {
    private Database $db;
    private McpFinancialAdapter $mcpAdapter;
    private string $apiKey;
    private string $model = 'gpt-4';
    private array $cache = [];
    private array $rateLimits = [];

    // Cache settings
    private int $cacheTtl = 3600; // 1 hour
    private int $maxRequestsPerHour = 50;
    private int $maxRequestsPerDay = 200;

    public function __construct(Database $db, string $apiKey = '') {
        $this->db = $db;
        $this->mcpAdapter = new McpFinancialAdapter($db);
        $this->apiKey = $apiKey ?: $_ENV['OPENAI_API_KEY'] ?? '';

        // Load cache from database
        $this->loadCache();
        $this->loadRateLimits();
    }

    /**
     * Generate insight using specified prompt type
     */
    public function generateInsight(int $userId, string $promptType, array $userContext = []): array {
        // Check rate limits
        if (!$this->checkRateLimit($userId)) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
                'type' => $promptType
            ];
        }

        // Get financial context
        $this->mcpAdapter = new McpFinancialAdapter($this->db, $userContext);
        $context = $this->buildContext($userId, $promptType);

        // Check cache
        $cacheKey = $this->generateCacheKey($userId, $promptType, $context);
        if ($cached = $this->getCachedResponse($cacheKey)) {
            return [
                'success' => true,
                'response' => $cached['response'],
                'cached' => true,
                'generated_at' => $cached['created_at'],
                'type' => $promptType
            ];
        }

        // Generate prompt
        $prompt = LlmPromptTemplates::getPrompt($promptType, $context);

        // Redact sensitive data
        $prompt = $this->redactSensitiveData($prompt);

        try {
            $response = $this->callLlm($prompt);

            // Cache the response
            $this->cacheResponse($cacheKey, $response, $userId, $promptType);

            // Update rate limits
            $this->updateRateLimit($userId);

            return [
                'success' => true,
                'response' => $response,
                'cached' => false,
                'generated_at' => date('Y-m-d H:i:s'),
                'type' => $promptType
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to generate insight: ' . $e->getMessage(),
                'type' => $promptType
            ];
        }
    }

    /**
     * Build context for prompt type
     */
    private function buildContext(int $userId, string $promptType): array {
        $baseContext = [
            'financial_stats' => $this->mcpAdapter->getFinancialStats($userId)
        ];

        return match($promptType) {
            'budget_analyzer' => $baseContext,
            'cash_flow_forecaster' => array_merge($baseContext, [
                'transaction_timelines' => $this->mcpAdapter->getTransactionTimelines($userId),
                'recurring_bills' => $this->mcpAdapter->getRecurringBills($userId),
                'debt_list' => $this->mcpAdapter->getDebtList($userId)
            ]),
            'savings_debt_coach' => array_merge($baseContext, [
                'debt_list' => $this->mcpAdapter->getDebtList($userId)
            ]),
            'career_uplift_advisor' => [
                'user_skills' => $this->mcpAdapter->getUserSkillsContext($userId),
                'market_data' => $this->mcpAdapter->getMarketDataContext()
            ],
            'income_strategy' => [
                'user_skills' => $this->mcpAdapter->getUserSkillsContext($userId)
            ],
            'resilience_roadmap' => [
                'budget_status' => $this->mcpAdapter->getBudgetStatus($userId),
                'goals_and_urgency' => $this->mcpAdapter->getGoalsAndUrgency($userId)
            ],
            'crisis_mode' => [
                'financial_stats' => $baseContext['financial_stats'],
                'goals_and_urgency' => $this->mcpAdapter->getGoalsAndUrgency($userId)
            ],
            default => $baseContext
        };
    }

    /**
     * Call LLM API with redacted data
     */
    private function callLlm(string $prompt): string {
        if (empty($this->apiKey)) {
            throw new \Exception('OpenAI API key not configured');
        }

        $url = 'https://api.openai.com/v1/chat/completions';

        $data = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a financial advisor specializing in Czech personal finance. Provide clear, actionable advice in Czech language. Be specific with amounts, timeframes, and local resources.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 2000,
            'presence_penalty' => 0.1,
            'frequency_penalty' => 0.1
        ];

        $options = [
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->apiKey
                ],
                'content' => json_encode($data),
                'timeout' => 30
            ]
        ];

        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);

        if (!$response) {
            throw new \Exception('LLM API call failed');
        }

        $result = json_decode($response, true);

        if (!isset($result['choices'][0]['message']['content'])) {
            throw new \Exception('Invalid LLM response: ' . json_encode($result));
        }

        return $result['choices'][0]['message']['content'];
    }

    /**
     * Redact sensitive data from prompts
     */
    private function redactSensitiveData(string $prompt): string {
        // Remove or mask specific account numbers, full names, addresses
        $patterns = [
            // Account numbers (various formats)
            '/\b\d{10,20}\b/' => '[ACCOUNT_NUMBER]',
            // Email addresses
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/' => '[EMAIL]',
            // Phone numbers
            '/\b\d{3}[\s.-]?\d{3}[\s.-]?\d{4}\b/' => '[PHONE]',
            // Full addresses (simplified)
            '/\b\d+\s+[A-Za-z\s,]+\s+\d{5}\b/' => '[ADDRESS]',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $prompt = preg_replace($pattern, $replacement, $prompt);
        }

        return $prompt;
    }

    /**
     * Generate cache key
     */
    private function generateCacheKey(int $userId, string $promptType, array $context): string {
        $dataHash = md5(json_encode($context));
        return "llm_{$userId}_{$promptType}_{$dataHash}";
    }

    /**
     * Get cached response
     */
    private function getCachedResponse(string $cacheKey): ?array {
        if (isset($this->cache[$cacheKey])) {
            $cached = $this->cache[$cacheKey];
            if (strtotime($cached['created_at']) > time() - $this->cacheTtl) {
                return $cached;
            } else {
                // Expired, remove from cache
                unset($this->cache[$cacheKey]);
                $this->db->delete('llm_cache', ['cache_key' => $cacheKey]);
            }
        }
        return null;
    }

    /**
     * Cache response
     */
    private function cacheResponse(string $cacheKey, string $response, int $userId, string $promptType): void {
        $data = [
            'cache_key' => $cacheKey,
            'user_id' => $userId,
            'prompt_type' => $promptType,
            'response' => $response,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->db->insert('llm_cache', $data);
        $this->cache[$cacheKey] = $data;
    }

    /**
     * Check rate limit
     */
    private function checkRateLimit(int $userId): bool {
        $userLimits = $this->rateLimits[$userId] ?? ['hour' => 0, 'day' => 0, 'last_reset' => time()];

        // Reset counters if needed
        $now = time();
        $hourAgo = $now - 3600;
        $dayAgo = $now - 86400;

        if ($userLimits['last_reset'] < $hourAgo) {
            $userLimits['hour'] = 0;
        }
        if ($userLimits['last_reset'] < $dayAgo) {
            $userLimits['day'] = 0;
        }

        return $userLimits['hour'] < $this->maxRequestsPerHour &&
               $userLimits['day'] < $this->maxRequestsPerDay;
    }

    /**
     * Update rate limit counters
     */
    private function updateRateLimit(int $userId): void {
        if (!isset($this->rateLimits[$userId])) {
            $this->rateLimits[$userId] = ['hour' => 0, 'day' => 0, 'last_reset' => time()];
        }

        $this->rateLimits[$userId]['hour']++;
        $this->rateLimits[$userId]['day']++;
        $this->rateLimits[$userId]['last_reset'] = time();

        // Persist to database
        $this->db->insert('llm_rate_limits', [
            'user_id' => $userId,
            'hour_count' => $this->rateLimits[$userId]['hour'],
            'day_count' => $this->rateLimits[$userId]['day'],
            'last_reset' => date('Y-m-d H:i:s', $this->rateLimits[$userId]['last_reset'])
        ], true); // Upsert
    }

    /**
     * Load cache from database
     */
    private function loadCache(): void {
        $cached = $this->db->query(
            "SELECT * FROM llm_cache
             WHERE created_at > datetime('now', '-1 hour')
             ORDER BY created_at DESC"
        );

        foreach ($cached as $item) {
            $this->cache[$item['cache_key']] = $item;
        }
    }

    /**
     * Load rate limits from database
     */
    private function loadRateLimits(): void {
        $limits = $this->db->query(
            "SELECT user_id, hour_count, day_count, last_reset
             FROM llm_rate_limits
             WHERE last_reset > datetime('now', '-24 hours')"
        );

        foreach ($limits as $limit) {
            $this->rateLimits[$limit['user_id']] = [
                'hour' => $limit['hour_count'],
                'day' => $limit['day_count'],
                'last_reset' => strtotime($limit['last_reset'])
            ];
        }
    }

    /**
     * Get available prompt types
     */
    public static function getAvailablePromptTypes(): array {
        return [
            'budget_analyzer',
            'cash_flow_forecaster',
            'savings_debt_coach',
            'career_uplift_advisor',
            'income_strategy',
            'resilience_roadmap',
            'crisis_mode'
        ];
    }

    /**
     * Clear user cache
     */
    public function clearUserCache(int $userId): void {
        $this->db->delete('llm_cache', ['user_id' => $userId]);
        // Clear from memory cache
        foreach ($this->cache as $key => $item) {
            if ($item['user_id'] == $userId) {
                unset($this->cache[$key]);
            }
        }
    }

    /**
     * Get cache stats
     */
    public function getCacheStats(): array {
        return [
            'cached_responses' => count($this->cache),
            'cache_size_mb' => round(strlen(json_encode($this->cache)) / 1024 / 1024, 2)
        ];
    }
}