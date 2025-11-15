<?php
namespace BudgetApp;

class Application {
    private Database $db;
    private Router $router;
    private Config $config;
    private array $routes = [];

    public function __construct(string $basePath = __DIR__ . '/..') {
        $this->config = new Config($basePath);
        $this->db = new Database($this->config->getDatabasePath());
        $this->router = new Router();
        $this->setupRoutes();
    }

    public function getDatabase(): Database {
        return $this->db;
    }

    public function getConfig(): Config {
        return $this->config;
    }

    public function getRouter(): Router {
        return $this->router;
    }

    private function setupRoutes(): void {
        // Dashboard routes
        $this->router->get('/', 'DashboardController@index');

        // Account routes
        $this->router->get('/accounts', 'AccountController@list');
        $this->router->get('/accounts/create', 'AccountController@createForm');
        $this->router->post('/accounts', 'AccountController@create');
        $this->router->get('/accounts/:id', 'AccountController@show');
        $this->router->post('/accounts/:id/update', 'AccountController@update');
        $this->router->post('/accounts/:id/delete', 'AccountController@delete');

        // Transaction routes
        $this->router->get('/transactions', 'TransactionController@list');
        $this->router->get('/transactions/create', 'TransactionController@createForm');
        $this->router->post('/transactions', 'TransactionController@create');
        $this->router->get('/transactions/:id', 'TransactionController@show');
        $this->router->post('/transactions/:id/update', 'TransactionController@update');
        $this->router->post('/transactions/:id/delete', 'TransactionController@delete');
        $this->router->get('/transactions/export/csv', 'TransactionController@exportCsv');
        $this->router->get('/transactions/export/xlsx', 'TransactionController@exportExcel');
        $this->router->post('/transactions/bulk-action', 'TransactionController@bulkAction');

        // Transaction split routes (UI)
        $this->router->get('/transactions/:id/splits', 'TransactionController@splitsView');
        // Transaction split routes (API)
        $this->router->post('/transactions/:id/split', 'TransactionController@createSplit');
        $this->router->put('/transactions/:id/split', 'TransactionController@updateSplit');
        $this->router->delete('/transactions/:id/split', 'TransactionController@deleteSplit');
        $this->router->get('/transactions/:id/split', 'TransactionController@getSplits');

        // Recurring transaction routes (UI)
        $this->router->get('/transactions/recurring', 'TransactionController@recurringView');
        // Recurring transaction routes (API)
        $this->router->get('/transactions/recurring/detect', 'TransactionController@detectRecurring');
        $this->router->post('/transactions/recurring/create', 'TransactionController@createRecurring');
        $this->router->get('/api/transactions/recurring', 'TransactionController@getRecurring');
        $this->router->post('/transactions/recurring/:id/update', 'TransactionController@updateRecurring');
        $this->router->post('/transactions/recurring/:id/delete', 'TransactionController@deleteRecurring');

        // Category routes
        $this->router->get('/categories', 'CategoryController@list');
        $this->router->post('/categories', 'CategoryController@create');
        $this->router->post('/categories/:id/update', 'CategoryController@update');
        $this->router->post('/categories/:id/delete', 'CategoryController@delete');

        // Budget routes
        $this->router->get('/budgets', 'BudgetController@list');
        $this->router->post('/budgets', 'BudgetController@create');
        $this->router->post('/budgets/:id/update', 'BudgetController@update');
        $this->router->post('/budgets/:id/delete', 'BudgetController@delete');
        // Budget alert routes
        $this->router->get('/budgets/alerts', 'BudgetController@getAlerts');
        $this->router->post('/budgets/alerts/:id/acknowledge', 'BudgetController@acknowledgeAlert');
        $this->router->post('/budgets/alerts/:id/dismiss', 'BudgetController@dismissAlert');
        $this->router->get('/budgets/alerts/stats', 'BudgetController@getAlertStats');
        $this->router->post('/budgets/alerts/generate', 'BudgetController@generateAlerts');
        // Budget template routes (UI)
        $this->router->get('/budgets/templates', 'BudgetController@templatesView');
        // Budget template routes (API)
        $this->router->get('/api/budgets/templates', 'BudgetController@getTemplates');
        $this->router->post('/budgets/templates/:id/apply', 'BudgetController@applyTemplate');
        $this->router->get('/budgets/templates/:id/preview', 'BudgetController@previewTemplate');
        $this->router->post('/budgets/templates', 'BudgetController@createTemplate');
        $this->router->put('/budgets/templates/:id', 'BudgetController@updateTemplate');
        $this->router->delete('/budgets/templates/:id', 'BudgetController@deleteTemplate');
        $this->router->get('/budgets/templates/:id/export', 'BudgetController@exportTemplate');
        $this->router->post('/budgets/templates/import', 'BudgetController@importTemplate');
        // Budget analytics routes
        $this->router->get('/budgets/analytics', 'BudgetController@getAnalytics');
        $this->router->get('/budgets/performance', 'BudgetController@getPerformance');
        $this->router->get('/budgets/templates/preferences', 'BudgetController@getTemplatePreferences');

        // CSV Import routes
        $this->router->get('/import', 'ImportController@form');
        $this->router->post('/import/upload', 'ImportController@upload');
        $this->router->post('/import/process', 'ImportController@process');

        // Bank JSON Import routes
        $this->router->get('/bank-import', 'BankImportController@index');
        $this->router->post('/bank-import/import-file', 'BankImportController@importFile');
        $this->router->post('/bank-import/auto-import', 'BankImportController@autoImportAll');
        $this->router->get('/bank-import/job-status', 'BankImportController@jobStatus');

        // Investment routes
        $this->router->get('/investments', 'InvestmentController@list');
        $this->router->get('/investments/portfolio', 'InvestmentController@portfolio');
        $this->router->post('/investments', 'InvestmentController@create');
        $this->router->post('/investments/:id/update', 'InvestmentController@update');
        $this->router->post('/investments/:id/delete', 'InvestmentController@delete');
        $this->router->post('/investments/transactions', 'InvestmentController@recordTransaction');
        $this->router->get('/investments/transactions', 'InvestmentController@getTransactions');
        $this->router->get('/investments/performance', 'InvestmentController@getPerformance');
        $this->router->post('/investments/prices', 'InvestmentController@updatePrices');
        $this->router->get('/investments/diversification', 'InvestmentController@getDiversification');
        $this->router->get('/investments/accounts', 'InvestmentController@getAccounts');
        $this->router->post('/investments/accounts', 'InvestmentController@createAccount');

        // Asset allocation routes
        $this->router->get('/api/investments/allocation/current', 'InvestmentController@getCurrentAssetAllocation');
        $this->router->get('/api/investments/allocation/ideal/:riskProfile', 'InvestmentController@getIdealAllocationByRisk');
        $this->router->get('/api/investments/allocation/rebalance/:riskProfile', 'InvestmentController@getRebalancingAdvice');
        $this->router->get('/api/investments/allocation/compare/:riskProfile', 'InvestmentController@compareAllocations');

        // Financial Goals routes
        $this->router->get('/goals', 'GoalsController@list');
        $this->router->get('/goals/dashboard', 'GoalsController@dashboard');
        $this->router->get('/goals/:id', 'GoalsController@show');
        $this->router->post('/goals', 'GoalsController@create');
        $this->router->post('/goals/:id/update', 'GoalsController@update');
        $this->router->post('/goals/:id/delete', 'GoalsController@delete');
        $this->router->get('/goals/:id/milestones', 'GoalsController@getMilestones');
        $this->router->post('/goals/:id/milestones', 'GoalsController@createMilestone');
        $this->router->post('/goals/milestones/:id/update', 'GoalsController@updateMilestone');
        $this->router->get('/goals/:id/projection', 'GoalsController@getProjection');

        // Opportunities routes (UI)
        $this->router->get('/opportunities', 'OpportunitiesController@listView');
        // Opportunities routes (API)
        $this->router->get('/api/opportunities/dashboard', 'OpportunitiesController@dashboard');
        $this->router->get('/api/opportunities/learning', 'OpportunitiesController@learningPaths');
        $this->router->get('/api/opportunities/jobs', 'OpportunitiesController@jobs');
        $this->router->get('/api/opportunities/freelance', 'OpportunitiesController@freelance');
        $this->router->get('/api/opportunities/events', 'OpportunitiesController@events');
        $this->router->get('/api/opportunities/certifications', 'OpportunitiesController@certifications');
        $this->router->get('/api/opportunities/insights', 'OpportunitiesController@marketInsights');
        $this->router->post('/api/opportunities/track', 'OpportunitiesController@trackInteraction');
        $this->router->get('/api/opportunities/recommendations', 'OpportunitiesController@recommendations');
        $this->router->post('/api/opportunities/save', 'OpportunitiesController@saveOpportunity');
        $this->router->get('/api/opportunities/saved', 'OpportunitiesController@savedOpportunities');
        $this->router->delete('/api/opportunities/saved/:id', 'OpportunitiesController@removeSaved');

        // Scenario Planning routes (UI)
        $this->router->get('/scenario', 'ScenarioPlanningController@planningView');
        // Scenario Planning routes (API)
        $this->router->get('/api/scenario/generate', 'ScenarioPlanningController@generateScenarios');
        $this->router->get('/api/scenario/goal/:goal_id', 'ScenarioPlanningController@generateGoalScenarios');
        $this->router->get('/api/scenario/retirement', 'ScenarioPlanningController@generateRetirementScenarios');
        $this->router->get('/api/scenario/compare', 'ScenarioPlanningController@compareScenarios');
        $this->router->post('/api/scenario/save-as-goal', 'ScenarioPlanningController@saveScenarioAsGoal');
        $this->router->get('/api/scenario/templates', 'ScenarioPlanningController@getScenarioTemplates');

        // Automation Dashboard route (UI)
        $this->router->get('/automation', 'AutomationController@dashboardView');

        // Tips/Education routes
        $this->router->get('/tips', 'TipsController@list');
        $this->router->get('/tips/:id', 'TipsController@show');
        $this->router->get('/guides', 'GuidesController@list');

        // Reports routes
        $this->router->get('/reports/monthly', 'ReportController@monthly');
        $this->router->get('/reports/yearly', 'ReportController@yearly');
        $this->router->get('/reports/net-worth', 'ReportController@netWorth');
        $this->router->get('/reports/analytics', 'ReportController@analytics');
        $this->router->get('/reports/export/csv/:type', 'ReportController@exportCsv');
        $this->router->get('/reports/export/xlsx/:type', 'ReportController@exportExcel');

        // API routes for AJAX (legacy)
        $this->router->post('/api/transactions/categorize', 'ApiController@categorizeTransaction');
        $this->router->post('/api/recommendations', 'ApiController@getRecommendations');
        $this->router->get('/api/analytics/:period', 'ApiController@getAnalytics');

        // RESTful API v1 routes
        $this->router->get('/api/v1/docs', 'ApiController@getDocumentation');

        // Transaction API routes
        $this->router->get('/api/v1/transactions', 'ApiController@getTransactions');
        $this->router->get('/api/v1/transactions/:id', 'ApiController@getTransaction');
        $this->router->post('/api/v1/transactions', 'ApiController@createTransaction');
        $this->router->put('/api/v1/transactions/:id', 'ApiController@updateTransaction');
        $this->router->delete('/api/v1/transactions/:id', 'ApiController@deleteTransaction');

        // Account API routes
        $this->router->get('/api/v1/accounts', 'ApiController@getAccounts');
        $this->router->get('/api/v1/accounts/:id', 'ApiController@getAccount');
        $this->router->post('/api/v1/accounts', 'ApiController@createAccount');
        $this->router->put('/api/v1/accounts/:id', 'ApiController@updateAccount');
        $this->router->delete('/api/v1/accounts/:id', 'ApiController@deleteAccount');

        // Budget API routes
        $this->router->get('/api/v1/budgets', 'ApiController@getBudgets');
        $this->router->get('/api/v1/budgets/:id', 'ApiController@getBudget');
        $this->router->post('/api/v1/budgets', 'ApiController@createBudget');
        $this->router->put('/api/v1/budgets/:id', 'ApiController@updateBudget');
        $this->router->delete('/api/v1/budgets/:id', 'ApiController@deleteBudget');

        // Report API routes
        $this->router->get('/api/v1/reports/summary', 'ApiController@getReportsSummary');
        $this->router->get('/api/v1/reports/transactions', 'ApiController@getReportsTransactions');
        $this->router->get('/api/v1/reports/budgets', 'ApiController@getReportsBudgets');

        // Analytics API routes
        $this->router->get('/api/v1/analytics/:period', 'ApiController@getAnalytics');

        // Authentication routes
        $this->router->get('/login', 'AuthController@loginForm');
        $this->router->post('/login', 'AuthController@login');
        $this->router->get('/register', 'AuthController@registerForm');
        $this->router->post('/register', 'AuthController@register');
        $this->router->post('/logout', 'AuthController@logout');

        // Password reset routes
        $this->router->get('/forgot-password', 'AuthController@forgotPasswordForm');
        $this->router->post('/forgot-password', 'AuthController@forgotPassword');
        $this->router->get('/reset-password', 'AuthController@resetPasswordForm');
        $this->router->post('/reset-password', 'AuthController@resetPassword');

        // Email verification routes
        $this->router->get('/email-verification', 'EmailVerificationController@showVerificationPage');
        $this->router->get('/verify-email', 'EmailVerificationController@verifyEmail');
        $this->router->post('/api/email/resend', 'EmailVerificationController@resendVerificationEmail');
        $this->router->get('/api/email/status', 'EmailVerificationController@getStatus');

        // Two-Factor Authentication routes
        $this->router->get('/settings/two-factor', 'TwoFactorController@settings');
        $this->router->post('/api/2fa/setup', 'TwoFactorController@setup');
        $this->router->post('/api/2fa/enable', 'TwoFactorController@enable');
        $this->router->post('/api/2fa/disable', 'TwoFactorController@disable');
        $this->router->post('/api/2fa/verify', 'TwoFactorController@verify');
        $this->router->get('/api/2fa/devices', 'TwoFactorController@getTrustedDevices');
        $this->router->post('/api/2fa/devices/revoke', 'TwoFactorController@revokeTrustedDevice');
        $this->router->post('/api/2fa/backup-codes/regenerate', 'TwoFactorController@regenerateBackupCodes');

        // Settings routes
        $this->router->get('/settings', 'SettingsController@show');
        $this->router->get('/settings/profile', 'SettingsController@showProfile');
        $this->router->post('/settings/profile', 'SettingsController@updateProfile');
        $this->router->get('/settings/notifications', 'SettingsController@showNotifications');
        $this->router->post('/settings/notifications', 'SettingsController@updateNotifications');
        $this->router->get('/settings/preferences', 'SettingsController@showPreferences');
        $this->router->post('/settings/preferences', 'SettingsController@updatePreferences');
        $this->router->get('/settings/security', 'SettingsController@showSecurity');
        $this->router->post('/settings/security', 'SettingsController@updateSecurity');
        $this->router->get('/settings/automation', 'SettingsController@showAutomation');
        $this->router->post('/settings/automation', 'SettingsController@updateAutomation');

        // Automation API routes
        $this->router->post('/api/automation/execute', 'AutomationController@executeActions');
        $this->router->get('/api/automation/actions', 'AutomationController@getUserActions');
        $this->router->post('/api/automation/actions', 'AutomationController@createAction');
        $this->router->get('/api/automation/jobs', 'AutomationController@getJobOpportunities');
        $this->router->post('/api/automation/jobs/status', 'AutomationController@updateJobStatus');
        $this->router->get('/api/automation/jobs/insights', 'AutomationController@generateCareerInsights');
        $this->router->get('/api/automation/benefits', 'AutomationController@getBenefits');
        $this->router->post('/api/automation/benefits/apply', 'AutomationController@applyForBenefit');
        $this->router->get('/api/automation/benefits/applications', 'AutomationController@getBenefitApplications');
        $this->router->post('/api/automation/feedback', 'AutomationController@submitFeedback');
        $this->router->get('/api/automation/recommendations/history', 'AutomationController@getRecommendationHistory');
        $this->router->get('/api/automation/feedback/stats', 'AutomationController@getFeedbackStats');
        $this->router->get('/api/automation/performance', 'AutomationController@getPerformanceDashboard');
        $this->router->get('/api/automation/security/logs', 'AutomationController@getSecurityLogs');
        $this->router->post('/api/automation/usability/session', 'AutomationController@startUsabilitySession');
        $this->router->post('/api/automation/usability/session/complete', 'AutomationController@completeUsabilitySession');
        $this->router->get('/api/automation/ab-test/:test_name', 'AutomationController@getAbTestResults');
        $this->router->post('/settings/update', 'SettingsController@updateSettings');
        $this->router->get('/settings/export', 'SettingsController@exportData');
        $this->router->post('/settings/import', 'SettingsController@importData');
        $this->router->post('/settings/delete-account', 'SettingsController@deleteAccount');
        $this->router->post('/settings/change-password', 'SettingsController@changePassword');

        // ========================================
        // Family Sharing Routes (v2.0)
        // ========================================

        // Household Management
        $this->router->get('/household', 'HouseholdController@index');
        $this->router->get('/household/create', 'HouseholdController@createForm');
        $this->router->post('/household/store', 'HouseholdController@store');
        $this->router->get('/household/:id', 'HouseholdController@show');
        $this->router->post('/household/:id/update', 'HouseholdController@update');
        $this->router->post('/household/:id/delete', 'HouseholdController@delete');
        $this->router->post('/household/:id/leave', 'HouseholdController@leave');

        // Member Management
        $this->router->post('/household/:id/invite', 'HouseholdController@inviteMember');
        $this->router->post('/household/:id/member/:memberId/role', 'HouseholdController@updateMemberRole');
        $this->router->post('/household/:id/member/:memberId/remove', 'HouseholdController@removeMember');

        // Invitations
        $this->router->get('/invitation/accept/:token', 'HouseholdController@acceptInvitationForm');
        $this->router->post('/invitation/accept', 'HouseholdController@processAcceptance');
        $this->router->post('/invitation/:id/cancel', 'HouseholdController@cancelInvitation');
        $this->router->post('/invitation/:id/resend', 'HouseholdController@resendInvitation');

        // Activity Feed
        $this->router->get('/activity/:householdId', 'HouseholdController@activity');
        $this->router->get('/activity/:householdId/filter', 'HouseholdController@filterActivity');

        // Notifications
        $this->router->get('/notifications', 'NotificationController@index');
        $this->router->get('/notifications/unread', 'NotificationController@unread');
        $this->router->post('/notifications/:id/read', 'NotificationController@markAsRead');
        $this->router->post('/notifications/mark-all-read', 'NotificationController@markAllAsRead');
        $this->router->post('/notifications/:id/dismiss', 'NotificationController@dismiss');

        // Approvals
        $this->router->get('/approval/household/:householdId', 'ApprovalController@index');
        $this->router->get('/approval/:id', 'ApprovalController@show');
        $this->router->post('/approval/:id/approve', 'ApprovalController@approve');
        $this->router->post('/approval/:id/reject', 'ApprovalController@reject');

        // Child Accounts
        $this->router->get('/child-account/:householdId', 'ChildAccountController@index');
        $this->router->get('/child-account/:householdId/settings', 'ChildAccountController@settings');
        $this->router->get('/child-account/:householdId/transactions', 'ChildAccountController@transactions');
        $this->router->get('/child-account/:householdId/allowance', 'ChildAccountController@allowance');
        $this->router->post('/child-account/:householdId/money-request', 'ChildAccountController@createMoneyRequest');
        $this->router->post('/child-account/money-request/:id/cancel', 'ChildAccountController@cancelMoneyRequest');
        $this->router->post('/child-account/chore/:choreId/complete', 'ChildAccountController@completeChore');

        // Chores
        $this->router->get('/chores/household/:householdId', 'ChoreController@index');
        $this->router->get('/chores/household/:householdId/stats', 'ChoreController@stats');
        $this->router->get('/chores/my-chores', 'ChoreController@myChores');
        $this->router->post('/chores/store', 'ChoreController@store');
        $this->router->get('/chores/:id', 'ChoreController@show');
        $this->router->post('/chores/:id/update', 'ChoreController@update');
        $this->router->post('/chores/:id/delete', 'ChoreController@delete');
        $this->router->post('/chores/completion/:completionId/verify', 'ChoreController@verifyCompletion');

        // Comments
        $this->router->get('/comments/:entityType/:entityId', 'CommentController@index');
        $this->router->post('/comments/store', 'CommentController@store');
        $this->router->post('/comments/:id/update', 'CommentController@update');
        $this->router->post('/comments/:id/delete', 'CommentController@delete');
    }

    public function run(): void {
        // Session is already started in index.php with SessionConfig::start()

        try {
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

            // Remove base path if needed
            $basePath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
            if ($basePath !== '/') {
                $path = str_replace($basePath, '', $path);
            }

            $route = $this->router->match($method, $path);

            if (!$route) {
                http_response_code(404);
                echo $this->render('404');
                return;
            }

            // Get controller and action
            [$controller, $action] = explode('@', $route['handler']);
            $controllerClass = "BudgetApp\\Controllers\\{$controller}";

            if (!class_exists($controllerClass)) {
                throw new \Exception("Controller not found: {$controllerClass}");
            }

            // Instantiate and execute
            $instance = new $controllerClass($this);
            $instance->$action($route['params'] ?? []);

        } catch (\Exception $e) {
            http_response_code(500);
            echo $this->renderError($e);
        }
    }

    public function render(string $template, array $data = []): string {
        // Don't wrap auth pages with layout (they have their own HTML)
        $authPages = ['auth/login', 'auth/register', 'auth/forgot-password', 'auth/reset-password', 'auth/email-verification', 'auth/email-verified', '404'];

        // Add flash data before rendering view
        if (isset($_SESSION['flash'])) {
            $data['flash'] = $_SESSION['flash'];
            unset($_SESSION['flash']);
        }

        if (in_array($template, $authPages)) {
            extract($data);
            ob_start();
            include $this->config->getViewPath() . "/{$template}.php";
            return ob_get_clean();
        }

        // For all other pages, wrap with layout
        extract($data);
        ob_start();
        include $this->config->getViewPath() . "/{$template}.php";
        $content = ob_get_clean();

        // Prepare data for layout rendering
        $data['template'] = $template;
        $data['content'] = $content;

        extract($data);
        ob_start();
        include $this->config->getViewPath() . "/layout.php";
        return ob_get_clean();
    }

    private function renderError(\Exception $e): string {
        return "<h1>Error</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
