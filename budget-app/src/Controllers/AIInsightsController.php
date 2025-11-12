<?php
namespace BudgetApp\Controllers;

use BudgetApp\Database;
use BudgetApp\Services\AIInsightsService;
use BudgetApp\Auth;

class AIInsightsController {
    private Database $db;
    private AIInsightsService $aiService;
    private Auth $auth;

    public function __construct(Database $db, AIInsightsService $aiService, Auth $auth) {
        $this->db = $db;
        $this->aiService = $aiService;
        $this->auth = $auth;
    }

    public function index(): void {
        $user = $this->auth->requireAuth();
        $insights = $this->aiService->getUserInsights($user['id']);
        
        $this->render('ai-insights/index', [
            'title' => 'Financial Insights',
            'insights' => $insights
        ]);
    }

    public function generate(): void {
        $user = $this->auth->requireAuth();
        
        try {
            $insights = $this->aiService->generateInsights($user['id']);
            echo json_encode(['success' => true, 'count' => count($insights)]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function chat(): void {
        $user = $this->auth->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $sessionId = $data['session_id'] ?? session_id();
        
        try {
            $response = $this->aiService->chat($user['id'], $data['message'], $sessionId);
            echo json_encode(['success' => true, 'response' => $response]);
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
