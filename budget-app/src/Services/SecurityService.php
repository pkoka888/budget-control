<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

class SecurityService {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Encrypt sensitive data
     */
    public function encryptData(string $data): string {
        $key = $this->getEncryptionKey();
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt sensitive data
     */
    public function decryptData(string $encryptedData): string {
        $key = $this->getEncryptionKey();
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }

    /**
     * Get encryption key from environment or generate one
     */
    private function getEncryptionKey(): string {
        $key = $_ENV['ENCRYPTION_KEY'] ?? '';
        if (empty($key)) {
            // Generate a key if not set (in production, this should be set in env)
            $key = base64_encode(openssl_random_pseudo_bytes(32));
        }
        return substr($key, 0, 32); // Ensure 256-bit key
    }

    /**
     * Log security audit event
     */
    public function logAuditEvent(?int $userId, string $actionType, array $details = []): void {
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        // Determine risk level
        $riskLevel = $this->assessRiskLevel($actionType, $details);

        $this->db->insert('security_audit_log', [
            'user_id' => $userId,
            'action_type' => $actionType,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'resource_accessed' => $details['resource'] ?? null,
            'action_details' => json_encode($details),
            'risk_level' => $riskLevel,
            'is_suspicious' => $riskLevel === 'high' || $riskLevel === 'critical' ? 1 : 0
        ]);
    }

    /**
     * Assess risk level of an action
     */
    private function assessRiskLevel(string $actionType, array $details): string {
        $highRiskActions = ['password_change', 'data_export', 'api_key_create'];
        $criticalActions = ['admin_access', 'bulk_delete'];

        if (in_array($actionType, $criticalActions)) {
            return 'critical';
        }

        if (in_array($actionType, $highRiskActions)) {
            return 'high';
        }

        // Check for suspicious patterns
        if ($this->isSuspiciousActivity($actionType, $details)) {
            return 'high';
        }

        return 'low';
    }

    /**
     * Check for suspicious activity patterns
     */
    private function isSuspiciousActivity(string $actionType, array $details): bool {
        // Check for rapid login attempts
        if ($actionType === 'login') {
            $recentLogins = $this->db->query(
                "SELECT COUNT(*) as count FROM security_audit_log
                 WHERE action_type = 'login' AND ip_address = ?
                 AND created_at > datetime('now', '-5 minutes')",
                [$details['ip_address'] ?? '']
            );

            if (($recentLogins[0]['count'] ?? 0) > 5) {
                return true;
            }
        }

        // Check for unusual access patterns
        if ($actionType === 'data_access') {
            $recentAccess = $this->db->query(
                "SELECT COUNT(*) as count FROM security_audit_log
                 WHERE user_id = ? AND action_type = 'data_access'
                 AND created_at > datetime('now', '-1 hour')",
                [$details['user_id'] ?? null]
            );

            if (($recentAccess[0]['count'] ?? 0) > 20) {
                return true;
            }
        }

        return false;
    }

    /**
     * Rotate encryption keys (for scheduled maintenance)
     */
    public function rotateEncryptionKeys(): void {
        // This would be called periodically to rotate encryption keys
        // Implementation would involve re-encrypting all sensitive data with new keys
        // For now, just log the rotation
        $this->logAuditEvent(null, 'key_rotation', [
            'action' => 'encryption_key_rotation_started'
        ]);
    }

    /**
     * Get security audit logs
     */
    public function getAuditLogs(?int $userId = null, int $limit = 100): array {
        $whereClause = $userId ? "WHERE user_id = ?" : "";
        $params = $userId ? [$userId] : [];

        return $this->db->query(
            "SELECT * FROM security_audit_log {$whereClause}
             ORDER BY created_at DESC LIMIT ?",
            array_merge($params, [$limit])
        );
    }

    /**
     * Check if IP is blocked or suspicious
     */
    public function isBlockedIp(string $ipAddress): bool {
        // Check for IPs with high suspicious activity
        $suspiciousCount = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM security_audit_log
             WHERE ip_address = ? AND is_suspicious = 1
             AND created_at > datetime('now', '-24 hours')",
            [$ipAddress]
        );

        return ($suspiciousCount['count'] ?? 0) > 10;
    }

    /**
     * Generate secure API key
     */
    public function generateApiKey(): string {
        return bin2hex(random_bytes(32));
    }

    /**
     * Hash password securely
     */
    public function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    /**
     * Sanitize input data
     */
    public function sanitizeInput(string $data): string {
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate input data
     */
    public function validateInput(array $data, array $rules): array {
        $errors = [];

        foreach ($rules as $field => $rule) {
            if (!isset($data[$field])) {
                if (strpos($rule, 'required') !== false) {
                    $errors[$field] = 'This field is required';
                }
                continue;
            }

            $value = $data[$field];

            if (strpos($rule, 'email') !== false && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = 'Invalid email format';
            }

            if (strpos($rule, 'numeric') !== false && !is_numeric($value)) {
                $errors[$field] = 'Must be a number';
            }

            if (preg_match('/min:(\d+)/', $rule, $matches)) {
                $min = (int)$matches[1];
                if (strlen($value) < $min) {
                    $errors[$field] = "Must be at least {$min} characters";
                }
            }

            if (preg_match('/max:(\d+)/', $rule, $matches)) {
                $max = (int)$matches[1];
                if (strlen($value) > $max) {
                    $errors[$field] = "Must be no more than {$max} characters";
                }
            }
        }

        return $errors;
    }

    /**
     * Get security dashboard data
     */
    public function getSecurityDashboard(?int $userId = null): array {
        $whereClause = $userId ? "WHERE user_id = ?" : "";
        $params = $userId ? [$userId] : [];

        $stats = $this->db->queryOne(
            "SELECT
                COUNT(*) as total_events,
                SUM(CASE WHEN risk_level = 'critical' THEN 1 ELSE 0 END) as critical_events,
                SUM(CASE WHEN risk_level = 'high' THEN 1 ELSE 0 END) as high_risk_events,
                SUM(CASE WHEN is_suspicious = 1 THEN 1 ELSE 0 END) as suspicious_events,
                COUNT(DISTINCT ip_address) as unique_ips
             FROM security_audit_log {$whereClause}
             AND created_at > datetime('now', '-30 days')",
            $params
        );

        $recentEvents = $this->db->query(
            "SELECT * FROM security_audit_log {$whereClause}
             ORDER BY created_at DESC LIMIT 10",
            $params
        );

        return [
            'stats' => $stats,
            'recent_events' => $recentEvents
        ];
    }
}