<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

class CategorizationService {
    private Database $db;
    private array $rules = [];
    private array $categoryMappings = [];

    public function __construct(Database $db) {
        $this->db = $db;
        $this->loadRules();
        $this->loadCategoryMappings();
    }

    /**
     * Load categorization rules from database
     */
    private function loadRules(): void {
        $rules = $this->db->query(
            "SELECT cr.*, c.name as category_name, c.type as category_type
             FROM categorization_rules cr
             JOIN categories c ON cr.category_id = c.id
             WHERE cr.is_active = 1
             ORDER BY cr.priority ASC"
        );

        $this->rules = $rules;
    }

    /**
     * Load category mappings for quick lookup
     */
    private function loadCategoryMappings(): void {
        $categories = $this->db->query(
            "SELECT id, name, type FROM categories WHERE is_active = 1"
        );

        foreach ($categories as $category) {
            $this->categoryMappings[strtolower($category['name'])] = $category;
        }
    }

    /**
     * Categorize a single transaction
     */
    public function categorizeTransaction(array $transaction, int $userId): array {
        $description = strtolower($transaction['description'] ?? '');
        $merchantName = strtolower($transaction['merchant_name'] ?? '');
        $amount = $transaction['amount'] ?? 0;

        // Step 1: Check explicit categorization rules
        $categoryId = $this->checkRules($description, $merchantName, $amount);
        if ($categoryId) {
            return [
                'category_id' => $categoryId,
                'confidence' => 'high',
                'method' => 'rule_based',
                'tags' => $this->generateTags($transaction)
            ];
        }

        // Step 2: Check merchant history
        $categoryId = $this->checkMerchantHistory($userId, $merchantName);
        if ($categoryId) {
            return [
                'category_id' => $categoryId,
                'confidence' => 'medium',
                'method' => 'merchant_history',
                'tags' => $this->generateTags($transaction)
            ];
        }

        // Step 3: Pattern-based categorization
        $categoryId = $this->patternBasedCategorization($description, $merchantName, $amount);
        if ($categoryId) {
            return [
                'category_id' => $categoryId,
                'confidence' => 'medium',
                'method' => 'pattern_based',
                'tags' => $this->generateTags($transaction)
            ];
        }

        // Step 4: ML-ready feature extraction (for future enhancement)
        $features = $this->extractFeatures($transaction);

        return [
            'category_id' => null,
            'confidence' => 'low',
            'method' => 'uncategorized',
            'tags' => $this->generateTags($transaction),
            'features' => $features
        ];
    }

    /**
     * Check explicit categorization rules
     */
    private function checkRules(string $description, string $merchantName, float $amount): ?int {
        foreach ($this->rules as $rule) {
            $pattern = $rule['pattern'];
            $searchText = $description . ' ' . $merchantName;

            $matches = false;
            if ($rule['is_regex']) {
                $matches = @preg_match($pattern, $searchText);
            } else {
                $matches = stripos($searchText, $pattern) !== false;
            }

            if ($matches) {
                return $rule['category_id'];
            }
        }

        return null;
    }

    /**
     * Check merchant categorization history
     */
    private function checkMerchantHistory(int $userId, string $merchantName): ?int {
        if (empty($merchantName)) {
            return null;
        }

        $merchant = $this->db->queryOne(
            "SELECT category_id FROM merchants
             WHERE user_id = ? AND LOWER(name) = ? AND category_id IS NOT NULL",
            [$userId, $merchantName]
        );

        return $merchant ? $merchant['category_id'] : null;
    }

    /**
     * Pattern-based categorization using predefined rules
     */
    private function patternBasedCategorization(string $description, string $merchantName, float $amount): ?int {
        $searchText = $description . ' ' . $merchantName;

        // Czech banking patterns
        $patterns = [
            // Food & Dining
            'food' => [
                'patterns' => ['restaurace', 'restaurant', 'jídlo', 'oběd', 'večeře', 'snídaně', 'kavárna', 'cafe', 'pizzeria', 'hamburger'],
                'merchants' => ['mcdonald', 'kfc', 'burger king', 'subway', 'starbucks', 'costa']
            ],

            // Groceries
            'groceries' => [
                'patterns' => ['supermarket', 'supermarket', 'nakup', 'potraviny'],
                'merchants' => ['tesco', 'albert', 'lidl', 'kaufland', 'penny', 'billa', 'makro', 'globus']
            ],

            // Transport
            'transport' => [
                'patterns' => ['benzín', 'benzin', 'diesel', 'čerpací stanice', 'vlak', 'bus', 'tram', 'metro', 'taxi', 'uber', 'bolt'],
                'merchants' => ['ceske drahy', 'dp', 'regiojet', 'student agency', 'shell', 'benzinol', 'pap oil']
            ],

            // Utilities
            'utilities' => [
                'patterns' => ['elektřina', 'elektrina', 'voda', 'plyn', 'internet', 'telefon', 'vodafone', 'o2', 't-mobile', 'čez', 'pražská plynárenská'],
                'merchants' => ['cez', 'pražská plynárenská', 'vodovody', 'vodafone', 'o2', 't-mobile']
            ],

            // Entertainment
            'entertainment' => [
                'patterns' => ['kino', 'divadlo', 'koncert', 'kniha', 'knihy', 'spotify', 'netflix', 'hbo', 'disney', 'hudební', 'film'],
                'merchants' => ['spotify', 'netflix', 'hbo', 'disney', 'cinestar', 'světozor']
            ],

            // Shopping
            'shopping' => [
                'patterns' => ['obchod', 'nákup', 'nakup', 'zboží', 'shopping'],
                'merchants' => ['zara', 'hm', 'ikea', 'mall', 'nákupní centrum', 'tchibo', 'dm', 'rossmann']
            ],

            // Healthcare
            'healthcare' => [
                'patterns' => ['lékař', 'lekar', 'zubař', 'zubar', 'lékárna', 'lekárna', 'nemocnice', 'ordinace', 'zdravotní', 'pojištění'],
                'merchants' => ['benu', 'dr max', 'zdravotní pojišťovna', 'všeobecná zdravotní pojišťovna']
            ],

            // Salary & Income
            'salary' => [
                'patterns' => ['plat', 'mzda', 'výplata', 'vyplata', 'salary', 'payroll', 'příjem', 'prijem'],
                'merchants' => []
            ],

            // Rent & Housing
            'rent' => [
                'patterns' => ['nájem', 'najem', 'rent', 'nájemné', 'najemne', 'nájemní', 'pronájem'],
                'merchants' => []
            ],

            // Insurance
            'insurance' => [
                'patterns' => ['pojištění', 'pojisteni', 'pojišťovna', 'pojistovna', 'havarijní', 'životní'],
                'merchants' => ['kooperativa', 'česká pojišťovna', 'allianz', 'generali']
            ],

            // Education
            'education' => [
                'patterns' => ['škola', 'skola', 'univerzita', 'vzdělání', 'vzdelani', 'kurz', 'školení', 'skoleni'],
                'merchants' => ['karolinka', 'univerzita karlova']
            ],

            // Travel
            'travel' => [
                'patterns' => ['hotel', 'letiště', 'letiste', 'letecká společnost', 'airline', 'booking', 'expedia'],
                'merchants' => ['booking.com', 'airbnb', 'expedia', 'student agency']
            ],

            // Banking & Fees
            'banking' => [
                'patterns' => ['poplatek', 'fee', 'bankovní', 'výpis', 'vyber', 'vklad', 'převod', 'prevod'],
                'merchants' => ['bank', 'banka']
            ]
        ];

        foreach ($patterns as $categoryName => $categoryData) {
            // Check patterns
            foreach ($categoryData['patterns'] as $pattern) {
                if (stripos($searchText, $pattern) !== false) {
                    return $this->getCategoryIdByName($categoryName);
                }
            }

            // Check merchants
            foreach ($categoryData['merchants'] as $merchant) {
                if (stripos($searchText, $merchant) !== false) {
                    return $this->getCategoryIdByName($categoryName);
                }
            }
        }

        return null;
    }

    /**
     * Get category ID by name
     */
    private function getCategoryIdByName(string $categoryName): ?int {
        $categoryKey = strtolower($categoryName);
        return $this->categoryMappings[$categoryKey]['id'] ?? null;
    }

    /**
     * Generate tags for transaction
     */
    private function generateTags(array $transaction): array {
        $tags = [];
        $description = strtolower($transaction['description'] ?? '');
        $merchantName = strtolower($transaction['merchant_name'] ?? '');

        // Online transactions
        if (strpos($description, 'online') !== false ||
            strpos($description, 'internet') !== false ||
            strpos($description, 'web') !== false) {
            $tags[] = 'online';
        }

        // Cash transactions
        if (strpos($description, 'hotovost') !== false ||
            strpos($description, 'cash') !== false ||
            strpos($description, 'výběr') !== false) {
            $tags[] = 'cash';
        }

        // Card transactions
        if (strpos($description, 'karta') !== false ||
            strpos($description, 'card') !== false ||
            strpos($description, 'debit') !== false ||
            strpos($description, 'credit') !== false) {
            $tags[] = 'card';
        }

        // Recurring transactions
        if (strpos($description, 'měsíční') !== false ||
            strpos($description, 'roční') !== false ||
            strpos($description, 'subscription') !== false) {
            $tags[] = 'recurring';
        }

        // International transactions
        if (isset($transaction['currency']) && $transaction['currency'] !== 'CZK') {
            $tags[] = 'international';
        }

        // Large transactions
        if (abs($transaction['amount'] ?? 0) > 5000) {
            $tags[] = 'large_transaction';
        }

        return array_unique($tags);
    }

    /**
     * Extract ML-ready features for future enhancement
     */
    private function extractFeatures(array $transaction): array {
        $description = strtolower($transaction['description'] ?? '');
        $merchantName = strtolower($transaction['merchant_name'] ?? '');

        return [
            'description_length' => strlen($description),
            'has_numbers' => preg_match('/\d/', $description) ? 1 : 0,
            'has_special_chars' => preg_match('/[^a-zA-Z\s]/', $description) ? 1 : 0,
            'word_count' => str_word_count($description),
            'amount_range' => $this->categorizeAmount($transaction['amount'] ?? 0),
            'has_merchant' => !empty($merchantName) ? 1 : 0,
            'merchant_length' => strlen($merchantName),
            'contains_czech_chars' => preg_match('/[áéíóúýčďěňřšťž]/', $description) ? 1 : 0,
            'time_features' => $this->extractTimeFeatures($transaction['date'] ?? ''),
            'text_features' => $this->extractTextFeatures($description)
        ];
    }

    /**
     * Categorize amount into ranges
     */
    private function categorizeAmount(float $amount): string {
        $absAmount = abs($amount);

        if ($absAmount < 100) return 'very_small';
        if ($absAmount < 500) return 'small';
        if ($absAmount < 2000) return 'medium';
        if ($absAmount < 10000) return 'large';
        return 'very_large';
    }

    /**
     * Extract time-based features
     */
    private function extractTimeFeatures(string $dateStr): array {
        if (empty($dateStr)) {
            return ['day_of_week' => null, 'month' => null, 'is_weekend' => null];
        }

        $timestamp = strtotime($dateStr);
        $dayOfWeek = date('N', $timestamp); // 1 = Monday, 7 = Sunday
        $month = date('n', $timestamp); // 1-12

        return [
            'day_of_week' => $dayOfWeek,
            'month' => $month,
            'is_weekend' => ($dayOfWeek >= 6) ? 1 : 0,
            'is_month_start' => (date('j', $timestamp) <= 7) ? 1 : 0,
            'is_month_end' => (date('j', $timestamp) >= 25) ? 1 : 0
        ];
    }

    /**
     * Extract text features
     */
    private function extractTextFeatures(string $description): array {
        $words = str_word_count($description, 1);
        $features = [];

        // Common Czech transaction keywords
        $keywords = [
            'výběr' => 'withdrawal',
            'vklad' => 'deposit',
            'převod' => 'transfer',
            'platba' => 'payment',
            'poplatek' => 'fee',
            'úrok' => 'interest',
            'nákup' => 'purchase',
            'prodej' => 'sale'
        ];

        foreach ($keywords as $czech => $english) {
            $features[$english] = (stripos($description, $czech) !== false) ? 1 : 0;
        }

        return $features;
    }

    /**
     * Batch categorize transactions
     */
    public function categorizeTransactions(array $transactions, int $userId): array {
        $results = [];

        foreach ($transactions as $transaction) {
            $results[] = $this->categorizeTransaction($transaction, $userId);
        }

        return $results;
    }

    /**
     * Learn from user corrections
     */
    public function learnFromCorrection(int $userId, array $transaction, int $correctCategoryId): void {
        $merchantName = $transaction['merchant_name'] ?? '';

        if (!empty($merchantName)) {
            // Update merchant category association
            $existing = $this->db->queryOne(
                "SELECT id FROM merchants WHERE user_id = ? AND name = ?",
                [$userId, $merchantName]
            );

            if ($existing) {
                $this->db->update('merchants', [
                    'category_id' => $correctCategoryId,
                    'last_used' => date('Y-m-d')
                ], ['id' => $existing['id']]);
            } else {
                $this->db->insert('merchants', [
                    'user_id' => $userId,
                    'name' => $merchantName,
                    'category_id' => $correctCategoryId,
                    'frequency' => 1,
                    'last_used' => date('Y-m-d')
                ]);
            }
        }

        // Could also create new rules based on patterns, but keeping it simple for now
    }

    /**
     * Get categorization statistics
     */
    public function getCategorizationStats(int $userId): array {
        $stats = $this->db->queryOne(
            "SELECT
                COUNT(*) as total_transactions,
                SUM(CASE WHEN category_id IS NOT NULL THEN 1 ELSE 0 END) as categorized_transactions,
                COUNT(DISTINCT merchant_id) as unique_merchants
             FROM transactions
             WHERE user_id = ?",
            [$userId]
        );

        $ruleCount = count($this->db->query(
            "SELECT id FROM categorization_rules WHERE user_id = ? AND is_active = 1",
            [$userId]
        ));

        return [
            'total_transactions' => (int)($stats['total_transactions'] ?? 0),
            'categorized_transactions' => (int)($stats['categorized_transactions'] ?? 0),
            'categorization_rate' => $stats['total_transactions'] > 0
                ? round(($stats['categorized_transactions'] / $stats['total_transactions']) * 100, 2)
                : 0,
            'unique_merchants' => (int)($stats['unique_merchants'] ?? 0),
            'active_rules' => $ruleCount
        ];
    }

    /**
     * Create a new categorization rule
     */
    public function createRule(int $userId, array $ruleData): bool {
        try {
            $this->db->insert('categorization_rules', [
                'user_id' => $userId,
                'category_id' => $ruleData['category_id'],
                'rule_type' => $ruleData['rule_type'] ?? 'keyword',
                'pattern' => $ruleData['pattern'],
                'is_regex' => $ruleData['is_regex'] ?? false,
                'priority' => $ruleData['priority'] ?? 100,
                'is_active' => true
            ]);

            // Reload rules
            $this->loadRules();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get suggested rules based on uncategorized transactions
     */
    public function getSuggestedRules(int $userId, int $limit = 10): array {
        $uncategorized = $this->db->query(
            "SELECT description, merchant_name, COUNT(*) as frequency
             FROM transactions
             WHERE user_id = ? AND category_id IS NULL
             GROUP BY description, merchant_name
             ORDER BY frequency DESC
             LIMIT ?",
            [$userId, $limit]
        );

        $suggestions = [];
        foreach ($uncategorized as $item) {
            $suggestions[] = [
                'pattern' => $this->extractPattern($item['description'], $item['merchant_name']),
                'frequency' => $item['frequency'],
                'suggested_category' => $this->patternBasedCategorization(
                    $item['description'],
                    $item['merchant_name'],
                    0
                )
            ];
        }

        return $suggestions;
    }

    /**
     * Extract pattern from transaction data
     */
    private function extractPattern(string $description, string $merchantName): string {
        // Simple pattern extraction - take the most specific part
        if (!empty($merchantName)) {
            return $merchantName;
        }

        // Extract first meaningful words
        $words = explode(' ', $description);
        return implode(' ', array_slice($words, 0, min(3, count($words))));
    }
}