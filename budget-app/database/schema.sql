-- Budget Control - Financial Management Application
-- SQLite Database Schema
-- Version 1.0

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    currency TEXT DEFAULT 'CZK',
    timezone TEXT DEFAULT 'Europe/Prague',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Accounts Table (Checking, Savings, Credit Cards, Loans, Investments)
CREATE TABLE IF NOT EXISTS accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    type TEXT NOT NULL,
    currency TEXT DEFAULT 'CZK',
    balance DECIMAL(15, 2) DEFAULT 0,
    initial_balance DECIMAL(15, 2) DEFAULT 0,
    opening_date DATE,
    description TEXT,
    account_number TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    type TEXT NOT NULL,
    color TEXT DEFAULT '#3b82f6',
    icon TEXT DEFAULT 'tag',
    description TEXT,
    is_custom INTEGER DEFAULT 1,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id, name)
);

-- Transactions Table
CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    account_id INTEGER NOT NULL,
    category_id INTEGER,
    merchant_id INTEGER,
    type TEXT NOT NULL,
    description TEXT NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    currency TEXT DEFAULT 'CZK',
    date DATE NOT NULL,
    notes TEXT,
    tags TEXT,
    is_transfer INTEGER DEFAULT 0,
    transfer_id INTEGER,
    is_reconciled INTEGER DEFAULT 0,
    is_locked INTEGER DEFAULT 0,
    reference_number TEXT,
    imported_from_json TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY(category_id) REFERENCES categories(id),
    FOREIGN KEY(merchant_id) REFERENCES merchants(id)
);

-- Merchants Table (for categorization learning)
CREATE TABLE IF NOT EXISTS merchants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    category_id INTEGER,
    frequency INTEGER DEFAULT 1,
    last_used DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id, name)
);

-- Budgets Table
CREATE TABLE IF NOT EXISTS budgets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    category_id INTEGER NOT NULL,
    month TEXT NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    spent DECIMAL(15, 2) DEFAULT 0,
    notes TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE(user_id, category_id, month)
);

-- CSV Imports Table (tracking import history)
CREATE TABLE IF NOT EXISTS csv_imports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    account_id INTEGER NOT NULL,
    filename TEXT NOT NULL,
    rows_processed INTEGER DEFAULT 0,
    rows_imported INTEGER DEFAULT 0,
    rows_skipped INTEGER DEFAULT 0,
    errors TEXT,
    status TEXT DEFAULT 'pending',
    imported_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(account_id) REFERENCES accounts(id) ON DELETE CASCADE
);

-- Exchange Rates Table (for multi-currency support)
CREATE TABLE IF NOT EXISTS exchange_rates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    from_currency TEXT NOT NULL,
    to_currency TEXT NOT NULL,
    rate DECIMAL(10, 6) NOT NULL,
    date DATE NOT NULL,
    source TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(from_currency, to_currency, date)
);

-- Financial Goals Table
CREATE TABLE IF NOT EXISTS goals (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    goal_type TEXT NOT NULL DEFAULT 'savings', -- savings, debt_payoff, investment, emergency_fund
    target_amount DECIMAL(15, 2) NOT NULL,
    current_amount DECIMAL(15, 2) DEFAULT 0,
    target_date DATE,
    category TEXT DEFAULT 'general', -- general, vacation, car, house, education, retirement, etc.
    priority TEXT DEFAULT 'medium', -- low, medium, high
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Goal Milestones Table
CREATE TABLE IF NOT EXISTS goal_milestones (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    goal_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    target_amount DECIMAL(15, 2) NOT NULL,
    target_date DATE,
    is_completed INTEGER DEFAULT 0,
    completed_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(goal_id) REFERENCES goals(id) ON DELETE CASCADE
);

-- Goal Progress History Table (for tracking historical progress over time)
CREATE TABLE IF NOT EXISTS goal_progress_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    goal_id INTEGER NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(goal_id) REFERENCES goals(id) ON DELETE CASCADE
);

-- Investment Holdings Table (Enhanced)
CREATE TABLE IF NOT EXISTS investments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    account_id INTEGER NOT NULL,
    symbol TEXT NOT NULL,
    name TEXT NOT NULL,
    asset_type TEXT NOT NULL DEFAULT 'stock', -- stock, bond, etf, crypto, mutual_fund
    quantity DECIMAL(15, 6) NOT NULL,
    purchase_price DECIMAL(15, 2) NOT NULL,
    current_price DECIMAL(15, 2),
    currency TEXT DEFAULT 'CZK',
    exchange TEXT DEFAULT 'NASDAQ', -- exchange/market identifier
    sector TEXT, -- technology, healthcare, etc.
    last_updated DATE,
    notes TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(account_id) REFERENCES accounts(id) ON DELETE CASCADE
);

-- Investment Transactions Table (Enhanced)
CREATE TABLE IF NOT EXISTS investment_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    investment_id INTEGER NOT NULL,
    account_id INTEGER NOT NULL,
    transaction_type TEXT NOT NULL, -- buy, sell, dividend, stock_split, interest
    quantity DECIMAL(15, 6),
    price DECIMAL(15, 2),
    total_amount DECIMAL(15, 2) NOT NULL,
    currency TEXT DEFAULT 'CZK',
    exchange_rate DECIMAL(10, 6) DEFAULT 1.0,
    transaction_date DATE NOT NULL,
    settlement_date DATE,
    fees DECIMAL(10, 2) DEFAULT 0,
    taxes DECIMAL(10, 2) DEFAULT 0,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(investment_id) REFERENCES investments(id) ON DELETE CASCADE,
    FOREIGN KEY(account_id) REFERENCES accounts(id) ON DELETE CASCADE
);

-- Investment Price History Table (for performance tracking)
CREATE TABLE IF NOT EXISTS investment_prices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    investment_id INTEGER NOT NULL,
    price DECIMAL(15, 2) NOT NULL,
    currency TEXT DEFAULT 'CZK',
    date DATE NOT NULL,
    source TEXT, -- API source or manual
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(investment_id) REFERENCES investments(id) ON DELETE CASCADE,
    UNIQUE(investment_id, date)
);

-- Investment Accounts Table (separate investment accounts)
CREATE TABLE IF NOT EXISTS investment_accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    account_type TEXT NOT NULL, -- brokerage, retirement, taxable, etc.
    broker TEXT, -- broker name
    account_number TEXT,
    currency TEXT DEFAULT 'CZK',
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Categorization Rules Table (for auto-categorization)
CREATE TABLE IF NOT EXISTS categorization_rules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    category_id INTEGER NOT NULL,
    rule_type TEXT NOT NULL,
    pattern TEXT NOT NULL,
    is_regex INTEGER DEFAULT 0,
    priority INTEGER DEFAULT 100,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Financial Metrics (cached for performance)
CREATE TABLE IF NOT EXISTS financial_metrics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    month TEXT NOT NULL,
    total_income DECIMAL(15, 2) DEFAULT 0,
    total_expenses DECIMAL(15, 2) DEFAULT 0,
    total_savings DECIMAL(15, 2) DEFAULT 0,
    net_worth DECIMAL(15, 2) DEFAULT 0,
    calculated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id, month)
);

-- Notes/Tips Table (for financial education)
CREATE TABLE IF NOT EXISTS tips (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    category TEXT,
    tags TEXT,
    is_published INTEGER DEFAULT 1,
    priority INTEGER DEFAULT 100,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- AI Recommendations Table
CREATE TABLE IF NOT EXISTS ai_recommendations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    type TEXT NOT NULL,
    title TEXT NOT NULL,
    description TEXT NOT NULL,
    priority TEXT DEFAULT 'medium',
    is_dismissed INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Transaction Splits Table (for splitting transactions across multiple categories)
CREATE TABLE IF NOT EXISTS transaction_splits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    parent_transaction_id INTEGER NOT NULL,
    category_id INTEGER NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(parent_transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Recurring Transactions Table (for storing recurring transaction patterns)
CREATE TABLE IF NOT EXISTS recurring_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    description TEXT NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    frequency TEXT NOT NULL, -- daily, weekly, bi-weekly, monthly, quarterly, yearly
    account_id INTEGER NOT NULL,
    category_id INTEGER,
    type TEXT NOT NULL, -- income, expense
    next_due_date DATE NOT NULL,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY(category_id) REFERENCES categories(id)
);

-- Budget Templates Table (for predefined budget structures)
CREATE TABLE IF NOT EXISTS budget_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    template_type TEXT NOT NULL, -- student, single, family, retiree, minimalist, luxury
    is_default INTEGER DEFAULT 0, -- 1 for system default templates, 0 for user-created
    user_id INTEGER, -- NULL for system templates, user_id for user-created templates
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Budget Template Categories Table (categories within templates)
CREATE TABLE IF NOT EXISTS budget_template_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    template_id INTEGER NOT NULL,
    category_name TEXT NOT NULL,
    category_type TEXT NOT NULL, -- income, expense, savings
    suggested_percentage DECIMAL(5, 2), -- percentage of total income (e.g., 30.00 for 30%)
    suggested_amount DECIMAL(15, 2), -- fixed amount if not percentage-based
    is_required INTEGER DEFAULT 0, -- 1 if this category is required for the template
    priority INTEGER DEFAULT 100, -- for ordering categories
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(template_id) REFERENCES budget_templates(id) ON DELETE CASCADE
);

-- User Template Preferences Table (user preferences for templates)
CREATE TABLE IF NOT EXISTS user_template_preferences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    template_id INTEGER NOT NULL,
    preferred_income DECIMAL(15, 2), -- user's expected monthly income for calculations
    customizations TEXT, -- JSON string of custom category adjustments
    last_used_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(template_id) REFERENCES budget_templates(id) ON DELETE CASCADE,
    UNIQUE(user_id, template_id)
);

-- Budget Alerts Table (for budget monitoring and notifications)
CREATE TABLE IF NOT EXISTS budget_alerts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    budget_id INTEGER NOT NULL,
    alert_type TEXT NOT NULL, -- percentage_threshold, amount_threshold, time_based
    threshold_value DECIMAL(5, 2), -- percentage (e.g., 50.00) or amount depending on type
    current_value DECIMAL(15, 2), -- current spending amount or percentage
    status TEXT DEFAULT 'active', -- active, acknowledged, dismissed
    message TEXT NOT NULL,
    triggered_at DATETIME,
    acknowledged_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(budget_id) REFERENCES budgets(id) ON DELETE CASCADE
);

-- Create Indexes for Performance
CREATE INDEX IF NOT EXISTS idx_transactions_user_id ON transactions(user_id);
CREATE INDEX IF NOT EXISTS idx_transactions_account_id ON transactions(account_id);
CREATE INDEX IF NOT EXISTS idx_transactions_category_id ON transactions(category_id);
CREATE INDEX IF NOT EXISTS idx_transactions_date ON transactions(date);
CREATE INDEX IF NOT EXISTS idx_accounts_user_id ON accounts(user_id);
CREATE INDEX IF NOT EXISTS idx_categories_user_id ON categories(user_id);
CREATE INDEX IF NOT EXISTS idx_merchants_user_id ON merchants(user_id);
CREATE INDEX IF NOT EXISTS idx_budgets_user_id ON budgets(user_id);
CREATE INDEX IF NOT EXISTS idx_budgets_month ON budgets(month);
CREATE INDEX IF NOT EXISTS idx_investments_user_id ON investments(user_id);
CREATE INDEX IF NOT EXISTS idx_investments_account_id ON investments(account_id);
CREATE INDEX IF NOT EXISTS idx_investments_symbol ON investments(symbol);
CREATE INDEX IF NOT EXISTS idx_investments_asset_type ON investments(asset_type);
CREATE INDEX IF NOT EXISTS idx_investment_transactions_user_id ON investment_transactions(user_id);
CREATE INDEX IF NOT EXISTS idx_investment_transactions_investment_id ON investment_transactions(investment_id);
CREATE INDEX IF NOT EXISTS idx_investment_transactions_account_id ON investment_transactions(account_id);
CREATE INDEX IF NOT EXISTS idx_investment_transactions_type ON investment_transactions(transaction_type);
CREATE INDEX IF NOT EXISTS idx_investment_transactions_date ON investment_transactions(transaction_date);
CREATE INDEX IF NOT EXISTS idx_investment_prices_investment_id ON investment_prices(investment_id);
CREATE INDEX IF NOT EXISTS idx_investment_prices_date ON investment_prices(date);
CREATE INDEX IF NOT EXISTS idx_investment_accounts_user_id ON investment_accounts(user_id);
CREATE INDEX IF NOT EXISTS idx_financial_metrics_user_id ON financial_metrics(user_id);
CREATE INDEX IF NOT EXISTS idx_financial_metrics_month ON financial_metrics(month);
CREATE INDEX IF NOT EXISTS idx_transaction_splits_parent_transaction_id ON transaction_splits(parent_transaction_id);
CREATE INDEX IF NOT EXISTS idx_transaction_splits_category_id ON transaction_splits(category_id);
CREATE INDEX IF NOT EXISTS idx_recurring_transactions_user_id ON recurring_transactions(user_id);
CREATE INDEX IF NOT EXISTS idx_recurring_transactions_account_id ON recurring_transactions(account_id);
CREATE INDEX IF NOT EXISTS idx_recurring_transactions_category_id ON recurring_transactions(category_id);
CREATE INDEX IF NOT EXISTS idx_recurring_transactions_next_due_date ON recurring_transactions(next_due_date);

-- Indexes for Budget Templates
CREATE INDEX IF NOT EXISTS idx_budget_templates_type ON budget_templates(template_type);
CREATE INDEX IF NOT EXISTS idx_budget_templates_user_id ON budget_templates(user_id);
CREATE INDEX IF NOT EXISTS idx_budget_templates_is_default ON budget_templates(is_default);

-- Indexes for Budget Template Categories
CREATE INDEX IF NOT EXISTS idx_budget_template_categories_template_id ON budget_template_categories(template_id);
CREATE INDEX IF NOT EXISTS idx_budget_template_categories_type ON budget_template_categories(category_type);

-- Indexes for User Template Preferences
CREATE INDEX IF NOT EXISTS idx_user_template_preferences_user_id ON user_template_preferences(user_id);
CREATE INDEX IF NOT EXISTS idx_user_template_preferences_template_id ON user_template_preferences(template_id);

-- Indexes for Budget Alerts
CREATE INDEX IF NOT EXISTS idx_budget_alerts_user_id ON budget_alerts(user_id);
CREATE INDEX IF NOT EXISTS idx_budget_alerts_budget_id ON budget_alerts(budget_id);
CREATE INDEX IF NOT EXISTS idx_budget_alerts_status ON budget_alerts(status);
CREATE INDEX IF NOT EXISTS idx_budget_alerts_triggered_at ON budget_alerts(triggered_at);

-- Indexes for Goals
CREATE INDEX IF NOT EXISTS idx_goals_user_id ON goals(user_id);
CREATE INDEX IF NOT EXISTS idx_goals_goal_type ON goals(goal_type);
CREATE INDEX IF NOT EXISTS idx_goals_target_date ON goals(target_date);
CREATE INDEX IF NOT EXISTS idx_goals_is_active ON goals(is_active);

-- Indexes for Goal Milestones
CREATE INDEX IF NOT EXISTS idx_goal_milestones_goal_id ON goal_milestones(goal_id);
CREATE INDEX IF NOT EXISTS idx_goal_milestones_is_completed ON goal_milestones(is_completed);

-- Indexes for Goal Progress History
CREATE INDEX IF NOT EXISTS idx_goal_progress_history_goal_id ON goal_progress_history(goal_id);
CREATE INDEX IF NOT EXISTS idx_goal_progress_history_recorded_at ON goal_progress_history(recorded_at);

-- User Settings Table (for comprehensive user preferences and settings)
CREATE TABLE IF NOT EXISTS user_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    category TEXT NOT NULL, -- profile, notifications, preferences, security
    setting_key TEXT NOT NULL,
    setting_value TEXT,
    is_encrypted INTEGER DEFAULT 0, -- for sensitive data like passwords
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id, category, setting_key)
);

-- API Keys Table (for API authentication)
CREATE TABLE IF NOT EXISTS api_keys (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    api_key TEXT UNIQUE NOT NULL,
    permissions TEXT DEFAULT 'read', -- read, write, admin (comma-separated)
    rate_limit INTEGER DEFAULT 1000, -- requests per hour
    is_active INTEGER DEFAULT 1,
    last_used_at DATETIME,
    expires_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- API Rate Limiting Table
CREATE TABLE IF NOT EXISTS api_rate_limits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    api_key_id INTEGER NOT NULL,
    hour_window DATETIME NOT NULL, -- YYYY-MM-DD HH:00:00
    request_count INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(api_key_id) REFERENCES api_keys(id) ON DELETE CASCADE,
    UNIQUE(api_key_id, hour_window)
);

-- Indexes for User Settings
CREATE INDEX IF NOT EXISTS idx_user_settings_user_id ON user_settings(user_id);
CREATE INDEX IF NOT EXISTS idx_user_settings_category ON user_settings(category);
CREATE INDEX IF NOT EXISTS idx_user_settings_key ON user_settings(setting_key);

-- Indexes for API Keys
CREATE INDEX IF NOT EXISTS idx_api_keys_user_id ON api_keys(user_id);
CREATE INDEX IF NOT EXISTS idx_api_keys_api_key ON api_keys(api_key);
CREATE INDEX IF NOT EXISTS idx_api_keys_is_active ON api_keys(is_active);

-- Indexes for API Rate Limits
CREATE INDEX IF NOT EXISTS idx_api_rate_limits_api_key_id ON api_rate_limits(api_key_id);
-- LLM Cache Table (for caching LLM responses)
CREATE TABLE IF NOT EXISTS llm_cache (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cache_key TEXT UNIQUE NOT NULL,
    user_id INTEGER NOT NULL,
    prompt_type TEXT NOT NULL,
    response TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- LLM Rate Limits Table (for per-user rate limiting)
CREATE TABLE IF NOT EXISTS llm_rate_limits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    hour_count INTEGER DEFAULT 0,
    day_count INTEGER DEFAULT 0,
    last_reset DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id)
);

-- Crisis Mode Settings Table
CREATE TABLE IF NOT EXISTS crisis_mode_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    is_active INTEGER DEFAULT 0,
    activated_at DATETIME,
    thresholds TEXT, -- JSON: tightened thresholds for alerts
    notifications_enabled INTEGER DEFAULT 1,
    escalation_rules TEXT, -- JSON: notification escalation rules
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id)
);

-- AI Insight Panels Table (for dashboard widgets)
CREATE TABLE IF NOT EXISTS ai_insight_panels (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    panel_type TEXT NOT NULL, -- budget_health, savings_runway, debt_tracker, cash_flow, career_uplift
    title TEXT NOT NULL,
    content TEXT,
    priority TEXT DEFAULT 'medium', -- low, medium, high
    is_visible INTEGER DEFAULT 1,
    last_updated DATETIME,
    refresh_interval INTEGER DEFAULT 3600, -- seconds
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Indexes for LLM Cache
CREATE INDEX IF NOT EXISTS idx_llm_cache_user_id ON llm_cache(user_id);
CREATE INDEX IF NOT EXISTS idx_llm_cache_prompt_type ON llm_cache(prompt_type);
CREATE INDEX IF NOT EXISTS idx_llm_cache_created_at ON llm_cache(created_at);

-- Indexes for LLM Rate Limits
CREATE INDEX IF NOT EXISTS idx_llm_rate_limits_user_id ON llm_rate_limits(user_id);
CREATE INDEX IF NOT EXISTS idx_llm_rate_limits_last_reset ON llm_rate_limits(last_reset);

-- Indexes for Crisis Mode
CREATE INDEX IF NOT EXISTS idx_crisis_mode_settings_user_id ON crisis_mode_settings(user_id);
CREATE INDEX IF NOT EXISTS idx_crisis_mode_settings_is_active ON crisis_mode_settings(is_active);

-- Indexes for AI Insight Panels
CREATE INDEX IF NOT EXISTS idx_ai_insight_panels_user_id ON ai_insight_panels(user_id);
CREATE INDEX IF NOT EXISTS idx_ai_insight_panels_type ON ai_insight_panels(panel_type);
CREATE INDEX IF NOT EXISTS idx_ai_insight_panels_priority ON ai_insight_panels(priority);
CREATE INDEX IF NOT EXISTS idx_ai_insight_panels_visible ON ai_insight_panels(is_visible);
CREATE INDEX IF NOT EXISTS idx_api_rate_limits_hour_window ON api_rate_limits(hour_window);

-- Month 4: Automation & Scaling Features

-- Recommendation Feedback Table (for continuous improvement)
CREATE TABLE IF NOT EXISTS recommendation_feedback (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    recommendation_id INTEGER NOT NULL,
    feedback_type TEXT NOT NULL, -- 'helpful', 'not_helpful', 'implemented', 'dismissed'
    rating INTEGER, -- 1-5 scale for helpfulness
    comment TEXT,
    implemented_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(recommendation_id) REFERENCES ai_recommendations(id) ON DELETE CASCADE
);

-- Recommendation History Table (tracking all recommendations over time)
CREATE TABLE IF NOT EXISTS recommendation_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    recommendation_id INTEGER,
    type TEXT NOT NULL,
    title TEXT NOT NULL,
    description TEXT NOT NULL,
    priority TEXT DEFAULT 'medium',
    context_data TEXT, -- JSON: financial data used to generate recommendation
    prompt_version TEXT, -- version of prompt template used
    ai_model TEXT, -- AI model used (gpt-3.5-turbo, gpt-4, etc.)
    response_metadata TEXT, -- JSON: tokens used, response time, etc.
    is_implemented INTEGER DEFAULT 0,
    implemented_at DATETIME,
    feedback_count INTEGER DEFAULT 0,
    average_rating DECIMAL(3,2),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(recommendation_id) REFERENCES ai_recommendations(id)
);

-- Automated Actions Table (proactive automations)
CREATE TABLE IF NOT EXISTS automated_actions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    action_type TEXT NOT NULL, -- 'budget_generation', 'subscription_check', 'debt_reminder', 'benefit_lookup'
    trigger_type TEXT NOT NULL, -- 'scheduled', 'threshold', 'event'
    trigger_condition TEXT, -- JSON: conditions that trigger the action
    action_data TEXT, -- JSON: data needed to execute the action
    is_active INTEGER DEFAULT 1,
    last_executed_at DATETIME,
    next_execution_at DATETIME,
    execution_count INTEGER DEFAULT 0,
    success_count INTEGER DEFAULT 0,
    failure_count INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Job Market Feeds Table (for AI-enabled technician roles)
CREATE TABLE IF NOT EXISTS job_market_feeds (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    source_name TEXT NOT NULL, -- 'linkedin', 'indeed', 'glassdoor', 'rss_feed'
    source_url TEXT,
    feed_type TEXT NOT NULL, -- 'api', 'rss', 'scraping'
    api_key TEXT, -- encrypted
    last_fetched_at DATETIME,
    fetch_interval_minutes INTEGER DEFAULT 60,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Job Opportunities Table
CREATE TABLE IF NOT EXISTS job_opportunities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    feed_id INTEGER,
    external_id TEXT, -- unique ID from source
    title TEXT NOT NULL,
    company TEXT NOT NULL,
    location TEXT,
    salary_range TEXT, -- '50000-70000 EUR' or similar
    job_type TEXT, -- 'full-time', 'contract', 'remote'
    description TEXT,
    requirements TEXT,
    application_url TEXT,
    posted_date DATE,
    expires_date DATE,
    relevance_score DECIMAL(3,2), -- AI-calculated relevance to user
    is_saved INTEGER DEFAULT 0,
    is_applied INTEGER DEFAULT 0,
    applied_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(feed_id) REFERENCES job_market_feeds(id),
    UNIQUE(user_id, external_id)
);

-- Czech Benefits Lookup Table
CREATE TABLE IF NOT EXISTS czech_benefits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    benefit_type TEXT NOT NULL, -- 'unemployment', 'parental_leave', 'housing_allowance', 'hardship_fund'
    name TEXT NOT NULL,
    description TEXT NOT NULL,
    eligibility_criteria TEXT, -- JSON: requirements
    application_process TEXT,
    contact_info TEXT,
    website_url TEXT,
    is_active INTEGER DEFAULT 1,
    last_updated DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- User Benefit Applications Table
CREATE TABLE IF NOT EXISTS user_benefit_applications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    benefit_id INTEGER NOT NULL,
    application_status TEXT DEFAULT 'interested', -- 'interested', 'applied', 'approved', 'denied'
    application_date DATE,
    approval_date DATE,
    amount_received DECIMAL(15,2),
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(benefit_id) REFERENCES czech_benefits(id) ON DELETE CASCADE
);

-- Security Audit Log Table
CREATE TABLE IF NOT EXISTS security_audit_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action_type TEXT NOT NULL, -- 'login', 'logout', 'password_change', 'data_access', 'api_call'
    ip_address TEXT,
    user_agent TEXT,
    session_id TEXT,
    resource_accessed TEXT, -- what was accessed/modified
    action_details TEXT, -- JSON: additional context
    risk_level TEXT DEFAULT 'low', -- 'low', 'medium', 'high', 'critical'
    is_suspicious INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Performance Metrics Table (for optimization tracking)
CREATE TABLE IF NOT EXISTS performance_metrics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    metric_type TEXT NOT NULL, -- 'page_load', 'api_response', 'database_query', 'memory_usage'
    metric_name TEXT NOT NULL,
    value DECIMAL(10,4),
    unit TEXT, -- 'ms', 'bytes', 'seconds', etc.
    context_data TEXT, -- JSON: user_id, page, action, etc.
    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Usability Test Sessions Table
CREATE TABLE IF NOT EXISTS usability_test_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    session_type TEXT NOT NULL, -- 'a_b_test', 'user_journey', 'feature_test'
    test_name TEXT NOT NULL,
    variant TEXT, -- 'A', 'B', 'control' for A/B tests
    start_time DATETIME,
    end_time DATETIME,
    completion_status TEXT DEFAULT 'incomplete', -- 'complete', 'abandoned', 'error'
    user_feedback TEXT, -- JSON: ratings, comments
    interaction_data TEXT, -- JSON: clicks, time on page, errors
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Bank Import Jobs Table (for background processing)
CREATE TABLE IF NOT EXISTS bank_import_jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    job_id TEXT UNIQUE NOT NULL, -- UUID for tracking
    status TEXT DEFAULT 'pending', -- 'pending', 'processing', 'completed', 'failed'
    total_files INTEGER DEFAULT 0,
    processed_files INTEGER DEFAULT 0,
    imported_count INTEGER DEFAULT 0,
    skipped_count INTEGER DEFAULT 0,
    error_count INTEGER DEFAULT 0,
    results TEXT, -- JSON: file-by-file results
    error_message TEXT,
    started_at DATETIME,
    completed_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Indexes for Month 4 Tables
CREATE INDEX IF NOT EXISTS idx_recommendation_feedback_user_id ON recommendation_feedback(user_id);
CREATE INDEX IF NOT EXISTS idx_recommendation_feedback_recommendation_id ON recommendation_feedback(recommendation_id);
CREATE INDEX IF NOT EXISTS idx_recommendation_history_user_id ON recommendation_history(user_id);
CREATE INDEX IF NOT EXISTS idx_recommendation_history_type ON recommendation_history(type);
CREATE INDEX IF NOT EXISTS idx_recommendation_history_created_at ON recommendation_history(created_at);
CREATE INDEX IF NOT EXISTS idx_automated_actions_user_id ON automated_actions(user_id);
CREATE INDEX IF NOT EXISTS idx_automated_actions_type ON automated_actions(action_type);
CREATE INDEX IF NOT EXISTS idx_automated_actions_next_execution ON automated_actions(next_execution_at);
CREATE INDEX IF NOT EXISTS idx_job_opportunities_user_id ON job_opportunities(user_id);
CREATE INDEX IF NOT EXISTS idx_job_opportunities_relevance ON job_opportunities(relevance_score);
CREATE INDEX IF NOT EXISTS idx_job_opportunities_saved ON job_opportunities(is_saved);
CREATE INDEX IF NOT EXISTS idx_user_benefit_applications_user_id ON user_benefit_applications(user_id);
CREATE INDEX IF NOT EXISTS idx_user_benefit_applications_status ON user_benefit_applications(application_status);
CREATE INDEX IF NOT EXISTS idx_security_audit_log_user_id ON security_audit_log(user_id);
CREATE INDEX IF NOT EXISTS idx_security_audit_log_action_type ON security_audit_log(action_type);
CREATE INDEX IF NOT EXISTS idx_security_audit_log_created_at ON security_audit_log(created_at);
CREATE INDEX IF NOT EXISTS idx_performance_metrics_type ON performance_metrics(metric_type);
CREATE INDEX IF NOT EXISTS idx_performance_metrics_recorded_at ON performance_metrics(recorded_at);
CREATE INDEX IF NOT EXISTS idx_usability_test_sessions_user_id ON usability_test_sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_usability_test_sessions_type ON usability_test_sessions(session_type);
