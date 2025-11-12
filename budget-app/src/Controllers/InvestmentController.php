<?php
namespace BudgetApp\Controllers;

use BudgetApp\Database;
use BudgetApp\Services\InvestmentService;
use BudgetApp\Auth;

class InvestmentController {
    private Database $db;
    private InvestmentService $investmentService;
    private Auth $auth;

    public function __construct(Database $db, InvestmentService $investmentService, Auth $auth) {
        $this->db = $db;
        $this->investmentService = $investmentService;
        $this->auth = $auth;
    }

    public function index(): void {
        $user = $this->auth->requireAuth();
        $portfolio = $this->investmentService->getUserPortfolio($user['id']);
        $performance = $this->investmentService->getPortfolioPerformance($user['id'], 30);
        $sectors = $this->investmentService->getSectorAllocation($user['id']);
        
        $this->render('investment/index', [
            'title' => 'Investment Portfolio',
            'portfolio' => $portfolio,
            'performance' => $performance,
            'sectors' => $sectors
        ]);
    }

    public function addHolding(): void {
        $user = $this->auth->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $holdingId = $this->investmentService->addHolding($data['account_id'], $data);
            echo json_encode(['success' => true, 'holding_id' => $holdingId]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function updatePrices(): void {
        $user = $this->auth->requireAuth();
        $portfolio = $this->investmentService->getUserPortfolio($user['id']);
        
        $updated = 0;
        foreach ($portfolio['accounts'] as $account) {
            foreach ($account['holdings'] as $holding) {
                if ($this->investmentService->updateHoldingPrice($holding['id'])) {
                    $updated++;
                }
            }
        }
        
        echo json_encode(['success' => true, 'updated' => $updated]);
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
