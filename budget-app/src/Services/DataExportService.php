<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

/**
 * Data Export Service
 * 
 * Handles data exports, imports, and API access
 */
class DataExportService {
    private Database $db;
    private string $exportDir;
    
    public function __construct(Database $db, string $exportDir = null) {
        $this->db = $db;
        $this->exportDir = $exportDir ?? __DIR__ . '/../../storage/exports';
        
        if (!is_dir($this->exportDir)) {
            mkdir($this->exportDir, 0755, true);
        }
    }
    
    // ===== EXPORT MANAGEMENT =====
    
    public function createExport(int $userId, array $options): int {
        $this->db->query(
            "INSERT INTO export_jobs 
             (user_id, export_type, format, date_range_start, date_range_end, filters_json, status)
             VALUES (?, ?, ?, ?, ?, ?, 'pending')",
            [
                $userId,
                $options['type'],
                $options['format'],
                $options['start_date'] ?? null,
                $options['end_date'] ?? null,
                isset($options['filters']) ? json_encode($options['filters']) : null
            ]
        );
        
        $jobId = $this->db->lastInsertId();
        
        // Process export asynchronously or immediately
        if ($options['async'] ?? false) {
            // Queue for background processing
            return $jobId;
        } else {
            $this->processExport($jobId);
            return $jobId;
        }
    }
    
    public function processExport(int $jobId): bool {
        $job = $this->db->query(
            "SELECT * FROM export_jobs WHERE id = ?",
            [$jobId]
        )[0] ?? null;
        
        if (!$job) return false;
        
        // Update status
        $this->db->query(
            "UPDATE export_jobs SET status = 'processing', progress = 0 WHERE id = ?",
            [$jobId]
        );
        
        try {
            // Get data based on export type
            $data = $this->fetchExportData($job);
            
            // Generate file based on format
            $filePath = $this->generateExportFile($job, $data);
            
            // Update job with file info
            $this->db->query(
                "UPDATE export_jobs 
                 SET status = 'completed', 
                     progress = 100,
                     file_path = ?,
                     file_size = ?,
                     completed_at = CURRENT_TIMESTAMP,
                     expires_at = datetime('now', '+7 days')
                 WHERE id = ?",
                [$filePath, filesize($filePath), $jobId]
            );
            
            return true;
        } catch (\Exception $e) {
            $this->db->query(
                "UPDATE export_jobs SET status = 'failed', error_message = ? WHERE id = ?",
                [$e->getMessage(), $jobId]
            );
            return false;
        }
    }
    
    private function fetchExportData(array $job): array {
        $filters = json_decode($job['filters_json'] ?? '{}', true);
        
        switch ($job['export_type']) {
            case 'transactions':
                return $this->fetchTransactions($job['user_id'], $job['date_range_start'], $job['date_range_end'], $filters);
            
            case 'budgets':
                return $this->fetchBudgets($job['user_id'], $filters);
            
            case 'goals':
                return $this->fetchGoals($job['user_id'], $filters);
            
            case 'investments':
                return $this->fetchInvestments($job['user_id'], $filters);
            
            case 'full_backup':
                return $this->fetchFullBackup($job['user_id']);
            
            default:
                throw new \Exception("Unknown export type: {$job['export_type']}");
        }
    }
    
    private function fetchTransactions(int $userId, ?string $startDate, ?string $endDate, array $filters): array {
        $query = "SELECT t.*, c.name as category_name, a.name as account_name
                  FROM transactions t
                  LEFT JOIN categories c ON c.id = t.category_id
                  LEFT JOIN accounts a ON a.id = t.account_id
                  WHERE t.user_id = ?";
        $params = [$userId];
        
        if ($startDate) {
            $query .= " AND t.date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $query .= " AND t.date <= ?";
            $params[] = $endDate;
        }
        
        if (!empty($filters['type'])) {
            $query .= " AND t.type = ?";
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['category'])) {
            $query .= " AND t.category = ?";
            $params[] = $filters['category'];
        }
        
        $query .= " ORDER BY t.date DESC";
        
        return $this->db->query($query, $params);
    }
    
    private function fetchBudgets(int $userId, array $filters): array {
        return $this->db->query(
            "SELECT b.*, c.name as category_name FROM budgets b
             LEFT JOIN categories c ON c.id = b.category_id
             WHERE b.user_id = ?",
            [$userId]
        );
    }
    
    private function fetchGoals(int $userId, array $filters): array {
        return $this->db->query(
            "SELECT * FROM goals WHERE user_id = ?",
            [$userId]
        );
    }
    
    private function fetchInvestments(int $userId, array $filters): array {
        return $this->db->query(
            "SELECT ia.*, h.*, it.*
             FROM investment_accounts ia
             LEFT JOIN investment_holdings h ON h.investment_account_id = ia.id
             LEFT JOIN investment_transactions it ON it.investment_account_id = ia.id
             WHERE ia.user_id = ?",
            [$userId]
        );
    }
    
    private function fetchFullBackup(int $userId): array {
        return [
            'transactions' => $this->fetchTransactions($userId, null, null, []),
            'budgets' => $this->fetchBudgets($userId, []),
            'goals' => $this->fetchGoals($userId, []),
            'accounts' => $this->db->query("SELECT * FROM accounts WHERE user_id = ?", [$userId]),
            'categories' => $this->db->query("SELECT * FROM categories WHERE user_id = ?", [$userId])
        ];
    }
    
    // ===== FILE GENERATION =====
    
    private function generateExportFile(array $job, array $data): string {
        $filename = sprintf(
            '%s_%s_%s.%s',
            $job['export_type'],
            $job['user_id'],
            date('Y-m-d_His'),
            $this->getFileExtension($job['format'])
        );
        
        $filePath = $this->exportDir . '/' . $filename;
        
        switch ($job['format']) {
            case 'csv':
                $this->generateCSV($filePath, $data);
                break;
            
            case 'json':
                $this->generateJSON($filePath, $data);
                break;
            
            case 'xlsx':
                $this->generateXLSX($filePath, $data);
                break;
            
            case 'qif':
                $this->generateQIF($filePath, $data);
                break;
            
            case 'ofx':
                $this->generateOFX($filePath, $data);
                break;
            
            default:
                throw new \Exception("Unsupported format: {$job['format']}");
        }
        
        return $filePath;
    }
    
    private function generateCSV(string $filePath, array $data): void {
        $fp = fopen($filePath, 'w');
        
        if (!empty($data)) {
            // Write headers
            fputcsv($fp, array_keys($data[0]));
            
            // Write data
            foreach ($data as $row) {
                fputcsv($fp, $row);
            }
        }
        
        fclose($fp);
    }
    
    private function generateJSON(string $filePath, array $data): void {
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    private function generateXLSX(string $filePath, array $data): void {
        // Simplified XLSX generation (would use PHPSpreadsheet in production)
        // For now, generate CSV
        $this->generateCSV(str_replace('.xlsx', '.csv', $filePath), $data);
    }
    
    private function generateQIF(string $filePath, array $data): void {
        $qif = "!Type:Bank\n";
        
        foreach ($data as $tx) {
            $qif .= "D" . date('m/d/Y', strtotime($tx['date'])) . "\n";
            $qif .= "T" . ($tx['type'] === 'expense' ? '-' : '') . $tx['amount'] . "\n";
            $qif .= "P" . $tx['description'] . "\n";
            if ($tx['category_name']) {
                $qif .= "L" . $tx['category_name'] . "\n";
            }
            $qif .= "^\n";
        }
        
        file_put_contents($filePath, $qif);
    }
    
    private function generateOFX(string $filePath, array $data): void {
        $ofx = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $ofx .= '<OFX>' . "\n";
        $ofx .= '  <BANKTRANLIST>' . "\n";
        
        foreach ($data as $tx) {
            $ofx .= '    <STMTTRN>' . "\n";
            $ofx .= '      <TRNTYPE>' . strtoupper($tx['type']) . '</TRNTYPE>' . "\n";
            $ofx .= '      <DTPOSTED>' . date('Ymd', strtotime($tx['date'])) . '</DTPOSTED>' . "\n";
            $ofx .= '      <TRNAMT>' . ($tx['type'] === 'expense' ? '-' : '') . $tx['amount'] . '</TRNAMT>' . "\n";
            $ofx .= '      <NAME>' . htmlspecialchars($tx['description']) . '</NAME>' . "\n";
            $ofx .= '    </STMTTRN>' . "\n";
        }
        
        $ofx .= '  </BANKTRANLIST>' . "\n";
        $ofx .= '</OFX>';
        
        file_put_contents($filePath, $ofx);
    }
    
    private function getFileExtension(string $format): string {
        return ['csv' => 'csv', 'json' => 'json', 'xlsx' => 'xlsx', 'qif' => 'qif', 'ofx' => 'ofx'][$format] ?? 'txt';
    }
    
    // ===== DATA IMPORT =====
    
    public function createImport(int $userId, array $uploadedFile, string $importType, string $format): int {
        // Save uploaded file
        $filename = sprintf('import_%s_%s_%s', $userId, time(), $uploadedFile['name']);
        $filePath = $this->exportDir . '/' . $filename;
        
        move_uploaded_file($uploadedFile['tmp_name'], $filePath);
        
        // Create import job
        $this->db->query(
            "INSERT INTO import_jobs 
             (user_id, import_type, source_format, file_path, file_name, file_size, status)
             VALUES (?, ?, ?, ?, ?, ?, 'pending')",
            [$userId, $importType, $format, $filePath, $uploadedFile['name'], $uploadedFile['size']]
        );
        
        return $this->db->lastInsertId();
    }
    
    public function processImport(int $jobId, bool $dryRun = false): array {
        $job = $this->db->query(
            "SELECT * FROM import_jobs WHERE id = ?",
            [$jobId]
        )[0] ?? null;
        
        if (!$job) throw new \Exception("Import job not found");
        
        // Parse file
        $data = $this->parseImportFile($job['file_path'], $job['source_format']);
        
        if ($dryRun) {
            return ['preview' => array_slice($data, 0, 10), 'total_rows' => count($data)];
        }
        
        // Import data
        $imported = 0;
        $failed = 0;
        $errors = [];
        
        foreach ($data as $row) {
            try {
                $this->importRow($job['user_id'], $job['import_type'], $row);
                $imported++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = ['row' => $row, 'error' => $e->getMessage()];
            }
        }
        
        // Update job
        $this->db->query(
            "UPDATE import_jobs 
             SET status = ?, rows_imported = ?, rows_failed = ?, errors_json = ?, completed_at = CURRENT_TIMESTAMP
             WHERE id = ?",
            [
                $failed > 0 ? 'partially_completed' : 'completed',
                $imported,
                $failed,
                json_encode($errors),
                $jobId
            ]
        );
        
        return ['imported' => $imported, 'failed' => $failed, 'errors' => $errors];
    }
    
    private function parseImportFile(string $filePath, string $format): array {
        switch ($format) {
            case 'csv':
                return $this->parseCSV($filePath);
            case 'json':
                return json_decode(file_get_contents($filePath), true);
            default:
                throw new \Exception("Unsupported import format: {$format}");
        }
    }
    
    private function parseCSV(string $filePath): array {
        $data = [];
        $fp = fopen($filePath, 'r');
        $headers = fgetcsv($fp);
        
        while (($row = fgetcsv($fp)) !== false) {
            $data[] = array_combine($headers, $row);
        }
        
        fclose($fp);
        return $data;
    }
    
    private function importRow(int $userId, string $importType, array $row): void {
        switch ($importType) {
            case 'transactions':
                $this->importTransaction($userId, $row);
                break;
            default:
                throw new \Exception("Unsupported import type");
        }
    }
    
    private function importTransaction(int $userId, array $row): void {
        $this->db->query(
            "INSERT INTO transactions (user_id, type, amount, description, date, category)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $userId,
                $row['type'] ?? 'expense',
                $row['amount'],
                $row['description'] ?? '',
                $row['date'] ?? date('Y-m-d'),
                $row['category'] ?? null
            ]
        );
    }
    
    // ===== API KEY MANAGEMENT =====
    
    public function createApiKey(int $userId, string $keyName, array $permissions): string {
        $apiKey = bin2hex(random_bytes(32));
        $apiSecret = bin2hex(random_bytes(32));
        
        $this->db->query(
            "INSERT INTO api_keys 
             (user_id, key_name, api_key, api_secret, permissions_json)
             VALUES (?, ?, ?, ?, ?)",
            [$userId, $keyName, $apiKey, $apiSecret, json_encode($permissions)]
        );
        
        return $apiKey;
    }
    
    public function validateApiKey(string $apiKey): ?array {
        $key = $this->db->query(
            "SELECT * FROM api_keys WHERE api_key = ? AND is_active = 1",
            [$apiKey]
        )[0] ?? null;
        
        if (!$key) return null;
        
        // Check expiration
        if ($key['expires_at'] && strtotime($key['expires_at']) < time()) {
            return null;
        }
        
        // Update last used
        $this->db->query(
            "UPDATE api_keys SET last_used = CURRENT_TIMESTAMP WHERE id = ?",
            [$key['id']]
        );
        
        return $key;
    }
    
    public function logApiRequest(int $keyId, string $endpoint, string $method, int $statusCode, int $responseTime): void {
        $this->db->query(
            "INSERT INTO api_request_logs 
             (api_key_id, endpoint, method, status_code, response_time)
             VALUES (?, ?, ?, ?, ?)",
            [$keyId, $endpoint, $method, $statusCode, $responseTime]
        );
    }
}
