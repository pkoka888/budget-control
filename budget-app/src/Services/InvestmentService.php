<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

class InvestmentService {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Get portfolio summary for user
     */
    public function getPortfolioSummary(int $userId): array {
        // Get all investments with current values
        $investments = $this->db->query(
            "SELECT i.*, ia.name as account_name, ia.account_type
             FROM investments i
             LEFT JOIN investment_accounts ia ON i.account_id = ia.id
             WHERE i.user_id = ? AND i.is_active = 1
             ORDER BY i.asset_type, i.symbol",
            [$userId]
        );

        $totalValue = 0;
        $totalCost = 0;
        $assetAllocation = [];
        $accountAllocation = [];

        foreach ($investments as &$investment) {
            $currentValue = $investment['quantity'] * $investment['current_price'];
            $costBasis = $investment['quantity'] * $investment['purchase_price'];

            $investment['current_value'] = $currentValue;
            $investment['cost_basis'] = $costBasis;
            $investment['unrealized_gain'] = $currentValue - $costBasis;
            $investment['gain_percentage'] = $costBasis > 0 ? (($currentValue - $costBasis) / $costBasis) * 100 : 0;

            $totalValue += $currentValue;
            $totalCost += $costBasis;

            // Asset allocation
            $assetType = $investment['asset_type'];
            if (!isset($assetAllocation[$assetType])) {
                $assetAllocation[$assetType] = ['value' => 0, 'percentage' => 0];
            }
            $assetAllocation[$assetType]['value'] += $currentValue;

            // Account allocation
            $accountId = $investment['account_id'];
            if (!isset($accountAllocation[$accountId])) {
                $accountAllocation[$accountId] = [
                    'name' => $investment['account_name'] ?: 'Unknown Account',
                    'type' => $investment['account_type'] ?: 'brokerage',
                    'value' => 0,
                    'percentage' => 0
                ];
            }
            $accountAllocation[$accountId]['value'] += $currentValue;
        }

        // Calculate percentages
        foreach ($assetAllocation as &$allocation) {
            $allocation['percentage'] = $totalValue > 0 ? ($allocation['value'] / $totalValue) * 100 : 0;
        }

        foreach ($accountAllocation as &$allocation) {
            $allocation['percentage'] = $totalValue > 0 ? ($allocation['value'] / $totalValue) * 100 : 0;
        }

        return [
            'investments' => $investments,
            'total_value' => $totalValue,
            'total_cost' => $totalCost,
            'total_gain' => $totalValue - $totalCost,
            'total_gain_percentage' => $totalCost > 0 ? (($totalValue - $totalCost) / $totalCost) * 100 : 0,
            'asset_allocation' => $assetAllocation,
            'account_allocation' => $accountAllocation,
            'investment_count' => count($investments)
        ];
    }

    /**
     * Record investment transaction
     */
    public function recordTransaction(int $userId, array $data): int {
        // Validate required fields
        $required = ['investment_id', 'transaction_type', 'total_amount', 'transaction_date'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        // Verify investment belongs to user
        $investment = $this->db->queryOne(
            "SELECT i.*, ia.user_id as account_user_id FROM investments i
             LEFT JOIN investment_accounts ia ON i.account_id = ia.id
             WHERE i.id = ?",
            [$data['investment_id']]
        );

        if (!$investment || ($investment['user_id'] != $userId && $investment['account_user_id'] != $userId)) {
            throw new \Exception("Investment not found or access denied");
        }

        // Calculate quantity for buy/sell transactions
        $quantity = $data['quantity'] ?? null;
        if (in_array($data['transaction_type'], ['buy', 'sell']) && $quantity === null) {
            throw new \InvalidArgumentException("Quantity required for buy/sell transactions");
        }

        // Insert transaction
        $transactionId = $this->db->insert('investment_transactions', [
            'user_id' => $userId,
            'investment_id' => $data['investment_id'],
            'account_id' => $investment['account_id'],
            'transaction_type' => $data['transaction_type'],
            'quantity' => $quantity,
            'price' => $data['price'] ?? null,
            'total_amount' => $data['total_amount'],
            'currency' => $data['currency'] ?? $investment['currency'] ?? 'CZK',
            'exchange_rate' => $data['exchange_rate'] ?? 1.0,
            'transaction_date' => $data['transaction_date'],
            'settlement_date' => $data['settlement_date'] ?? $data['transaction_date'],
            'fees' => $data['fees'] ?? 0,
            'taxes' => $data['taxes'] ?? 0,
            'notes' => $data['notes'] ?? null
        ]);

        // Update investment quantity and average price for buy/sell
        if (in_array($data['transaction_type'], ['buy', 'sell'])) {
            $this->updateInvestmentPosition($data['investment_id'], $data['transaction_type'], $quantity, $data['price'] ?? 0);
        }

        return $transactionId;
    }

    /**
     * Update investment position after transaction
     */
    private function updateInvestmentPosition(int $investmentId, string $type, float $quantity, float $price): void {
        $investment = $this->db->queryOne(
            "SELECT * FROM investments WHERE id = ?",
            [$investmentId]
        );

        if (!$investment) return;

        if ($type === 'buy') {
            // Calculate new average price
            $currentValue = $investment['quantity'] * $investment['purchase_price'];
            $newValue = $quantity * $price;
            $totalQuantity = $investment['quantity'] + $quantity;
            $newAveragePrice = $totalQuantity > 0 ? ($currentValue + $newValue) / $totalQuantity : $price;

            $this->db->update('investments', [
                'quantity' => $totalQuantity,
                'purchase_price' => $newAveragePrice,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $investmentId]);
        } elseif ($type === 'sell') {
            $newQuantity = max(0, $investment['quantity'] - $quantity);
            $this->db->update('investments', [
                'quantity' => $newQuantity,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $investmentId]);
        }
    }

    /**
     * Get investment performance over time
     */
    public function getPerformance(int $userId, string $period = '1Y'): array {
        $endDate = date('Y-m-d');
        $startDate = $this->getStartDateForPeriod($period);

        // Get daily portfolio values
        $performance = $this->db->query(
            "SELECT
                ip.date,
                SUM(ip.price * i.quantity) as portfolio_value
             FROM investment_prices ip
             JOIN investments i ON ip.investment_id = i.id
             WHERE i.user_id = ? AND i.is_active = 1
               AND ip.date BETWEEN ? AND ?
             GROUP BY ip.date
             ORDER BY ip.date",
            [$userId, $startDate, $endDate]
        );

        if (empty($performance)) {
            return ['data' => [], 'start_value' => 0, 'end_value' => 0, 'change' => 0, 'change_percentage' => 0];
        }

        $startValue = $performance[0]['portfolio_value'];
        $endValue = end($performance)['portfolio_value'];
        $change = $endValue - $startValue;
        $changePercentage = $startValue > 0 ? ($change / $startValue) * 100 : 0;

        return [
            'data' => $performance,
            'start_value' => $startValue,
            'end_value' => $endValue,
            'change' => $change,
            'change_percentage' => $changePercentage,
            'period' => $period
        ];
    }

    /**
     * Get investment transactions with pagination and enhanced performance data
     */
    public function getTransactions(int $userId, array $filters = [], int $page = 1, int $limit = 20): array {
        $offset = ($page - 1) * $limit;

        $query = "SELECT it.*, i.symbol, i.name, i.asset_type, ia.name as account_name,
                         i.purchase_price as investment_purchase_price,
                         i.current_price as investment_current_price
                  FROM investment_transactions it
                  JOIN investments i ON it.investment_id = i.id
                  LEFT JOIN investment_accounts ia ON it.account_id = ia.id
                  WHERE it.user_id = ?";
        $params = [$userId];

        // Apply filters
        if (!empty($filters['investment_id'])) {
            $query .= " AND it.investment_id = ?";
            $params[] = $filters['investment_id'];
        }

        if (!empty($filters['transaction_type'])) {
            $query .= " AND it.transaction_type = ?";
            $params[] = $filters['transaction_type'];
        }

        if (!empty($filters['start_date'])) {
            $query .= " AND it.transaction_date >= ?";
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $query .= " AND it.transaction_date <= ?";
            $params[] = $filters['end_date'];
        }

        if (!empty($filters['account_id'])) {
            $query .= " AND it.account_id = ?";
            $params[] = $filters['account_id'];
        }

        if (!empty($filters['asset_type'])) {
            $query .= " AND i.asset_type = ?";
            $params[] = $filters['asset_type'];
        }

        if (!empty($filters['min_amount'])) {
            $query .= " AND ABS(it.total_amount) >= ?";
            $params[] = $filters['min_amount'];
        }

        if (!empty($filters['max_amount'])) {
            $query .= " AND ABS(it.total_amount) <= ?";
            $params[] = $filters['max_amount'];
        }

        // Search functionality
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $query .= " AND (i.symbol LIKE ? OR i.name LIKE ? OR it.notes LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $query .= " ORDER BY it.transaction_date DESC, it.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $transactions = $this->db->query($query, $params);

        // Get total count
        $countQuery = "SELECT COUNT(*) as total_count
                       FROM investment_transactions it
                       JOIN investments i ON it.investment_id = i.id
                       LEFT JOIN investment_accounts ia ON it.account_id = ia.id
                       WHERE it.user_id = ?";
        $countParams = [$userId];

        if (!empty($filters['investment_id'])) {
            $countQuery .= " AND it.investment_id = ?";
            $countParams[] = $filters['investment_id'];
        }

        if (!empty($filters['transaction_type'])) {
            $countQuery .= " AND it.transaction_type = ?";
            $countParams[] = $filters['transaction_type'];
        }

        if (!empty($filters['start_date'])) {
            $countQuery .= " AND it.transaction_date >= ?";
            $countParams[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $countQuery .= " AND it.transaction_date <= ?";
            $countParams[] = $filters['end_date'];
        }

        if (!empty($filters['account_id'])) {
            $countQuery .= " AND it.account_id = ?";
            $countParams[] = $filters['account_id'];
        }

        if (!empty($filters['asset_type'])) {
            $countQuery .= " AND i.asset_type = ?";
            $countParams[] = $filters['asset_type'];
        }

        if (!empty($filters['min_amount'])) {
            $countQuery .= " AND ABS(it.total_amount) >= ?";
            $countParams[] = $filters['min_amount'];
        }

        if (!empty($filters['max_amount'])) {
            $countQuery .= " AND ABS(it.total_amount) <= ?";
            $countParams[] = $filters['max_amount'];
        }

        // Search functionality for count
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $countQuery .= " AND (i.symbol LIKE ? OR i.name LIKE ? OR it.notes LIKE ?)";
            $countParams[] = $searchTerm;
            $countParams[] = $searchTerm;
            $countParams[] = $searchTerm;
        }

        $countResult = $this->db->queryOne($countQuery, $countParams);
        $totalCount = $countResult['total_count'] ?? 0;

        // Enhance transactions with performance data
        foreach ($transactions as &$transaction) {
            $transaction = $this->calculateTransactionPerformance($transaction);
        }

        return [
            'transactions' => $transactions,
            'total_count' => $totalCount,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($totalCount / $limit),
            'filters_applied' => $filters
        ];
    }

    /**
     * Update investment prices (bulk update)
     */
    public function updatePrices(int $userId, array $priceUpdates): array {
        $updated = 0;
        $errors = [];

        foreach ($priceUpdates as $update) {
            try {
                // Verify investment belongs to user
                $investment = $this->db->queryOne(
                    "SELECT id FROM investments WHERE id = ? AND user_id = ?",
                    [$update['investment_id'], $userId]
                );

                if (!$investment) {
                    $errors[] = "Investment {$update['investment_id']} not found";
                    continue;
                }

                // Update current price
                $this->db->update('investments', [
                    'current_price' => $update['price'],
                    'last_updated' => date('Y-m-d'),
                    'updated_at' => date('Y-m-d H:i:s')
                ], ['id' => $update['investment_id']]);

                // Insert price history
                $this->db->insert('investment_prices', [
                    'investment_id' => $update['investment_id'],
                    'price' => $update['price'],
                    'currency' => $update['currency'] ?? 'CZK',
                    'date' => date('Y-m-d'),
                    'source' => $update['source'] ?? 'manual'
                ]);

                $updated++;
            } catch (\Exception $e) {
                $errors[] = "Failed to update price for investment {$update['investment_id']}: " . $e->getMessage();
            }
        }

        return ['updated' => $updated, 'errors' => $errors];
    }

    /**
     * Get diversification analysis
     */
    public function getDiversificationAnalysis(int $userId): array {
        $portfolio = $this->getPortfolioSummary($userId);

        $analysis = [
            'asset_type_diversity' => count($portfolio['asset_allocation']),
            'account_diversity' => count($portfolio['account_allocation']),
            'largest_holding' => 0,
            'largest_holding_percentage' => 0,
            'concentration_warnings' => []
        ];

        // Find largest holding
        foreach ($portfolio['investments'] as $investment) {
            $percentage = $portfolio['total_value'] > 0 ? ($investment['current_value'] / $portfolio['total_value']) * 100 : 0;
            if ($percentage > $analysis['largest_holding_percentage']) {
                $analysis['largest_holding_percentage'] = $percentage;
                $analysis['largest_holding'] = $investment['id'];
            }
        }

        // Concentration warnings
        if ($analysis['largest_holding_percentage'] > 50) {
            $analysis['concentration_warnings'][] = 'High concentration in single investment';
        }

        if ($analysis['asset_type_diversity'] < 3) {
            $analysis['concentration_warnings'][] = 'Limited asset type diversification';
        }

        if ($analysis['account_diversity'] < 2) {
            $analysis['concentration_warnings'][] = 'Limited account diversification';
        }

        return $analysis;
    }

    /**
     * Helper method to get start date for period
     */
    private function getStartDateForPeriod(string $period): string {
        $now = time();

        switch ($period) {
            case '1W': return date('Y-m-d', strtotime('-1 week', $now));
            case '1M': return date('Y-m-d', strtotime('-1 month', $now));
            case '3M': return date('Y-m-d', strtotime('-3 months', $now));
            case '6M': return date('Y-m-d', strtotime('-6 months', $now));
            case '1Y': return date('Y-m-d', strtotime('-1 year', $now));
            case '2Y': return date('Y-m-d', strtotime('-2 years', $now));
            case '5Y': return date('Y-m-d', strtotime('-5 years', $now));
            default: return date('Y-m-d', strtotime('-1 year', $now));
        }
    }
    /**
     * Calculate transaction performance metrics
     */
    private function calculateTransactionPerformance(array $transaction): array {
        $performance = $transaction;

        // Calculate holding period for buy transactions
        if ($transaction['transaction_type'] === 'buy') {
            $performance['holding_period_days'] = $this->calculateHoldingPeriod($transaction['investment_id'], $transaction['transaction_date']);
            $performance['current_value'] = $transaction['quantity'] * $transaction['investment_current_price'];
            $performance['unrealized_gain'] = $performance['current_value'] - $transaction['total_amount'];
            $performance['unrealized_gain_percentage'] = $transaction['total_amount'] > 0
                ? ($performance['unrealized_gain'] / $transaction['total_amount']) * 100
                : 0;
        }

        // Calculate realized gain/loss for sell transactions
        if ($transaction['transaction_type'] === 'sell') {
            $performance['realized_gain'] = $this->calculateRealizedGain($transaction);
            $performance['realized_gain_percentage'] = $transaction['total_amount'] > 0
                ? ($performance['realized_gain'] / abs($transaction['total_amount'])) * 100
                : 0;
        }

        // Calculate dividend yield for dividend transactions
        if ($transaction['transaction_type'] === 'dividend') {
            $performance['dividend_yield'] = $this->calculateDividendYield($transaction);
        }

        // Calculate interest rate for interest transactions
        if ($transaction['transaction_type'] === 'interest') {
            $performance['interest_rate'] = $this->calculateInterestRate($transaction);
        }

        // Calculate cost basis for stock splits
        if ($transaction['transaction_type'] === 'stock_split') {
            $performance['adjusted_cost_basis'] = $this->calculateAdjustedCostBasis($transaction);
        }

        return $performance;
    }

    /**
     * Calculate holding period in days
     */
    private function calculateHoldingPeriod(int $investmentId, string $buyDate): int {
        $now = new \DateTime();
        $buyDateTime = new \DateTime($buyDate);
        $interval = $now->diff($buyDateTime);
        return $interval->days;
    }

    /**
     * Calculate realized gain/loss for sell transactions
     */
    private function calculateRealizedGain(array $transaction): float {
        // Get average cost basis at time of sale
        $costBasis = $this->getCostBasisAtDate($transaction['investment_id'], $transaction['transaction_date']);
        $saleValue = $transaction['quantity'] * $transaction['price'];
        return $saleValue - ($transaction['quantity'] * $costBasis);
    }

    /**
     * Get cost basis at specific date
     */
    private function getCostBasisAtDate(int $investmentId, string $date): float {
        // Get all buy transactions before the sell date
        $buyTransactions = $this->db->query(
            "SELECT * FROM investment_transactions
             WHERE investment_id = ? AND transaction_type = 'buy'
             AND transaction_date <= ?
             ORDER BY transaction_date ASC",
            [$investmentId, $date]
        );

        $totalCost = 0;
        $totalQuantity = 0;

        foreach ($buyTransactions as $buy) {
            $totalCost += $buy['total_amount'];
            $totalQuantity += $buy['quantity'];
        }

        return $totalQuantity > 0 ? $totalCost / $totalQuantity : 0;
    }

    /**
     * Calculate dividend yield
     */
    private function calculateDividendYield(array $transaction): float {
        $investment = $this->db->queryOne(
            "SELECT current_price FROM investments WHERE id = ?",
            [$transaction['investment_id']]
        );

        if (!$investment || $investment['current_price'] <= 0) {
            return 0;
        }

        // Annualize dividend if needed (assuming quarterly)
        $annualDividend = $transaction['total_amount'] * 4;
        return ($annualDividend / $investment['current_price']) * 100;
    }

    /**
     * Calculate interest rate for interest transactions
     */
    private function calculateInterestRate(array $transaction): float {
        // This would need more context about the principal amount
        // For now, return a placeholder
        return 0.0;
    }

    /**
     * Calculate adjusted cost basis for stock splits
     */
    private function calculateAdjustedCostBasis(array $transaction): float {
        // Get split ratio from transaction notes or calculate from quantity change
        // This is a simplified implementation
        $investment = $this->db->queryOne(
            "SELECT purchase_price FROM investments WHERE id = ?",
            [$transaction['investment_id']]
        );

        if (!$investment) return 0;

        // Assume split ratio is stored in notes or can be calculated
        // For now, return current purchase price
        return $investment['purchase_price'];
    }

    /**
     * Get transaction summary with performance metrics
     */
    public function getTransactionSummary(int $userId, array $filters = []): array {
        $transactions = $this->getTransactions($userId, $filters, 1, 1000)['transactions'];

        $summary = [
            'total_transactions' => count($transactions),
            'transaction_types' => [],
            'total_volume' => 0,
            'total_fees' => 0,
            'total_taxes' => 0,
            'performance_metrics' => [
                'total_realized_gain' => 0,
                'total_unrealized_gain' => 0,
                'total_dividends' => 0,
                'total_interest' => 0
            ],
            'period' => $filters['start_date'] ?? 'all'
        ];

        foreach ($transactions as $transaction) {
            // Count by type
            $type = $transaction['transaction_type'];
            if (!isset($summary['transaction_types'][$type])) {
                $summary['transaction_types'][$type] = 0;
            }
            $summary['transaction_types'][$type]++;

            // Accumulate totals
            $summary['total_volume'] += abs($transaction['total_amount']);
            $summary['total_fees'] += $transaction['fees'] ?? 0;
            $summary['total_taxes'] += $transaction['taxes'] ?? 0;

            // Performance metrics
            if (isset($transaction['realized_gain'])) {
                $summary['performance_metrics']['total_realized_gain'] += $transaction['realized_gain'];
            }
            if (isset($transaction['unrealized_gain'])) {
                $summary['performance_metrics']['total_unrealized_gain'] += $transaction['unrealized_gain'];
            }
            if ($transaction['transaction_type'] === 'dividend') {
                $summary['performance_metrics']['total_dividends'] += $transaction['total_amount'];
            }
            if ($transaction['transaction_type'] === 'interest') {
                $summary['performance_metrics']['total_interest'] += $transaction['total_amount'];
            }
        }

        return $summary;
    }

    /**
     * Export transactions to CSV
     */
    public function exportTransactions(int $userId, array $filters = []): string {
        $transactions = $this->getTransactions($userId, $filters, 1, 10000)['transactions'];

        $csv = "Date,Type,Symbol,Name,Quantity,Price,Total Amount,Currency,Fees,Taxes,Account,Notes\n";

        foreach ($transactions as $transaction) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $transaction['transaction_date'],
                $transaction['transaction_type'],
                $transaction['symbol'],
                '"' . str_replace('"', '""', $transaction['name']) . '"',
                $transaction['quantity'] ?? '',
                $transaction['price'] ?? '',
                $transaction['total_amount'],
                $transaction['currency'],
                $transaction['fees'] ?? 0,
                $transaction['taxes'] ?? 0,
                '"' . str_replace('"', '""', $transaction['account_name'] ?? '') . '"',
                '"' . str_replace('"', '""', $transaction['notes'] ?? '') . '"'
            );
        }

        return $csv;
    }

    /**
     * Get current asset allocation
     */
    public function getCurrentAssetAllocation(int $userId): array {
        // Get all investments with current values
        $investments = $this->db->query(
            "SELECT i.asset_type, i.quantity, i.current_price
             FROM investments i
             WHERE i.user_id = ? AND i.is_active = 1",
            [$userId]
        );

        $totalValue = 0;
        $assetAllocation = [];

        // Calculate total portfolio value and asset allocation
        foreach ($investments as $investment) {
            $currentValue = $investment['quantity'] * $investment['current_price'];
            $totalValue += $currentValue;

            $assetType = $investment['asset_type'];
            if (!isset($assetAllocation[$assetType])) {
                $assetAllocation[$assetType] = ['value' => 0, 'percentage' => 0];
            }
            $assetAllocation[$assetType]['value'] += $currentValue;
        }

        // Calculate percentages
        foreach ($assetAllocation as &$allocation) {
            $allocation['percentage'] = $totalValue > 0 ? ($allocation['value'] / $totalValue) * 100 : 0;
        }

        return [
            'total_value' => $totalValue,
            'asset_allocation' => $assetAllocation,
            'allocation_count' => count($assetAllocation)
        ];
    }

    /**
     * Get ideal allocation by risk profile
     */
    public function getIdealAllocationByRisk(string $riskProfile): array {
        $profiles = [
            'conservative' => [
                'bonds' => 60,
                'stocks' => 30,
                'cash' => 10
            ],
            'moderate' => [
                'bonds' => 40,
                'stocks' => 50,
                'cash' => 10
            ],
            'aggressive' => [
                'bonds' => 20,
                'stocks' => 70,
                'cash' => 10
            ]
        ];

        if (!isset($profiles[$riskProfile])) {
            throw new \InvalidArgumentException("Invalid risk profile: {$riskProfile}. Must be conservative, moderate, or aggressive.");
        }

        return [
            'risk_profile' => $riskProfile,
            'ideal_allocation' => $profiles[$riskProfile]
        ];
    }

    /**
     * Get rebalancing advice
     */
    public function getRebalancingAdvice(int $userId, string $riskProfile): array {
        $current = $this->getCurrentAssetAllocation($userId);
        $ideal = $this->getIdealAllocationByRisk($riskProfile);

        $advice = [
            'current_allocation' => $current['asset_allocation'],
            'ideal_allocation' => $ideal['ideal_allocation'],
            'total_value' => $current['total_value'],
            'recommendations' => []
        ];

        $totalValue = $current['total_value'];

        foreach ($ideal['ideal_allocation'] as $assetType => $idealPercentage) {
            $currentPercentage = $current['asset_allocation'][$assetType]['percentage'] ?? 0;
            $idealValue = ($idealPercentage / 100) * $totalValue;
            $currentValue = ($currentPercentage / 100) * $totalValue;
            $difference = $idealValue - $currentValue;

            if (abs($difference) > 100) { // Only recommend if difference is significant (> $100)
                $action = $difference > 0 ? 'buy' : 'sell';
                $amount = abs($difference);

                $advice['recommendations'][] = [
                    'asset_type' => $assetType,
                    'action' => $action,
                    'amount' => $amount,
                    'current_percentage' => round($currentPercentage, 2),
                    'ideal_percentage' => $idealPercentage,
                    'difference_percentage' => round($idealPercentage - $currentPercentage, 2)
                ];
            }
        }

        return $advice;
    }

    /**
     * Compare current vs ideal allocations
     */
    public function compareAllocations(int $userId, string $riskProfile): array {
        $current = $this->getCurrentAssetAllocation($userId);
        $ideal = $this->getIdealAllocationByRisk($riskProfile);

        $comparison = [
            'total_value' => $current['total_value'],
            'comparison' => []
        ];

        foreach ($ideal['ideal_allocation'] as $assetType => $idealPercentage) {
            $currentPercentage = $current['asset_allocation'][$assetType]['percentage'] ?? 0;
            $currentValue = $current['asset_allocation'][$assetType]['value'] ?? 0;
            $idealValue = ($idealPercentage / 100) * $current['total_value'];

            $comparison['comparison'][$assetType] = [
                'current_percentage' => round($currentPercentage, 2),
                'ideal_percentage' => $idealPercentage,
                'difference_percentage' => round($idealPercentage - $currentPercentage, 2),
                'current_value' => $currentValue,
                'ideal_value' => $idealValue,
                'difference_value' => $idealValue - $currentValue
            ];
        }

        return $comparison;
    }
}