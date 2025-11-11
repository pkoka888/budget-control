<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

class GoalService {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Calculate goal progress and projections
     */
    public function calculateGoalProgress(array $goal): array {
        $progress = [
            'percentage' => 0,
            'days_left' => 0,
            'months_left' => 0,
            'monthly_savings_needed' => 0,
            'projected_completion_date' => null,
            'is_on_track' => false,
            'status' => 'active'
        ];

        if ($goal['target_amount'] <= 0) {
            return $progress;
        }

        // Calculate progress percentage
        $progress['percentage'] = min(100, ($goal['current_amount'] / $goal['target_amount']) * 100);

        if (!empty($goal['target_date'])) {
            $targetTimestamp = strtotime($goal['target_date']);
            $now = time();
            $daysLeft = ($targetTimestamp - $now) / (60 * 60 * 24);

            $progress['days_left'] = max(0, (int)$daysLeft);
            $progress['months_left'] = max(0, $daysLeft / 30);

            // Calculate monthly savings needed
            if ($daysLeft > 0) {
                $remainingAmount = $goal['target_amount'] - $goal['current_amount'];
                $progress['monthly_savings_needed'] = $remainingAmount / max(1, $progress['months_left']);
            }

            // Check if goal is on track
            $progress['is_on_track'] = $this->isGoalOnTrack($goal, $progress);

            // Calculate projected completion date
            if ($progress['monthly_savings_needed'] > 0) {
                $monthsToComplete = ($goal['target_amount'] - $goal['current_amount']) / $progress['monthly_savings_needed'];
                $progress['projected_completion_date'] = date('Y-m-d', strtotime("+{$monthsToComplete} months"));
            }
        }

        // Determine status
        if ($progress['percentage'] >= 100) {
            $progress['status'] = 'completed';
        } elseif ($progress['days_left'] <= 0 && $progress['percentage'] < 100) {
            $progress['status'] = 'overdue';
        }

        return $progress;
    }

    /**
     * Check if goal is on track to meet target date
     */
    private function isGoalOnTrack(array $goal, array $progress): bool {
        if (empty($goal['target_date']) || $progress['months_left'] <= 0) {
            return true; // No deadline or already past deadline
        }

        $remainingAmount = $goal['target_amount'] - $goal['current_amount'];
        $requiredMonthly = $remainingAmount / $progress['months_left'];

        // Consider goal on track if current monthly savings rate can meet the requirement
        // This is a simplified check - in reality you'd want to analyze historical savings
        return $progress['monthly_savings_needed'] <= ($requiredMonthly * 1.2); // 20% buffer
    }

    /**
     * Get goal dashboard data
     */
    public function getGoalDashboard(int $userId): array {
        $goals = $this->db->query(
            "SELECT * FROM goals WHERE user_id = ? AND is_active = 1 ORDER BY priority DESC, target_date ASC",
            [$userId]
        );

        $dashboard = [
            'total_goals' => count($goals),
            'completed_goals' => 0,
            'active_goals' => 0,
            'overdue_goals' => 0,
            'total_target_amount' => 0,
            'total_current_amount' => 0,
            'goals_by_type' => [],
            'goals_by_priority' => [],
            'upcoming_deadlines' => []
        ];

        foreach ($goals as &$goal) {
            $progress = $this->calculateGoalProgress($goal);
            $goal['progress'] = $progress;

            $dashboard['total_target_amount'] += $goal['target_amount'];
            $dashboard['total_current_amount'] += $goal['current_amount'];

            // Count by status
            switch ($progress['status']) {
                case 'completed':
                    $dashboard['completed_goals']++;
                    break;
                case 'overdue':
                    $dashboard['overdue_goals']++;
                    break;
                default:
                    $dashboard['active_goals']++;
            }

            // Group by type
            $type = $goal['goal_type'];
            if (!isset($dashboard['goals_by_type'][$type])) {
                $dashboard['goals_by_type'][$type] = [];
            }
            $dashboard['goals_by_type'][$type][] = $goal;

            // Group by priority
            $priority = $goal['priority'];
            if (!isset($dashboard['goals_by_priority'][$priority])) {
                $dashboard['goals_by_priority'][$priority] = [];
            }
            $dashboard['goals_by_priority'][$priority][] = $goal;

            // Add to upcoming deadlines if within 30 days
            if ($progress['days_left'] > 0 && $progress['days_left'] <= 30) {
                $dashboard['upcoming_deadlines'][] = $goal;
            }
        }

        $dashboard['overall_progress'] = $dashboard['total_target_amount'] > 0
            ? ($dashboard['total_current_amount'] / $dashboard['total_target_amount']) * 100
            : 0;

        return $dashboard;
    }

    /**
     * Calculate savings projections for a goal
     */
    public function calculateSavingsProjection(array $goal, float $monthlyContribution): array {
        $projection = [
            'monthly_contribution' => $monthlyContribution,
            'months_to_complete' => 0,
            'completion_date' => null,
            'total_contributions' => 0,
            'monthly_breakdown' => []
        ];

        if ($monthlyContribution <= 0) {
            return $projection;
        }

        $remainingAmount = $goal['target_amount'] - $goal['current_amount'];
        $projection['months_to_complete'] = ceil($remainingAmount / $monthlyContribution);
        $projection['total_contributions'] = $remainingAmount;
        $projection['completion_date'] = date('Y-m-d', strtotime("+{$projection['months_to_complete']} months"));

        // Generate monthly breakdown
        $currentAmount = $goal['current_amount'];
        $currentDate = new \DateTime();

        for ($i = 1; $i <= min($projection['months_to_complete'], 12); $i++) { // Show max 12 months
            $currentAmount += $monthlyContribution;
            $currentDate->modify('+1 month');

            $projection['monthly_breakdown'][] = [
                'month' => $currentDate->format('Y-m'),
                'projected_amount' => min($currentAmount, $goal['target_amount']),
                'is_target_reached' => $currentAmount >= $goal['target_amount']
            ];

            if ($currentAmount >= $goal['target_amount']) {
                break;
            }
        }

        return $projection;
    }

    /**
     * Calculate required monthly savings to reach goal in specified months
     */
    public function calculateSavingsNeeded(array $goal, int $months): float {
        if ($months <= 0) {
            return 0;
        }

        $remainingAmount = $goal['target_amount'] - $goal['current_amount'];
        return $remainingAmount / $months;
    }

    /**
     * Calculate projected completion date based on monthly contribution
     */
    public function projectCompletionDate(array $goal, float $monthlyContribution): ?string {
        if ($monthlyContribution <= 0) {
            return null;
        }

        $remainingAmount = $goal['target_amount'] - $goal['current_amount'];
        if ($remainingAmount <= 0) {
            return date('Y-m-d'); // Already completed
        }

        $monthsToComplete = ceil($remainingAmount / $monthlyContribution);
        return date('Y-m-d', strtotime("+{$monthsToComplete} months"));
    }

    /**
     * Get savings scenarios with different monthly contribution rates
     */
    public function getSavingsScenarios(array $goal): array {
        $scenarios = [];
        $remainingAmount = $goal['target_amount'] - $goal['current_amount'];

        if ($remainingAmount <= 0) {
            return $scenarios;
        }

        // Define different scenario rates (as percentages of remaining amount per month)
        $rates = [0.01, 0.02, 0.03, 0.05, 0.08, 0.10]; // 1%, 2%, 3%, 5%, 8%, 10% of remaining

        foreach ($rates as $rate) {
            $monthlyContribution = $remainingAmount * $rate;
            $monthsToComplete = ceil($remainingAmount / $monthlyContribution);
            $completionDate = date('Y-m-d', strtotime("+{$monthsToComplete} months"));

            $scenarios[] = [
                'monthly_contribution' => round($monthlyContribution, 2),
                'months_to_complete' => $monthsToComplete,
                'completion_date' => $completionDate,
                'total_contributions' => $remainingAmount,
                'rate_percentage' => $rate * 100
            ];
        }

        return $scenarios;
    }

    /**
     * Get goal milestones
     */
    public function getGoalMilestones(int $goalId): array {
        return $this->db->query(
            "SELECT * FROM goal_milestones WHERE goal_id = ? ORDER BY target_date ASC",
            [$goalId]
        );
    }

    /**
     * Create a goal milestone
     */
    public function createMilestone(int $goalId, string $name, float $targetAmount, ?string $targetDate = null): int {
        return $this->db->insert('goal_milestones', [
            'goal_id' => $goalId,
            'name' => $name,
            'target_amount' => $targetAmount,
            'target_date' => $targetDate,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Update milestone completion status
     */
    public function updateMilestoneStatus(int $milestoneId, bool $isCompleted): bool {
        $updates = ['is_completed' => $isCompleted ? 1 : 0];

        if ($isCompleted) {
            $updates['completed_at'] = date('Y-m-d H:i:s');
        }

        return $this->db->update('goal_milestones', $updates, ['id' => $milestoneId]);
    }

    /**
     * Record a progress snapshot for a goal
     */
    public function recordProgressSnapshot(int $goalId, float $amount): bool {
        return $this->db->insert('goal_progress_history', [
            'goal_id' => $goalId,
            'amount' => $amount,
            'recorded_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get progress history for a goal
     */
    public function getProgressHistory(int $goalId): array {
        return $this->db->query(
            "SELECT * FROM goal_progress_history WHERE goal_id = ? ORDER BY recorded_at ASC",
            [$goalId]
        );
    }

    /**
     * Get milestone timeline (completed milestones with completion dates)
     */
    public function getMilestoneTimeline(int $goalId): array {
        return $this->db->query(
            "SELECT id, name, target_amount, completed_at
             FROM goal_milestones
             WHERE goal_id = ? AND is_completed = 1
             ORDER BY completed_at ASC",
            [$goalId]
        );
    }

    /**
     * Validate goal data
     */
    public function validateGoalData(array $data): array {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Goal name is required';
        }

        if (!isset($data['target_amount']) || $data['target_amount'] <= 0) {
            $errors['target_amount'] = 'Target amount must be greater than 0';
        }

        if (!empty($data['target_date']) && strtotime($data['target_date']) < time()) {
            $errors['target_date'] = 'Target date cannot be in the past';
        }

        $validTypes = ['savings', 'debt_payoff', 'investment', 'emergency_fund'];
        if (!empty($data['goal_type']) && !in_array($data['goal_type'], $validTypes)) {
            $errors['goal_type'] = 'Invalid goal type';
        }

        $validPriorities = ['low', 'medium', 'high'];
        if (!empty($data['priority']) && !in_array($data['priority'], $validPriorities)) {
            $errors['priority'] = 'Invalid priority level';
        }

        return $errors;
    }
}