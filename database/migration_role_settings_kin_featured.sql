-- ============================================================
-- MIGRATION: Role Management/Settings, Next of Kin, Featured Staff
-- Run once against an existing NRMS database (after schema.sql and the
-- monthly-allowance migration have already been applied). Also folded
-- into schema.sql directly for brand-new installs.
-- ============================================================

USE nrms_db;

-- ------------------------------------------------------------
-- 1. ROLE MANAGEMENT & SYSTEM SETTINGS
-- ------------------------------------------------------------

-- Prevent duplicate permission rows when toggling checkboxes in the UI.
SET @idx_exists := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'permissions' AND INDEX_NAME = 'uniq_role_permission'
);
SET @sql := IF(@idx_exists = 0,
    'ALTER TABLE permissions ADD UNIQUE KEY uniq_role_permission (role_id, permission_key)',
    'SELECT ''permissions.uniq_role_permission already exists, skipping'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- New lookup table: Section (used by the Monthly Allowance module). Kept as
-- a managed lookup list like Rank/Bank/Department, while the
-- monthly_allowance_personnel.section column itself stays a plain VARCHAR
-- for backward compatibility with existing data — this table just gives
-- administrators a managed set of values to pick from.
CREATE TABLE IF NOT EXISTS sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE
);

-- Seed it from whatever section values are already in use, so nothing
-- already-entered disappears from the dropdown after this migration.
INSERT IGNORE INTO sections (name)
SELECT DISTINCT section FROM monthly_allowance_personnel WHERE section IS NOT NULL AND section != '';

-- Default permission grants matching the app's existing hardcoded role
-- checks, plus the new Settings area (Super Administrator only for
-- Roles/Permissions/System Settings; Super Administrator + Administrator
-- for Lookup Management and Front Page Featured Staff).
INSERT IGNORE INTO permissions (role_id, permission_key)
SELECT id, 'settings.roles' FROM roles WHERE name = 'Super Administrator';
INSERT IGNORE INTO permissions (role_id, permission_key)
SELECT id, 'settings.permissions' FROM roles WHERE name = 'Super Administrator';
INSERT IGNORE INTO permissions (role_id, permission_key)
SELECT id, 'settings.system' FROM roles WHERE name = 'Super Administrator';
INSERT IGNORE INTO permissions (role_id, permission_key)
SELECT id, 'settings.lookups' FROM roles WHERE name IN ('Super Administrator', 'Administrator');
INSERT IGNORE INTO permissions (role_id, permission_key)
SELECT id, 'settings.featured_staff' FROM roles WHERE name IN ('Super Administrator', 'Administrator');

-- A handful of starter system settings the app now reads (see config/app.php
-- and includes/functions.php's setting() helper). Safe to edit afterward
-- from Settings > System Settings.
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
    ('site_name', 'Nominal Roll Management System'),
    ('records_per_page', '20'),
    ('session_timeout_minutes', '30');

-- ------------------------------------------------------------
-- 2. NEXT OF KIN (up to 2 per personnel record)
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS personnel_next_of_kin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    kin_number TINYINT NOT NULL,               -- 1 or 2
    full_name VARCHAR(150),
    relationship VARCHAR(100),
    email_address VARCHAR(150),
    residential_address VARCHAR(255),
    phone_number VARCHAR(20),
    alt_phone_number VARCHAR(20),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_personnel_kin_slot (personnel_id, kin_number),
    CONSTRAINT chk_kin_number CHECK (kin_number IN (1, 2))
);

-- ------------------------------------------------------------
-- 3. FRONT PAGE FEATURED STAFF (IGP down to Staff Officers)
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS featured_staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL UNIQUE,          -- one featured slot per personnel record, no duplicates
    display_order INT NOT NULL DEFAULT 0,
    added_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE,
    FOREIGN KEY (added_by) REFERENCES users(id)
);
