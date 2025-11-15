-- =============================================================================
-- SEED DATA FOR DEMO ACCOUNT
-- =============================================================================
-- Demo user: demo@budgetcontrol.cz / DemoPassword123!
-- Includes realistic Czech financial data
-- =============================================================================

-- Demo User
INSERT INTO users (id, name, email, password_hash, currency, timezone, email_verified, created_at) VALUES
(1, 'Demo Uživatel', 'demo@budgetcontrol.cz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'CZK', 'Europe/Prague', 1, datetime('now', '-90 days'));
-- Password: DemoPassword123!

-- Demo Household
INSERT INTO households (id, name, description, created_by, currency, timezone, is_active, created_at) VALUES
(1, 'Rodina Demo', 'Ukázková domácnost pro testování aplikace', 1, 'CZK', 'Europe/Prague', 1, datetime('now', '-90 days'));

-- Add user to household as owner
INSERT INTO household_members (household_id, user_id, role, permission_level, is_active, joined_at) VALUES
(1, 1, 'owner', 100, 1, datetime('now', '-90 days'));

-- Household settings (simplified schema)
INSERT INTO household_settings (household_id, default_visibility, allow_member_invites, require_approval_threshold, notify_new_transactions, notify_budget_alerts, allow_child_accounts, child_requires_approval, created_at) VALUES
(1, 'shared', 1, 5000.00, 1, 1, 1, 1, datetime('now', '-90 days'));

-- =============================================================================
-- ACCOUNTS
-- =============================================================================
INSERT INTO accounts (user_id, name, type, balance, currency, is_active, created_at) VALUES
(1, 'Běžný účet', 'checking', 45230.50, 'CZK', 1, datetime('now', '-90 days')),
(1, 'Spořící účet', 'savings', 128500.00, 'CZK', 1, datetime('now', '-90 days')),
(1, 'Investice', 'investment', 85000.00, 'CZK', 1, datetime('now', '-90 days')),
(1, 'Hotovost', 'checking', 3500.00, 'CZK', 1, datetime('now', '-90 days'));

-- =============================================================================
-- CATEGORIES
-- =============================================================================
INSERT INTO categories (user_id, name, type, icon, color, parent_id, created_at) VALUES
-- Expenses
(1, 'Bydlení', 'expense', '🏠', '#e74c3c', NULL, datetime('now', '-90 days')),
(1, 'Nájem', 'expense', '🔑', '#c0392b', 1, datetime('now', '-90 days')),
(1, 'Energie', 'expense', '⚡', '#e67e22', 1, datetime('now', '-90 days')),
(1, 'Potraviny', 'expense', '🛒', '#27ae60', NULL, datetime('now', '-90 days')),
(1, 'Doprava', 'expense', '🚗', '#3498db', NULL, datetime('now', '-90 days')),
(1, 'Benzín', 'expense', '⛽', '#2980b9', 5, datetime('now', '-90 days')),
(1, 'MHD', 'expense', '🚌', '#1abc9c', 5, datetime('now', '-90 days')),
(1, 'Zábava', 'expense', '🎬', '#9b59b6', NULL, datetime('now', '-90 days')),
(1, 'Restaurace', 'expense', '🍽️', '#8e44ad', 8, datetime('now', '-90 days')),
(1, 'Zdraví', 'expense', '💊', '#16a085', NULL, datetime('now', '-90 days')),
(1, 'Vzdělání', 'expense', '📚', '#f39c12', NULL, datetime('now', '-90 days')),
(1, 'Oblečení', 'expense', '👕', '#34495e', NULL, datetime('now', '-90 days')),
(1, 'Dárky', 'expense', '🎁', '#e91e63', NULL, datetime('now', '-90 days')),
-- Income
(1, 'Příjem', 'income', '💰', '#2ecc71', NULL, datetime('now', '-90 days')),
(1, 'Mzda', 'income', '💼', '#27ae60', 14, datetime('now', '-90 days')),
(1, 'Freelance', 'income', '💻', '#16a085', 14, datetime('now', '-90 days'));

-- =============================================================================
-- TRANSACTIONS - Last 3 months
-- =============================================================================
-- Month 1 (90 days ago)
INSERT INTO transactions (user_id, account_id, category_id, amount, type, description, merchant_name, date, created_at) VALUES
-- Income
(1, 1, 15, 45000.00, 'income', 'Výplata - září', NULL, date('now', '-90 days'), datetime('now', '-90 days')),
-- Expenses
(1, 1, 2, -12000.00, 'expense', 'Nájem', NULL, date('now', '-89 days'), datetime('now', '-89 days')),
(1, 1, 3, -2850.00, 'expense', 'Elektřina + plyn', NULL, date('now', '-88 days'), datetime('now', '-88 days')),
(1, 1, 4, -2150.00, 'expense', 'Nákup potravin', 'Albert', date('now', '-87 days'), datetime('now', '-87 days')),
(1, 1, 4, -1850.00, 'expense', 'Nákup potravin', 'Kaufland', date('now', '-85 days'), datetime('now', '-85 days')),
(1, 1, 4, -1650.00, 'expense', 'Nákup potravin', 'Lidl', date('now', '-82 days'), datetime('now', '-82 days')),
(1, 1, 6, -1200.00, 'expense', 'Benzín', 'Benzina', date('now', '-86 days'), datetime('now', '-86 days')),
(1, 1, 7, -550.00, 'expense', 'Lítačka - měsíční', 'DPP', date('now', '-84 days'), datetime('now', '-84 days')),
(1, 1, 9, -850.00, 'expense', 'Večeře', 'Restaurant U Dvou koček', date('now', '-80 days'), datetime('now', '-80 days')),
(1, 4, 9, -420.00, 'expense', 'Oběd', 'Café Imperial', date('now', '-78 days'), datetime('now', '-78 days')),
(1, 1, 10, -1250.00, 'expense', 'Léky', 'Lékárna U Anděla', date('now', '-75 days'), datetime('now', '-75 days')),
(1, 1, 12, -3200.00, 'expense', 'Oblečení', 'Reserved', date('now', '-72 days'), datetime('now', '-72 days')),
-- Transfer to savings
(1, 1, NULL, -10000.00, 'transfer', 'Převod na spořící účet', NULL, date('now', '-70 days'), datetime('now', '-70 days')),

-- Month 2 (60 days ago)
(1, 1, 15, 45000.00, 'income', 'Výplata - říjen', NULL, date('now', '-60 days'), datetime('now', '-60 days')),
(1, 1, 16, 8500.00, 'income', 'Freelance projekt', 'Klient XYZ', date('now', '-58 days'), datetime('now', '-58 days')),
-- Expenses
(1, 1, 2, -12000.00, 'expense', 'Nájem', NULL, date('now', '-59 days'), datetime('now', '-59 days')),
(1, 1, 3, -2950.00, 'expense', 'Elektřina + plyn', NULL, date('now', '-57 days'), datetime('now', '-57 days')),
(1, 1, 4, -2050.00, 'expense', 'Nákup potravin', 'Albert', date('now', '-56 days'), datetime('now', '-56 days')),
(1, 1, 4, -1950.00, 'expense', 'Nákup potravin', 'Kaufland', date('now', '-54 days'), datetime('now', '-54 days')),
(1, 1, 4, -1750.00, 'expense', 'Nákup potravin', 'Tesco', date('now', '-51 days'), datetime('now', '-51 days')),
(1, 1, 6, -1350.00, 'expense', 'Benzín', 'Shell', date('now', '-55 days'), datetime('now', '-55 days')),
(1, 1, 7, -550.00, 'expense', 'Lítačka - měsíční', 'DPP', date('now', '-53 days'), datetime('now', '-53 days')),
(1, 1, 8, -1200.00, 'expense', 'Kino + popcorn', 'Cinema City', date('now', '-50 days'), datetime('now', '-50 days')),
(1, 1, 9, -950.00, 'expense', 'Večeře', 'Lokál Dlouhááá', date('now', '-48 days'), datetime('now', '-48 days')),
(1, 4, 9, -380.00, 'expense', 'Oběd', 'Bistro', date('now', '-46 days'), datetime('now', '-46 days')),
(1, 1, 11, -1850.00, 'expense', 'Online kurz', 'Udemy', date('now', '-45 days'), datetime('now', '-45 days')),
(1, 1, 13, -650.00, 'expense', 'Dárek - narozeniny', 'Knihkupectví', date('now', '-42 days'), datetime('now', '-42 days')),
-- Transfer to savings
(1, 1, NULL, -15000.00, 'transfer', 'Převod na spořící účet', NULL, date('now', '-40 days'), datetime('now', '-40 days')),

-- Month 3 (30 days ago) - Current
(1, 1, 15, 45000.00, 'income', 'Výplata - listopad', NULL, date('now', '-30 days'), datetime('now', '-30 days')),
-- Expenses
(1, 1, 2, -12000.00, 'expense', 'Nájem', NULL, date('now', '-29 days'), datetime('now', '-29 days')),
(1, 1, 3, -3150.00, 'expense', 'Elektřina + plyn', NULL, date('now', '-27 days'), datetime('now', '-27 days')),
(1, 1, 4, -2100.00, 'expense', 'Nákup potravin', 'Albert', date('now', '-26 days'), datetime('now', '-26 days')),
(1, 1, 4, -1900.00, 'expense', 'Nákup potravin', 'Kaufland', date('now', '-24 days'), datetime('now', '-24 days')),
(1, 1, 4, -1800.00, 'expense', 'Nákup potravin', 'Billa', date('now', '-21 days'), datetime('now', '-21 days')),
(1, 1, 6, -1400.00, 'expense', 'Benzín', 'Benzina', date('now', '-25 days'), datetime('now', '-25 days')),
(1, 1, 7, -550.00, 'expense', 'Lítačka - měsíční', 'DPP', date('now', '-23 days'), datetime('now', '-23 days')),
(1, 1, 9, -1150.00, 'expense', 'Večeře', 'Sansho', date('now', '-20 days'), datetime('now', '-20 days')),
(1, 4, 9, -520.00, 'expense', 'Oběd', 'Café Louvre', date('now', '-18 days'), datetime('now', '-18 days')),
(1, 1, 12, -2800.00, 'expense', 'Boty', 'CCC', date('now', '-15 days'), datetime('now', '-15 days')),
(1, 1, 8, -450.00, 'expense', 'Streamovací služby', 'Netflix', date('now', '-12 days'), datetime('now', '-12 days')),
(1, 4, 4, -850.00, 'expense', 'Nákup potravin', 'Večerka', date('now', '-10 days'), datetime('now', '-10 days')),
(1, 1, 10, -420.00, 'expense', 'Vitamíny', 'DM Drogerie', date('now', '-8 days'), datetime('now', '-8 days')),
(1, 4, 9, -320.00, 'expense', 'Káva', 'Starbucks', date('now', '-5 days'), datetime('now', '-5 days')),
(1, 1, 6, -800.00, 'expense', 'Benzín', 'Shell', date('now', '-3 days'), datetime('now', '-3 days')),
(1, 4, 4, -450.00, 'expense', 'Nákup potravin', 'Tesco Express', date('now', '-2 days'), datetime('now', '-2 days'));

-- =============================================================================
-- BUDGETS
-- =============================================================================
INSERT INTO budgets (user_id, category_id, amount, period, start_date, end_date, alert_threshold, is_active, created_at) VALUES
(1, 1, 15000.00, 'monthly', date('now', 'start of month'), date('now', 'start of month', '+1 month', '-1 day'), 0.8, 1, datetime('now', '-90 days')),
(1, 4, 8000.00, 'monthly', date('now', 'start of month'), date('now', 'start of month', '+1 month', '-1 day'), 0.8, 1, datetime('now', '-90 days')),
(1, 5, 4000.00, 'monthly', date('now', 'start of month'), date('now', 'start of month', '+1 month', '-1 day'), 0.8, 1, datetime('now', '-90 days')),
(1, 8, 3000.00, 'monthly', date('now', 'start of month'), date('now', 'start of month', '+1 month', '-1 day'), 0.8, 1, datetime('now', '-90 days'));

-- =============================================================================
-- FINANCIAL GOALS
-- =============================================================================
INSERT INTO goals (user_id, name, description, goal_type, target_amount, current_amount, target_date, category, priority, is_active, created_at) VALUES
(1, 'Dovolená 2025', 'Letní dovolená v Chorvatsku', 'savings', 50000.00, 28500.00, date('now', '+180 days'), 'travel', 'high', 1, datetime('now', '-60 days')),
(1, 'Nouzový fond', 'Rezerva na 6 měsíců výdajů', 'savings', 200000.00, 128500.00, date('now', '+365 days'), 'emergency', 'high', 1, datetime('now', '-90 days')),
(1, 'Nový notebook', 'MacBook Pro pro práci', 'purchase', 45000.00, 15000.00, date('now', '+90 days'), 'purchase', 'medium', 1, datetime('now', '-30 days'));

-- =============================================================================
-- MERCHANTS (for categorization learning)
-- =============================================================================
INSERT INTO merchants (name, default_category_id, created_at) VALUES
('Albert', 4, datetime('now', '-90 days')),
('Kaufland', 4, datetime('now', '-90 days')),
('Lidl', 4, datetime('now', '-90 days')),
('Tesco', 4, datetime('now', '-90 days')),
('Benzina', 6, datetime('now', '-90 days')),
('Shell', 6, datetime('now', '-90 days')),
('DPP', 7, datetime('now', '-90 days')),
('ČEZ', 3, datetime('now', '-90 days'));

-- =============================================================================
-- CATEGORIZATION RULES
-- =============================================================================
INSERT INTO categorization_rules (user_id, rule_name, pattern, category_id, priority, is_active, created_at) VALUES
(1, 'Nájem auto-kategorizace', 'nájem', 2, 100, 1, datetime('now', '-90 days')),
(1, 'Supermarkety', 'Albert|Kaufland|Lidl|Tesco', 4, 90, 1, datetime('now', '-90 days')),
(1, 'Benzín', 'Benzina|Shell|OMV', 6, 90, 1, datetime('now', '-90 days')),
(1, 'MHD', 'DPP', 7, 100, 1, datetime('now', '-90 days'));

-- =============================================================================
-- RECURRING TRANSACTIONS
-- =============================================================================
INSERT INTO recurring_transactions (user_id, account_id, category_id, type, amount, description, frequency, start_date, next_date, is_active, created_at) VALUES
(1, 1, 2, 'expense', -12000.00, 'Nájem', 'monthly', date('now', '-90 days'), date('now', '+1 month', 'start of month'), 1, datetime('now', '-90 days')),
(1, 1, 3, 'expense', -3000.00, 'Elektřina + plyn', 'monthly', date('now', '-90 days'), date('now', '+1 month', 'start of month', '+5 days'), 1, datetime('now', '-90 days')),
(1, 1, 7, 'expense', -550.00, 'Lítačka - měsíční', 'monthly', date('now', '-90 days'), date('now', '+1 month', 'start of month'), 1, datetime('now', '-90 days')),
(1, 1, 8, 'expense', -450.00, 'Netflix', 'monthly', date('now', '-90 days'), date('now', '+12 days'), 1, datetime('now', '-90 days')),
(1, 1, 15, 'income', 45000.00, 'Výplata', 'monthly', date('now', '-90 days'), date('now', '+1 month', 'start of month'), 1, datetime('now', '-90 days'));

-- =============================================================================
-- INVESTMENTS (Portfolio)
-- =============================================================================
INSERT INTO investments (user_id, account_id, name, symbol, type, quantity, purchase_price, current_price, currency, purchase_date, created_at) VALUES
(1, 3, 'ETF S&P 500', 'SPY', 'etf', 15.5, 4200.00, 4580.00, 'CZK', date('now', '-180 days'), datetime('now', '-180 days')),
(1, 3, 'Česká spořitelna', 'ERSTE', 'stock', 50, 850.00, 920.00, 'CZK', date('now', '-120 days'), datetime('now', '-120 days')),
(1, 3, 'Bitcoin', 'BTC', 'crypto', 0.05, 480000.00, 520000.00, 'CZK', date('now', '-60 days'), datetime('now', '-60 days'));

-- =============================================================================
-- NOTIFICATIONS
-- =============================================================================
INSERT INTO notifications (user_id, type, title, message, is_read, action_url, created_at) VALUES
(1, 'budget_alert', 'Rozpočet zábava překročen', 'Váš rozpočet na zábavu byl překročen o 450 Kč', 0, '/budgets', datetime('now', '-2 days')),
(1, 'goal_milestone', 'Cíl dovolená - 50% splněno!', 'Gratulujeme! Máte našetřeno 50% na dovolenou 2025', 1, '/goals', datetime('now', '-10 days')),
(1, 'bill_reminder', 'Připomínka platby', 'Nájem splatný za 3 dny', 1, '/transactions', datetime('now', '-5 days'));

-- =============================================================================
-- Update account balances to match transactions
-- =============================================================================
UPDATE accounts SET balance = 45230.50 WHERE id = 1;
UPDATE accounts SET balance = 128500.00 WHERE id = 2;
UPDATE accounts SET balance = 85000.00 WHERE id = 3;
UPDATE accounts SET balance = 3500.00 WHERE id = 4;
