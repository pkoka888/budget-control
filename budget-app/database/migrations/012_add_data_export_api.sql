-- Migration 012: Advanced Data Export & API
-- Adds comprehensive data export, API access, and integrations

-- API keys (for external access)
CREATE TABLE IF NOT EXISTS api_keys (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    key_name TEXT NOT NULL,
    api_key TEXT NOT NULL UNIQUE,
    api_secret TEXT,
    permissions_json TEXT NOT NULL, -- JSON array of permissions
    rate_limit INTEGER DEFAULT 1000, -- requests per hour
    is_active INTEGER DEFAULT 1,
    last_used DATETIME,
    expires_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_api_keys_key ON api_keys(api_key);

-- API request logs
CREATE TABLE IF NOT EXISTS api_request_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    api_key_id INTEGER NOT NULL,
    endpoint TEXT NOT NULL,
    method TEXT NOT NULL, -- GET, POST, PUT, DELETE
    status_code INTEGER,
    request_ip TEXT,
    request_data TEXT,
    response_time INTEGER, -- milliseconds
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (api_key_id) REFERENCES api_keys(id) ON DELETE CASCADE
);

CREATE INDEX idx_api_logs_created ON api_request_logs(created_at);

-- Export jobs (async export generation)
CREATE TABLE IF NOT EXISTS export_jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    export_type TEXT NOT NULL, -- transactions, budgets, goals, investments, full_backup
    format TEXT NOT NULL, -- csv, json, xlsx, pdf, qif, ofx
    date_range_start DATE,
    date_range_end DATE,
    filters_json TEXT, -- JSON filter criteria
    file_path TEXT,
    file_size INTEGER,
    status TEXT DEFAULT 'pending', -- pending, processing, completed, failed
    progress INTEGER DEFAULT 0, -- 0-100
    error_message TEXT,
    download_url TEXT,
    download_count INTEGER DEFAULT 0,
    expires_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_exports_user_status ON export_jobs(user_id, status);

-- Integration connections (external services)
CREATE TABLE IF NOT EXISTS integrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    integration_type TEXT NOT NULL, -- plaid, mint, ynab, google_sheets, zapier, etc.
    name TEXT NOT NULL,
    status TEXT DEFAULT 'active', -- active, error, disconnected
    access_token TEXT,
    refresh_token TEXT,
    token_expires_at DATETIME,
    config_json TEXT, -- Integration-specific configuration
    last_sync DATETIME,
    sync_frequency TEXT DEFAULT 'daily', -- manual, hourly, daily, weekly
    auto_sync_enabled INTEGER DEFAULT 1,
    error_count INTEGER DEFAULT 0,
    last_error TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Integration sync logs
CREATE TABLE IF NOT EXISTS integration_sync_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    integration_id INTEGER NOT NULL,
    sync_type TEXT NOT NULL, -- full, incremental
    status TEXT NOT NULL, -- started, completed, failed
    records_synced INTEGER DEFAULT 0,
    records_failed INTEGER DEFAULT 0,
    error_message TEXT,
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME,
    FOREIGN KEY (integration_id) REFERENCES integrations(id) ON DELETE CASCADE
);

-- Webhooks (outgoing notifications)
CREATE TABLE IF NOT EXISTS webhooks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    url TEXT NOT NULL,
    event_types TEXT NOT NULL, -- JSON array of event types
    secret TEXT,
    is_active INTEGER DEFAULT 1,
    retry_count INTEGER DEFAULT 3,
    last_triggered DATETIME,
    last_status INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Webhook delivery logs
CREATE TABLE IF NOT EXISTS webhook_deliveries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    webhook_id INTEGER NOT NULL,
    event_type TEXT NOT NULL,
    payload TEXT NOT NULL,
    response_status INTEGER,
    response_body TEXT,
    delivery_time INTEGER, -- milliseconds
    attempt_number INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (webhook_id) REFERENCES webhooks(id) ON DELETE CASCADE
);

-- Data import jobs
CREATE TABLE IF NOT EXISTS import_jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    import_type TEXT NOT NULL, -- transactions, budgets, investments
    source_format TEXT NOT NULL, -- csv, xlsx, json, qif, ofx, mint, ynab
    file_path TEXT NOT NULL,
    file_name TEXT NOT NULL,
    file_size INTEGER,
    mapping_json TEXT, -- Column mapping configuration
    status TEXT DEFAULT 'pending', -- pending, processing, completed, failed, partially_completed
    progress INTEGER DEFAULT 0,
    total_rows INTEGER,
    rows_imported INTEGER DEFAULT 0,
    rows_failed INTEGER DEFAULT 0,
    errors_json TEXT, -- JSON array of errors
    preview_data TEXT, -- Sample of imported data
    is_dry_run INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Import field mappings (saved mapping templates)
CREATE TABLE IF NOT EXISTS import_mappings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    mapping_name TEXT NOT NULL,
    import_type TEXT NOT NULL,
    source_format TEXT NOT NULL,
    field_mappings_json TEXT NOT NULL, -- JSON field mapping
    date_format TEXT,
    amount_format TEXT,
    is_default INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Scheduled exports (recurring automatic exports)
CREATE TABLE IF NOT EXISTS scheduled_exports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    export_type TEXT NOT NULL,
    format TEXT NOT NULL,
    frequency TEXT NOT NULL, -- daily, weekly, monthly, quarterly, annually
    next_run_date DATETIME NOT NULL,
    last_run_date DATETIME,
    delivery_method TEXT DEFAULT 'download', -- download, email, google_drive, dropbox, sftp
    delivery_config_json TEXT,
    filters_json TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- OAuth tokens (for integrations)
CREATE TABLE IF NOT EXISTS oauth_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    provider TEXT NOT NULL, -- google, dropbox, github, etc.
    access_token TEXT NOT NULL,
    refresh_token TEXT,
    token_type TEXT DEFAULT 'Bearer',
    scope TEXT,
    expires_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id, provider)
);

-- API rate limiting
CREATE TABLE IF NOT EXISTS api_rate_limits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    api_key_id INTEGER NOT NULL,
    window_start DATETIME NOT NULL,
    request_count INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (api_key_id) REFERENCES api_keys(id) ON DELETE CASCADE,
    UNIQUE(api_key_id, window_start)
);

-- Supported export formats metadata
CREATE TABLE IF NOT EXISTS export_formats (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    format_code TEXT NOT NULL UNIQUE,
    format_name TEXT NOT NULL,
    mime_type TEXT NOT NULL,
    file_extension TEXT NOT NULL,
    supports_transactions INTEGER DEFAULT 1,
    supports_budgets INTEGER DEFAULT 1,
    supports_investments INTEGER DEFAULT 1,
    description TEXT
);

-- Insert supported export formats
INSERT OR IGNORE INTO export_formats (format_code, format_name, mime_type, file_extension) VALUES
('csv', 'CSV (Comma Separated Values)', 'text/csv', 'csv'),
('xlsx', 'Excel Spreadsheet', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'xlsx'),
('json', 'JSON', 'application/json', 'json'),
('pdf', 'PDF Report', 'application/pdf', 'pdf'),
('qif', 'Quicken Interchange Format', 'application/vnd.intu.qif', 'qif'),
('ofx', 'Open Financial Exchange', 'application/vnd.intu.ofx', 'ofx'),
('xml', 'XML', 'application/xml', 'xml');
