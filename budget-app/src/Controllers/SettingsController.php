<?php
namespace BudgetApp\Controllers;

use BudgetApp\Services\UserSettingsService;

class SettingsController extends BaseController {
    private UserSettingsService $settingsService;

    public function __construct($app) {
        parent::__construct($app);
        $this->settingsService = new UserSettingsService($this->db);
    }

    public function show(array $params = []): void {
        $userId = $this->getUserId();
        $user = $this->getUser();

        // Get all settings organized by category
        $settings = $this->settingsService->getAllSettings($userId);

        // Get settings with defaults for each category
        $notificationSettings = $this->settingsService->getNotificationSettings($userId);
        $applicationPreferences = $this->settingsService->getApplicationPreferences($userId);
        $securitySettings = $this->settingsService->getSecuritySettings($userId);

        echo $this->app->render('settings/show', [
            'user' => $user,
            'settings' => $settings,
            'notificationSettings' => $notificationSettings,
            'applicationPreferences' => $applicationPreferences,
            'securitySettings' => $securitySettings
        ]);
    }

    public function updateProfile(array $params = []): void {
        $userId = $this->getUserId();

        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';

        // Validate input
        $errors = $this->settingsService->validateSettings('profile', ['name' => $name, 'email' => $email]);
        if (!empty($errors)) {
            $this->setFlash('error', implode(', ', $errors));
            $this->redirect('/settings');
        }

        // Check if email is already used by another user
        $existing = $this->db->queryOne(
            "SELECT id FROM users WHERE email = ? AND id != ?",
            [$email, $userId]
        );

        if ($existing) {
            $this->setFlash('error', 'Tento e-mail je již registrován');
            $this->redirect('/settings');
        }

        // Update user table
        $this->db->update('users', ['name' => $name, 'email' => $email], ['id' => $userId]);

        // Update settings
        $this->settingsService->updateSettings($userId, 'profile', [
            'name' => $name,
            'email' => $email
        ]);

        $this->setFlash('success', 'Profil aktualizován');
        $this->redirect('/settings');
    }

    public function updateNotifications(array $params = []): void {
        $userId = $this->getUserId();

        $settings = [
            'email_enabled' => $_POST['email_enabled'] ?? '0',
            'budget_alerts' => $_POST['budget_alerts'] ?? '0',
            'goal_reminders' => $_POST['goal_reminders'] ?? '0',
            'weekly_reports' => $_POST['weekly_reports'] ?? '0',
            'monthly_reports' => $_POST['monthly_reports'] ?? '0',
            'alert_frequency' => $_POST['alert_frequency'] ?? 'immediate'
        ];

        // Validate settings
        $errors = $this->settingsService->validateSettings('notifications', $settings);
        if (!empty($errors)) {
            $this->setFlash('error', implode(', ', $errors));
            $this->redirect('/settings');
        }

        $this->settingsService->updateSettings($userId, 'notifications', $settings);

        $this->setFlash('success', 'Notification settings updated');
        $this->redirect('/settings');
    }

    public function updatePreferences(array $params = []): void {
        $userId = $this->getUserId();

        $settings = [
            'currency' => $_POST['currency'] ?? 'CZK',
            'date_format' => $_POST['date_format'] ?? 'd.m.Y',
            'theme' => $_POST['theme'] ?? 'light',
            'language' => $_POST['language'] ?? 'cs',
            'timezone' => $_POST['timezone'] ?? 'Europe/Prague',
            'items_per_page' => (int)($_POST['items_per_page'] ?? 25)
        ];

        // Validate settings
        $errors = $this->settingsService->validateSettings('preferences', $settings);
        if (!empty($errors)) {
            $this->setFlash('error', implode(', ', $errors));
            $this->redirect('/settings');
        }

        // Update both user table and settings
        $this->db->update('users', [
            'currency' => $settings['currency'],
            'timezone' => $settings['timezone']
        ], ['id' => $userId]);

        $this->settingsService->updateSettings($userId, 'preferences', $settings);

        $this->setFlash('success', 'Preferences updated');
        $this->redirect('/settings');
    }

    public function updateSecurity(array $params = []): void {
        $userId = $this->getUserId();

        $settings = [
            'two_factor_enabled' => $_POST['two_factor_enabled'] ?? '0',
            'session_timeout' => (int)($_POST['session_timeout'] ?? 3600),
            'login_notifications' => $_POST['login_notifications'] ?? '0'
        ];

        // Validate settings
        $errors = $this->settingsService->validateSettings('security', $settings);
        if (!empty($errors)) {
            $this->setFlash('error', implode(', ', $errors));
            $this->redirect('/settings');
        }

        $this->settingsService->updateSettings($userId, 'security', $settings);

        $this->setFlash('success', 'Security settings updated');
        $this->redirect('/settings');
    }

    public function updateSettings(array $params = []): void {
        $userId = $this->getUserId();
        $category = $_POST['category'] ?? '';

        if (empty($category)) {
            $this->setFlash('error', 'Category is required');
            $this->redirect('/settings');
        }

        $settings = $_POST;
        unset($settings['category']);

        // Validate settings
        $errors = $this->settingsService->validateSettings($category, $settings);
        if (!empty($errors)) {
            $this->setFlash('error', implode(', ', $errors));
            $this->redirect('/settings');
        }

        $this->settingsService->updateSettings($userId, $category, $settings);

        $this->setFlash('success', ucfirst($category) . ' settings updated');
        $this->redirect('/settings');
    }

    public function exportData(array $params = []): void {
        $userId = $this->getUserId();

        try {
            $data = $this->settingsService->exportUserData($userId);

            // Set headers for JSON download
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="budget-data-export-' . date('Y-m-d') . '.json"');
            header('Cache-Control: no-cache, no-store, must-revalidate');

            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;

        } catch (\Exception $e) {
            $this->setFlash('error', 'Export failed: ' . $e->getMessage());
            $this->redirect('/settings');
        }
    }

    public function importData(array $params = []): void {
        $userId = $this->getUserId();

        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            $this->setFlash('error', 'No file uploaded or upload error');
            $this->redirect('/settings');
        }

        $file = $_FILES['import_file'];
        $fileContent = file_get_contents($file['tmp_name']);

        try {
            $data = json_decode($fileContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON file');
            }

            $result = $this->settingsService->importUserData($userId, $data);

            if ($result['success']) {
                $message = 'Data imported successfully. ';
                if (!empty($result['imported'])) {
                    $message .= 'Imported: ' . implode(', ', array_map(function($k, $v) {
                        return $k . ' (' . count($v) . ')';
                    }, array_keys($result['imported']), $result['imported']));
                }
                $this->setFlash('success', $message);
            } else {
                $this->setFlash('error', 'Import failed: ' . implode(', ', $result['errors']));
            }

        } catch (\Exception $e) {
            $this->setFlash('error', 'Import failed: ' . $e->getMessage());
        }

        $this->redirect('/settings');
    }

    public function deleteAccount(array $params = []): void {
        $userId = $this->getUserId();
        $confirmation = $_POST['confirmation'] ?? '';

        if ($confirmation !== 'DELETE') {
            $this->setFlash('error', 'Please type DELETE to confirm account deletion');
            $this->redirect('/settings');
        }

        try {
            // Log out user before deletion
            session_destroy();

            $success = $this->settingsService->deleteUserAccount($userId);

            if ($success) {
                header('Location: /?message=Account deleted successfully');
                exit;
            } else {
                $this->setFlash('error', 'Account deletion failed');
                $this->redirect('/settings');
            }

        } catch (\Exception $e) {
            $this->setFlash('error', 'Account deletion failed: ' . $e->getMessage());
            $this->redirect('/settings');
        }
    }

    public function changePassword(array $params = []): void {
        $userId = $this->getUserId();

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $this->setFlash('error', 'All password fields are required');
            $this->redirect('/settings');
        }

        if ($newPassword !== $confirmPassword) {
            $this->setFlash('error', 'New passwords do not match');
            $this->redirect('/settings');
        }

        if (strlen($newPassword) < 8) {
            $this->setFlash('error', 'Password must be at least 8 characters long');
            $this->redirect('/settings');
        }

        // Verify current password
        $user = $this->db->queryOne("SELECT password_hash FROM users WHERE id = ?", [$userId]);
        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            $this->setFlash('error', 'Current password is incorrect');
            $this->redirect('/settings');
        }

        // Update password
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->update('users', ['password_hash' => $newHash], ['id' => $userId]);

        // Update security settings
        $this->settingsService->setSetting($userId, 'security', 'password_last_changed', date('Y-m-d H:i:s'));

        $this->setFlash('success', 'Password changed successfully');
        $this->redirect('/settings');
    }
    public function enable2FA(array $params = []): void {
        $userId = $this->getUserId();

        try {
            $result = $this->settingsService->enable2FA($userId);

            echo $this->app->render('settings/2fa_setup', [
                'user' => $this->getUser(),
                'secret' => $result['secret'],
                'backup_codes' => $result['backup_codes'],
                'qr_code_uri' => $result['qr_code_uri']
            ]);
        } catch (\Exception $e) {
            $this->setFlash('error', 'Failed to enable 2FA: ' . $e->getMessage());
            $this->redirect('/settings');
        }
    }

    public function verify2FA(array $params = []): void {
        $userId = $this->getUserId();
        $token = $_POST['token'] ?? '';

        if (empty($token)) {
            $this->setFlash('error', 'Token is required');
            $this->redirect('/settings/2fa/setup');
        }

        if ($this->settingsService->verify2FA($userId, $token)) {
            // Enable 2FA
            $this->settingsService->setSetting($userId, 'security', 'two_factor_enabled', '1');
            $this->setFlash('success', 'Two-factor authentication has been enabled');
            $this->redirect('/settings');
        } else {
            $this->setFlash('error', 'Invalid token. Please try again.');
            $this->redirect('/settings/2fa/setup');
        }
    }

    public function disable2FA(array $params = []): void {
        $userId = $this->getUserId();

        try {
            $this->settingsService->disable2FA($userId);
            $this->setFlash('success', 'Two-factor authentication has been disabled');
            $this->redirect('/settings');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Failed to disable 2FA: ' . $e->getMessage());
            $this->redirect('/settings');
        }
    }
}
?>
