-- ============================================================
-- MIGRATION: Monthly Allowance Management Module
-- Run this once against an existing NRMS database (after schema.sql
-- has already been applied). It is also folded into schema.sql
-- directly, so brand-new installs don't need to run this separately.
-- ============================================================

USE nrms_db;

-- ------------------------------------------------------------
-- New table: personnel registered for monthly allowance payment.
-- Deliberately standalone from the `personnel` table — this module
-- is meant to also cover people who receive an allowance but aren't
-- (yet) on the main nominal roll (e.g. attached/contract staff).
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS monthly_allowance_personnel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fap_number VARCHAR(30) NOT NULL UNIQUE,   -- F-AP Number
    rank_id INT,
    full_name VARCHAR(150) NOT NULL,
    account_number VARCHAR(30),
    bank_id INT,
    employee_code VARCHAR(50),
    section VARCHAR(150),
    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (rank_id) REFERENCES ranks(id),
    FOREIGN KEY (bank_id) REFERENCES banks(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_allowance_fap (fap_number),
    INDEX idx_allowance_name (full_name),
    INDEX idx_allowance_section (section),
    INDEX idx_allowance_status (status)
);

-- ------------------------------------------------------------
-- Reuse the existing import_history table for this module's bulk-upload
-- log too, distinguished by a `module` column. Existing rows (from the
-- main Personnel bulk upload) default to 'personnel' so nothing already
-- logged needs to change.
-- ------------------------------------------------------------

SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'import_history' AND COLUMN_NAME = 'module'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE import_history ADD COLUMN module VARCHAR(50) NOT NULL DEFAULT ''personnel'' AFTER uploaded_by',
    'SELECT ''import_history.module already exists, skipping'''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
