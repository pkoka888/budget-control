<?php
namespace BudgetApp\Controllers;

use BudgetApp\Services\OpportunitiesService;

class OpportunitiesController extends BaseController {
    private OpportunitiesService $opportunitiesService;

    public function __construct($app) {
        parent::__construct($app);
        $this->opportunitiesService = new OpportunitiesService($this->db);
    }

    /**
     * Get opportunities dashboard
     */
    public function dashboard(array $params = []): void {
        $userId = $this->getUserId();

        try {
            $dashboard = $this->opportunitiesService->getOpportunitiesDashboard($userId);

            $this->json([
                'success' => true,
                'dashboard' => $dashboard
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to get opportunities dashboard: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get learning paths
     */
    public function learningPaths(array $params = []): void {
        $userId = $this->getUserId();
        $category = $this->getQueryParam('category'); // ai, cloud, fullstack, etc.

        try {
            $userSkills = $this->getUserSkills($userId);
            $allPaths = $this->opportunitiesService->getOpportunitiesDashboard($userId)['personalized_learning'];

            if ($category) {
                $allPaths = array_filter($allPaths, function($path) use ($category) {
                    return in_array($category, $path['skills_gained'] ?? []);
                });
            }

            $this->json([
                'success' => true,
                'learning_paths' => array_values($allPaths),
                'total' => count($allPaths)
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to get learning paths: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get job opportunities
     */
    public function jobs(array $params = []): void {
        $userId = $this->getUserId();
        $region = $this->getQueryParam('region');
        $remoteOnly = $this->getQueryParam('remote_only', 'false') === 'true';

        try {
            $userSkills = $this->getUserSkills($userId);
            $allJobs = $this->opportunitiesService->getOpportunitiesDashboard($userId)['recommended_jobs'];

            // Filter by region if specified
            if ($region) {
                $allJobs = array_filter($allJobs, function($job) use ($region) {
                    $jobRegion = strtolower(str_replace(' ', '', explode(',', $job['location'])[0]));
                    return $jobRegion === $region;
                });
            }

            // Filter remote jobs if requested
            if ($remoteOnly) {
                $allJobs = array_filter($allJobs, function($job) {
                    return $job['remote'] === 'remote';
                });
            }

            $this->json([
                'success' => true,
                'jobs' => array_values($allJobs),
                'total' => count($allJobs),
                'filters_applied' => [
                    'region' => $region,
                    'remote_only' => $remoteOnly
                ]
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to get job opportunities: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get freelance opportunities
     */
    public function freelance(array $params = []): void {
        $userId = $this->getUserId();
        $platform = $this->getQueryParam('platform'); // upwork, toptal, fiverr
        $minBudget = (float)$this->getQueryParam('min_budget', 0);

        try {
            $allGigs = $this->opportunitiesService->getOpportunitiesDashboard($userId)['freelance_opportunities'];

            // Filter by platform if specified
            if ($platform) {
                $allGigs = array_filter($allGigs, function($gig) use ($platform) {
                    return strtolower($gig['platform']) === $platform;
                });
            }

            // Filter by minimum budget
            if ($minBudget > 0) {
                $allGigs = array_filter($allGigs, function($gig) use ($minBudget) {
                    return $gig['budget_range']['min'] >= $minBudget;
                });
            }

            $this->json([
                'success' => true,
                'freelance_gigs' => array_values($allGigs),
                'total' => count($allGigs),
                'filters_applied' => [
                    'platform' => $platform,
                    'min_budget' => $minBudget
                ]
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to get freelance opportunities: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get networking events
     */
    public function events(array $params = []): void {
        $userId = $this->getUserId();
        $type = $this->getQueryParam('type'); // meetup, conference, webinar
        $upcomingOnly = $this->getQueryParam('upcoming_only', 'true') === 'true';

        try {
            $allEvents = $this->opportunitiesService->getOpportunitiesDashboard($userId)['upcoming_events'];

            // Filter by type if specified
            if ($type) {
                $allEvents = array_filter($allEvents, function($event) use ($type) {
                    return $event['type'] === $type;
                });
            }

            // Filter upcoming events (already done in service, but double-check)
            if ($upcomingOnly) {
                $allEvents = array_filter($allEvents, function($event) {
                    return strtotime($event['date']) >= time();
                });
            }

            $this->json([
                'success' => true,
                'events' => array_values($allEvents),
                'total' => count($allEvents),
                'filters_applied' => [
                    'type' => $type,
                    'upcoming_only' => $upcomingOnly
                ]
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to get networking events: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get certifications and courses
     */
    public function certifications(array $params = []): void {
        $userId = $this->getUserId();
        $provider = $this->getQueryParam('provider'); // aws, google, microsoft
        $maxCost = (float)$this->getQueryParam('max_cost', 0);

        try {
            $allCerts = $this->opportunitiesService->getOpportunitiesDashboard($userId)['certifications'];

            // Filter by provider if specified
            if ($provider) {
                $allCerts = array_filter($allCerts, function($cert) use ($provider) {
                    return strtolower($cert['provider']) === $provider;
                });
            }

            // Filter by maximum cost
            if ($maxCost > 0) {
                $allCerts = array_filter($allCerts, function($cert) use ($maxCost) {
                    return ($cert['cost'] + ($cert['exam_fee'] ?? 0)) <= $maxCost;
                });
            }

            $this->json([
                'success' => true,
                'certifications' => array_values($allCerts),
                'total' => count($allCerts),
                'filters_applied' => [
                    'provider' => $provider,
                    'max_cost' => $maxCost
                ]
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to get certifications: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get market insights
     */
    public function marketInsights(array $params = []): void {
        $userId = $this->getUserId();
        $region = $this->getQueryParam('region');

        try {
            $insights = $this->opportunitiesService->getOpportunitiesDashboard($userId)['market_insights'];

            // Filter insights by region if specified
            if ($region && isset($insights['regional_trends'][$region])) {
                $insights['regional_trends'] = [$region => $insights['regional_trends'][$region]];
                $insights['salary_projections'] = [$region => $insights['salary_projections'][$region]];
            }

            $this->json([
                'success' => true,
                'insights' => $insights,
                'region_filter' => $region
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to get market insights: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Track opportunity interaction
     */
    public function trackInteraction(array $params = []): void {
        $userId = $this->getUserId();

        $interactionData = json_decode(file_get_contents('php://input'), true);

        if (!$interactionData || !isset($interactionData['opportunity_id'], $interactionData['interaction_type'])) {
            $this->json(['error' => 'Invalid interaction data'], 400);
            return;
        }

        try {
            // Store interaction in database (you might want to add an interactions table)
            $this->db->insert('opportunity_interactions', [
                'user_id' => $userId,
                'opportunity_id' => $interactionData['opportunity_id'],
                'opportunity_type' => $interactionData['opportunity_type'],
                'interaction_type' => $interactionData['interaction_type'],
                'metadata' => json_encode($interactionData['metadata'] ?? []),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $this->json([
                'success' => true,
                'message' => 'Interaction tracked successfully'
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to track interaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get personalized recommendations
     */
    public function recommendations(array $params = []): void {
        $userId = $this->getUserId();
        $limit = (int)$this->getQueryParam('limit', 5);

        try {
            $dashboard = $this->opportunitiesService->getOpportunitiesDashboard($userId);

            $recommendations = [
                'next_steps' => array_slice($dashboard['next_steps'], 0, $limit),
                'top_learning_path' => !empty($dashboard['personalized_learning']) ? $dashboard['personalized_learning'][0] : null,
                'top_job_opportunity' => !empty($dashboard['recommended_jobs']) ? $dashboard['recommended_jobs'][0] : null,
                'upcoming_event' => !empty($dashboard['upcoming_events']) ? $dashboard['upcoming_events'][0] : null,
                'recommended_certification' => !empty($dashboard['certifications']) ? $dashboard['certifications'][0] : null
            ];

            $this->json([
                'success' => true,
                'recommendations' => $recommendations,
                'generated_at' => date('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to get recommendations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save opportunity to user's saved list
     */
    public function saveOpportunity(array $params = []): void {
        $userId = $this->getUserId();

        $saveData = json_decode(file_get_contents('php://input'), true);

        if (!$saveData || !isset($saveData['opportunity_id'], $saveData['opportunity_type'])) {
            $this->json(['error' => 'Invalid save data'], 400);
            return;
        }

        try {
            // Check if already saved
            $existing = $this->db->queryOne(
                "SELECT id FROM saved_opportunities
                 WHERE user_id = ? AND opportunity_id = ? AND opportunity_type = ?",
                [$userId, $saveData['opportunity_id'], $saveData['opportunity_type']]
            );

            if ($existing) {
                $this->json(['error' => 'Opportunity already saved'], 409);
                return;
            }

            // Save opportunity
            $this->db->insert('saved_opportunities', [
                'user_id' => $userId,
                'opportunity_id' => $saveData['opportunity_id'],
                'opportunity_type' => $saveData['opportunity_type'],
                'opportunity_data' => json_encode($saveData['opportunity_data'] ?? []),
                'notes' => $saveData['notes'] ?? '',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $this->json([
                'success' => true,
                'message' => 'Opportunity saved successfully'
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to save opportunity: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's saved opportunities
     */
    public function savedOpportunities(array $params = []): void {
        $userId = $this->getUserId();
        $type = $this->getQueryParam('type'); // learning, jobs, freelance, events, certifications

        try {
            $query = "SELECT * FROM saved_opportunities WHERE user_id = ?";
            $params = [$userId];

            if ($type) {
                $query .= " AND opportunity_type = ?";
                $params[] = $type;
            }

            $query .= " ORDER BY created_at DESC";

            $saved = $this->db->query($query, $params);

            // Decode opportunity data
            foreach ($saved as &$item) {
                $item['opportunity_data'] = json_decode($item['opportunity_data'], true) ?: [];
            }

            $this->json([
                'success' => true,
                'saved_opportunities' => $saved,
                'total' => count($saved),
                'type_filter' => $type
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to get saved opportunities: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove saved opportunity
     */
    public function removeSaved(array $params = []): void {
        $savedId = $params['id'] ?? 0;
        $userId = $this->getUserId();

        if (!$savedId) {
            $this->json(['error' => 'Saved opportunity ID is required'], 400);
            return;
        }

        try {
            $deleted = $this->db->delete('saved_opportunities',
                ['id' => $savedId, 'user_id' => $userId]
            );

            if ($deleted) {
                $this->json(['success' => true, 'message' => 'Saved opportunity removed']);
            } else {
                $this->json(['error' => 'Saved opportunity not found'], 404);
            }

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to remove saved opportunity: ' . $e->getMessage()
            ], 500);
        }
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

        return $skills;
    }
}