-- Migration 013: Household Foundation
-- Purpose: Create core household/family sharing structure
-- Features: Multi-user households, member management, invitations, settings

-- =============================================================================
-- HOUSEHOLDS TABLE
-- =============================================================================
-- Main household/family entity
CREATE TABLE IF NOT EXISTS households (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    created_by INTEGER NOT NULL,
    currency TEXT DEFAULT 'CZK',
    timezone TEXT DEFAULT 'Europe/Prague',
    avatar_url TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_households_created_by ON households(created_by);
CREATE INDEX idx_households_is_active ON households(is_active);

-- =============================================================================
-- HOUSEHOLD MEMBERS TABLE
-- =============================================================================
-- Links users to households with roles and permissions
CREATE TABLE IF NOT EXISTS household_members (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    household_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    role TEXT NOT NULL CHECK(role IN ('owner', 'partner', 'viewer', 'child')),
    permission_level INTEGER NOT NULL DEFAULT 50, -- 0-100 scale
    custom_permissions TEXT, -- JSON: {"can_view_reports": true, "can_create_budgets": false}
    display_name TEXT, -- Optional nickname within household
    is_active INTEGER DEFAULT 1,
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_activity_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(household_id, user_id)
);

CREATE INDEX idx_household_members_household ON household_members(household_id);
CREATE INDEX idx_household_members_user ON household_members(user_id);
CREATE INDEX idx_household_members_role ON household_members(role);
CREATE INDEX idx_household_members_is_active ON household_members(is_active);

-- =============================================================================
-- HOUSEHOLD INVITATIONS TABLE
-- =============================================================================
-- Invitation system for adding new members
CREATE TABLE IF NOT EXISTS household_invitations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    household_id INTEGER NOT NULL,
    invited_by INTEGER NOT NULL,
    invitee_email TEXT NOT NULL,
    invitee_user_id INTEGER, -- Set when user accepts
    role TEXT NOT NULL CHECK(role IN ('partner', 'viewer', 'child')),
    permission_level INTEGER NOT NULL DEFAULT 50,
    invitation_token TEXT NOT NULL UNIQUE,
    message TEXT,
    status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'accepted', 'declined', 'expired', 'cancelled')),
    expires_at DATETIME NOT NULL,
    accepted_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invitee_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_invitations_household ON household_invitations(household_id);
CREATE INDEX idx_invitations_email ON household_invitations(invitee_email);
CREATE INDEX idx_invitations_token ON household_invitations(invitation_token);
CREATE INDEX idx_invitations_status ON household_invitations(status);
CREATE INDEX idx_invitations_expires ON household_invitations(expires_at);

-- =============================================================================
-- HOUSEHOLD SETTINGS TABLE
-- =============================================================================
-- Configuration and preferences for each household
CREATE TABLE IF NOT EXISTS household_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    household_id INTEGER NOT NULL UNIQUE,

    -- Visibility settings
    default_visibility TEXT DEFAULT 'shared' CHECK(default_visibility IN ('private', 'shared')),
    allow_member_invites INTEGER DEFAULT 0, -- Can non-owners invite?
    require_approval_threshold REAL DEFAULT 1000.00, -- Transactions above this need approval

    -- Notification settings
    notify_new_transactions INTEGER DEFAULT 1,
    notify_budget_alerts INTEGER DEFAULT 1,
    notify_goal_milestones INTEGER DEFAULT 1,
    notify_member_changes INTEGER DEFAULT 1,

    -- Privacy settings
    allow_private_accounts INTEGER DEFAULT 1,
    allow_private_transactions INTEGER DEFAULT 1,
    show_member_activity INTEGER DEFAULT 1,

    -- Child account settings
    allow_child_accounts INTEGER DEFAULT 1,
    child_max_transaction REAL DEFAULT 50.00,
    child_requires_approval INTEGER DEFAULT 1,

    -- Other settings
    settings_json TEXT, -- Additional flexible settings

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE
);

CREATE INDEX idx_household_settings_household ON household_settings(household_id);

-- =============================================================================
-- PERMISSION ROLES REFERENCE
-- =============================================================================
-- owner (100): Full household control, can delete, manage all members
-- partner (75): Can manage shared finances, cannot delete household
-- viewer (50): Read-only access to shared data
-- child (25): Limited access with spending limits

-- =============================================================================
-- DATA MIGRATION: CREATE HOUSEHOLDS FOR EXISTING USERS
-- =============================================================================
-- Create single-member household for each existing user
INSERT INTO households (name, created_by, currency)
SELECT
    u.username || '''s Household',
    u.id,
    'CZK'
FROM users u
WHERE NOT EXISTS (
    SELECT 1 FROM household_members WHERE user_id = u.id
);

-- Add existing users as owners of their households
INSERT INTO household_members (household_id, user_id, role, permission_level)
SELECT
    h.id,
    h.created_by,
    'owner',
    100
FROM households h
WHERE NOT EXISTS (
    SELECT 1 FROM household_members WHERE household_id = h.id AND user_id = h.created_by
);

-- Create default settings for all households
INSERT INTO household_settings (household_id)
SELECT id FROM households
WHERE id NOT IN (SELECT household_id FROM household_settings);

-- =============================================================================
-- NOTES
-- =============================================================================
-- Security Rules:
-- 1. Always check permission_level before allowing operations
-- 2. Owners (100) can do everything
-- 3. Partners (75) can manage finances but not delete household
-- 4. Viewers (50) can only read
-- 5. Children (25) have spending limits and require approval
--
-- Privacy Rules:
-- 1. Private data is never shared with household
-- 2. Shared data is visible based on permission level
-- 3. All operations are logged in activity feed
