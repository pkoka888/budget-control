<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

/**
 * AI Insights Service
 * 
 * Provides intelligent financial insights, predictions, and recommendations
 */
class AIInsightsService {
    private Database $db;
    private array $config;
    
    public function __construct(Database $db, array $config = []) {
        $this->db = $db;
        $this->config = array_merge([
            'openai_api_key' => $_ENV['OPENAI_API_KEY'] ?? '',
            'min_confidence' => 0.6,
            'lookback_months' => 6
        ], $config);
    }
    
    // ===== INSIGHT GENERATION =====
    
    public function generateInsights(int $userId): array {
        $insights = [];
        
        // Detect spending patterns
        $insights = array_merge($insights, $this->detectSpendingPatterns($userId));
        
        // Find savings opportunities
        $insights = array_merge($insights, $this->findSavingsOpportunities($userId));
        
        // Check budget adherence
        $insights = array_merge($insights, $this->analyzeBudgetPerformance($userId));
        
        // Detect anomalies
        $insights = array_merge($insights, $this->detectAnomalies($userId));
        
        // Save insights
        foreach ($insights as $insight) {
            $this->saveInsight($userId, $insight);
        }
        
        return $insights;
    }
    
    public function getUserInsights(int $userId, ?string $type = null, bool $unreadOnly = false): array {
        $query = "SELECT * FROM ai_insights WHERE user_id = ? AND is_dismissed = 0";
        $params = [$userId];
        
        if ($type) {
            $query .= " AND insight_type = ?";
            $params[] = $type;
        }
        
        if ($unreadOnly) {
            $query .= " AND is_read = 0";
        }
        
        $query .= " ORDER BY created_at DESC LIMIT 50";
        
        return $this->db->query($query, $params);
    }
    
    private function saveInsight(int $userId, array $insight): int {
        $this->db->query(
            "INSERT INTO ai_insights 
             (user_id, insight_type, category, title, description, severity, confidence_score, data_json)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $userId,
                $insight['type'],
                $insight['category'] ?? null,
                $insight['title'],
                $insight['description'],
                $insight['severity'] ?? 'info',
                $insight['confidence'] ?? 0.8,
                isset($insight['data']) ? json_encode($insight['data']) : null
            ]
        );
        
        return $this->db->lastInsertId();
    }
    
    // ===== SPENDING PATTERN DETECTION =====
    
    public function detectSpendingPatterns(int $userId): array {
        $insights = [];
        $months = $this->config['lookback_months'];
        
        // Get transactions
        $transactions = $this->db->query(
            "SELECT category, SUM(amount) as total, COUNT(*) as count,
                    strftime('%Y-%m', date) as month
             FROM transactions 
             WHERE user_id = ? AND type = 'expense' 
                   AND date >= DATE('now', '-{$months} months')
             GROUP BY category, month
             ORDER BY month, total DESC",
            [$userId]
        );
        
        // Analyze each category
        $categoryData = [];
        foreach ($transactions as $tx) {
            if (!isset($categoryData[$tx['category']])) {
                $categoryData[$tx['category']] = [];
            }
            $categoryData[$tx['category']][] = $tx['total'];
        }
        
        foreach ($categoryData as $category => $amounts) {
            if (count($amounts) < 3) continue;
            
            $avg = array_sum($amounts) / count($amounts);
            $recent = end($amounts);
            $trend = ($recent - $avg) / $avg * 100;
            
            // Trending up significantly
            if ($trend > 20) {
                $insights[] = [
                    'type' => 'spending_pattern',
                    'category' => $category,
                    'title' => "Spending Increasing in {$category}",
                    'description' => sprintf(
                        "Your spending in %s has increased by %.1f%% compared to your average. Recent: %.2f CZK, Average: %.2f CZK",
                        $category, $trend, $recent, $avg
                    ),
                    'severity' => $trend > 50 ? 'warning' : 'info',
                    'confidence' => 0.85,
                    'data' => ['trend' => $trend, 'recent' => $recent, 'average' => $avg]
                ];
            }
            
            // Consistent spending (good pattern)
            $stdDev = $this->calculateStdDev($amounts);
            $coefficient = $avg > 0 ? ($stdDev / $avg) : 0;
            
            if ($coefficient < 0.15 && count($amounts) >= 4) {
                $insights[] = [
                    'type' => 'spending_pattern',
                    'category' => $category,
                    'title' => "Consistent Spending in {$category}",
                    'description' => sprintf(
                        "Your %s spending is very consistent at around %.2f CZK per month. Great budget control!",
                        $category, $avg
                    ),
                    'severity' => 'positive',
                    'confidence' => 0.9
                ];
            }
        }
        
        return $insights;
    }
    
    // ===== SAVINGS OPPORTUNITIES =====
    
    public function findSavingsOpportunities(int $userId): array {
        $insights = [];
        
        // Detect unused subscriptions
        $subscriptions = $this->db->query(
            "SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active'",
            [$userId]
        );
        
        foreach ($subscriptions as $sub) {
            if ($sub['usage_tracking'] && $sub['last_used']) {
                $daysSinceUse = (strtotime('now') - strtotime($sub['last_used'])) / 86400;
                
                if ($daysSinceUse > 60) {
                    $insights[] = [
                        'type' => 'saving_opportunity',
                        'title' => "Unused Subscription: {$sub['service_name']}",
                        'description' => sprintf(
                            "You haven't used %s in %d days. Consider canceling to save %.2f CZK per month.",
                            $sub['service_name'], (int)$daysSinceUse, $sub['amount']
                        ),
                        'severity' => 'warning',
                        'confidence' => 0.9,
                        'data' => ['savings_monthly' => $sub['amount'], 'savings_annual' => $sub['amount'] * 12]
                    ];
                }
            }
        }
        
        // Detect duplicate categories
        $duplicates = $this->detectDuplicateSubscriptions($userId);
        foreach ($duplicates as $dup) {
            $insights[] = $dup;
        }
        
        return $insights;
    }
    
    private function detectDuplicateSubscriptions(int $userId): array {
        $insights = [];
        
        // Group subscriptions by category
        $subs = $this->db->query(
            "SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active' ORDER BY category",
            [$userId]
        );
        
        $byCategory = [];
        foreach ($subs as $sub) {
            $byCategory[$sub['category']][] = $sub;
        }
        
        foreach ($byCategory as $category => $subscriptions) {
            if (count($subscriptions) > 1) {
                $total = array_sum(array_column($subscriptions, 'amount'));
                $names = implode(', ', array_column($subscriptions, 'service_name'));
                
                $insights[] = [
                    'type' => 'saving_opportunity',
                    'category' => $category,
                    'title' => "Multiple {$category} Subscriptions",
                    'description' => sprintf(
                        "You have %d %s subscriptions (%s) costing %.2f CZK/month. Consider consolidating.",
                        count($subscriptions), $category, $names, $total
                    ),
                    'severity' => 'info',
                    'confidence' => 0.75
                ];
            }
        }
        
        return $insights;
    }
    
    // ===== BUDGET ANALYSIS =====
    
    public function analyzeBudgetPerformance(int $userId): array {
        $insights = [];
        $currentMonth = date('Y-m');
        
        $budgets = $this->db->query(
            "SELECT b.*, c.name as category_name,
                    COALESCE(SUM(t.amount), 0) as spent
             FROM budgets b
             LEFT JOIN categories c ON c.id = b.category_id
             LEFT JOIN transactions t ON t.category_id = b.category_id 
                  AND t.user_id = b.user_id
                  AND t.type = 'expense'
                  AND strftime('%Y-%m', t.date) = ?
             WHERE b.user_id = ? AND b.period = 'monthly'
             GROUP BY b.id",
            [$currentMonth, $userId]
        );
        
        foreach ($budgets as $budget) {
            $percentage = ($budget['spent'] / $budget['amount']) * 100;
            
            // Over budget
            if ($percentage > 100) {
                $insights[] = [
                    'type' => 'budget_alert',
                    'category' => $budget['category_name'],
                    'title' => "Budget Exceeded: {$budget['category_name']}",
                    'description' => sprintf(
                        "You've spent %.2f CZK (%.1f%%) of your %.2f CZK budget for %s.",
                        $budget['spent'], $percentage, $budget['amount'], $budget['category_name']
                    ),
                    'severity' => 'critical',
                    'confidence' => 1.0
                ];
            }
            // Close to limit
            elseif ($percentage > 80) {
                $insights[] = [
                    'type' => 'budget_alert',
                    'category' => $budget['category_name'],
                    'title' => "Approaching Budget Limit: {$budget['category_name']}",
                    'description' => sprintf(
                        "You've used %.1f%% of your %s budget. %.2f CZK remaining.",
                        $percentage, $budget['category_name'], $budget['amount'] - $budget['spent']
                    ),
                    'severity' => 'warning',
                    'confidence' => 1.0
                ];
            }
            // Under-utilizing
            elseif ($percentage < 50 && date('d') > 25) {
                $insights[] = [
                    'type' => 'budget_alert',
                    'category' => $budget['category_name'],
                    'title' => "Budget Underutilized: {$budget['category_name']}",
                    'description' => sprintf(
                        "You've only used %.1f%% of your %s budget. Consider adjusting for next month.",
                        $percentage, $budget['category_name']
                    ),
                    'severity' => 'info',
                    'confidence' => 0.7
                ];
            }
        }
        
        return $insights;
    }
    
    // ===== ANOMALY DETECTION =====
    
    public function detectAnomalies(int $userId): array {
        $insights = [];
        $months = 3;
        
        // Get recent transactions
        $transactions = $this->db->query(
            "SELECT * FROM transactions 
             WHERE user_id = ? AND type = 'expense' 
                   AND date >= DATE('now', '-{$months} months')
             ORDER BY date DESC",
            [$userId]
        );
        
        // Calculate statistics by category
        $stats = [];
        foreach ($transactions as $tx) {
            if (!isset($stats[$tx['category_id']])) {
                $stats[$tx['category_id']] = ['amounts' => [], 'category' => $tx['category_id']];
            }
            $stats[$tx['category_id']]['amounts'][] = $tx['amount'];
        }
        
        // Check recent transactions against norms
        foreach ($transactions as $tx) {
            if (!isset($stats[$tx['category_id']])) continue;
            
            $amounts = $stats[$tx['category_id']]['amounts'];
            if (count($amounts) < 5) continue;
            
            $mean = array_sum($amounts) / count($amounts);
            $stdDev = $this->calculateStdDev($amounts);
            
            // Z-score calculation
            $zScore = $stdDev > 0 ? abs(($tx['amount'] - $mean) / $stdDev) : 0;
            
            // Unusual transaction (z-score > 2)
            if ($zScore > 2) {
                $anomalyScore = min($zScore / 5, 1.0); // Normalize to 0-1
                
                $this->db->query(
                    "INSERT OR IGNORE INTO transaction_anomalies 
                     (user_id, transaction_id, anomaly_type, anomaly_score, expected_range_min, expected_range_max, description)
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [
                        $userId,
                        $tx['id'],
                        'unusual_amount',
                        $anomalyScore,
                        $mean - $stdDev,
                        $mean + $stdDev,
                        sprintf("Amount %.2f CZK is unusual for this category (avg: %.2f CZK)", $tx['amount'], $mean)
                    ]
                );
                
                if ($anomalyScore > 0.6) {
                    $insights[] = [
                        'type' => 'anomaly',
                        'title' => "Unusual Transaction Detected",
                        'description' => sprintf(
                            "Transaction of %.2f CZK on %s is significantly higher than your typical %.2f CZK for this category.",
                            $tx['amount'], $tx['date'], $mean
                        ),
                        'severity' => $anomalyScore > 0.8 ? 'warning' : 'info',
                        'confidence' => $anomalyScore
                    ];
                }
            }
        }
        
        return $insights;
    }
    
    // ===== PREDICTIONS =====
    
    public function predictNextMonthSpending(int $userId, string $category): float {
        $transactions = $this->db->query(
            "SELECT SUM(amount) as total, strftime('%Y-%m', date) as month
             FROM transactions 
             WHERE user_id = ? AND category = ? AND type = 'expense'
                   AND date >= DATE('now', '-6 months')
             GROUP BY month
             ORDER BY month",
            [$userId, $category]
        );
        
        if (empty($transactions)) return 0;
        
        $amounts = array_column($transactions, 'total');
        
        // Simple moving average with trend
        $recent3 = array_slice($amounts, -3);
        $older3 = array_slice($amounts, -6, 3);
        
        $recentAvg = array_sum($recent3) / count($recent3);
        $olderAvg = count($older3) > 0 ? array_sum($older3) / count($older3) : $recentAvg;
        
        $trend = $recentAvg - $olderAvg;
        
        // Predict: recent average + trend
        return max(0, $recentAvg + $trend);
    }
    
    // ===== CHAT ASSISTANT =====
    
    public function chat(int $userId, string $message, string $sessionId): string {
        // Save user message
        $this->db->query(
            "INSERT INTO ai_chat_history (user_id, session_id, role, message) VALUES (?, ?, ?, ?)",
            [$userId, $sessionId, 'user', $message]
        );
        
        // Get financial context
        $context = $this->getUserFinancialContext($userId);
        
        // Generate response (simplified - in production, use OpenAI API)
        $response = $this->generateChatResponse($message, $context);
        
        // Save assistant response
        $this->db->query(
            "INSERT INTO ai_chat_history (user_id, session_id, role, message, context_json) VALUES (?, ?, ?, ?, ?)",
            [$userId, $sessionId, 'assistant', $response, json_encode($context)]
        );
        
        return $response;
    }
    
    private function getUserFinancialContext(int $userId): array {
        $context = [];
        
        // Current balance
        $accounts = $this->db->query(
            "SELECT SUM(balance) as total FROM accounts WHERE user_id = ?",
            [$userId]
        );
        $context['total_balance'] = $accounts[0]['total'] ?? 0;
        
        // This month spending
        $spending = $this->db->query(
            "SELECT SUM(amount) as total FROM transactions 
             WHERE user_id = ? AND type = 'expense' 
                   AND strftime('%Y-%m', date) = strftime('%Y-%m', 'now')",
            [$userId]
        );
        $context['monthly_spending'] = $spending[0]['total'] ?? 0;
        
        return $context;
    }
    
    private function generateChatResponse(string $message, array $context): string {
        // Simplified response generation
        $lower = strtolower($message);
        
        if (strpos($lower, 'balance') !== false) {
            return sprintf("Your current total balance is %.2f CZK across all accounts.", $context['total_balance']);
        }
        
        if (strpos($lower, 'spending') !== false || strpos($lower, 'spent') !== false) {
            return sprintf("You've spent %.2f CZK so far this month.", $context['monthly_spending']);
        }
        
        return "I can help you with your finances. Try asking about your balance, spending, or budgets.";
    }
    
    // ===== UTILITIES =====
    
    private function calculateStdDev(array $values): float {
        $count = count($values);
        if ($count < 2) return 0;
        
        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values)) / $count;
        
        return sqrt($variance);
    }
}
