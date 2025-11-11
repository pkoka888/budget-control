-- Migration: Add 2FA and Email Verification Support
-- Created: 2025-11-11
-- Phase 2: Advanced Authentication Features

-- Add 2FA and email verification columns to users table
ALTER TABLE users ADD COLUMN email_verified INTEGER DEFAULT 0;
ALTER TABLE users ADD COLUMN email_verification_token TEXT;
ALTER TABLE users ADD COLUMN email_verification_sent_at DATETIME;
ALTER TABLE users ADD COLUMN two_factor_enabled INTEGER DEFAULT 0;
ALTER TABLE users ADD COLUMN two_factor_secret TEXT;
ALTER TABLE users ADD COLUMN two_factor_backup_codes TEXT; -- JSON array of backup codes

-- Email Verification Tokens Table
CREATE TABLE IF NOT EXISTS email_verification_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token TEXT NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    verified_at DATETIME,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_email_verification_token ON email_verification_tokens(token);
CREATE INDEX idx_email_verification_user_id ON email_verification_tokens(user_id);

-- Two-Factor Authentication Sessions Table (for remember device feature)
CREATE TABLE IF NOT EXISTS two_factor_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    session_token TEXT NOT NULL UNIQUE,
    device_fingerprint TEXT,
    ip_address TEXT,
    user_agent TEXT,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_used_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_2fa_session_token ON two_factor_sessions(session_token);
CREATE INDEX idx_2fa_user_id ON two_factor_sessions(user_id);

-- Two-Factor Backup Codes Table (for tracking used backup codes)
CREATE TABLE IF NOT EXISTS two_factor_backup_codes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    code_hash TEXT NOT NULL,
    used_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_2fa_backup_user_id ON two_factor_backup_codes(user_id);

-- Audit log for 2FA events
CREATE TABLE IF NOT EXISTS two_factor_audit_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    event_type TEXT NOT NULL, -- 'enabled', 'disabled', 'verified', 'failed', 'backup_used'
    ip_address TEXT,
    user_agent TEXT,
    success INTEGER DEFAULT 1,
    metadata TEXT, -- JSON for additional event data
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_2fa_audit_user_id ON two_factor_audit_log(user_id);
CREATE INDEX idx_2fa_audit_event_type ON two_factor_audit_log(event_type);
CREATE INDEX idx_2fa_audit_created_at ON two_factor_audit_log(created_at);
