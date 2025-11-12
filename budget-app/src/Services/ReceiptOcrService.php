<?php
namespace BudgetApp\Services;

use BudgetApp\Database;
use BudgetApp\Config;

/**
 * Receipt OCR Service
 *
 * Handles receipt scanning, OCR processing, and data extraction
 */
class ReceiptOcrService {
    private Database $db;
    private Config $config;
    private string $ocrProvider;
    private string $uploadDir;

    public function __construct(Database $db, Config $config) {
        $this->db = $db;
        $this->config = $config;
        $this->ocrProvider = $_ENV['OCR_PROVIDER'] ?? 'google';
        $this->uploadDir = $config->getBasePath() . '/uploads/receipts';

        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Process uploaded receipt image
     */
    public function processReceipt(int $userId, array $uploadedFile): array {
        // Validate and save image
        $imagePath = $this->saveUploadedImage($userId, $uploadedFile);

        // Create receipt scan record
        $scanId = $this->createScanRecord($userId, $imagePath, $uploadedFile['size']);

        // Process in background or immediately
        try {
            $result = $this->performOcr($scanId, $imagePath);
            return $result;
        } catch (\Exception $e) {
            $this->updateScanStatus($scanId, 'failed', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Perform OCR on receipt image
     */
    private function performOcr(int $scanId, string $imagePath): array {
        $startTime = microtime(true);

        // Preprocess image
        $processedImage = $this->preprocessImage($imagePath);

        // Perform OCR based on provider
        $ocrText = $this->callOcrProvider($processedImage);

        // Parse extracted text
        $parsedData = $this->parseReceiptData($ocrText);

        // Calculate confidence score
        $confidence = $this->calculateConfidence($parsedData);

        // Update scan record
        $processingTime = round((microtime(true) - $startTime) * 1000); // ms

        $this->db->query(
            "UPDATE receipt_scans
             SET ocr_text = ?, parsed_data = ?, confidence_score = ?,
                 status = ?, processing_time = ?, processed_at = CURRENT_TIMESTAMP
             WHERE id = ?",
            [
                $ocrText,
                json_encode($parsedData),
                $confidence,
                $confidence >= 0.7 ? 'completed' : 'review_needed',
                $processingTime,
                $scanId
            ]
        );

        // If confidence is low, add to review queue
        if ($confidence < 0.7) {
            $this->addToReviewQueue($scanId, $confidence);
        }

        // Extract and save line items
        if (!empty($parsedData['items'])) {
            $this->saveReceiptItems($scanId, $parsedData['items']);
        }

        // Log usage
        $this->logOcrUsage($scanId, $processingTime);

        return [
            'scan_id' => $scanId,
            'confidence' => $confidence,
            'parsed_data' => $parsedData,
            'status' => $confidence >= 0.7 ? 'completed' : 'review_needed'
        ];
    }

    /**
     * Parse receipt data from OCR text
     */
    private function parseReceiptData(string $text): array {
        $data = [
            'total' => null,
            'date' => null,
            'merchant' => null,
            'items' => [],
            'tax' => null,
            'currency' => 'CZK'
        ];

        // Parse total amount
        $data['total'] = $this->extractTotal($text);

        // Parse date
        $data['date'] = $this->extractDate($text);

        // Parse merchant
        $data['merchant'] = $this->extractMerchant($text);

        // Parse line items
        $data['items'] = $this->extractLineItems($text);

        // Parse tax
        $data['tax'] = $this->extractTax($text);

        // Detect currency
        $data['currency'] = $this->detectCurrency($text);

        return $data;
    }

    /**
     * Extract total amount from receipt text
     */
    private function extractTotal(string $text): ?float {
        // Common patterns for total
        $patterns = [
            '/TOTAL[:\s]*(\d+[.,]\d{2})/i',
            '/CELKEM[:\s]*(\d+[.,]\d{2})/i',
            '/SUM[:\s]*(\d+[.,]\d{2})/i',
            '/SUMA[:\s]*(\d+[.,]\d{2})/i',
            '/(\d+[.,]\d{2})\s*Kč/i',
            '/(\d+[.,]\d{2})\s*CZK/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $amount = str_replace(',', '.', $matches[1]);
                return (float)$amount;
            }
        }

        return null;
    }

    /**
     * Extract date from receipt text
     */
    private function extractDate(string $text): ?string {
        // Date patterns
        $patterns = [
            '/(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{4})/',  // DD.MM.YYYY
            '/(\d{4})[.\/-](\d{1,2})[.\/-](\d{1,2})/',  // YYYY-MM-DD
            '/(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{2})/',  // DD.MM.YY
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                // Convert to standard format
                try {
                    $date = new \DateTime($matches[0]);
                    return $date->format('Y-m-d');
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return null;
    }

    /**
     * Extract merchant name
     */
    private function extractMerchant(string $text): ?string {
        // Get first few lines (merchant name usually at top)
        $lines = explode("\n", $text);
        $topLines = array_slice($lines, 0, 5);

        // Check against known merchants
        foreach ($topLines as $line) {
            $line = trim($line);
            if (strlen($line) > 3 && strlen($line) < 50) {
                $merchant = $this->findKnownMerchant($line);
                if ($merchant) {
                    return $merchant;
                }
            }
        }

        // Return first non-empty line as fallback
        return $topLines[0] ?? null;
    }

    /**
     * Extract line items from receipt
     */
    private function extractLineItems(string $text): array {
        $items = [];
        $lines = explode("\n", $text);

        foreach ($lines as $line) {
            // Pattern: item name ... quantity x price = total
            if (preg_match('/(.+?)\s+(\d+)\s*[xX*]\s*(\d+[.,]\d{2})\s*=?\s*(\d+[.,]\d{2})/', $line, $matches)) {
                $items[] = [
                    'name' => trim($matches[1]),
                    'quantity' => (int)$matches[2],
                    'unit_price' => (float)str_replace(',', '.', $matches[3]),
                    'total' => (float)str_replace(',', '.', $matches[4])
                ];
            }
            // Pattern: item name ... price
            elseif (preg_match('/(.+?)\s+(\d+[.,]\d{2})\s*Kč?/i', $line, $matches)) {
                $items[] = [
                    'name' => trim($matches[1]),
                    'quantity' => 1,
                    'unit_price' => (float)str_replace(',', '.', $matches[2]),
                    'total' => (float)str_replace(',', '.', $matches[2])
                ];
            }
        }

        return $items;
    }

    /**
     * Extract tax amount
     */
    private function extractTax(string $text): ?float {
        $patterns = [
            '/DPH[:\s]*(\d+[.,]\d{2})/i',
            '/VAT[:\s]*(\d+[.,]\d{2})/i',
            '/TAX[:\s]*(\d+[.,]\d{2})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return (float)str_replace(',', '.', $matches[1]);
            }
        }

        return null;
    }

    /**
     * Detect currency from text
     */
    private function detectCurrency(string $text): string {
        $currencies = [
            'CZK' => ['Kč', 'CZK', 'Koruna'],
            'EUR' => ['€', 'EUR', 'Euro'],
            'USD' => ['$', 'USD', 'Dollar'],
            'GBP' => ['£', 'GBP', 'Pound'],
        ];

        foreach ($currencies as $code => $patterns) {
            foreach ($patterns as $pattern) {
                if (stripos($text, $pattern) !== false) {
                    return $code;
                }
            }
        }

        return 'CZK'; // Default
    }

    /**
     * Calculate confidence score
     */
    private function calculateConfidence(array $parsedData): float {
        $score = 0;
        $maxScore = 5;

        // Has total?
        if ($parsedData['total'] !== null) $score++;

        // Has date?
        if ($parsedData['date'] !== null) $score++;

        // Has merchant?
        if ($parsedData['merchant'] !== null) $score++;

        // Has items?
        if (!empty($parsedData['items'])) $score++;

        // Items match total? (within 5%)
        if (!empty($parsedData['items']) && $parsedData['total'] !== null) {
            $itemsTotal = array_sum(array_column($parsedData['items'], 'total'));
            $diff = abs($itemsTotal - $parsedData['total']);
            if ($diff / $parsedData['total'] < 0.05) {
                $score++;
            }
        }

        return round($score / $maxScore, 2);
    }

    /**
     * Preprocess image for better OCR
     */
    private function preprocessImage(string $imagePath): string {
        // In real implementation, use ImageMagick or GD to:
        // - Convert to grayscale
        // - Increase contrast
        // - Remove noise
        // - Deskew if needed

        return $imagePath; // Return original for now
    }

    /**
     * Call OCR provider API
     */
    private function callOcrProvider(string $imagePath): string {
        switch ($this->ocrProvider) {
            case 'google':
                return $this->callGoogleVisionApi($imagePath);
            case 'aws':
                return $this->callAwsTextract($imagePath);
            case 'azure':
                return $this->callAzureVision($imagePath);
            case 'tesseract':
                return $this->callTesseract($imagePath);
            default:
                throw new \Exception("Unknown OCR provider: {$this->ocrProvider}");
        }
    }

    private function callGoogleVisionApi(string $imagePath): string {
        // Google Cloud Vision API implementation
        $apiKey = $_ENV['GOOGLE_VISION_API_KEY'] ?? '';

        $imageContent = base64_encode(file_get_contents($imagePath));

        $requestData = [
            'requests' => [
                [
                    'image' => ['content' => $imageContent],
                    'features' => [['type' => 'TEXT_DETECTION']]
                ]
            ]
        ];

        $ch = curl_init("https://vision.googleapis.com/v1/images:annotate?key={$apiKey}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        return $data['responses'][0]['fullTextAnnotation']['text'] ?? '';
    }

    private function callAwsTextract(string $imagePath): string {
        // AWS Textract implementation
        // Requires AWS SDK
        return "AWS Textract not implemented yet";
    }

    private function callAzureVision(string $imagePath): string {
        // Azure Computer Vision implementation
        return "Azure Vision not implemented yet";
    }

    private function callTesseract(string $imagePath): string {
        // Tesseract OCR (local)
        $output = shell_exec("tesseract {$imagePath} stdout 2>/dev/null");
        return $output ?: '';
    }

    // Helper methods

    private function saveUploadedImage(int $userId, array $file): string {
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('receipt_' . $userId . '_') . '.' . $extension;
        $targetPath = $this->uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new \Exception("Failed to save uploaded image");
        }

        return $targetPath;
    }

    private function createScanRecord(int $userId, string $imagePath, int $imageSize): int {
        $this->db->query(
            "INSERT INTO receipt_scans (user_id, image_path, image_size, ocr_provider, status)
             VALUES (?, ?, ?, ?, 'processing')",
            [$userId, $imagePath, $imageSize, $this->ocrProvider]
        );

        return $this->db->lastInsertId();
    }

    private function updateScanStatus(int $scanId, string $status, ?string $errorMessage = null): void {
        $this->db->query(
            "UPDATE receipt_scans SET status = ?, error_message = ? WHERE id = ?",
            [$status, $errorMessage, $scanId]
        );
    }

    private function addToReviewQueue(int $scanId, float $confidence): void {
        $priority = $confidence < 0.3 ? 2 : ($confidence < 0.5 ? 1 : 0);

        $this->db->query(
            "INSERT INTO receipt_review_queue (receipt_scan_id, priority)
             VALUES (?, ?)",
            [$scanId, $priority]
        );
    }

    private function saveReceiptItems(int $scanId, array $items): void {
        foreach ($items as $index => $item) {
            $this->db->query(
                "INSERT INTO receipt_items
                 (receipt_scan_id, item_name, quantity, unit_price, total_price, line_number)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $scanId,
                    $item['name'],
                    $item['quantity'] ?? 1,
                    $item['unit_price'] ?? $item['total'],
                    $item['total'],
                    $index + 1
                ]
            );
        }
    }

    private function findKnownMerchant(string $text): ?string {
        $result = $this->db->query(
            "SELECT name FROM receipt_merchants
             WHERE LOWER(normalized_name) = LOWER(?) OR aliases LIKE ?
             LIMIT 1",
            [$text, '%' . $text . '%']
        );

        return $result[0]['name'] ?? null;
    }

    private function logOcrUsage(int $scanId, int $processingTime): void {
        $scan = $this->db->query("SELECT user_id, image_size FROM receipt_scans WHERE id = ?", [$scanId]);

        if (!empty($scan)) {
            $this->db->query(
                "INSERT INTO ocr_usage_log
                 (user_id, provider, operation, image_size, processing_time, success)
                 VALUES (?, ?, 'scan', ?, ?, 1)",
                [$scan[0]['user_id'], $this->ocrProvider, $scan[0]['image_size'], $processingTime]
            );
        }
    }

    /**
     * Get user's recent scans
     */
    public function getUserScans(int $userId, int $limit = 20): array {
        return $this->db->query(
            "SELECT * FROM receipt_scans
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT ?",
            [$userId, $limit]
        );
    }

    /**
     * Get scan details with items
     */
    public function getScanDetails(int $scanId): array {
        $scan = $this->db->query(
            "SELECT * FROM receipt_scans WHERE id = ?",
            [$scanId]
        );

        if (empty($scan)) {
            throw new \Exception("Receipt scan not found");
        }

        $scan = $scan[0];
        $scan['parsed_data'] = json_decode($scan['parsed_data'] ?? '{}', true);

        $items = $this->db->query(
            "SELECT * FROM receipt_items WHERE receipt_scan_id = ? ORDER BY line_number",
            [$scanId]
        );

        $scan['items'] = $items;

        return $scan;
    }
}
