-- Migration 009: Investment Portfolio Tracker
-- Adds comprehensive investment tracking with real-time portfolio management

-- Investment accounts (stocks, crypto, bonds, mutual funds, ETFs)
CREATE TABLE IF NOT EXISTS investment_accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    type TEXT NOT NULL, -- stock, crypto, bond, mutual_fund, etf, commodity
    broker TEXT,
    account_number TEXT,
    balance REAL DEFAULT 0,
    currency TEXT DEFAULT 'CZK',
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Holdings (individual positions)
CREATE TABLE IF NOT EXISTS investment_holdings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    investment_account_id INTEGER NOT NULL,
    symbol TEXT NOT NULL, -- AAPL, BTC, etc.
    name TEXT NOT NULL,
    type TEXT NOT NULL, -- stock, crypto, bond, etf, mutual_fund
    quantity REAL NOT NULL,
    average_buy_price REAL NOT NULL,
    current_price REAL,
    currency TEXT DEFAULT 'USD',
    exchange TEXT, -- NASDAQ, NYSE, Binance, etc.
    sector TEXT, -- Technology, Finance, Healthcare, etc.
    last_price_update DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (investment_account_id) REFERENCES investment_accounts(id) ON DELETE CASCADE
);

CREATE INDEX idx_holdings_symbol ON investment_holdings(symbol);
CREATE INDEX idx_holdings_account ON investment_holdings(investment_account_id);

-- Transactions (buy, sell, dividend, split, etc.)
CREATE TABLE IF NOT EXISTS investment_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    investment_account_id INTEGER NOT NULL,
    holding_id INTEGER,
    type TEXT NOT NULL, -- buy, sell, dividend, interest, split, fee
    symbol TEXT NOT NULL,
    quantity REAL,
    price REAL,
    total_amount REAL NOT NULL,
    currency TEXT DEFAULT 'USD',
    fees REAL DEFAULT 0,
    tax REAL DEFAULT 0,
    transaction_date DATE NOT NULL,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (investment_account_id) REFERENCES investment_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (holding_id) REFERENCES investment_holdings(id) ON DELETE SET NULL
);

CREATE INDEX idx_inv_transactions_date ON investment_transactions(transaction_date);
CREATE INDEX idx_inv_transactions_symbol ON investment_transactions(symbol);

-- Price history cache
CREATE TABLE IF NOT EXISTS investment_price_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    symbol TEXT NOT NULL,
    price REAL NOT NULL,
    currency TEXT DEFAULT 'USD',
    date DATE NOT NULL,
    open REAL,
    high REAL,
    low REAL,
    close REAL,
    volume INTEGER,
    source TEXT DEFAULT 'api', -- api, manual
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(symbol, date)
);

CREATE INDEX idx_price_history_symbol_date ON investment_price_history(symbol, date);

-- Watchlist (assets to monitor)
CREATE TABLE IF NOT EXISTS investment_watchlist (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    symbol TEXT NOT NULL,
    name TEXT NOT NULL,
    type TEXT NOT NULL,
    target_buy_price REAL,
    target_sell_price REAL,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id, symbol)
);

-- Portfolio snapshots (daily performance tracking)
CREATE TABLE IF NOT EXISTS portfolio_snapshots (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    investment_account_id INTEGER,
    snapshot_date DATE NOT NULL,
    total_value REAL NOT NULL,
    total_cost_basis REAL NOT NULL,
    total_gain_loss REAL NOT NULL,
    total_gain_loss_percentage REAL NOT NULL,
    daily_change REAL,
    daily_change_percentage REAL,
    holdings_json TEXT, -- JSON snapshot of all holdings
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (investment_account_id) REFERENCES investment_accounts(id) ON DELETE CASCADE,
    UNIQUE(user_id, investment_account_id, snapshot_date)
);

CREATE INDEX idx_snapshots_date ON portfolio_snapshots(snapshot_date);

-- Dividend tracking
CREATE TABLE IF NOT EXISTS investment_dividends (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    investment_account_id INTEGER NOT NULL,
    holding_id INTEGER,
    symbol TEXT NOT NULL,
    amount REAL NOT NULL,
    currency TEXT DEFAULT 'USD',
    payment_date DATE NOT NULL,
    ex_dividend_date DATE,
    record_date DATE,
    is_paid INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (investment_account_id) REFERENCES investment_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (holding_id) REFERENCES investment_holdings(id) ON DELETE SET NULL
);

-- Alerts (price alerts, portfolio alerts)
CREATE TABLE IF NOT EXISTS investment_alerts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    symbol TEXT NOT NULL,
    alert_type TEXT NOT NULL, -- price_above, price_below, gain_percentage, loss_percentage
    threshold_value REAL NOT NULL,
    is_active INTEGER DEFAULT 1,
    last_triggered DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Sectors allocation
CREATE TABLE IF NOT EXISTS portfolio_sectors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    sector TEXT NOT NULL,
    target_allocation REAL, -- percentage
    current_allocation REAL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert common sectors
INSERT OR IGNORE INTO portfolio_sectors (user_id, sector, target_allocation)
SELECT 1, 'Technology', 30.0 WHERE EXISTS (SELECT 1 FROM users WHERE id = 1);
INSERT OR IGNORE INTO portfolio_sectors (user_id, sector, target_allocation)
SELECT 1, 'Healthcare', 15.0 WHERE EXISTS (SELECT 1 FROM users WHERE id = 1);
INSERT OR IGNORE INTO portfolio_sectors (user_id, sector, target_allocation)
SELECT 1, 'Finance', 15.0 WHERE EXISTS (SELECT 1 FROM users WHERE id = 1);
INSERT OR IGNORE INTO portfolio_sectors (user_id, sector, target_allocation)
SELECT 1, 'Consumer', 15.0 WHERE EXISTS (SELECT 1 FROM users WHERE id = 1);
INSERT OR IGNORE INTO portfolio_sectors (user_id, sector, target_allocation)
SELECT 1, 'Energy', 10.0 WHERE EXISTS (SELECT 1 FROM users WHERE id = 1);
INSERT OR IGNORE INTO portfolio_sectors (user_id, sector, target_allocation)
SELECT 1, 'Real Estate', 10.0 WHERE EXISTS (SELECT 1 FROM users WHERE id = 1);
INSERT OR IGNORE INTO portfolio_sectors (user_id, sector, target_allocation)
SELECT 1, 'Other', 5.0 WHERE EXISTS (SELECT 1 FROM users WHERE id = 1);
