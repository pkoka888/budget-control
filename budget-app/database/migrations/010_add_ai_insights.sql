-- Migration 010: Smart Financial Insights with AI
-- Adds AI-powered financial analysis, predictions, and recommendations

-- AI insights (generated insights and recommendations)
CREATE TABLE IF NOT EXISTS ai_insights (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    insight_type TEXT NOT NULL, -- spending_pattern, saving_opportunity, budget_alert, investment_suggestion, anomaly
    category TEXT, -- category related to insight
    title TEXT NOT NULL,
    description TEXT NOT NULL,
    severity TEXT DEFAULT 'info', -- info, warning, critical, positive
    confidence_score REAL, -- 0.0 to 1.0
    data_json TEXT, -- JSON with supporting data
    is_read INTEGER DEFAULT 0,
    is_dismissed INTEGER DEFAULT 0,
    is_actioned INTEGER DEFAULT 0,
    action_taken TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_insights_user_type ON ai_insights(user_id, insight_type);
CREATE INDEX idx_insights_created ON ai_insights(created_at);

-- Spending patterns (ML-detected patterns)
CREATE TABLE IF NOT EXISTS spending_patterns (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    pattern_type TEXT NOT NULL, -- recurring, seasonal, unusual, trending_up, trending_down
    category TEXT,
    merchant TEXT,
    pattern_description TEXT NOT NULL,
    average_amount REAL,
    frequency TEXT, -- daily, weekly, monthly, quarterly
    last_occurrence DATE,
    next_predicted DATE,
    confidence REAL,
    detected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Financial predictions (future spending, income, savings)
CREATE TABLE IF NOT EXISTS financial_predictions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    prediction_type TEXT NOT NULL, -- spending, income, savings, net_worth
    category TEXT,
    prediction_date DATE NOT NULL,
    predicted_amount REAL NOT NULL,
    confidence_interval_low REAL,
    confidence_interval_high REAL,
    actual_amount REAL,
    accuracy_score REAL,
    model_version TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_predictions_user_date ON financial_predictions(user_id, prediction_date);

-- Savings opportunities (AI-detected ways to save money)
CREATE TABLE IF NOT EXISTS savings_opportunities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    opportunity_type TEXT NOT NULL, -- subscription, recurring_expense, switch_provider, negotiate
    category TEXT,
    title TEXT NOT NULL,
    description TEXT NOT NULL,
    potential_savings_monthly REAL,
    potential_savings_annual REAL,
    effort_level TEXT DEFAULT 'medium', -- low, medium, high
    priority_score REAL,
    status TEXT DEFAULT 'open', -- open, in_progress, completed, dismissed
    data_json TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Budget recommendations (AI-suggested budget adjustments)
CREATE TABLE IF NOT EXISTS budget_recommendations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    category TEXT NOT NULL,
    current_budget REAL,
    recommended_budget REAL,
    reasoning TEXT NOT NULL,
    based_on_months INTEGER DEFAULT 3,
    confidence REAL,
    is_applied INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    applied_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Anomaly detection (unusual transactions)
CREATE TABLE IF NOT EXISTS transaction_anomalies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    transaction_id INTEGER NOT NULL,
    anomaly_type TEXT NOT NULL, -- unusual_amount, unusual_merchant, unusual_time, unusual_location
    anomaly_score REAL NOT NULL, -- 0.0 to 1.0, higher = more unusual
    expected_range_min REAL,
    expected_range_max REAL,
    description TEXT,
    is_reviewed INTEGER DEFAULT 0,
    is_legitimate INTEGER,
    reviewed_at DATETIME,
    detected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE
);

CREATE INDEX idx_anomalies_score ON transaction_anomalies(anomaly_score DESC);

-- Financial goals predictions (goal achievement likelihood)
CREATE TABLE IF NOT EXISTS goal_predictions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    goal_id INTEGER NOT NULL,
    predicted_completion_date DATE,
    probability_of_success REAL, -- 0.0 to 1.0
    suggested_monthly_amount REAL,
    predicted_shortfall REAL,
    reasoning TEXT,
    model_version TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE
);

-- ML model metadata
CREATE TABLE IF NOT EXISTS ml_models (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    model_name TEXT NOT NULL UNIQUE,
    model_type TEXT NOT NULL, -- regression, classification, clustering, time_series
    version TEXT NOT NULL,
    accuracy REAL,
    last_trained DATETIME,
    training_data_size INTEGER,
    parameters_json TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- User feedback on insights (for model improvement)
CREATE TABLE IF NOT EXISTS insight_feedback (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    insight_id INTEGER NOT NULL,
    feedback_type TEXT NOT NULL, -- helpful, not_helpful, incorrect, spam
    rating INTEGER, -- 1-5
    comment TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (insight_id) REFERENCES ai_insights(id) ON DELETE CASCADE
);

-- AI chat history (conversational AI assistant)
CREATE TABLE IF NOT EXISTS ai_chat_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    session_id TEXT NOT NULL,
    role TEXT NOT NULL, -- user, assistant
    message TEXT NOT NULL,
    context_json TEXT, -- Financial data context
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_chat_session ON ai_chat_history(session_id);

-- Insert sample ML models
INSERT OR IGNORE INTO ml_models (model_name, model_type, version, is_active) VALUES
('spending_predictor', 'time_series', '1.0', 1),
('anomaly_detector', 'classification', '1.0', 1),
('budget_optimizer', 'regression', '1.0', 1),
('savings_finder', 'clustering', '1.0', 1);
