<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

class RecurringTransactionService {
    private Database $db;

    // Supported frequencies and their intervals in days
    private const FREQUENCIES = [
        'daily' => 1,
        'weekly' => 7,
        'bi-weekly' => 14,
        'monthly' => 30,
        'quarterly' => 90,
        'yearly' => 365
    ];

    // Minimum number of transactions to consider for pattern detection
    private const MIN_TRANSACTIONS = 3;

    // Maximum allowed variance in amounts (percentage)
    private const AMOUNT_VARIANCE_THRESHOLD = 0.15; // 15%

    // Maximum allowed variance in intervals (days)
    private const INTERVAL_VARIANCE_THRESHOLD = 3;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Detect recurring transaction patterns for a user
     */
    public function detectRecurring(int $userId, int $minOccurrences = 3, int $lookbackDays = 365): array {
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime("-{$lookbackDays} days"));

        // Get all transactions for analysis
        $transactions = $this->db->query(
            "SELECT id, description, amount, date, category_id, account_id, type
             FROM transactions
             WHERE user_id = ? AND date BETWEEN ? AND ?
             ORDER BY date ASC",
            [$userId, $startDate, $endDate]
        );

        $patterns = [];

        // Group transactions by similar characteristics
        $transactionGroups = $this->groupSimilarTransactions($transactions);

        foreach ($transactionGroups as $group) {
            if (count($group) < $minOccurrences) {
                continue;
            }

            $pattern = $this->analyzeTransactionPattern($group);
            if ($pattern) {
                $patterns[] = $pattern;
            }
        }

        // Sort by confidence score
        usort($patterns, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });

        return $patterns;
    }

    /**
     * Group transactions by similar description and amount
     */
    private function groupSimilarTransactions(array $transactions): array {
        $groups = [];

        foreach ($transactions as $transaction) {
            $key = $this->generateGroupingKey($transaction);
            if (!isset($groups[$key])) {
                $groups[$key] = [];
            }
            $groups[$key][] = $transaction;
        }

        return array_filter($groups, function($group) {
            return count($group) >= self::MIN_TRANSACTIONS;
        });
    }

    /**
     * Generate a grouping key based on transaction characteristics
     */
    private function generateGroupingKey(array $transaction): string {
        // Normalize description (remove common variations)
        $normalizedDesc = $this->normalizeDescription($transaction['description']);

        // Round amount to nearest 10 for grouping (to handle small variations)
        $roundedAmount = round($transaction['amount'] / 10) * 10;

        return sprintf(
            "%s|%s|%s|%s",
            $transaction['type'],
            $normalizedDesc,
            $roundedAmount,
            $transaction['category_id'] ?? 'null'
        );
    }

    /**
     * Normalize transaction description for better grouping
     */
    private function normalizeDescription(string $description): string {
        // Convert to lowercase and remove extra spaces
        $normalized = strtolower(trim($description));

        // Remove common prefixes/suffixes that might vary
        $patterns = [
            '/^.*?(\d{4}).*$/', // Extract year if present
            '/^.*?(\d{2}\/\d{2}).*$/', // Extract date patterns
            '/\s+/', // Multiple spaces
        ];

        foreach ($patterns as $pattern) {
            $normalized = preg_replace($pattern, ' ', $normalized);
        }

        return trim($normalized);
    }

    /**
     * Analyze a group of transactions for recurring patterns
     */
    private function analyzeTransactionPattern(array $transactions): ?array {
        if (count($transactions) < self::MIN_TRANSACTIONS) {
            return null;
        }

        // Sort by date
        usort($transactions, function($a, $b) {
            return strtotime($a['date']) <=> strtotime($b['date']);
        });

        // Calculate intervals between transactions
        $intervals = [];
        for ($i = 1; $i < count($transactions); $i++) {
            $days = $this->calculateDaysBetween(
                $transactions[$i-1]['date'],
                $transactions[$i]['date']
            );
            $intervals[] = $days;
        }

        // Check amount consistency
        $amounts = array_column($transactions, 'amount');
        $avgAmount = array_sum($amounts) / count($amounts);
        $amountVariance = $this->calculateVariance($amounts, $avgAmount);

        if ($amountVariance > self::AMOUNT_VARIANCE_THRESHOLD) {
            return null; // Too much variation in amounts
        }

        // Determine frequency
        $frequency = $this->determineFrequency($intervals);
        if (!$frequency) {
            return null;
        }

        // Calculate confidence score
        $confidence = $this->calculateConfidence($transactions, $intervals, $amountVariance, $frequency);

        return [
            'description' => $transactions[0]['description'],
            'amount' => $avgAmount,
            'frequency' => $frequency,
            'category_id' => $transactions[0]['category_id'],
            'account_id' => $transactions[0]['account_id'],
            'type' => $transactions[0]['type'],
            'occurrences' => count($transactions),
            'first_date' => $transactions[0]['date'],
            'last_date' => end($transactions)['date'],
            'average_interval' => array_sum($intervals) / count($intervals),
            'confidence' => $confidence,
            'next_expected_date' => $this->calculateNextExpectedDate(end($transactions)['date'], $frequency)
        ];
    }

    /**
     * Calculate days between two dates
     */
    private function calculateDaysBetween(string $date1, string $date2): int {
        $timestamp1 = strtotime($date1);
        $timestamp2 = strtotime($date2);
        return abs(($timestamp2 - $timestamp1) / (60 * 60 * 24));
    }

    /**
     * Calculate variance of amounts
     */
    private function calculateVariance(array $amounts, float $mean): float {
        if (empty($amounts)) return 0;

        $variance = 0;
        foreach ($amounts as $amount) {
            $variance += pow($amount - $mean, 2);
        }

        return sqrt($variance / count($amounts)) / $mean; // Coefficient of variation
    }

    /**
     * Determine the frequency based on intervals
     */
    private function determineFrequency(array $intervals): ?string {
        if (empty($intervals)) return null;

        $avgInterval = array_sum($intervals) / count($intervals);
        $variance = $this->calculateIntervalVariance($intervals, $avgInterval);

        if ($variance > self::INTERVAL_VARIANCE_THRESHOLD) {
            return null; // Too inconsistent intervals
        }

        // Find closest matching frequency
        $bestMatch = null;
        $minDifference = PHP_FLOAT_MAX;

        foreach (self::FREQUENCIES as $frequency => $expectedDays) {
            $difference = abs($avgInterval - $expectedDays);
            if ($difference < $minDifference) {
                $minDifference = $difference;
                $bestMatch = $frequency;
            }
        }

        // Only accept if difference is within reasonable bounds
        return $minDifference <= 5 ? $bestMatch : null;
    }

    /**
     * Calculate variance of intervals
     */
    private function calculateIntervalVariance(array $intervals, float $mean): float {
        if (empty($intervals)) return 0;

        $variance = 0;
        foreach ($intervals as $interval) {
            $variance += pow($interval - $mean, 2);
        }

        return sqrt($variance / count($intervals));
    }

    /**
     * Calculate confidence score for the pattern
     */
    private function calculateConfidence(array $transactions, array $intervals, float $amountVariance, string $frequency): float {
        $occurrences = count($transactions);
        $intervalConsistency = 1 - min($this->calculateIntervalVariance($intervals, array_sum($intervals) / count($intervals)) / 10, 1);
        $amountConsistency = 1 - $amountVariance;

        // Weight factors
        $occurrenceWeight = min($occurrences / 12, 1); // Max at 12 occurrences
        $intervalWeight = 0.4;
        $amountWeight = 0.4;

        return ($occurrenceWeight * 0.2) + ($intervalConsistency * $intervalWeight) + ($amountConsistency * $amountWeight);
    }

    /**
     * Calculate next expected date based on frequency
     */
    private function calculateNextExpectedDate(string $lastDate, string $frequency): string {
        $lastTimestamp = strtotime($lastDate);
        $interval = self::FREQUENCIES[$frequency] * 24 * 60 * 60; // Convert to seconds

        return date('Y-m-d', $lastTimestamp + $interval);
    }

    /**
     * Create a recurring transaction definition
     */
    public function createRecurringTransaction(int $userId, array $data): int {
        // Validate required fields
        $required = ['description', 'amount', 'frequency', 'account_id', 'type'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        // Validate frequency
        if (!isset(self::FREQUENCIES[$data['frequency']])) {
            throw new \InvalidArgumentException("Invalid frequency: {$data['frequency']}");
        }

        return $this->db->insert('recurring_transactions', [
            'user_id' => $userId,
            'description' => $data['description'],
            'amount' => (float)$data['amount'],
            'frequency' => $data['frequency'],
            'account_id' => (int)$data['account_id'],
            'category_id' => $data['category_id'] ?? null,
            'type' => $data['type'],
            'next_due_date' => $data['next_due_date'] ?? $this->calculateNextExpectedDate(date('Y-m-d'), $data['frequency']),
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get all recurring transactions for a user
     */
    public function getRecurringTransactions(int $userId): array {
        return $this->db->query(
            "SELECT rt.*, a.name as account_name, c.name as category_name
             FROM recurring_transactions rt
             LEFT JOIN accounts a ON rt.account_id = a.id
             LEFT JOIN categories c ON rt.category_id = c.id
             WHERE rt.user_id = ? AND rt.is_active = 1
             ORDER BY rt.next_due_date ASC",
            [$userId]
        );
    }

    /**
     * Update recurring transaction
     */
    public function updateRecurringTransaction(int $userId, int $id, array $data): bool {
        // Verify ownership
        $existing = $this->db->queryOne(
            "SELECT id FROM recurring_transactions WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );

        if (!$existing) {
            return false;
        }

        $updates = [];
        $allowedFields = ['description', 'amount', 'frequency', 'account_id', 'category_id', 'next_due_date', 'is_active'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[$field] = $data[$field];
            }
        }

        if (!empty($updates)) {
            $updates['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update('recurring_transactions', $updates, ['id' => $id]);
        }

        return true;
    }

    /**
     * Delete recurring transaction
     */
    public function deleteRecurringTransaction(int $userId, int $id): bool {
        $existing = $this->db->queryOne(
            "SELECT id FROM recurring_transactions WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );

        if (!$existing) {
            return false;
        }

        $this->db->delete('recurring_transactions', ['id' => $id]);
        return true;
    }
}