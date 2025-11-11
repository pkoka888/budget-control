<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

class CrisisModeService {
    private Database $db;
    private AnalyticsWorker $analytics;

    // Default crisis thresholds (more aggressive than normal)
    private array $defaultThresholds = [
        'emergency_runway_min' => 1, // months
        'budget_alert_percentage' => 75, // percent of budget used
        'savings_rate_min' => 5, // percent
        'debt_to_income_max' => 40, // percent
        'expense_variance_max' => 25 // percent month-over-month change
    ];

    public function __construct(Database $db) {
        $this->db = $db;
        $this->analytics = new AnalyticsWorker($db);
    }

    /**
     * Activate crisis mode for user
     */
    public function activateCrisisMode(int $userId, array $customThresholds = []): array {
        $thresholds = array_merge($this->defaultThresholds, $customThresholds);

        $this->db->insert('crisis_mode_settings', [
            'user_id' => $userId,
            'is_active' => 1,
            'activated_at' => date('Y-m-d H:i:s'),
            'thresholds' => json_encode($thresholds),
            'notifications_enabled' => 1,
            'escalation_rules' => json_encode($this->getDefaultEscalationRules())
        ], true); // Upsert

        // Generate immediate crisis insights
        $insights = $this->generateCrisisInsights($userId, $thresholds);

        return [
            'activated' => true,
            'thresholds' => $thresholds,
            'immediate_actions' => $insights['immediate_actions'],
            'activated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Deactivate crisis mode
     */
    public function deactivateCrisisMode(int $userId): bool {
        $this->db->update('crisis_mode_settings',
            ['is_active' => 0],
            ['user_id' => $userId]
        );
        return true;
    }

    /**
     * Check if crisis mode should be triggered
     */
    public function shouldTriggerCrisisMode(int $userId): array {
        $settings = $this->getCrisisSettings($userId);
        if (!$settings || !$settings['notifications_enabled']) {
            return ['trigger' => false];
        }

        $thresholds = json_decode($settings['thresholds'], true);
        $analytics = $this->analytics->calculateBudgetHealth($userId);
        $runway = $this->analytics->calculateSavingsRunway($userId);

        $triggers = [];

        // Check emergency runway
        if ($runway['runway_months'] < ($thresholds['emergency_runway_min'] ?? 1)) {
            $triggers[] = [
                'type' => 'emergency_runway',
                'severity' => 'critical',
                'message' => "Emergency runway is only {$runway['runway_months']} months (minimum: {$thresholds['emergency_runway_min']})"
            ];
        }

        // Check budget compliance
        if ($analytics['budget_compliance'] > ($thresholds['budget_alert_percentage'] ?? 75)) {
            $triggers[] = [
                'type' => 'budget_overspend',
                'severity' => 'high',
                'message' => "Budget utilization at {$analytics['budget_compliance']}% (threshold: {$thresholds['budget_alert_percentage']}%)"
            ];
        }

        // Check savings rate
        if ($analytics['savings_rate'] < ($thresholds['savings_rate_min'] ?? 5)) {
            $triggers[] = [
                'type' => 'low_savings',
                'severity' => 'high',
                'message' => "Savings rate is only {$analytics['savings_rate']}% (minimum: {$thresholds['savings_rate_min']}%)"
            ];
        }

        return [
            'trigger' => !empty($triggers),
            'triggers' => $triggers,
            'severity' => $this->calculateOverallSeverity($triggers)
        ];
    }

    /**
     * Generate crisis-specific insights
     */
    public function generateCrisisInsights(int $userId, array $thresholds = null): array {
        if (!$thresholds) {
            $settings = $this->getCrisisSettings($userId);
            $thresholds = $settings ? json_decode($settings['thresholds'], true) : $this->defaultThresholds;
        }

        $analytics = $this->analytics->calculateBudgetHealth($userId);
        $runway = $this->analytics->calculateSavingsRunway($userId);
        $debt = $this->analytics->calculateDebtTracking($userId);

        $immediateActions = [];
        $shortTermGoals = [];
        $monitoringPoints = [];

        // Emergency cash preservation
        if ($runway['runway_months'] < 2) {
            $immediateActions[] = [
                'priority' => 'critical',
                'action' => 'Stop all non-essential subscriptions immediately',
                'impact' => 'Save ' . $this->estimateSubscriptionSavings($userId) . ' CZK/month'
            ];

            $immediateActions[] = [
                'priority' => 'critical',
                'action' => 'Negotiate payment plans with all creditors',
                'impact' => 'Prevent default and maintain credit'
            ];
        }

        // Expense reduction protocol
        if ($analytics['budget_compliance'] > 80) {
            $immediateActions[] = [
                'priority' => 'high',
                'action' => 'Cut discretionary spending by 50%',
                'impact' => 'Free up cash for essentials'
            ];
        }

        // Income acceleration
        $immediateActions[] = [
            'priority' => 'high',
            'action' => 'Activate all available side income streams',
            'impact' => 'Immediate cash injection within 24-48 hours'
        ];

        // Debt management
        if ($debt['total_debt'] > 0) {
            $shortTermGoals[] = [
                'goal' => 'Pay minimums on all debts while focusing on highest interest',
                'timeline' => 'Ongoing',
                'target' => 'Zero missed payments'
            ];
        }

        // Monitoring setup
        $monitoringPoints = [
            'Daily cash position checks',
            'Weekly expense reviews',
            'Bi-weekly income opportunity assessments',
            'Immediate alerts for runway < 2 weeks'
        ];

        return [
            'immediate_actions' => $immediateActions,
            'short_term_goals' => $shortTermGoals,
            'monitoring_points' => $monitoringPoints,
            'crisis_severity' => $this->calculateCrisisSeverity($runway, $analytics),
            'estimated_recovery_time' => $this->estimateRecoveryTime($runway, $analytics)
        ];
    }

    /**
     * Get crisis mode settings
     */
    public function getCrisisSettings(int $userId): ?array {
        return $this->db->queryOne(
            "SELECT * FROM crisis_mode_settings WHERE user_id = ?",
            [$userId]
        );
    }

    /**
     * Update crisis thresholds
     */
    public function updateThresholds(int $userId, array $newThresholds): bool {
        $current = $this->getCrisisSettings($userId);
        if (!$current) return false;

        $updatedThresholds = array_merge(
            json_decode($current['thresholds'], true),
            $newThresholds
        );

        $this->db->update('crisis_mode_settings',
            ['thresholds' => json_encode($updatedThresholds)],
            ['user_id' => $userId]
        );

        return true;
    }

    /**
     * Get crisis mode status
     */
    public function getCrisisStatus(int $userId): array {
        $settings = $this->getCrisisSettings($userId);
        $triggerCheck = $this->shouldTriggerCrisisMode($userId);

        return [
            'is_active' => $settings && $settings['is_active'],
            'activated_at' => $settings['activated_at'] ?? null,
            'should_trigger' => $triggerCheck['trigger'],
            'current_triggers' => $triggerCheck['triggers'],
            'severity' => $triggerCheck['severity'],
            'thresholds' => $settings ? json_decode($settings['thresholds'], true) : null
        ];
    }

    /**
     * Calculate overall severity from triggers
     */
    private function calculateOverallSeverity(array $triggers): string {
        $severityLevels = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];
        $maxSeverity = 0;

        foreach ($triggers as $trigger) {
            $severity = $severityLevels[$trigger['severity']] ?? 1;
            $maxSeverity = max($maxSeverity, $severity);
        }

        $severityMap = [1 => 'low', 2 => 'medium', 3 => 'high', 4 => 'critical'];
        return $severityMap[$maxSeverity] ?? 'low';
    }

    /**
     * Calculate crisis severity score
     */
    private function calculateCrisisSeverity(array $runway, array $analytics): string {
        $score = 0;

        // Runway score (0-40 points)
        if ($runway['runway_months'] < 1) $score += 40;
        elseif ($runway['runway_months'] < 2) $score += 30;
        elseif ($runway['runway_months'] < 3) $score += 20;
        elseif ($runway['runway_months'] < 6) $score += 10;

        // Budget score (0-30 points)
        if ($analytics['budget_compliance'] > 90) $score += 30;
        elseif ($analytics['budget_compliance'] > 80) $score += 20;
        elseif ($analytics['budget_compliance'] > 70) $score += 10;

        // Savings score (0-30 points)
        if ($analytics['savings_rate'] < 5) $score += 30;
        elseif ($analytics['savings_rate'] < 10) $score += 20;
        elseif ($analytics['savings_rate'] < 15) $score += 10;

        if ($score >= 70) return 'critical';
        if ($score >= 40) return 'high';
        if ($score >= 20) return 'medium';
        return 'low';
    }

    /**
     * Estimate recovery time
     */
    private function estimateRecoveryTime(array $runway, array $analytics): string {
        $severity = $this->calculateCrisisSeverity($runway, $analytics);

        switch ($severity) {
            case 'critical': return '3-6 months';
            case 'high': return '2-4 months';
            case 'medium': return '1-3 months';
            default: return '2-4 weeks';
        }
    }

    /**
     * Estimate subscription savings
     */
    private function estimateSubscriptionSavings(int $userId): float {
        // Simple estimation based on recurring transactions
        $subscriptions = $this->db->query(
            "SELECT SUM(amount) as total FROM recurring_transactions
             WHERE user_id = ? AND type = 'expense' AND amount > 100",
            [$userId]
        );

        return $subscriptions[0]['total'] ?? 0;
    }

    /**
     * Get default escalation rules
     */
    private function getDefaultEscalationRules(): array {
        return [
            'email_frequency' => 'daily',
            'push_notifications' => true,
            'escalate_after_days' => 3,
            'emergency_contacts' => false,
            'auto_freeze_accounts' => false
        ];
    }
}