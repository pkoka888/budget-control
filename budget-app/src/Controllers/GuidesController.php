<?php
namespace BudgetApp\Controllers;

class GuidesController extends BaseController {
    public function list(array $params = []): void {
        $guides = $this->db->query(
            "SELECT * FROM tips ORDER BY created_at DESC"
        );

        echo $this->app->render('guides/list', ['guides' => $guides]);
    }
}
