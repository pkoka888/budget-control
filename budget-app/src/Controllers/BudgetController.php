<?php
namespace BudgetApp\Controllers;

class BudgetController extends BaseController {
    public function list(array $params = []): void {
        $userId = $this->getUserId();
        $month = $this->getQueryParam('month', date('Y-m'));

        $budgets = $this->db->query(
            "SELECT b.*, c.name as category_name FROM budgets b
             LEFT JOIN categories c ON b.category_id = c.id
             WHERE b.user_id = ? AND b.month = ?
             ORDER BY c.name",
            [$userId, $month]
        );

        // Add spending info for each budget
        foreach ($budgets as &$budget) {
            $spent = $this->db->queryOne(
                "SELECT COALESCE(SUM(ABS(amount)), 0) as total FROM transactions
                 WHERE category_id = ? AND type = 'expense' AND SUBSTR(date, 1, 7) = ?",
                [$budget['category_id'], $month]
            );
            $budget['spent'] = $spent['total'] ?? 0;
            $budget['remaining'] = $budget['limit'] - $budget['spent'];
            $budget['percentage'] = $budget['limit'] > 0 ? ($budget['spent'] / $budget['limit']) * 100 : 0;
        }

        $categories = $this->db->query(
            "SELECT id, name FROM categories WHERE user_id = ? OR user_id IS NULL ORDER BY name",
            [$userId]
        );

        echo $this->app->render('budgets/list', [
            'budgets' => $budgets,
            'categories' => $categories,
            'month' => $month
        ]);
    }

    public function create(array $params = []): void {
        $userId = $this->getUserId();

        $categoryId = (int)$_POST['category_id'];
        $limit = (float)$_POST['limit'];
        $month = $_POST['month'] ?? date('Y-m');

        // Check if budget already exists for this category and month
        $existing = $this->db->queryOne(
            "SELECT id FROM budgets WHERE user_id = ? AND category_id = ? AND month = ?",
            [$userId, $categoryId, $month]
        );

        if ($existing) {
            $this->json(['error' => 'Rozpočet pro tuto kategorii v tomto měsíci již existuje'], 400);
            return;
        }

        $budgetId = $this->db->insert('budgets', [
            'user_id' => $userId,
            'category_id' => $categoryId,
            'month' => $month,
            'limit' => $limit,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $this->json(['id' => $budgetId]);
    }

    public function update(array $params = []): void {
        $budgetId = $params['id'] ?? 0;
        $userId = $this->getUserId();

        $budget = $this->db->queryOne(
            "SELECT * FROM budgets WHERE id = ? AND user_id = ?",
            [$budgetId, $userId]
        );

        if (!$budget) {
            http_response_code(404);
            return;
        }

        $updates = [];
        if (isset($_POST['limit'])) $updates['limit'] = (float)$_POST['limit'];

        $this->db->update('budgets', $updates, ['id' => $budgetId]);

        $this->json(['success' => true]);
    }

    public function delete(array $params = []): void {
        $budgetId = $params['id'] ?? 0;
        $userId = $this->getUserId();

        $budget = $this->db->queryOne(
            "SELECT * FROM budgets WHERE id = ? AND user_id = ?",
            [$budgetId, $userId]
        );

        if (!$budget) {
            http_response_code(404);
            return;
        }

        $this->db->delete('budgets', ['id' => $budgetId]);

        $this->json(['success' => true]);
    }

    /**
     * Get budget alerts for the current user
     */
    public function getAlerts(array $params = []): void {
        $userId = $this->getUserId();
        $status = $this->getQueryParam('status'); // active, acknowledged, dismissed, or null for all

        $alertService = new BudgetAlertService($this->db);
        $alerts = $alertService->getAlerts($userId, $status);

        $this->json(['alerts' => $alerts]);
    }

    /**
     * Acknowledge a budget alert
     */
    public function acknowledgeAlert(array $params = []): void {
        $alertId = (int)($params['id'] ?? 0);
        $userId = $this->getUserId();

        if (!$alertId) {
            $this->json(['error' => 'Alert ID is required'], 400);
            return;
        }

        $alertService = new BudgetAlertService($this->db);
        $success = $alertService->acknowledgeAlert($userId, $alertId);

        if ($success) {
            $this->json(['success' => true]);
        } else {
            $this->json(['error' => 'Alert not found or access denied'], 404);
        }
    }

    /**
     * Dismiss a budget alert
     */
    public function dismissAlert(array $params = []): void {
        $alertId = (int)($params['id'] ?? 0);
        $userId = $this->getUserId();

        if (!$alertId) {
            $this->json(['error' => 'Alert ID is required'], 400);
            return;
        }

        $alertService = new BudgetAlertService($this->db);
        $success = $alertService->dismissAlert($userId, $alertId);

        if ($success) {
            $this->json(['success' => true]);
        } else {
            $this->json(['error' => 'Alert not found or access denied'], 404);
        }
    }

    /**
     * Get alert statistics for the current user
     */
    public function getAlertStats(array $params = []): void {
        $userId = $this->getUserId();

        $alertService = new BudgetAlertService($this->db);
        $stats = $alertService->getAlertStats($userId);

        $this->json(['stats' => $stats]);
    }

    /**
     * Generate alerts for all budgets (can be called by cron job or manually)
     */
    public function generateAlerts(array $params = []): void {
        $userId = $this->getUserId();

        $alertService = new BudgetAlertService($this->db);
        $alerts = $alertService->generateAlerts($userId);

        $this->json([
            'success' => true,
            'alerts_generated' => count($alerts),
            'alerts' => $alerts
        ]);
    }
    /**
     * Get available budget templates
     */
    public function getTemplates(array $params = []): void {
        $userId = $this->getUserId();

        $templateService = new BudgetTemplateService($this->db);
        $templates = $templateService->getTemplates($userId);

        $this->json(['templates' => $templates]);
    }

    /**
     * Apply a budget template
     */
    public function applyTemplate(array $params = []): void {
        $userId = $this->getUserId();
        $templateId = (int)($params['id'] ?? 0);

        if (!$templateId) {
            $this->json(['error' => 'Template ID is required'], 400);
            return;
        }

        // Get POST data for customizations
        $customizations = $_POST['customizations'] ?? [];
        $month = $_POST['month'] ?? date('Y-m');
        $income = isset($_POST['income']) ? (float)$_POST['income'] : null;

        try {
            $templateService = new BudgetTemplateService($this->db);
            $appliedBudgets = $templateService->applyTemplate($userId, $templateId, $customizations, $month);

            $this->json([
                'success' => true,
                'budgets_created' => count($appliedBudgets),
                'budgets' => $appliedBudgets
            ]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get template preview with calculated amounts
     */
    public function previewTemplate(array $params = []): void {
        $userId = $this->getUserId();
        $templateId = (int)($params['id'] ?? 0);

        if (!$templateId) {
            $this->json(['error' => 'Template ID is required'], 400);
            return;
        }

        $income = isset($_GET['income']) ? (float)$_GET['income'] : 30000; // Default CZK
        $customizations = $_GET['customizations'] ?? [];

        try {
            $templateService = new BudgetTemplateService($this->db);
            $template = $templateService->getTemplate($templateId, $userId);

            if (!$template) {
                $this->json(['error' => 'Template not found'], 404);
                return;
            }

            // Calculate amounts for preview
            foreach ($template['categories'] as &$category) {
                $categoryKey = $category['category_name'];
                if (isset($customizations[$categoryKey])) {
                    $custom = $customizations[$categoryKey];
                    if (isset($custom['amount'])) {
                        $category['calculated_amount'] = (float)$custom['amount'];
                    } elseif (isset($custom['percentage'])) {
                        $category['calculated_amount'] = $income * ((float)$custom['percentage'] / 100);
                    }
                } else {
                    if ($category['suggested_percentage'] > 0) {
                        $category['calculated_amount'] = $income * ($category['suggested_percentage'] / 100);
                    } else {
                        $category['calculated_amount'] = (float)$category['suggested_amount'];
                    }
                }
            }

            $this->json([
                'template' => $template,
                'income' => $income,
                'preview' => $template['categories']
            ]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get user's template preferences
     */
    public function getTemplatePreferences(array $params = []): void {
        $userId = $this->getUserId();

        $preferences = $this->db->query(
            "SELECT tp.*, bt.name as template_name, bt.template_type
             FROM user_template_preferences tp
             LEFT JOIN budget_templates bt ON tp.template_id = bt.id
             WHERE tp.user_id = ?
             ORDER BY tp.last_used_at DESC",
            [$userId]
        );

        // Decode customizations JSON
        foreach ($preferences as &$pref) {
            $pref['customizations'] = json_decode($pref['customizations'] ?? '{}', true);
        }

        $this->json(['preferences' => $preferences]);
    }

    /**
     * Get budget analytics and insights
     */
    public function getAnalytics(array $params = []): void {
        $userId = $this->getUserId();
        $month = $this->getQueryParam('month', date('Y-m'));

        try {
            $analyticsService = new BudgetAnalyticsService($this->db);
            $analytics = $analyticsService->getBudgetAnalytics($userId, $month);

            $this->json([
                'success' => true,
                'analytics' => $analytics,
                'month' => $month
            ]);
        } catch (\Exception $e) {
            $this->json(['error' => 'Failed to generate analytics: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get budget performance metrics
     */
    public function getPerformance(array $params = []): void {
        $userId = $this->getUserId();
        $month = $this->getQueryParam('month', date('Y-m'));

        try {
            $analyticsService = new BudgetAnalyticsService($this->db);
            $performance = $analyticsService->calculatePerformanceScore($userId, $month);

            $this->json([
                'success' => true,
                'performance' => $performance,
                'month' => $month
            ]);
        } catch (\Exception $e) {
            $this->json(['error' => 'Failed to calculate performance: ' . $e->getMessage()], 500);
        }
    }
}
