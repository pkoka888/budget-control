#!/usr/bin/env php
<?php
/**
 * Database Migration Runner
 * Applies SQL migrations to the SQLite database
 */

// Define paths
$dbPath = __DIR__ . '/budget.db';
$migrationsDir = __DIR__ . '/migrations';

if (!file_exists($dbPath)) {
    echo "Error: Database file not found at: $dbPath\n";
    exit(1);
}

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create migrations tracking table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS schema_migrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            migration_name TEXT NOT NULL UNIQUE,
            applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Get list of applied migrations
    $stmt = $db->query("SELECT migration_name FROM schema_migrations");
    $appliedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get list of migration files
    $migrationFiles = glob($migrationsDir . '/*.sql');
    sort($migrationFiles);

    if (empty($migrationFiles)) {
        echo "No migration files found in: $migrationsDir\n";
        exit(0);
    }

    $appliedCount = 0;
    $skippedCount = 0;

    foreach ($migrationFiles as $migrationFile) {
        $migrationName = basename($migrationFile);

        if (in_array($migrationName, $appliedMigrations)) {
            echo "⏭️  Skipping already applied: $migrationName\n";
            $skippedCount++;
            continue;
        }

        echo "🔄 Applying migration: $migrationName\n";

        // Read migration SQL
        $sql = file_get_contents($migrationFile);

        // Execute migration in a transaction
        $db->beginTransaction();
        try {
            // Split by semicolons and execute each statement
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function($stmt) {
                    return !empty($stmt) &&
                           !preg_match('/^\s*--/', $stmt);
                }
            );

            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $db->exec($statement);
                }
            }

            // Record migration as applied
            $stmt = $db->prepare("INSERT INTO schema_migrations (migration_name) VALUES (?)");
            $stmt->execute([$migrationName]);

            $db->commit();
            echo "✅ Successfully applied: $migrationName\n";
            $appliedCount++;

        } catch (Exception $e) {
            $db->rollBack();
            echo "❌ Failed to apply migration: $migrationName\n";
            echo "Error: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    echo "\n";
    echo "📊 Migration Summary:\n";
    echo "  Applied: $appliedCount\n";
    echo "  Skipped: $skippedCount\n";
    echo "  Total:   " . count($migrationFiles) . "\n";
    echo "\n✅ All migrations completed successfully!\n";

} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
}
