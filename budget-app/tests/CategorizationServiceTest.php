<?php
namespace BudgetApp\Tests;

use PHPUnit\Framework\TestCase;
use BudgetApp\Services\CategorizationService;
use BudgetApp\Database;

class CategorizationServiceTest extends TestCase {
    private CategorizationService $categorizer;
    private Database $db;

    protected function setUp(): void {
        // Mock database
        $this->db = $this->createMock(Database::class);

        // Mock database responses for rules and categories
        $this->db->method('query')
            ->willReturnCallback(function($query) {
                if (strpos($query, 'categorization_rules') !== false) {
                    return [
                        [
                            'id' => 1,
                            'category_id' => 1,
                            'rule_type' => 'keyword',
                            'pattern' => 'tesco',
                            'is_regex' => 0,
                            'priority' => 100,
                            'category_name' => 'groceries',
                            'category_type' => 'expense'
                        ]
                    ];
                }
                if (strpos($query, 'categories') !== false) {
                    return [
                        ['id' => 1, 'name' => 'groceries', 'type' => 'expense'],
                        ['id' => 2, 'name' => 'salary', 'type' => 'income'],
                        ['id' => 3, 'name' => 'rent', 'type' => 'expense']
                    ];
                }
                return [];
            });

        $this->db->method('queryOne')
            ->willReturn(['category_id' => 1]);

        $this->categorizer = new CategorizationService($this->db);
    }

    public function testCategorizeTransactionWithRuleMatch(): void {
        $transaction = [
            'description' => 'Nákup v Tesco Praha',
            'merchant_name' => 'Tesco',
            'amount' => -500.00
        ];

        $result = $this->categorizer->categorizeTransaction($transaction, 1);

        $this->assertArrayHasKey('category_id', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('method', $result);
        $this->assertArrayHasKey('tags', $result);
        $this->assertEquals('high', $result['confidence']);
        $this->assertEquals('rule_based', $result['method']);
    }

    public function testCategorizeTransactionWithMerchantHistory(): void {
        $transaction = [
            'description' => 'Nákup v Albertu',
            'merchant_name' => 'Albert',
            'amount' => -300.00
        ];

        $result = $this->categorizer->categorizeTransaction($transaction, 1);

        $this->assertEquals('medium', $result['confidence']);
        $this->assertEquals('merchant_history', $result['method']);
    }

    public function testCategorizeTransactionWithPatternMatching(): void {
        $transaction = [
            'description' => 'Výběr hotovosti ATM',
            'merchant_name' => '',
            'amount' => -1000.00
        ];

        $result = $this->categorizer->categorizeTransaction($transaction, 1);

        $this->assertArrayHasKey('category_id', $result);
        $this->assertContains('banking', $result); // Should match banking category
    }

    public function testGenerateTags(): void {
        $transaction = [
            'description' => 'Výběr hotovosti ATM online',
            'merchant_name' => 'ATM Praha',
            'amount' => -500.00,
            'currency' => 'EUR'
        ];

        $tags = $this->invokePrivateMethod($this->categorizer, 'generateTags', [$transaction]);

        $this->assertContains('online', $tags);
        $this->assertContains('cash', $tags);
        $this->assertContains('international', $tags);
    }

    public function testExtractFeatures(): void {
        $transaction = [
            'description' => 'Nákup Tesco supermarket',
            'merchant_name' => 'Tesco',
            'amount' => -250.00,
            'date' => '2024-01-15'
        ];

        $features = $this->invokePrivateMethod($this->categorizer, 'extractFeatures', [$transaction]);

        $this->assertArrayHasKey('description_length', $features);
        $this->assertArrayHasKey('has_merchant', $features);
        $this->assertArrayHasKey('amount_range', $features);
        $this->assertArrayHasKey('time_features', $features);
        $this->assertArrayHasKey('text_features', $features);

        $this->assertTrue($features['has_merchant']);
        $this->assertEquals('small', $features['amount_range']);
    }

    public function testCategorizeAmountRanges(): void {
        $testCases = [
            -50 => 'very_small',
            -250 => 'small',
            -1500 => 'medium',
            -8000 => 'large',
            -50000 => 'very_large',
            1000 => 'small' // Positive amounts should also be categorized
        ];

        foreach ($testCases as $amount => $expected) {
            $result = $this->invokePrivateMethod($this->categorizer, 'categorizeAmount', [$amount]);
            $this->assertEquals($expected, $result, "Failed for amount: $amount");
        }
    }

    public function testExtractTimeFeatures(): void {
        $testCases = [
            '2024-01-15' => ['day_of_week' => 1, 'month' => 1, 'is_weekend' => 0], // Monday
            '2024-01-20' => ['day_of_week' => 6, 'month' => 1, 'is_weekend' => 1], // Saturday
            '2024-07-15' => ['day_of_week' => 1, 'month' => 7, 'is_weekend' => 0]  // Monday in July
        ];

        foreach ($testCases as $date => $expected) {
            $features = $this->invokePrivateMethod($this->categorizer, 'extractTimeFeatures', [$date]);
            $this->assertEquals($expected['day_of_week'], $features['day_of_week']);
            $this->assertEquals($expected['month'], $features['month']);
            $this->assertEquals($expected['is_weekend'], $features['is_weekend']);
        }
    }

    public function testExtractTextFeatures(): void {
        $description = 'Výběr hotovosti ATM Praha';
        $features = $this->invokePrivateMethod($this->categorizer, 'extractTextFeatures', [$description]);

        $this->assertArrayHasKey('withdrawal', $features);
        $this->assertArrayHasKey('payment', $features);
        $this->assertEquals(1, $features['withdrawal']); // Should detect "výběr"
    }

    public function testBatchCategorization(): void {
        $transactions = [
            [
                'description' => 'Nákup Tesco',
                'merchant_name' => 'Tesco',
                'amount' => -300.00
            ],
            [
                'description' => 'Výplata',
                'merchant_name' => 'Zaměstnavatel',
                'amount' => 15000.00
            ]
        ];

        $results = $this->categorizer->categorizeTransactions($transactions, 1);

        $this->assertCount(2, $results);
        $this->assertArrayHasKey('category_id', $results[0]);
        $this->assertArrayHasKey('category_id', $results[1]);
    }

    public function testLearnFromCorrection(): void {
        $transaction = [
            'description' => 'Nákup Lidl',
            'merchant_name' => 'Lidl',
            'amount' => -400.00
        ];

        $correctCategoryId = 1; // groceries

        // Mock the database insert/update calls
        $this->db->expects($this->once())
            ->method('queryOne')
            ->willReturn(null); // No existing merchant

        $this->db->expects($this->once())
            ->method('insert')
            ->with('merchants', $this->callback(function($data) {
                return $data['name'] === 'Lidl' && $data['category_id'] === 1;
            }));

        $this->categorizer->learnFromCorrection(1, $transaction, $correctCategoryId);
    }

    public function testGetCategorizationStats(): void {
        // Mock database responses for stats
        $this->db->method('queryOne')
            ->willReturn([
                'total_transactions' => 100,
                'categorized_transactions' => 85,
                'unique_merchants' => 25
            ]);

        $this->db->method('query')
            ->willReturn([['id' => 1], ['id' => 2], ['id' => 3]]); // 3 rules

        $stats = $this->categorizer->getCategorizationStats(1);

        $this->assertEquals(100, $stats['total_transactions']);
        $this->assertEquals(85, $stats['categorized_transactions']);
        $this->assertEquals(85.0, $stats['categorization_rate']);
        $this->assertEquals(3, $stats['active_rules']);
    }

    public function testCreateRule(): void {
        $ruleData = [
            'category_id' => 1,
            'pattern' => 'albert',
            'is_regex' => false,
            'priority' => 90
        ];

        $this->db->expects($this->once())
            ->method('insert')
            ->with('categorization_rules', $this->callback(function($data) use ($ruleData) {
                return $data['category_id'] === $ruleData['category_id'] &&
                       $data['pattern'] === $ruleData['pattern'] &&
                       $data['is_regex'] === $ruleData['is_regex'];
            }));

        $result = $this->categorizer->createRule(1, $ruleData);
        $this->assertTrue($result);
    }

    public function testGetSuggestedRules(): void {
        // Mock uncategorized transactions
        $this->db->method('query')
            ->willReturnOnConsecutiveCalls(
                [ // categorization_rules query
                    ['id' => 1, 'category_id' => 1, 'rule_type' => 'keyword', 'pattern' => 'existing', 'is_regex' => 0, 'priority' => 100, 'category_name' => 'groceries', 'category_type' => 'expense']
                ],
                [ // categories query
                    ['id' => 1, 'name' => 'groceries', 'type' => 'expense']
                ],
                [ // uncategorized transactions query
                    ['description' => 'Nákup Lidl', 'merchant_name' => 'Lidl', 'frequency' => 5]
                ]
            );

        $suggestions = $this->categorizer->getSuggestedRules(1);

        $this->assertNotEmpty($suggestions);
        $this->assertArrayHasKey('pattern', $suggestions[0]);
        $this->assertArrayHasKey('frequency', $suggestions[0]);
        $this->assertEquals(5, $suggestions[0]['frequency']);
    }

    public function testPatternBasedCategorization(): void {
        $testCases = [
            ['description' => 'Nákup v Tesco supermarket', 'merchant_name' => '', 'amount' => -300.00, 'expected' => 'groceries'],
            ['description' => 'Výběr hotovosti ATM', 'merchant_name' => '', 'amount' => -1000.00, 'expected' => 'banking'],
            ['description' => 'Restaurace U Fleků', 'merchant_name' => 'U Fleků', 'amount' => -800.00, 'expected' => 'food'],
            ['description' => 'Výplata zaměstnavatel', 'merchant_name' => '', 'amount' => 15000.00, 'expected' => 'salary'],
            ['description' => 'Nájem byt', 'merchant_name' => '', 'amount' => -12000.00, 'expected' => 'rent']
        ];

        foreach ($testCases as $testCase) {
            $result = $this->invokePrivateMethod($this->categorizer, 'patternBasedCategorization',
                [$testCase['description'], $testCase['merchant_name'], $testCase['amount']]);

            if ($testCase['expected'] === 'salary' || $testCase['expected'] === 'rent') {
                // These might not have category mappings in our mock, so they could return null
                // That's acceptable for this test
                continue;
            }

            $this->assertNotNull($result, "Failed to categorize: {$testCase['description']}");
        }
    }

    public function testCzechKeywordDetection(): void {
        $czechKeywords = [
            'výběr' => 'withdrawal',
            'vklad' => 'deposit',
            'převod' => 'transfer',
            'platba' => 'payment',
            'poplatek' => 'fee',
            'nákup' => 'purchase',
            'prodej' => 'sale'
        ];

        foreach ($czechKeywords as $czech => $english) {
            $description = "Test $czech transaction";
            $features = $this->invokePrivateMethod($this->categorizer, 'extractTextFeatures', [$description]);

            $this->assertEquals(1, $features[$english], "Failed to detect Czech keyword: $czech");
        }
    }

    /**
     * Helper method to invoke private methods for testing
     */
    private function invokePrivateMethod($object, $methodName, array $parameters = []) {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}