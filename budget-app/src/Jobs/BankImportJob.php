<?php
// Bank Import Background Job
namespace BudgetApp\Jobs;

use BudgetApp\Database;

class BankImportJob {
    private Database $db;
    private string $jobId;
    private int $userId;
    private string $bankJsonFolder = '/var/www/html/user-data/bank-json';

    public function __construct(Database $db, string $jobId, int $userId) {
        $this->db = $db;
        $this->jobId = $jobId;
        $this->userId = $userId;
    }

    /**
     * Execute the import job
     */
    public function execute(): void {
        try {
            // Mark job as processing
            $this->updateJobStatus('processing', null);

            // Get all JSON files
            $jsonFiles = glob($this->bankJsonFolder . '/*.json') ?: [];

            if (empty($jsonFiles)) {
                $this->updateJobStatus('completed', json_encode([
                    'success' => 0,
                    'failed' => 0,
                    'files' => [],
                    'message' => 'No JSON files found'
                ]));
                return;
            }

            $results = [
                'success' => 0,
                'failed' => 0,
                'files' => [],
            ];

            // Update total files
            $this->db->update('bank_import_jobs', [
                'total_files' => count($jsonFiles),
                'started_at' => date('Y-m-d H:i:s'),
            ], [
                'job_id' => $this->jobId
            ]);

            // Process each file
            foreach ($jsonFiles as $filepath) {
                try {
                    $result = $this->processBankJsonFile($filepath);
                    $results['success'] += $result['imported_count'] ?? 0;

                    $fileResult = [
                        'name' => basename($filepath),
                        'status' => 'success',
                        'imported' => $result['imported_count'] ?? 0,
                        'skipped' => $result['skipped_count'] ?? 0,
                        'total_processed' => $result['total_processed'] ?? 0,
                    ];

                    if (!empty($result['errors'])) {
                        $fileResult['errors'] = $result['errors'];
                    }

                    $results['files'][] = $fileResult;

                    // Update processed count
                    $this->db->update('bank_import_jobs', [
                        'processed_files' => count($results['files']),
                        'imported_count' => $results['success'],
                    ], ['job_id' => $this->jobId]);

                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['files'][] = [
                        'name' => basename($filepath),
                        'status' => 'error',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            // Mark job as completed
            $this->updateJobStatus('completed', json_encode($results));

        } catch (\Exception $e) {
            // Mark job as failed
            $this->updateJobStatus('failed', null, $e->getMessage());
        }
    }

    /**
     * Get job status
     */
    public function getStatus(): ?array {
        return $this->db->queryOne(
            "SELECT * FROM bank_import_jobs WHERE job_id = ? AND user_id = ?",
            [$this->jobId, $this->userId]
        );
    }

    /**
     * Update job status
     */
    private function updateJobStatus(string $status, ?string $results = null, ?string $errorMessage = null): void {
        $data = [
            'status' => $status,
        ];

        if ($results !== null) {
            $data['results'] = $results;
        }

        if ($errorMessage !== null) {
            $data['error_message'] = $errorMessage;
        }

        if ($status === 'completed' || $status === 'failed') {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }

        $this->db->update('bank_import_jobs', $data, ['job_id' => $this->jobId]);
    }

    /**
     * Process a bank JSON file and import transactions
     */
    private function processBankJsonFile(string $filepath): array {
        if (!file_exists($filepath)) {
            throw new \Exception("File not found: $filepath");
        }

        $json = file_get_contents($filepath);
        if ($json === false) {
            throw new \Exception("Could not read file: $filepath");
        }

        $transactions = json_decode($json, true);

        if ($transactions === null) {
            throw new \Exception("JSON decode error: " . json_last_error_msg());
        }

        if (!is_array($transactions)) {
            throw new \Exception('Invalid JSON format - not an array');
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($transactions as $index => $tx) {
            try {
                // Check if transaction already exists
                $refNum = $tx['referenceNumber'] ?? null;
                $existing = $this->db->queryOne(
                    "SELECT id FROM transactions WHERE reference_number = ? AND user_id = ?",
                    [$refNum, $this->userId]
                );

                if ($existing) {
                    $skipped++;
                    continue;
                }

                // Parse transaction
                $parsed = $this->parseTransaction($tx, $this->userId);
                if (!$parsed) {
                    $skipped++;
                    continue;
                }

                // Get or create account
                $accountNumber = $tx['ownerAccountNumber'] ?? 'Unknown';
                $account = $this->db->queryOne(
                    "SELECT id FROM accounts WHERE account_number = ? AND user_id = ?",
                    [$accountNumber, $this->userId]
                );

                if (!$account) {
                    $accountId = $this->db->insert('accounts', [
                        'user_id' => $this->userId,
                        'name' => $tx['ownerAccountTitle'] ?? $accountNumber,
                        'account_number' => $accountNumber,
                        'balance' => 0,
                        'currency' => $tx['amount']['currency'] ?? 'CZK',
                        'type' => 'bank',
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                } else {
                    $accountId = $account['id'];
                }

                // Get or create category
                $categoryName = $this->mapBankCategoryToAppCategory($tx['categories'] ?? []);
                $category = $this->db->queryOne(
                    "SELECT id FROM categories WHERE name = ? AND user_id = ?",
                    [$categoryName, $this->userId]
                );

                if (!$category) {
                    $categoryId = $this->db->insert('categories', [
                        'user_id' => $this->userId,
                        'name' => $categoryName,
                        'type' => 'expense',
                        'color' => $this->getCategoryColor($categoryName),
                        'description' => 'Imported from bank data',
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                } else {
                    $categoryId = $category['id'];
                }

                // Insert transaction
                $this->db->insert('transactions', [
                    'user_id' => $this->userId,
                    'account_id' => $accountId,
                    'category_id' => $categoryId,
                    'description' => $parsed['description'],
                    'amount' => abs($parsed['amount']),
                    'type' => $parsed['type'],
                    'date' => $parsed['date'],
                    'reference_number' => $refNum,
                    'imported_from_json' => basename($filepath),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Transaction $index error: " . $e->getMessage();
            }
        }

        return [
            'imported_count' => $imported,
            'skipped_count' => $skipped,
            'errors' => $errors,
            'total_processed' => count($transactions),
        ];
    }

    /**
     * Parse bank transaction
     */
    private function parseTransaction(array $tx, int $userId): ?array {
        $amount = $tx['amount']['value'] ?? 0;
        if ($amount === 0 || $amount === null) {
            return null;
        }

        $booking = $tx['booking'] ?? date('Y-m-d H:i:s');
        $date = substr($booking, 0, 10);

        $type = $amount > 0 ? 'income' : 'expense';

        $description = '';
        if (!empty($tx['partnerName'])) {
            $description = $tx['partnerName'];
        } elseif (!empty($tx['cardLocation'])) {
            $description = $tx['cardLocation'];
        } else {
            $description = $tx['bookingTypeTranslation'] ?? 'Bank Transaction';
        }

        if (!empty($tx['note'])) {
            $description .= ' - ' . $tx['note'];
        }

        if (!empty($tx['variableSymbol'])) {
            $description .= ' (Vs: ' . $tx['variableSymbol'] . ')';
        }

        return [
            'amount' => $amount,
            'type' => $type,
            'date' => $date,
            'description' => substr($description, 0, 255),
        ];
    }

    /**
     * Map bank category to app category
     */
    private function mapBankCategoryToAppCategory(array $categories): string {
        $categoryMap = [
            'Potraviny' => 'Food',
            'Vzdělání' => 'Education',
            'Sport' => 'Sports',
            'Zábava' => 'Entertainment',
            'Zdraví' => 'Health',
            'Doprava' => 'Transport',
            'Bydlení' => 'Housing',
            'Nákupy' => 'Shopping',
            'Platby kartou' => 'Card Payment',
            'Příjmy' => 'Income',
            'Splátka úvěrů' => 'Loan Payment',
            'Úroky' => 'Interest',
            'Bez kategorie' => 'Uncategorized',
        ];

        foreach ($categories as $cat) {
            if (isset($categoryMap[$cat])) {
                return $categoryMap[$cat];
            }
            foreach ($categoryMap as $bankCat => $appCat) {
                if (strpos($cat, $bankCat) !== false) {
                    return $appCat;
                }
            }
        }

        return 'Other';
    }

    /**
     * Get category color
     */
    private function getCategoryColor(string $categoryName): string {
        $colors = [
            'Food' => '#FF6B6B',
            'Education' => '#4ECDC4',
            'Sports' => '#FFE66D',
            'Entertainment' => '#95E1D3',
            'Health' => '#F38181',
            'Transport' => '#AA96DA',
            'Housing' => '#FCBAD3',
            'Shopping' => '#A8E6CF',
            'Card Payment' => '#FFD3B6',
            'Income' => '#4CAF50',
            'Loan Payment' => '#FF9999',
            'Interest' => '#FFB6C1',
            'Uncategorized' => '#CCCCCC',
        ];

        return $colors[$categoryName] ?? '#3b82f6';
    }
}
?>
