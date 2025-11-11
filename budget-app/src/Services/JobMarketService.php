<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

class JobMarketService {
    private Database $db;
    private LlmService $llmService;

    public function __construct(Database $db) {
        $this->db = $db;
        $this->llmService = new LlmService($db);
    }

    /**
     * Fetch job opportunities from configured feeds
     */
    public function fetchJobOpportunities(int $userId): array {
        $feeds = $this->getActiveFeeds();
        $opportunities = [];

        foreach ($feeds as $feed) {
            try {
                $feedOpportunities = $this->fetchFromFeed($feed, $userId);
                $opportunities = array_merge($opportunities, $feedOpportunities);
            } catch (\Exception $e) {
                // Log error but continue with other feeds
                error_log("Failed to fetch from feed {$feed['id']}: " . $e->getMessage());
            }
        }

        return $opportunities;
    }

    /**
     * Get active job market feeds
     */
    private function getActiveFeeds(): array {
        return $this->db->query(
            "SELECT * FROM job_market_feeds WHERE is_active = 1",
            []
        );
    }

    /**
     * Fetch opportunities from a specific feed
     */
    private function fetchFromFeed(array $feed, int $userId): array {
        $opportunities = [];

        switch ($feed['feed_type']) {
            case 'api':
                $opportunities = $this->fetchFromAPI($feed);
                break;
            case 'rss':
                $opportunities = $this->fetchFromRSS($feed);
                break;
            case 'scraping':
                $opportunities = $this->fetchFromScraping($feed);
                break;
        }

        // Calculate relevance scores and store
        foreach ($opportunities as $opp) {
            $opp['relevance_score'] = $this->calculateRelevanceScore($opp, $userId);
            $this->storeOpportunity($opp, $feed['id'], $userId);
        }

        return $opportunities;
    }

    /**
     * Fetch from API (placeholder for actual API integration)
     */
    private function fetchFromAPI(array $feed): array {
        // This would integrate with actual job APIs like LinkedIn, Indeed, etc.
        // For now, return mock data focused on AI/technical roles
        return [
            [
                'external_id' => 'mock-ai-engineer-1',
                'title' => 'AI/ML Engineer - Remote',
                'company' => 'TechCorp Prague',
                'location' => 'Prague, Czech Republic',
                'salary_range' => '80000-120000 CZK',
                'job_type' => 'full-time',
                'description' => 'Develop AI-powered applications using Python, TensorFlow, and cloud platforms.',
                'requirements' => 'Python, ML, Docker, Kubernetes',
                'application_url' => 'https://example.com/job/1',
                'posted_date' => date('Y-m-d'),
                'expires_date' => date('Y-m-d', strtotime('+30 days'))
            ],
            [
                'external_id' => 'mock-data-scientist-1',
                'title' => 'Data Scientist - AI Focus',
                'company' => 'DataTech EU',
                'location' => 'Brno, Czech Republic',
                'salary_range' => '70000-100000 CZK',
                'job_type' => 'full-time',
                'description' => 'Analyze large datasets and build predictive models for business intelligence.',
                'requirements' => 'Python, R, SQL, Machine Learning',
                'application_url' => 'https://example.com/job/2',
                'posted_date' => date('Y-m-d'),
                'expires_date' => date('Y-m-d', strtotime('+30 days'))
            ]
        ];
    }

    /**
     * Fetch from RSS feed
     */
    private function fetchFromRSS(array $feed): array {
        // Placeholder for RSS feed parsing
        // Would use SimpleXML or similar to parse RSS feeds
        return [];
    }

    /**
     * Fetch via web scraping (placeholder)
     */
    private function fetchFromScraping(array $feed): array {
        // Placeholder for web scraping functionality
        // Would use tools like Guzzle and DOM parsing
        return [];
    }

    /**
     * Calculate relevance score based on user profile and job requirements
     */
    private function calculateRelevanceScore(array $opportunity, int $userId): float {
        $score = 0.5; // Base score

        // Get user skills/interests from settings or profile
        $userSkills = $this->getUserSkills($userId);
        $userPreferences = $this->getUserJobPreferences($userId);

        // Check for AI/ML keywords in job title/description
        $aiKeywords = ['ai', 'artificial intelligence', 'machine learning', 'ml', 'deep learning', 'neural network'];
        $jobText = strtolower($opportunity['title'] . ' ' . $opportunity['description'] . ' ' . $opportunity['requirements']);

        foreach ($aiKeywords as $keyword) {
            if (strpos($jobText, $keyword) !== false) {
                $score += 0.2;
                break;
            }
        }

        // Check for technical skills match
        $techKeywords = ['python', 'javascript', 'php', 'docker', 'kubernetes', 'aws', 'azure'];
        foreach ($techKeywords as $keyword) {
            if (strpos($jobText, $keyword) !== false && in_array($keyword, $userSkills)) {
                $score += 0.1;
            }
        }

        // Location preference
        if (!empty($userPreferences['preferred_location']) &&
            strpos($opportunity['location'], $userPreferences['preferred_location']) !== false) {
            $score += 0.15;
        }

        // Remote work preference
        if ($userPreferences['remote_ok'] && $opportunity['job_type'] === 'remote') {
            $score += 0.1;
        }

        return min($score, 1.0); // Cap at 1.0
    }

    /**
     * Get user skills from profile/settings
     */
    private function getUserSkills(int $userId): array {
        $skills = $this->db->query(
            "SELECT setting_value FROM user_settings
             WHERE user_id = ? AND category = 'profile' AND setting_key = 'skills'",
            [$userId]
        );

        if (!empty($skills)) {
            return json_decode($skills[0]['setting_value'], true) ?? [];
        }

        return ['php', 'javascript', 'python']; // Default skills
    }

    /**
     * Get user job preferences
     */
    private function getUserJobPreferences(int $userId): array {
        $preferences = $this->db->query(
            "SELECT setting_key, setting_value FROM user_settings
             WHERE user_id = ? AND category = 'career'",
            [$userId]
        );

        $prefs = [];
        foreach ($preferences as $pref) {
            $prefs[$pref['setting_key']] = json_decode($pref['setting_value'], true) ?? $pref['setting_value'];
        }

        return $prefs;
    }

    /**
     * Store job opportunity in database
     */
    private function storeOpportunity(array $opportunity, int $feedId, int $userId): void {
        // Check if opportunity already exists
        $existing = $this->db->query(
            "SELECT id FROM job_opportunities
             WHERE user_id = ? AND external_id = ?",
            [$userId, $opportunity['external_id']]
        );

        if (empty($existing)) {
            $this->db->insert('job_opportunities', [
                'user_id' => $userId,
                'feed_id' => $feedId,
                'external_id' => $opportunity['external_id'],
                'title' => $opportunity['title'],
                'company' => $opportunity['company'],
                'location' => $opportunity['location'],
                'salary_range' => $opportunity['salary_range'],
                'job_type' => $opportunity['job_type'],
                'description' => $opportunity['description'],
                'requirements' => $opportunity['requirements'],
                'application_url' => $opportunity['application_url'],
                'posted_date' => $opportunity['posted_date'],
                'expires_date' => $opportunity['expires_date'],
                'relevance_score' => $opportunity['relevance_score']
            ]);
        }
    }

    /**
     * Get relevant job opportunities for user
     */
    public function getRelevantOpportunities(int $userId, int $limit = 10): array {
        return $this->db->query(
            "SELECT * FROM job_opportunities
             WHERE user_id = ? AND relevance_score > 0.6
             ORDER BY relevance_score DESC, posted_date DESC
             LIMIT ?",
            [$userId, $limit]
        );
    }

    /**
     * Mark opportunity as saved/applied
     */
    public function updateOpportunityStatus(int $opportunityId, string $status, ?string $appliedAt = null): void {
        $data = [];

        switch ($status) {
            case 'saved':
                $data['is_saved'] = 1;
                break;
            case 'applied':
                $data['is_applied'] = 1;
                $data['applied_at'] = $appliedAt ?? date('Y-m-d H:i:s');
                break;
        }

        if (!empty($data)) {
            $this->db->update('job_opportunities', $data, ['id' => $opportunityId]);
        }
    }

    /**
     * Generate career insights using LLM
     */
    public function generateCareerInsights(int $userId): array {
        $opportunities = $this->getRelevantOpportunities($userId, 5);
        $userSkills = $this->getUserSkills($userId);
        $userPreferences = $this->getUserJobPreferences($userId);

        if (empty($opportunities)) {
            return [];
        }

        $context = [
            'skills' => $userSkills,
            'preferences' => $userPreferences,
            'opportunities' => array_map(function($opp) {
                return [
                    'title' => $opp['title'],
                    'company' => $opp['company'],
                    'salary_range' => $opp['salary_range'],
                    'location' => $opp['location'],
                    'relevance_score' => $opp['relevance_score']
                ];
            }, $opportunities)
        ];

        $prompt = $this->buildCareerPrompt($context);

        try {
            $insights = $this->llmService->generateResponse($prompt, $userId, 'career_uplift');
            return [
                'insights' => $insights,
                'opportunities_count' => count($opportunities)
            ];
        } catch (\Exception $e) {
            return [
                'insights' => 'Unable to generate career insights at this time.',
                'opportunities_count' => count($opportunities)
            ];
        }
    }

    /**
     * Build career uplift prompt
     */
    private function buildCareerPrompt(array $context): string {
        $opportunitiesText = '';
        foreach ($context['opportunities'] as $opp) {
            $opportunitiesText .= "- {$opp['title']} at {$opp['company']} ({$opp['salary_range']}) in {$opp['location']}\n";
        }

        $skillsText = implode(', ', $context['skills']);

        return <<<PROMPT
You advise a Czech IT technician skilled in AI-assisted coding, open to remote or partial relocation. From USER_SKILLS and MARKET_DATA, list the five highest-demand roles:

USER_SKILLS: {$skillsText}
AVAILABLE_OPPORTUNITIES:
{$opportunitiesText}

Provide:
1. Top 3 recommended roles with salary ranges and requirements
2. Skills gaps to address for better opportunities
3. Suggested next steps for career advancement
4. Any Czech-specific benefits or programs for IT professionals

Respond in Czech language.
PROMPT;
    }
}