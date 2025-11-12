-- Migration 011: Bill Payment Automation
-- Adds automated bill tracking, payment scheduling, and reminders

-- Bill providers/payees
CREATE TABLE IF NOT EXISTS bill_providers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    category TEXT, -- utilities, internet, phone, insurance, subscription, etc.
    logo_url TEXT,
    website TEXT,
    phone TEXT,
    email TEXT,
    account_number TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Recurring bills
CREATE TABLE IF NOT EXISTS recurring_bills (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    provider_id INTEGER,
    name TEXT NOT NULL,
    category TEXT NOT NULL,
    amount REAL,
    amount_type TEXT DEFAULT 'fixed', -- fixed, variable, estimated
    currency TEXT DEFAULT 'CZK',
    frequency TEXT NOT NULL, -- weekly, monthly, quarterly, annually
    next_due_date DATE NOT NULL,
    last_due_date DATE,
    auto_pay_enabled INTEGER DEFAULT 0,
    auto_pay_account_id INTEGER,
    payment_method TEXT, -- bank_transfer, card, direct_debit, cash
    reminder_days_before INTEGER DEFAULT 3,
    is_active INTEGER DEFAULT 1,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES bill_providers(id) ON DELETE SET NULL,
    FOREIGN KEY (auto_pay_account_id) REFERENCES accounts(id) ON DELETE SET NULL
);

CREATE INDEX idx_bills_due_date ON recurring_bills(next_due_date);
CREATE INDEX idx_bills_user ON recurring_bills(user_id);

-- Bill payment history
CREATE TABLE IF NOT EXISTS bill_payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    recurring_bill_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    amount REAL NOT NULL,
    currency TEXT DEFAULT 'CZK',
    due_date DATE NOT NULL,
    paid_date DATE,
    payment_method TEXT,
    account_id INTEGER,
    transaction_id INTEGER,
    status TEXT DEFAULT 'pending', -- pending, paid, overdue, scheduled, failed
    confirmation_number TEXT,
    late_fee REAL DEFAULT 0,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recurring_bill_id) REFERENCES recurring_bills(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE SET NULL,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE SET NULL
);

CREATE INDEX idx_payments_status ON bill_payments(status);
CREATE INDEX idx_payments_due_date ON bill_payments(due_date);

-- Payment reminders
CREATE TABLE IF NOT EXISTS bill_reminders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    bill_payment_id INTEGER NOT NULL,
    reminder_date DATE NOT NULL,
    reminder_time TIME DEFAULT '09:00:00',
    notification_method TEXT DEFAULT 'email', -- email, sms, push, in_app
    is_sent INTEGER DEFAULT 0,
    sent_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (bill_payment_id) REFERENCES bill_payments(id) ON DELETE CASCADE
);

CREATE INDEX idx_reminders_date ON bill_reminders(reminder_date);

-- Payment rules (automation rules)
CREATE TABLE IF NOT EXISTS payment_rules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    rule_type TEXT NOT NULL, -- auto_categorize, auto_pay, auto_split
    conditions_json TEXT NOT NULL, -- JSON conditions
    actions_json TEXT NOT NULL, -- JSON actions to take
    priority INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    execution_count INTEGER DEFAULT 0,
    last_executed DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Bill templates (for creating new bills quickly)
CREATE TABLE IF NOT EXISTS bill_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    category TEXT NOT NULL,
    default_amount REAL,
    default_frequency TEXT,
    default_payment_method TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Bill attachments (invoices, receipts)
CREATE TABLE IF NOT EXISTS bill_attachments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    bill_payment_id INTEGER NOT NULL,
    file_name TEXT NOT NULL,
    file_path TEXT NOT NULL,
    file_type TEXT,
    file_size INTEGER,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bill_payment_id) REFERENCES bill_payments(id) ON DELETE CASCADE
);

-- Bill analytics (spending by provider over time)
CREATE TABLE IF NOT EXISTS bill_analytics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    provider_id INTEGER,
    month DATE NOT NULL,
    total_amount REAL NOT NULL,
    payment_count INTEGER NOT NULL,
    on_time_count INTEGER DEFAULT 0,
    late_count INTEGER DEFAULT 0,
    average_amount REAL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES bill_providers(id) ON DELETE SET NULL,
    UNIQUE(user_id, provider_id, month)
);

-- Subscription tracking (special type of recurring bills)
CREATE TABLE IF NOT EXISTS subscriptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    recurring_bill_id INTEGER,
    service_name TEXT NOT NULL,
    category TEXT, -- streaming, software, news, fitness, etc.
    amount REAL NOT NULL,
    currency TEXT DEFAULT 'CZK',
    billing_cycle TEXT NOT NULL, -- monthly, annually
    start_date DATE NOT NULL,
    renewal_date DATE NOT NULL,
    cancellation_date DATE,
    auto_renew INTEGER DEFAULT 1,
    trial_end_date DATE,
    is_trial INTEGER DEFAULT 0,
    usage_tracking INTEGER DEFAULT 0, -- track if user is actually using it
    last_used DATE,
    value_rating INTEGER, -- 1-5, user's perceived value
    status TEXT DEFAULT 'active', -- active, paused, cancelled
    cancellation_url TEXT,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recurring_bill_id) REFERENCES recurring_bills(id) ON DELETE SET NULL
);

-- Payment methods configuration
CREATE TABLE IF NOT EXISTS payment_methods (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    method_type TEXT NOT NULL, -- bank_account, credit_card, debit_card, paypal, etc.
    name TEXT NOT NULL,
    last_four TEXT,
    account_id INTEGER,
    is_default INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE SET NULL
);

-- Insert common bill categories
INSERT OR IGNORE INTO bill_templates (user_id, name, category, default_frequency)
SELECT 1, 'Electric Bill', 'utilities', 'monthly' WHERE EXISTS (SELECT 1 FROM users WHERE id = 1);
INSERT OR IGNORE INTO bill_templates (user_id, name, category, default_frequency)
SELECT 1, 'Internet', 'utilities', 'monthly' WHERE EXISTS (SELECT 1 FROM users WHERE id = 1);
INSERT OR IGNORE INTO bill_templates (user_id, name, category, default_frequency)
SELECT 1, 'Phone', 'utilities', 'monthly' WHERE EXISTS (SELECT 1 FROM users WHERE id = 1);
INSERT OR IGNORE INTO bill_templates (user_id, name, category, default_frequency)
SELECT 1, 'Rent/Mortgage', 'housing', 'monthly' WHERE EXISTS (SELECT 1 FROM users WHERE id = 1);
INSERT OR IGNORE INTO bill_templates (user_id, name, category, default_frequency)
SELECT 1, 'Insurance', 'insurance', 'monthly' WHERE EXISTS (SELECT 1 FROM users WHERE id = 1);
