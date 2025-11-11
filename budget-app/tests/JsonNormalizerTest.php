<?php
namespace BudgetApp\Tests;

use PHPUnit\Framework\TestCase;
use BudgetApp\Services\JsonNormalizer;
use BudgetApp\Database;

class JsonNormalizerTest extends TestCase {
    private JsonNormalizer $normalizer;
    private Database $db;

    protected function setUp(): void {
        // Mock database for testing
        $this->db = $this->createMock(Database::class);
        $this->normalizer = new JsonNormalizer($this->db);
    }

    public function testNormalizeCsobFormat(): void {
        $csobData = [
            'ucet' => [
                'cisloUctu' => '1234567890',
                'nazevUctu' => 'Osobní účet',
                'mena' => 'CZK',
                'zustatek' => 15000.50,
                'dostupnyZustatek' => 14500.50,
                'typUctu' => 'běžný',
                'iban' => 'CZ1234567890123456789012',
                'bic' => 'KOMBCZPP'
            ],
            'transakce' => [
                [
                    'id' => 'tx001',
                    'datum' => '2024-01-15',
                    'castka' => -500.00,
                    'mena' => 'CZK',
                    'typ' => 'výběr',
                    'popis' => 'Výběr hotovosti ATM Praha',
                    'referencniCislo' => 'REF001',
                    'zustatekPo' => 14500.50
                ],
                [
                    'id' => 'tx002',
                    'datum' => '2024-01-16',
                    'castka' => 2500.00,
                    'mena' => 'CZK',
                    'typ' => 'převod',
                    'popis' => 'Výplata - zaměstnavatel s.r.o.',
                    'referencniCislo' => 'SAL001',
                    'zustatekPo' => 17000.50
                ]
            ]
        ];

        $result = $this->normalizer->normalizeBankHistory($csobData, 1);

        $this->assertArrayHasKey('accounts', $result);
        $this->assertArrayHasKey('transactions', $result);
        $this->assertArrayHasKey('merchants', $result);
        $this->assertArrayHasKey('cohorts', $result);
        $this->assertArrayHasKey('cash_flow_events', $result);
        $this->assertArrayHasKey('metadata', $result);

        // Test account normalization
        $this->assertCount(1, $result['accounts']);
        $this->assertEquals('1234567890', $result['accounts'][0]['account_number']);
        $this->assertEquals('ČSOB', $result['accounts'][0]['bank_name']);
        $this->assertEquals(15000.50, $result['accounts'][0]['balance']);

        // Test transaction normalization
        $this->assertCount(2, $result['transactions']);
        $this->assertEquals('tx001', $result['transactions'][0]['transaction_id']);
        $this->assertEquals(-500.00, $result['transactions'][0]['amount']);
        $this->assertEquals('debit', $result['transactions'][0]['type']);
        $this->assertEquals('ATM Praha', $result['transactions'][0]['merchant_name']);

        $this->assertEquals(2500.00, $result['transactions'][1]['amount']);
        $this->assertEquals('credit', $result['transactions'][1]['type']);
        $this->assertEquals('zaměstnavatel s.r.o.', $result['transactions'][1]['merchant_name']);
    }

    public function testNormalizeCeskaSporitelnaFormat(): void {
        $csData = [
            'account' => [
                'number' => '9876543210',
                'name' => 'Spořicí účet',
                'currency' => 'CZK',
                'balance' => 25000.00,
                'availableBalance' => 25000.00,
                'type' => 'spořicí'
            ],
            'transactions' => [
                [
                    'id' => 'cs001',
                    'date' => '2024-01-20',
                    'amount' => -1200.00,
                    'description' => 'Nákup Tesco Palackého',
                    'reference' => 'TES001',
                    'balanceAfter' => 23800.00
                ]
            ]
        ];

        $result = $this->normalizer->normalizeBankHistory($csData, 1);

        $this->assertEquals('9876543210', $result['accounts'][0]['account_number']);
        $this->assertEquals('Česká spořitelna', $result['accounts'][0]['bank_name']);
        $this->assertEquals('Tesco Palackého', $result['transactions'][0]['merchant_name']);
        $this->assertEquals('groceries', $result['transactions'][0]['category_suggestion']);
    }

    public function testNormalizeKomercniBankaFormat(): void {
        $kbData = [
            'accountNumber' => '555666777',
            'accountName' => 'Firemní účet',
            'currency' => 'CZK',
            'balance' => 50000.00,
            'availableBalance' => 48000.00,
            'accountType' => 'firemní',
            'transactionList' => [
                [
                    'transactionId' => 'kb001',
                    'date' => '2024-01-25',
                    'amount' => 15000.00,
                    'description' => 'Faktura za služby - Klient XYZ',
                    'referenceNumber' => 'INV001',
                    'balanceAfter' => 65000.00
                ]
            ]
        ];

        $result = $this->normalizer->normalizeBankHistory($kbData, 1);

        $this->assertEquals('555666777', $result['accounts'][0]['account_number']);
        $this->assertEquals('Komerční banka', $result['accounts'][0]['bank_name']);
        $this->assertEquals('Klient XYZ', $result['transactions'][0]['merchant_name']);
        $this->assertEquals('income', $result['transactions'][0]['category_suggestion']);
    }

    public function testNormalizeGenericFormat(): void {
        $genericData = [
            'accounts' => [
                [
                    'account_number' => '111222333',
                    'account_name' => 'Generic Account',
                    'bank_name' => 'Generic Bank',
                    'currency' => 'CZK',
                    'balance' => 10000.00
                ]
            ],
            'transactions' => [
                [
                    'transaction_id' => 'gen001',
                    'date' => '2024-01-30',
                    'amount' => -250.00,
                    'description' => 'Generic transaction',
                    'balance_after' => 9750.00
                ]
            ]
        ];

        $result = $this->normalizer->normalizeBankHistory($genericData, 1);

        $this->assertEquals('111222333', $result['accounts'][0]['account_number']);
        $this->assertEquals('Generic Bank', $result['accounts'][0]['bank_name']);
        $this->assertEquals('gen001', $result['transactions'][0]['transaction_id']);
    }

    public function testDateNormalization(): void {
        // Test various date formats
        $testDates = [
            '15.01.2024' => '2024-01-15',
            '2024-01-15' => '2024-01-15',
            '01/15/2024' => '2024-01-15',
            '15.1.2024' => '2024-01-15'
        ];

        foreach ($testDates as $input => $expected) {
            $normalized = $this->invokePrivateMethod($this->normalizer, 'normalizeDate', [$input]);
            $this->assertEquals($expected, $normalized, "Failed to normalize date: $input");
        }
    }

    public function testMerchantNameExtraction(): void {
        $testCases = [
            'Výběr hotovosti ATM Praha' => 'ATM Praha',
            'Převod od Jan Novák' => 'Jan Novák',
            'Nákup Lidl Praha 4' => 'Lidl Praha 4',
            'PLATBA KARTOU Tesco' => 'Tesco',
            'UBYTOVÁNÍ Hotel Central' => 'Hotel Central'
        ];

        foreach ($testCases as $description => $expected) {
            $extracted = $this->invokePrivateMethod($this->normalizer, 'extractMerchantName', [$description]);
            $this->assertEquals($expected, $extracted, "Failed to extract merchant from: $description");
        }
    }

    public function testCategorySuggestion(): void {
        $testCases = [
            'Výběr hotovosti ATM' => 'banking',
            'Nákup Tesco supermarket' => 'groceries',
            'Restaurace U Fleků' => 'food',
            'Výplata zaměstnavatel' => 'salary',
            'Nájem byt' => 'rent',
            'Elektřina ČEZ' => 'utilities'
        ];

        foreach ($testCases as $description => $expected) {
            $suggested = $this->invokePrivateMethod($this->normalizer, 'suggestCategory', [$description]);
            $this->assertEquals($expected, $suggested, "Failed to suggest category for: $description");
        }
    }

    public function testCashFlowEventsGeneration(): void {
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

        $events = $this->invokePrivateMethod($this->normalizer, 'generateCashFlowEvents', [$transactions]);

        $this->assertNotEmpty($events);
        $this->assertArrayHasKey('period', $events[0]);
        $this->assertArrayHasKey('income', $events[0]);
        $this->assertArrayHasKey('expenses', $events[0]);
        $this->assertArrayHasKey('net_flow', $events[0]);
    }

    public function testCohortGeneration(): void {
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

        $cohorts = $this->invokePrivateMethod($this->normalizer, 'generateCohorts', [$transactions]);

        $this->assertNotEmpty($cohorts);
        $this->assertArrayHasKey('name', $cohorts[0]);
        $this->assertArrayHasKey('total_spent', $cohorts[0]);
        $this->assertArrayHasKey('cohort_type', $cohorts[0]);
    }

    public function testValidation(): void {
        $invalidData = [
            'accounts' => [
                ['account_number' => ''] // Invalid: missing account number
            ],
            'transactions' => [
                ['date' => '', 'amount' => 'invalid'] // Invalid: bad date and amount
            ],
            'merchants' => [],
            'cohorts' => [],
            'cash_flow_events' => []
        ];

        $result = $this->invokePrivateMethod($this->normalizer, 'validateNormalizedData', [$invalidData]);

        $this->assertEmpty($result['accounts'], 'Should reject accounts without account numbers');
        $this->assertEmpty($result['transactions'], 'Should reject transactions with invalid data');
    }

    public function testErrorHandling(): void {
        $invalidJson = "invalid json string";

        // Should handle gracefully without throwing exceptions
        $result = $this->normalizer->normalizeBankHistory($invalidJson, 1);

        $this->assertArrayHasKey('metadata', $result);
        $this->assertArrayHasKey('errors', $result['metadata']);
        $this->assertNotEmpty($result['metadata']['errors']);
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