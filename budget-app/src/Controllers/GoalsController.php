<?php
namespace BudgetApp\Controllers;

use BudgetApp\Services\GoalService;

class GoalsController extends BaseController {
    private GoalService $goalService;

    public function __construct($app) {
        parent::__construct($app);
        $this->goalService = new GoalService($this->db);
    }

    public function dashboard(array $params = []): void {
        $userId = $this->getUserId();
        $dashboard = $this->goalService->getGoalDashboard($userId);

        echo $this->app->render('goals/dashboard', ['dashboard' => $dashboard]);
    }

    public function list(array $params = []): void {
        $userId = $this->getUserId();

        $goals = $this->db->query(
            "SELECT * FROM goals WHERE user_id = ? AND is_active = 1 ORDER BY priority DESC, target_date ASC",
            [$userId]
        );

        // Calculate progress for each goal using GoalService
        foreach ($goals as &$goal) {
            $goal['progress'] = $this->goalService->calculateGoalProgress($goal);
        }

        echo $this->app->render('goals/list', ['goals' => $goals]);
    }

    public function create(array $params = []): void {
        $userId = $this->getUserId();

        $goalData = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'target_amount' => (float)($_POST['target_amount'] ?? 0),
            'current_amount' => (float)($_POST['current_amount'] ?? 0),
            'target_date' => $_POST['target_date'] ?? null,
            'goal_type' => $_POST['goal_type'] ?? 'savings',
            'category' => $_POST['category'] ?? 'general',
            'priority' => $_POST['priority'] ?? 'medium'
        ];

        // Validate goal data
        $errors = $this->goalService->validateGoalData($goalData);
        if (!empty($errors)) {
            $this->json(['errors' => $errors], 400);
            return;
        }

        $goalId = $this->db->insert('goals', [
            'user_id' => $userId,
            'name' => $goalData['name'],
            'description' => $goalData['description'],
            'goal_type' => $goalData['goal_type'],
            'target_amount' => $goalData['target_amount'],
            'current_amount' => $goalData['current_amount'],
            'target_date' => $goalData['target_date'],
            'category' => $goalData['category'],
            'priority' => $goalData['priority'],
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $this->json(['id' => $goalId, 'message' => 'Goal created successfully']);
    }

    public function update(array $params = []): void {
        $goalId = $params['id'] ?? 0;
        $userId = $this->getUserId();

        $goal = $this->db->queryOne(
            "SELECT * FROM goals WHERE id = ? AND user_id = ?",
            [$goalId, $userId]
        );

        if (!$goal) {
            $this->json(['error' => 'Goal not found'], 404);
            return;
        }

        $updates = [];
        $allowedFields = ['name', 'description', 'target_amount', 'current_amount', 'target_date', 'goal_type', 'category', 'priority'];

        foreach ($allowedFields as $field) {
            if (isset($_POST[$field])) {
                $value = $_POST[$field];
                if (in_array($field, ['target_amount', 'current_amount'])) {
                    $value = (float)$value;
                }
                $updates[$field] = $value;
            }
        }

        if (!empty($updates)) {
            $updates['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update('goals', $updates, ['id' => $goalId]);
        }

        $this->json(['success' => true, 'message' => 'Goal updated successfully']);
    }

    public function delete(array $params = []): void {
        $goalId = $params['id'] ?? 0;
        $userId = $this->getUserId();

        $goal = $this->db->queryOne(
            "SELECT * FROM goals WHERE id = ? AND user_id = ?",
            [$goalId, $userId]
        );

        if (!$goal) {
            $this->json(['error' => 'Goal not found'], 404);
            return;
        }

        // Soft delete by setting is_active to 0
        $this->db->update('goals', ['is_active' => 0], ['id' => $goalId]);

        $this->json(['success' => true, 'message' => 'Goal deleted successfully']);
    }

    public function show(array $params = []): void {
        $goalId = $params['id'] ?? 0;
        $userId = $this->getUserId();

        $goal = $this->db->queryOne(
            "SELECT * FROM goals WHERE id = ? AND user_id = ? AND is_active = 1",
            [$goalId, $userId]
        );

        if (!$goal) {
            http_response_code(404);
            echo $this->app->render('404');
            return;
        }

        $goal['progress'] = $this->goalService->calculateGoalProgress($goal);
        $goal['milestones'] = $this->goalService->getGoalMilestones($goalId);

        // Calculate savings projections
        $monthlyContribution = (float)($this->getQueryParam('monthly_contribution', 0));
        if ($monthlyContribution > 0) {
            $goal['projection'] = $this->goalService->calculateSavingsProjection($goal, $monthlyContribution);
        }

        echo $this->app->render('goals/show', ['goal' => $goal]);
    }

    public function getMilestones(array $params = []): void {
        $goalId = $params['id'] ?? 0;
        $userId = $this->getUserId();

        // Verify goal ownership
        $goal = $this->db->queryOne(
            "SELECT id FROM goals WHERE id = ? AND user_id = ? AND is_active = 1",
            [$goalId, $userId]
        );

        if (!$goal) {
            $this->json(['error' => 'Goal not found'], 404);
            return;
        }

        $milestones = $this->goalService->getGoalMilestones($goalId);
        $this->json(['milestones' => $milestones]);
    }

    public function createMilestone(array $params = []): void {
        $goalId = $params['id'] ?? 0;
        $userId = $this->getUserId();

        // Verify goal ownership
        $goal = $this->db->queryOne(
            "SELECT id FROM goals WHERE id = ? AND user_id = ? AND is_active = 1",
            [$goalId, $userId]
        );

        if (!$goal) {
            $this->json(['error' => 'Goal not found'], 404);
            return;
        }

        $name = $_POST['name'] ?? '';
        $targetAmount = (float)($_POST['target_amount'] ?? 0);
        $targetDate = $_POST['target_date'] ?? null;

        if (empty($name) || $targetAmount <= 0) {
            $this->json(['error' => 'Milestone name and target amount are required'], 400);
            return;
        }

        $milestoneId = $this->goalService->createMilestone($goalId, $name, $targetAmount, $targetDate);

        $this->json(['id' => $milestoneId, 'message' => 'Milestone created successfully']);
    }

    public function updateMilestone(array $params = []): void {
        $milestoneId = $params['id'] ?? 0;
        $userId = $this->getUserId();

        // Verify milestone ownership through goal
        $milestone = $this->db->queryOne(
            "SELECT gm.* FROM goal_milestones gm
             JOIN goals g ON gm.goal_id = g.id
             WHERE gm.id = ? AND g.user_id = ?",
            [$milestoneId, $userId]
        );

        if (!$milestone) {
            $this->json(['error' => 'Milestone not found'], 404);
            return;
        }

        $isCompleted = isset($_POST['is_completed']) ? (bool)$_POST['is_completed'] : null;

        if ($isCompleted !== null) {
            $success = $this->goalService->updateMilestoneStatus($milestoneId, $isCompleted);
            if ($success) {
                $this->json(['success' => true, 'message' => 'Milestone updated successfully']);
            } else {
                $this->json(['error' => 'Failed to update milestone'], 500);
            }
        } else {
            $this->json(['error' => 'Invalid update parameters'], 400);
        }
    }

    public function getProjection(array $params = []): void {
        $goalId = $params['id'] ?? 0;
        $userId = $this->getUserId();
        $monthlyContribution = (float)($this->getQueryParam('monthly_contribution', 0));

        $goal = $this->db->queryOne(
            "SELECT * FROM goals WHERE id = ? AND user_id = ? AND is_active = 1",
            [$goalId, $userId]
        );

        if (!$goal) {
            $this->json(['error' => 'Goal not found'], 404);
            return;
        }

        if ($monthlyContribution <= 0) {
            $this->json(['error' => 'Monthly contribution must be greater than 0'], 400);
            return;
        }

        $projection = $this->goalService->calculateSavingsProjection($goal, $monthlyContribution);
        $this->json(['projection' => $projection]);
    }

    public function getProgressHistory(array $params = []): void {
        $goalId = $params['id'] ?? 0;
        $userId = $this->getUserId();

        // Verify goal ownership
        $goal = $this->db->queryOne(
            "SELECT id FROM goals WHERE id = ? AND user_id = ? AND is_active = 1",
            [$goalId, $userId]
        );

        if (!$goal) {
            $this->json(['error' => 'Goal not found'], 404);
            return;
        }

        $progressHistory = $this->goalService->getProgressHistory($goalId);
        $milestoneTimeline = $this->goalService->getMilestoneTimeline($goalId);

        $this->json([
            'progress_history' => $progressHistory,
            'milestone_timeline' => $milestoneTimeline
        ]);
    }

    public function getSavingsCalculation(array $params = []): void {
        $goalId = $params['id'] ?? 0;
        $userId = $this->getUserId();

        $goal = $this->db->queryOne(
            "SELECT * FROM goals WHERE id = ? AND user_id = ? AND is_active = 1",
            [$goalId, $userId]
        );

        if (!$goal) {
            $this->json(['error' => 'Goal not found'], 404);
            return;
        }

        $calculation = [];

        // Get savings scenarios
        $calculation['scenarios'] = $this->goalService->getSavingsScenarios($goal);

        // Calculate based on query parameters
        $months = (int)($this->getQueryParam('months', 0));
        $monthlyContribution = (float)($this->getQueryParam('monthly_contribution', 0));

        if ($months > 0) {
            $calculation['savings_needed'] = $this->goalService->calculateSavingsNeeded($goal, $months);
        }

        if ($monthlyContribution > 0) {
            $calculation['projected_completion_date'] = $this->goalService->projectCompletionDate($goal, $monthlyContribution);
        }

        $this->json(['calculation' => $calculation]);
    }
}
