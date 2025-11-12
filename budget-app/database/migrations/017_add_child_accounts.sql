-- Migration 017: Child Accounts
-- Purpose: Child financial education with allowances, chores, and rewards
-- Features: Spending limits, parental approval, chores system, rewards, savings goals

-- =============================================================================
-- CHILD ACCOUNT SETTINGS TABLE
-- =============================================================================
-- Extended settings for child household members
CREATE TABLE IF NOT EXISTS child_account_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    household_member_id INTEGER NOT NULL UNIQUE,
    user_id INTEGER NOT NULL,
    household_id INTEGER NOT NULL,

    -- Spending limits
    daily_limit REAL DEFAULT 10.00,
    weekly_limit REAL DEFAULT 50.00,
    monthly_limit REAL DEFAULT 200.00,
    per_transaction_limit REAL DEFAULT 20.00,

    -- Approval settings
    requires_approval_above REAL DEFAULT 10.00,
    requires_approval_categories TEXT, -- JSON array of category IDs
    auto_approve_merchants TEXT, -- JSON array of trusted merchants

    -- Access controls
    can_view_household_data INTEGER DEFAULT 0,
    can_create_goals INTEGER DEFAULT 1,
    can_request_money INTEGER DEFAULT 1,
    can_send_money INTEGER DEFAULT 0,

    -- Parental controls
    supervised_by INTEGER NOT NULL, -- Parent/guardian user ID
    restricted_categories TEXT, -- JSON array of blocked categories
    allowed_hours_start TEXT DEFAULT '06:00',
    allowed_hours_end TEXT DEFAULT '22:00',
    block_weekdays INTEGER DEFAULT 0,

    -- Balance
    current_balance REAL DEFAULT 0.00,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_member_id) REFERENCES household_members(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (supervised_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_child_settings_member ON child_account_settings(household_member_id);
CREATE INDEX idx_child_settings_user ON child_account_settings(user_id);
CREATE INDEX idx_child_settings_household ON child_account_settings(household_id);
CREATE INDEX idx_child_settings_supervisor ON child_account_settings(supervised_by);

-- =============================================================================
-- ALLOWANCES TABLE
-- =============================================================================
-- Recurring allowance payments to children
CREATE TABLE IF NOT EXISTS allowances (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    household_id INTEGER NOT NULL,
    child_user_id INTEGER NOT NULL,
    parent_user_id INTEGER NOT NULL,

    amount REAL NOT NULL,
    frequency TEXT NOT NULL CHECK(frequency IN ('daily', 'weekly', 'biweekly', 'monthly')),
    day_of_payment INTEGER, -- Day of week (1-7) or day of month (1-31)

    is_active INTEGER DEFAULT 1,
    requires_chores INTEGER DEFAULT 0, -- Must complete chores to receive
    min_chores_required INTEGER DEFAULT 0,

    next_payment_date DATE NOT NULL,
    last_payment_date DATE,

    auto_split_savings INTEGER DEFAULT 0, -- Automatically move % to savings
    savings_percentage REAL DEFAULT 0.00,

    notes TEXT,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (child_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_allowances_household ON allowances(household_id);
CREATE INDEX idx_allowances_child ON allowances(child_user_id);
CREATE INDEX idx_allowances_parent ON allowances(parent_user_id);
CREATE INDEX idx_allowances_next_payment ON allowances(next_payment_date);
CREATE INDEX idx_allowances_active ON allowances(is_active);

-- =============================================================================
-- ALLOWANCE PAYMENTS TABLE
-- =============================================================================
-- History of allowance payments
CREATE TABLE IF NOT EXISTS allowance_payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    allowance_id INTEGER NOT NULL,
    household_id INTEGER NOT NULL,
    child_user_id INTEGER NOT NULL,
    parent_user_id INTEGER NOT NULL,

    amount REAL NOT NULL,
    status TEXT DEFAULT 'completed' CHECK(status IN ('pending', 'completed', 'skipped', 'cancelled')),
    skip_reason TEXT,

    chores_completed INTEGER DEFAULT 0,
    chores_required INTEGER DEFAULT 0,

    scheduled_date DATE NOT NULL,
    paid_date DATETIME,

    notes TEXT,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (allowance_id) REFERENCES allowances(id) ON DELETE CASCADE,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (child_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_allowance_payments_allowance ON allowance_payments(allowance_id);
CREATE INDEX idx_allowance_payments_child ON allowance_payments(child_user_id);
CREATE INDEX idx_allowance_payments_scheduled ON allowance_payments(scheduled_date);

-- =============================================================================
-- CHORES TABLE
-- =============================================================================
-- Household chores that earn rewards
CREATE TABLE IF NOT EXISTS chores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    household_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    description TEXT,
    category TEXT, -- cleaning, yard, pet_care, homework, etc.

    assigned_to INTEGER, -- Child user ID
    created_by INTEGER NOT NULL, -- Parent user ID

    reward_amount REAL DEFAULT 0.00,
    reward_type TEXT DEFAULT 'money' CHECK(reward_type IN ('money', 'points', 'privilege')),

    difficulty TEXT DEFAULT 'easy' CHECK(difficulty IN ('easy', 'medium', 'hard')),
    estimated_minutes INTEGER,

    frequency TEXT DEFAULT 'once' CHECK(frequency IN ('once', 'daily', 'weekly', 'monthly')),
    recurring_days TEXT, -- JSON array: [1,3,5] for Mon/Wed/Fri

    is_active INTEGER DEFAULT 1,
    is_template INTEGER DEFAULT 0, -- Reusable chore template

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_chores_household ON chores(household_id);
CREATE INDEX idx_chores_assigned ON chores(assigned_to);
CREATE INDEX idx_chores_active ON chores(is_active);
CREATE INDEX idx_chores_template ON chores(is_template);

-- =============================================================================
-- CHORE COMPLETIONS TABLE
-- =============================================================================
-- Track when chores are completed
CREATE TABLE IF NOT EXISTS chore_completions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    chore_id INTEGER NOT NULL,
    household_id INTEGER NOT NULL,
    completed_by INTEGER NOT NULL,

    status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'approved', 'rejected', 'disputed')),
    verified_by INTEGER, -- Parent who verified
    verified_at DATETIME,
    verification_notes TEXT,

    completion_date DATE NOT NULL,
    completion_time DATETIME DEFAULT CURRENT_TIMESTAMP,

    quality_rating INTEGER, -- 1-5 stars
    time_taken_minutes INTEGER,

    photo_proof TEXT, -- File path to photo
    notes TEXT,

    reward_amount REAL DEFAULT 0.00,
    reward_paid INTEGER DEFAULT 0,
    reward_paid_at DATETIME,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chore_id) REFERENCES chores(id) ON DELETE CASCADE,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_completions_chore ON chore_completions(chore_id);
CREATE INDEX idx_completions_completed_by ON chore_completions(completed_by);
CREATE INDEX idx_completions_status ON chore_completions(status);
CREATE INDEX idx_completions_date ON chore_completions(completion_date);

-- =============================================================================
-- REWARDS TABLE
-- =============================================================================
-- Special rewards and bonuses
CREATE TABLE IF NOT EXISTS rewards (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    household_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    description TEXT,

    reward_type TEXT NOT NULL CHECK(reward_type IN ('bonus', 'achievement', 'milestone', 'behavior')),

    given_to INTEGER NOT NULL,
    given_by INTEGER NOT NULL,

    amount REAL DEFAULT 0.00,
    currency_type TEXT DEFAULT 'money' CHECK(currency_type IN ('money', 'points')),

    reason TEXT,
    is_recurring INTEGER DEFAULT 0,

    awarded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (given_to) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (given_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_rewards_household ON rewards(household_id);
CREATE INDEX idx_rewards_recipient ON rewards(given_to);
CREATE INDEX idx_rewards_type ON rewards(reward_type);

-- =============================================================================
-- MONEY REQUESTS TABLE
-- =============================================================================
-- Children can request money from parents
CREATE TABLE IF NOT EXISTS money_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    household_id INTEGER NOT NULL,
    requested_by INTEGER NOT NULL, -- Child user ID
    requested_from INTEGER NOT NULL, -- Parent user ID

    amount REAL NOT NULL,
    reason TEXT NOT NULL,
    category TEXT,

    status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'approved', 'rejected', 'cancelled')),
    reviewed_at DATETIME,
    review_notes TEXT,

    is_urgent INTEGER DEFAULT 0,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_from) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_money_requests_household ON money_requests(household_id);
CREATE INDEX idx_money_requests_child ON money_requests(requested_by);
CREATE INDEX idx_money_requests_parent ON money_requests(requested_from);
CREATE INDEX idx_money_requests_status ON money_requests(status);

-- =============================================================================
-- SAVINGS GOALS (CHILD-SPECIFIC)
-- =============================================================================
-- Link child users to their savings goals
ALTER TABLE goals ADD COLUMN is_child_goal INTEGER DEFAULT 0;
ALTER TABLE goals ADD COLUMN parent_contributions_allowed INTEGER DEFAULT 1;
ALTER TABLE goals ADD COLUMN matching_percentage REAL DEFAULT 0.00; -- Parent matches X%

CREATE INDEX idx_goals_child ON goals(is_child_goal);

-- =============================================================================
-- LEARNING RESOURCES TABLE
-- =============================================================================
-- Financial education content for children
CREATE TABLE IF NOT EXISTS learning_resources (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    household_id INTEGER,
    title TEXT NOT NULL,
    description TEXT,
    content_type TEXT NOT NULL CHECK(content_type IN ('article', 'video', 'quiz', 'game', 'lesson')),
    age_range TEXT, -- "8-12", "13-17"
    topic TEXT, -- saving, budgeting, investing, earning
    url TEXT,
    duration_minutes INTEGER,
    difficulty TEXT DEFAULT 'beginner' CHECK(difficulty IN ('beginner', 'intermediate', 'advanced')),
    points_reward INTEGER DEFAULT 0,
    is_required INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_learning_household ON learning_resources(household_id);
CREATE INDEX idx_learning_topic ON learning_resources(topic);
CREATE INDEX idx_learning_age ON learning_resources(age_range);

-- =============================================================================
-- LEARNING PROGRESS TABLE
-- =============================================================================
-- Track child progress through learning resources
CREATE TABLE IF NOT EXISTS learning_progress (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    resource_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    household_id INTEGER NOT NULL,

    status TEXT DEFAULT 'not_started' CHECK(status IN ('not_started', 'in_progress', 'completed')),
    progress_percentage INTEGER DEFAULT 0,
    quiz_score INTEGER,
    time_spent_minutes INTEGER DEFAULT 0,

    completed_at DATETIME,
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resource_id) REFERENCES learning_resources(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    UNIQUE(resource_id, user_id)
);

CREATE INDEX idx_learning_progress_user ON learning_progress(user_id);
CREATE INDEX idx_learning_progress_resource ON learning_progress(resource_id);

-- =============================================================================
-- NOTES
-- =============================================================================
-- Child Account Features:
-- 1. Spending limits (daily, weekly, monthly, per-transaction)
-- 2. Parental approval workflows
-- 3. Allowance automation with optional chore requirements
-- 4. Chores system with rewards
-- 5. Money request system
-- 6. Savings goals with parent matching
-- 7. Financial education tracking
--
-- Security:
-- - All child transactions require parent approval above threshold
-- - Spending limits enforced at transaction creation
-- - Parents can block specific categories
-- - Time-of-day restrictions available
--
-- Educational Focus:
-- - Chores teach earning money
-- - Allowances teach regular income
-- - Savings goals teach delayed gratification
-- - Learning resources teach financial literacy
