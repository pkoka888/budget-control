<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

class CareerService {
    private Database $db;
    private array $salaryData;

    public function __construct(Database $db) {
        $this->db = $db;
        $this->loadSalaryData();
    }

    /**
     * Load salary data for different roles and regions
     */
    private function loadSalaryData(): void {
        // Czech Republic salary data (in CZK, monthly averages)
        $this->salaryData = [
            'prague' => [
                'junior_developer' => ['min' => 35000, 'avg' => 45000, 'max' => 60000],
                'mid_developer' => ['min' => 50000, 'avg' => 65000, 'max' => 85000],
                'senior_developer' => ['min' => 70000, 'avg' => 90000, 'max' => 120000],
                'tech_lead' => ['min' => 85000, 'avg' => 110000, 'max' => 150000],
                'engineering_manager' => ['min' => 95000, 'avg' => 130000, 'max' => 180000],
                'ai_engineer' => ['min' => 60000, 'avg' => 80000, 'max' => 110000],
                'data_scientist' => ['min' => 55000, 'avg' => 75000, 'max' => 100000],
                'devops_engineer' => ['min' => 55000, 'avg' => 75000, 'max' => 100000]
            ],
            'brno' => [
                'junior_developer' => ['min' => 30000, 'avg' => 38000, 'max' => 50000],
                'mid_developer' => ['min' => 42000, 'avg' => 55000, 'max' => 72000],
                'senior_developer' => ['min' => 60000, 'avg' => 75000, 'max' => 95000],
                'tech_lead' => ['min' => 70000, 'avg' => 90000, 'max' => 120000],
                'engineering_manager' => ['min' => 80000, 'avg' => 105000, 'max' => 140000],
                'ai_engineer' => ['min' => 50000, 'avg' => 65000, 'max' => 85000],
                'data_scientist' => ['min' => 48000, 'avg' => 62000, 'max' => 80000],
                'devops_engineer' => ['min' => 48000, 'avg' => 62000, 'max' => 80000]
            ],
            'ostrava' => [
                'junior_developer' => ['min' => 25000, 'avg' => 32000, 'max' => 42000],
                'mid_developer' => ['min' => 35000, 'avg' => 45000, 'max' => 58000],
                'senior_developer' => ['min' => 48000, 'avg' => 60000, 'max' => 75000],
                'tech_lead' => ['min' => 55000, 'avg' => 70000, 'max' => 90000],
                'engineering_manager' => ['min' => 65000, 'avg' => 85000, 'max' => 110000],
                'ai_engineer' => ['min' => 42000, 'avg' => 55000, 'max' => 70000],
                'data_scientist' => ['min' => 40000, 'avg' => 52000, 'max' => 65000],
                'devops_engineer' => ['min' => 40000, 'avg' => 52000, 'max' => 65000]
            ],
            'berlin' => [
                'junior_developer' => ['min' => 3500, 'avg' => 4500, 'max' => 5500], // EUR
                'mid_developer' => ['min' => 4500, 'avg' => 6000, 'max' => 7500],
                'senior_developer' => ['min' => 6500, 'avg' => 8500, 'max' => 11000],
                'tech_lead' => ['min' => 7500, 'avg' => 10000, 'max' => 13000],
                'engineering_manager' => ['min' => 8500, 'avg' => 12000, 'max' => 16000],
                'ai_engineer' => ['min' => 5500, 'avg' => 7500, 'max' => 9500],
                'data_scientist' => ['min' => 5000, 'avg' => 7000, 'max' => 9000],
                'devops_engineer' => ['min' => 5500, 'avg' => 7500, 'max' => 9500]
            ],
            'amsterdam' => [
                'junior_developer' => ['min' => 2800, 'avg' => 3500, 'max' => 4500],
                'mid_developer' => ['min' => 4000, 'avg' => 5500, 'max' => 7000],
                'senior_developer' => ['min' => 6000, 'avg' => 8000, 'max' => 10000],
                'tech_lead' => ['min' => 7000, 'avg' => 9500, 'max' => 12000],
                'engineering_manager' => ['min' => 8000, 'avg' => 11000, 'max' => 14000],
                'ai_engineer' => ['min' => 5500, 'avg' => 7500, 'max' => 9500],
                'data_scientist' => ['min' => 5000, 'avg' => 7000, 'max' => 9000],
                'devops_engineer' => ['min' => 5500, 'avg' => 7500, 'max' => 9500]
            ]
        ];
    }

    /**
     * Assess user skills and provide career recommendations
     */
    public function assessSkills(int $userId): array {
        $userSkills = $this->getUserSkills($userId);
        $assessment = [
            'current_level' => $this->determineSkillLevel($userSkills),
            'skill_gaps' => $this->identifySkillGaps($userSkills),
            'recommended_roles' => $this->getRecommendedRoles($userSkills),
            'salary_projections' => $this->getSalaryProjections($userSkills),
            'learning_path' => $this->generateLearningPath($userSkills),
            'market_demand' => $this->getMarketDemandData()
        ];

        return $assessment;
    }

    /**
     * Get user skills from profile/settings
     */
    private function getUserSkills(int $userId): array {
        // Get skills from user settings
        $skillsSetting = $this->db->queryOne(
            "SELECT setting_value FROM user_settings
             WHERE user_id = ? AND category = 'profile' AND setting_key = 'skills'",
            [$userId]
        );

        $skills = [];
        if ($skillsSetting && $skillsSetting['setting_value']) {
            $skills = json_decode($skillsSetting['setting_value'], true) ?: [];
        }

        // Default skills if none set
        if (empty($skills)) {
            $skills = [
                'programming_languages' => ['php', 'javascript'],
                'frameworks' => ['laravel', 'react'],
                'tools' => ['git', 'docker'],
                'experience_years' => 3,
                'current_role' => 'developer',
                'preferred_regions' => ['prague', 'brno'],
                'willing_to_relocate' => false,
                'remote_work_preference' => 'hybrid'
            ];
        }

        return $skills;
    }

    /**
     * Determine skill level based on experience and skills
     */
    private function determineSkillLevel(array $skills): string {
        $experience = $skills['experience_years'] ?? 0;
        $languages = count($skills['programming_languages'] ?? []);
        $frameworks = count($skills['frameworks'] ?? []);

        if ($experience >= 5 && $languages >= 3 && $frameworks >= 2) {
            return 'senior';
        } elseif ($experience >= 2 && $languages >= 2) {
            return 'mid';
        } else {
            return 'junior';
        }
    }

    /**
     * Identify skill gaps for career advancement
     */
    private function identifySkillGaps(array $skills): array {
        $gaps = [];
        $currentSkills = array_merge(
            $skills['programming_languages'] ?? [],
            $skills['frameworks'] ?? [],
            $skills['tools'] ?? []
        );

        $requiredSkills = [
            'senior' => ['python', 'typescript', 'aws', 'kubernetes', 'machine_learning'],
            'mid' => ['typescript', 'node.js', 'sql', 'testing', 'api_design'],
            'junior' => ['git', 'sql', 'testing', 'html', 'css']
        ];

        $level = $this->determineSkillLevel($skills);
        $required = $requiredSkills[$level] ?? [];

        foreach ($required as $skill) {
            if (!in_array($skill, $currentSkills)) {
                $gaps[] = [
                    'skill' => $skill,
                    'priority' => 'high',
                    'time_to_learn' => $this->estimateLearningTime($skill)
                ];
            }
        }

        return $gaps;
    }

    /**
     * Get recommended roles based on skills
     */
    private function getRecommendedRoles(array $skills): array {
        $level = $this->determineSkillLevel($skills);
        $roles = [];

        $roleMapping = [
            'junior' => ['junior_developer'],
            'mid' => ['mid_developer', 'ai_engineer', 'devops_engineer'],
            'senior' => ['senior_developer', 'tech_lead', 'engineering_manager', 'data_scientist']
        ];

        $possibleRoles = $roleMapping[$level] ?? ['junior_developer'];

        foreach ($possibleRoles as $role) {
            $roles[] = [
                'role' => $role,
                'match_score' => rand(70, 95), // Simplified scoring
                'requirements' => $this->getRoleRequirements($role),
                'growth_potential' => $this->getRoleGrowthPotential($role)
            ];
        }

        return $roles;
    }

    /**
     * Get salary projections for different roles and regions
     */
    private function getSalaryProjections(array $skills): array {
        $level = $this->determineSkillLevel($skills);
        $regions = $skills['preferred_regions'] ?? ['prague'];

        $projections = [];

        foreach ($regions as $region) {
            if (!isset($this->salaryData[$region])) continue;

            $regionData = $this->salaryData[$region];
            $roleKey = $level . '_developer'; // Default mapping

            if (isset($regionData[$roleKey])) {
                $salary = $regionData[$roleKey];
                $projections[$region] = [
                    'current_level' => $level,
                    'salary_range' => $salary,
                    'currency' => $this->getRegionCurrency($region),
                    'cost_of_living_adjustment' => $this->getCostOfLivingAdjustment($region),
                    'tax_impact' => $this->estimateTaxes($salary['avg'], $region)
                ];
            }
        }

        return $projections;
    }

    /**
     * Generate personalized learning path
     */
    private function generateLearningPath(array $skills): array {
        $level = $this->determineSkillLevel($skills);
        $paths = [
            'junior' => [
                ['skill' => 'Advanced JavaScript/TypeScript', 'duration' => '4 weeks', 'resources' => ['Udemy', 'MDN']],
                ['skill' => 'Database Design', 'duration' => '3 weeks', 'resources' => ['SQLZoo', 'PostgreSQL docs']],
                ['skill' => 'Testing Fundamentals', 'duration' => '2 weeks', 'resources' => ['Jest docs', 'Testing Library']],
                ['skill' => 'API Design', 'duration' => '3 weeks', 'resources' => ['REST API docs', 'GraphQL']]
            ],
            'mid' => [
                ['skill' => 'System Design', 'duration' => '6 weeks', 'resources' => ['System Design Primer', 'Grokking']],
                ['skill' => 'Cloud Platforms', 'duration' => '8 weeks', 'resources' => ['AWS', 'Azure', 'GCP']],
                ['skill' => 'Container Orchestration', 'duration' => '4 weeks', 'resources' => ['Kubernetes docs']],
                ['skill' => 'AI/ML Basics', 'duration' => '6 weeks', 'resources' => ['Coursera', 'fast.ai']]
            ],
            'senior' => [
                ['skill' => 'Leadership & Management', 'duration' => '8 weeks', 'resources' => ['Manager Tools', 'Leading Teams']],
                ['skill' => 'Advanced AI/ML', 'duration' => '12 weeks', 'resources' => ['Deep Learning specialization']],
                ['skill' => 'Architecture Patterns', 'duration' => '6 weeks', 'resources' => ['Clean Architecture', 'DDD']],
                ['skill' => 'Technical Writing', 'duration' => '4 weeks', 'resources' => ['Technical Writing courses']]
            ]
        ];

        return $paths[$level] ?? $paths['junior'];
    }

    /**
     * Get market demand data
     */
    private function getMarketDemandData(): array {
        return [
            'high_demand_skills' => ['ai', 'machine_learning', 'cloud', 'cybersecurity', 'data_engineering'],
            'emerging_trends' => ['ai_engineering', 'blockchain', 'iot', 'quantum_computing'],
            'regional_demand' => [
                'prague' => ['ai', 'fintech', 'cybersecurity'],
                'berlin' => ['fintech', 'ai', 'blockchain'],
                'amsterdam' => ['ai', 'data_science', 'cloud']
            ],
            'salary_growth_trends' => [
                'ai_engineer' => 15, // 15% annual growth
                'data_scientist' => 12,
                'cloud_engineer' => 10,
                'cybersecurity' => 18
            ]
        ];
    }

    /**
     * Helper methods
     */
    private function estimateLearningTime(string $skill): string {
        $timeEstimates = [
            'python' => '4-6 weeks',
            'typescript' => '2-3 weeks',
            'aws' => '6-8 weeks',
            'kubernetes' => '4-6 weeks',
            'machine_learning' => '8-12 weeks',
            'sql' => '1-2 weeks',
            'testing' => '2-3 weeks',
            'git' => '1 week'
        ];

        return $timeEstimates[$skill] ?? '4-6 weeks';
    }

    private function getRoleRequirements(string $role): array {
        $requirements = [
            'junior_developer' => ['Basic programming', 'Version control', 'Database basics'],
            'mid_developer' => ['2+ years experience', 'Multiple languages', 'API development'],
            'senior_developer' => ['5+ years experience', 'Architecture design', 'Mentoring'],
            'tech_lead' => ['Technical leadership', 'Project management', 'Code reviews'],
            'ai_engineer' => ['Python', 'ML frameworks', 'Data processing'],
            'data_scientist' => ['Statistics', 'Python/R', 'Data visualization']
        ];

        return $requirements[$role] ?? [];
    }

    private function getRoleGrowthPotential(string $role): array {
        $growth = [
            'junior_developer' => ['promotion_timeline' => '2-3 years', 'salary_increase' => 30],
            'mid_developer' => ['promotion_timeline' => '2-4 years', 'salary_increase' => 25],
            'senior_developer' => ['promotion_timeline' => '3-5 years', 'salary_increase' => 20],
            'tech_lead' => ['promotion_timeline' => '2-3 years', 'salary_increase' => 25],
            'ai_engineer' => ['promotion_timeline' => '2-4 years', 'salary_increase' => 35],
            'data_scientist' => ['promotion_timeline' => '2-4 years', 'salary_increase' => 30]
        ];

        return $growth[$role] ?? ['promotion_timeline' => '3 years', 'salary_increase' => 25];
    }

    private function getRegionCurrency(string $region): string {
        return in_array($region, ['berlin', 'amsterdam']) ? 'EUR' : 'CZK';
    }

    private function getCostOfLivingAdjustment(string $region): float {
        $adjustments = [
            'prague' => 1.0,
            'brno' => 0.85,
            'ostrava' => 0.75,
            'berlin' => 1.15,
            'amsterdam' => 1.25
        ];

        return $adjustments[$region] ?? 1.0;
    }

    private function estimateTaxes(float $salary, string $region): array {
        // Simplified tax estimation for Czech Republic
        if (in_array($region, ['prague', 'brno', 'ostrava'])) {
            $taxRate = 0.20; // 20% income tax
            $socialInsurance = 0.065; // 6.5% social insurance
            $healthInsurance = 0.045; // 4.5% health insurance

            $totalTaxRate = $taxRate + $socialInsurance + $healthInsurance;
            $takeHome = $salary * (1 - $totalTaxRate);

            return [
                'total_tax_rate' => $totalTaxRate * 100,
                'take_home_monthly' => round($takeHome, 2),
                'breakdown' => [
                    'income_tax' => $taxRate * 100,
                    'social_insurance' => $socialInsurance * 100,
                    'health_insurance' => $healthInsurance * 100
                ]
            ];
        }

        // EU rates (simplified)
        return [
            'total_tax_rate' => 35,
            'take_home_monthly' => round($salary * 0.65, 2),
            'note' => 'Simplified EU tax estimation'
        ];
    }

    /**
     * Get career opportunities based on skills and preferences
     */
    public function getCareerOpportunities(int $userId): array {
        $skills = $this->getUserSkills($userId);
        $assessment = $this->assessSkills($userId);

        $opportunities = [
            'recommended_roles' => $assessment['recommended_roles'],
            'salary_projections' => $assessment['salary_projections'],
            'skill_development' => $assessment['learning_path'],
            'market_insights' => $assessment['market_demand'],
            'next_steps' => $this->generateNextSteps($assessment)
        ];

        return $opportunities;
    }

    /**
     * Generate actionable next steps
     */
    private function generateNextSteps(array $assessment): array {
        $steps = [];

        // Skill gaps
        if (!empty($assessment['skill_gaps'])) {
            $topGap = $assessment['skill_gaps'][0];
            $steps[] = [
                'action' => 'Learn ' . $topGap['skill'],
                'timeline' => $topGap['time_to_learn'],
                'impact' => 'High',
                'type' => 'skill_development'
            ];
        }

        // Salary optimization
        $steps[] = [
            'action' => 'Research salary ranges in target regions',
            'timeline' => '1-2 weeks',
            'impact' => 'Medium',
            'type' => 'research'
        ];

        // Networking
        $steps[] = [
            'action' => 'Connect with professionals in target roles',
            'timeline' => 'Ongoing',
            'impact' => 'High',
            'type' => 'networking'
        ];

        // Certification
        $steps[] = [
            'action' => 'Consider relevant certifications',
            'timeline' => '3-6 months',
            'impact' => 'Medium',
            'type' => 'certification'
        ];

        return $steps;
    }
}