<?php
namespace BudgetApp\Controllers;

use BudgetApp\Services\AutomationService;
use BudgetApp\Services\JobMarketService;
use BudgetApp\Services\CzechBenefitsService;
use BudgetApp\Services\SecurityService;
use BudgetApp\Services\PerformanceService;

class AutomationController extends BaseController {
    private AutomationService $automationService;
    private JobMarketService $jobMarketService;
    private CzechBenefitsService $czechBenefitsService;
    private SecurityService $securityService;
    private PerformanceService $performanceService;

    public function __construct($app = null) {
        parent::__construct($app);
        $this->automationService = new AutomationService($this->db);
        $this->jobMarketService = new JobMarketService($this->db);
        $this->czechBenefitsService = new CzechBenefitsService($this->db);
        $this->securityService = new SecurityService($this->db);
        $this->performanceService = new PerformanceService($this->db);
    }

    /**
     * Show automation dashboard view (UI)
     */
    public function dashboardView(array $params = []): void {
        $userId = $this->getUserId();

        try {
            // Get user's current automation rules
            $actions = $this->automationService->getUserAutomatedActions($userId);

            echo $this->app->render('automation/dashboard', [
                'actions' => $actions
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo $this->app->renderError($e);
        }
    }

    /**
     * Execute automated actions for user
     */
    public function executeActions(): void {
        $this->requireAuth();

        $startTime = microtime(true);
        $this->performanceService->recordApiResponse('automation/execute', 0, 200, ['user_id' => $this->userId]);

        try {
            $results = $this->automationService->executeAutomatedActions($this->userId);

            $executionTime = (microtime(true) - $startTime) * 1000;
            $this->performanceService->recordApiResponse('automation/execute', $executionTime, 200, ['user_id' => $this->userId]);

            $this->jsonResponse([
                'success' => true,
                'results' => $results,
                'execution_time_ms' => round($executionTime, 2)
            ]);

        } catch (\Exception $e) {
            $this->performanceService->recordApiResponse('automation/execute', (microtime(true) - $startTime) * 1000, 500, ['error' => $e->getMessage()]);
            $this->jsonResponse(['error' => 'Failed to execute automated actions'], 500);
        }
    }

    /**
     * Get user's automated actions
     */
    public function getUserActions(): void {
        $this->requireAuth();

        $actions = $this->automationService->getUserAutomatedActions($this->userId);

        $this->jsonResponse([
            'success' => true,
            'actions' => $actions
        ]);
    }

    /**
     * Create new automated action
     */
    public function createAction(): void {
        $this->requireAuth();

        $data = $this->getJsonInput();
        $this->validateInput($data, [
            'action_type' => 'required',
            'trigger_type' => 'required'
        ]);

        $actionId = $this->automationService->createAutomatedAction(
            $this->userId,
            $data['action_type'],
            $data['trigger_type'],
            $data['trigger_condition'] ?? [],
            $data['action_data'] ?? []
        );

        // Log security event
        $this->securityService->logAuditEvent($this->userId, 'automation_created', [
            'action_id' => $actionId,
            'action_type' => $data['action_type']
        ]);

        $this->jsonResponse([
            'success' => true,
            'action_id' => $actionId
        ]);
    }

    /**
     * Get job market opportunities
     */
    public function getJobOpportunities(): void {
        $this->requireAuth();

        $opportunities = $this->jobMarketService->getRelevantOpportunities($this->userId);

        $this->jsonResponse([
            'success' => true,
            'opportunities' => $opportunities
        ]);
    }

    /**
     * Update job opportunity status
     */
    public function updateJobStatus(): void {
        $this->requireAuth();

        $data = $this->getJsonInput();
        $this->validateInput($data, [
            'opportunity_id' => 'required|numeric',
            'status' => 'required'
        ]);

        $appliedAt = null;
        if ($data['status'] === 'applied' && isset($data['applied_at'])) {
            $appliedAt = $data['applied_at'];
        }

        $this->jobMarketService->updateOpportunityStatus(
            (int)$data['opportunity_id'],
            $data['status'],
            $appliedAt
        );

        $this->jsonResponse(['success' => true]);
    }

    /**
     * Generate career insights
     */
    public function generateCareerInsights(): void {
        $this->requireAuth();

        $insights = $this->jobMarketService->generateCareerInsights($this->userId);

        $this->jsonResponse([
            'success' => true,
            'insights' => $insights
        ]);
    }

    /**
     * Get Czech benefits for user
     */
    public function getBenefits(): void {
        $this->requireAuth();

        $benefits = $this->czechBenefitsService->getPotentialBenefits($this->userId);

        $this->jsonResponse([
            'success' => true,
            'benefits' => $benefits
        ]);
    }

    /**
     * Record benefit application
     */
    public function applyForBenefit(): void {
        $this->requireAuth();

        $data = $this->getJsonInput();
        $this->validateInput($data, [
            'benefit_id' => 'required|numeric',
            'status' => 'required'
        ]);

        $this->czechBenefitsService->recordBenefitApplication(
            $this->userId,
            (int)$data['benefit_id'],
            $data['status']
        );

        $this->jsonResponse(['success' => true]);
    }

    /**
     * Get user's benefit applications
     */
    public function getBenefitApplications(): void {
        $this->requireAuth();

        $applications = $this->czechBenefitsService->getUserBenefitApplications($this->userId);

        $this->jsonResponse([
            'success' => true,
            'applications' => $applications
        ]);
    }

    /**
     * Submit feedback for AI recommendation
     */
    public function submitFeedback(): void {
        $this->requireAuth();

        $data = $this->getJsonInput();
        $this->validateInput($data, [
            'recommendation_id' => 'required|numeric',
            'feedback_type' => 'required'
        ]);

        $aiService = new \BudgetApp\Services\AiRecommendations($this->db);
        $aiService->submitFeedback(
            $this->userId,
            (int)$data['recommendation_id'],
            $data['feedback_type'],
            $data['rating'] ?? null,
            $data['comment'] ?? null,
            $data['implemented_at'] ?? null
        );

        $this->jsonResponse(['success' => true]);
    }

    /**
     * Get recommendation history
     */
    public function getRecommendationHistory(): void {
        $this->requireAuth();

        $limit = (int)($_GET['limit'] ?? 50);
        $aiService = new \BudgetApp\Services\AiRecommendations($this->db);
        $history = $aiService->getRecommendationHistory($this->userId, $limit);

        $this->jsonResponse([
            'success' => true,
            'history' => $history
        ]);
    }

    /**
     * Get feedback statistics
     */
    public function getFeedbackStats(): void {
        $this->requireAuth();

        $aiService = new \BudgetApp\Services\AiRecommendations($this->db);
        $stats = $aiService->getFeedbackStats($this->userId);

        $this->jsonResponse([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Get performance dashboard data
     */
    public function getPerformanceDashboard(): void {
        $this->requireAuth();

        $dashboard = $this->performanceService->getPerformanceDashboard();

        $this->jsonResponse([
            'success' => true,
            'dashboard' => $dashboard
        ]);
    }

    /**
     * Get security audit logs
     */
    public function getSecurityLogs(): void {
        $this->requireAuth();

        $limit = (int)($_GET['limit'] ?? 100);
        $logs = $this->securityService->getAuditLogs($this->userId, $limit);

        $this->jsonResponse([
            'success' => true,
            'logs' => $logs
        ]);
    }

    /**
     * Start usability test session
     */
    public function startUsabilitySession(): void {
        $this->requireAuth();

        $data = $this->getJsonInput();
        $this->validateInput($data, [
            'session_type' => 'required',
            'test_name' => 'required'
        ]);

        $sessionId = $this->performanceService->recordUsabilitySession(
            $this->userId,
            $data['session_type'],
            $data['test_name'],
            $data['variant'] ?? null
        );

        $this->jsonResponse([
            'success' => true,
            'session_id' => $sessionId
        ]);
    }

    /**
     * Complete usability test session
     */
    public function completeUsabilitySession(): void {
        $this->requireAuth();

        $data = $this->getJsonInput();
        $this->validateInput($data, [
            'session_id' => 'required|numeric'
        ]);

        $this->performanceService->completeUsabilitySession(
            (int)$data['session_id'],
            $data['feedback'] ?? []
        );

        $this->jsonResponse(['success' => true]);
    }

    /**
     * Get A/B test results
     */
    public function getAbTestResults(): void {
        $this->requireAuth();

        $testName = $_GET['test_name'] ?? '';
        if (empty($testName)) {
            $this->jsonResponse(['error' => 'Test name required'], 400);
            return;
        }

        $results = $this->performanceService->getAbTestResults($testName);

        $this->jsonResponse([
            'success' => true,
            'results' => $results
        ]);
    }
}