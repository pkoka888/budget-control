<?php
namespace BudgetApp\Helpers;

/**
 * Format Helper
 *
 * Utility functions for formatting data
 */
class FormatHelper {
    /**
     * Format currency amount
     */
    public static function currency(float $amount, string $currency = 'CZK', int $decimals = 2): string {
        $symbols = [
            'CZK' => 'Kč',
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            'JPY' => '¥',
            'CHF' => 'CHF'
        ];

        $symbol = $symbols[$currency] ?? $currency;
        $formatted = number_format($amount, $decimals, '.', ' ');

        // Symbol position
        if (in_array($currency, ['USD', 'GBP'])) {
            return $symbol . $formatted;
        }

        return $formatted . ' ' . $symbol;
    }

    /**
     * Format percentage
     */
    public static function percentage(float $value, int $decimals = 2): string {
        return number_format($value, $decimals) . '%';
    }

    /**
     * Format number with abbreviation (K, M, B)
     */
    public static function abbreviateNumber(float $number, int $decimals = 1): string {
        if ($number >= 1000000000) {
            return number_format($number / 1000000000, $decimals) . 'B';
        } elseif ($number >= 1000000) {
            return number_format($number / 1000000, $decimals) . 'M';
        } elseif ($number >= 1000) {
            return number_format($number / 1000, $decimals) . 'K';
        }

        return number_format($number, $decimals);
    }

    /**
     * Format date in user-friendly format
     */
    public static function date(string $date, string $format = 'M j, Y'): string {
        return date($format, strtotime($date));
    }

    /**
     * Format date with time
     */
    public static function datetime(string $datetime, string $format = 'M j, Y g:i A'): string {
        return date($format, strtotime($datetime));
    }

    /**
     * Format relative time (e.g., "2 hours ago")
     */
    public static function timeAgo(string $datetime): string {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'just now';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 2592000) {
            $weeks = floor($diff / 604800);
            return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 31536000) {
            $months = floor($diff / 2592000);
            return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
        } else {
            $years = floor($diff / 31536000);
            return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
        }
    }

    /**
     * Format file size
     */
    public static function fileSize(int $bytes): string {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }

    /**
     * Truncate text
     */
    public static function truncate(string $text, int $length = 100, string $suffix = '...'): string {
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length) . $suffix;
    }

    /**
     * Generate color from string (for avatars, etc.)
     */
    public static function stringToColor(string $str): string {
        $hash = md5($str);
        $r = hexdec(substr($hash, 0, 2));
        $g = hexdec(substr($hash, 2, 2));
        $b = hexdec(substr($hash, 4, 2));

        return sprintf('rgb(%d, %d, %d)', $r, $g, $b);
    }

    /**
     * Format transaction type badge
     */
    public static function transactionTypeBadge(string $type): string {
        $badges = [
            'income' => '<span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded">Income</span>',
            'expense' => '<span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 rounded">Expense</span>',
            'transfer' => '<span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded">Transfer</span>'
        ];

        return $badges[$type] ?? $type;
    }

    /**
     * Format status badge
     */
    public static function statusBadge(string $status, array $config = []): string {
        $defaults = [
            'pending' => 'yellow',
            'completed' => 'green',
            'failed' => 'red',
            'processing' => 'blue',
            'active' => 'green',
            'inactive' => 'gray'
        ];

        $colors = array_merge($defaults, $config);
        $color = $colors[$status] ?? 'gray';

        return sprintf(
            '<span class="px-2 py-1 text-xs font-medium bg-%s-100 text-%s-800 dark:bg-%s-900 dark:text-%s-200 rounded">%s</span>',
            $color, $color, $color, $color, ucfirst($status)
        );
    }
}
