-- Migration: Add Multi-Currency Support
-- Created: 2025-11-12
-- v1.1 Feature: Multi-currency enhancement

-- Add currency to transactions
ALTER TABLE transactions ADD COLUMN currency TEXT DEFAULT 'CZK';
ALTER TABLE transactions ADD COLUMN exchange_rate REAL DEFAULT 1.0;
ALTER TABLE transactions ADD COLUMN original_amount REAL;
ALTER TABLE transactions ADD COLUMN original_currency TEXT;

-- Add currency to accounts
ALTER TABLE accounts ADD COLUMN currency TEXT DEFAULT 'CZK';

-- Add currency to budgets
ALTER TABLE budgets ADD COLUMN currency TEXT DEFAULT 'CZK';

-- Add currency to goals
ALTER TABLE goals ADD COLUMN currency TEXT DEFAULT 'CZK';

-- Add currency to investments
ALTER TABLE investments ADD COLUMN currency TEXT DEFAULT 'CZK';

-- Exchange rates table
CREATE TABLE IF NOT EXISTS exchange_rates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    from_currency TEXT NOT NULL,
    to_currency TEXT NOT NULL,
    rate REAL NOT NULL,
    fetched_at DATETIME NOT NULL,
    source TEXT DEFAULT 'api',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(from_currency, to_currency, fetched_at)
);

CREATE INDEX idx_exchange_rates_currencies ON exchange_rates(from_currency, to_currency);
CREATE INDEX idx_exchange_rates_date ON exchange_rates(fetched_at DESC);

-- User currency preferences
CREATE TABLE IF NOT EXISTS user_currency_preferences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    base_currency TEXT DEFAULT 'CZK',
    display_currencies TEXT,
    auto_convert INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_user_currency_user ON user_currency_preferences(user_id);

-- Supported currencies reference
CREATE TABLE IF NOT EXISTS supported_currencies (
    code TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    symbol TEXT NOT NULL,
    decimal_places INTEGER DEFAULT 2,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insert common currencies
INSERT OR IGNORE INTO supported_currencies (code, name, symbol) VALUES
('CZK', 'Czech Koruna', 'Kč'),
('EUR', 'Euro', '€'),
('USD', 'US Dollar', '$'),
('GBP', 'British Pound', '£'),
('PLN', 'Polish Złoty', 'zł'),
('CHF', 'Swiss Franc', 'CHF'),
('JPY', 'Japanese Yen', '¥'),
('CAD', 'Canadian Dollar', 'CA$'),
('AUD', 'Australian Dollar', 'A$'),
('CNY', 'Chinese Yuan', '¥'),
('SEK', 'Swedish Krona', 'kr'),
('NOK', 'Norwegian Krone', 'kr'),
('DKK', 'Danish Krone', 'kr'),
('HUF', 'Hungarian Forint', 'Ft'),
('RON', 'Romanian Leu', 'lei'),
('RUB', 'Russian Ruble', '₽'),
('BRL', 'Brazilian Real', 'R$'),
('INR', 'Indian Rupee', '₹'),
('MXN', 'Mexican Peso', '$'),
('ZAR', 'South African Rand', 'R');

-- Exchange rate history for reporting
CREATE TABLE IF NOT EXISTS exchange_rate_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    from_currency TEXT NOT NULL,
    to_currency TEXT NOT NULL,
    rate REAL NOT NULL,
    date DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_exchange_history_currencies ON exchange_rate_history(from_currency, to_currency, date);
