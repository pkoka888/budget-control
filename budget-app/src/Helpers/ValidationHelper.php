<?php
namespace BudgetApp\Helpers;

/**
 * Validation Helper
 *
 * Common validation functions
 */
class ValidationHelper {
    /**
     * Validate email
     */
    public static function email(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate required field
     */
    public static function required($value): bool {
        if (is_string($value)) {
            return trim($value) !== '';
        }

        return !empty($value);
    }

    /**
     * Validate numeric value
     */
    public static function numeric($value): bool {
        return is_numeric($value);
    }

    /**
     * Validate integer
     */
    public static function integer($value): bool {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Validate float
     */
    public static function float($value): bool {
        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    }

    /**
     * Validate minimum length
     */
    public static function minLength(string $value, int $min): bool {
        return strlen($value) >= $min;
    }

    /**
     * Validate maximum length
     */
    public static function maxLength(string $value, int $max): bool {
        return strlen($value) <= $max;
    }

    /**
     * Validate date format
     */
    public static function date(string $date, string $format = 'Y-m-d'): bool {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Validate URL
     */
    public static function url(string $url): bool {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate currency code
     */
    public static function currencyCode(string $code): bool {
        $validCodes = ['CZK', 'EUR', 'USD', 'GBP', 'JPY', 'CHF', 'AUD', 'CAD', 'CNY', 'INR'];
        return in_array(strtoupper($code), $validCodes);
    }

    /**
     * Validate amount (positive number with max 2 decimals)
     */
    public static function amount($amount): bool {
        if (!is_numeric($amount)) {
            return false;
        }

        $amount = (float)$amount;
        if ($amount < 0) {
            return false;
        }

        // Check max 2 decimal places
        $decimals = strlen(substr(strrchr((string)$amount, "."), 1));
        return $decimals <= 2;
    }

    /**
     * Validate file upload
     */
    public static function file(array $file, array $allowedTypes = [], int $maxSize = 10485760): array {
        $errors = [];

        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error';
            return ['valid' => false, 'errors' => $errors];
        }

        if ($file['size'] > $maxSize) {
            $errors[] = 'File too large (max ' . ($maxSize / 1048576) . 'MB)';
        }

        if (!empty($allowedTypes)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedTypes)) {
                $errors[] = 'Invalid file type';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate password strength
     */
    public static function passwordStrength(string $password, array $requirements = []): array {
        $defaults = [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_number' => true,
            'require_special' => true
        ];

        $requirements = array_merge($defaults, $requirements);
        $errors = [];

        if (strlen($password) < $requirements['min_length']) {
            $errors[] = "Password must be at least {$requirements['min_length']} characters";
        }

        if ($requirements['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        if ($requirements['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        if ($requirements['require_number'] && !preg_match('/\d/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        if ($requirements['require_special'] && !preg_match('/[^a-zA-Z\d]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'strength' => self::calculatePasswordStrength($password)
        ];
    }

    private static function calculatePasswordStrength(string $password): string {
        $score = 0;

        if (strlen($password) >= 8) $score++;
        if (strlen($password) >= 12) $score++;
        if (preg_match('/[A-Z]/', $password)) $score++;
        if (preg_match('/[a-z]/', $password)) $score++;
        if (preg_match('/\d/', $password)) $score++;
        if (preg_match('/[^a-zA-Z\d]/', $password)) $score++;

        if ($score < 3) return 'weak';
        if ($score < 5) return 'medium';
        return 'strong';
    }

    /**
     * Sanitize string
     */
    public static function sanitize(string $value): string {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate array of rules
     */
    public static function validate(array $data, array $rules): array {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                if (is_string($rule)) {
                    $method = $rule;
                    $params = [];
                } else {
                    $method = $rule[0];
                    $params = array_slice($rule, 1);
                }

                if (method_exists(self::class, $method)) {
                    if (!call_user_func_array([self::class, $method], array_merge([$value], $params))) {
                        $errors[$field][] = ucfirst($field) . ' validation failed: ' . $method;
                    }
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
