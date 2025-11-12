-- Budget Control Application - Base Database Schema
-- SQLite Database Structure
-- Created: 2025-11-12
-- Description: Complete schema for budget control and financial management application

-- Enable foreign keys
PRAGMA foreign_keys = ON;

-- ============================================================================
-- Core User Management
-- ============================================================================

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    currency TEXT DEFAULT 'CZK',
    timezone TEXT DEFAULT 'Europe/Prague',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_users_email ON users(email);

-- Password Reset Tokens
CREATE TABLE IF NOT EXISTS password_resets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token TEXT NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    used_at DATETIME,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_password_resets_token ON password_resets(token);
CREATE INDEX idx_password_resets_user_id ON password_resets(user_id);

-- User Settings
CREATE TABLE IF NOT EXISTS user_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    category TEXT NOT NULL, -- 'profile', 'notifications', 'preferences', 'security', 'automation'
    setting_key TEXT NOT NULL,
    setting_value TEXT, -- JSON encoded value
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id, category, setting_key)
);

CREATE INDEX idx_user_settings_user_id ON user_settings(user_id);
CREATE INDEX idx_user_settings_category ON user_settings(category);

-- ============================================================================
-- Accounts & Transactions
-- ============================================================================

CREATE TABLE IF NOT EXISTS accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    type TEXT NOT NULL, -- 'checking', 'savings', 'credit_card', 'investment', 'cash'
    balance REAL DEFAULT 0,
    currency TEXT DEFAULT 'CZK',
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_accounts_user_id ON accounts(user_id);
CREATE INDEX idx_accounts_type ON accounts(type);

CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER, -- NULL for system categories
    name TEXT NOT NULL,
    type TEXT NOT NULL, -- 'income', 'expense'
    icon TEXT,
    color TEXT,
    parent_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE INDEX idx_categories_user_id ON categories(user_id);
CREATE INDEX idx_categories_type ON categories(type);

CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    account_id INTEGER NOT NULL,
    category_id INTEGER,
    type TEXT NOT NULL, -- 'income', 'expense', 'transfer'
    amount REAL NOT NULL,
    date DATE NOT NULL,
    description TEXT,
    merchant_name TEXT,
    notes TEXT,
    is_recurring INTEGER DEFAULT 0,
    recurring_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY(recurring_id) REFERENCES recurring_transactions(id) ON DELETE SET NULL
);

CREATE INDEX idx_transactions_user_id ON transactions(user_id);
CREATE INDEX idx_transactions_account_id ON transactions(account_id);
CREATE INDEX idx_transactions_category_id ON transactions(category_id);
CREATE INDEX idx_transactions_date ON transactions(date);
CREATE INDEX idx_transactions_type ON transactions(type);

-- Transaction Splits (for splitting transactions across multiple categories)
CREATE TABLE IF NOT EXISTS transaction_splits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    transaction_id INTEGER NOT NULL,
    category_id INTEGER NOT NULL,
    amount REAL NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE CASCADE
);

CREATE INDEX idx_transaction_splits_transaction_id ON transaction_splits(transaction_id);

-- Recurring Transactions
CREATE TABLE IF NOT EXISTS recurring_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    account_id INTEGER NOT NULL,
    category_id INTEGER,
    type TEXT NOT NULL,
    amount REAL NOT NULL,
    frequency TEXT NOT NULL, -- 'daily', 'weekly', 'bi-weekly', 'monthly', 'quarterly', 'yearly'
    description TEXT,
    start_date DATE NOT NULL,
    end_date DATE,
    next_date DATE NOT NULL,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE INDEX idx_recurring_transactions_user_id ON recurring_transactions(user_id);
CREATE INDEX idx_recurring_transactions_next_date ON recurring_transactions(next_date);

-- Merchants (for transaction categorization)
CREATE TABLE IF NOT EXISTS merchants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    default_category_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(default_category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE INDEX idx_merchants_name ON merchants(name);

-- ============================================================================
-- Budgets
-- ============================================================================

CREATE TABLE IF NOT EXISTS budgets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    category_id INTEGER NOT NULL,
    amount REAL NOT NULL,
    period TEXT NOT NULL, -- 'monthly', 'quarterly', 'yearly'
    start_date DATE NOT NULL,
    end_date DATE,
    alert_threshold REAL DEFAULT 0.8, -- Alert when 80% spent
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE CASCADE
);

CREATE INDEX idx_budgets_user_id ON budgets(user_id);
CREATE INDEX idx_budgets_category_id ON budgets(category_id);
CREATE INDEX idx_budgets_period ON budgets(period);

-- Budget Alerts
CREATE TABLE IF NOT EXISTS budget_alerts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    budget_id INTEGER NOT NULL,
    alert_type TEXT NOT NULL, -- 'threshold', 'exceeded', 'near_end'
    percentage REAL NOT NULL,
    amount_spent REAL NOT NULL,
    budget_amount REAL NOT NULL,
    acknowledged INTEGER DEFAULT 0,
    dismissed INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(budget_id) REFERENCES budgets(id) ON DELETE CASCADE
);

CREATE INDEX idx_budget_alerts_user_id ON budget_alerts(user_id);
CREATE INDEX idx_budget_alerts_budget_id ON budget_alerts(budget_id);
CREATE INDEX idx_budget_alerts_created_at ON budget_alerts(created_at);

-- Budget Templates
CREATE TABLE IF NOT EXISTS budget_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER, -- NULL for system templates
    name TEXT NOT NULL,
    description TEXT,
    template_type TEXT NOT NULL, -- 'percentage', 'fixed', 'zero_based'
    is_system INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_budget_templates_user_id ON budget_templates(user_id);
CREATE INDEX idx_budget_templates_is_system ON budget_templates(is_system);

CREATE TABLE IF NOT EXISTS budget_template_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    template_id INTEGER NOT NULL,
    category_id INTEGER,
    category_name TEXT NOT NULL,
    suggested_amount REAL,
    suggested_percentage REAL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(template_id) REFERENCES budget_templates(id) ON DELETE CASCADE,
    FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE INDEX idx_budget_template_categories_template_id ON budget_template_categories(template_id);

CREATE TABLE IF NOT EXISTS user_template_preferences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    template_id INTEGER NOT NULL,
    last_applied_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(template_id) REFERENCES budget_templates(id) ON DELETE CASCADE,
    UNIQUE(user_id, template_id)
);

CREATE INDEX idx_user_template_preferences_user_id ON user_template_preferences(user_id);

-- ============================================================================
-- Financial Goals
-- ============================================================================

CREATE TABLE IF NOT EXISTS goals (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    goal_type TEXT NOT NULL, -- 'savings', 'debt_payoff', 'investment', 'purchase'
    target_amount REAL NOT NULL,
    current_amount REAL DEFAULT 0,
    target_date DATE,
    category TEXT, -- 'emergency', 'retirement', 'purchase', 'travel', 'education', etc.
    priority TEXT DEFAULT 'medium', -- 'low', 'medium', 'high'
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_goals_user_id ON goals(user_id);
CREATE INDEX idx_goals_type ON goals(goal_type);
CREATE INDEX idx_goals_target_date ON goals(target_date);

CREATE TABLE IF NOT EXISTS goal_milestones (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    goal_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    target_amount REAL NOT NULL,
    target_date DATE,
    completed INTEGER DEFAULT 0,
    completed_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(goal_id) REFERENCES goals(id) ON DELETE CASCADE
);

CREATE INDEX idx_goal_milestones_goal_id ON goal_milestones(goal_id);

CREATE TABLE IF NOT EXISTS goal_progress_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    goal_id INTEGER NOT NULL,
    amount REAL NOT NULL,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(goal_id) REFERENCES goals(id) ON DELETE CASCADE
);

CREATE INDEX idx_goal_progress_history_goal_id ON goal_progress_history(goal_id);

-- ============================================================================
-- Investments
-- ============================================================================

CREATE TABLE IF NOT EXISTS investment_accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    account_type TEXT NOT NULL, -- 'brokerage', 'retirement', 'ira', 'pension'
    broker_name TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_investment_accounts_user_id ON investment_accounts(user_id);

CREATE TABLE IF NOT EXISTS investments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    account_id INTEGER NOT NULL,
    symbol TEXT NOT NULL,
    name TEXT NOT NULL,
    type TEXT NOT NULL, -- 'stock', 'bond', 'etf', 'mutual_fund', 'crypto', 'real_estate'
    quantity REAL NOT NULL,
    purchase_price REAL NOT NULL,
    current_price REAL,
    currency TEXT DEFAULT 'CZK',
    purchase_date DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(account_id) REFERENCES investment_accounts(id) ON DELETE CASCADE
);

CREATE INDEX idx_investments_user_id ON investments(user_id);
CREATE INDEX idx_investments_account_id ON investments(account_id);
CREATE INDEX idx_investments_symbol ON investments(symbol);

CREATE TABLE IF NOT EXISTS investment_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    investment_id INTEGER NOT NULL,
    transaction_type TEXT NOT NULL, -- 'buy', 'sell', 'dividend', 'split'
    quantity REAL NOT NULL,
    price REAL NOT NULL,
    fees REAL DEFAULT 0,
    transaction_date DATE NOT NULL,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(investment_id) REFERENCES investments(id) ON DELETE CASCADE
);

CREATE INDEX idx_investment_transactions_investment_id ON investment_transactions(investment_id);
CREATE INDEX idx_investment_transactions_date ON investment_transactions(transaction_date);

CREATE TABLE IF NOT EXISTS investment_prices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    symbol TEXT NOT NULL,
    price REAL NOT NULL,
    currency TEXT DEFAULT 'CZK',
    price_date DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(symbol, price_date)
);

CREATE INDEX idx_investment_prices_symbol ON investment_prices(symbol);
CREATE INDEX idx_investment_prices_date ON investment_prices(price_date);

-- ============================================================================
-- Import/Export
-- ============================================================================

CREATE TABLE IF NOT EXISTS csv_imports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    filename TEXT NOT NULL,
    row_count INTEGER NOT NULL,
    imported_count INTEGER DEFAULT 0,
    failed_count INTEGER DEFAULT 0,
    status TEXT DEFAULT 'processing', -- 'processing', 'completed', 'failed'
    mapping_config TEXT, -- JSON mapping configuration
    error_log TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_csv_imports_user_id ON csv_imports(user_id);
CREATE INDEX idx_csv_imports_status ON csv_imports(status);

CREATE TABLE IF NOT EXISTS bank_import_jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    bank_name TEXT NOT NULL,
    account_id INTEGER,
    file_path TEXT NOT NULL,
    status TEXT DEFAULT 'pending', -- 'pending', 'processing', 'completed', 'failed'
    transactions_imported INTEGER DEFAULT 0,
    error_message TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(account_id) REFERENCES accounts(id) ON DELETE SET NULL
);

CREATE INDEX idx_bank_import_jobs_user_id ON bank_import_jobs(user_id);
CREATE INDEX idx_bank_import_jobs_status ON bank_import_jobs(status);

-- ============================================================================
-- Automation & AI
-- ============================================================================

CREATE TABLE IF NOT EXISTS categorization_rules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    rule_name TEXT NOT NULL,
    pattern TEXT NOT NULL, -- Regex or keyword pattern
    category_id INTEGER NOT NULL,
    priority INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE CASCADE
);

CREATE INDEX idx_categorization_rules_user_id ON categorization_rules(user_id);

CREATE TABLE IF NOT EXISTS automated_actions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    action_type TEXT NOT NULL, -- 'auto_categorize', 'budget_alert', 'recurring_create'
    trigger_type TEXT NOT NULL, -- 'schedule', 'transaction_create', 'budget_threshold'
    trigger_condition TEXT, -- JSON condition
    action_data TEXT, -- JSON action configuration
    is_active INTEGER DEFAULT 1,
    last_executed_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_automated_actions_user_id ON automated_actions(user_id);

CREATE TABLE IF NOT EXISTS ai_recommendations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    recommendation_type TEXT NOT NULL, -- 'budget_optimization', 'savings_opportunity', 'debt_reduction'
    title TEXT NOT NULL,
    description TEXT NOT NULL,
    priority TEXT DEFAULT 'medium',
    potential_savings REAL,
    confidence_score REAL,
    status TEXT DEFAULT 'active', -- 'active', 'accepted', 'rejected', 'expired'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_ai_recommendations_user_id ON ai_recommendations(user_id);
CREATE INDEX idx_ai_recommendations_status ON ai_recommendations(status);

CREATE TABLE IF NOT EXISTS recommendation_feedback (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    recommendation_id INTEGER NOT NULL,
    feedback_type TEXT NOT NULL, -- 'accepted', 'rejected', 'helpful', 'not_helpful'
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(recommendation_id) REFERENCES ai_recommendations(id) ON DELETE CASCADE
);

CREATE INDEX idx_recommendation_feedback_user_id ON recommendation_feedback(user_id);
CREATE INDEX idx_recommendation_feedback_recommendation_id ON recommendation_feedback(recommendation_id);

CREATE TABLE IF NOT EXISTS recommendation_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    recommendation_type TEXT NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_recommendation_history_user_id ON recommendation_history(user_id);

-- ============================================================================
-- Czech-specific Features
-- ============================================================================

CREATE TABLE IF NOT EXISTS czech_benefits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    benefit_name TEXT NOT NULL,
    benefit_type TEXT NOT NULL, -- 'tax_deduction', 'family_allowance', 'housing_support', etc.
    description TEXT,
    eligibility_criteria TEXT,
    amount_min REAL,
    amount_max REAL,
    application_url TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_czech_benefits_type ON czech_benefits(benefit_type);

CREATE TABLE IF NOT EXISTS user_benefit_applications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    benefit_id INTEGER NOT NULL,
    status TEXT DEFAULT 'draft', -- 'draft', 'applied', 'approved', 'rejected'
    application_date DATE,
    approval_date DATE,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(benefit_id) REFERENCES czech_benefits(id) ON DELETE CASCADE
);

CREATE INDEX idx_user_benefit_applications_user_id ON user_benefit_applications(user_id);

CREATE TABLE IF NOT EXISTS job_opportunities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    company TEXT NOT NULL,
    location TEXT,
    salary_min REAL,
    salary_max REAL,
    currency TEXT DEFAULT 'CZK',
    job_type TEXT, -- 'full_time', 'part_time', 'contract', 'remote'
    description TEXT,
    requirements TEXT,
    url TEXT,
    source TEXT, -- 'jobs_cz', 'linkedin', 'startupjobs', etc.
    posted_date DATE,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_job_opportunities_location ON job_opportunities(location);
CREATE INDEX idx_job_opportunities_posted_date ON job_opportunities(posted_date);

CREATE TABLE IF NOT EXISTS job_market_feeds (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    feed_name TEXT NOT NULL,
    feed_url TEXT NOT NULL,
    feed_type TEXT NOT NULL, -- 'rss', 'api', 'scraper'
    is_active INTEGER DEFAULT 1,
    last_fetched_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- Notifications & Tips
-- ============================================================================

CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    type TEXT NOT NULL, -- 'budget_alert', 'goal_milestone', 'bill_reminder', 'recommendation'
    title TEXT NOT NULL,
    message TEXT NOT NULL,
    is_read INTEGER DEFAULT 0,
    action_url TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);
CREATE INDEX idx_notifications_created_at ON notifications(created_at);

CREATE TABLE IF NOT EXISTS tips (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    category TEXT NOT NULL, -- 'budgeting', 'saving', 'investing', 'debt', 'taxes'
    difficulty TEXT DEFAULT 'beginner', -- 'beginner', 'intermediate', 'advanced'
    priority INTEGER DEFAULT 0,
    is_published INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_tips_category ON tips(category);
CREATE INDEX idx_tips_is_published ON tips(is_published);

-- ============================================================================
-- Performance & Analytics
-- ============================================================================

CREATE TABLE IF NOT EXISTS financial_metrics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    metric_date DATE NOT NULL,
    total_income REAL DEFAULT 0,
    total_expenses REAL DEFAULT 0,
    net_savings REAL DEFAULT 0,
    budget_adherence REAL, -- Percentage
    savings_rate REAL, -- Percentage
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id, metric_date)
);

CREATE INDEX idx_financial_metrics_user_id ON financial_metrics(user_id);
CREATE INDEX idx_financial_metrics_date ON financial_metrics(metric_date);

CREATE TABLE IF NOT EXISTS performance_metrics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    metric_type TEXT NOT NULL, -- 'api_response', 'page_load', 'query_time'
    metric_name TEXT NOT NULL,
    value REAL NOT NULL,
    unit TEXT DEFAULT 'ms',
    metadata TEXT, -- JSON
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_performance_metrics_type ON performance_metrics(metric_type);
CREATE INDEX idx_performance_metrics_created_at ON performance_metrics(created_at);

-- ============================================================================
-- Security & Audit
-- ============================================================================

CREATE TABLE IF NOT EXISTS security_audit_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    event_type TEXT NOT NULL, -- 'login', 'logout', 'password_change', 'setting_change', etc.
    ip_address TEXT,
    user_agent TEXT,
    success INTEGER DEFAULT 1,
    metadata TEXT, -- JSON
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_security_audit_log_user_id ON security_audit_log(user_id);
CREATE INDEX idx_security_audit_log_event_type ON security_audit_log(event_type);
CREATE INDEX idx_security_audit_log_created_at ON security_audit_log(created_at);

-- ============================================================================
-- API & Integration
-- ============================================================================

CREATE TABLE IF NOT EXISTS api_keys (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    key_name TEXT NOT NULL,
    api_key TEXT NOT NULL UNIQUE,
    api_secret TEXT NOT NULL,
    permissions TEXT, -- JSON array of permissions
    is_active INTEGER DEFAULT 1,
    last_used_at DATETIME,
    expires_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_api_keys_user_id ON api_keys(user_id);
CREATE INDEX idx_api_keys_api_key ON api_keys(api_key);

CREATE TABLE IF NOT EXISTS api_rate_limits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    api_key TEXT NOT NULL,
    endpoint TEXT NOT NULL,
    request_count INTEGER DEFAULT 0,
    window_start DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(api_key, endpoint, window_start)
);

CREATE INDEX idx_api_rate_limits_api_key ON api_rate_limits(api_key);
CREATE INDEX idx_api_rate_limits_window_start ON api_rate_limits(window_start);

-- ============================================================================
-- LLM & AI Features
-- ============================================================================

CREATE TABLE IF NOT EXISTS llm_cache (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    prompt_hash TEXT NOT NULL UNIQUE,
    prompt TEXT NOT NULL,
    response TEXT NOT NULL,
    model TEXT NOT NULL,
    tokens_used INTEGER,
    cost REAL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME
);

CREATE INDEX idx_llm_cache_prompt_hash ON llm_cache(prompt_hash);
CREATE INDEX idx_llm_cache_expires_at ON llm_cache(expires_at);

CREATE TABLE IF NOT EXISTS llm_rate_limits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    request_count INTEGER DEFAULT 0,
    tokens_used INTEGER DEFAULT 0,
    window_start DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id, window_start)
);

CREATE INDEX idx_llm_rate_limits_user_id ON llm_rate_limits(user_id);

-- ============================================================================
-- Usability & Testing
-- ============================================================================

CREATE TABLE IF NOT EXISTS usability_test_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    session_type TEXT NOT NULL,
    ab_test_variant TEXT,
    task_completion_time REAL,
    success INTEGER,
    feedback TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_usability_test_sessions_user_id ON usability_test_sessions(user_id);

CREATE TABLE IF NOT EXISTS ai_insight_panels (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    panel_type TEXT NOT NULL, -- 'spending_insights', 'savings_opportunities', 'budget_health'
    content TEXT NOT NULL, -- JSON content
    is_dismissed INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    dismissed_at DATETIME,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_ai_insight_panels_user_id ON ai_insight_panels(user_id);

-- ============================================================================
-- Crisis Mode & Emergency Features
-- ============================================================================

CREATE TABLE IF NOT EXISTS crisis_mode_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    is_active INTEGER DEFAULT 0,
    trigger_threshold REAL, -- Savings threshold that triggers crisis mode
    essential_categories TEXT, -- JSON array of category IDs
    activated_at DATETIME,
    deactivated_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id)
);

CREATE INDEX idx_crisis_mode_settings_user_id ON crisis_mode_settings(user_id);
