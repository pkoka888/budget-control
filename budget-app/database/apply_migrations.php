#!/usr/bin/env php
<?php
/**
 * Manual Migration Applier for migrations 006-017
 * Applies migrations one at a time with better error handling
 */

$dbPath = __DIR__ . '/budget.db';
$migrationsDir = __DIR__ . '/migrations';

if (!file_exists($dbPath)) {
    echo "Error: Database file not found\n";
    exit(1);
}

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create migrations table if needed
    $db->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        migration_name TEXT NOT NULL UNIQUE,
        applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Get applied migrations
    $appliedMigrations = $db->query("SELECT migration_name FROM schema_migrations")->fetchAll(PDO::FETCH_COLUMN);

    // Migrations to apply
    $migrationsToApply = [
        '006_add_multi_currency.sql',
        '007_add_expense_splitting.sql',
        '008_add_receipt_ocr.sql',
        '009_add_investment_portfolio.sql',
        '010_add_ai_insights.sql',
        '011_add_bill_automation.sql',
        '012_add_data_export_api.sql',
        '013_add_household_foundation.sql',
        '014_add_shared_data_flags.sql',
        '015_add_activity_audit.sql',
        '016_add_comments_communication.sql',
        '017_add_child_accounts.sql'
    ];

    $applied = 0;
    $skipped = 0;

    foreach ($migrationsToApply as $migrationName) {
        if (in_array($migrationName, $appliedMigrations)) {
            echo "⏭️  Skip: $migrationName (already applied)\n";
            $skipped++;
            continue;
        }

        $migrationFile = $migrationsDir . '/' . $migrationName;
        if (!file_exists($migrationFile)) {
            echo "⚠️  Skip: $migrationName (file not found)\n";
            continue;
        }

        echo "🔄 Applying: $migrationName\n";

        $sql = file_get_contents($migrationFile);

        // Begin transaction
        $db->beginTransaction();
        try {
            // Execute the entire SQL as one block (SQLite handles multiple statements)
            $db->exec($sql);

            // Record as applied
            $stmt = $db->prepare("INSERT INTO schema_migrations (migration_name) VALUES (?)");
            $stmt->execute([$migrationName]);

            $db->commit();
            echo "✅ Success: $migrationName\n";
            $applied++;

        } catch (Exception $e) {
            $db->rollBack();
            echo "❌ Failed: $migrationName\n";
            echo "   Error: " . $e->getMessage() . "\n";

            // Try to continue with next migration
            echo "   Continuing with next migration...\n";
        }
    }

    echo "\n📊 Summary:\n";
    echo "  Applied: $applied\n";
    echo "  Skipped: $skipped\n";
    echo "\n✅ Migration process completed!\n";

} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
}
