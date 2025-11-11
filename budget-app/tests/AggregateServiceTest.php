<?php
namespace BudgetApp\Tests;

use PHPUnit\Framework\TestCase;
use BudgetApp\Services\AggregateService;
use BudgetApp\Database;

class AggregateServiceTest extends TestCase {
    private AggregateService $aggregator;
    private Database $db;

    protected function setUp(): void {
        // Mock database
        $this->db = $this->createMock(Database::class);
        $this->aggregator = new AggregateService($this->db);
    }

    public function testBuildAggregatesSuccess(): void {
        // Mock successful database operations
        $this->db->expects($this->exactly(3))
            ->method('query')
            ->willReturnOnConsecutiveCalls(
                [], // recurring income
                [], // recurring expenses
                []  // category trends
            );

        $this->db->expects($this->once())
            ->method('beginTransaction');

        $this->db->expects($this->once())
            ->method('commit');

        $result = $this->aggregator->buildAggregates(1, '2024-01-01', '2024-12-31');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('recurring_income', $result);
        $this->assertArrayHasKey('recurring_expenses', $result);
        $this->assertArrayHasKey('balances', $result);
        $this->assertArrayHasKey('monthly_summaries', $result);
        $this->assertArrayHasKey('category_trends', $result);
    }

    public function testBuildRecurringIncomeAggregates(): void {
        $mockIncomeData = [
            [
                'description' => 'Salary',
                'merchant_name' => 'Employer Inc',
                'amount' => 3000.00,
                'month_year' => '2024-01',
                'frequency' => 3,
                'avg_amount' => 3000.00,
                'first_occurrence' => '2024-01-01',
                'last_occurrence' => '2024-01-31'
            ]
        ];

        $this->db->expects($this->once())
            ->method('query')
            ->willReturn($mockIncomeData);

        $income = $this->invokePrivateMethod($this->aggregator, 'buildRecurringIncomeAggregates', [1, '2024-01-01', '2024-12-31']);

        $this->assertNotEmpty($income);
        $this->assertEquals('Salary', $income[0]['description']);
        $this->assertEquals(3000.00, $income[0]['avg_amount']);
        $this->assertArrayHasKey('recurrence_type', $income[0]);
        $this->assertArrayHasKey('total_annual', $income[0]);
    }

    public function testBuildRecurringExpensesAggregates(): void {
        $mockExpenseData = [
            [
                'description' => 'Rent Payment',
                'merchant_name' => 'Landlord',
                'amount' => -1200.00,
                'month_year' => '2024-01',
                'frequency' => 2,
                'avg_amount' => 1200.00,
                'first_occurrence' => '2024-01-01',
                'last_occurrence' => '2024-01-31',
                'category_name' => 'rent'
            ]
        ];

        $this->db->expects($this->once())
            ->method('query')
            ->willReturn($mockExpenseData);

        $expenses = $this->invokePrivateMethod($this->aggregator, 'buildRecurringExpensesAggregates', [1, '2024-01-01', '2024-12-31']);

        $this->assertNotEmpty($expenses);
        $this->assertEquals('Rent Payment', $expenses[0]['description']);
        $this->assertEquals(1200.00, $expenses[0]['avg_amount']);
        $this->assertEquals('rent', $expenses[0]['category_name']);
        $this->assertArrayHasKey('budget_impact', $expenses[0]);
    }

    public function testBuildBalanceAggregates(): void {
        $mockBalanceData = [
            [
                'account_name' => 'Checking Account',
                'account_type' => 'checking',
                'currency' => 'CZK',
                'date' => '2024-01-15',
                'inflows' => 5000.00,
                'outflows' => 1200.00,
                'running_balance' => 3800.00
            ]
        ];

        $this->db->expects($this->once())
            ->method('query')
            ->willReturn($mockBalanceData);

        $balances = $this->invokePrivateMethod($this->aggregator, 'buildBalanceAggregates', [1, '2024-01-01', '2024-12-31']);

        $this->assertNotEmpty($balances);
        $this->assertEquals('2024-01', $balances[0]['period']);
        $this->assertArrayHasKey('accounts', $balances[0]);
        $this->assertEquals('Checking Account', $balances[0]['accounts'][0]['name']);
    }

    public function testBuildMonthlySummaries(): void {
        $mockSummaryData = [
            [
                'period' => '2024-01',
                'transaction_count' => 45,
                'total_income' => 15000.00,
                'total_expenses' => 12000.00,
                'net_flow' => 3000.00,
                'avg_income_transaction' => 750.00,
                'avg_expense_transaction' => 266.67,
                'unique_categories_used' => 8,
                'unique_merchants' => 12
            ]
        ];

        $this->db->expects($this->once())
            ->method('query')
            ->willReturn($mockSummaryData);

        $this->db->expects($this->exactly(1))
            ->method('queryOne')
            ->willReturn([
                'budgeted' => 13000.00,
                'spent' => 12000.00
            ]);

        $summaries = $this->invokePrivateMethod($this->aggregator, 'buildMonthlySummaries', [1, '2024-01-01', '2024-12-31']);

        $this->assertNotEmpty($summaries);
        $this->assertEquals('2024-01', $summaries[0]['period']);
        $this->assertEquals(15000.00, $summaries[0]['total_income']);
        $this->assertArrayHasKey('budget_comparison', $summaries[0]);
    }

    public function testBuildCategoryTrends(): void {
        $mockTrendData = [
            [
                'category_name' => 'groceries',
                'category_type' => 'expense',
                'period' => '2024-01',
                'total_amount' => 2500.00,
                'transaction_count' => 8,
                'avg_transaction' => 312.50,
                'min_transaction' => 150.00,
                'max_transaction' => 800.00
            ]
        ];

        $this->db->expects($this->once())
            ->method('query')
            ->willReturn($mockTrendData);

        $trends = $this->invokePrivateMethod($this->aggregator, 'buildCategoryTrends', [1, '2024-01-01', '2024-12-31']);

        $this->assertNotEmpty($trends);
        $this->assertEquals('groceries', $trends[0]['category_name']);
        $this->assertArrayHasKey('monthly_data', $trends[0]);
        $this->assertArrayHasKey('trend_analysis', $trends[0]);
    }

    public function testAnalyzeRecurrencePattern(): void {
        // Test with sufficient recurring transactions
        $transaction = [
            'description' => 'Monthly Rent',
            'merchant_name' => 'Landlord',
            'amount' => -1200.00
        ];

        $this->db->expects($this->once())
            ->method('query')
            ->willReturn([
                ['date' => '2024-01-01'],
                ['date' => '2024-02-01'],
                ['date' => '2024-03-01']
            ]);

        $isRecurring = $this->invokePrivateMethod($this->aggregator, 'analyzeRecurrencePattern', [1, $transaction, '2024-01-01', '2024-12-31']);

        $this->assertTrue($isRecurring);
    }

    public function testAnalyzeRecurrencePatternInsufficientData(): void {
        // Test with insufficient recurring transactions
        $transaction = [
            'description' => 'One-time Purchase',
            'merchant_name' => 'Store',
            'amount' => -500.00
        ];

        $this->db->expects($this->once())
            ->method('query')
            ->willReturn([
                ['date' => '2024-01-15'] // Only one occurrence
            ]);

        $isRecurring = $this->invokePrivateMethod($this->aggregator, 'analyzeRecurrencePattern', [1, $transaction, '2024-01-01', '2024-12-31']);

        $this->assertFalse($isRecurring);
    }

    public function testDetermineRecurrenceType(): void {
        $transaction = [
            'description' => 'Monthly Subscription',
            'frequency' => 12 // Appears every month for a year
        ];

        $type = $this->invokePrivateMethod($this->aggregator, 'determineRecurrenceType', [$transaction]);

        $this->assertEquals('monthly', $type);
    }

    public function testCalculateAnnualTotal(): void {
        $transaction = [
            'avg_amount' => 1000.00
        ];

        $annual = $this->invokePrivateMethod($this->aggregator, 'calculateAnnualTotal', [$transaction]);

        $this->assertEquals(12000.00, $annual);
    }

    public function testCalculateConsistencyScore(): void {
        $transaction = [
            'frequency' => 10
        ];

        $score = $this->invokePrivateMethod($this->aggregator, 'calculateConsistencyScore', [1, $transaction, '2024-01-01', '2024-12-31']);

        $this->assertIsInt($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    public function testCalculateBudgetImpact(): void {
        $transaction = [
            'avg_amount' => 1200.00,
            'category_name' => 'rent'
        ];

        $this->db->expects($this->once())
            ->method('queryOne')
            ->willReturn([
                'amount' => 1300.00,
                'spent' => 1200.00
            ]);

        $impact = $this->invokePrivateMethod($this->aggregator, 'calculateBudgetImpact', [1, $transaction]);

        $this->assertArrayHasKey('budget_status', $impact);
        $this->assertArrayHasKey('impact_percentage', $impact);
        $this->assertEquals('within_budget', $impact['budget_status']);
    }

    public function testGetBudgetComparison(): void {
        $this->db->expects($this->once())
            ->method('query')
            ->willReturn([
                [
                    'category_name' => 'groceries',
                    'budgeted' => 3000.00,
                    'actual' => 2800.00
                ]
            ]);

        $comparison = $this->invokePrivateMethod($this->aggregator, 'getBudgetComparison', [1, '2024-01']);

        $this->assertArrayHasKey('budget_comparison', $comparison);
        $this->assertEquals(3000.00, $comparison['budget_comparison']['total_budgeted']);
        $this->assertEquals(2800.00, $comparison['budget_comparison']['total_actual']);
    }

    public function testAnalyzeCategoryTrend(): void {
        $monthlyData = [
            [
                'period' => '2024-01',
                'total_amount' => 2000.00
            ],
            [
                'period' => '2024-02',
                'total_amount' => 2200.00
            ],
            [
                'period' => '2024-03',
                'total_amount' => 2400.00
            ]
        ];

        $analysis = $this->invokePrivateMethod($this->aggregator, 'analyzeCategoryTrend', [$monthlyData]);

        $this->assertArrayHasKey('trend', $analysis);
        $this->assertArrayHasKey('change_percentage', $analysis);
        $this->assertEquals('increasing', $analysis['trend']);
        $this->assertEquals(20.0, $analysis['change_percentage']);
    }

    public function testMonthsBetween(): void {
        $months = $this->invokePrivateMethod($this->aggregator, 'monthsBetween', ['2024-01-01', '2024-12-31']);

        $this->assertEquals(12, $months);
    }

    public function testGetAggregateSummary(): void {
        $this->db->expects($this->once())
            ->method('queryOne')
            ->willReturn([
                'current_month_transactions' => 25,
                'last_month_transactions' => 30,
                'current_income' => 8000.00,
                'current_expenses' => 6500.00,
                'last_income' => 7500.00,
                'last_expenses' => 7000.00
            ]);

        $summary = $this->aggregator->getAggregateSummary(1);

        $this->assertArrayHasKey('current_month', $summary);
        $this->assertArrayHasKey('last_month', $summary);
        $this->assertArrayHasKey('changes', $summary);

        $this->assertEquals(25, $summary['current_month']['transactions']);
        $this->assertEquals(8000.00, $summary['current_month']['income']);
        $this->assertEquals(6500.00, $summary['current_month']['expenses']);
    }

    public function testCalculatePercentageChange(): void {
        $change = $this->invokePrivateMethod($this->aggregator, 'calculatePercentageChange', [100, 120]);

        $this->assertEquals(20.0, $change);

        // Test with zero old value
        $change = $this->invokePrivateMethod($this->aggregator, 'calculatePercentageChange', [0, 50]);

        $this->assertEquals(100.0, $change);
    }

    public function testGenerateCashFlowEvents(): void {
        $transactions = [
            [
                'date' => '2024-01-15',
                'amount' => 3000.00,
                'type' => 'credit'
            ],
            [
                'date' => '2024-01-16',
                'amount' => -500.00,
                'type' => 'debit'
            ],
            [
                'date' => '2024-01-17',
                'amount' => -300.00,
                'type' => 'debit'
            ]
        ];

        $events = $this->invokePrivateMethod($this->aggregator, 'generateCashFlowEvents', [$transactions]);

        $this->assertNotEmpty($events);
        $this->assertArrayHasKey('period', $events[0]);
        $this->assertArrayHasKey('income', $events[0]);
        $this->assertArrayHasKey('expenses', $events[0]);
        $this->assertArrayHasKey('net_flow', $events[0]);
        $this->assertEquals(3000.00, $events[0]['income']);
        $this->assertEquals(800.00, $events[0]['expenses']);
        $this->assertEquals(2200.00, $events[0]['net_flow']);
    }

    public function testGenerateCohorts(): void {
        $transactions = [
            [
                'date' => '2024-01-01',
                'amount' => -100.00,
                'merchant_name' => 'Tesco',
                'category_suggestion' => 'groceries'
            ],
            [
                'date' => '2024-01-15',
                'amount' => -120.00,
                'merchant_name' => 'Tesco',
                'category_suggestion' => 'groceries'
            ],
            [
                'date' => '2024-02-01',
                'amount' => -110.00,
                'merchant_name' => 'Tesco',
                'category_suggestion' => 'groceries'
            ]
        ];

        $cohorts = $this->invokePrivateMethod($this->aggregator, 'generateCohorts', [$transactions]);

        $this->assertNotEmpty($cohorts);
        $this->assertArrayHasKey('name', $cohorts[0]);
        $this->assertArrayHasKey('total_spent', $cohorts[0]);
        $this->assertArrayHasKey('cohort_type', $cohorts[0]);
        $this->assertEquals('Tesco', $cohorts[0]['name']);
        $this->assertEquals(330.00, $cohorts[0]['total_spent']);
    }

    public function testBuildAggregatesFailure(): void {
        // Mock database failure
        $this->db->expects($this->once())
            ->method('beginTransaction');

        $this->db->expects($this->once())
            ->method('rollback');

        $this->db->expects($this->any())
            ->method('query')
            ->willThrowException(new \Exception('Database error'));

        $result = $this->aggregator->buildAggregates(1, '2024-01-01', '2024-12-31');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
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