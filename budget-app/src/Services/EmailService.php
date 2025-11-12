<?php
namespace BudgetApp\Services;

use BudgetApp\Database;
use BudgetApp\Config;

/**
 * Email Service
 *
 * Handles email sending through various providers (SMTP, SendGrid, Mailgun, AWS SES)
 */
class EmailService {
    private Database $db;
    private Config $config;
    private string $provider;
    private array $settings;

    public function __construct(Database $db, Config $config) {
        $this->db = $db;
        $this->config = $config;

        // Load email configuration from environment or config
        $this->provider = $_ENV['MAIL_DRIVER'] ?? 'smtp';
        $this->settings = [
            'host' => $_ENV['MAIL_HOST'] ?? 'smtp.example.com',
            'port' => $_ENV['MAIL_PORT'] ?? 587,
            'username' => $_ENV['MAIL_USERNAME'] ?? '',
            'password' => $_ENV['MAIL_PASSWORD'] ?? '',
            'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
            'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@budgetcontrol.local',
            'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'Budget Control',
            'api_key' => $_ENV['MAIL_API_KEY'] ?? '',
        ];
    }

    /**
     * Send email using configured provider
     */
    public function send(string $to, string $subject, string $htmlBody, string $textBody = null): bool {
        try {
            switch ($this->provider) {
                case 'sendgrid':
                    return $this->sendViaSendGrid($to, $subject, $htmlBody, $textBody);
                case 'mailgun':
                    return $this->sendViaMailgun($to, $subject, $htmlBody, $textBody);
                case 'ses':
                    return $this->sendViaSES($to, $subject, $htmlBody, $textBody);
                case 'smtp':
                default:
                    return $this->sendViaSMTP($to, $subject, $htmlBody, $textBody);
            }
        } catch (\Exception $e) {
            error_log("Email send failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send budget alert email
     */
    public function sendBudgetAlert(array $user, array $budget, float $currentAmount, float $budgetAmount): bool {
        $percentage = ($currentAmount / $budgetAmount) * 100;

        $data = [
            'user_name' => $user['name'],
            'budget_name' => $budget['name'],
            'current_amount' => number_format($currentAmount, 2),
            'budget_amount' => number_format($budgetAmount, 2),
            'percentage' => round($percentage, 1),
            'currency' => $user['currency'] ?? 'CZK',
        ];

        $html = $this->renderTemplate('budget-alert', $data);
        $text = $this->renderTextTemplate('budget-alert', $data);

        return $this->send(
            $user['email'],
            "⚠️ Budget Alert: {$budget['name']}",
            $html,
            $text
        );
    }

    /**
     * Send goal milestone notification
     */
    public function sendGoalMilestone(array $user, array $goal, array $milestone): bool {
        $data = [
            'user_name' => $user['name'],
            'goal_name' => $goal['name'],
            'milestone_name' => $milestone['name'],
            'milestone_amount' => number_format($milestone['amount'], 2),
            'progress_percentage' => round(($goal['current_amount'] / $goal['target_amount']) * 100, 1),
            'currency' => $user['currency'] ?? 'CZK',
        ];

        $html = $this->renderTemplate('goal-milestone', $data);
        $text = $this->renderTextTemplate('goal-milestone', $data);

        return $this->send(
            $user['email'],
            "🎯 Goal Milestone Reached: {$goal['name']}",
            $html,
            $text
        );
    }

    /**
     * Send bill reminder
     */
    public function sendBillReminder(array $user, array $bill): bool {
        $daysUntilDue = floor((strtotime($bill['due_date']) - time()) / 86400);

        $data = [
            'user_name' => $user['name'],
            'bill_name' => $bill['name'],
            'amount' => number_format($bill['amount'], 2),
            'due_date' => date('d.m.Y', strtotime($bill['due_date'])),
            'days_until_due' => $daysUntilDue,
            'currency' => $user['currency'] ?? 'CZK',
        ];

        $html = $this->renderTemplate('bill-reminder', $data);
        $text = $this->renderTextTemplate('bill-reminder', $data);

        return $this->send(
            $user['email'],
            "📅 Bill Reminder: {$bill['name']} due in {$daysUntilDue} days",
            $html,
            $text
        );
    }

    /**
     * Send weekly summary
     */
    public function sendWeeklySummary(array $user, array $summary): bool {
        $data = [
            'user_name' => $user['name'],
            'week_start' => date('d.m.Y', strtotime($summary['week_start'])),
            'week_end' => date('d.m.Y', strtotime($summary['week_end'])),
            'total_income' => number_format($summary['total_income'], 2),
            'total_expenses' => number_format($summary['total_expenses'], 2),
            'net_savings' => number_format($summary['total_income'] - $summary['total_expenses'], 2),
            'top_categories' => $summary['top_categories'],
            'currency' => $user['currency'] ?? 'CZK',
        ];

        $html = $this->renderTemplate('weekly-summary', $data);
        $text = $this->renderTextTemplate('weekly-summary', $data);

        return $this->send(
            $user['email'],
            "📊 Your Weekly Financial Summary",
            $html,
            $text
        );
    }

    /**
     * Render HTML email template
     */
    private function renderTemplate(string $template, array $data): string {
        $templatePath = $this->config->getBasePath() . "/views/emails/{$template}.php";

        if (!file_exists($templatePath)) {
            throw new \Exception("Email template not found: {$template}");
        }

        // Extract data to variables
        extract($data);

        // Capture template output
        ob_start();
        include $templatePath;
        $content = ob_get_clean();

        // Wrap in layout
        $layoutPath = $this->config->getBasePath() . "/views/emails/layout.php";
        if (file_exists($layoutPath)) {
            ob_start();
            include $layoutPath;
            return ob_get_clean();
        }

        return $content;
    }

    /**
     * Render plain text email template
     */
    private function renderTextTemplate(string $template, array $data): string {
        $templatePath = $this->config->getBasePath() . "/views/emails/{$template}.txt";

        if (!file_exists($templatePath)) {
            // Generate simple text version from data
            return strip_tags($this->renderTemplate($template, $data));
        }

        extract($data);
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }

    /**
     * Send via SMTP
     */
    private function sendViaSMTP(string $to, string $subject, string $htmlBody, ?string $textBody): bool {
        $headers = [
            "From: {$this->settings['from_name']} <{$this->settings['from_address']}>",
            "Reply-To: {$this->settings['from_address']}",
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=UTF-8",
        ];

        // Use PHP's mail() function (requires configured mail server)
        return mail($to, $subject, $htmlBody, implode("\r\n", $headers));
    }

    /**
     * Send via SendGrid API
     */
    private function sendViaSendGrid(string $to, string $subject, string $htmlBody, ?string $textBody): bool {
        $url = 'https://api.sendgrid.com/v3/mail/send';

        $data = [
            'personalizations' => [
                ['to' => [['email' => $to]]]
            ],
            'from' => [
                'email' => $this->settings['from_address'],
                'name' => $this->settings['from_name']
            ],
            'subject' => $subject,
            'content' => [
                ['type' => 'text/html', 'value' => $htmlBody]
            ]
        ];

        if ($textBody) {
            $data['content'][] = ['type' => 'text/plain', 'value' => $textBody];
        }

        return $this->sendApiRequest($url, $data, [
            "Authorization: Bearer {$this->settings['api_key']}",
            "Content-Type: application/json"
        ]);
    }

    /**
     * Send via Mailgun API
     */
    private function sendViaMailgun(string $to, string $subject, string $htmlBody, ?string $textBody): bool {
        $domain = $_ENV['MAILGUN_DOMAIN'] ?? 'mg.example.com';
        $url = "https://api.mailgun.net/v3/{$domain}/messages";

        $data = [
            'from' => "{$this->settings['from_name']} <{$this->settings['from_address']}>",
            'to' => $to,
            'subject' => $subject,
            'html' => $htmlBody,
        ];

        if ($textBody) {
            $data['text'] = $textBody;
        }

        return $this->sendApiRequest($url, $data, [
            "Authorization: Basic " . base64_encode("api:{$this->settings['api_key']}")
        ], 'POST', true);
    }

    /**
     * Send via AWS SES
     */
    private function sendViaSES(string $to, string $subject, string $htmlBody, ?string $textBody): bool {
        // AWS SES implementation would require AWS SDK
        // For now, fallback to SMTP
        return $this->sendViaSMTP($to, $subject, $htmlBody, $textBody);
    }

    /**
     * Helper to send API requests
     */
    private function sendApiRequest(string $url, array $data, array $headers, string $method = 'POST', bool $formData = false): bool {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($formData) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $statusCode >= 200 && $statusCode < 300;
    }

    /**
     * Check if user has email notifications enabled for a type
     */
    public function isNotificationEnabled(int $userId, string $notificationType): bool {
        $result = $this->db->query(
            "SELECT setting_value FROM user_settings
             WHERE user_id = ? AND category = 'notifications' AND setting_key = ?",
            [$userId, "email_{$notificationType}"]
        );

        if (empty($result)) {
            return true; // Default to enabled
        }

        return $result[0]['setting_value'] === '1' || $result[0]['setting_value'] === 'true';
    }
}
