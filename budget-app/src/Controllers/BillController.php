<?php
namespace BudgetApp\Controllers;

use BudgetApp\Database;
use BudgetApp\Services\BillAutomationService;
use BudgetApp\Auth;

class BillController {
    private Database $db;
    private BillAutomationService $billService;
    private Auth $auth;

    public function __construct(Database $db, BillAutomationService $billService, Auth $auth) {
        $this->db = $db;
        $this->billService = $billService;
        $this->auth = $auth;
    }

    public function index(): void {
        $user = $this->auth->requireAuth();
        $bills = $this->billService->getUserBills($user['id']);
        $upcoming = $this->billService->getUpcomingBills($user['id'], 30);
        $analytics = $this->billService->getBillAnalytics($user['id']);
        
        $this->render('bill/index', [
            'title' => 'Bills & Subscriptions',
            'bills' => $bills,
            'upcoming' => $upcoming,
            'analytics' => $analytics
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
            $billId = $this->billService->createRecurringBill($user['id'], $data);
            echo json_encode(['success' => true, 'bill_id' => $billId]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function markPaid(): void {
        $user = $this->auth->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $this->billService->markBillPaid($data['payment_id'], $data);
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function subscriptions(): void {
        $user = $this->auth->requireAuth();
        $subscriptions = $this->billService->getUserSubscriptions($user['id']);
        
        $this->render('bill/subscriptions', [
            'title' => 'Subscriptions',
            'subscriptions' => $subscriptions
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
