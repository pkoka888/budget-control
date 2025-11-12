<?php
namespace BudgetApp\Controllers;

use BudgetApp\Database;
use BudgetApp\Services\ReceiptOcrService;
use BudgetApp\Auth;

/**
 * Receipt Controller
 *
 * Handles receipt scanning, OCR processing, and review
 */
class ReceiptController {
    private Database $db;
    private ReceiptOcrService $receiptService;
    private Auth $auth;

    public function __construct(Database $db, ReceiptOcrService $receiptService, Auth $auth) {
        $this->db = $db;
        $this->receiptService = $receiptService;
        $this->auth = $auth;
    }

    /**
     * Receipt scanner main page
     */
    public function index(): void {
        $user = $this->auth->requireAuth();

        $recentScans = $this->receiptService->getUserScans($user['id'], 10);

        $this->render('receipt/index', [
            'title' => 'Receipt Scanner',
            'recent_scans' => $recentScans
        ]);
    }

    /**
     * Upload and process receipt
     */
    public function upload(): void {
        $user = $this->auth->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        // Check if file was uploaded
        if (!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'No file uploaded or upload error']);
            return;
        }

        $file = $_FILES['receipt'];

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid file type. Only images are allowed.']);
            return;
        }

        // Validate file size (max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['error' => 'File too large. Maximum size is 10MB.']);
            return;
        }

        try {
            $result = $this->receiptService->processReceipt($user['id'], $file);

            echo json_encode([
                'success' => true,
                'message' => 'Receipt processed successfully',
                'scan_id' => $result['scan_id'],
                'confidence' => $result['confidence'],
                'parsed_data' => $result['parsed_data'],
                'status' => $result['status']
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get scan details
     */
    public function show(): void {
        $user = $this->auth->requireAuth();
        $scanId = (int)($_GET['id'] ?? 0);

        if (!$scanId) {
            http_response_code(400);
            echo "Scan ID required";
            return;
        }

        try {
            $scan = $this->receiptService->getScanDetails($scanId);

            // Verify ownership
            if ($scan['user_id'] != $user['id']) {
                http_response_code(403);
                echo "Unauthorized";
                return;
            }

            $this->render('receipt/show', [
                'title' => 'Receipt Details',
                'scan' => $scan
            ]);
        } catch (\Exception $e) {
            http_response_code(404);
            echo "Receipt not found";
        }
    }

    /**
     * Get scan details as JSON
     */
    public function getScan(): void {
        $user = $this->auth->requireAuth();
        $scanId = (int)($_GET['id'] ?? 0);

        if (!$scanId) {
            http_response_code(400);
            echo json_encode(['error' => 'Scan ID required']);
            return;
        }

        try {
            $scan = $this->receiptService->getScanDetails($scanId);

            // Verify ownership
            if ($scan['user_id'] != $user['id']) {
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }

            echo json_encode($scan);
        } catch (\Exception $e) {
            http_response_code(404);
            echo json_encode(['error' => 'Receipt not found']);
        }
    }

    /**
     * List all scans for user
     */
    public function list(): void {
        $user = $this->auth->requireAuth();

        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);
        $status = $_GET['status'] ?? null;

        $query = "SELECT * FROM receipt_scans WHERE user_id = ?";
        $params = [$user['id']];

        if ($status) {
            $query .= " AND status = ?";
            $params[] = $status;
        }

        $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $scans = $this->db->query($query, $params);

        $this->render('receipt/list', [
            'title' => 'Receipt History',
            'scans' => $scans,
            'current_page' => floor($offset / $limit) + 1,
            'per_page' => $limit
        ]);
    }

    /**
     * Review queue for low confidence scans
     */
    public function reviewQueue(): void {
        $user = $this->auth->requireAuth();

        $queue = $this->db->query(
            "SELECT rs.*, rq.priority, rq.review_status
             FROM receipt_review_queue rq
             JOIN receipt_scans rs ON rs.id = rq.receipt_scan_id
             WHERE rs.user_id = ? AND rq.review_status = 'pending'
             ORDER BY rq.priority DESC, rq.created_at ASC",
            [$user['id']]
        );

        $this->render('receipt/review-queue', [
            'title' => 'Review Queue',
            'queue' => $queue
        ]);
    }

    /**
     * Update parsed data after manual review
     */
    public function updateScan(): void {
        $user = $this->auth->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $scanId = (int)($data['scan_id'] ?? 0);
        $parsedData = $data['parsed_data'] ?? [];

        if (!$scanId) {
            http_response_code(400);
            echo json_encode(['error' => 'Scan ID required']);
            return;
        }

        try {
            $scan = $this->receiptService->getScanDetails($scanId);

            // Verify ownership
            if ($scan['user_id'] != $user['id']) {
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }

            // Update scan data
            $this->db->query(
                "UPDATE receipt_scans
                 SET parsed_data = ?, status = 'completed', confidence_score = 1.0
                 WHERE id = ?",
                [json_encode($parsedData), $scanId]
            );

            // Remove from review queue
            $this->db->query(
                "UPDATE receipt_review_queue
                 SET review_status = 'completed', reviewed_at = CURRENT_TIMESTAMP
                 WHERE receipt_scan_id = ?",
                [$scanId]
            );

            echo json_encode([
                'success' => true,
                'message' => 'Receipt updated successfully'
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Create transaction from receipt scan
     */
    public function createTransaction(): void {
        $user = $this->auth->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $scanId = (int)($data['scan_id'] ?? 0);
        $accountId = (int)($data['account_id'] ?? 0);
        $categoryId = (int)($data['category_id'] ?? 0);

        if (!$scanId || !$accountId) {
            http_response_code(400);
            echo json_encode(['error' => 'Scan ID and account ID required']);
            return;
        }

        try {
            $scan = $this->receiptService->getScanDetails($scanId);

            // Verify ownership
            if ($scan['user_id'] != $user['id']) {
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }

            $parsedData = $scan['parsed_data'];

            // Create transaction
            $this->db->query(
                "INSERT INTO transactions
                 (user_id, account_id, type, amount, currency, description, date, category_id, notes, receipt_scan_id)
                 VALUES (?, ?, 'expense', ?, ?, ?, ?, ?, ?, ?)",
                [
                    $user['id'],
                    $accountId,
                    $parsedData['total'] ?? 0,
                    $parsedData['currency'] ?? 'CZK',
                    $parsedData['merchant'] ?? 'Receipt Purchase',
                    $parsedData['date'] ?? date('Y-m-d'),
                    $categoryId ?: null,
                    $scan['ocr_text'] ?? null,
                    $scanId
                ]
            );

            $transactionId = $this->db->lastInsertId();

            // Update account balance
            $this->db->query(
                "UPDATE accounts
                 SET balance = balance - ?
                 WHERE id = ?",
                [$parsedData['total'], $accountId]
            );

            echo json_encode([
                'success' => true,
                'message' => 'Transaction created successfully',
                'transaction_id' => $transactionId
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Delete receipt scan
     */
    public function delete(): void {
        $user = $this->auth->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $scanId = (int)($data['scan_id'] ?? 0);

        if (!$scanId) {
            http_response_code(400);
            echo json_encode(['error' => 'Scan ID required']);
            return;
        }

        try {
            $scan = $this->db->query(
                "SELECT * FROM receipt_scans WHERE id = ?",
                [$scanId]
            )[0] ?? null;

            if (!$scan) {
                http_response_code(404);
                echo json_encode(['error' => 'Scan not found']);
                return;
            }

            // Verify ownership
            if ($scan['user_id'] != $user['id']) {
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }

            // Delete image file
            if (file_exists($scan['image_path'])) {
                unlink($scan['image_path']);
            }

            // Delete scan record (cascade deletes items and review queue entry)
            $this->db->query("DELETE FROM receipt_scans WHERE id = ?", [$scanId]);

            echo json_encode([
                'success' => true,
                'message' => 'Receipt deleted successfully'
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get OCR usage statistics
     */
    public function stats(): void {
        $user = $this->auth->requireAuth();

        $stats = $this->db->query(
            "SELECT
                COUNT(*) as total_scans,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'review_needed' THEN 1 ELSE 0 END) as review_needed,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                AVG(confidence_score) as avg_confidence,
                AVG(processing_time) as avg_processing_time,
                SUM(image_size) as total_size
             FROM receipt_scans
             WHERE user_id = ?",
            [$user['id']]
        )[0] ?? [];

        echo json_encode($stats);
    }

    /**
     * Merchant management page
     */
    public function merchants(): void {
        $user = $this->auth->requireAuth();

        $merchants = $this->db->query(
            "SELECT * FROM receipt_merchants ORDER BY name ASC"
        );

        $this->render('receipt/merchants', [
            'title' => 'Merchant Database',
            'merchants' => $merchants
        ]);
    }

    private function render(string $view, array $data = []): void {
        extract($data);
        $content = '';

        ob_start();
        require __DIR__ . "/../../views/{$view}.php";
        $content = ob_get_clean();

        require __DIR__ . '/../../views/layout.php';
    }
}
