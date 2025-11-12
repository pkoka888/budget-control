<?php
namespace BudgetApp\Controllers;

use BudgetApp\Database;
use BudgetApp\Services\DataExportService;
use BudgetApp\Auth;

class ExportController {
    private Database $db;
    private DataExportService $exportService;
    private Auth $auth;

    public function __construct(Database $db, DataExportService $exportService, Auth $auth) {
        $this->db = $db;
        $this->exportService = $exportService;
        $this->auth = $auth;
    }

    public function index(): void {
        $user = $this->auth->requireAuth();
        
        $jobs = $this->db->query(
            "SELECT * FROM export_jobs WHERE user_id = ? ORDER BY created_at DESC LIMIT 20",
            [$user['id']]
        );
        
        $this->render('export/index', [
            'title' => 'Data Export & API',
            'jobs' => $jobs
        ]);
    }

    public function create(): void {
        $user = $this->auth->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $jobId = $this->exportService->createExport($user['id'], $data);
            echo json_encode(['success' => true, 'job_id' => $jobId]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function apiKeys(): void {
        $user = $this->auth->requireAuth();
        
        $keys = $this->db->query(
            "SELECT * FROM api_keys WHERE user_id = ? ORDER BY created_at DESC",
            [$user['id']]
        );
        
        $this->render('export/api-keys', [
            'title' => 'API Keys',
            'api_keys' => $keys
        ]);
    }

    public function createApiKey(): void {
        $user = $this->auth->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $apiKey = $this->exportService->createApiKey(
                $user['id'],
                $data['key_name'],
                $data['permissions'] ?? ['read']
            );
            echo json_encode(['success' => true, 'api_key' => $apiKey]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
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
