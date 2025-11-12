#!/usr/bin/env php
<?php
/**
 * Fix and apply household migrations (013-015)
 */

$dbPath = __DIR__ . '/budget.db';

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "🔄 Applying Household Migrations...\n\n";

    // Migration 013: Household Foundation
    echo "Step 1: Creating households table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS households (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        created_by INTEGER NOT NULL,
        currency TEXT DEFAULT 'CZK',
        timezone TEXT DEFAULT 'Europe/Prague',
        avatar_url TEXT,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    echo "Step 2: Creating household_members table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS household_members (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        household_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        role TEXT NOT NULL,
        permission_level INTEGER NOT NULL DEFAULT 50,
        custom_permissions TEXT,
        display_name TEXT,
        is_active INTEGER DEFAULT 1,
        joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_activity_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    echo "Step 3: Creating household_invitations table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS household_invitations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        household_id INTEGER NOT NULL,
        invited_by INTEGER NOT NULL,
        invitee_email TEXT NOT NULL,
        invitee_user_id INTEGER,
        role TEXT NOT NULL,
        permission_level INTEGER NOT NULL DEFAULT 50,
        invitation_token TEXT NOT NULL UNIQUE,
        message TEXT,
        status TEXT DEFAULT 'pending',
        expires_at DATETIME NOT NULL,
        accepted_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    echo "Step 4: Creating household_settings table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS household_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        household_id INTEGER NOT NULL UNIQUE,
        default_visibility TEXT DEFAULT 'shared',
        allow_member_invites INTEGER DEFAULT 0,
        require_approval_threshold REAL DEFAULT 1000.00,
        notify_new_transactions INTEGER DEFAULT 1,
        notify_budget_alerts INTEGER DEFAULT 1,
        allow_child_accounts INTEGER DEFAULT 1,
        child_requires_approval INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    echo "Step 5: Creating indexes...\n";
    $db->exec("CREATE INDEX IF NOT EXISTS idx_households_created_by ON households(created_by)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_household_members_household ON household_members(household_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_household_members_user ON household_members(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_invitations_token ON household_invitations(invitation_token)");

    echo "Step 6: Creating households for existing users...\n";
    $stmt = $db->query("SELECT id, name, currency FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $householdsCreated = 0;
    foreach ($users as $user) {
        // Check if user already has a household
        $check = $db->prepare("SELECT COUNT(*) FROM household_members WHERE user_id = ?");
        $check->execute([$user['id']]);
        if ($check->fetchColumn() > 0) {
            continue;
        }

        // Create household
        $stmt = $db->prepare("INSERT INTO households (name, created_by, currency) VALUES (?, ?, ?)");
        $stmt->execute([$user['name'] . "'s Household", $user['id'], $user['currency']]);
        $householdId = $db->lastInsertId();

        // Add user as owner
        $stmt = $db->prepare("INSERT INTO household_members (household_id, user_id, role, permission_level) VALUES (?, ?, 'owner', 100)");
        $stmt->execute([$householdId, $user['id']]);

        // Create settings
        $stmt = $db->prepare("INSERT INTO household_settings (household_id) VALUES (?)");
        $stmt->execute([$householdId]);

        $householdsCreated++;
    }
    echo "  Created $householdsCreated households\n";

    // Mark migration 013 as applied
    $db->exec("INSERT OR IGNORE INTO schema_migrations (migration_name) VALUES ('013_add_household_foundation.sql')");
    echo "✅ Migration 013 complete\n\n";

    // Migration 014: Shared Data Flags
    echo "Migration 014: Adding shared data flags...\n";

    // Add columns to transactions
    $db->exec("ALTER TABLE transactions ADD COLUMN household_id INTEGER");
    $db->exec("ALTER TABLE transactions ADD COLUMN visibility TEXT DEFAULT 'private'");

    // Add to other tables
    $db->exec("ALTER TABLE accounts ADD COLUMN household_id INTEGER");
    $db->exec("ALTER TABLE accounts ADD COLUMN visibility TEXT DEFAULT 'private'");

    $db->exec("ALTER TABLE budgets ADD COLUMN household_id INTEGER");
    $db->exec("ALTER TABLE budgets ADD COLUMN visibility TEXT DEFAULT 'private'");

    $db->exec("ALTER TABLE goals ADD COLUMN household_id INTEGER");
    $db->exec("ALTER TABLE goals ADD COLUMN visibility TEXT DEFAULT 'private'");

    // Link existing data to households
    $db->exec("UPDATE transactions SET household_id = (
        SELECT hm.household_id FROM household_members hm
        WHERE hm.user_id = transactions.user_id AND hm.role = 'owner' LIMIT 1
    ) WHERE household_id IS NULL");

    $db->exec("UPDATE accounts SET household_id = (
        SELECT hm.household_id FROM household_members hm
        WHERE hm.user_id = accounts.user_id AND hm.role = 'owner' LIMIT 1
    ) WHERE household_id IS NULL");

    $db->exec("UPDATE budgets SET household_id = (
        SELECT hm.household_id FROM household_members hm
        WHERE hm.user_id = budgets.user_id AND hm.role = 'owner' LIMIT 1
    ) WHERE household_id IS NULL");

    $db->exec("UPDATE goals SET household_id = (
        SELECT hm.household_id FROM household_members hm
        WHERE hm.user_id = goals.user_id AND hm.role = 'owner' LIMIT 1
    ) WHERE household_id IS NULL");

    $db->exec("INSERT OR IGNORE INTO schema_migrations (migration_name) VALUES ('014_add_shared_data_flags.sql')");
    echo "✅ Migration 014 complete\n\n";

    // Migration 015: Activity & Notifications
    echo "Migration 015: Creating activity and notification tables...\n";

    $db->exec("CREATE TABLE IF NOT EXISTS household_activities (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        household_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        activity_type TEXT NOT NULL,
        entity_type TEXT NOT NULL,
        entity_id INTEGER NOT NULL,
        action TEXT NOT NULL,
        description TEXT NOT NULL,
        metadata_json TEXT,
        visibility TEXT DEFAULT 'all',
        is_important INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS approval_requests (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        household_id INTEGER NOT NULL,
        requested_by INTEGER NOT NULL,
        request_type TEXT NOT NULL,
        entity_type TEXT NOT NULL,
        entity_id INTEGER NOT NULL,
        amount REAL,
        description TEXT NOT NULL,
        status TEXT DEFAULT 'pending',
        reviewed_by INTEGER,
        reviewed_at DATETIME,
        expires_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        household_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        notification_type TEXT NOT NULL,
        title TEXT NOT NULL,
        message TEXT NOT NULL,
        priority TEXT DEFAULT 'normal',
        action_url TEXT,
        action_label TEXT,
        icon TEXT,
        is_read INTEGER DEFAULT 0,
        is_archived INTEGER DEFAULT 0,
        read_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE INDEX IF NOT EXISTS idx_activities_household ON household_activities(household_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_notifications_read ON notifications(is_read)");

    $db->exec("INSERT OR IGNORE INTO schema_migrations (migration_name) VALUES ('015_add_activity_audit.sql')");
    echo "✅ Migration 015 complete\n\n";

    echo "\n✅ All household migrations applied successfully!\n";
    echo "\n📊 Summary:\n";
    echo "  ✅ Household foundation (013)\n";
    echo "  ✅ Shared data flags (014)\n";
    echo "  ✅ Activity & notifications (015)\n";
    echo "  ⏭️  Comments (016) - already applied\n";
    echo "  ⚠️  Child accounts (017) - needs manual fix\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
