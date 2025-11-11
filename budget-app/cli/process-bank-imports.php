<?php
/**
 * CLI Script: Process pending bank import jobs
 *
 * Usage: php cli/process-bank-imports.php [--job-id=<id>]
 *
 * Without --job-id: processes all pending jobs
 * With --job-id: processes specific job
 */

require_once __DIR__ . '/../vendor/autoload.php';

use BudgetApp\Database;
use BudgetApp\Jobs\BankImportJob;
use BudgetApp\Config;

// Parse command line arguments
$jobId = null;
foreach ($argv as $arg) {
    if (strpos($arg, '--job-id=') === 0) {
        $jobId = substr($arg, strlen('--job-id='));
    }
}

try {
    // Initialize database
    $config = new Config(__DIR__ . '/..');
    $db = new Database($config->getDatabasePath());

    // Determine which jobs to process
    if ($jobId) {
        // Process specific job
        $jobs = [$db->queryOne(
            "SELECT * FROM bank_import_jobs WHERE job_id = ? AND status IN ('pending', 'processing')",
            [$jobId]
        )];

        if (!$jobs[0]) {
            echo "❌ Job not found or already completed: $jobId\n";
            exit(1);
        }
    } else {
        // Process all pending jobs
        $jobs = $db->query(
            "SELECT * FROM bank_import_jobs WHERE status = 'pending' ORDER BY created_at ASC"
        ) ?: [];
    }

    if (empty($jobs)) {
        echo "✓ No pending jobs to process\n";
        exit(0);
    }

    $totalProcessed = 0;
    $totalSuccessful = 0;
    $totalFailed = 0;

    foreach ($jobs as $jobRecord) {
        $jobId = $jobRecord['job_id'];
        $userId = $jobRecord['user_id'];

        echo "\n📦 Processing job: $jobId\n";
        echo "   User ID: $userId\n";
        echo "   Status: {$jobRecord['status']}\n";

        try {
            $job = new BankImportJob($db, $jobId, $userId);
            $job->execute();

            $jobRecord = $db->queryOne(
                "SELECT * FROM bank_import_jobs WHERE job_id = ?",
                [$jobId]
            );

            echo "✅ Job completed successfully!\n";
            echo "   Status: {$jobRecord['status']}\n";
            echo "   Imported: {$jobRecord['imported_count']} transactions\n";
            echo "   Files: {$jobRecord['processed_files']}/{$jobRecord['total_files']}\n";

            $totalSuccessful++;

        } catch (\Exception $e) {
            echo "❌ Job failed: " . $e->getMessage() . "\n";
            $totalFailed++;
        }

        $totalProcessed++;
    }

    // Summary
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "📊 SUMMARY\n";
    echo "==========\n";
    echo "Total jobs processed: $totalProcessed\n";
    echo "Successful: $totalSuccessful\n";
    echo "Failed: $totalFailed\n";
    echo str_repeat('=', 60) . "\n";

    exit($totalFailed > 0 ? 1 : 0);

} catch (\Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
