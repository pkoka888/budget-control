<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

class UserSettingsService {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Get all settings for a user, organized by category
     */
    public function getAllSettings(int $userId): array {
        $settings = $this->db->query(
            "SELECT category, setting_key, setting_value FROM user_settings WHERE user_id = ?",
            [$userId]
        );

        $organized = [];
        foreach ($settings as $setting) {
            $organized[$setting['category']][$setting['setting_key']] = $setting['setting_value'];
        }

        return $organized;
    }

    /**
     * Get settings for a specific category
     */
    public function getSettingsByCategory(int $userId, string $category): array {
        $settings = $this->db->query(
            "SELECT setting_key, setting_value FROM user_settings WHERE user_id = ? AND category = ?",
            [$userId, $category]
        );

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['setting_key']] = $setting['setting_value'];
        }

        return $result;
    }

    /**
     * Update settings for a category
     */
    public function updateSettings(int $userId, string $category, array $settings): bool {
        $this->db->beginTransaction();

        try {
            foreach ($settings as $key => $value) {
                $this->setSetting($userId, $category, $key, $value);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Set a single setting value
     */
    public function setSetting(int $userId, string $category, string $key, $value): void {
        $existing = $this->db->queryOne(
            "SELECT id FROM user_settings WHERE user_id = ? AND category = ? AND setting_key = ?",
            [$userId, $category, $key]
        );

        if ($existing) {
            $this->db->update('user_settings', [
                'setting_value' => $value,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $existing['id']]);
        } else {
            $this->db->insert('user_settings', [
                'user_id' => $userId,
                'category' => $category,
                'setting_key' => $key,
                'setting_value' => $value
            ]);
        }
    }

    /**
     * Get a single setting value
     */
    public function getSetting(int $userId, string $category, string $key, $default = null) {
        $setting = $this->db->queryOne(
            "SELECT setting_value FROM user_settings WHERE user_id = ? AND category = ? AND setting_key = ?",
            [$userId, $category, $key]
        );

        return $setting ? $setting['setting_value'] : $default;
    }

    /**
     * Delete a setting
     */
    public function deleteSetting(int $userId, string $category, string $key): bool {
        return $this->db->delete('user_settings', [
            'user_id' => $userId,
            'category' => $category,
            'setting_key' => $key
        ]);
    }

    /**
     * Get notification settings with defaults
     */
    public function getNotificationSettings(int $userId): array {
        $settings = $this->getSettingsByCategory($userId, 'notifications');

        return [
            'email_enabled' => $settings['email_enabled'] ?? '1',
            'budget_alerts' => $settings['budget_alerts'] ?? '1',
            'goal_reminders' => $settings['goal_reminders'] ?? '1',
            'weekly_reports' => $settings['weekly_reports'] ?? '0',
            'monthly_reports' => $settings['monthly_reports'] ?? '1',
            'alert_frequency' => $settings['alert_frequency'] ?? 'immediate'
        ];
    }

    /**
     * Get application preferences with defaults
     */
    public function getApplicationPreferences(int $userId): array {
        $settings = $this->getSettingsByCategory($userId, 'preferences');

        return [
            'currency' => $settings['currency'] ?? 'CZK',
            'date_format' => $settings['date_format'] ?? 'd.m.Y',
            'theme' => $settings['theme'] ?? 'light',
            'language' => $settings['language'] ?? 'cs',
            'timezone' => $settings['timezone'] ?? 'Europe/Prague',
            'items_per_page' => (int)($settings['items_per_page'] ?? 25)
        ];
    }

    /**
     * Get security settings with defaults
     */
    public function getSecuritySettings(int $userId): array {
        $settings = $this->getSettingsByCategory($userId, 'security');

        return [
            'two_factor_enabled' => $settings['two_factor_enabled'] ?? '0',
            'session_timeout' => (int)($settings['session_timeout'] ?? 3600),
            'password_last_changed' => $settings['password_last_changed'] ?? null,
            'login_notifications' => $settings['login_notifications'] ?? '1'
        ];
    }
    /**
     * Enable two-factor authentication for a user
     */
    public function enable2FA(int $userId): array {
        // Generate random secret (32 bytes = 256 bits)
        $secret = $this->generateTOTPSecret();

        // Generate backup codes
        $backupCodes = $this->generateBackupCodes();

        // Get user info for QR code
        $user = $this->db->queryOne("SELECT name, email FROM users WHERE id = ?", [$userId]);
        $issuer = 'Budget App';
        $accountName = $user['email'];

        // Store in settings
        $this->setSetting($userId, 'security', 'two_factor_secret', $secret);
        $this->setSetting($userId, 'security', 'two_factor_backup_codes', json_encode($backupCodes));
        $this->setSetting($userId, 'security', 'two_factor_enabled', '0'); // Not enabled until verified

        return [
            'secret' => $secret,
            'backup_codes' => $backupCodes,
            'qr_code_uri' => $this->generateQRCodeURI($issuer, $accountName, $secret)
        ];
    }

    /**
     * Disable two-factor authentication for a user
     */
    public function disable2FA(int $userId): bool {
        $this->deleteSetting($userId, 'security', 'two_factor_secret');
        $this->deleteSetting($userId, 'security', 'two_factor_backup_codes');
        $this->setSetting($userId, 'security', 'two_factor_enabled', '0');

        return true;
    }

    /**
     * Verify two-factor authentication token or backup code
     */
    public function verify2FA(int $userId, string $token): bool {
        $secret = $this->getSetting($userId, 'security', 'two_factor_secret');

        if (!$secret) {
            return false;
        }

        // Check if it's a backup code first
        $backupCodesJson = $this->getSetting($userId, 'security', 'two_factor_backup_codes');
        if ($backupCodesJson) {
            $backupCodes = json_decode($backupCodesJson, true);
            $tokenIndex = array_search($token, $backupCodes);
            if ($tokenIndex !== false) {
                // Remove used backup code
                unset($backupCodes[$tokenIndex]);
                $this->setSetting($userId, 'security', 'two_factor_backup_codes', json_encode(array_values($backupCodes)));
                return true;
            }
        }

        // Verify TOTP token
        return $this->verifyTOTP($secret, $token);
    }

    /**
     * Generate backup codes for 2FA
     */
    private function generateBackupCodes(int $count = 10): array {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        }
        return $codes;
    }

    /**
     * Generate a random TOTP secret
     */
    private function generateTOTPSecret(int $length = 32): string {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $secret;
    }

    /**
     * Generate QR code provisioning URI for TOTP
     */
    private function generateQRCodeURI(string $issuer, string $accountName, string $secret): string {
        $label = urlencode($issuer . ':' . $accountName);
        return "otpauth://totp/{$label}?secret={$secret}&issuer=" . urlencode($issuer);
    }

    /**
     * Verify TOTP token
     */
    private function verifyTOTP(string $secret, string $token, int $window = 1): bool {
        $time = floor(time() / 30); // TOTP uses 30-second windows

        // Check current time window and adjacent windows
        for ($i = -$window; $i <= $window; $i++) {
            $timeWindow = $time + $i;
            $hash = hash_hmac('sha1', pack('N*', 0) . pack('N*', $timeWindow), $this->base32Decode($secret), true);
            $offset = ord($hash[19]) & 0xf;
            $code = (
                ((ord($hash[$offset]) & 0x7f) << 24) |
                ((ord($hash[$offset + 1]) & 0xff) << 16) |
                ((ord($hash[$offset + 2]) & 0xff) << 8) |
                (ord($hash[$offset + 3]) & 0xff)
            ) % 1000000;

            if (str_pad($code, 6, '0', STR_PAD_LEFT) === $token) {
                return true;
            }
        }

        return false;
    }

    /**
     * Decode base32 string
     */
    private function base32Decode(string $base32): string {
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32charsflipped = array_flip(str_split($base32chars));

        $binary = '';
        $base32 = strtoupper($base32);

        for ($i = 0; $i < strlen($base32); $i++) {
            if (!isset($base32charsflipped[$base32[$i]])) {
                continue;
            }
            $val = $base32charsflipped[$base32[$i]];
            $binary .= str_pad(decbin($val), 5, '0', STR_PAD_LEFT);
        }

        $chunks = str_split($binary, 8);
        $decoded = '';
        foreach ($chunks as $chunk) {
            if (strlen($chunk) === 8) {
                $decoded .= chr(bindec($chunk));
            }
        }

        return $decoded;
    }

    /**
     * Export user data
     */
    public function exportUserData(int $userId): array {
        $data = [
            'user' => $this->db->queryOne("SELECT id, name, email, currency, timezone, created_at FROM users WHERE id = ?", [$userId]),
            'accounts' => $this->db->query("SELECT * FROM accounts WHERE user_id = ?", [$userId]),
            'categories' => $this->db->query("SELECT * FROM categories WHERE user_id = ?", [$userId]),
            'transactions' => $this->db->query("SELECT * FROM transactions WHERE user_id = ?", [$userId]),
            'budgets' => $this->db->query("SELECT * FROM budgets WHERE user_id = ?", [$userId]),
            'goals' => $this->db->query("SELECT * FROM goals WHERE user_id = ?", [$userId]),
            'investments' => $this->db->query("SELECT * FROM investments WHERE user_id = ?", [$userId]),
            'settings' => $this->getAllSettings($userId),
            'exported_at' => date('Y-m-d H:i:s')
        ];

        return $data;
    }

    /**
     * Import user data
     */
    public function importUserData(int $userId, array $data): array {
        $results = [
            'success' => true,
            'imported' => [],
            'errors' => []
        ];

        $this->db->beginTransaction();

        try {
            // Import categories
            if (isset($data['categories'])) {
                foreach ($data['categories'] as $category) {
                    try {
                        $this->db->insert('categories', [
                            'user_id' => $userId,
                            'name' => $category['name'],
                            'type' => $category['type'],
                            'color' => $category['color'] ?? '#3b82f6',
                            'icon' => $category['icon'] ?? 'tag',
                            'description' => $category['description'] ?? null,
                            'is_custom' => 1
                        ]);
                        $results['imported']['categories'][] = $category['name'];
                    } catch (\Exception $e) {
                        $results['errors'][] = "Failed to import category {$category['name']}: " . $e->getMessage();
                    }
                }
            }

            // Import accounts
            if (isset($data['accounts'])) {
                foreach ($data['accounts'] as $account) {
                    try {
                        $this->db->insert('accounts', [
                            'user_id' => $userId,
                            'name' => $account['name'],
                            'type' => $account['type'],
                            'currency' => $account['currency'] ?? 'CZK',
                            'balance' => $account['balance'] ?? 0,
                            'initial_balance' => $account['initial_balance'] ?? 0,
                            'opening_date' => $account['opening_date'] ?? null,
                            'description' => $account['description'] ?? null,
                            'is_active' => $account['is_active'] ?? 1
                        ]);
                        $results['imported']['accounts'][] = $account['name'];
                    } catch (\Exception $e) {
                        $results['errors'][] = "Failed to import account {$account['name']}: " . $e->getMessage();
                    }
                }
            }

            // Import budgets
            if (isset($data['budgets'])) {
                foreach ($data['budgets'] as $budget) {
                    try {
                        $this->db->insert('budgets', [
                            'user_id' => $userId,
                            'category_id' => $budget['category_id'],
                            'amount' => $budget['amount'],
                            'period' => $budget['period'],
                            'start_date' => $budget['start_date'],
                            'end_date' => $budget['end_date'] ?? null,
                            'description' => $budget['description'] ?? null,
                            'is_active' => $budget['is_active'] ?? 1
                        ]);
                        $results['imported']['budgets'][] = $budget['category_id'] . ' (' . $budget['amount'] . ')';
                    } catch (\Exception $e) {
                        $results['errors'][] = "Failed to import budget: " . $e->getMessage();
                    }
                }
            }

            // Import goals
            if (isset($data['goals'])) {
                foreach ($data['goals'] as $goal) {
                    try {
                        $this->db->insert('goals', [
                            'user_id' => $userId,
                            'name' => $goal['name'],
                            'description' => $goal['description'] ?? null,
                            'target_amount' => $goal['target_amount'],
                            'current_amount' => $goal['current_amount'] ?? 0,
                            'target_date' => $goal['target_date'],
                            'category' => $goal['category'] ?? null,
                            'priority' => $goal['priority'] ?? 'medium',
                            'status' => $goal['status'] ?? 'active'
                        ]);
                        $results['imported']['goals'][] = $goal['name'];
                    } catch (\Exception $e) {
                        $results['errors'][] = "Failed to import goal {$goal['name']}: " . $e->getMessage();
                    }
                }
            }

            // Import investments
            if (isset($data['investments'])) {
                foreach ($data['investments'] as $investment) {
                    try {
                        $this->db->insert('investments', [
                            'user_id' => $userId,
                            'name' => $investment['name'],
                            'symbol' => $investment['symbol'] ?? null,
                            'type' => $investment['type'],
                            'quantity' => $investment['quantity'] ?? 0,
                            'purchase_price' => $investment['purchase_price'] ?? 0,
                            'current_price' => $investment['current_price'] ?? 0,
                            'purchase_date' => $investment['purchase_date'] ?? null,
                            'description' => $investment['description'] ?? null
                        ]);
                        $results['imported']['investments'][] = $investment['name'];
                    } catch (\Exception $e) {
                        $results['errors'][] = "Failed to import investment {$investment['name']}: " . $e->getMessage();
                    }
                }
            }

            // Import transactions last (depends on accounts and categories)
            if (isset($data['transactions'])) {
                foreach ($data['transactions'] as $transaction) {
                    try {
                        $this->db->insert('transactions', [
                            'user_id' => $userId,
                            'account_id' => $transaction['account_id'],
                            'category_id' => $transaction['category_id'] ?? null,
                            'amount' => $transaction['amount'],
                            'description' => $transaction['description'] ?? null,
                            'transaction_date' => $transaction['transaction_date'],
                            'type' => $transaction['type'],
                            'reference' => $transaction['reference'] ?? null,
                            'notes' => $transaction['notes'] ?? null,
                            'is_recurring' => $transaction['is_recurring'] ?? 0,
                            'parent_transaction_id' => $transaction['parent_transaction_id'] ?? null
                        ]);
                        $results['imported']['transactions'][] = $transaction['description'] ?? 'Transaction';
                    } catch (\Exception $e) {
                        $results['errors'][] = "Failed to import transaction: " . $e->getMessage();
                    }
                }
            }

            // Import settings
            if (isset($data['settings'])) {
                foreach ($data['settings'] as $category => $settings) {
                    foreach ($settings as $key => $value) {
                        $this->setSetting($userId, $category, $key, $value);
                    }
                }
                $results['imported']['settings'] = array_keys($data['settings']);
            }

            $this->db->commit();

        } catch (\Exception $e) {
            $this->db->rollback();
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Delete user account and all associated data
     */
    public function deleteUserAccount(int $userId): bool {
        $this->db->beginTransaction();

        try {
            // Delete in order of foreign key dependencies
            $this->db->delete('user_settings', ['user_id' => $userId]);
            $this->db->delete('budget_alerts', ['user_id' => $userId]);
            $this->db->delete('ai_recommendations', ['user_id' => $userId]);
            // Delete transaction-related data first
            $transactionIds = $this->db->query("SELECT id FROM transactions WHERE user_id = ?", [$userId]);
            if (!empty($transactionIds)) {
                $transactionIdList = array_column($transactionIds, 'id');
                $this->db->delete('transaction_splits', ['parent_transaction_id' => $transactionIdList]);
            }
            $this->db->delete('transactions', ['user_id' => $userId]);

            // Delete budget-related data
            $this->db->delete('budgets', ['user_id' => $userId]);

            // Delete goal-related data
            $goalIds = $this->db->query("SELECT id FROM goals WHERE user_id = ?", [$userId]);
            if (!empty($goalIds)) {
                $goalIdList = array_column($goalIds, 'id');
                $this->db->delete('goal_milestones', ['goal_id' => $goalIdList]);
            }
            $this->db->delete('goals', ['user_id' => $userId]);

            // Delete investment-related data
            $investmentIds = $this->db->query("SELECT id FROM investments WHERE user_id = ?", [$userId]);
            if (!empty($investmentIds)) {
                $investmentIdList = array_column($investmentIds, 'id');
                $this->db->delete('investment_transactions', ['investment_id' => $investmentIdList]);
                $this->db->delete('investment_prices', ['investment_id' => $investmentIdList]);
            }
            $this->db->delete('investments', ['user_id' => $userId]);
            $this->db->delete('investment_accounts', ['user_id' => $userId]);
            $this->db->delete('categorization_rules', ['user_id' => $userId]);
            $this->db->delete('financial_metrics', ['user_id' => $userId]);
            $this->db->delete('recurring_transactions', ['user_id' => $userId]);
            $this->db->delete('csv_imports', ['user_id' => $userId]);
            $this->db->delete('exchange_rates', ['source' => 'user_' . $userId]);
            $this->db->delete('merchants', ['user_id' => $userId]);
            $this->db->delete('categories', ['user_id' => $userId]);
            $this->db->delete('accounts', ['user_id' => $userId]);
            $this->db->delete('users', ['id' => $userId]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Validate settings data
     */
    public function validateSettings(string $category, array $data): array {
        $errors = [];

        switch ($category) {
            case 'profile':
                if (empty($data['name'])) {
                    $errors['name'] = 'Name is required';
                }
                if (empty($data['email'])) {
                    $errors['email'] = 'Email is required';
                } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors['email'] = 'Invalid email format';
                }
                break;

            case 'notifications':
                $validFrequencies = ['immediate', 'daily', 'weekly'];
                if (isset($data['alert_frequency']) && !in_array($data['alert_frequency'], $validFrequencies)) {
                    $errors['alert_frequency'] = 'Invalid alert frequency';
                }
                break;

            case 'preferences':
                $validCurrencies = ['CZK', 'EUR', 'USD', 'GBP'];
                if (isset($data['currency']) && !in_array($data['currency'], $validCurrencies)) {
                    $errors['currency'] = 'Invalid currency';
                }
                $validThemes = ['light', 'dark', 'auto'];
                if (isset($data['theme']) && !in_array($data['theme'], $validThemes)) {
                    $errors['theme'] = 'Invalid theme';
                }
                break;

            case 'security':
                if (isset($data['session_timeout']) && (!is_numeric($data['session_timeout']) || $data['session_timeout'] < 300)) {
                    $errors['session_timeout'] = 'Session timeout must be at least 300 seconds';
                }
                break;
        }

        return $errors;
    }
}