<?php
namespace BudgetApp\Services;

class BudgetTemplateService {
    private $db;

    // Predefined template types
    private const TEMPLATE_TYPES = [
        'student' => 'Student Budget',
        'single' => 'Single Person Budget',
        'family' => 'Family Budget',
        'retiree' => 'Retiree Budget',
        'minimalist' => 'Minimalist Budget',
        'luxury' => 'Luxury Budget'
    ];

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Get all available templates (system default + user-created)
     */
    public function getTemplates(int $userId): array {
        // Get system default templates
        $defaultTemplates = $this->getDefaultTemplates();

        // Get user-created templates
        $userTemplates = $this->db->query(
            "SELECT * FROM budget_templates WHERE user_id = ? AND is_active = 1 ORDER BY name",
            [$userId]
        );

        // Add categories to each template
        foreach ($defaultTemplates as &$template) {
            $template['categories'] = $this->getTemplateCategories($template['id']);
            $template['is_default'] = true;
        }

        foreach ($userTemplates as &$template) {
            $template['categories'] = $this->getTemplateCategories($template['id']);
            $template['is_default'] = false;
        }

        return array_merge($defaultTemplates, $userTemplates);
    }

    /**
     * Get a specific template by ID
     */
    public function getTemplate(int $templateId, int $userId = null): ?array {
        $template = $this->db->queryOne(
            "SELECT * FROM budget_templates WHERE id = ? AND is_active = 1",
            [$templateId]
        );

        if (!$template) {
            return null;
        }

        // Check if user has access to this template
        if ($template['user_id'] && $template['user_id'] != $userId) {
            return null;
        }

        $template['categories'] = $this->getTemplateCategories($templateId);
        return $template;
    }

    /**
     * Get template categories
     */
    private function getTemplateCategories(int $templateId): array {
        return $this->db->query(
            "SELECT * FROM budget_template_categories
             WHERE template_id = ?
             ORDER BY priority, category_name",
            [$templateId]
        );
    }

    /**
     * Apply a template to create budgets for a user
     */
    public function applyTemplate(int $userId, int $templateId, array $customizations = [], string $month = null): array {
        $template = $this->getTemplate($templateId, $userId);
        if (!$template) {
            throw new \Exception('Template not found or access denied');
        }

        $month = $month ?? date('Y-m');
        $appliedBudgets = [];

        // Get user's preferred income for percentage calculations
        $preferredIncome = $this->getUserPreferredIncome($userId, $templateId);

        foreach ($template['categories'] as $category) {
            // Skip income categories for budget creation (they're for reference)
            if ($category['category_type'] === 'income') {
                continue;
            }

            // Calculate budget amount
            $amount = $this->calculateBudgetAmount($category, $preferredIncome, $customizations);

            if ($amount > 0) {
                // Check if budget already exists
                $existingBudget = $this->db->queryOne(
                    "SELECT id FROM budgets WHERE user_id = ? AND category_id = (
                        SELECT id FROM categories WHERE user_id = ? AND name = ? AND type = ?
                    ) AND month = ?",
                    [$userId, $userId, $category['category_name'], $category['category_type'], $month]
                );

                if (!$existingBudget) {
                    // Create category if it doesn't exist
                    $categoryId = $this->ensureCategoryExists($userId, $category['category_name'], $category['category_type']);

                    // Create budget
                    $budgetId = $this->db->insert('budgets', [
                        'user_id' => $userId,
                        'category_id' => $categoryId,
                        'month' => $month,
                        'amount' => $amount,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);

                    $appliedBudgets[] = [
                        'id' => $budgetId,
                        'category_name' => $category['category_name'],
                        'amount' => $amount,
                        'type' => $category['category_type']
                    ];
                }
            }
        }

        // Update user preferences
        $this->updateUserTemplatePreferences($userId, $templateId, $preferredIncome, $customizations);

        return $appliedBudgets;
    }

    /**
     * Calculate budget amount based on template category and customizations
     */
    private function calculateBudgetAmount(array $category, float $preferredIncome, array $customizations): float {
        // Check for customizations first
        $categoryKey = $category['category_name'];
        if (isset($customizations[$categoryKey])) {
            $custom = $customizations[$categoryKey];
            if (isset($custom['amount'])) {
                return (float)$custom['amount'];
            }
            if (isset($custom['percentage'])) {
                return $preferredIncome * ((float)$custom['percentage'] / 100);
            }
        }

        // Use template defaults
        if ($category['suggested_percentage'] > 0) {
            return $preferredIncome * ($category['suggested_percentage'] / 100);
        }

        return (float)$category['suggested_amount'];
    }

    /**
     * Ensure a category exists for the user
     */
    private function ensureCategoryExists(int $userId, string $categoryName, string $categoryType): int {
        $existing = $this->db->queryOne(
            "SELECT id FROM categories WHERE user_id = ? AND name = ? AND type = ?",
            [$userId, $categoryName, $categoryType]
        );

        if ($existing) {
            return $existing['id'];
        }

        // Create new category
        return $this->db->insert('categories', [
            'user_id' => $userId,
            'name' => $categoryName,
            'type' => $categoryType,
            'is_custom' => 0, // Template-generated categories
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get user's preferred income for template calculations
     */
    private function getUserPreferredIncome(int $userId, int $templateId): float {
        $preference = $this->db->queryOne(
            "SELECT preferred_income FROM user_template_preferences
             WHERE user_id = ? AND template_id = ?",
            [$userId, $templateId]
        );

        return $preference ? (float)$preference['preferred_income'] : 30000; // Default CZK
    }

    /**
     * Update user template preferences
     */
    private function updateUserTemplatePreferences(int $userId, int $templateId, float $income, array $customizations): void {
        $existing = $this->db->queryOne(
            "SELECT id FROM user_template_preferences WHERE user_id = ? AND template_id = ?",
            [$userId, $templateId]
        );

        $data = [
            'preferred_income' => $income,
            'customizations' => json_encode($customizations),
            'last_used_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($existing) {
            $this->db->update('user_template_preferences', $data, ['id' => $existing['id']]);
        } else {
            $data['user_id'] = $userId;
            $data['template_id'] = $templateId;
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('user_template_preferences', $data);
        }
    }

    /**
     * Get system default templates with their predefined categories
     */
    private function getDefaultTemplates(): array {
        return [
            [
                'id' => 1,
                'name' => 'Student Budget',
                'description' => 'Budget for students with limited income and focused spending',
                'template_type' => 'student',
                'is_default' => 1,
                'user_id' => null,
                'is_active' => 1
            ],
            [
                'id' => 2,
                'name' => 'Single Person Budget',
                'description' => 'Budget for single individuals with moderate income',
                'template_type' => 'single',
                'is_default' => 1,
                'user_id' => null,
                'is_active' => 1
            ],
            [
                'id' => 3,
                'name' => 'Family Budget',
                'description' => 'Budget for families with children and household expenses',
                'template_type' => 'family',
                'is_default' => 1,
                'user_id' => null,
                'is_active' => 1
            ],
            [
                'id' => 4,
                'name' => 'Retiree Budget',
                'description' => 'Budget for retirees with pension income and conservative spending',
                'template_type' => 'retiree',
                'is_default' => 1,
                'user_id' => null,
                'is_active' => 1
            ],
            [
                'id' => 5,
                'name' => 'Minimalist Budget',
                'description' => 'Minimal spending focused on essentials only',
                'template_type' => 'minimalist',
                'is_default' => 1,
                'user_id' => null,
                'is_active' => 1
            ],
            [
                'id' => 6,
                'name' => 'Luxury Budget',
                'description' => 'High-income budget with luxury spending categories',
                'template_type' => 'luxury',
                'is_default' => 1,
                'user_id' => null,
                'is_active' => 1
            ]
        ];
    }

    /**
     * Get categories for a specific default template
     */
    public function getDefaultTemplateCategories(int $templateId): array {
        $categories = [];

        switch ($templateId) {
            case 1: // Student
                $categories = [
                    ['category_name' => 'Income', 'category_type' => 'income', 'suggested_percentage' => 100, 'is_required' => 1, 'priority' => 1],
                    ['category_name' => 'Housing', 'category_type' => 'expense', 'suggested_percentage' => 25, 'is_required' => 1, 'priority' => 2],
                    ['category_name' => 'Food', 'category_type' => 'expense', 'suggested_percentage' => 20, 'is_required' => 1, 'priority' => 3],
                    ['category_name' => 'Transportation', 'category_type' => 'expense', 'suggested_percentage' => 10, 'is_required' => 1, 'priority' => 4],
                    ['category_name' => 'Education', 'category_type' => 'expense', 'suggested_percentage' => 15, 'is_required' => 1, 'priority' => 5],
                    ['category_name' => 'Entertainment', 'category_type' => 'expense', 'suggested_percentage' => 5, 'is_required' => 0, 'priority' => 6],
                    ['category_name' => 'Savings', 'category_type' => 'savings', 'suggested_percentage' => 10, 'is_required' => 1, 'priority' => 7],
                    ['category_name' => 'Miscellaneous', 'category_type' => 'expense', 'suggested_percentage' => 15, 'is_required' => 0, 'priority' => 8]
                ];
                break;

            case 2: // Single
                $categories = [
                    ['category_name' => 'Income', 'category_type' => 'income', 'suggested_percentage' => 100, 'is_required' => 1, 'priority' => 1],
                    ['category_name' => 'Housing', 'category_type' => 'expense', 'suggested_percentage' => 30, 'is_required' => 1, 'priority' => 2],
                    ['category_name' => 'Food', 'category_type' => 'expense', 'suggested_percentage' => 15, 'is_required' => 1, 'priority' => 3],
                    ['category_name' => 'Transportation', 'category_type' => 'expense', 'suggested_percentage' => 10, 'is_required' => 1, 'priority' => 4],
                    ['category_name' => 'Utilities', 'category_type' => 'expense', 'suggested_percentage' => 8, 'is_required' => 1, 'priority' => 5],
                    ['category_name' => 'Insurance', 'category_type' => 'expense', 'suggested_percentage' => 5, 'is_required' => 1, 'priority' => 6],
                    ['category_name' => 'Entertainment', 'category_type' => 'expense', 'suggested_percentage' => 7, 'is_required' => 0, 'priority' => 7],
                    ['category_name' => 'Shopping', 'category_type' => 'expense', 'suggested_percentage' => 5, 'is_required' => 0, 'priority' => 8],
                    ['category_name' => 'Savings', 'category_type' => 'savings', 'suggested_percentage' => 15, 'is_required' => 1, 'priority' => 9],
                    ['category_name' => 'Miscellaneous', 'category_type' => 'expense', 'suggested_percentage' => 5, 'is_required' => 0, 'priority' => 10]
                ];
                break;

            case 3: // Family
                $categories = [
                    ['category_name' => 'Income', 'category_type' => 'income', 'suggested_percentage' => 100, 'is_required' => 1, 'priority' => 1],
                    ['category_name' => 'Housing', 'category_type' => 'expense', 'suggested_percentage' => 25, 'is_required' => 1, 'priority' => 2],
                    ['category_name' => 'Food', 'category_type' => 'expense', 'suggested_percentage' => 20, 'is_required' => 1, 'priority' => 3],
                    ['category_name' => 'Transportation', 'category_type' => 'expense', 'suggested_percentage' => 12, 'is_required' => 1, 'priority' => 4],
                    ['category_name' => 'Utilities', 'category_type' => 'expense', 'suggested_percentage' => 8, 'is_required' => 1, 'priority' => 5],
                    ['category_name' => 'Children', 'category_type' => 'expense', 'suggested_percentage' => 15, 'is_required' => 1, 'priority' => 6],
                    ['category_name' => 'Insurance', 'category_type' => 'expense', 'suggested_percentage' => 5, 'is_required' => 1, 'priority' => 7],
                    ['category_name' => 'Entertainment', 'category_type' => 'expense', 'suggested_percentage' => 5, 'is_required' => 0, 'priority' => 8],
                    ['category_name' => 'Savings', 'category_type' => 'savings', 'suggested_percentage' => 10, 'is_required' => 1, 'priority' => 9]
                ];
                break;

            case 4: // Retiree
                $categories = [
                    ['category_name' => 'Income', 'category_type' => 'income', 'suggested_percentage' => 100, 'is_required' => 1, 'priority' => 1],
                    ['category_name' => 'Housing', 'category_type' => 'expense', 'suggested_percentage' => 25, 'is_required' => 1, 'priority' => 2],
                    ['category_name' => 'Food', 'category_type' => 'expense', 'suggested_percentage' => 15, 'is_required' => 1, 'priority' => 3],
                    ['category_name' => 'Healthcare', 'category_type' => 'expense', 'suggested_percentage' => 15, 'is_required' => 1, 'priority' => 4],
                    ['category_name' => 'Transportation', 'category_type' => 'expense', 'suggested_percentage' => 8, 'is_required' => 1, 'priority' => 5],
                    ['category_name' => 'Utilities', 'category_type' => 'expense', 'suggested_percentage' => 10, 'is_required' => 1, 'priority' => 6],
                    ['category_name' => 'Entertainment', 'category_type' => 'expense', 'suggested_percentage' => 7, 'is_required' => 0, 'priority' => 7],
                    ['category_name' => 'Savings', 'category_type' => 'savings', 'suggested_percentage' => 20, 'is_required' => 1, 'priority' => 8]
                ];
                break;

            case 5: // Minimalist
                $categories = [
                    ['category_name' => 'Income', 'category_type' => 'income', 'suggested_percentage' => 100, 'is_required' => 1, 'priority' => 1],
                    ['category_name' => 'Housing', 'category_type' => 'expense', 'suggested_percentage' => 30, 'is_required' => 1, 'priority' => 2],
                    ['category_name' => 'Food', 'category_type' => 'expense', 'suggested_percentage' => 20, 'is_required' => 1, 'priority' => 3],
                    ['category_name' => 'Transportation', 'category_type' => 'expense', 'suggested_percentage' => 10, 'is_required' => 1, 'priority' => 4],
                    ['category_name' => 'Utilities', 'category_type' => 'expense', 'suggested_percentage' => 8, 'is_required' => 1, 'priority' => 5],
                    ['category_name' => 'Insurance', 'category_type' => 'expense', 'suggested_percentage' => 5, 'is_required' => 1, 'priority' => 6],
                    ['category_name' => 'Savings', 'category_type' => 'savings', 'suggested_percentage' => 27, 'is_required' => 1, 'priority' => 7]
                ];
                break;

            case 6: // Luxury
                $categories = [
                    ['category_name' => 'Income', 'category_type' => 'income', 'suggested_percentage' => 100, 'is_required' => 1, 'priority' => 1],
                    ['category_name' => 'Housing', 'category_type' => 'expense', 'suggested_percentage' => 20, 'is_required' => 1, 'priority' => 2],
                    ['category_name' => 'Food', 'category_type' => 'expense', 'suggested_percentage' => 10, 'is_required' => 1, 'priority' => 3],
                    ['category_name' => 'Transportation', 'category_type' => 'expense', 'suggested_percentage' => 8, 'is_required' => 1, 'priority' => 4],
                    ['category_name' => 'Luxury', 'category_type' => 'expense', 'suggested_percentage' => 15, 'is_required' => 0, 'priority' => 5],
                    ['category_name' => 'Travel', 'category_type' => 'expense', 'suggested_percentage' => 10, 'is_required' => 0, 'priority' => 6],
                    ['category_name' => 'Entertainment', 'category_type' => 'expense', 'suggested_percentage' => 7, 'is_required' => 0, 'priority' => 7],
                    ['category_name' => 'Shopping', 'category_type' => 'expense', 'suggested_percentage' => 8, 'is_required' => 0, 'priority' => 8],
                    ['category_name' => 'Utilities', 'category_type' => 'expense', 'suggested_percentage' => 5, 'is_required' => 1, 'priority' => 9],
                    ['category_name' => 'Insurance', 'category_type' => 'expense', 'suggested_percentage' => 3, 'is_required' => 1, 'priority' => 10],
                    ['category_name' => 'Savings', 'category_type' => 'savings', 'suggested_percentage' => 14, 'is_required' => 1, 'priority' => 11]
                ];
                break;
        }

        return $categories;
    }
}