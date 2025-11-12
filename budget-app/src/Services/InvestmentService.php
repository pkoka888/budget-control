<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

/**
 * Investment Portfolio Service
 * 
 * Handles investment tracking, portfolio management, and market data
 */
class InvestmentService {
    private Database $db;
    private array $config;
    
    // API providers for market data
    private const PROVIDER_ALPHA_VANTAGE = 'alphavantage';
    private const PROVIDER_YAHOO = 'yahoo';
    private const PROVIDER_COINMARKETCAP = 'coinmarketcap';
    
    public function __construct(Database $db, array $config = []) {
        $this->db = $db;
        $this->config = array_merge([
            'alpha_vantage_key' => $_ENV['ALPHA_VANTAGE_API_KEY'] ?? '',
            'coinmarketcap_key' => $_ENV['COINMARKETCAP_API_KEY'] ?? '',
            'cache_duration' => 300, // 5 minutes
            'default_provider' => self::PROVIDER_YAHOO
        ], $config);
    }
    
    // ===== PORTFOLIO MANAGEMENT =====
    
    public function createInvestmentAccount(int $userId, string $name, string $type, ?string $broker = null): array {
        $this->db->query(
            "INSERT INTO investment_accounts (user_id, name, type, broker) VALUES (?, ?, ?, ?)",
            [$userId, $name, $type, $broker]
        );
        
        return $this->getInvestmentAccount($this->db->lastInsertId());
    }
    
    public function getInvestmentAccount(int $accountId): array {
        $account = $this->db->query(
            "SELECT * FROM investment_accounts WHERE id = ?",
            [$accountId]
        )[0] ?? null;
        
        if (!$account) {
            throw new \Exception("Investment account not found");
        }
        
        // Get holdings
        $account['holdings'] = $this->getAccountHoldings($accountId);
        $account['total_value'] = array_sum(array_column($account['holdings'], 'current_value'));
        $account['total_cost'] = array_sum(array_column($account['holdings'], 'cost_basis'));
        $account['total_gain_loss'] = $account['total_value'] - $account['total_cost'];
        $account['total_gain_loss_pct'] = $account['total_cost'] > 0 
            ? ($account['total_gain_loss'] / $account['total_cost']) * 100 
            : 0;
        
        return $account;
    }
    
    public function getUserPortfolio(int $userId): array {
        $accounts = $this->db->query(
            "SELECT * FROM investment_accounts WHERE user_id = ? AND is_active = 1",
            [$userId]
        );
        
        $portfolio = [
            'accounts' => [],
            'total_value' => 0,
            'total_cost' => 0,
            'total_gain_loss' => 0,
            'daily_change' => 0
        ];
        
        foreach ($accounts as $account) {
            $accountData = $this->getInvestmentAccount($account['id']);
            $portfolio['accounts'][] = $accountData;
            $portfolio['total_value'] += $accountData['total_value'];
            $portfolio['total_cost'] += $accountData['total_cost'];
        }
        
        $portfolio['total_gain_loss'] = $portfolio['total_value'] - $portfolio['total_cost'];
        $portfolio['total_gain_loss_pct'] = $portfolio['total_cost'] > 0 
            ? ($portfolio['total_gain_loss'] / $portfolio['total_cost']) * 100 
            : 0;
        
        return $portfolio;
    }
    
    // ===== HOLDINGS MANAGEMENT =====
    
    public function addHolding(int $accountId, array $data): int {
        $this->db->query(
            "INSERT INTO investment_holdings 
             (investment_account_id, symbol, name, type, quantity, average_buy_price, currency, exchange, sector)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $accountId,
                strtoupper($data['symbol']),
                $data['name'],
                $data['type'],
                $data['quantity'],
                $data['price'],
                $data['currency'] ?? 'USD',
                $data['exchange'] ?? null,
                $data['sector'] ?? null
            ]
        );
        
        $holdingId = $this->db->lastInsertId();
        
        // Update current price
        $this->updateHoldingPrice($holdingId);
        
        return $holdingId;
    }
    
    public function getAccountHoldings(int $accountId): array {
        $holdings = $this->db->query(
            "SELECT * FROM investment_holdings WHERE investment_account_id = ?",
            [$accountId]
        );
        
        foreach ($holdings as &$holding) {
            $holding['cost_basis'] = $holding['quantity'] * $holding['average_buy_price'];
            $holding['current_value'] = $holding['quantity'] * ($holding['current_price'] ?? $holding['average_buy_price']);
            $holding['gain_loss'] = $holding['current_value'] - $holding['cost_basis'];
            $holding['gain_loss_pct'] = $holding['cost_basis'] > 0 
                ? ($holding['gain_loss'] / $holding['cost_basis']) * 100 
                : 0;
        }
        
        return $holdings;
    }
    
    public function updateHoldingPrice(int $holdingId): bool {
        $holding = $this->db->query(
            "SELECT * FROM investment_holdings WHERE id = ?",
            [$holdingId]
        )[0] ?? null;
        
        if (!$holding) return false;
        
        $price = $this->fetchCurrentPrice($holding['symbol'], $holding['type']);
        
        if ($price) {
            $this->db->query(
                "UPDATE investment_holdings 
                 SET current_price = ?, last_price_update = CURRENT_TIMESTAMP
                 WHERE id = ?",
                [$price, $holdingId]
            );
            return true;
        }
        
        return false;
    }
    
    // ===== TRANSACTIONS =====
    
    public function recordTransaction(int $accountId, array $data): int {
        $this->db->query(
            "INSERT INTO investment_transactions 
             (investment_account_id, holding_id, type, symbol, quantity, price, total_amount, currency, fees, tax, transaction_date, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $accountId,
                $data['holding_id'] ?? null,
                $data['type'],
                strtoupper($data['symbol']),
                $data['quantity'] ?? null,
                $data['price'] ?? null,
                $data['total_amount'],
                $data['currency'] ?? 'USD',
                $data['fees'] ?? 0,
                $data['tax'] ?? 0,
                $data['date'] ?? date('Y-m-d'),
                $data['notes'] ?? null
            ]
        );
        
        $transactionId = $this->db->lastInsertId();
        
        // Update holding if buy/sell
        if (in_array($data['type'], ['buy', 'sell']) && isset($data['holding_id'])) {
            $this->updateHoldingAfterTransaction($data['holding_id'], $data);
        }
        
        return $transactionId;
    }
    
    private function updateHoldingAfterTransaction(int $holdingId, array $transaction): void {
        $holding = $this->db->query(
            "SELECT * FROM investment_holdings WHERE id = ?",
            [$holdingId]
        )[0] ?? null;
        
        if (!$holding) return;
        
        if ($transaction['type'] === 'buy') {
            $newQuantity = $holding['quantity'] + $transaction['quantity'];
            $newAvgPrice = (($holding['quantity'] * $holding['average_buy_price']) + 
                           ($transaction['quantity'] * $transaction['price'])) / $newQuantity;
            
            $this->db->query(
                "UPDATE investment_holdings SET quantity = ?, average_buy_price = ? WHERE id = ?",
                [$newQuantity, $newAvgPrice, $holdingId]
            );
        } elseif ($transaction['type'] === 'sell') {
            $newQuantity = $holding['quantity'] - $transaction['quantity'];
            
            if ($newQuantity <= 0) {
                $this->db->query("DELETE FROM investment_holdings WHERE id = ?", [$holdingId]);
            } else {
                $this->db->query(
                    "UPDATE investment_holdings SET quantity = ? WHERE id = ?",
                    [$newQuantity, $holdingId]
                );
            }
        }
    }
    
    // ===== MARKET DATA =====
    
    public function fetchCurrentPrice(string $symbol, string $type = 'stock'): ?float {
        // Check cache first
        $cached = $this->db->query(
            "SELECT price FROM investment_price_history 
             WHERE symbol = ? AND date = DATE('now') 
             ORDER BY created_at DESC LIMIT 1",
            [$symbol]
        )[0] ?? null;
        
        if ($cached) {
            return (float)$cached['price'];
        }
        
        // Fetch from API
        $price = null;
        
        if ($type === 'crypto') {
            $price = $this->fetchCryptoPrice($symbol);
        } else {
            $price = $this->fetchStockPrice($symbol);
        }
        
        // Cache the price
        if ($price) {
            $this->db->query(
                "INSERT OR REPLACE INTO investment_price_history (symbol, price, date, close) VALUES (?, ?, DATE('now'), ?)",
                [$symbol, $price, $price]
            );
        }
        
        return $price;
    }
    
    private function fetchStockPrice(string $symbol): ?float {
        if ($this->config['default_provider'] === self::PROVIDER_ALPHA_VANTAGE && $this->config['alpha_vantage_key']) {
            return $this->fetchFromAlphaVantage($symbol);
        }
        
        return $this->fetchFromYahoo($symbol);
    }
    
    private function fetchFromYahoo(string $symbol): ?float {
        $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            $price = $data['chart']['result'][0]['meta']['regularMarketPrice'] ?? null;
            return $price ? (float)$price : null;
        }
        
        return null;
    }
    
    private function fetchFromAlphaVantage(string $symbol): ?float {
        $apiKey = $this->config['alpha_vantage_key'];
        $url = "https://www.alphavantage.co/query?function=GLOBAL_QUOTE&symbol={$symbol}&apikey={$apiKey}";
        
        $response = @file_get_contents($url);
        if ($response) {
            $data = json_decode($response, true);
            $price = $data['Global Quote']['05. price'] ?? null;
            return $price ? (float)$price : null;
        }
        
        return null;
    }
    
    private function fetchCryptoPrice(string $symbol): ?float {
        if (!$this->config['coinmarketcap_key']) {
            // Fallback to free API
            return $this->fetchCryptoFromCoingecko($symbol);
        }
        
        $apiKey = $this->config['coinmarketcap_key'];
        $url = "https://pro-api.coinmarketcap.com/v1/cryptocurrency/quotes/latest?symbol={$symbol}";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-CMC_PRO_API_KEY: {$apiKey}"
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        if ($response) {
            $data = json_decode($response, true);
            $price = $data['data'][$symbol]['quote']['USD']['price'] ?? null;
            return $price ? (float)$price : null;
        }
        
        return null;
    }
    
    private function fetchCryptoFromCoingecko(string $symbol): ?float {
        $coinId = strtolower($symbol);
        $url = "https://api.coingecko.com/api/v3/simple/price?ids={$coinId}&vs_currencies=usd";
        
        $response = @file_get_contents($url);
        if ($response) {
            $data = json_decode($response, true);
            $price = $data[$coinId]['usd'] ?? null;
            return $price ? (float)$price : null;
        }
        
        return null;
    }
    
    // ===== PORTFOLIO ANALYSIS =====
    
    public function getPortfolioPerformance(int $userId, int $days = 30): array {
        $snapshots = $this->db->query(
            "SELECT * FROM portfolio_snapshots 
             WHERE user_id = ? AND snapshot_date >= DATE('now', '-{$days} days')
             ORDER BY snapshot_date ASC",
            [$userId]
        );
        
        return [
            'snapshots' => $snapshots,
            'start_value' => $snapshots[0]['total_value'] ?? 0,
            'end_value' => end($snapshots)['total_value'] ?? 0,
            'total_return' => (end($snapshots)['total_value'] ?? 0) - ($snapshots[0]['total_value'] ?? 0)
        ];
    }
    
    public function getSectorAllocation(int $userId): array {
        $holdings = $this->db->query(
            "SELECT h.sector, SUM(h.quantity * h.current_price) as value
             FROM investment_holdings h
             JOIN investment_accounts a ON a.id = h.investment_account_id
             WHERE a.user_id = ? AND a.is_active = 1
             GROUP BY h.sector",
            [$userId]
        );
        
        $total = array_sum(array_column($holdings, 'value'));
        
        foreach ($holdings as &$sector) {
            $sector['percentage'] = $total > 0 ? ($sector['value'] / $total) * 100 : 0;
        }
        
        return $holdings;
    }
    
    public function createPortfolioSnapshot(int $userId, ?int $accountId = null): void {
        $portfolio = $this->getUserPortfolio($userId);
        
        $this->db->query(
            "INSERT INTO portfolio_snapshots 
             (user_id, investment_account_id, snapshot_date, total_value, total_cost_basis, total_gain_loss, total_gain_loss_percentage, holdings_json)
             VALUES (?, ?, DATE('now'), ?, ?, ?, ?, ?)",
            [
                $userId,
                $accountId,
                $portfolio['total_value'],
                $portfolio['total_cost'],
                $portfolio['total_gain_loss'],
                $portfolio['total_gain_loss_pct'],
                json_encode($portfolio['accounts'])
            ]
        );
    }
}
