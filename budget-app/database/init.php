#!/usr/bin/env php
<?php
/**
 * Database Initialization Script
 *
 * This script initializes the Budget Control database by:
 * 1. Creating the SQLite database file
 * 2. Applying the base schema from schema.sql
 * 3. Running all pending migrations
 *
 * Usage: php budget-app/database/init.php
 */

echo "🚀 Budget Control - Database Initialization\n";
echo str_repeat("=", 50) . "\n\n";

// Define paths
$dbPath = __DIR__ . '/budget.db';
$schemaPath = __DIR__ . '/schema.sql';
$migrationsDir = __DIR__ . '/migrations';

// Check if database already exists
if (file_exists($dbPath)) {
    echo "⚠️  Database already exists at: $dbPath\n";
    echo "Do you want to:\n";
    echo "  [1] Skip initialization (keep existing database)\n";
    echo "  [2] Delete and recreate (WARNING: ALL DATA WILL BE LOST)\n";
    echo "  [3] Just run pending migrations\n";
    echo "\nYour choice [1-3]: ";

    $handle = fopen("php://stdin", "r");
    $choice = trim(fgets($handle));
    fclose($handle);

    switch ($choice) {
        case '1':
            echo "\n✅ Keeping existing database. Exiting...\n";
            exit(0);

        case '2':
            echo "\n⚠️  DELETING existing database...\n";
            unlink($dbPath);
            echo "✅ Database deleted.\n\n";
            break;

        case '3':
            echo "\n📦 Skipping to migrations...\n\n";
            goto migrations;

        default:
            echo "\n❌ Invalid choice. Exiting...\n";
            exit(1);
    }
}

// Check if schema file exists
if (!file_exists($schemaPath)) {
    echo "❌ Error: Schema file not found at: $schemaPath\n";
    exit(1);
}

echo "📊 Step 1: Creating database file...\n";

try {
    // Create database connection
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Database file created: $dbPath\n\n";

} catch (PDOException $e) {
    echo "❌ Failed to create database: " . $e->getMessage() . "\n";
    exit(1);
}

echo "📋 Step 2: Applying base schema...\n";

try {
    // Read schema SQL
    $schema = file_get_contents($schemaPath);

    if (empty($schema)) {
        echo "❌ Error: Schema file is empty\n";
        exit(1);
    }

    // Disable foreign key enforcement temporarily during schema creation
    $db->exec("PRAGMA foreign_keys = OFF");

    // Remove PRAGMA statements from schema since we handle them separately
    $schema = preg_replace('/PRAGMA\s+[^;]+;?\s*/i', '', $schema);

    // Remove comment lines first (before splitting by semicolon)
    $lines = explode("\n", $schema);
    $cleanedLines = [];
    foreach ($lines as $line) {
        $line = trim($line);
        // Skip empty lines and comment lines
        if (empty($line) || strpos($line, '--') === 0) {
            continue;
        }
        $cleanedLines[] = $line;
    }
    $schema = implode("\n", $cleanedLines);

    // Split by semicolons and execute each statement
    $statements = array_values(array_filter(array_map('trim', explode(';', $schema))));

    echo "   Found " . count($statements) . " SQL statements to execute...\n";

    $successCount = 0;
    foreach ($statements as $statement) {
        if (!empty(trim($statement))) {
            try {
                $db->exec($statement);
                $successCount++;
            } catch (PDOException $e) {
                // Some CREATE TABLE IF NOT EXISTS statements might fail if already exists
                // This is OK during re-initialization
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "   ❌ Failed statement: " . substr($statement, 0, 100) . "...\n";
                    throw $e;
                }
            }
        }
    }

    // Re-enable foreign keys
    $db->exec("PRAGMA foreign_keys = ON");

    echo "✅ Base schema applied successfully ($successCount statements executed)\n\n";

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "❌ Failed to apply schema: " . $e->getMessage() . "\n";
    exit(1);
}

// Migrations section
migrations:

echo "🔄 Step 3: Running database migrations...\n";

try {
    // Reconnect if we jumped here via goto
    if (!isset($db)) {
        $db = new PDO('sqlite:' . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

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
    if (!is_dir($migrationsDir)) {
        echo "   ⚠️  No migrations directory found. Skipping migrations.\n\n";
    } else {
        $migrationFiles = glob($migrationsDir . '/*.sql');
        sort($migrationFiles);

        if (empty($migrationFiles)) {
            echo "   ℹ️  No migration files found.\n\n";
        } else {
            $appliedCount = 0;
            $skippedCount = 0;

            foreach ($migrationFiles as $migrationFile) {
                $migrationName = basename($migrationFile);

                if (in_array($migrationName, $appliedMigrations)) {
                    echo "   ⏭️  Skipping (already applied): $migrationName\n";
                    $skippedCount++;
                    continue;
                }

                echo "   🔄 Applying: $migrationName\n";

                // Read migration SQL
                $sql = file_get_contents($migrationFile);

                // Remove comment lines (same as schema processing)
                $lines = explode("\n", $sql);
                $cleanedLines = [];
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line) || strpos($line, '--') === 0) {
                        continue;
                    }
                    $cleanedLines[] = $line;
                }
                $sql = implode("\n", $cleanedLines);

                // Execute migration in a transaction
                $db->beginTransaction();
                try {
                    // Split and execute statements
                    $statements = array_values(array_filter(array_map('trim', explode(';', $sql))));

                    foreach ($statements as $statement) {
                        if (!empty($statement)) {
                            $db->exec($statement);
                        }
                    }

                    // Record migration as applied
                    $stmt = $db->prepare("INSERT INTO schema_migrations (migration_name) VALUES (?)");
                    $stmt->execute([$migrationName]);

                    $db->commit();
                    echo "   ✅ Successfully applied: $migrationName\n";
                    $appliedCount++;

                } catch (Exception $e) {
                    $db->rollBack();
                    echo "   ❌ Failed to apply migration: $migrationName\n";
                    echo "   Error: " . $e->getMessage() . "\n";
                    exit(1);
                }
            }

            echo "\n   📊 Migration Summary:\n";
            echo "      Applied: $appliedCount\n";
            echo "      Skipped: $skippedCount\n";
            echo "      Total:   " . count($migrationFiles) . "\n\n";
        }
    }

} catch (PDOException $e) {
    echo "❌ Migration error: " . $e->getMessage() . "\n";
    exit(1);
}

// Verify database structure
echo "🔍 Step 4: Verifying database structure...\n";

try {
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

    echo "   ✅ Found " . count($tables) . " tables:\n";

    // Group tables for better display
    $coreeTables = [];
    $phaseTables = [];

    foreach ($tables as $table) {
        if ($table === 'schema_migrations' || $table === 'sqlite_sequence') {
            continue;
        }

        if (strpos($table, 'opportunity_') === 0 ||
            strpos($table, 'saved_') === 0 ||
            strpos($table, 'scenario_') === 0 ||
            strpos($table, 'automation_') === 0) {
            $phaseTables[] = $table;
        } else {
            $coreTables[] = $table;
        }
    }

    if (!empty($coreTables)) {
        echo "\n   Core Tables (" . count($coreTables) . "):\n";
        foreach (array_chunk($coreTables, 3) as $chunk) {
            echo "      • " . implode(", ", $chunk) . "\n";
        }
    }

    if (!empty($phaseTables)) {
        echo "\n   Phase 3 Tables (" . count($phaseTables) . "):\n";
        foreach (array_chunk($phaseTables, 3) as $chunk) {
            echo "      • " . implode(", ", $chunk) . "\n";
        }
    }

    // Get total index count
    $indexes = $db->query("SELECT COUNT(*) FROM sqlite_master WHERE type='index'")->fetchColumn();
    echo "\n   ✅ Database has $indexes indexes\n";

} catch (PDOException $e) {
    echo "   ⚠️  Could not verify database structure: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🎉 Database initialization complete!\n\n";
echo "Next steps:\n";
echo "  1. Start your web server\n";
echo "  2. Navigate to the application URL\n";
echo "  3. Register a new account or log in\n\n";
echo "Database location: $dbPath\n";
echo "Database size: " . round(filesize($dbPath) / 1024, 2) . " KB\n\n";
echo "✨ Happy budgeting! ✨\n\n";
