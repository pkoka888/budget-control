<?php
namespace BudgetApp\Controllers;

class AccountController extends BaseController {
    public function list(array $params = []): void {
        $userId = $this->getUserId();

        $accounts = $this->db->query(
            "SELECT * FROM accounts WHERE user_id = ? ORDER BY created_at DESC",
            [$userId]
        );

        // Add balance info for each account
        foreach ($accounts as &$account) {
            $balance = $this->db->queryOne(
                "SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE account_id = ?",
                [$account['id']]
            );
            $account['current_balance'] = $account['initial_balance'] + ($balance['total'] ?? 0);
        }

        echo $this->app->render('accounts/list', ['accounts' => $accounts]);
    }

    public function createForm(array $params = []): void {
        echo $this->app->render('accounts/create');
    }

    public function create(array $params = []): void {
        $userId = $this->getUserId();
        $name = $_POST['name'] ?? '';
        $type = $_POST['type'] ?? 'checking';
        $initialBalance = (float)($_POST['initial_balance'] ?? 0);

        if (empty($name)) {
            echo $this->app->render('accounts/create', ['error' => 'Jméno účtu je povinné']);
            return;
        }

        $accountId = $this->db->insert('accounts', [
            'user_id' => $userId,
            'name' => $name,
            'type' => $type,
            'initial_balance' => $initialBalance,
            'currency' => $this->getUser()['currency'] ?? 'CZK',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $this->setFlash('success', 'Účet vytvořen úspěšně');
        header('Location: /accounts');
        exit;
    }

    public function show(array $params = []): void {
        $accountId = $params['id'] ?? 0;
        $userId = $this->getUserId();

        $account = $this->db->queryOne(
            "SELECT * FROM accounts WHERE id = ? AND user_id = ?",
            [$accountId, $userId]
        );

        if (!$account) {
            http_response_code(404);
            echo $this->app->render('404');
            return;
        }

        $transactions = $this->db->query(
            "SELECT t.*, c.name as category_name FROM transactions t
             LEFT JOIN categories c ON t.category_id = c.id
             WHERE t.account_id = ?
             ORDER BY t.date DESC LIMIT 20",
            [$accountId]
        );

        echo $this->app->render('accounts/show', ['account' => $account, 'transactions' => $transactions]);
    }

    public function update(array $params = []): void {
        $accountId = $params['id'] ?? 0;
        $userId = $this->getUserId();

        $account = $this->db->queryOne(
            "SELECT * FROM accounts WHERE id = ? AND user_id = ?",
            [$accountId, $userId]
        );

        if (!$account) {
            http_response_code(404);
            return;
        }

        $name = $_POST['name'] ?? $account['name'];

        $this->db->update('accounts', ['name' => $name], ['id' => $accountId]);

        $this->setFlash('success', 'Účet aktualizován');
        header('Location: /accounts');
        exit;
    }

    public function delete(array $params = []): void {
        $accountId = $params['id'] ?? 0;
        $userId = $this->getUserId();

        $account = $this->db->queryOne(
            "SELECT * FROM accounts WHERE id = ? AND user_id = ?",
            [$accountId, $userId]
        );

        if (!$account) {
            http_response_code(404);
            return;
        }

        // Check if account has transactions
        $count = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM transactions WHERE account_id = ?",
            [$accountId]
        );

        if ($count['count'] > 0) {
            $this->setFlash('error', 'Nelze smazat účet s transakcemi');
            header('Location: /accounts');
            exit;
        }

        $this->db->delete('accounts', ['id' => $accountId]);

        $this->setFlash('success', 'Účet smazán');
        header('Location: /accounts');
        exit;
    }
}
