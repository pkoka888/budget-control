<?php
// Bank JSON Import Controller
namespace BudgetApp\Controllers;

use BudgetApp\Database;
use BudgetApp\Jobs\BankImportJob;

class BankImportController extends BaseController {

    /**
     * Display bank import page
     */
    public function index(): void {
        $userId = $this->getUserId();

        // Get available JSON files from /user-data/bank-json folder
        $bankJsonFolder = '/var/www/html/user-data/bank-json';
        $files = [];
        $stats = [];

        if (is_dir($bankJsonFolder)) {
            $jsonFiles = glob($bankJsonFolder . '/*.json');
            if ($jsonFiles !== false) {
                foreach ($jsonFiles as $file) {
                    $filename = basename($file);
                    $files[] = [
                        'name' => $filename,
                        'path' => $file,
                        'size' => filesize($file),
                        'modified' => filemtime($file),
                    ];
                }
            }
        }

        // Get import statistics
        $importedCount = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM transactions WHERE imported_from_json IS NOT NULL AND user_id = ?",
            [$userId]
        );

        $stats = [
            'imported_transactions' => $importedCount['count'] ?? 0,
            'available_files' => count($files),
        ];

        echo $this->render('import/bank-json', [
            'files' => $files,
            'stats' => $stats,
            'bankJsonFolder' => $bankJsonFolder,
        ]);
    }

    /**
     * Import transactions from a specific JSON file
     */
    public function importFile(): void {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Method not allowed'], 405);
            return;
        }

        $userId = $this->getUserId();
        $filename = $_POST['filename'] ?? null;

        if (!$filename) {
            $this->json(['error' => 'Filename required'], 400);
            return;
        }

        // Security: Prevent directory traversal
        if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
            $this->json(['error' => 'Invalid filename'], 400);
            return;
        }

        $filepath = '/var/www/html/user-data/bank-json/' . basename($filename);

        if (!file_exists($filepath)) {
            $this->json(['error' => 'File not found'], 404);
            return;
        }

        try {
            $result = $this->processBankJsonFile($filepath, $userId);
            $this->json($result);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Auto-import all available JSON files
     * Returns 202 Accepted and processes in background via job queue
     */
    public function autoImportAll(): void {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Method not allowed'], 405);
            return;
        }

        $userId = $this->getUserId();
        $bankJsonFolder = '/var/www/html/user-data/bank-json';

        if (!is_dir($bankJsonFolder)) {
            $this->json(['error' => 'Bank JSON folder not found'], 400);
            return;
        }

        // Generate unique job ID
        $jobId = bin2hex(random_bytes(16));

        // Create job record in database
        try {
            $this->db->insert('bank_import_jobs', [
                'user_id' => $userId,
                'job_id' => $jobId,
                'status' => 'pending',
                'total_files' => 0,
                'processed_files' => 0,
                'imported_count' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            $this->json(['error' => 'Failed to create import job: ' . $e->getMessage()], 500);
            return;
        }

        // Execute job immediately (can be moved to background/cronjob in future)
        try {
            $job = new BankImportJob($this->db, $jobId, $userId);
            $job->execute();
        } catch (\Exception $e) {
            // Job execution failed, but job record exists for status tracking
            error_log("Bank import job failed: " . $e->getMessage());
        }

        // Return 202 Accepted with job ID
        $this->json([
            'job_id' => $jobId,
            'status' => 'accepted',
            'message' => 'Import job queued and processing'
        ], 202);
    }

    /**
     * Get job status
     */
    public function jobStatus(): void {
        $this->requireAuth();

        $jobId = $_GET['job_id'] ?? null;
        if (!$jobId) {
            $this->json(['error' => 'job_id required'], 400);
            return;
        }

        $userId = $this->getUserId();

        try {
            $job = $this->db->queryOne(
                "SELECT * FROM bank_import_jobs WHERE job_id = ? AND user_id = ?",
                [$jobId, $userId]
            );

            if (!$job) {
                $this->json(['error' => 'Job not found'], 404);
                return;
            }

            // Parse results JSON if available
            if (!empty($job['results'])) {
                $job['results'] = json_decode($job['results'], true);
            }

            $this->json([
                'job_id' => $job['job_id'],
                'status' => $job['status'],
                'progress' => [
                    'processed_files' => $job['processed_files'],
                    'total_files' => $job['total_files'],
                    'imported_count' => $job['imported_count'],
                ],
                'results' => $job['results'] ?? null,
                'error_message' => $job['error_message'] ?? null,
                'started_at' => $job['started_at'],
                'completed_at' => $job['completed_at'],
            ]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Process a bank JSON file and import transactions
     */
    private function processBankJsonFile(string $filepath, int $userId): array {
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

        // Debug: Log the number of transactions
        if (count($transactions) === 0) {
            $errors[] = "WARNING: No transactions found in JSON file!";
        }

        foreach ($transactions as $index => $tx) {
            try {
                // Check if transaction already exists
                $refNum = $tx['referenceNumber'] ?? null;
                $existing = $this->db->queryOne(
                    "SELECT id FROM transactions WHERE reference_number = ? AND user_id = ?",
                    [$refNum, $userId]
                );

                if ($existing) {
                    $skipped++;
                    continue;
                }

                // Parse transaction
                $parsed = $this->parseTransaction($tx, $userId);
                if (!$parsed) {
                    $amount = $tx['amount']['value'] ?? 'missing';
                    $errors[] = "Transaction $index: Failed to parse. Amount value: $amount";
                    $skipped++;
                    continue;
                }

                // Get or create account
                $accountNumber = $tx['ownerAccountNumber'] ?? 'Unknown';
                $account = $this->db->queryOne(
                    "SELECT id FROM accounts WHERE account_number = ? AND user_id = ?",
                    [$accountNumber, $userId]
                );

                if (!$account) {
                    // Create new account
                    $accountId = $this->db->insert('accounts', [
                        'user_id' => $userId,
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

                // Get or create category based on bank category
                $categoryName = $this->mapBankCategoryToAppCategory($tx['categories'] ?? []);
                $category = $this->db->queryOne(
                    "SELECT id FROM categories WHERE name = ? AND user_id = ?",
                    [$categoryName, $userId]
                );

                if (!$category) {
                    // Create new category
                    $categoryId = $this->db->insert('categories', [
                        'user_id' => $userId,
                        'name' => $categoryName,
                        'type' => 'expense',  // Default to expense for imported categories
                        'color' => $this->getCategoryColor($categoryName),
                        'description' => 'Imported from bank data',
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                } else {
                    $categoryId = $category['id'];
                }

                // Insert transaction
                $this->db->insert('transactions', [
                    'user_id' => $userId,
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
     * Parse bank transaction into app format
     */
    private function parseTransaction(array $tx, int $userId): ?array {
        $amount = $tx['amount']['value'] ?? 0;
        if ($amount === 0 || $amount === null) {
            return null;
        }

        $booking = $tx['booking'] ?? date('Y-m-d H:i:s');
        $date = substr($booking, 0, 10); // Extract date part

        $type = $amount > 0 ? 'income' : 'expense';

        // Build description from available fields
        $description = '';
        if (!empty($tx['partnerName'])) {
            $description = $tx['partnerName'];
        } elseif (!empty($tx['cardLocation'])) {
            $description = $tx['cardLocation'];
        } else {
            $description = $tx['bookingTypeTranslation'] ?? 'Bank Transaction';
        }

        // Add note if available
        if (!empty($tx['note'])) {
            $description .= ' - ' . $tx['note'];
        }

        // Add variable symbol if available (Czech standard)
        if (!empty($tx['variableSymbol'])) {
            $description .= ' (Vs: ' . $tx['variableSymbol'] . ')';
        }

        return [
            'amount' => $amount,
            'type' => $type,
            'date' => $date,
            'description' => substr($description, 0, 255), // Limit to DB field size
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
            // Check partial matches
            foreach ($categoryMap as $bankCat => $appCat) {
                if (strpos($cat, $bankCat) !== false) {
                    return $appCat;
                }
            }
        }

        return 'Other';
    }

    /**
     * Get color for category
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
