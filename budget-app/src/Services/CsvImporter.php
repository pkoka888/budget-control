<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

class CsvImporter {
    private Database $db;
    private array $mappings = [];
    private array $importedTransactions = [];
    private array $duplicates = [];
    private array $errors = [];

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Parse CSV file for Czech bank format (ČSOB, ČEZ, etc.)
     * Expected format: Date, Description, Amount, Balance
     */
    public function parseCzechBankFormat(string $filePath): array {
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }

        $transactions = [];
        $handle = fopen($filePath, 'r');

        if (!$handle) {
            throw new \Exception("Cannot open file: {$filePath}");
        }

        // Skip header row if exists
        $firstRow = fgetcsv($handle);
        if (!$this->isHeaderRow($firstRow)) {
            rewind($handle);
        }

        $rowNum = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            try {
                $transaction = $this->parseRow($row);
                if ($transaction) {
                    $transactions[] = $transaction;
                }
            } catch (\Exception $e) {
                $this->errors[] = "Row {$rowNum}: " . $e->getMessage();
            }
        }

        fclose($handle);
        return $transactions;
    }

    /**
     * Check if row is a header row
     */
    private function isHeaderRow(array $row): bool {
        $headers = array_map('strtolower', $row);
        $commonHeaders = ['date', 'description', 'amount', 'balance', 'name', 'type'];

        foreach ($commonHeaders as $header) {
            if (in_array($header, $headers)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse individual row from Czech bank CSV
     */
    private function parseRow(array $row): ?array {
        // Clean row
        $row = array_map('trim', $row);

        // Filter empty rows
        if (empty($row[0]) && empty($row[1])) {
            return null;
        }

        // Parse date (dd.mm.yyyy or yyyy-mm-dd format)
        $date = $this->parseDate($row[0] ?? '');
        if (!$date) {
            throw new \Exception("Invalid date format: {$row[0]}");
        }

        // Description/merchant
        $description = $row[1] ?? '';
        if (empty($description)) {
            throw new \Exception("Missing description");
        }

        // Amount - handle both positive and negative values
        $amount = $this->parseAmount($row[2] ?? '');
        if ($amount === null) {
            throw new \Exception("Invalid amount: {$row[2]}");
        }

        // Determine if income or expense
        $type = $amount > 0 ? 'income' : 'expense';
        $amount = abs($amount);

        return [
            'date' => $date,
            'description' => $description,
            'amount' => $amount,
            'type' => $type,
            'balance' => $this->parseAmount($row[3] ?? null),
            'raw_row' => $row
        ];
    }

    /**
     * Parse date in multiple formats
     */
    private function parseDate(string $dateStr): ?string {
        if (empty($dateStr)) {
            return null;
        }

        $dateStr = trim($dateStr);

        // Try dd.mm.yyyy format (Czech standard)
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $dateStr, $matches)) {
            return sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
        }

        // Try yyyy-mm-dd format
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $dateStr, $matches)) {
            return sprintf('%04d-%02d-%02d', $matches[1], $matches[2], $matches[3]);
        }

        // Try mm/dd/yyyy format
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateStr, $matches)) {
            return sprintf('%04d-%02d-%02d', $matches[3], $matches[1], $matches[2]);
        }

        return null;
    }

    /**
     * Parse amount with Czech formatting (uses comma as decimal separator)
     */
    private function parseAmount(?string $amountStr): ?float {
        if (empty($amountStr)) {
            return null;
        }

        $amountStr = trim($amountStr);

        // Remove spaces (thousands separator in Czech format)
        $amountStr = str_replace(' ', '', $amountStr);

        // Handle Czech decimal format (comma as decimal, space as thousands)
        // Can also handle US format (dot as decimal)
        $amountStr = str_replace(',', '.', $amountStr);

        if (!is_numeric($amountStr)) {
            return null;
        }

        return (float)$amountStr;
    }

    /**
     * Import transactions to database
     */
    public function importTransactions(
        int $userId,
        int $accountId,
        array $transactions,
        array $categoryMappings = []
    ): array {
        $this->db->beginTransaction();

        try {
            $imported = 0;
            $duplicates = 0;

            foreach ($transactions as $tx) {
                // Check for duplicate
                $existing = $this->db->queryOne(
                    "SELECT id FROM transactions WHERE user_id = ? AND account_id = ? AND date = ? AND amount = ? AND description = ?",
                    [$userId, $accountId, $tx['date'], $tx['amount'], $tx['description']]
                );

                if ($existing) {
                    $duplicates++;
                    continue;
                }

                // Auto-categorize
                $categoryId = $this->categorizeTransaction($userId, $tx);

                // Insert transaction
                $this->db->insert('transactions', [
                    'user_id' => $userId,
                    'account_id' => $accountId,
                    'category_id' => $categoryId,
                    'type' => $tx['type'],
                    'description' => $tx['description'],
                    'amount' => $tx['amount'],
                    'date' => $tx['date'],
                    'currency' => 'CZK'
                ]);

                $imported++;

                // Update merchant frequency
                $this->updateMerchantInfo($userId, $tx['description'], $categoryId);
            }

            // Update account balance if available
            if (!empty($transactions)) {
                $lastTx = end($transactions);
                if ($lastTx['balance'] !== null) {
                    $this->db->update('accounts', [
                        'balance' => $lastTx['balance']
                    ], ['id' => $accountId]);
                }
            }

            $this->db->commit();

            return [
                'success' => true,
                'imported' => $imported,
                'duplicates' => $duplicates,
                'errors' => $this->errors,
                'total' => $imported + $duplicates
            ];
        } catch (\Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'imported' => 0,
                'duplicates' => 0,
                'errors' => $this->errors
            ];
        }
    }

    /**
     * Auto-categorize transaction using rules and merchant history
     */
    private function categorizeTransaction(int $userId, array $transaction): ?int {
        // Check categorization rules
        $rules = $this->db->query(
            "SELECT cr.category_id, cr.pattern, cr.is_regex
             FROM categorization_rules cr
             WHERE cr.user_id = ? AND cr.is_active = 1
             ORDER BY cr.priority ASC",
            [$userId]
        );

        foreach ($rules as $rule) {
            $pattern = $rule['pattern'];
            $description = strtolower($transaction['description']);

            $matches = false;
            if ($rule['is_regex']) {
                $matches = @preg_match($pattern, $description);
            } else {
                $matches = stripos($description, $pattern) !== false;
            }

            if ($matches) {
                return $rule['category_id'];
            }
        }

        // Check merchant history
        $merchant = $this->db->queryOne(
            "SELECT category_id FROM merchants WHERE user_id = ? AND name = ?",
            [$userId, $transaction['description']]
        );

        if ($merchant && $merchant['category_id']) {
            return $merchant['category_id'];
        }

        return null;
    }

    /**
     * Update merchant information for learning
     */
    private function updateMerchantInfo(int $userId, string $merchantName, ?int $categoryId): void {
        $existing = $this->db->queryOne(
            "SELECT id, frequency FROM merchants WHERE user_id = ? AND name = ?",
            [$userId, $merchantName]
        );

        if ($existing) {
            $this->db->update('merchants', [
                'frequency' => $existing['frequency'] + 1,
                'last_used' => date('Y-m-d')
            ], ['id' => $existing['id']]);
        } else {
            $this->db->insert('merchants', [
                'user_id' => $userId,
                'name' => $merchantName,
                'category_id' => $categoryId,
                'frequency' => 1,
                'last_used' => date('Y-m-d')
            ]);
        }
    }

    /**
     * Get import statistics
     */
    public function getImportStats(int $userId): array {
        $imports = $this->db->query(
            "SELECT * FROM csv_imports WHERE user_id = ? ORDER BY created_at DESC LIMIT 10",
            [$userId]
        );

        return $imports;
    }
}
