<?php
namespace BudgetApp\Middleware;

use BudgetApp\Database;

class ApiAuthMiddleware {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function authenticate(): ?array {
        $headers = $this->getHeaders();

        // Check for API key in header
        $apiKey = $headers['X-API-Key'] ?? $headers['Authorization'] ?? null;

        if (!$apiKey) {
            return null;
        }

        // Remove "Bearer " prefix if present
        if (strpos($apiKey, 'Bearer ') === 0) {
            $apiKey = substr($apiKey, 7);
        }

        // Validate API key
        $keyData = $this->db->queryOne(
            "SELECT ak.*, u.id as user_id, u.name as user_name, u.email as user_email
             FROM api_keys ak
             JOIN users u ON ak.user_id = u.id
             WHERE ak.api_key = ? AND ak.is_active = 1
             AND (ak.expires_at IS NULL OR ak.expires_at > datetime('now'))",
            [$apiKey]
        );

        if (!$keyData) {
            return null;
        }

        // Check rate limit
        if (!$this->checkRateLimit($keyData['id'], $keyData['rate_limit'])) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Rate limit exceeded']);
            exit;
        }

        // Update last used timestamp
        $this->db->execute(
            "UPDATE api_keys SET last_used_at = datetime('now') WHERE id = ?",
            [$keyData['id']]
        );

        return [
            'user_id' => $keyData['user_id'],
            'user_name' => $keyData['user_name'],
            'user_email' => $keyData['user_email'],
            'api_key_id' => $keyData['id'],
            'permissions' => explode(',', $keyData['permissions']),
            'rate_limit' => $keyData['rate_limit']
        ];
    }

    private function checkRateLimit(int $apiKeyId, int $maxRequests): bool {
        $currentHour = date('Y-m-d H:00:00');

        // Get or create rate limit record for this hour
        $rateLimit = $this->db->queryOne(
            "SELECT request_count FROM api_rate_limits
             WHERE api_key_id = ? AND hour_window = ?",
            [$apiKeyId, $currentHour]
        );

        if (!$rateLimit) {
            // Create new record
            $this->db->execute(
                "INSERT INTO api_rate_limits (api_key_id, hour_window, request_count)
                 VALUES (?, ?, 1)",
                [$apiKeyId, $currentHour]
            );
            return true;
        }

        if ($rateLimit['request_count'] >= $maxRequests) {
            return false;
        }

        // Increment counter
        $this->db->execute(
            "UPDATE api_rate_limits SET request_count = request_count + 1, updated_at = datetime('now')
             WHERE api_key_id = ? AND hour_window = ?",
            [$apiKeyId, $currentHour]
        );

        return true;
    }

    private function getHeaders(): array {
        $headers = [];

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            // Fallback for servers that don't have getallheaders()
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        }

        return array_change_key_case($headers, CASE_UPPER);
    }

    public function requirePermission(array $authData, string $permission): void {
        if (!in_array($permission, $authData['permissions']) && !in_array('admin', $authData['permissions'])) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Insufficient permissions']);
            exit;
        }
    }

    /**
     * Check if authenticated user has a specific permission
     *
     * @param array $authData Authentication data from authenticate()
     * @param string $permission Permission to check (read/write/admin)
     * @return bool True if user has permission, false otherwise
     */
    public function hasPermission(array $authData, string $permission): bool {
        return in_array($permission, $authData['permissions']) || in_array('admin', $authData['permissions']);
    }

    /**
     * Validate scope-based access control for endpoints
     *
     * @param array $authData Authentication data from authenticate()
     * @param string $scope Required scope for the endpoint
     * @return bool True if access is allowed, false otherwise
     */
    public function validateScope(array $authData, string $scope): bool {
        // Define scope mappings to permissions
        $scopeMappings = [
            'read' => ['read', 'write', 'admin'],
            'write' => ['write', 'admin'],
            'admin' => ['admin']
        ];

        if (!isset($scopeMappings[$scope])) {
            return false;
        }

        $requiredPermissions = $scopeMappings[$scope];
        foreach ($requiredPermissions as $permission) {
            if ($this->hasPermission($authData, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Rotate API key - generate new key and deactivate old one
     *
     * @param int $apiKeyId ID of the API key to rotate
     * @return string|null New API key on success, null on failure
     */
    public function rotateKey(int $apiKeyId): ?string {
        // Get current key data
        $keyData = $this->db->queryOne(
            "SELECT id, user_id, name, permissions, rate_limit FROM api_keys WHERE id = ? AND is_active = 1",
            [$apiKeyId]
        );

        if (!$keyData) {
            return null;
        }

        // Generate new API key
        $newApiKey = $this->generateApiKey();

        // Deactivate old key
        $this->db->execute(
            "UPDATE api_keys SET is_active = 0 WHERE id = ?",
            [$apiKeyId]
        );

        // Create new key with same settings
        $this->db->execute(
            "INSERT INTO api_keys (user_id, name, api_key, permissions, rate_limit, is_active, created_at)
             VALUES (?, ?, ?, ?, ?, 1, datetime('now'))",
            [
                $keyData['user_id'],
                $keyData['name'] . ' (Rotated)',
                $newApiKey,
                $keyData['permissions'],
                $keyData['rate_limit']
            ]
        );

        return $newApiKey;
    }

    /**
     * Generate a secure random API key
     *
     * @return string Generated API key
     */
    private function generateApiKey(): string {
        return 'bk_' . bin2hex(random_bytes(32));
    }
}