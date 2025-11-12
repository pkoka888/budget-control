-- Migration: Add Receipt OCR Scanning
-- Created: 2025-11-12
-- v1.1 Feature: Receipt image scanning and automatic data extraction

-- Receipt scans
CREATE TABLE IF NOT EXISTS receipt_scans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    transaction_id INTEGER,
    image_path TEXT NOT NULL,
    image_size INTEGER,
    ocr_provider TEXT,
    ocr_text TEXT,
    parsed_data TEXT,
    confidence_score REAL,
    status TEXT DEFAULT 'processing',
    error_message TEXT,
    processing_time INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(transaction_id) REFERENCES transactions(id) ON DELETE SET NULL
);

CREATE INDEX idx_receipt_scans_user ON receipt_scans(user_id);
CREATE INDEX idx_receipt_scans_status ON receipt_scans(status);
CREATE INDEX idx_receipt_scans_created ON receipt_scans(created_at DESC);

-- Receipt items (line items from receipt)
CREATE TABLE IF NOT EXISTS receipt_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    receipt_scan_id INTEGER NOT NULL,
    item_name TEXT NOT NULL,
    quantity REAL,
    unit_price REAL,
    total_price REAL NOT NULL,
    category_id INTEGER,
    line_number INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(receipt_scan_id) REFERENCES receipt_scans(id) ON DELETE CASCADE,
    FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE INDEX idx_receipt_items_scan ON receipt_items(receipt_scan_id);

-- Merchant database (for better recognition)
CREATE TABLE IF NOT EXISTS receipt_merchants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    normalized_name TEXT NOT NULL,
    aliases TEXT,
    category_id INTEGER,
    logo_url TEXT,
    website TEXT,
    is_verified INTEGER DEFAULT 0,
    scan_count INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE INDEX idx_receipt_merchants_normalized ON receipt_merchants(normalized_name);
CREATE INDEX idx_receipt_merchants_scan_count ON receipt_merchants(scan_count DESC);

-- OCR service usage tracking
CREATE TABLE IF NOT EXISTS ocr_usage_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    provider TEXT NOT NULL,
    operation TEXT NOT NULL,
    image_size INTEGER,
    processing_time INTEGER,
    cost REAL,
    success INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_ocr_usage_user ON ocr_usage_log(user_id);
CREATE INDEX idx_ocr_usage_created ON ocr_usage_log(created_at DESC);

-- Receipt review queue (for low confidence scans)
CREATE TABLE IF NOT EXISTS receipt_review_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    receipt_scan_id INTEGER NOT NULL UNIQUE,
    priority INTEGER DEFAULT 0,
    assigned_to INTEGER,
    review_status TEXT DEFAULT 'pending',
    review_notes TEXT,
    reviewed_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(receipt_scan_id) REFERENCES receipt_scans(id) ON DELETE CASCADE,
    FOREIGN KEY(assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_review_queue_status ON receipt_review_queue(review_status);
CREATE INDEX idx_review_queue_priority ON receipt_review_queue(priority DESC);

-- Receipt templates (for common receipt formats)
CREATE TABLE IF NOT EXISTS receipt_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    merchant_id INTEGER,
    country TEXT,
    template_pattern TEXT NOT NULL,
    field_mappings TEXT NOT NULL,
    is_active INTEGER DEFAULT 1,
    usage_count INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(merchant_id) REFERENCES receipt_merchants(id) ON DELETE SET NULL
);

CREATE INDEX idx_receipt_templates_merchant ON receipt_templates(merchant_id);
CREATE INDEX idx_receipt_templates_active ON receipt_templates(is_active);
