<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

class CzechBenefitsService {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
        $this->initializeBenefits();
    }

    /**
     * Initialize Czech benefits database
     */
    private function initializeBenefits(): void {
        $benefits = [
            [
                'benefit_type' => 'unemployment',
                'name' => 'Podpora v nezaměstnanosti',
                'description' => 'Finanční podpora pro nezaměstnané občany ČR. Výše podpory závisí na předchozí mzdě.',
                'eligibility_criteria' => json_encode([
                    'citizenship' => 'CZ',
                    'employment_history' => 'min_12_months_last_3_years',
                    'actively_seeking_work' => true
                ]),
                'application_process' => 'Zaregistrujte se na Úřadu práce ČR a podejte žádost online nebo osobně.',
                'contact_info' => 'Úřad práce ČR - www.uradprace.cz',
                'website_url' => 'https://www.uradprace.cz/'
            ],
            [
                'benefit_type' => 'parental_leave',
                'name' => 'Rodičovská dovolená a příspěvek',
                'description' => 'Finanční podpora pro pečující rodiče. Lze čerpat až 4 roky s různou výší příspěvku.',
                'eligibility_criteria' => json_encode([
                    'has_child' => true,
                    'child_age' => 'under_4_years',
                    'parent_employed' => 'was_employed_before_birth'
                ]),
                'application_process' => 'Žádost se podává na OSSZ nebo online přes portál MPSV.',
                'contact_info' => 'ČSSZ - www.cssz.cz',
                'website_url' => 'https://www.cssz.cz/'
            ],
            [
                'benefit_type' => 'housing_allowance',
                'name' => 'Příspěvek na bydlení',
                'description' => 'Přínos na krytí nákladů na bydlení pro nízkopříjmové domácnosti.',
                'eligibility_criteria' => json_encode([
                    'income_threshold' => 'below_average_wage',
                    'housing_costs' => 'above_30_percent_income',
                    'residency' => 'permanent_CZ_resident'
                ]),
                'application_process' => 'Žádost na Úřadu práce ČR nebo obecním úřadu.',
                'contact_info' => 'Úřad práce ČR',
                'website_url' => 'https://www.uradprace.cz/'
            ],
            [
                'benefit_type' => 'hardship_fund',
                'name' => 'Fond sociální nouze',
                'description' => 'Jednorázová finanční pomoc v akutních životních situacích.',
                'eligibility_criteria' => json_encode([
                    'crisis_situation' => true,
                    'income_below_minimum' => true,
                    'no_other_sources' => true
                ]),
                'application_process' => 'Kontaktujte sociální odbor obecního úřadu.',
                'contact_info' => 'Obecní úřad - sociální odbor',
                'website_url' => 'https://www.mpsv.cz/'
            ],
            [
                'benefit_type' => 'energy_allowance',
                'name' => 'Příspěvek na energie',
                'description' => 'Přínos na krytí nákladů za energie pro nízkopříjmové domácnosti.',
                'eligibility_criteria' => json_encode([
                    'income_threshold' => 'below_living_minimum',
                    'energy_costs' => 'significant_portion_income'
                ]),
                'application_process' => 'Žádost na Úřadu práce ČR.',
                'contact_info' => 'Úřad práce ČR',
                'website_url' => 'https://www.uradprace.cz/'
            ],
            [
                'benefit_type' => 'student_allowance',
                'name' => 'Studentské příspěvky',
                'description' => 'Finanční podpora pro studenty vysokých škol v tíživé sociální situaci.',
                'eligibility_criteria' => json_encode([
                    'student_status' => true,
                    'income_below_threshold' => true,
                    'study_success' => 'satisfactory'
                ]),
                'application_process' => 'Žádost na vysoké škole nebo Úřadu práce ČR.',
                'contact_info' => 'Ministerstvo školství - www.msmt.cz',
                'website_url' => 'https://www.msmt.cz/'
            ]
        ];

        foreach ($benefits as $benefit) {
            $existing = $this->db->query(
                "SELECT id FROM czech_benefits WHERE benefit_type = ? AND name = ?",
                [$benefit['benefit_type'], $benefit['name']]
            );

            if (empty($existing)) {
                $this->db->insert('czech_benefits', $benefit);
            }
        }
    }

    /**
     * Get potential benefits for user based on financial situation
     */
    public function getPotentialBenefits(int $userId): array {
        // Get user's financial data
        $analyzer = new FinancialAnalyzer($this->db);
        $netWorth = $analyzer->getNetWorth($userId);
        $monthlyIncome = $analyzer->getMonthlyIncome($userId);
        $monthlyExpenses = $analyzer->getMonthSummary($userId, date('Y-m'))['total_expenses'];

        $potentialBenefits = [];

        // Low income benefits
        if ($monthlyIncome < 30000) { // Below average Czech wage
            $unemployment = $this->getBenefitByType('unemployment');
            if ($unemployment) {
                $potentialBenefits[] = $unemployment;
            }
        }

        // Housing cost burden
        $housingRatio = $this->calculateHousingRatio($userId);
        if ($housingRatio > 0.35) { // Housing costs > 35% of income
            $housing = $this->getBenefitByType('housing_allowance');
            if ($housing) {
                $potentialBenefits[] = $housing;
            }
        }

        // High energy costs (estimated)
        $energyEstimate = $monthlyExpenses * 0.15; // Estimate 15% for energy
        if ($energyEstimate > 5000 && $monthlyIncome < 40000) {
            $energy = $this->getBenefitByType('energy_allowance');
            if ($energy) {
                $potentialBenefits[] = $energy;
            }
        }

        // Crisis situations
        if ($netWorth['net_worth'] < 10000 && $monthlyIncome < 20000) {
            $hardship = $this->getBenefitByType('hardship_fund');
            if ($hardship) {
                $potentialBenefits[] = $hardship;
            }
        }

        return $potentialBenefits;
    }

    /**
     * Get benefit by type
     */
    private function getBenefitByType(string $type): ?array {
        $benefits = $this->db->query(
            "SELECT * FROM czech_benefits WHERE benefit_type = ? AND is_active = 1",
            [$type]
        );

        return $benefits[0] ?? null;
    }

    /**
     * Calculate housing cost ratio
     */
    private function calculateHousingRatio(int $userId): float {
        // Estimate housing costs from transactions
        $housingCategories = ['bydlení', 'nájem', 'hypotéka', 'housing', 'rent', 'mortgage'];

        $housingCosts = $this->db->queryOne(
            "SELECT SUM(t.amount) as total FROM transactions t
             JOIN categories c ON t.category_id = c.id
             WHERE t.user_id = ? AND t.type = 'expense'
             AND t.date >= date('now', '-3 months')
             AND (c.name LIKE '%bydlení%' OR c.name LIKE '%nájem%' OR c.name LIKE '%hypoték%')",
            [$userId]
        );

        $monthlyIncome = $this->db->queryOne(
            "SELECT AVG(monthly_total) as avg_income FROM (
                SELECT SUM(amount) as monthly_total
                FROM transactions
                WHERE user_id = ? AND type = 'income'
                AND date >= date('now', '-3 months')
                GROUP BY strftime('%Y-%m', date)
            )",
            [$userId]
        );

        $income = $monthlyIncome['avg_income'] ?? 0;
        $housing = $housingCosts['total'] ?? 0;

        return $income > 0 ? ($housing / $income) : 0;
    }

    /**
     * Record user benefit application
     */
    public function recordBenefitApplication(int $userId, int $benefitId, string $status = 'interested'): void {
        $this->db->insert('user_benefit_applications', [
            'user_id' => $userId,
            'benefit_id' => $benefitId,
            'application_status' => $status,
            'application_date' => date('Y-m-d')
        ]);
    }

    /**
     * Update benefit application status
     */
    public function updateBenefitApplication(int $applicationId, string $status, ?string $approvalDate = null, ?float $amount = null): void {
        $data = ['application_status' => $status];

        if ($approvalDate) {
            $data['approval_date'] = $approvalDate;
        }

        if ($amount) {
            $data['amount_received'] = $amount;
        }

        $this->db->update('user_benefit_applications', $data, ['id' => $applicationId]);
    }

    /**
     * Get user's benefit applications
     */
    public function getUserBenefitApplications(int $userId): array {
        return $this->db->query(
            "SELECT uba.*, cb.name, cb.benefit_type, cb.description
             FROM user_benefit_applications uba
             JOIN czech_benefits cb ON uba.benefit_id = cb.id
             WHERE uba.user_id = ?
             ORDER BY uba.created_at DESC",
            [$userId]
        );
    }

    /**
     * Get all available benefits
     */
    public function getAllBenefits(): array {
        return $this->db->query(
            "SELECT * FROM czech_benefits WHERE is_active = 1 ORDER BY benefit_type, name"
        );
    }

    /**
     * Check benefit eligibility based on user data
     */
    public function checkEligibility(int $benefitId, int $userId): array {
        $benefit = $this->db->queryOne(
            "SELECT * FROM czech_benefits WHERE id = ?",
            [$benefitId]
        );

        if (!$benefit) {
            return ['eligible' => false, 'reason' => 'Benefit not found'];
        }

        $criteria = json_decode($benefit['eligibility_criteria'], true);

        // Get user data for eligibility check
        $userData = $this->getUserEligibilityData($userId);

        $eligible = true;
        $reasons = [];

        foreach ($criteria as $key => $value) {
            if (!$this->checkCriterion($key, $value, $userData)) {
                $eligible = false;
                $reasons[] = "Criterion '{$key}' not met";
            }
        }

        return [
            'eligible' => $eligible,
            'reasons' => $reasons,
            'benefit' => $benefit
        ];
    }

    /**
     * Get user data for eligibility checking
     */
    private function getUserEligibilityData(int $userId): array {
        $analyzer = new FinancialAnalyzer($this->db);
        $netWorth = $analyzer->getNetWorth($userId);
        $monthlyIncome = $analyzer->getMonthlyIncome($userId);

        return [
            'monthly_income' => $monthlyIncome,
            'net_worth' => $netWorth['net_worth'],
            'total_assets' => $netWorth['total_assets'],
            'total_liabilities' => $netWorth['total_liabilities'],
            'housing_ratio' => $this->calculateHousingRatio($userId)
        ];
    }

    /**
     * Check individual eligibility criterion
     */
    private function checkCriterion(string $criterion, $requiredValue, array $userData): bool {
        switch ($criterion) {
            case 'income_threshold':
                if ($requiredValue === 'below_average_wage') {
                    return $userData['monthly_income'] < 35000; // CZK
                }
                if ($requiredValue === 'below_living_minimum') {
                    return $userData['monthly_income'] < 15000; // CZK
                }
                break;

            case 'housing_costs':
                if ($requiredValue === 'above_30_percent_income') {
                    return $userData['housing_ratio'] > 0.3;
                }
                break;

            case 'net_worth':
                if ($requiredValue === 'below_10000') {
                    return $userData['net_worth'] < 10000;
                }
                break;

            default:
                // For simple boolean checks
                return isset($userData[$criterion]) && $userData[$criterion] == $requiredValue;
        }

        return false;
    }
}