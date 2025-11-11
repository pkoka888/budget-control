<?php
namespace BudgetApp\Controllers;

class TipsController extends BaseController {
    public function list(): void {
        $tips = $this->db->query(
            "SELECT * FROM tips WHERE is_published = 1 ORDER BY priority ASC, created_at DESC"
        );

        // Group by category
        $grouped = [];
        foreach ($tips as $tip) {
            $category = $tip['category'] ?? 'Ostatní';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $tip;
        }

        echo $this->render('tips/list', [
            'title' => 'Tipy a průvodce',
            'tips' => $tips,
            'grouped' => $grouped
        ]);
    }

    public function show(array $params): void {
        $id = $params['id'] ?? 0;

        $tip = $this->db->queryOne(
            "SELECT * FROM tips WHERE id = ? AND is_published = 1",
            [$id]
        );

        if (!$tip) {
            http_response_code(404);
            echo "Tip nenalezen";
            return;
        }

        // Get related tips
        $related = $this->db->query(
            "SELECT * FROM tips WHERE category = ? AND id != ? AND is_published = 1 LIMIT 3",
            [$tip['category'], $id]
        );

        echo $this->render('tips/show', [
            'title' => $tip['title'],
            'tip' => $tip,
            'related' => $related
        ]);
    }
}
