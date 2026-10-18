-- ============================================================
-- NOMINAL ROLL MANAGEMENT SYSTEM (NRMS)
-- IGP-SECRETARIAT, Nigeria Police Force
-- MySQL 8 Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS nrms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nrms_db;

-- ---------------------------------------------------------
-- LOOKUP / REFERENCE TABLES
-- ---------------------------------------------------------

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,          -- Super Administrator, Administrator, Documentation Officer, Verification Officer, Read-only User
    description VARCHAR(255)
);

CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    permission_key VARCHAR(100) NOT NULL,      -- e.g. personnel.create, personnel.edit, reports.generate
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE          -- IGP Secretariat, ONSA, NRS, IGP Body Guards, Protocol, Escort, NASS, AM CON,
                                                -- NAPTIP, PMU, STFPIB, JAMB, ICPC, EFCC, NAFDAC, SEC, Civilian Staff, TIU,
                                                -- IGP Project Monitoring Unit, IGP Aides, POWA, NPF Trust Fund, PSIU, IGP Desk, IGP Guard House
);

CREATE TABLE ranks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    hierarchy_level INT DEFAULT 0              -- used for sorting IG > DIG > AIG ... down to Constable
);

CREATE TABLE geo_political_zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE           -- North Central, North East, North West, South East, South South, South West
);

CREATE TABLE states (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    zone_id INT,
    FOREIGN KEY (zone_id) REFERENCES geo_political_zones(id)
);

CREATE TABLE local_governments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    state_id INT NOT NULL,
    FOREIGN KEY (state_id) REFERENCES states(id)
);

CREATE TABLE tribes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE commands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE
);

CREATE TABLE banks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE pfas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE          -- Pension Fund Administrators
);

-- ---------------------------------------------------------
-- USERS / AUTH
-- ---------------------------------------------------------

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    role_id INT NOT NULL,
    status ENUM('active','disabled') DEFAULT 'active',
    two_factor_enabled TINYINT(1) DEFAULT 0,
    two_factor_secret VARCHAR(100) DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(100) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    username_attempted VARCHAR(50),
    ip_address VARCHAR(45),
    status ENUM('success','failed') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,              -- e.g. PERSONNEL_CREATED, PERSONNEL_UPDATED, PERSONNEL_TRANSFERRED
    entity_type VARCHAR(50),
    entity_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ---------------------------------------------------------
-- PERSONNEL (CORE TABLE)
-- ---------------------------------------------------------

CREATE TABLE personnel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ap_number VARCHAR(30) NOT NULL UNIQUE,     -- Service Number / AP Number
    photo_path VARCHAR(255) DEFAULT NULL,

    -- Personal Information
    full_name VARCHAR(150) NOT NULL,
    gender ENUM('Male','Female') NOT NULL,
    date_of_birth DATE,
    state_id INT,
    lga_id INT,
    tribe_id INT,
    zone_id INT,
    rank_id INT,

    -- Employment Information
    date_of_enlistment DATE,
    date_of_last_promotion DATE,
    date_of_retirement DATE,
    department_id INT,                         -- Current Unit
    previous_command_id INT,
    date_transferred DATE,
    duty_post VARCHAR(150),
    grade_level VARCHAR(20),
    employee_code VARCHAR(50),
    ippis_number VARCHAR(50),

    -- Contact
    phone_number VARCHAR(20),
    email_address VARCHAR(150),

    -- Financial
    bank_id INT,
    account_number VARCHAR(30),
    pfa_id INT,
    pension_pin VARCHAR(50),
    nhf_number VARCHAR(50),

    -- Education
    educational_qualification VARCHAR(150),

    -- Other
    gd_sp VARCHAR(50),
    mss VARCHAR(50),
    remarks TEXT,
    status ENUM('Active','Retired','Transferred','Suspended','Archived') DEFAULT 'Active',

    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (state_id) REFERENCES states(id),
    FOREIGN KEY (lga_id) REFERENCES local_governments(id),
    FOREIGN KEY (tribe_id) REFERENCES tribes(id),
    FOREIGN KEY (zone_id) REFERENCES geo_political_zones(id),
    FOREIGN KEY (rank_id) REFERENCES ranks(id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (previous_command_id) REFERENCES commands(id),
    FOREIGN KEY (bank_id) REFERENCES banks(id),
    FOREIGN KEY (pfa_id) REFERENCES pfas(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_name (full_name),
    INDEX idx_status (status),
    INDEX idx_dept (department_id),
    INDEX idx_rank (rank_id)
);

CREATE TABLE personnel_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_by INT,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    doc_type VARCHAR(100),
    file_path VARCHAR(255) NOT NULL,
    uploaded_by INT,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

CREATE TABLE transfers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    from_department_id INT,
    to_department_id INT,
    transfer_date DATE NOT NULL,
    reason VARCHAR(255),
    approved_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE,
    FOREIGN KEY (from_department_id) REFERENCES departments(id),
    FOREIGN KEY (to_department_id) REFERENCES departments(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

CREATE TABLE promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    from_rank_id INT,
    to_rank_id INT,
    promotion_date DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE,
    FOREIGN KEY (from_rank_id) REFERENCES ranks(id),
    FOREIGN KEY (to_rank_id) REFERENCES ranks(id)
);

CREATE TABLE education_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    qualification VARCHAR(150),
    institution VARCHAR(150),
    year_obtained YEAR,
    FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,                 -- RETIREMENT_DUE, BIRTHDAY, PROMOTION_DUE, MISSING_INFO, NEW_PERSONNEL
    personnel_id INT,
    message VARCHAR(255) NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE
);

CREATE TABLE import_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uploaded_by INT,
    module VARCHAR(50) NOT NULL DEFAULT 'personnel',   -- 'personnel' or 'monthly_allowance'
    file_name VARCHAR(255),
    total_rows INT DEFAULT 0,
    success_count INT DEFAULT 0,
    error_count INT DEFAULT 0,
    error_report TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT
);

-- ---------------------------------------------------------
-- MONTHLY ALLOWANCE MANAGEMENT MODULE
-- ---------------------------------------------------------

CREATE TABLE monthly_allowance_personnel (
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

-- ---------------------------------------------------------
-- ROLE MANAGEMENT & SYSTEM SETTINGS
-- ---------------------------------------------------------

-- `roles`, `permissions`, and `settings` already exist above; this just adds
-- the uniqueness guard and a managed lookup table for "Section".
ALTER TABLE permissions ADD UNIQUE KEY uniq_role_permission (role_id, permission_key);

CREATE TABLE sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE
);

-- ---------------------------------------------------------
-- NEXT OF KIN (up to 2 per personnel record)
-- ---------------------------------------------------------

CREATE TABLE personnel_next_of_kin (
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

-- ---------------------------------------------------------
-- FRONT PAGE FEATURED STAFF (IGP down to Staff Officers)
-- ---------------------------------------------------------

CREATE TABLE featured_staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL UNIQUE,          -- one featured slot per personnel record, no duplicates
    display_order INT NOT NULL DEFAULT 0,
    added_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE,
    FOREIGN KEY (added_by) REFERENCES users(id)
);

-- ---------------------------------------------------------
-- SEED DATA
-- ---------------------------------------------------------

INSERT INTO roles (name, description) VALUES
('Super Administrator','Full system control'),
('Administrator','Manage personnel, reports, imports, transfers'),
('Documentation Officer','Register and edit personnel, upload photographs'),
('Verification Officer','View and verify personnel, generate verification reports'),
('Read-only User','View reports and search personnel only');

INSERT INTO departments (name) VALUES
('IGP Secretariat'),('ONSA'),('NRS'),('IGP Body Guards'),('Protocol'),('Escort'),
('NASS'),('AM CON'),('NAPTIP'),('PMU'),('STFPIB'),('JAMB'),('ICPC'),('EFCC'),
('NAFDAC'),('SEC'),('Civilian Staff'),('TIU'),('IGP Project Monitoring Unit'),
('IGP Aides'),('POWA'),('NPF Trust Fund'),('PSIU'),('IGP Desk'),('IGP Guard House');

INSERT INTO geo_political_zones (name) VALUES
('North Central'),('North East'),('North West'),('South East'),('South South'),('South West');

INSERT INTO ranks (name, hierarchy_level) VALUES
('Inspector General of Police',1),('Deputy Inspector General',2),('Assistant Inspector General',3),
('Commissioner of Police',4),('Deputy Commissioner of Police',5),('Assistant Commissioner of Police',6),
('Chief Superintendent of Police',7),('Superintendent of Police',8),('Deputy Superintendent of Police',9),
('Assistant Superintendent of Police I',10),('Assistant Superintendent of Police II',11),
('Inspector of Police',12),('Sergeant Major',13),('Sergeant',14),('Corporal',15),('Constable',16);

-- Default permission grants matching the app's existing role checks, plus
-- the Settings area (Super Administrator only for Roles/Permissions/System
-- Settings; Super Administrator + Administrator for Lookup Management and
-- Front Page Featured Staff).
INSERT INTO permissions (role_id, permission_key)
SELECT id, 'settings.roles' FROM roles WHERE name = 'Super Administrator';
INSERT INTO permissions (role_id, permission_key)
SELECT id, 'settings.permissions' FROM roles WHERE name = 'Super Administrator';
INSERT INTO permissions (role_id, permission_key)
SELECT id, 'settings.system' FROM roles WHERE name = 'Super Administrator';
INSERT INTO permissions (role_id, permission_key)
SELECT id, 'settings.lookups' FROM roles WHERE name IN ('Super Administrator', 'Administrator');
INSERT INTO permissions (role_id, permission_key)
SELECT id, 'settings.featured_staff' FROM roles WHERE name IN ('Super Administrator', 'Administrator');

-- Starter system settings, editable afterward from Settings > System Settings.
INSERT INTO settings (setting_key, setting_value) VALUES
    ('site_name', 'Nominal Roll Management System'),
    ('records_per_page', '20'),
    ('session_timeout_minutes', '30');

-- No default admin user is seeded here on purpose (a hard-coded password hash in a script
-- you didn't generate yourself is a security smell). After importing this schema, open
-- setup.php in your browser ONCE to create the first Super Administrator account with a
-- real bcrypt hash generated by your own PHP install. setup.php deletes/locks itself after use.
