<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

class JsonNormalizer {
    private Database $db;
    private array $errors = [];
    private array $warnings = [];

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Normalize bank history JSON data into structured format
     * Supports multiple bank formats and normalizes to standard structure
     */
    public function normalizeBankHistory(array $jsonData, int $userId): array {
        $this->errors = [];
        $this->warnings = [];

        try {
            // Detect bank format
            $format = $this->detectBankFormat($jsonData);

            // Normalize based on format
            switch ($format) {
                case 'csob':
                    $normalized = $this->normalizeCsobFormat($jsonData);
                    break;
                case 'ceska_sporitelna':
                    $normalized = $this->normalizeCeskaSporitelnaFormat($jsonData);
                    break;
                case 'komercni_banka':
                    $normalized = $this->normalizeKomercniBankaFormat($jsonData);
                    break;
                case 'generic':
                default:
                    $normalized = $this->normalizeGenericFormat($jsonData);
                    break;
            }

            // Validate normalized data
            $validated = $this->validateNormalizedData($normalized);

            // Create structured output
            return [
                'accounts' => $validated['accounts'],
                'transactions' => $validated['transactions'],
                'merchants' => $validated['merchants'],
                'cohorts' => $validated['cohorts'],
                'cash_flow_events' => $validated['cash_flow_events'],
                'metadata' => [
                    'format_detected' => $format,
                    'total_accounts' => count($validated['accounts']),
                    'total_transactions' => count($validated['transactions']),
                    'total_merchants' => count($validated['merchants']),
                    'errors' => $this->errors,
                    'warnings' => $this->warnings,
                    'normalized_at' => date('Y-m-d H:i:s')
                ]
            ];

        } catch (\Exception $e) {
            $this->errors[] = "Normalization failed: " . $e->getMessage();
            return [
                'accounts' => [],
                'transactions' => [],
                'merchants' => [],
                'cohorts' => [],
                'cash_flow_events' => [],
                'metadata' => [
                    'format_detected' => 'unknown',
                    'total_accounts' => 0,
                    'total_transactions' => 0,
                    'total_merchants' => 0,
                    'errors' => $this->errors,
                    'warnings' => $this->warnings,
                    'normalized_at' => date('Y-m-d H:i:s')
                ]
            ];
        }
    }

    /**
     * Detect bank format from JSON structure
     */
    private function detectBankFormat(array $jsonData): string {
        // Check for ČSOB format
        if (isset($jsonData['ucet']) && isset($jsonData['transakce'])) {
            return 'csob';
        }

        // Check for Česká spořitelna format
        if (isset($jsonData['account']) && isset($jsonData['transactions'])) {
            return 'ceska_sporitelna';
        }

        // Check for Komerční banka format
        if (isset($jsonData['accountNumber']) && isset($jsonData['transactionList'])) {
            return 'komercni_banka';
        }

        // Generic format
        return 'generic';
    }

    /**
     * Normalize ČSOB bank format
     */
    private function normalizeCsobFormat(array $jsonData): array {
        $accounts = [];
        $transactions = [];
        $merchants = [];
        $cohorts = [];
        $cashFlowEvents = [];

        // Extract account information
        if (isset($jsonData['ucet'])) {
            $account = [
                'account_number' => $jsonData['ucet']['cisloUctu'] ?? '',
                'account_name' => $jsonData['ucet']['nazevUctu'] ?? 'ČSOB Account',
                'bank_name' => 'ČSOB',
                'currency' => $jsonData['ucet']['mena'] ?? 'CZK',
                'balance' => (float)($jsonData['ucet']['zustatek'] ?? 0),
                'available_balance' => (float)($jsonData['ucet']['dostupnyZustatek'] ?? 0),
                'account_type' => $this->mapAccountType($jsonData['ucet']['typUctu'] ?? 'checking'),
                'iban' => $jsonData['ucet']['iban'] ?? null,
                'bic' => $jsonData['ucet']['bic'] ?? null
            ];
            $accounts[] = $account;
        }

        // Extract transactions
        if (isset($jsonData['transakce']) && is_array($jsonData['transakce'])) {
            foreach ($jsonData['transakce'] as $tx) {
                $transaction = [
                    'transaction_id' => $tx['id'] ?? uniqid(),
                    'account_number' => $jsonData['ucet']['cisloUctu'] ?? '',
                    'date' => $this->normalizeDate($tx['datum'] ?? ''),
                    'amount' => (float)($tx['castka'] ?? 0),
                    'currency' => $tx['mena'] ?? 'CZK',
                    'type' => $this->mapTransactionType($tx['typ'] ?? ''),
                    'description' => $tx['popis'] ?? '',
                    'merchant_name' => $this->extractMerchantName($tx['popis'] ?? ''),
                    'reference_number' => $tx['referencniCislo'] ?? null,
                    'variable_symbol' => $tx['variabilniSymbol'] ?? null,
                    'constant_symbol' => $tx['konstantniSymbol'] ?? null,
                    'specific_symbol' => $tx['specifickySymbol'] ?? null,
                    'counterparty_account' => $tx['protiucet'] ?? null,
                    'counterparty_bank' => $tx['bankaProtiuctu'] ?? null,
                    'counterparty_name' => $tx['nazevProtiuctu'] ?? null,
                    'balance_after' => (float)($tx['zustatekPo'] ?? 0),
                    'fee' => (float)($tx['poplatek'] ?? 0),
                    'exchange_rate' => (float)($tx['kurz'] ?? 1.0),
                    'category_suggestion' => $this->suggestCategory($tx['popis'] ?? ''),
                    'tags' => $this->extractTags($tx['popis'] ?? ''),
                    'raw_data' => json_encode($tx)
                ];
                $transactions[] = $transaction;

                // Extract merchant if not already present
                if ($transaction['merchant_name']) {
                    $merchantKey = strtolower($transaction['merchant_name']);
                    if (!isset($merchants[$merchantKey])) {
                        $merchants[$merchantKey] = [
                            'name' => $transaction['merchant_name'],
                            'frequency' => 1,
                            'total_amount' => abs($transaction['amount']),
                            'categories' => [$transaction['category_suggestion']],
                            'last_seen' => $transaction['date']
                        ];
                    } else {
                        $merchants[$merchantKey]['frequency']++;
                        $merchants[$merchantKey]['total_amount'] += abs($transaction['amount']);
                        if (!in_array($transaction['category_suggestion'], $merchants[$merchantKey]['categories'])) {
                            $merchants[$merchantKey]['categories'][] = $transaction['category_suggestion'];
                        }
                        if ($transaction['date'] > $merchants[$merchantKey]['last_seen']) {
                            $merchants[$merchantKey]['last_seen'] = $transaction['date'];
                        }
                    }
                }
            }
        }

        // Convert merchants array to indexed array
        $merchants = array_values($merchants);

        // Generate cash flow events
        $cashFlowEvents = $this->generateCashFlowEvents($transactions);

        // Generate cohorts
        $cohorts = $this->generateCohorts($transactions);

        return [
            'accounts' => $accounts,
            'transactions' => $transactions,
            'merchants' => $merchants,
            'cohorts' => $cohorts,
            'cash_flow_events' => $cashFlowEvents
        ];
    }

    /**
     * Normalize Česká spořitelna format
     */
    private function normalizeCeskaSporitelnaFormat(array $jsonData): array {
        // Similar structure to ČSOB but with different field names
        $accounts = [];
        $transactions = [];
        $merchants = [];
        $cohorts = [];
        $cashFlowEvents = [];

        if (isset($jsonData['account'])) {
            $account = [
                'account_number' => $jsonData['account']['number'] ?? '',
                'account_name' => $jsonData['account']['name'] ?? 'Česká spořitelna Account',
                'bank_name' => 'Česká spořitelna',
                'currency' => $jsonData['account']['currency'] ?? 'CZK',
                'balance' => (float)($jsonData['account']['balance'] ?? 0),
                'available_balance' => (float)($jsonData['account']['availableBalance'] ?? 0),
                'account_type' => $this->mapAccountType($jsonData['account']['type'] ?? 'checking'),
                'iban' => $jsonData['account']['iban'] ?? null,
                'bic' => $jsonData['account']['bic'] ?? null
            ];
            $accounts[] = $account;
        }

        if (isset($jsonData['transactions']) && is_array($jsonData['transactions'])) {
            foreach ($jsonData['transactions'] as $tx) {
                $transaction = [
                    'transaction_id' => $tx['id'] ?? uniqid(),
                    'account_number' => $jsonData['account']['number'] ?? '',
                    'date' => $this->normalizeDate($tx['date'] ?? ''),
                    'amount' => (float)($tx['amount'] ?? 0),
                    'currency' => $tx['currency'] ?? 'CZK',
                    'type' => $this->mapTransactionType($tx['type'] ?? ''),
                    'description' => $tx['description'] ?? '',
                    'merchant_name' => $this->extractMerchantName($tx['description'] ?? ''),
                    'reference_number' => $tx['reference'] ?? null,
                    'counterparty_account' => $tx['counterpartyAccount'] ?? null,
                    'counterparty_bank' => $tx['counterpartyBank'] ?? null,
                    'counterparty_name' => $tx['counterpartyName'] ?? null,
                    'balance_after' => (float)($tx['balanceAfter'] ?? 0),
                    'category_suggestion' => $this->suggestCategory($tx['description'] ?? ''),
                    'tags' => $this->extractTags($tx['description'] ?? ''),
                    'raw_data' => json_encode($tx)
                ];
                $transactions[] = $transaction;
            }
        }

        $merchants = $this->extractMerchantsFromTransactions($transactions);
        $cashFlowEvents = $this->generateCashFlowEvents($transactions);
        $cohorts = $this->generateCohorts($transactions);

        return [
            'accounts' => $accounts,
            'transactions' => $transactions,
            'merchants' => $merchants,
            'cohorts' => $cohorts,
            'cash_flow_events' => $cashFlowEvents
        ];
    }

    /**
     * Normalize Komerční banka format
     */
    private function normalizeKomercniBankaFormat(array $jsonData): array {
        $accounts = [];
        $transactions = [];
        $merchants = [];
        $cohorts = [];
        $cashFlowEvents = [];

        if (isset($jsonData['accountNumber'])) {
            $account = [
                'account_number' => $jsonData['accountNumber'],
                'account_name' => $jsonData['accountName'] ?? 'Komerční banka Account',
                'bank_name' => 'Komerční banka',
                'currency' => $jsonData['currency'] ?? 'CZK',
                'balance' => (float)($jsonData['balance'] ?? 0),
                'available_balance' => (float)($jsonData['availableBalance'] ?? 0),
                'account_type' => $this->mapAccountType($jsonData['accountType'] ?? 'checking'),
                'iban' => $jsonData['iban'] ?? null,
                'bic' => $jsonData['bic'] ?? null
            ];
            $accounts[] = $account;
        }

        if (isset($jsonData['transactionList']) && is_array($jsonData['transactionList'])) {
            foreach ($jsonData['transactionList'] as $tx) {
                $transaction = [
                    'transaction_id' => $tx['transactionId'] ?? uniqid(),
                    'account_number' => $jsonData['accountNumber'],
                    'date' => $this->normalizeDate($tx['date'] ?? ''),
                    'amount' => (float)($tx['amount'] ?? 0),
                    'currency' => $tx['currency'] ?? 'CZK',
                    'type' => $this->mapTransactionType($tx['type'] ?? ''),
                    'description' => $tx['description'] ?? '',
                    'merchant_name' => $this->extractMerchantName($tx['description'] ?? ''),
                    'reference_number' => $tx['referenceNumber'] ?? null,
                    'counterparty_account' => $tx['counterpartyAccount'] ?? null,
                    'counterparty_bank' => $tx['counterpartyBank'] ?? null,
                    'counterparty_name' => $tx['counterpartyName'] ?? null,
                    'balance_after' => (float)($tx['balanceAfter'] ?? 0),
                    'category_suggestion' => $this->suggestCategory($tx['description'] ?? ''),
                    'tags' => $this->extractTags($tx['description'] ?? ''),
                    'raw_data' => json_encode($tx)
                ];
                $transactions[] = $transaction;
            }
        }

        $merchants = $this->extractMerchantsFromTransactions($transactions);
        $cashFlowEvents = $this->generateCashFlowEvents($transactions);
        $cohorts = $this->generateCohorts($transactions);

        return [
            'accounts' => $accounts,
            'transactions' => $transactions,
            'merchants' => $merchants,
            'cohorts' => $cohorts,
            'cash_flow_events' => $cashFlowEvents
        ];
    }

    /**
     * Normalize generic JSON format
     */
    private function normalizeGenericFormat(array $jsonData): array {
        $accounts = [];
        $transactions = [];
        $merchants = [];
        $cohorts = [];
        $cashFlowEvents = [];

        // Try to extract accounts
        if (isset($jsonData['accounts']) && is_array($jsonData['accounts'])) {
            foreach ($jsonData['accounts'] as $acc) {
                $accounts[] = [
                    'account_number' => $acc['account_number'] ?? $acc['number'] ?? '',
                    'account_name' => $acc['account_name'] ?? $acc['name'] ?? 'Account',
                    'bank_name' => $acc['bank_name'] ?? $acc['bank'] ?? 'Unknown Bank',
                    'currency' => $acc['currency'] ?? 'CZK',
                    'balance' => (float)($acc['balance'] ?? 0),
                    'available_balance' => (float)($acc['available_balance'] ?? $acc['balance'] ?? 0),
                    'account_type' => $this->mapAccountType($acc['account_type'] ?? $acc['type'] ?? 'checking'),
                    'iban' => $acc['iban'] ?? null,
                    'bic' => $acc['bic'] ?? null
                ];
            }
        }

        // Try to extract transactions
        if (isset($jsonData['transactions']) && is_array($jsonData['transactions'])) {
            foreach ($jsonData['transactions'] as $tx) {
                $transactions[] = [
                    'transaction_id' => $tx['transaction_id'] ?? $tx['id'] ?? uniqid(),
                    'account_number' => $tx['account_number'] ?? $tx['account'] ?? '',
                    'date' => $this->normalizeDate($tx['date'] ?? ''),
                    'amount' => (float)($tx['amount'] ?? 0),
                    'currency' => $tx['currency'] ?? 'CZK',
                    'type' => $this->mapTransactionType($tx['type'] ?? ''),
                    'description' => $tx['description'] ?? '',
                    'merchant_name' => $this->extractMerchantName($tx['description'] ?? ''),
                    'reference_number' => $tx['reference_number'] ?? $tx['reference'] ?? null,
                    'counterparty_account' => $tx['counterparty_account'] ?? null,
                    'counterparty_bank' => $tx['counterparty_bank'] ?? null,
                    'counterparty_name' => $tx['counterparty_name'] ?? null,
                    'balance_after' => (float)($tx['balance_after'] ?? 0),
                    'category_suggestion' => $this->suggestCategory($tx['description'] ?? ''),
                    'tags' => $this->extractTags($tx['description'] ?? ''),
                    'raw_data' => json_encode($tx)
                ];
            }
        }

        $merchants = $this->extractMerchantsFromTransactions($transactions);
        $cashFlowEvents = $this->generateCashFlowEvents($transactions);
        $cohorts = $this->generateCohorts($transactions);

        return [
            'accounts' => $accounts,
            'transactions' => $transactions,
            'merchants' => $merchants,
            'cohorts' => $cohorts,
            'cash_flow_events' => $cashFlowEvents
        ];
    }

    /**
     * Validate normalized data
     */
    private function validateNormalizedData(array $data): array {
        $validated = [
            'accounts' => [],
            'transactions' => [],
            'merchants' => [],
            'cohorts' => [],
            'cash_flow_events' => []
        ];

        // Validate accounts
        foreach ($data['accounts'] as $account) {
            if (empty($account['account_number'])) {
                $this->warnings[] = "Account missing account number";
                continue;
            }
            $validated['accounts'][] = $account;
        }

        // Validate transactions
        foreach ($data['transactions'] as $transaction) {
            if (empty($transaction['date']) || !is_numeric($transaction['amount'])) {
                $this->warnings[] = "Transaction missing required fields: " . json_encode($transaction);
                continue;
            }
            $validated['transactions'][] = $transaction;
        }

        // Copy other data as-is (they're generated from transactions)
        $validated['merchants'] = $data['merchants'];
        $validated['cohorts'] = $data['cohorts'];
        $validated['cash_flow_events'] = $data['cash_flow_events'];

        return $validated;
    }

    /**
     * Map account type to standard values
     */
    private function mapAccountType(string $type): string {
        $type = strtolower($type);
        $mapping = [
            'checking' => 'checking',
            'savings' => 'savings',
            'credit_card' => 'credit_card',
            'loan' => 'loan',
            'investment' => 'investment',
            'běžný' => 'checking',
            'spořicí' => 'savings',
            'kreditní_karta' => 'credit_card',
            'úvěr' => 'loan',
            'investice' => 'investment'
        ];

        return $mapping[$type] ?? 'checking';
    }

    /**
     * Map transaction type to standard values
     */
    private function mapTransactionType(string $type): string {
        $type = strtolower($type);
        $mapping = [
            'debit' => 'debit',
            'credit' => 'credit',
            'transfer' => 'transfer',
            'fee' => 'fee',
            'interest' => 'interest',
            'dividend' => 'dividend',
            'výběr' => 'debit',
            'vklad' => 'credit',
            'převod' => 'transfer',
            'poplatek' => 'fee',
            'úrok' => 'interest',
            'dividenda' => 'dividend'
        ];

        return $mapping[$type] ?? 'debit';
    }

    /**
     * Normalize date to YYYY-MM-DD format
     */
    private function normalizeDate(string $dateStr): string {
        if (empty($dateStr)) {
            return date('Y-m-d');
        }

        // Try different date formats
        $formats = [
            'Y-m-d',
            'd.m.Y',
            'd/m/Y',
            'm/d/Y',
            'Y-m-d H:i:s',
            'd.m.Y H:i:s'
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $dateStr);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        // If no format matches, try to parse with strtotime
        $timestamp = strtotime($dateStr);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        $this->warnings[] = "Could not parse date: {$dateStr}";
        return date('Y-m-d');
    }

    /**
     * Extract merchant name from transaction description
     */
    private function extractMerchantName(string $description): string {
        // Common Czech merchant patterns
        $patterns = [
            '/^([A-Z][A-Z\s]+[A-Z])\s/', // ALL CAPS merchant name
            '/^([A-Z][a-z\s]+[a-z])\s/', // Title case merchant name
            '/(\w+)\s+\d+\/\d+/', // Merchant before date
            '/(\w+)\s+\d+\.\d+\./', // Merchant before date
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $description, $matches)) {
                $merchant = trim($matches[1]);
                if (strlen($merchant) > 2 && !is_numeric($merchant)) {
                    return $merchant;
                }
            }
        }

        // Fallback: take first 3 words
        $words = explode(' ', $description);
        $merchant = implode(' ', array_slice($words, 0, min(3, count($words))));
        return trim($merchant);
    }

    /**
     * Suggest category based on description
     */
    private function suggestCategory(string $description): string {
        $desc = strtolower($description);

        // Czech category patterns
        $patterns = [
            'food' => ['restaurant', 'restaurace', 'jídlo', 'oběd', 'večeře', 'snídaně', 'kavárna', 'cafe'],
            'groceries' => ['supermarket', 'supermarket', 'tesco', 'albert', 'lidl', 'kaufland', 'penny', 'billa'],
            'transport' => ['benzín', 'benzin', 'diesel', 'čerpací stanice', 'vlak', 'bus', 'tram', 'metro', 'taxi', 'uber'],
            'utilities' => ['elektřina', 'elektrina', 'voda', 'plyn', 'internet', 'telefon', 'vodafone', 'o2', 't-mobile'],
            'entertainment' => ['kino', 'divadlo', 'koncert', 'kniha', 'knihy', 'spotify', 'netflix', 'hbo'],
            'shopping' => ['obchod', 'nákup', 'nakup', 'zara', 'hm', 'ikea', 'mall', 'nákupní centrum'],
            'healthcare' => ['lékař', 'lekar', 'zubař', 'zubar', 'lékárna', 'lekárna', 'nemocnice', 'ordinace'],
            'salary' => ['plat', 'mzda', 'výplata', 'vyplata', 'salary', 'payroll'],
            'rent' => ['nájem', 'najem', 'rent', 'nájemné', 'najemne'],
            'insurance' => ['pojištění', 'pojisteni', 'pojišťovna', 'pojistovna']
        ];

        foreach ($patterns as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($desc, $keyword) !== false) {
                    return $category;
                }
            }
        }

        return 'other';
    }

    /**
     * Extract tags from description
     */
    private function extractTags(string $description): array {
        $tags = [];
        $desc = strtolower($description);

        // Common tags
        $tagPatterns = [
            'online' => ['online', 'internet', 'web'],
            'cash' => ['hotovost', 'cash', 'výběr', 'vyber'],
            'card' => ['karta', 'card', 'debit', 'credit'],
            'recurring' => ['měsíční', 'mesicni', 'roční', 'rocni', 'subscription']
        ];

        foreach ($tagPatterns as $tag => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($desc, $keyword) !== false) {
                    $tags[] = $tag;
                    break;
                }
            }
        }

        return array_unique($tags);
    }

    /**
     * Extract merchants from transactions
     */
    private function extractMerchantsFromTransactions(array $transactions): array {
        $merchants = [];

        foreach ($transactions as $transaction) {
            if (!empty($transaction['merchant_name'])) {
                $key = strtolower($transaction['merchant_name']);
                if (!isset($merchants[$key])) {
                    $merchants[$key] = [
                        'name' => $transaction['merchant_name'],
                        'frequency' => 1,
                        'total_amount' => abs($transaction['amount']),
                        'categories' => [$transaction['category_suggestion']],
                        'last_seen' => $transaction['date']
                    ];
                } else {
                    $merchants[$key]['frequency']++;
                    $merchants[$key]['total_amount'] += abs($transaction['amount']);
                    if (!in_array($transaction['category_suggestion'], $merchants[$key]['categories'])) {
                        $merchants[$key]['categories'][] = $transaction['category_suggestion'];
                    }
                    if ($transaction['date'] > $merchants[$key]['last_seen']) {
                        $merchants[$key]['last_seen'] = $transaction['date'];
                    }
                }
            }
        }

        return array_values($merchants);
    }

    /**
     * Generate cash flow events from transactions
     */
    private function generateCashFlowEvents(array $transactions): array {
        $events = [];
        $monthlyFlows = [];

        // Group transactions by month
        foreach ($transactions as $transaction) {
            $month = date('Y-m', strtotime($transaction['date']));
            if (!isset($monthlyFlows[$month])) {
                $monthlyFlows[$month] = [
                    'income' => 0,
                    'expenses' => 0,
                    'transactions' => []
                ];
            }

            if ($transaction['amount'] > 0) {
                $monthlyFlows[$month]['income'] += $transaction['amount'];
            } else {
                $monthlyFlows[$month]['expenses'] += abs($transaction['amount']);
            }

            $monthlyFlows[$month]['transactions'][] = $transaction;
        }

        // Generate cash flow events
        foreach ($monthlyFlows as $month => $flow) {
            $netFlow = $flow['income'] - $flow['expenses'];

            $events[] = [
                'period' => $month,
                'income' => $flow['income'],
                'expenses' => $flow['expenses'],
                'net_flow' => $netFlow,
                'transaction_count' => count($flow['transactions']),
                'flow_type' => $netFlow > 0 ? 'surplus' : 'deficit',
                'magnitude' => abs($netFlow)
            ];
        }

        return $events;
    }

    /**
     * Generate spending cohorts from transactions
     */
    private function generateCohorts(array $transactions): array {
        $cohorts = [];
        $merchantSpending = [];

        // Group by merchant and calculate spending patterns
        foreach ($transactions as $transaction) {
            if ($transaction['amount'] < 0 && !empty($transaction['merchant_name'])) {
                $merchant = strtolower($transaction['merchant_name']);
                if (!isset($merchantSpending[$merchant])) {
                    $merchantSpending[$merchant] = [
                        'name' => $transaction['merchant_name'],
                        'total_spent' => 0,
                        'transaction_count' => 0,
                        'dates' => [],
                        'categories' => [],
                        'avg_transaction' => 0
                    ];
                }

                $merchantSpending[$merchant]['total_spent'] += abs($transaction['amount']);
                $merchantSpending[$merchant]['transaction_count']++;
                $merchantSpending[$merchant]['dates'][] = $transaction['date'];
                if (!in_array($transaction['category_suggestion'], $merchantSpending[$merchant]['categories'])) {
                    $merchantSpending[$merchant]['categories'][] = $transaction['category_suggestion'];
                }
            }
        }

        // Calculate cohort metrics
        foreach ($merchantSpending as $merchant => $data) {
            if ($data['transaction_count'] > 1) {
                $data['avg_transaction'] = $data['total_spent'] / $data['transaction_count'];

                // Calculate frequency (days between transactions)
                sort($data['dates']);
                $intervals = [];
                for ($i = 1; $i < count($data['dates']); $i++) {
                    $intervals[] = (strtotime($data['dates'][$i]) - strtotime($data['dates'][$i-1])) / (60*60*24);
                }
                $data['avg_frequency_days'] = count($intervals) > 0 ? array_sum($intervals) / count($intervals) : null;

                // Determine cohort type
                if ($data['avg_frequency_days'] <= 7) {
                    $data['cohort_type'] = 'weekly';
                } elseif ($data['avg_frequency_days'] <= 30) {
                    $data['cohort_type'] = 'monthly';
                } elseif ($data['avg_frequency_days'] <= 90) {
                    $data['cohort_type'] = 'quarterly';
                } else {
                    $data['cohort_type'] = 'irregular';
                }

                $cohorts[] = $data;
            }
        }

        // Sort by total spending (highest first)
        usort($cohorts, function($a, $b) {
            return $b['total_spent'] <=> $a['total_spent'];
        });

        return $cohorts;
    }

    /**
     * Get normalization errors
     */
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * Get normalization warnings
     */
    public function getWarnings(): array {
        return $this->warnings;
    }
}