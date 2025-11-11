<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

class BudgetAlertService {
    private Database $db;

    // Default alert thresholds (percentages)
    private const DEFAULT_THRESHOLDS = [50, 75, 90, 100];

    // Alert types
    private const ALERT_TYPES = [
        'percentage_threshold',
        'amount_threshold',
        'time_based'
    ];

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Generate alerts for all active budgets for a user
     */
    public function generateAlerts(int $userId): array {
        $alerts = [];

        // Get all active budgets for the user
        $budgets = $this->db->query(
            "SELECT b.*, c.name as category_name FROM budgets b
             LEFT JOIN categories c ON b.category_id = c.id
             WHERE b.user_id = ? AND b.is_active = 1",
            [$userId]
        );

        foreach ($budgets as $budget) {
            $budgetAlerts = $this->checkBudgetAlerts($userId, $budget);
            $alerts = array_merge($alerts, $budgetAlerts);
        }

        return $alerts;
    }

    /**
     * Check alerts for a specific budget
     */
    public function checkBudgetAlerts(int $userId, array $budget): array {
        $alerts = [];

        // Calculate current spending for this budget
        $currentMonth = date('Y-m');
        $spent = $this->calculateBudgetSpending($budget['category_id'], $currentMonth);
        $budgetLimit = $budget['amount'];
        $percentage = $budgetLimit > 0 ? ($spent / $budgetLimit) * 100 : 0;

        // Check percentage-based alerts
        foreach (self::DEFAULT_THRESHOLDS as $threshold) {
            if ($percentage >= $threshold) {
                $alert = $this->createOrUpdateAlert($userId, $budget, 'percentage_threshold', $threshold, $spent, $percentage);
                if ($alert) {
                    $alerts[] = $alert;
                }
            }
        }

        // Check time-based alerts (weekly and monthly reminders)
        $timeAlerts = $this->checkTimeBasedAlerts($userId, $budget, $spent, $percentage);
        $alerts = array_merge($alerts, $timeAlerts);

        return $alerts;
    }

    /**
     * Calculate total spending for a budget category in a given month
     */
    private function calculateBudgetSpending(int $categoryId, string $month): float {
        $result = $this->db->queryOne(
            "SELECT COALESCE(SUM(ABS(amount)), 0) as total FROM transactions
             WHERE category_id = ? AND type = 'expense' AND SUBSTR(date, 1, 7) = ?",
            [$categoryId, $month]
        );

        return (float)($result['total'] ?? 0);
    }

    /**
     * Create or update an alert if it doesn't already exist
     */
    private function createOrUpdateAlert(int $userId, array $budget, string $alertType, float $threshold, float $currentAmount, float $currentPercentage): ?array {
        // Check if alert already exists and is active
        $existingAlert = $this->db->queryOne(
            "SELECT id, status FROM budget_alerts
             WHERE user_id = ? AND budget_id = ? AND alert_type = ? AND threshold_value = ?
             AND DATE(triggered_at) = DATE('now')",
            [$userId, $budget['id'], $alertType, $threshold]
        );

        if ($existingAlert && $existingAlert['status'] === 'active') {
            // Alert already exists and is active, return it
            return $this->getAlertById($existingAlert['id']);
        }

        // Create new alert
        $message = $this->generateAlertMessage($alertType, $threshold, $currentAmount, $currentPercentage, $budget);

        $alertId = $this->db->insert('budget_alerts', [
            'user_id' => $userId,
            'budget_id' => $budget['id'],
            'alert_type' => $alertType,
            'threshold_value' => $threshold,
            'current_value' => $currentAmount,
            'status' => 'active',
            'message' => $message,
            'triggered_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $this->getAlertById($alertId);
    }

    /**
     * Generate alert message based on type and values
     */
    private function generateAlertMessage(string $alertType, float $threshold, float $currentAmount, float $currentPercentage, array $budget): string {
        $categoryName = $budget['category_name'] ?? 'Unknown Category';
        $budgetLimit = $budget['amount'];

        switch ($alertType) {
            case 'percentage_threshold':
                return sprintf(
                    'Budget alert: You have spent %.1f%% (%.2f %s) of your %s budget limit (%.2f %s)',
                    $currentPercentage,
                    $currentAmount,
                    'CZK', // Assuming CZK currency, could be made dynamic
                    $categoryName,
                    $budgetLimit,
                    'CZK'
                );

            case 'amount_threshold':
                return sprintf(
                    'Budget alert: You have spent %.2f %s of your %s budget, reaching the threshold of %.2f %s',
                    $currentAmount,
                    'CZK',
                    $categoryName,
                    $threshold,
                    'CZK'
                );

            case 'time_based':
                return sprintf(
                    'Budget reminder: You have spent %.2f %s (%.1f%%) of your %s budget this month',
                    $currentAmount,
                    'CZK',
                    $currentPercentage,
                    $categoryName
                );

            default:
                return 'Budget alert triggered';
        }
    }

    /**
     * Check time-based alerts (weekly and monthly reminders)
     */
    private function checkTimeBasedAlerts(int $userId, array $budget, float $spent, float $percentage): array {
        $alerts = [];
        $currentDate = date('Y-m-d');
        $currentMonth = date('Y-m');

        // Weekly reminder (every 7 days)
        $lastWeekAlert = $this->db->queryOne(
            "SELECT id FROM budget_alerts
             WHERE user_id = ? AND budget_id = ? AND alert_type = 'time_based'
             AND threshold_value = 7 AND triggered_at >= date('now', '-7 days')",
            [$userId, $budget['id']]
        );

        if (!$lastWeekAlert && $spent > 0) {
            $alert = $this->createOrUpdateAlert($userId, $budget, 'time_based', 7, $spent, $percentage);
            if ($alert) {
                $alerts[] = $alert;
            }
        }

        // Monthly reminder (on the 15th of each month)
        $currentDay = (int)date('d');
        if ($currentDay === 15) {
            $monthlyAlert = $this->db->queryOne(
                "SELECT id FROM budget_alerts
                 WHERE user_id = ? AND budget_id = ? AND alert_type = 'time_based'
                 AND threshold_value = 30 AND DATE(triggered_at) = ?",
                [$userId, $budget['id'], $currentDate]
            );

            if (!$monthlyAlert && $spent > 0) {
                $alert = $this->createOrUpdateAlert($userId, $budget, 'time_based', 30, $spent, $percentage);
                if ($alert) {
                    $alerts[] = $alert;
                }
            }
        }

        return $alerts;
    }

    /**
     * Get alert by ID
     */
    private function getAlertById(int $alertId): ?array {
        return $this->db->queryOne(
            "SELECT ba.*, b.month, c.name as category_name
             FROM budget_alerts ba
             LEFT JOIN budgets b ON ba.budget_id = b.id
             LEFT JOIN categories c ON b.category_id = c.id
             WHERE ba.id = ?",
            [$alertId]
        );
    }

    /**
     * Get all alerts for a user
     */
    public function getAlerts(int $userId, string $status = null): array {
        $params = [$userId];
        $whereClause = "WHERE user_id = ?";

        if ($status) {
            $whereClause .= " AND status = ?";
            $params[] = $status;
        }

        return $this->db->query(
            "SELECT ba.*, b.month, c.name as category_name
             FROM budget_alerts ba
             LEFT JOIN budgets b ON ba.budget_id = b.id
             LEFT JOIN categories c ON b.category_id = c.id
             {$whereClause}
             ORDER BY ba.triggered_at DESC",
            $params
        );
    }

    /**
     * Acknowledge an alert
     */
    public function acknowledgeAlert(int $userId, int $alertId): bool {
        $alert = $this->db->queryOne(
            "SELECT id FROM budget_alerts WHERE id = ? AND user_id = ?",
            [$alertId, $userId]
        );

        if (!$alert) {
            return false;
        }

        $this->db->update('budget_alerts', [
            'status' => 'acknowledged',
            'acknowledged_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $alertId]);

        return true;
    }

    /**
     * Dismiss an alert
     */
    public function dismissAlert(int $userId, int $alertId): bool {
        $alert = $this->db->queryOne(
            "SELECT id FROM budget_alerts WHERE id = ? AND user_id = ?",
            [$alertId, $userId]
        );

        if (!$alert) {
            return false;
        }

        $this->db->update('budget_alerts', [
            'status' => 'dismissed',
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $alertId]);

        return true;
    }

    /**
     * Get alert statistics for a user
     */
    public function getAlertStats(int $userId): array {
        $stats = $this->db->queryOne(
            "SELECT
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_count,
                COUNT(CASE WHEN status = 'acknowledged' THEN 1 END) as acknowledged_count,
                COUNT(CASE WHEN status = 'dismissed' THEN 1 END) as dismissed_count,
                COUNT(*) as total_count,
                MAX(triggered_at) as last_alert_date
             FROM budget_alerts
             WHERE user_id = ?",
            [$userId]
        );

        return $stats ?: [
            'active_count' => 0,
            'acknowledged_count' => 0,
            'dismissed_count' => 0,
            'total_count' => 0,
            'last_alert_date' => null
        ];
    }

    /**
     * Clean up old dismissed alerts (older than 30 days)
     */
    public function cleanupOldAlerts(int $userId, int $daysOld = 30): int {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));

        return $this->db->execute(
            "DELETE FROM budget_alerts
             WHERE user_id = ? AND status = 'dismissed' AND triggered_at < ?",
            [$userId, $cutoffDate]
        );
    }
}