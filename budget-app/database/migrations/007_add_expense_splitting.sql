-- Migration: Add Expense Splitting with Friends
-- Created: 2025-11-12
-- v1.1 Feature: Expense splitting and group management

-- Groups for shared expenses
CREATE TABLE IF NOT EXISTS expense_groups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    image_url TEXT,
    created_by INTEGER NOT NULL,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_expense_groups_created_by ON expense_groups(created_by);
CREATE INDEX idx_expense_groups_active ON expense_groups(is_active);

-- Group members
CREATE TABLE IF NOT EXISTS expense_group_members (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    group_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    role TEXT DEFAULT 'member',
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    left_at DATETIME,
    invited_by INTEGER,
    FOREIGN KEY(group_id) REFERENCES expense_groups(id) ON DELETE CASCADE,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(invited_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE(group_id, user_id)
);

CREATE INDEX idx_group_members_group ON expense_group_members(group_id);
CREATE INDEX idx_group_members_user ON expense_group_members(user_id);

-- Group invitations
CREATE TABLE IF NOT EXISTS expense_group_invitations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    group_id INTEGER NOT NULL,
    email TEXT NOT NULL,
    invited_by INTEGER NOT NULL,
    token TEXT NOT NULL UNIQUE,
    status TEXT DEFAULT 'pending',
    expires_at DATETIME NOT NULL,
    accepted_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(group_id) REFERENCES expense_groups(id) ON DELETE CASCADE,
    FOREIGN KEY(invited_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_group_invitations_token ON expense_group_invitations(token);
CREATE INDEX idx_group_invitations_email ON expense_group_invitations(email);

-- Split expenses
CREATE TABLE IF NOT EXISTS split_expenses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    group_id INTEGER NOT NULL,
    transaction_id INTEGER,
    paid_by INTEGER NOT NULL,
    total_amount REAL NOT NULL,
    currency TEXT DEFAULT 'CZK',
    split_type TEXT DEFAULT 'equal',
    description TEXT,
    notes TEXT,
    date DATE NOT NULL,
    category_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(group_id) REFERENCES expense_groups(id) ON DELETE CASCADE,
    FOREIGN KEY(transaction_id) REFERENCES transactions(id) ON DELETE SET NULL,
    FOREIGN KEY(paid_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE INDEX idx_split_expenses_group ON split_expenses(group_id);
CREATE INDEX idx_split_expenses_paid_by ON split_expenses(paid_by);
CREATE INDEX idx_split_expenses_date ON split_expenses(date DESC);

-- Individual splits
CREATE TABLE IF NOT EXISTS expense_splits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    split_expense_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    amount REAL NOT NULL,
    percentage REAL,
    shares INTEGER,
    is_settled INTEGER DEFAULT 0,
    settled_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(split_expense_id) REFERENCES split_expenses(id) ON DELETE CASCADE,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_expense_splits_expense ON expense_splits(split_expense_id);
CREATE INDEX idx_expense_splits_user ON expense_splits(user_id);
CREATE INDEX idx_expense_splits_settled ON expense_splits(is_settled);

-- Settlement transactions
CREATE TABLE IF NOT EXISTS settlements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    group_id INTEGER NOT NULL,
    from_user INTEGER NOT NULL,
    to_user INTEGER NOT NULL,
    amount REAL NOT NULL,
    currency TEXT DEFAULT 'CZK',
    status TEXT DEFAULT 'pending',
    payment_method TEXT,
    reference_number TEXT,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    settled_at DATETIME,
    FOREIGN KEY(group_id) REFERENCES expense_groups(id) ON DELETE CASCADE,
    FOREIGN KEY(from_user) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(to_user) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_settlements_group ON settlements(group_id);
CREATE INDEX idx_settlements_from_user ON settlements(from_user);
CREATE INDEX idx_settlements_to_user ON settlements(to_user);
CREATE INDEX idx_settlements_status ON settlements(status);

-- Group activity log
CREATE TABLE IF NOT EXISTS expense_group_activity (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    group_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    activity_type TEXT NOT NULL,
    description TEXT,
    metadata TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(group_id) REFERENCES expense_groups(id) ON DELETE CASCADE,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_group_activity_group ON expense_group_activity(group_id);
CREATE INDEX idx_group_activity_created ON expense_group_activity(created_at DESC);
