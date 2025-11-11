<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

class PerformanceService {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Record performance metric
     */
    public function recordMetric(string $metricType, string $metricName, float $value, array $context = []): void {
        $this->db->insert('performance_metrics', [
            'metric_type' => $metricType,
            'metric_name' => $metricName,
            'value' => $value,
            'unit' => $this->getUnitForMetric($metricType),
            'context_data' => json_encode($context)
        ]);
    }

    /**
     * Get unit for metric type
     */
    private function getUnitForMetric(string $metricType): string {
        return match($metricType) {
            'page_load' => 'ms',
            'api_response' => 'ms',
            'database_query' => 'ms',
            'memory_usage' => 'bytes',
            'cpu_usage' => 'percent',
            'error_rate' => 'percent',
            default => 'count'
        };
    }

    /**
     * Record page load time
     */
    public function recordPageLoad(string $page, float $loadTime, array $context = []): void {
        $context = array_merge($context, ['page' => $page]);
        $this->recordMetric('page_load', 'page_load_time', $loadTime, $context);
    }

    /**
     * Record API response time
     */
    public function recordApiResponse(string $endpoint, float $responseTime, int $statusCode, array $context = []): void {
        $context = array_merge($context, [
            'endpoint' => $endpoint,
            'status_code' => $statusCode
        ]);
        $this->recordMetric('api_response', 'response_time', $responseTime, $context);
    }

    /**
     * Record database query performance
     */
    public function recordDatabaseQuery(string $queryType, float $executionTime, array $context = []): void {
        $context = array_merge($context, ['query_type' => $queryType]);
        $this->recordMetric('database_query', 'execution_time', $executionTime, $context);
    }

    /**
     * Record memory usage
     */
    public function recordMemoryUsage(float $memoryUsed, array $context = []): void {
        $this->recordMetric('memory_usage', 'memory_used', $memoryUsed, $context);
    }

    /**
     * Get performance statistics
     */
    public function getPerformanceStats(string $metricType, int $hours = 24): array {
        $stats = $this->db->queryOne(
            "SELECT
                COUNT(*) as sample_count,
                AVG(value) as avg_value,
                MIN(value) as min_value,
                MAX(value) as max_value,
                (
                    SELECT value FROM performance_metrics
                    WHERE metric_type = ? AND recorded_at >= datetime('now', '-{$hours} hours')
                    ORDER BY recorded_at DESC LIMIT 1
                ) as latest_value
             FROM performance_metrics
             WHERE metric_type = ? AND recorded_at >= datetime('now', '-{$hours} hours')",
            [$metricType, $metricType]
        );

        // Get percentile data
        $percentiles = $this->getPercentiles($metricType, $hours);

        return array_merge($stats, $percentiles);
    }

    /**
     * Get percentile data for metrics
     */
    private function getPercentiles(string $metricType, int $hours): array {
        $values = $this->db->query(
            "SELECT value FROM performance_metrics
             WHERE metric_type = ? AND recorded_at >= datetime('now', '-{$hours} hours')
             ORDER BY value",
            [$metricType]
        );

        if (empty($values)) {
            return [
                'p50' => 0,
                'p95' => 0,
                'p99' => 0
            ];
        }

        $values = array_column($values, 'value');
        sort($values);

        return [
            'p50' => $this->calculatePercentile($values, 50),
            'p95' => $this->calculatePercentile($values, 95),
            'p99' => $this->calculatePercentile($values, 99)
        ];
    }

    /**
     * Calculate percentile from sorted array
     */
    private function calculatePercentile(array $values, float $percentile): float {
        $index = ($percentile / 100) * (count($values) - 1);
        $lower = floor($index);
        $upper = ceil($index);
        $weight = $index - $lower;

        if ($upper >= count($values)) {
            return $values[count($values) - 1];
        }

        return $values[$lower] * (1 - $weight) + $values[$upper] * $weight;
    }

    /**
     * Get slow queries/performance issues
     */
    public function getPerformanceIssues(int $hours = 24): array {
        // Slow API responses (>1000ms)
        $slowApis = $this->db->query(
            "SELECT * FROM performance_metrics
             WHERE metric_type = 'api_response' AND value > 1000
             AND recorded_at >= datetime('now', '-{$hours} hours')
             ORDER BY value DESC LIMIT 10"
        );

        // Slow page loads (>3000ms)
        $slowPages = $this->db->query(
            "SELECT * FROM performance_metrics
             WHERE metric_type = 'page_load' AND value > 3000
             AND recorded_at >= datetime('now', '-{$hours} hours')
             ORDER BY value DESC LIMIT 10"
        );

        // Slow database queries (>500ms)
        $slowQueries = $this->db->query(
            "SELECT * FROM performance_metrics
             WHERE metric_type = 'database_query' AND value > 500
             AND recorded_at >= datetime('now', '-{$hours} hours')
             ORDER BY value DESC LIMIT 10"
        );

        return [
            'slow_apis' => $slowApis,
            'slow_pages' => $slowPages,
            'slow_queries' => $slowQueries
        ];
    }

    /**
     * Optimize database queries (add indexes for slow queries)
     */
    public function optimizeDatabase(): array {
        $optimizations = [];

        // Check for missing indexes on frequently queried columns
        $missingIndexes = $this->identifyMissingIndexes();

        foreach ($missingIndexes as $table => $columns) {
            foreach ($columns as $column) {
                $optimizations[] = [
                    'type' => 'create_index',
                    'table' => $table,
                    'column' => $column,
                    'sql' => "CREATE INDEX IF NOT EXISTS idx_{$table}_{$column} ON {$table}({$column});"
                ];
            }
        }

        return $optimizations;
    }

    /**
     * Identify potentially missing indexes
     */
    private function identifyMissingIndexes(): array {
        // This is a simplified version - in practice, you'd analyze query patterns
        // and slow query logs to identify optimization opportunities
        return [
            'transactions' => ['date', 'amount'],
            'ai_recommendations' => ['created_at'],
            'performance_metrics' => ['recorded_at']
        ];
    }

    /**
     * Clean up old performance data
     */
    public function cleanupOldData(int $daysToKeep = 90): int {
        $deleted = $this->db->queryOne(
            "DELETE FROM performance_metrics
             WHERE recorded_at < datetime('now', '-{$daysToKeep} days')",
            []
        );

        return $deleted['changes'] ?? 0;
    }

    /**
     * Get performance dashboard data
     */
    public function getPerformanceDashboard(): array {
        return [
            'page_load_stats' => $this->getPerformanceStats('page_load'),
            'api_stats' => $this->getPerformanceStats('api_response'),
            'db_stats' => $this->getPerformanceStats('database_query'),
            'issues' => $this->getPerformanceIssues(),
            'optimizations' => $this->optimizeDatabase()
        ];
    }

    /**
     * Record usability test session
     */
    public function recordUsabilitySession(int $userId, string $sessionType, string $testName, ?string $variant = null): int {
        return $this->db->insert('usability_test_sessions', [
            'user_id' => $userId,
            'session_type' => $sessionType,
            'test_name' => $testName,
            'variant' => $variant,
            'start_time' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Complete usability test session
     */
    public function completeUsabilitySession(int $sessionId, array $userFeedback = []): void {
        $this->db->update('usability_test_sessions', [
            'end_time' => date('Y-m-d H:i:s'),
            'completion_status' => 'complete',
            'user_feedback' => json_encode($userFeedback)
        ], ['id' => $sessionId]);
    }

    /**
     * Get A/B test results
     */
    public function getAbTestResults(string $testName): array {
        $variants = $this->db->query(
            "SELECT
                variant,
                COUNT(*) as total_sessions,
                SUM(CASE WHEN completion_status = 'complete' THEN 1 ELSE 0 END) as completed_sessions,
                AVG(CASE WHEN user_feedback LIKE '%rating%' THEN
                    CAST(JSON_EXTRACT(user_feedback, '$.rating') AS INTEGER) ELSE NULL END) as avg_rating
             FROM usability_test_sessions
             WHERE session_type = 'a_b_test' AND test_name = ?
             GROUP BY variant",
            [$testName]
        );

        return $variants;
    }

    /**
     * Get usability test analytics
     */
    public function getUsabilityAnalytics(): array {
        $stats = $this->db->queryOne(
            "SELECT
                COUNT(*) as total_sessions,
                AVG(CASE WHEN completion_status = 'complete' THEN 1 ELSE 0 END) as completion_rate,
                AVG(strftime('%s', end_time) - strftime('%s', start_time)) as avg_session_time
             FROM usability_test_sessions
             WHERE created_at >= datetime('now', '-30 days')"
        );

        $testResults = $this->db->query(
            "SELECT test_name, session_type, COUNT(*) as sessions
             FROM usability_test_sessions
             WHERE created_at >= datetime('now', '-30 days')
             GROUP BY test_name, session_type
             ORDER BY sessions DESC"
        );

        return [
            'overall_stats' => $stats,
            'test_results' => $testResults
        ];
    }
}