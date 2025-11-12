<?php
namespace BudgetApp\Controllers;

use BudgetApp\Database;
use BudgetApp\Services\CurrencyService;
use BudgetApp\Auth;

/**
 * Currency Controller
 *
 * Handles multi-currency operations, preferences, and conversions
 */
class CurrencyController {
    private Database $db;
    private CurrencyService $currencyService;
    private Auth $auth;

    public function __construct(Database $db, CurrencyService $currencyService, Auth $auth) {
        $this->db = $db;
        $this->currencyService = $currencyService;
        $this->auth = $auth;
    }

    /**
     * Currency settings page
     */
    public function index(): void {
        $user = $this->auth->requireAuth();

        // Get user preferences
        $preferences = $this->currencyService->getUserPreferences($user['id']);

        // Get supported currencies
        $currencies = $this->currencyService->getSupportedCurrencies();

        // Get current exchange rates for common currencies
        $commonCurrencies = ['EUR', 'USD', 'GBP', 'CHF'];
        $rates = [];
        foreach ($commonCurrencies as $currency) {
            if ($currency !== $preferences['base_currency']) {
                $rates[$currency] = $this->currencyService->getExchangeRate(
                    $preferences['base_currency'],
                    $currency
                );
            }
        }

        $this->render('currency/index', [
            'title' => 'Currency Settings',
            'preferences' => $preferences,
            'currencies' => $currencies,
            'rates' => $rates
        ]);
    }

    /**
     * Update user currency preferences
     */
    public function updatePreferences(): void {
        $user = $this->auth->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $baseCurrency = $data['base_currency'] ?? null;
        $displayCurrencies = $data['display_currencies'] ?? [];
        $autoConvert = $data['auto_convert'] ?? true;

        if (!$baseCurrency) {
            http_response_code(400);
            echo json_encode(['error' => 'Base currency is required']);
            return;
        }

        try {
            $this->currencyService->updateUserPreferences(
                $user['id'],
                $baseCurrency,
                $displayCurrencies,
                $autoConvert
            );

            echo json_encode([
                'success' => true,
                'message' => 'Currency preferences updated successfully'
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get current exchange rate
     */
    public function getExchangeRate(): void {
        $this->auth->requireAuth();

        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $date = $_GET['date'] ?? null;

        if (!$from || !$to) {
            http_response_code(400);
            echo json_encode(['error' => 'From and to currencies are required']);
            return;
        }

        try {
            $rate = $this->currencyService->getExchangeRate($from, $to, $date);

            echo json_encode([
                'from' => $from,
                'to' => $to,
                'rate' => $rate,
                'date' => $date ?? date('Y-m-d')
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Convert amount between currencies
     */
    public function convert(): void {
        $this->auth->requireAuth();

        $amount = $_GET['amount'] ?? null;
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $date = $_GET['date'] ?? null;

        if (!$amount || !$from || !$to) {
            http_response_code(400);
            echo json_encode(['error' => 'Amount, from, and to currencies are required']);
            return;
        }

        try {
            $converted = $this->currencyService->convert((float)$amount, $from, $to, $date);
            $rate = $this->currencyService->getExchangeRate($from, $to, $date);

            echo json_encode([
                'original_amount' => (float)$amount,
                'from' => $from,
                'to' => $to,
                'converted_amount' => $converted,
                'rate' => $rate,
                'date' => $date ?? date('Y-m-d')
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get historical exchange rates for chart
     */
    public function getHistory(): void {
        $this->auth->requireAuth();

        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $days = (int)($_GET['days'] ?? 30);

        if (!$from || !$to) {
            http_response_code(400);
            echo json_encode(['error' => 'From and to currencies are required']);
            return;
        }

        try {
            $history = $this->currencyService->getExchangeRateHistory($from, $to, $days);

            echo json_encode([
                'from' => $from,
                'to' => $to,
                'history' => $history
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Update all exchange rates (admin function)
     */
    public function updateRates(): void {
        $user = $this->auth->requireAuth();

        // Check if user is admin
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $baseCurrency = $data['base_currency'] ?? 'EUR';

        try {
            $count = $this->currencyService->updateAllRates($baseCurrency);

            echo json_encode([
                'success' => true,
                'message' => "Updated {$count} exchange rates",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get supported currencies list
     */
    public function getCurrencies(): void {
        $this->auth->requireAuth();

        try {
            $currencies = $this->currencyService->getSupportedCurrencies();
            echo json_encode($currencies);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Calculate exchange gain/loss for transaction
     */
    public function calculateGainLoss(): void {
        $this->auth->requireAuth();

        $transactionId = $_GET['transaction_id'] ?? null;

        if (!$transactionId) {
            http_response_code(400);
            echo json_encode(['error' => 'Transaction ID is required']);
            return;
        }

        try {
            // Get transaction details
            $transaction = $this->db->query(
                "SELECT * FROM transactions WHERE id = ?",
                [$transactionId]
            )[0] ?? null;

            if (!$transaction) {
                http_response_code(404);
                echo json_encode(['error' => 'Transaction not found']);
                return;
            }

            if ($transaction['original_currency']) {
                $gainLoss = $this->currencyService->calculateExchangeGainLoss(
                    $transaction['original_amount'],
                    $transaction['original_currency'],
                    $transaction['currency'],
                    $transaction['date'],
                    date('Y-m-d')
                );

                echo json_encode($gainLoss);
            } else {
                echo json_encode([
                    'gain_loss' => 0,
                    'percentage' => 0,
                    'message' => 'No foreign currency involved'
                ]);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Currency converter tool page
     */
    public function converter(): void {
        $user = $this->auth->requireAuth();

        $preferences = $this->currencyService->getUserPreferences($user['id']);
        $currencies = $this->currencyService->getSupportedCurrencies();

        $this->render('currency/converter', [
            'title' => 'Currency Converter',
            'preferences' => $preferences,
            'currencies' => $currencies
        ]);
    }

    /**
     * Exchange rate trends and analysis
     */
    public function trends(): void {
        $user = $this->auth->requireAuth();

        $preferences = $this->currencyService->getUserPreferences($user['id']);
        $baseCurrency = $preferences['base_currency'];

        // Get major currencies
        $majorCurrencies = ['EUR', 'USD', 'GBP', 'JPY', 'CHF', 'CNY'];
        $trends = [];

        foreach ($majorCurrencies as $currency) {
            if ($currency !== $baseCurrency) {
                $history = $this->currencyService->getExchangeRateHistory(
                    $baseCurrency,
                    $currency,
                    90  // 90 days
                );

                $trends[$currency] = $history;
            }
        }

        $this->render('currency/trends', [
            'title' => 'Exchange Rate Trends',
            'base_currency' => $baseCurrency,
            'trends' => $trends,
            'currencies' => $this->currencyService->getSupportedCurrencies()
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
