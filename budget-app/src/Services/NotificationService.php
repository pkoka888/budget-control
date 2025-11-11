<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

class NotificationService {
    private Database $db;
    private array $notificationTypes;

    public function __construct(Database $db) {
        $this->db = $db;
        $this->notificationTypes = [
            'goal_milestone' => 'Goal milestone achieved',
            'budget_alert' => 'Budget limit exceeded',
            'weekly_digest' => 'Weekly financial summary',
            'scenario_insight' => 'New scenario planning insight',
            'career_opportunity' => 'Career opportunity available',
            'crisis_alert' => 'Financial crisis alert',
            'savings_reminder' => 'Savings goal reminder',
            'investment_alert' => 'Investment opportunity alert'
        ];
    }

    /**
     * Create a notification
     */
    public function createNotification(int $userId, string $type, string $title, string $message, array $metadata = []): int {
        return $this->db->insert('notifications', [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'metadata' => json_encode($metadata),
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get user notifications
     */
    public function getNotifications(int $userId, int $limit = 50, int $offset = 0): array {
        $notifications = $this->db->query(
            "SELECT * FROM notifications
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?",
            [$userId, $limit, $offset]
        );

        // Decode metadata
        foreach ($notifications as &$notification) {
            $notification['metadata'] = json_decode($notification['metadata'], true) ?: [];
        }

        return $notifications;
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $userId, int $notificationId): bool {
        return $this->db->update('notifications',
            ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')],
            ['id' => $notificationId, 'user_id' => $userId]
        );
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(int $userId): bool {
        return $this->db->update('notifications',
            ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')],
            ['user_id' => $userId, 'is_read' => 0]
        );
    }

    /**
     * Delete notification
     */
    public function deleteNotification(int $userId, int $notificationId): bool {
        return $this->db->delete('notifications', ['id' => $notificationId, 'user_id' => $userId]);
    }

    /**
     * Generate weekly digest
     */
    public function generateWeeklyDigest(int $userId): array {
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $weekEnd = date('Y-m-d', strtotime('sunday this week'));

        $digest = [
            'period' => ['start' => $weekStart, 'end' => $weekEnd],
            'financial_summary' => $this->getFinancialSummary($userId, $weekStart, $weekEnd),
            'goal_progress' => $this->getGoalProgress($userId),
            'budget_performance' => $this->getBudgetPerformance($userId, $weekStart, $weekEnd),
            'insights' => $this->getWeeklyInsights($userId),
            'recommendations' => $this->getWeeklyRecommendations($userId)
        ];

        return $digest;
    }

    /**
     * Send weekly digest email
     */
    public function sendWeeklyDigest(int $userId): bool {
        $digest = $this->generateWeeklyDigest($userId);

        // Create notification
        $title = 'Weekly Financial Digest - ' . date('M j, Y');
        $message = $this->formatDigestMessage($digest);

        $this->createNotification($userId, 'weekly_digest', $title, $message, [
            'digest_data' => $digest,
            'email_sent' => true
        ]);

        // TODO: Implement actual email sending
        // $this->sendEmail($userId, $title, $this->formatDigestEmail($digest));

        return true;
    }

    /**
     * Create contextual action notification
     */
    public function createContextualAction(int $userId, string $contextType, array $contextData): int {
        $action = $this->generateContextualAction($contextType, $contextData);

        return $this->createNotification(
            $userId,
            'contextual_action',
            $action['title'],
            $action['message'],
            [
                'context_type' => $contextType,
                'context_data' => $contextData,
                'action_type' => $action['action_type'],
                'action_data' => $action['action_data']
            ]
        );
    }

    /**
     * Get notification preferences
     */
    public function getNotificationPreferences(int $userId): array {
        $preferences = [];

        foreach ($this->notificationTypes as $type => $description) {
            $setting = $this->db->queryOne(
                "SELECT setting_value FROM user_settings
                 WHERE user_id = ? AND category = 'notifications' AND setting_key = ?",
                [$userId, $type . '_enabled']
            );

            $preferences[$type] = [
                'enabled' => $setting ? (bool)$setting['setting_value'] : true,
                'description' => $description
            ];
        }

        return $preferences;
    }

    /**
     * Update notification preferences
     */
    public function updateNotificationPreferences(int $userId, array $preferences): bool {
        foreach ($preferences as $type => $enabled) {
            $this->db->insert('user_settings', [
                'user_id' => $userId,
                'category' => 'notifications',
                'setting_key' => $type . '_enabled',
                'setting_value' => $enabled ? '1' : '0',
                'updated_at' => date('Y-m-d H:i:s')
            ], true); // Upsert
        }

        return true;
    }

    /**
     * Helper methods
     */
    private function getFinancialSummary(int $userId, string $startDate, string $endDate): array {
        // Get transactions for the week
        $transactions = $this->db->query(
            "SELECT type, amount FROM transactions
             WHERE user_id = ? AND date BETWEEN ? AND ?",
            [$userId, $startDate, $endDate]
        );

        $income = 0;
        $expenses = 0;

        foreach ($transactions as $transaction) {
            if ($transaction['type'] === 'income') {
                $income += $transaction['amount'];
            } else {
                $expenses += $transaction['amount'];
            }
        }

        return [
            'total_income' => $income,
            'total_expenses' => $expenses,
            'net_savings' => $income - $expenses,
            'transaction_count' => count($transactions)
        ];
    }

    private function getGoalProgress(int $userId): array {
        $goals = $this->db->query(
            "SELECT id, name, current_amount, target_amount FROM goals
             WHERE user_id = ? AND is_active = 1",
            [$userId]
        );

        $progress = [];
        foreach ($goals as $goal) {
            $percentage = $goal['target_amount'] > 0 ? ($goal['current_amount'] / $goal['target_amount']) * 100 : 0;
            $progress[] = [
                'name' => $goal['name'],
                'progress_percentage' => round($percentage, 1),
                'current_amount' => $goal['current_amount'],
                'target_amount' => $goal['target_amount']
            ];
        }

        return $progress;
    }

    private function getBudgetPerformance(int $userId, string $startDate, string $endDate): array {
        $budgets = $this->db->query(
            "SELECT b.category_id, b.amount as budget_amount, c.name as category_name,
                    COALESCE(SUM(t.amount), 0) as spent
             FROM budgets b
             JOIN categories c ON b.category_id = c.id
             LEFT JOIN transactions t ON t.category_id = b.category_id
                AND t.user_id = b.user_id
                AND t.type = 'expense'
                AND t.date BETWEEN ? AND ?
             WHERE b.user_id = ? AND b.month = ?
             GROUP BY b.category_id, b.amount, c.name",
            [$startDate, $endDate, $userId, date('Y-m')]
        );

        $performance = [];
        foreach ($budgets as $budget) {
            $percentage = $budget['budget_amount'] > 0 ? ($budget['spent'] / $budget['budget_amount']) * 100 : 0;
            $performance[] = [
                'category' => $budget['category_name'],
                'budget_amount' => $budget['budget_amount'],
                'spent' => $budget['spent'],
                'percentage_used' => round($percentage, 1),
                'status' => $percentage > 100 ? 'over_budget' : ($percentage > 80 ? 'warning' : 'good')
            ];
        }

        return $performance;
    }

    private function getWeeklyInsights(int $userId): array {
        $insights = [];

        // Check for unusual spending patterns
        $avgDailySpending = $this->getAverageDailySpending($userId);
        $yesterdaySpending = $this->getYesterdaySpending($userId);

        if ($yesterdaySpending > $avgDailySpending * 2) {
            $insights[] = [
                'type' => 'spending_alert',
                'message' => 'Yesterday\'s spending was significantly higher than your daily average.',
                'severity' => 'medium'
            ];
        }

        // Check goal progress
        $goals = $this->db->query(
            "SELECT name, target_date FROM goals
             WHERE user_id = ? AND is_active = 1 AND target_date IS NOT NULL",
            [$userId]
        );

        foreach ($goals as $goal) {
            $daysLeft = floor((strtotime($goal['target_date']) - time()) / (60 * 60 * 24));
            if ($daysLeft <= 7 && $daysLeft > 0) {
                $insights[] = [
                    'type' => 'goal_deadline',
                    'message' => "Goal '{$goal['name']}' is due in {$daysLeft} days.",
                    'severity' => 'high'
                ];
            }
        }

        return $insights;
    }

    private function getWeeklyRecommendations(int $userId): array {
        $recommendations = [];

        // Budget recommendations
        $budgetPerformance = $this->getBudgetPerformance($userId, date('Y-m-d', strtotime('-7 days')), date('Y-m-d'));
        foreach ($budgetPerformance as $budget) {
            if ($budget['status'] === 'over_budget') {
                $recommendations[] = [
                    'type' => 'budget_control',
                    'title' => 'Review ' . $budget['category'] . ' spending',
                    'message' => "You've exceeded your {$budget['category']} budget. Consider reviewing expenses.",
                    'action_type' => 'view_budget',
                    'action_data' => ['category_id' => $budget['category_id']]
                ];
            }
        }

        // Savings recommendations
        $goalProgress = $this->getGoalProgress($userId);
        foreach ($goalProgress as $goal) {
            if ($goal['progress_percentage'] < 50) {
                $recommendations[] = [
                    'type' => 'savings_boost',
                    'title' => 'Accelerate ' . $goal['name'],
                    'message' => "You're {$goal['progress_percentage']}% toward your goal. Consider increasing monthly contributions.",
                    'action_type' => 'view_goal',
                    'action_data' => ['goal_name' => $goal['name']]
                ];
            }
        }

        return $recommendations;
    }

    private function generateContextualAction(string $contextType, array $contextData): array {
        switch ($contextType) {
            case 'high_expense':
                return [
                    'title' => 'Unusual Expense Detected',
                    'message' => "Large expense of {$contextData['amount']} CZK in {$contextData['category']}. Review transaction?",
                    'action_type' => 'review_transaction',
                    'action_data' => ['transaction_id' => $contextData['transaction_id']]
                ];

            case 'goal_milestone':
                return [
                    'title' => 'Goal Milestone Reached!',
                    'message' => "Congratulations! You've reached {$contextData['percentage']}% of your {$contextData['goal_name']} goal.",
                    'action_type' => 'celebrate_milestone',
                    'action_data' => ['goal_id' => $contextData['goal_id']]
                ];

            case 'budget_warning':
                return [
                    'title' => 'Budget Alert',
                    'message' => "You've used {$contextData['percentage']}% of your {$contextData['category']} budget.",
                    'action_type' => 'adjust_budget',
                    'action_data' => ['category_id' => $contextData['category_id']]
                ];

            case 'career_opportunity':
                return [
                    'title' => 'Career Opportunity',
                    'message' => "New {$contextData['role']} position available with salary up to {$contextData['salary']} CZK.",
                    'action_type' => 'explore_career',
                    'action_data' => ['role' => $contextData['role'], 'region' => $contextData['region']]
                ];

            default:
                return [
                    'title' => 'Action Required',
                    'message' => 'Please review this notification.',
                    'action_type' => 'general_action',
                    'action_data' => $contextData
                ];
        }
    }

    private function formatDigestMessage(array $digest): string {
        $message = "📊 Weekly Financial Summary\n\n";

        $summary = $digest['financial_summary'];
        $message .= "💰 Income: " . number_format($summary['total_income'], 0, ',', ' ') . " CZK\n";
        $message .= "💸 Expenses: " . number_format($summary['total_expenses'], 0, ',', ' ') . " CZK\n";
        $message .= "📈 Net Savings: " . number_format($summary['net_savings'], 0, ',', ' ') . " CZK\n\n";

        if (!empty($digest['insights'])) {
            $message .= "🔍 Key Insights:\n";
            foreach ($digest['insights'] as $insight) {
                $message .= "• " . $insight['message'] . "\n";
            }
            $message .= "\n";
        }

        if (!empty($digest['recommendations'])) {
            $message .= "💡 Recommendations:\n";
            foreach ($digest['recommendations'] as $rec) {
                $message .= "• " . $rec['message'] . "\n";
            }
        }

        return $message;
    }

    private function getAverageDailySpending(int $userId): float {
        $result = $this->db->queryOne(
            "SELECT AVG(daily_total) as avg_daily
             FROM (
                 SELECT DATE(date) as day, SUM(amount) as daily_total
                 FROM transactions
                 WHERE user_id = ? AND type = 'expense'
                 AND date >= DATE('now', '-30 days')
                 GROUP BY DATE(date)
             )",
            [$userId]
        );

        return $result['avg_daily'] ?? 0;
    }

    private function getYesterdaySpending(int $userId): float {
        $result = $this->db->queryOne(
            "SELECT COALESCE(SUM(amount), 0) as yesterday_total
             FROM transactions
             WHERE user_id = ? AND type = 'expense'
             AND DATE(date) = DATE('now', '-1 day')",
            [$userId]
        );

        return $result['yesterday_total'] ?? 0;
    }
}