<?php
namespace BudgetApp\Controllers;

use BudgetApp\Services\CsvImporter;

class ImportController extends BaseController {
    public function form(): void {
        $userId = $this->getUserId();

        // Get user accounts
        $accounts = $this->db->query(
            "SELECT id, name, type FROM accounts WHERE user_id = ? ORDER BY name",
            [$userId]
        );

        echo $this->render('import/form', [
            'title' => 'Import CSV',
            'accounts' => $accounts
        ]);
    }

    public function upload(): void {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }

        if (!isset($_FILES['csv_file'])) {
            $this->json(['error' => 'Soubor nebyl nahrán'], 400);
        }

        $file = $_FILES['csv_file'];
        $accountId = $this->getPostParam('account_id');

        // Validation
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'Chyba při nahrávání souboru'], 400);
        }

        if ($file['size'] > 10 * 1024 * 1024) {
            $this->json(['error' => 'Soubor je příliš velký (max 10MB)'], 400);
        }

        if (!in_array($file['type'], ['text/csv', 'application/csv', 'text/plain'])) {
            $this->json(['error' => 'Neplatný typ souboru'], 400);
        }

        // Verify account ownership
        $account = $this->db->queryOne(
            "SELECT id FROM accounts WHERE id = ? AND user_id = ?",
            [$accountId, $this->getUserId()]
        );

        if (!$account) {
            $this->json(['error' => 'Účet nenalezen'], 403);
        }

        // Parse CSV
        try {
            $importer = new CsvImporter($this->db);
            $transactions = $importer->parseCzechBankFormat($file['tmp_name']);

            // Store in session for preview
            $_SESSION['import_data'] = [
                'transactions' => $transactions,
                'account_id' => $accountId,
                'filename' => $file['name']
            ];

            $this->json([
                'success' => true,
                'count' => count($transactions),
                'preview' => array_slice($transactions, 0, 5)
            ]);
        } catch (\Exception $e) {
            $this->json(['error' => 'Chyba při parsování: ' . $e->getMessage()], 400);
        }
    }

    public function process(): void {
        $this->requireAuth();

        if (!isset($_SESSION['import_data'])) {
            $this->json(['error' => 'Žádná data k importu'], 400);
        }

        $data = $_SESSION['import_data'];
        $userId = $this->getUserId();

        try {
            $importer = new CsvImporter($this->db);
            $result = $importer->importTransactions(
                $userId,
                $data['account_id'],
                $data['transactions']
            );

            if ($result['success']) {
                // Log import
                $this->db->insert('csv_imports', [
                    'user_id' => $userId,
                    'account_id' => $data['account_id'],
                    'filename' => $data['filename'],
                    'rows_processed' => $result['total'],
                    'rows_imported' => $result['imported'],
                    'rows_skipped' => $result['duplicates'],
                    'status' => 'completed',
                    'imported_at' => date('Y-m-d H:i:s')
                ]);

                unset($_SESSION['import_data']);

                $this->json([
                    'success' => true,
                    'imported' => $result['imported'],
                    'duplicates' => $result['duplicates'],
                    'message' => "Importováno {$result['imported']} transakcí"
                ]);
            } else {
                $this->json(['error' => $result['error']], 400);
            }
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
