-- Migration: Add Performance Optimization Indexes
-- Created: 2025-11-12
-- Description: Additional indexes for frequently queried columns to improve performance

-- Composite indexes for common query patterns
-- These indexes support queries that filter by multiple columns

-- Transactions: user_id + date (for date-range queries per user)
CREATE INDEX IF NOT EXISTS idx_transactions_user_date ON transactions(user_id, date);

-- Transactions: user_id + type (for filtering by transaction type)
CREATE INDEX IF NOT EXISTS idx_transactions_user_type ON transactions(user_id, type);

-- Transactions: account_id + date (for account statements)
CREATE INDEX IF NOT EXISTS idx_transactions_account_date ON transactions(account_id, date);

-- Transactions: category_id + date (for category analysis)
CREATE INDEX IF NOT EXISTS idx_transactions_category_date ON transactions(category_id, date);

-- Budgets: user_id + period (for retrieving budgets by period)
CREATE INDEX IF NOT EXISTS idx_budgets_user_period ON budgets(user_id, period);

-- Budgets: user_id + is_active (for active budgets)
CREATE INDEX IF NOT EXISTS idx_budgets_user_active ON budgets(user_id, is_active);

-- Goals: user_id + is_active (for active goals)
CREATE INDEX IF NOT EXISTS idx_goals_user_active ON goals(user_id, is_active);

-- Goals: user_id + goal_type (for goal filtering)
CREATE INDEX IF NOT EXISTS idx_goals_user_type ON goals(user_id, goal_type);

-- Recurring Transactions: user_id + is_active (for active recurring)
CREATE INDEX IF NOT EXISTS idx_recurring_user_active ON recurring_transactions(user_id, is_active);

-- Recurring Transactions: is_active + next_date (for processing due transactions)
CREATE INDEX IF NOT EXISTS idx_recurring_active_next_date ON recurring_transactions(is_active, next_date);

-- Investments: user_id + account_id (for portfolio queries)
CREATE INDEX IF NOT EXISTS idx_investments_user_account ON investments(user_id, account_id);

-- Investments: symbol + user_id (for tracking specific holdings)
CREATE INDEX IF NOT EXISTS idx_investments_symbol_user ON investments(symbol, user_id);

-- Budget Alerts: user_id + acknowledged (for unacknowledged alerts)
CREATE INDEX IF NOT EXISTS idx_budget_alerts_user_ack ON budget_alerts(user_id, acknowledged);

-- Notifications: user_id + is_read (for unread notifications)
CREATE INDEX IF NOT EXISTS idx_notifications_user_read ON notifications(user_id, is_read);

-- Notifications: user_id + created_at (for recent notifications)
CREATE INDEX IF NOT EXISTS idx_notifications_user_created ON notifications(user_id, created_at DESC);

-- Security Audit Log: user_id + created_at (for user activity timeline)
CREATE INDEX IF NOT EXISTS idx_security_audit_user_created ON security_audit_log(user_id, created_at DESC);

-- Security Audit Log: event_type + created_at (for event analysis)
CREATE INDEX IF NOT EXISTS idx_security_audit_event_created ON security_audit_log(event_type, created_at DESC);

-- AI Recommendations: user_id + status (for active recommendations)
CREATE INDEX IF NOT EXISTS idx_ai_recommendations_user_status ON ai_recommendations(user_id, status);

-- API Keys: user_id + is_active (for active API keys)
CREATE INDEX IF NOT EXISTS idx_api_keys_user_active ON api_keys(user_id, is_active);

-- Investment Transactions: investment_id + transaction_date (for transaction history)
CREATE INDEX IF NOT EXISTS idx_investment_txn_investment_date ON investment_transactions(investment_id, transaction_date DESC);

-- Goal Progress History: goal_id + created_at (for progress tracking)
CREATE INDEX IF NOT EXISTS idx_goal_progress_goal_created ON goal_progress_history(goal_id, created_at DESC);

-- CSV Imports: user_id + status (for import monitoring)
CREATE INDEX IF NOT EXISTS idx_csv_imports_user_status ON csv_imports(user_id, status);

-- Bank Import Jobs: user_id + status (for job monitoring)
CREATE INDEX IF NOT EXISTS idx_bank_import_user_status ON bank_import_jobs(user_id, status);

-- Categorization Rules: user_id + is_active (for active rules)
CREATE INDEX IF NOT EXISTS idx_categorization_rules_user_active ON categorization_rules(user_id, is_active);

-- Categorization Rules: user_id + priority (for rule evaluation order)
CREATE INDEX IF NOT EXISTS idx_categorization_rules_user_priority ON categorization_rules(user_id, priority DESC);

-- Automated Actions: user_id + is_active (for active automations)
CREATE INDEX IF NOT EXISTS idx_automated_actions_user_active ON automated_actions(user_id, is_active);

-- User Settings: user_id + category (for settings retrieval)
CREATE INDEX IF NOT EXISTS idx_user_settings_user_category ON user_settings(user_id, category);

-- Investment Accounts: user_id + is_active (for active accounts)
CREATE INDEX IF NOT EXISTS idx_investment_accounts_user_active ON investment_accounts(user_id, is_active);

-- Saved Opportunities: user_id + status (for active saved items)
CREATE INDEX IF NOT EXISTS idx_saved_opps_user_status ON saved_opportunities(user_id, status);

-- Opportunity Interactions: user_id + opportunity_type (for interaction analysis)
CREATE INDEX IF NOT EXISTS idx_opp_interactions_user_type ON opportunity_interactions(user_id, opportunity_type);

-- Learning Progress: user_id + status (for active learning)
CREATE INDEX IF NOT EXISTS idx_learning_progress_user_status ON learning_progress(user_id, status);

-- Scenario Plans: user_id + scenario_type (for scenario retrieval)
CREATE INDEX IF NOT EXISTS idx_scenario_plans_user_type ON scenario_plans(user_id, scenario_type);

-- Advisor Notes: user_id + is_read + priority (for important unread notes)
CREATE INDEX IF NOT EXISTS idx_advisor_notes_user_read_priority ON advisor_notes(user_id, is_read, priority DESC);

-- Performance Metrics: metric_type + created_at (for performance analysis)
CREATE INDEX IF NOT EXISTS idx_performance_metrics_type_created ON performance_metrics(metric_type, created_at DESC);

-- Financial Metrics: user_id + metric_date (for trend analysis)
CREATE INDEX IF NOT EXISTS idx_financial_metrics_user_date ON financial_metrics(user_id, metric_date DESC);

-- User Benefit Applications: user_id + status (for application tracking)
CREATE INDEX IF NOT EXISTS idx_user_benefits_user_status ON user_benefit_applications(user_id, status);

-- Budget Template Categories: template_id + category_id (for template application)
CREATE INDEX IF NOT EXISTS idx_budget_template_cats_template ON budget_template_categories(template_id, category_id);

-- Transaction Splits: transaction_id + category_id (for split analysis)
CREATE INDEX IF NOT EXISTS idx_transaction_splits_txn_cat ON transaction_splits(transaction_id, category_id);

-- Two-Factor Sessions: user_id + expires_at (for valid sessions)
CREATE INDEX IF NOT EXISTS idx_2fa_sessions_user_expires ON two_factor_sessions(user_id, expires_at);

-- Email Verification Tokens: user_id + expires_at (for valid tokens)
CREATE INDEX IF NOT EXISTS idx_email_verification_user_expires ON email_verification_tokens(user_id, expires_at);

-- Password Resets: user_id + expires_at (for valid resets)
CREATE INDEX IF NOT EXISTS idx_password_resets_user_expires ON password_resets(user_id, expires_at);

-- LLM Cache: expires_at (for cache cleanup)
CREATE INDEX IF NOT EXISTS idx_llm_cache_cleanup ON llm_cache(expires_at);

-- Merchants: name (for merchant lookup - case insensitive)
-- Note: SQLite doesn't have native case-insensitive indexes, handled in queries with COLLATE NOCASE

-- ANALYZE statement to update SQLite query planner statistics
-- This helps SQLite choose the best query plans with these new indexes
ANALYZE;
