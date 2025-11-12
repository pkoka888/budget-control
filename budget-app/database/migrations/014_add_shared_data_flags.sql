-- Migration 014: Shared Data Flags
-- Purpose: Add household_id and visibility flags to all core financial tables
-- Features: Shared vs private data, household linking

-- =============================================================================
-- ADD HOUSEHOLD COLUMNS TO CORE TABLES
-- =============================================================================

-- TRANSACTIONS
ALTER TABLE transactions ADD COLUMN household_id INTEGER REFERENCES households(id) ON DELETE SET NULL;
ALTER TABLE transactions ADD COLUMN visibility TEXT DEFAULT 'private' CHECK(visibility IN ('private', 'shared'));
ALTER TABLE transactions ADD COLUMN requires_approval INTEGER DEFAULT 0;
ALTER TABLE transactions ADD COLUMN approved_by INTEGER REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE transactions ADD COLUMN approved_at DATETIME;

CREATE INDEX idx_transactions_household ON transactions(household_id);
CREATE INDEX idx_transactions_visibility ON transactions(visibility);
CREATE INDEX idx_transactions_requires_approval ON transactions(requires_approval);

-- ACCOUNTS
ALTER TABLE accounts ADD COLUMN household_id INTEGER REFERENCES households(id) ON DELETE SET NULL;
ALTER TABLE accounts ADD COLUMN visibility TEXT DEFAULT 'private' CHECK(visibility IN ('private', 'shared'));
ALTER TABLE accounts ADD COLUMN managed_by TEXT; -- JSON array of user IDs who can manage

CREATE INDEX idx_accounts_household ON accounts(household_id);
CREATE INDEX idx_accounts_visibility ON accounts(visibility);

-- BUDGETS
ALTER TABLE budgets ADD COLUMN household_id INTEGER REFERENCES households(id) ON DELETE SET NULL;
ALTER TABLE budgets ADD COLUMN visibility TEXT DEFAULT 'private' CHECK(visibility IN ('private', 'shared'));
ALTER TABLE budgets ADD COLUMN managed_by TEXT; -- JSON array of user IDs

CREATE INDEX idx_budgets_household ON budgets(household_id);
CREATE INDEX idx_budgets_visibility ON budgets(visibility);

-- GOALS
ALTER TABLE goals ADD COLUMN household_id INTEGER REFERENCES households(id) ON DELETE SET NULL;
ALTER TABLE goals ADD COLUMN visibility TEXT DEFAULT 'private' CHECK(visibility IN ('private', 'shared'));
ALTER TABLE goals ADD COLUMN contributors TEXT; -- JSON array of user IDs who can contribute

CREATE INDEX idx_goals_household ON goals(household_id);
CREATE INDEX idx_goals_visibility ON goals(visibility);

-- CATEGORIES
ALTER TABLE categories ADD COLUMN household_id INTEGER REFERENCES households(id) ON DELETE SET NULL;
ALTER TABLE categories ADD COLUMN visibility TEXT DEFAULT 'private' CHECK(visibility IN ('private', 'shared'));

CREATE INDEX idx_categories_household ON categories(household_id);
CREATE INDEX idx_categories_visibility ON categories(visibility);

-- =============================================================================
-- ADD HOUSEHOLD COLUMNS TO v1.1 FEATURE TABLES
-- =============================================================================

-- EXPENSE GROUPS (already multi-user, but add household link)
ALTER TABLE expense_groups ADD COLUMN household_id INTEGER REFERENCES households(id) ON DELETE SET NULL;

CREATE INDEX idx_expense_groups_household ON expense_groups(household_id);

-- RECEIPT SCANS
ALTER TABLE receipt_scans ADD COLUMN household_id INTEGER REFERENCES households(id) ON DELETE SET NULL;
ALTER TABLE receipt_scans ADD COLUMN visibility TEXT DEFAULT 'private' CHECK(visibility IN ('private', 'shared'));

CREATE INDEX idx_receipt_scans_household ON receipt_scans(household_id);
CREATE INDEX idx_receipt_scans_visibility ON receipt_scans(visibility);

-- =============================================================================
-- ADD HOUSEHOLD COLUMNS TO v2.0 FEATURE TABLES
-- =============================================================================

-- INVESTMENT ACCOUNTS
ALTER TABLE investment_accounts ADD COLUMN household_id INTEGER REFERENCES households(id) ON DELETE SET NULL;
ALTER TABLE investment_accounts ADD COLUMN visibility TEXT DEFAULT 'private' CHECK(visibility IN ('private', 'shared'));

CREATE INDEX idx_investment_accounts_household ON investment_accounts(household_id);
CREATE INDEX idx_investment_accounts_visibility ON investment_accounts(visibility);

-- RECURRING BILLS
ALTER TABLE recurring_bills ADD COLUMN household_id INTEGER REFERENCES households(id) ON DELETE SET NULL;
ALTER TABLE recurring_bills ADD COLUMN visibility TEXT DEFAULT 'private' CHECK(visibility IN ('private', 'shared'));
ALTER TABLE recurring_bills ADD COLUMN managed_by TEXT; -- JSON array

CREATE INDEX idx_recurring_bills_household ON recurring_bills(household_id);
CREATE INDEX idx_recurring_bills_visibility ON recurring_bills(visibility);

-- SUBSCRIPTIONS
ALTER TABLE subscriptions ADD COLUMN household_id INTEGER REFERENCES households(id) ON DELETE SET NULL;
ALTER TABLE subscriptions ADD COLUMN visibility TEXT DEFAULT 'private' CHECK(visibility IN ('private', 'shared'));

CREATE INDEX idx_subscriptions_household ON subscriptions(household_id);
CREATE INDEX idx_subscriptions_visibility ON subscriptions(visibility);

-- AI INSIGHTS
ALTER TABLE ai_insights ADD COLUMN household_id INTEGER REFERENCES households(id) ON DELETE SET NULL;
ALTER TABLE ai_insights ADD COLUMN visibility TEXT DEFAULT 'private' CHECK(visibility IN ('private', 'shared'));

CREATE INDEX idx_ai_insights_household ON ai_insights(household_id);
CREATE INDEX idx_ai_insights_visibility ON ai_insights(visibility);

-- =============================================================================
-- DATA MIGRATION: LINK EXISTING DATA TO HOUSEHOLDS
-- =============================================================================

-- Link all existing transactions to user's household
UPDATE transactions
SET household_id = (
    SELECT hm.household_id
    FROM household_members hm
    WHERE hm.user_id = transactions.user_id AND hm.role = 'owner'
    LIMIT 1
)
WHERE household_id IS NULL;

-- Link all existing accounts to user's household
UPDATE accounts
SET household_id = (
    SELECT hm.household_id
    FROM household_members hm
    WHERE hm.user_id = accounts.user_id AND hm.role = 'owner'
    LIMIT 1
)
WHERE household_id IS NULL;

-- Link all existing budgets to user's household
UPDATE budgets
SET household_id = (
    SELECT hm.household_id
    FROM household_members hm
    WHERE hm.user_id = budgets.user_id AND hm.role = 'owner'
    LIMIT 1
)
WHERE household_id IS NULL;

-- Link all existing goals to user's household
UPDATE goals
SET household_id = (
    SELECT hm.household_id
    FROM household_members hm
    WHERE hm.user_id = goals.user_id AND hm.role = 'owner'
    LIMIT 1
)
WHERE household_id IS NULL;

-- Link all existing categories to user's household
UPDATE categories
SET household_id = (
    SELECT hm.household_id
    FROM household_members hm
    WHERE hm.user_id = categories.user_id AND hm.role = 'owner'
    LIMIT 1
)
WHERE household_id IS NULL;

-- Link expense groups
UPDATE expense_groups
SET household_id = (
    SELECT hm.household_id
    FROM household_members hm
    WHERE hm.user_id = expense_groups.created_by AND hm.role = 'owner'
    LIMIT 1
)
WHERE household_id IS NULL;

-- Link receipt scans
UPDATE receipt_scans
SET household_id = (
    SELECT hm.household_id
    FROM household_members hm
    WHERE hm.user_id = receipt_scans.user_id AND hm.role = 'owner'
    LIMIT 1
)
WHERE household_id IS NULL;

-- Link investment accounts
UPDATE investment_accounts
SET household_id = (
    SELECT hm.household_id
    FROM household_members hm
    WHERE hm.user_id = investment_accounts.user_id AND hm.role = 'owner'
    LIMIT 1
)
WHERE household_id IS NULL;

-- Link recurring bills
UPDATE recurring_bills
SET household_id = (
    SELECT hm.household_id
    FROM household_members hm
    WHERE hm.user_id = recurring_bills.user_id AND hm.role = 'owner'
    LIMIT 1
)
WHERE household_id IS NULL;

-- Link subscriptions
UPDATE subscriptions
SET household_id = (
    SELECT hm.household_id
    FROM household_members hm
    WHERE hm.user_id = subscriptions.user_id AND hm.role = 'owner'
    LIMIT 1
)
WHERE household_id IS NULL;

-- Link AI insights
UPDATE ai_insights
SET household_id = (
    SELECT hm.household_id
    FROM household_members hm
    WHERE hm.user_id = ai_insights.user_id AND hm.role = 'owner'
    LIMIT 1
)
WHERE household_id IS NULL;

-- =============================================================================
-- NOTES
-- =============================================================================
-- Visibility Rules:
-- - 'private': Only visible to the user who created it
-- - 'shared': Visible to all household members (based on permission level)
--
-- Default Behavior:
-- - All existing data remains private
-- - Users can explicitly mark data as shared
-- - Shared data respects permission levels
--
-- Migration Safety:
-- - All household_id columns are nullable (no data loss)
-- - Existing data linked to single-member households
-- - Visibility defaults to 'private' (preserves privacy)
