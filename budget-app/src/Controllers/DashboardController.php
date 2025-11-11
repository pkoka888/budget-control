<?php
namespace BudgetApp\Controllers;

use BudgetApp\Services\FinancialAnalyzer;
use BudgetApp\Services\AiRecommendations;

class DashboardController extends BaseController {
    public function index(): void {
        $userId = $this->getUserId();
        $analyzer = new FinancialAnalyzer($this->db);

        // Get current month summary
        $thisMonth = $analyzer->getMonthSummary($userId, date('Y-m'));

        // Get net worth
        $netWorth = $analyzer->getNetWorth($userId);

        // Get spending trend (last 30 days)
        $spendingTrend = $analyzer->getSpendingTrend($userId, 30);

        // Get top expense categories
        $topCategories = $analyzer->getExpensesByCategory(
            $userId,
            date('Y-m-01'),
            date('Y-m-t')
        );

        // Get health score
        $healthScore = $analyzer->getHealthScore($userId);

        // Get AI recommendations
        $aiRec = new AiRecommendations($this->db);
        $recommendations = $aiRec->getStoredRecommendations($userId);

        // Get recent transactions
        $recentTransactions = $this->db->query(
            "SELECT t.*, c.name as category_name, c.color, c.icon
             FROM transactions t
             LEFT JOIN categories c ON t.category_id = c.id
             WHERE t.user_id = ?
             ORDER BY t.date DESC
             LIMIT 10",
            [$userId]
        );

        // Get accounts
        $accounts = $this->db->query(
            "SELECT * FROM accounts WHERE user_id = ? ORDER BY updated_at DESC",
            [$userId]
        );

        $data = [
            'month' => $thisMonth,
            'netWorth' => $netWorth,
            'spendingTrend' => $spendingTrend,
            'topCategories' => $topCategories,
            'healthScore' => $healthScore,
            'recommendations' => $recommendations,
            'recentTransactions' => $recentTransactions,
            'accounts' => $accounts,
            'currentMonth' => date('Y-m')
        ];

        echo $this->render('dashboard', $data);
    }
}
