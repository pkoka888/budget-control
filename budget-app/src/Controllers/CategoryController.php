<?php
namespace BudgetApp\Controllers;

class CategoryController extends BaseController {
    public function list(array $params = []): void {
        $userId = $this->getUserId();

        $categories = $this->db->query(
            "SELECT * FROM categories WHERE user_id = ? OR user_id IS NULL ORDER BY name",
            [$userId]
        );

        // Add spending info for each category
        foreach ($categories as &$category) {
            $spending = $this->db->queryOne(
                "SELECT COALESCE(SUM(ABS(amount)), 0) as total FROM transactions
                 WHERE category_id = ? AND type = 'expense'",
                [$category['id']]
            );
            $category['total_spending'] = $spending['total'] ?? 0;
        }

        echo $this->app->render('categories/list', ['categories' => $categories]);
    }

    public function create(array $params = []): void {
        $userId = $this->getUserId();

        $name = $_POST['name'] ?? '';
        $color = $_POST['color'] ?? '#3b82f6';
        $icon = $_POST['icon'] ?? 'tag';

        if (empty($name)) {
            $this->json(['error' => 'Jméno kategorie je povinné'], 400);
            return;
        }

        $categoryId = $this->db->insert('categories', [
            'user_id' => $userId,
            'name' => $name,
            'color' => $color,
            'icon' => $icon,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $this->json(['id' => $categoryId, 'name' => $name]);
    }

    public function update(array $params = []): void {
        $categoryId = $params['id'] ?? 0;
        $userId = $this->getUserId();

        $category = $this->db->queryOne(
            "SELECT * FROM categories WHERE id = ? AND user_id = ?",
            [$categoryId, $userId]
        );

        if (!$category) {
            http_response_code(404);
            return;
        }

        $updates = [];
        if (isset($_POST['name'])) $updates['name'] = $_POST['name'];
        if (isset($_POST['color'])) $updates['color'] = $_POST['color'];
        if (isset($_POST['icon'])) $updates['icon'] = $_POST['icon'];

        $this->db->update('categories', $updates, ['id' => $categoryId]);

        $this->json(['success' => true]);
    }

    public function delete(array $params = []): void {
        $categoryId = $params['id'] ?? 0;
        $userId = $this->getUserId();

        $category = $this->db->queryOne(
            "SELECT * FROM categories WHERE id = ? AND user_id = ?",
            [$categoryId, $userId]
        );

        if (!$category) {
            http_response_code(404);
            return;
        }

        // Check if category has transactions
        $count = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM transactions WHERE category_id = ?",
            [$categoryId]
        );

        if ($count['count'] > 0) {
            $this->json(['error' => 'Nelze smazat kategorii s transakcemi'], 400);
            return;
        }

        $this->db->delete('categories', ['id' => $categoryId]);

        $this->json(['success' => true]);
    }
}
