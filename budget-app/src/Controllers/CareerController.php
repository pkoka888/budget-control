<?php
namespace BudgetApp\Controllers;

use BudgetApp\Services\CareerService;

class CareerController extends BaseController {
    private CareerService $careerService;

    public function __construct($app) {
        parent::__construct($app);
        $this->careerService = new CareerService($this->db);
    }

    /**
     * Get career assessment
     */
    public function assess(array $params = []): void {
        $userId = $this->getUserId();

        try {
            $assessment = $this->careerService->assessSkills($userId);

            $this->json([
                'success' => true,
                'assessment' => $assessment
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to generate career assessment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get career opportunities
     */
    public function opportunities(array $params = []): void {
        $userId = $this->getUserId();

        try {
            $opportunities = $this->careerService->getCareerOpportunities($userId);

            $this->json([
                'success' => true,
                'opportunities' => $opportunities
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to get career opportunities: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user skills profile
     */
    public function updateSkills(array $params = []): void {
        $userId = $this->getUserId();

        $skillsData = json_decode(file_get_contents('php://input'), true);

        if (!$skillsData) {
            $this->json(['error' => 'Invalid skills data'], 400);
            return;
        }

        try {
            // Store skills in user settings
            $this->db->insert('user_settings', [
                'user_id' => $userId,
                'category' => 'profile',
                'setting_key' => 'skills',
                'setting_value' => json_encode($skillsData),
                'updated_at' => date('Y-m-d H:i:s')
            ], true); // Upsert

            $this->json([
                'success' => true,
                'message' => 'Skills profile updated successfully'
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to update skills profile: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user skills profile
     */
    public function getSkills(array $params = []): void {
        $userId = $this->getUserId();

        try {
            $skillsSetting = $this->db->queryOne(
                "SELECT setting_value FROM user_settings
                 WHERE user_id = ? AND category = 'profile' AND setting_key = 'skills'",
                [$userId]
            );

            $skills = [];
            if ($skillsSetting && $skillsSetting['setting_value']) {
                $skills = json_decode($skillsSetting['setting_value'], true) ?: [];
            }

            $this->json([
                'success' => true,
                'skills' => $skills
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to get skills profile: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get salary data for specific role and region
     */
    public function getSalaryData(array $params = []): void {
        $role = $this->getQueryParam('role');
        $region = $this->getQueryParam('region', 'prague');

        if (!$role) {
            $this->json(['error' => 'Role parameter is required'], 400);
            return;
        }

        try {
            $userId = $this->getUserId();
            $assessment = $this->careerService->assessSkills($userId);

            $salaryData = [
                'role' => $role,
                'region' => $region,
                'salary_range' => $assessment['salary_projections'][$region] ?? null,
                'market_comparison' => $this->getMarketComparison($role, $region),
                'growth_potential' => $this->getSalaryGrowthProjection($role, $region)
            ];

            $this->json([
                'success' => true,
                'salary_data' => $salaryData
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to get salary data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get learning path recommendations
     */
    public function getLearningPath(array $params = []): void {
        $userId = $this->getUserId();
        $targetRole = $this->getQueryParam('target_role');

        try {
            $assessment = $this->careerService->assessSkills($userId);

            $learningPath = [
                'current_level' => $assessment['current_level'],
                'target_role' => $targetRole,
                'recommended_path' => $assessment['learning_path'],
                'skill_gaps' => $assessment['skill_gaps'],
                'estimated_time' => $this->calculatePathDuration($assessment['learning_path']),
                'resources' => $this->getRecommendedResources($targetRole)
            ];

            $this->json([
                'success' => true,
                'learning_path' => $learningPath
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to get learning path: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get job market insights
     */
    public function getMarketInsights(array $params = []): void {
        $region = $this->getQueryParam('region', 'prague');

        try {
            $userId = $this->getUserId();
            $assessment = $this->careerService->assessSkills($userId);

            $insights = [
                'region' => $region,
                'high_demand_roles' => $this->getHighDemandRoles($region),
                'salary_trends' => $this->getSalaryTrends($region),
                'remote_work_opportunities' => $this->getRemoteWorkData($region),
                'visa_requirements' => $this->getVisaRequirements($region),
                'market_demand' => $assessment['market_demand']
            ];

            $this->json([
                'success' => true,
                'insights' => $insights
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to get market insights: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper methods
     */
    private function getMarketComparison(string $role, string $region): array {
        // Simplified market comparison
        return [
            'regional_average' => 'Average for ' . ucfirst($region),
            'national_average' => 'Czech Republic average',
            'percentile_25' => '25th percentile',
            'percentile_75' => '75th percentile',
            'compared_to_last_year' => '+8%'
        ];
    }

    private function getSalaryGrowthProjection(string $role, string $region): array {
        return [
            'next_1_year' => '+5-8%',
            'next_3_years' => '+15-25%',
            'next_5_years' => '+30-40%',
            'factors' => ['experience', 'certifications', 'market_demand']
        ];
    }

    private function calculatePathDuration(array $learningPath): string {
        $totalWeeks = 0;
        foreach ($learningPath as $step) {
            // Extract weeks from duration string (e.g., "4 weeks" -> 4)
            if (preg_match('/(\d+)/', $step['duration'], $matches)) {
                $totalWeeks += (int)$matches[1];
            }
        }

        if ($totalWeeks <= 12) {
            return $totalWeeks . ' weeks';
        } else {
            $months = ceil($totalWeeks / 4.33); // Average weeks per month
            return $months . ' months';
        }
    }

    private function getRecommendedResources(?string $targetRole): array {
        $resources = [
            'general' => [
                ['name' => 'Coursera', 'type' => 'platform', 'focus' => 'Professional certificates'],
                ['name' => 'Udemy', 'type' => 'platform', 'focus' => 'Practical skills'],
                ['name' => 'LinkedIn Learning', 'type' => 'platform', 'focus' => 'Career development'],
                ['name' => 'freeCodeCamp', 'type' => 'platform', 'focus' => 'Free coding tutorials']
            ],
            'ai_engineer' => [
                ['name' => 'Andrew Ng\'s Machine Learning Course', 'type' => 'course', 'focus' => 'ML fundamentals'],
                ['name' => 'Fast.ai', 'type' => 'platform', 'focus' => 'Practical deep learning'],
                ['name' => 'Hugging Face', 'type' => 'platform', 'focus' => 'NLP and transformers']
            ],
            'data_scientist' => [
                ['name' => 'Data Science Specialization (Coursera)', 'type' => 'course', 'focus' => 'Data science fundamentals'],
                ['name' => 'Kaggle', 'type' => 'platform', 'focus' => 'Competitions and datasets'],
                ['name' => 'Towards Data Science', 'type' => 'blog', 'focus' => 'Industry insights']
            ]
        ];

        return $resources[$targetRole] ?? $resources['general'];
    }

    private function getHighDemandRoles(string $region): array {
        $regionalDemand = [
            'prague' => ['ai_engineer', 'data_scientist', 'devops_engineer', 'full_stack_developer'],
            'berlin' => ['ai_engineer', 'blockchain_developer', 'cloud_engineer', 'data_scientist'],
            'amsterdam' => ['ai_engineer', 'data_scientist', 'cloud_engineer', 'cybersecurity']
        ];

        return $regionalDemand[$region] ?? ['ai_engineer', 'data_scientist'];
    }

    private function getSalaryTrends(string $region): array {
        return [
            'ai_engineer' => ['growth' => '+15%', 'demand' => 'High', 'competition' => 'Medium'],
            'data_scientist' => ['growth' => '+12%', 'demand' => 'High', 'competition' => 'Medium'],
            'devops_engineer' => ['growth' => '+10%', 'demand' => 'High', 'competition' => 'Low'],
            'full_stack_developer' => ['growth' => '+8%', 'demand' => 'Medium', 'competition' => 'High']
        ];
    }

    private function getRemoteWorkData(string $region): array {
        $remoteData = [
            'prague' => ['remote_friendly' => 75, 'hybrid_options' => 85, 'full_remote_jobs' => 60],
            'berlin' => ['remote_friendly' => 80, 'hybrid_options' => 90, 'full_remote_jobs' => 70],
            'amsterdam' => ['remote_friendly' => 85, 'hybrid_options' => 95, 'full_remote_jobs' => 75]
        ];

        return $remoteData[$region] ?? ['remote_friendly' => 70, 'hybrid_options' => 80, 'full_remote_jobs' => 50];
    }

    private function getVisaRequirements(string $region): array {
        if (in_array($region, ['prague', 'brno', 'ostrava'])) {
            return [
                'work_permit' => 'Required for non-EU citizens',
                'blue_card' => 'Available for highly skilled workers',
                'job_seeker_visa' => '90 days to find employment',
                'eu_citizens' => 'No restrictions'
            ];
        }

        return [
            'work_permit' => 'Required for non-EU citizens',
            'blue_card' => 'EU Blue Card available',
            'job_seeker_visa' => 'Available in most EU countries',
            'eu_citizens' => 'Free movement within EU'
        ];
    }
}