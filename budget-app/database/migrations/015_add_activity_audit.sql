-- Migration 015: Activity & Audit
-- Purpose: Activity feed, audit logging, approval workflows, notifications
-- Features: Track all household activities, approval system, real-time notifications

-- =============================================================================
-- ACTIVITY FEED TABLE
-- =============================================================================
-- Comprehensive activity tracking for household
CREATE TABLE IF NOT EXISTS household_activities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    household_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL, -- Who performed the action
    activity_type TEXT NOT NULL, -- transaction_created, budget_updated, member_added, etc.
    entity_type TEXT NOT NULL, -- transaction, budget, goal, account, member
    entity_id INTEGER NOT NULL,
    action TEXT NOT NULL, -- created, updated, deleted, approved, rejected
    description TEXT NOT NULL,
    metadata_json TEXT, -- Additional data about the activity
    visibility TEXT DEFAULT 'all' CHECK(visibility IN ('all', 'admins_only', 'private')),
    is_important INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_activities_household ON household_activities(household_id);
CREATE INDEX idx_activities_user ON household_activities(user_id);
CREATE INDEX idx_activities_type ON household_activities(activity_type);
CREATE INDEX idx_activities_entity ON household_activities(entity_type, entity_id);
CREATE INDEX idx_activities_created ON household_activities(created_at DESC);
CREATE INDEX idx_activities_important ON household_activities(is_important);

-- =============================================================================
-- AUDIT LOG TABLE
-- =============================================================================
-- Security audit trail for sensitive operations
CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    household_id INTEGER,
    user_id INTEGER,
    action TEXT NOT NULL,
    resource_type TEXT NOT NULL,
    resource_id INTEGER,
    changes_json TEXT, -- Before/after values
    ip_address TEXT,
    user_agent TEXT,
    status TEXT DEFAULT 'success' CHECK(status IN ('success', 'failure', 'blocked')),
    failure_reason TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_audit_household ON audit_logs(household_id);
CREATE INDEX idx_audit_user ON audit_logs(user_id);
CREATE INDEX idx_audit_action ON audit_logs(action);
CREATE INDEX idx_audit_status ON audit_logs(status);
CREATE INDEX idx_audit_created ON audit_logs(created_at DESC);

-- =============================================================================
-- APPROVAL REQUESTS TABLE
-- =============================================================================
-- Workflow for transactions requiring approval
CREATE TABLE IF NOT EXISTS approval_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    household_id INTEGER NOT NULL,
    requested_by INTEGER NOT NULL,
    request_type TEXT NOT NULL, -- transaction, budget_change, goal_withdrawal, etc.
    entity_type TEXT NOT NULL,
    entity_id INTEGER NOT NULL,
    amount REAL,
    description TEXT NOT NULL,
    justification TEXT,
    status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'approved', 'rejected', 'cancelled')),
    reviewed_by INTEGER,
    reviewed_at DATETIME,
    review_notes TEXT,
    expires_at DATETIME,
    metadata_json TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_approvals_household ON approval_requests(household_id);
CREATE INDEX idx_approvals_requester ON approval_requests(requested_by);
CREATE INDEX idx_approvals_status ON approval_requests(status);
CREATE INDEX idx_approvals_reviewer ON approval_requests(reviewed_by);
CREATE INDEX idx_approvals_expires ON approval_requests(expires_at);

-- =============================================================================
-- NOTIFICATIONS TABLE
-- =============================================================================
-- Real-time notifications for household members
CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    household_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL, -- Recipient
    notification_type TEXT NOT NULL, -- activity, approval, invitation, alert, achievement
    title TEXT NOT NULL,
    message TEXT NOT NULL,
    priority TEXT DEFAULT 'normal' CHECK(priority IN ('low', 'normal', 'high', 'urgent')),
    action_url TEXT, -- Where to go when clicked
    action_label TEXT, -- Button text (e.g., "View Transaction", "Approve Request")
    icon TEXT, -- Icon name or emoji
    is_read INTEGER DEFAULT 0,
    is_archived INTEGER DEFAULT 0,
    read_at DATETIME,
    related_entity_type TEXT,
    related_entity_id INTEGER,
    metadata_json TEXT,
    expires_at DATETIME, -- Auto-archive after this date
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_notifications_household ON notifications(household_id);
CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_type ON notifications(notification_type);
CREATE INDEX idx_notifications_read ON notifications(is_read);
CREATE INDEX idx_notifications_priority ON notifications(priority);
CREATE INDEX idx_notifications_created ON notifications(created_at DESC);

-- =============================================================================
-- NOTIFICATION PREFERENCES TABLE
-- =============================================================================
-- User preferences for notifications
CREATE TABLE IF NOT EXISTS notification_preferences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    household_id INTEGER,

    -- In-app notifications
    notify_transactions INTEGER DEFAULT 1,
    notify_budgets INTEGER DEFAULT 1,
    notify_goals INTEGER DEFAULT 1,
    notify_approvals INTEGER DEFAULT 1,
    notify_members INTEGER DEFAULT 1,
    notify_bills INTEGER DEFAULT 1,
    notify_investments INTEGER DEFAULT 1,

    -- Email notifications
    email_daily_summary INTEGER DEFAULT 1,
    email_weekly_report INTEGER DEFAULT 1,
    email_approvals INTEGER DEFAULT 1,
    email_alerts INTEGER DEFAULT 1,
    email_invitations INTEGER DEFAULT 1,

    -- Frequency
    activity_digest TEXT DEFAULT 'realtime' CHECK(activity_digest IN ('realtime', 'hourly', 'daily', 'never')),
    quiet_hours_start TEXT, -- "22:00"
    quiet_hours_end TEXT, -- "07:00"

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    UNIQUE(user_id, household_id)
);

CREATE INDEX idx_notif_prefs_user ON notification_preferences(user_id);
CREATE INDEX idx_notif_prefs_household ON notification_preferences(household_id);

-- =============================================================================
-- ACHIEVEMENTS TABLE (Gamification)
-- =============================================================================
-- Track milestones and achievements
CREATE TABLE IF NOT EXISTS achievements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    household_id INTEGER,
    user_id INTEGER NOT NULL,
    achievement_type TEXT NOT NULL, -- savings_streak, budget_master, goal_reached, etc.
    title TEXT NOT NULL,
    description TEXT,
    icon TEXT,
    points INTEGER DEFAULT 0,
    unlocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    metadata_json TEXT,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_achievements_household ON achievements(household_id);
CREATE INDEX idx_achievements_user ON achievements(user_id);
CREATE INDEX idx_achievements_type ON achievements(achievement_type);

-- =============================================================================
-- CREATE DEFAULT NOTIFICATION PREFERENCES
-- =============================================================================
-- Create preferences for all existing users
INSERT INTO notification_preferences (user_id)
SELECT id FROM users
WHERE id NOT IN (SELECT user_id FROM notification_preferences WHERE household_id IS NULL);

-- =============================================================================
-- NOTES
-- =============================================================================
-- Activity Types:
-- - transaction_created, transaction_updated, transaction_deleted
-- - budget_created, budget_updated, budget_exceeded
-- - goal_created, goal_updated, goal_completed
-- - member_invited, member_joined, member_left
-- - account_created, account_updated
-- - approval_requested, approval_granted, approval_denied
--
-- Notification Priorities:
-- - low: Informational updates
-- - normal: Standard activity notifications
-- - high: Important alerts (budget exceeded, bill due)
-- - urgent: Critical actions needed (approval requests)
--
-- Audit Log Actions:
-- - login, logout, permission_change, data_export
-- - household_created, household_deleted, member_removed
-- - sensitive_data_access, bulk_delete
