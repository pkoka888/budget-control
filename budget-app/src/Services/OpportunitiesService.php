<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

class OpportunitiesService {
    private Database $db;
    private array $opportunitiesData;

    public function __construct(Database $db) {
        $this->db = $db;
        $this->loadOpportunitiesData();
    }

    /**
     * Load curated opportunities data
     */
    private function loadOpportunitiesData(): void {
        $this->opportunitiesData = [
            'learning_paths' => [
                [
                    'id' => 'ai_fundamentals',
                    'title' => 'AI Fundamentals for Developers',
                    'description' => 'Master the basics of AI and machine learning for software development',
                    'duration' => '8 weeks',
                    'difficulty' => 'intermediate',
                    'prerequisites' => ['programming', 'math'],
                    'resources' => [
                        ['name' => 'Coursera: Machine Learning by Andrew Ng', 'url' => 'https://www.coursera.org/learn/machine-learning', 'type' => 'course', 'cost' => 'free'],
                        ['name' => 'Fast.ai Practical Deep Learning', 'url' => 'https://course.fast.ai/', 'type' => 'course', 'cost' => 'free'],
                        ['name' => 'Python for Data Science Handbook', 'url' => 'https://jakevdp.github.io/PythonDataScienceHandbook/', 'type' => 'book', 'cost' => 'free']
                    ],
                    'skills_gained' => ['machine_learning', 'python', 'data_analysis'],
                    'career_impact' => 'high'
                ],
                [
                    'id' => 'cloud_architecture',
                    'title' => 'Cloud Architecture & DevOps',
                    'description' => 'Design scalable cloud systems and master DevOps practices',
                    'duration' => '12 weeks',
                    'difficulty' => 'advanced',
                    'prerequisites' => ['programming', 'linux'],
                    'resources' => [
                        ['name' => 'AWS Solutions Architect Associate', 'url' => 'https://aws.amazon.com/certification/certified-solutions-architect-associate/', 'type' => 'certification', 'cost' => '$150'],
                        ['name' => 'Kubernetes Fundamentals', 'url' => 'https://www.edx.org/course/introduction-to-kubernetes', 'type' => 'course', 'cost' => 'free'],
                        ['name' => 'Terraform Getting Started', 'url' => 'https://learn.hashicorp.com/terraform', 'type' => 'tutorial', 'cost' => 'free']
                    ],
                    'skills_gained' => ['aws', 'kubernetes', 'terraform', 'devops'],
                    'career_impact' => 'high'
                ],
                [
                    'id' => 'fullstack_modern',
                    'title' => 'Modern Full-Stack Development',
                    'description' => 'Build modern web applications with React, Node.js, and cloud services',
                    'duration' => '16 weeks',
                    'difficulty' => 'intermediate',
                    'prerequisites' => ['javascript', 'html', 'css'],
                    'resources' => [
                        ['name' => 'The Odin Project', 'url' => 'https://www.theodinproject.com/', 'type' => 'curriculum', 'cost' => 'free'],
                        ['name' => 'React Documentation', 'url' => 'https://reactjs.org/docs/getting-started.html', 'type' => 'documentation', 'cost' => 'free'],
                        ['name' => 'Node.js Best Practices', 'url' => 'https://github.com/goldbergyoni/nodebestpractices', 'type' => 'guide', 'cost' => 'free']
                    ],
                    'skills_gained' => ['react', 'nodejs', 'mongodb', 'express'],
                    'career_impact' => 'medium'
                ]
            ],
            'job_opportunities' => [
                [
                    'id' => 'ai_engineer_prague',
                    'title' => 'AI Engineer',
                    'company' => 'TechCorp Prague',
                    'location' => 'Prague, Czech Republic',
                    'salary_range' => ['min' => 80000, 'max' => 120000, 'currency' => 'CZK'],
                    'type' => 'full-time',
                    'remote' => 'hybrid',
                    'requirements' => ['python', 'machine_learning', 'tensorflow', '3+ years experience'],
                    'benefits' => ['health_insurance', 'flexible_hours', 'training_budget'],
                    'application_deadline' => '2025-12-31',
                    'match_score' => 85
                ],
                [
                    'id' => 'senior_devops_berlin',
                    'title' => 'Senior DevOps Engineer',
                    'company' => 'CloudTech Berlin',
                    'location' => 'Berlin, Germany',
                    'salary_range' => ['min' => 6500, 'max' => 8500, 'currency' => 'EUR'],
                    'type' => 'full-time',
                    'remote' => 'remote',
                    'requirements' => ['kubernetes', 'aws', 'terraform', '5+ years experience'],
                    'benefits' => ['visa_sponsorship', 'relocation_package', 'stock_options'],
                    'application_deadline' => '2025-11-30',
                    'match_score' => 78
                ],
                [
                    'id' => 'ml_engineer_amsterdam',
                    'title' => 'Machine Learning Engineer',
                    'company' => 'DataFlow Amsterdam',
                    'location' => 'Amsterdam, Netherlands',
                    'salary_range' => ['min' => 5500, 'max' => 7500, 'currency' => 'EUR'],
                    'type' => 'full-time',
                    'remote' => 'hybrid',
                    'requirements' => ['python', 'pytorch', 'mlflow', '2+ years experience'],
                    'benefits' => ['conference_budget', 'flexible_hours', 'learning_stipend'],
                    'application_deadline' => '2025-12-15',
                    'match_score' => 82
                ]
            ],
            'freelance_gigs' => [
                [
                    'id' => 'ai_chatbot_development',
                    'title' => 'AI Chatbot Development for E-commerce',
                    'platform' => 'Upwork',
                    'budget_range' => ['min' => 2000, 'max' => 5000, 'currency' => 'USD'],
                    'duration' => '4-6 weeks',
                    'skills_required' => ['python', 'nlp', 'api_integration'],
                    'difficulty' => 'intermediate',
                    'client_rating' => 4.8,
                    'posted_date' => '2025-11-01'
                ],
                [
                    'id' => 'data_pipeline_optimization',
                    'title' => 'Data Pipeline Optimization',
                    'platform' => 'Toptal',
                    'budget_range' => ['min' => 3000, 'max' => 8000, 'currency' => 'USD'],
                    'duration' => '6-8 weeks',
                    'skills_required' => ['python', 'apache_airflow', 'aws', 'sql'],
                    'difficulty' => 'advanced',
                    'client_rating' => 4.9,
                    'posted_date' => '2025-10-28'
                ],
                [
                    'id' => 'automation_script_creation',
                    'title' => 'Business Process Automation Scripts',
                    'platform' => 'Fiverr',
                    'budget_range' => ['min' => 500, 'max' => 2000, 'currency' => 'USD'],
                    'duration' => '1-2 weeks',
                    'skills_required' => ['python', 'selenium', 'api_automation'],
                    'difficulty' => 'beginner',
                    'client_rating' => 4.7,
                    'posted_date' => '2025-11-05'
                ]
            ],
            'courses_and_certifications' => [
                [
                    'id' => 'aws_solutions_architect',
                    'title' => 'AWS Solutions Architect Associate',
                    'provider' => 'Amazon Web Services',
                    'duration' => '3 months study',
                    'cost' => 150,
                    'currency' => 'USD',
                    'exam_fee' => 150,
                    'validity_years' => 3,
                    'difficulty' => 'intermediate',
                    'prerequisites' => ['basic_cloud_knowledge'],
                    'career_boost' => 'high'
                ],
                [
                    'id' => 'google_data_engineer',
                    'title' => 'Google Cloud Professional Data Engineer',
                    'provider' => 'Google Cloud',
                    'duration' => '4 months study',
                    'cost' => 0, // Free resources available
                    'currency' => 'USD',
                    'exam_fee' => 200,
                    'validity_years' => 2,
                    'difficulty' => 'advanced',
                    'prerequisites' => ['sql', 'python', 'data_warehousing'],
                    'career_boost' => 'high'
                ],
                [
                    'id' => 'cka_certification',
                    'title' => 'Certified Kubernetes Administrator (CKA)',
                    'provider' => 'Cloud Native Computing Foundation',
                    'duration' => '2 months study',
                    'cost' => 0,
                    'currency' => 'USD',
                    'exam_fee' => 375,
                    'validity_years' => 2,
                    'difficulty' => 'advanced',
                    'prerequisites' => ['kubernetes_basics', 'linux', 'networking'],
                    'career_boost' => 'high'
                ]
            ],
            'networking_events' => [
                [
                    'id' => 'prague_ai_meetup',
                    'title' => 'Prague AI & Machine Learning Meetup',
                    'location' => 'Prague, Czech Republic',
                    'date' => '2025-11-20',
                    'type' => 'meetup',
                    'cost' => 0,
                    'currency' => 'CZK',
                    'expected_attendees' => 150,
                    'topics' => ['ai', 'machine_learning', 'career_development'],
                    'organizer' => 'Prague AI Community'
                ],
                [
                    'id' => 'berlin_tech_conference',
                    'title' => 'Berlin Tech Conference 2025',
                    'location' => 'Berlin, Germany',
                    'date' => '2025-12-05',
                    'type' => 'conference',
                    'cost' => 150,
                    'currency' => 'EUR',
                    'expected_attendees' => 800,
                    'topics' => ['cloud', 'ai', 'blockchain', 'career'],
                    'organizer' => 'Berlin Tech Hub'
                ],
                [
                    'id' => 'online_webinar_series',
                    'title' => 'European Tech Leaders Webinar Series',
                    'location' => 'Online',
                    'date' => '2025-11-15', // First session
                    'type' => 'webinar',
                    'cost' => 0,
                    'currency' => 'EUR',
                    'expected_attendees' => 500,
                    'topics' => ['leadership', 'innovation', 'remote_work'],
                    'organizer' => 'European Tech Association'
                ]
            ]
        ];
    }

    /**
     * Get personalized opportunities dashboard
     */
    public function getOpportunitiesDashboard(int $userId): array {
        $userSkills = $this->getUserSkills($userId);
        $careerAssessment = $this->getCareerAssessment($userId);

        $dashboard = [
            'personalized_learning' => $this->getPersonalizedLearningPaths($userSkills),
            'recommended_jobs' => $this->getRecommendedJobs($userSkills, $careerAssessment),
            'freelance_opportunities' => $this->getFreelanceOpportunities($userSkills),
            'upcoming_events' => $this->getUpcomingEvents($userSkills),
            'certifications' => $this->getRecommendedCertifications($userSkills),
            'market_insights' => $this->getMarketInsights($userSkills),
            'next_steps' => $this->generateNextSteps($userSkills, $careerAssessment)
        ];

        return $dashboard;
    }

    /**
     * Get personalized learning paths based on user skills
     */
    private function getPersonalizedLearningPaths(array $userSkills): array {
        $skillLevel = $this->determineSkillLevel($userSkills);
        $userSkillSet = $userSkills['skills'] ?? [];

        $recommendedPaths = [];

        foreach ($this->opportunitiesData['learning_paths'] as $path) {
            $matchScore = $this->calculatePathMatchScore($path, $userSkillSet, $skillLevel);

            if ($matchScore >= 60) { // Only show relevant paths
                $path['match_score'] = $matchScore;
                $path['estimated_completion'] = $this->estimateCompletionTime($path, $userSkillSet);
                $recommendedPaths[] = $path;
            }
        }

        // Sort by match score
        usort($recommendedPaths, fn($a, $b) => $b['match_score'] <=> $a['match_score']);

        return array_slice($recommendedPaths, 0, 5); // Top 5 recommendations
    }

    /**
     * Get recommended jobs based on skills and career goals
     */
    private function getRecommendedJobs(array $userSkills, array $careerAssessment): array {
        $userSkillSet = $userSkills['skills'] ?? [];
        $preferredRegions = $userSkills['preferred_regions'] ?? ['prague'];
        $willingToRelocate = $userSkills['willing_to_relocate'] ?? false;

        $recommendedJobs = [];

        foreach ($this->opportunitiesData['job_opportunities'] as $job) {
            // Check region preference
            $jobRegion = strtolower(str_replace(' ', '', explode(',', $job['location'])[0]));
            if (!in_array($jobRegion, $preferredRegions) && !$willingToRelocate) {
                continue;
            }

            $matchScore = $this->calculateJobMatchScore($job, $userSkillSet, $careerAssessment);

            if ($matchScore >= 70) { // Only show good matches
                $job['match_score'] = $matchScore;
                $job['application_tips'] = $this->generateApplicationTips($job, $userSkillSet);
                $recommendedJobs[] = $job;
            }
        }

        // Sort by match score
        usort($recommendedJobs, fn($a, $b) => $b['match_score'] <=> $a['match_score']);

        return array_slice($recommendedJobs, 0, 3); // Top 3 recommendations
    }

    /**
     * Get freelance opportunities
     */
    private function getFreelanceOpportunities(array $userSkills): array {
        $userSkillSet = $userSkills['skills'] ?? [];

        $opportunities = [];

        foreach ($this->opportunitiesData['freelance_gigs'] as $gig) {
            $matchScore = $this->calculateGigMatchScore($gig, $userSkillSet);

            if ($matchScore >= 60) {
                $gig['match_score'] = $matchScore;
                $gig['estimated_earnings'] = $this->estimateGigEarnings($gig, $userSkillSet);
                $opportunities[] = $gig;
            }
        }

        // Sort by match score and recency
        usort($opportunities, function($a, $b) {
            if ($a['match_score'] === $b['match_score']) {
                return strtotime($b['posted_date']) <=> strtotime($a['posted_date']);
            }
            return $b['match_score'] <=> $a['match_score'];
        });

        return array_slice($opportunities, 0, 4); // Top 4 opportunities
    }

    /**
     * Get upcoming networking events
     */
    private function getUpcomingEvents(array $userSkills): array {
        $preferredRegions = $userSkills['preferred_regions'] ?? ['prague'];
        $interests = $userSkills['interests'] ?? ['technology'];

        $events = [];

        foreach ($this->opportunitiesData['networking_events'] as $event) {
            // Check if event is upcoming
            if (strtotime($event['date']) < time()) {
                continue;
            }

            // Check region preference
            $eventRegion = strtolower(str_replace(' ', '', explode(',', $event['location'])[0]));
            $regionMatch = in_array($eventRegion, $preferredRegions) || $event['location'] === 'Online';

            // Check topic relevance
            $topicMatch = !empty(array_intersect($event['topics'], $interests));

            if ($regionMatch && $topicMatch) {
                $event['days_until'] = floor((strtotime($event['date']) - time()) / (60 * 60 * 24));
                $events[] = $event;
            }
        }

        // Sort by date
        usort($events, fn($a, $b) => strtotime($a['date']) <=> strtotime($b['date']));

        return array_slice($events, 0, 3); // Next 3 events
    }

    /**
     * Get recommended certifications
     */
    private function getRecommendedCertifications(array $userSkills): array {
        $userSkillSet = $userSkills['skills'] ?? [];
        $skillLevel = $this->determineSkillLevel($userSkills);

        $certifications = [];

        foreach ($this->opportunitiesData['courses_and_certifications'] as $cert) {
            $matchScore = $this->calculateCertificationMatchScore($cert, $userSkillSet, $skillLevel);

            if ($matchScore >= 65) {
                $cert['match_score'] = $matchScore;
                $cert['roi_estimate'] = $this->estimateCertificationROI($cert, $userSkillSet);
                $certifications[] = $cert;
            }
        }

        // Sort by match score
        usort($certifications, fn($a, $b) => $b['match_score'] <=> $a['match_score']);

        return array_slice($certifications, 0, 3); // Top 3 recommendations
    }

    /**
     * Get market insights
     */
    private function getMarketInsights(array $userSkills): array {
        $preferredRegions = $userSkills['preferred_regions'] ?? ['prague'];

        return [
            'high_demand_skills' => ['ai', 'machine_learning', 'cloud', 'cybersecurity', 'data_engineering'],
            'regional_trends' => [
                'prague' => ['ai_growth' => 25, 'avg_salary_increase' => 8, 'remote_jobs' => 65],
                'berlin' => ['ai_growth' => 30, 'avg_salary_increase' => 10, 'remote_jobs' => 75],
                'amsterdam' => ['ai_growth' => 28, 'avg_salary_increase' => 9, 'remote_jobs' => 70]
            ],
            'salary_projections' => $this->getSalaryProjections($preferredRegions),
            'industry_growth' => [
                'ai_ml' => 35,
                'cloud_computing' => 20,
                'cybersecurity' => 25,
                'data_science' => 30
            ]
        ];
    }

    /**
     * Generate next steps based on user profile
     */
    private function generateNextSteps(array $userSkills, array $careerAssessment): array {
        $steps = [];

        // Skill development
        if (!empty($careerAssessment['skill_gaps'])) {
            $topGap = $careerAssessment['skill_gaps'][0];
            $steps[] = [
                'action' => "Learn {$topGap['skill']}",
                'type' => 'skill_development',
                'priority' => 'high',
                'timeline' => $topGap['time_to_learn'],
                'impact' => 'career_growth'
            ];
        }

        // Job applications
        $steps[] = [
            'action' => 'Apply to 2-3 recommended positions',
            'type' => 'job_search',
            'priority' => 'high',
            'timeline' => '2 weeks',
            'impact' => 'immediate_income'
        ];

        // Networking
        $steps[] = [
            'action' => 'Attend upcoming tech meetup or webinar',
            'type' => 'networking',
            'priority' => 'medium',
            'timeline' => '1 month',
            'impact' => 'long_term_connections'
        ];

        // Certification
        $steps[] = [
            'action' => 'Start preparing for a relevant certification',
            'type' => 'certification',
            'priority' => 'medium',
            'timeline' => '3 months',
            'impact' => 'career_advancement'
        ];

        return $steps;
    }

    /**
     * Helper methods
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
                'skills' => ['php', 'javascript', 'sql'],
                'experience_years' => 3,
                'preferred_regions' => ['prague'],
                'willing_to_relocate' => false,
                'interests' => ['technology', 'ai']
            ];
        }

        return $skills;
    }

    private function getCareerAssessment(int $userId): array {
        // Get from career service or return default
        return [
            'current_level' => 'mid',
            'skill_gaps' => [
                ['skill' => 'python', 'time_to_learn' => '4-6 weeks'],
                ['skill' => 'machine_learning', 'time_to_learn' => '8-12 weeks']
            ]
        ];
    }

    private function determineSkillLevel(array $userSkills): string {
        $experience = $userSkills['experience_years'] ?? 0;
        $skillCount = count($userSkills['skills'] ?? []);

        if ($experience >= 5 && $skillCount >= 5) {
            return 'senior';
        } elseif ($experience >= 2 && $skillCount >= 3) {
            return 'mid';
        } else {
            return 'junior';
        }
    }

    private function calculatePathMatchScore(array $path, array $userSkills, string $skillLevel): int {
        $score = 50; // Base score

        // Skill prerequisites match
        $prereqMatch = count(array_intersect($path['prerequisites'], $userSkills));
        $score += ($prereqMatch / count($path['prerequisites'])) * 30;

        // Difficulty level match
        $difficultyMatch = match($skillLevel) {
            'junior' => $path['difficulty'] === 'beginner' ? 20 : ($path['difficulty'] === 'intermediate' ? 10 : 0),
            'mid' => $path['difficulty'] === 'intermediate' ? 20 : ($path['difficulty'] === 'advanced' ? 10 : 0),
            'senior' => $path['difficulty'] === 'advanced' ? 20 : 10
        };
        $score += $difficultyMatch;

        return min(100, $score);
    }

    private function calculateJobMatchScore(array $job, array $userSkills, array $careerAssessment): int {
        $score = 60; // Base score

        // Skills match
        $requiredSkills = $job['requirements'];
        $skillMatch = count(array_intersect($requiredSkills, $userSkills));
        $score += ($skillMatch / count($requiredSkills)) * 30;

        // Experience level match
        $jobLevel = strpos($job['title'], 'Senior') !== false ? 'senior' : 'mid';
        if ($jobLevel === $careerAssessment['current_level']) {
            $score += 10;
        }

        return min(100, $score);
    }

    private function calculateGigMatchScore(array $gig, array $userSkills): int {
        $score = 50; // Base score

        $requiredSkills = $gig['skills_required'];
        $skillMatch = count(array_intersect($requiredSkills, $userSkills));
        $score += ($skillMatch / count($requiredSkills)) * 40;

        // Difficulty adjustment
        $difficultyBonus = match($gig['difficulty']) {
            'beginner' => 10,
            'intermediate' => 5,
            'advanced' => 0
        };
        $score += $difficultyBonus;

        return min(100, $score);
    }

    private function calculateCertificationMatchScore(array $cert, array $userSkills, string $skillLevel): int {
        $score = 55; // Base score

        // Prerequisite match
        $prereqMatch = count(array_intersect($cert['prerequisites'], $userSkills));
        $score += ($prereqMatch / count($cert['prerequisites'])) * 25;

        // Difficulty match
        $difficultyMatch = match($skillLevel) {
            'junior' => $cert['difficulty'] === 'intermediate' ? 15 : 5,
            'mid' => $cert['difficulty'] === 'intermediate' ? 15 : ($cert['difficulty'] === 'advanced' ? 10 : 0),
            'senior' => $cert['difficulty'] === 'advanced' ? 15 : 10
        };
        $score += $difficultyMatch;

        return min(100, $score);
    }

    private function estimateCompletionTime(array $path, array $userSkills): string {
        $baseWeeks = (int)explode(' ', $path['duration'])[0];

        // Reduce time if user already has some skills
        $skillOverlap = count(array_intersect($path['prerequisites'], $userSkills));
        $reduction = ($skillOverlap / count($path['prerequisites'])) * 0.3; // 30% reduction max

        $adjustedWeeks = $baseWeeks * (1 - $reduction);

        if ($adjustedWeeks <= 4) {
            return ceil($adjustedWeeks) . ' weeks';
        } else {
            $months = ceil($adjustedWeeks / 4.33);
            return $months . ' months';
        }
    }

    private function generateApplicationTips(array $job, array $userSkills): array {
        $tips = [];

        $missingSkills = array_diff($job['requirements'], $userSkills);
        if (!empty($missingSkills)) {
            $tips[] = 'Highlight transferable skills and eagerness to learn the missing technologies: ' . implode(', ', $missingSkills);
        }

        $tips[] = 'Customize your CV to emphasize relevant experience and quantify achievements';
        $tips[] = 'Prepare for technical interviews by reviewing ' . implode(' and ', $job['requirements']);
        $tips[] = 'Research the company and prepare thoughtful questions about their tech stack and culture';

        return $tips;
    }

    private function estimateGigEarnings(array $gig, array $userSkills): array {
        $baseRate = ($gig['budget_range']['min'] + $gig['budget_range']['max']) / 2;
        $duration = (int)explode('-', $gig['duration'])[0]; // Take minimum duration

        // Adjust based on skill match
        $skillMatch = count(array_intersect($gig['skills_required'], $userSkills));
        $skillMultiplier = 1 + ($skillMatch / count($gig['skills_required']) * 0.2);

        $estimatedTotal = $baseRate * $skillMultiplier;
        $hourlyRate = $estimatedTotal / ($duration * 40); // Assuming 40 hours/week

        return [
            'estimated_total' => round($estimatedTotal, 2),
            'hourly_rate' => round($hourlyRate, 2),
            'currency' => $gig['budget_range']['currency'],
            'confidence' => $skillMatch / count($gig['skills_required']) * 100
        ];
    }

    private function estimateCertificationROI(array $cert, array $userSkills): array {
        $totalCost = $cert['cost'] + ($cert['exam_fee'] ?? 0);
        $salaryIncrease = $this->estimateSalaryIncrease($cert, $userSkills);

        $monthsToBreakEven = $totalCost / ($salaryIncrease / 12);
        $yearlyROI = ($salaryIncrease / $totalCost) * 100;

        return [
            'total_cost' => $totalCost,
            'estimated_salary_increase' => $salaryIncrease,
            'months_to_breakeven' => ceil($monthsToBreakEven),
            'yearly_roi_percentage' => round($yearlyROI, 1)
        ];
    }

    private function estimateSalaryIncrease(array $cert, array $userSkills): float {
        // Simplified estimation based on certification impact
        $baseIncrease = match($cert['career_boost']) {
            'high' => 15000, // CZK
            'medium' => 8000,
            'low' => 3000
        };

        // Adjust based on current skill level
        $skillMultiplier = match($this->determineSkillLevel(['skills' => $userSkills])) {
            'junior' => 1.3,
            'mid' => 1.0,
            'senior' => 0.8
        };

        return $baseIncrease * $skillMultiplier;
    }

    private function getSalaryProjections(array $regions): array {
        $projections = [];

        foreach ($regions as $region) {
            $projections[$region] = [
                'junior_developer' => ['current_avg' => 45000, 'projected_2026' => 49000, 'growth' => 9],
                'mid_developer' => ['current_avg' => 65000, 'projected_2026' => 71500, 'growth' => 10],
                'senior_developer' => ['current_avg' => 90000, 'projected_2026' => 100000, 'growth' => 11],
                'ai_engineer' => ['current_avg' => 80000, 'projected_2026' => 92000, 'growth' => 15]
            ];
        }

        return $projections;
    }
}