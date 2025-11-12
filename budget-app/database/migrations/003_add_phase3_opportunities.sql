-- Migration: Add Phase 3 Opportunities & Career Features
-- Created: 2025-11-12
-- Phase 3: Complete UI Layer - Opportunities, Scenario Planning, Saved Items

-- Opportunity Interactions Tracking
-- Tracks user interactions with job opportunities, courses, events, etc.
CREATE TABLE IF NOT EXISTS opportunity_interactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    opportunity_id TEXT NOT NULL, -- External opportunity identifier
    opportunity_type TEXT NOT NULL, -- 'job', 'learning', 'freelance', 'event', 'certification'
    interaction_type TEXT NOT NULL, -- 'view', 'click', 'apply', 'save', 'share'
    metadata TEXT, -- JSON for additional interaction data
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_opportunity_interactions_user_id ON opportunity_interactions(user_id);
CREATE INDEX idx_opportunity_interactions_opportunity_id ON opportunity_interactions(opportunity_id);
CREATE INDEX idx_opportunity_interactions_type ON opportunity_interactions(opportunity_type);
CREATE INDEX idx_opportunity_interactions_created_at ON opportunity_interactions(created_at);

-- Saved Opportunities
-- Allows users to save opportunities for later review
CREATE TABLE IF NOT EXISTS saved_opportunities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    opportunity_id TEXT NOT NULL, -- External opportunity identifier
    opportunity_type TEXT NOT NULL, -- 'learning', 'jobs', 'freelance', 'events', 'certifications'
    opportunity_data TEXT NOT NULL, -- JSON snapshot of the opportunity
    notes TEXT,
    tags TEXT, -- JSON array of user tags
    priority TEXT DEFAULT 'medium', -- 'low', 'medium', 'high'
    status TEXT DEFAULT 'active', -- 'active', 'applied', 'completed', 'expired', 'archived'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id, opportunity_id, opportunity_type)
);

CREATE INDEX idx_saved_opportunities_user_id ON saved_opportunities(user_id);
CREATE INDEX idx_saved_opportunities_type ON saved_opportunities(opportunity_type);
CREATE INDEX idx_saved_opportunities_status ON saved_opportunities(status);
CREATE INDEX idx_saved_opportunities_created_at ON saved_opportunities(created_at);

-- Learning Progress Tracking
-- Tracks user progress in educational content
CREATE TABLE IF NOT EXISTS learning_progress (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    content_id TEXT NOT NULL, -- Course/path identifier
    content_type TEXT NOT NULL, -- 'course', 'path', 'certification'
    progress_percentage INTEGER DEFAULT 0,
    time_spent_minutes INTEGER DEFAULT 0,
    status TEXT DEFAULT 'in_progress', -- 'not_started', 'in_progress', 'completed'
    last_accessed_at DATETIME,
    completed_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id, content_id, content_type)
);

CREATE INDEX idx_learning_progress_user_id ON learning_progress(user_id);
CREATE INDEX idx_learning_progress_status ON learning_progress(status);

-- Career Milestones
-- Tracks career progression and achievements
CREATE TABLE IF NOT EXISTS career_milestones (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    milestone_type TEXT NOT NULL, -- 'certification', 'promotion', 'salary_increase', 'skill_mastery'
    title TEXT NOT NULL,
    description TEXT,
    achievement_date DATE,
    income_impact REAL, -- Monetary impact if applicable
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_career_milestones_user_id ON career_milestones(user_id);
CREATE INDEX idx_career_milestones_type ON career_milestones(milestone_type);
CREATE INDEX idx_career_milestones_date ON career_milestones(achievement_date);

-- Scenario Planning Results
-- Stores financial scenario planning calculations for future reference
CREATE TABLE IF NOT EXISTS scenario_plans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    scenario_name TEXT NOT NULL,
    scenario_type TEXT NOT NULL, -- 'conservative', 'moderate', 'optimistic', 'crisis', 'custom'
    timeframe_months INTEGER NOT NULL,
    initial_balance REAL NOT NULL,
    projected_balance REAL NOT NULL,
    assumptions TEXT NOT NULL, -- JSON with scenario assumptions
    projections TEXT NOT NULL, -- JSON with month-by-month projections
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_scenario_plans_user_id ON scenario_plans(user_id);
CREATE INDEX idx_scenario_plans_type ON scenario_plans(scenario_type);
CREATE INDEX idx_scenario_plans_created_at ON scenario_plans(created_at);

-- Bookmarks for Tips & Educational Content
CREATE TABLE IF NOT EXISTS tip_bookmarks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    tip_id INTEGER NOT NULL,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(tip_id) REFERENCES tips(id) ON DELETE CASCADE,
    UNIQUE(user_id, tip_id)
);

CREATE INDEX idx_tip_bookmarks_user_id ON tip_bookmarks(user_id);

-- Financial Advisor Notes (for future advisor feature)
CREATE TABLE IF NOT EXISTS advisor_notes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    note_type TEXT NOT NULL, -- 'recommendation', 'observation', 'warning', 'celebration'
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    priority TEXT DEFAULT 'normal', -- 'low', 'normal', 'high', 'urgent'
    is_read INTEGER DEFAULT 0,
    action_required INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_advisor_notes_user_id ON advisor_notes(user_id);
CREATE INDEX idx_advisor_notes_is_read ON advisor_notes(is_read);
CREATE INDEX idx_advisor_notes_priority ON advisor_notes(priority);

-- Network/Mentor Connections (for future networking feature)
CREATE TABLE IF NOT EXISTS user_connections (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    connection_type TEXT NOT NULL, -- 'mentor', 'peer', 'advisor', 'accountability_partner'
    connection_name TEXT NOT NULL,
    connection_email TEXT,
    connection_profile TEXT, -- JSON with LinkedIn, etc.
    relationship_notes TEXT,
    status TEXT DEFAULT 'active', -- 'pending', 'active', 'inactive'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_user_connections_user_id ON user_connections(user_id);
CREATE INDEX idx_user_connections_type ON user_connections(connection_type);
CREATE INDEX idx_user_connections_status ON user_connections(status);
