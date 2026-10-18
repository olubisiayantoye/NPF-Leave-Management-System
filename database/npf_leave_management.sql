-- =====================================================================
-- NPF Leave Management System — Core Schema (Phase 1) + Phase 2 stubs
-- MySQL 8+
-- =====================================================================

CREATE DATABASE IF NOT EXISTS npf_leave_management
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE npf_leave_management;

-- ---------------------------------------------------------------------
-- Lookup tables
-- ---------------------------------------------------------------------

CREATE TABLE ranks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0
);

CREATE TABLE departments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE
);

CREATE TABLE sections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
);

CREATE TABLE commands (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE
);

-- Excluded, along with Saturdays and Sundays, from leave-day calculation.
CREATE TABLE public_holidays (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    holiday_date DATE NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL
);

-- ---------------------------------------------------------------------
-- RBAC
-- ---------------------------------------------------------------------

CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(40) NOT NULL UNIQUE,      -- super_admin | administrator | i_admin | administration_officer | pso_i | pso_ii | read_only
    description VARCHAR(255)
);

CREATE TABLE permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(60) NOT NULL UNIQUE,      -- e.g. personnel.create, leave.approve
    description VARCHAR(255)
);

CREATE TABLE role_permissions (
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- Users & personnel
-- ---------------------------------------------------------------------

CREATE TABLE personnel (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    f_ap_number VARCHAR(30) NOT NULL UNIQUE,
    first_name VARCHAR(80) NOT NULL,
    middle_name VARCHAR(80),
    surname VARCHAR(80) NOT NULL,
    gender ENUM('M','F') DEFAULT NULL,
    date_of_birth DATE DEFAULT NULL,
    rank_id INT UNSIGNED NOT NULL,
    department_id INT UNSIGNED NOT NULL,
    section_id INT UNSIGNED DEFAULT NULL,
    command_id INT UNSIGNED DEFAULT NULL,
    date_of_first_appointment DATE DEFAULT NULL,
    date_of_last_promotion DATE DEFAULT NULL,
    phone VARCHAR(30),
    email VARCHAR(150),
    status ENUM('active','on_leave','suspended','retired') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rank_id) REFERENCES ranks(id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (section_id) REFERENCES sections(id),
    FOREIGN KEY (command_id) REFERENCES commands(id),
    INDEX idx_personnel_name (surname, first_name),
    INDEX idx_personnel_dept (department_id)
);

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(150) UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    personnel_id INT UNSIGNED DEFAULT NULL,     -- link to their own personnel record, if applicable
    status ENUM('active','inactive','locked') NOT NULL DEFAULT 'active',
    failed_login_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    mfa_secret VARCHAR(32) DEFAULT NULL,
    mfa_enabled TINYINT(1) NOT NULL DEFAULT 0,
    last_login_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE SET NULL
);

CREATE TABLE user_roles (
    user_id INT UNSIGNED NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    -- Optional scope: a Sectional Head's role may be scoped to one department/section
    department_id INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

-- ---------------------------------------------------------------------
-- Leave
-- ---------------------------------------------------------------------

CREATE TABLE leave_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    code VARCHAR(20) NOT NULL UNIQUE,
    description VARCHAR(255),
    is_active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE leave_entitlements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT UNSIGNED NOT NULL,
    leave_type_id INT UNSIGNED NOT NULL,
    year SMALLINT UNSIGNED NOT NULL,
    entitled_days SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    used_days SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id),
    UNIQUE KEY uq_entitlement (personnel_id, leave_type_id, year)
);

CREATE TABLE leave_applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_no VARCHAR(30) NOT NULL UNIQUE,   -- NPF-LV-2026-000001
    personnel_id INT UNSIGNED NOT NULL,
    leave_type_id INT UNSIGNED NOT NULL,

    previous_leave_date DATE DEFAULT NULL,   -- "Date Resumed Duty from Last Leave" on the paper form
    leave_due_date DATE DEFAULT NULL,
    days_requested SMALLINT UNSIGNED NOT NULL,

    accumulation_authority VARCHAR(255) DEFAULT NULL,
    recommended_by_name VARCHAR(150) DEFAULT NULL,
    recommendation_date DATE DEFAULT NULL,

    proceeding_date DATE NOT NULL,
    expected_resumption_date DATE DEFAULT NULL,

    home_town VARCHAR(120) DEFAULT NULL,
    leave_address VARCHAR(255) DEFAULT NULL,
    proceeding_with_wife TINYINT(1) DEFAULT NULL,

    remarks TEXT,   -- I-Admin's own note, entered at submission

    status ENUM('draft','pending_soa','pending_pso','approved','disapproved','cancelled','curtailed','completed')
        NOT NULL DEFAULT 'draft',

    submitted_by INT UNSIGNED DEFAULT NULL,
    submitted_at DATETIME DEFAULT NULL,

    -- Administration Officer (SOA) review stage
    soa_reviewed_by INT UNSIGNED DEFAULT NULL,
    soa_reviewed_at DATETIME DEFAULT NULL,
    soa_remarks TEXT,                                    -- mandatory when recommendation_decision = 'disapprove'
    recommendation_decision ENUM('approve','disapprove') DEFAULT NULL,
    assigned_reviewer_id INT UNSIGNED DEFAULT NULL,       -- the specific PSO I/II the SOA forwarded this to

    approved_by INT UNSIGNED DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,

    disapproved_by INT UNSIGNED DEFAULT NULL,
    disapproved_at DATETIME DEFAULT NULL,

    approval_remarks TEXT,                                -- PSO's own final remarks
    checked_by_name VARCHAR(150) DEFAULT NULL,             -- I-Admin, optional, at submission
    checked_up_to DATE DEFAULT NULL,                       -- I-Admin, optional, at submission

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (personnel_id) REFERENCES personnel(id),
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id),
    FOREIGN KEY (submitted_by) REFERENCES users(id),
    FOREIGN KEY (soa_reviewed_by) REFERENCES users(id),
    FOREIGN KEY (assigned_reviewer_id) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (disapproved_by) REFERENCES users(id),
    INDEX idx_leaveapp_status (status),
    INDEX idx_leaveapp_personnel (personnel_id),
    INDEX idx_leaveapp_assigned (assigned_reviewer_id)
);

-- Full approve/disapprove action history (supports multi-level approval later)
CREATE TABLE leave_approvals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    leave_application_id INT UNSIGNED NOT NULL,
    action ENUM('recommended_approve','recommended_disapprove','approved','disapproved') NOT NULL,
    acted_by INT UNSIGNED NOT NULL,
    remarks TEXT,
    acted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (leave_application_id) REFERENCES leave_applications(id) ON DELETE CASCADE,
    FOREIGN KEY (acted_by) REFERENCES users(id)
);

CREATE TABLE leave_resumptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    leave_application_id INT UNSIGNED NOT NULL UNIQUE,
    expected_resumption_date DATE DEFAULT NULL,
    actual_resumption_date DATE DEFAULT NULL,
    recorded_by INT UNSIGNED DEFAULT NULL,
    recorded_at DATETIME DEFAULT NULL,
    FOREIGN KEY (leave_application_id) REFERENCES leave_applications(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id)
);

-- Phase 2 stubs — status changes are recorded via leave_applications.status
-- + audit_logs; these tables hold the extra detail specific to each action.
CREATE TABLE leave_cancellations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    leave_application_id INT UNSIGNED NOT NULL,
    reason TEXT,
    cancelled_by INT UNSIGNED NOT NULL,
    cancelled_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (leave_application_id) REFERENCES leave_applications(id) ON DELETE CASCADE,
    FOREIGN KEY (cancelled_by) REFERENCES users(id)
);

CREATE TABLE leave_curtailments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    leave_application_id INT UNSIGNED NOT NULL,
    new_resumption_date DATE NOT NULL,
    reason TEXT,
    curtailed_by INT UNSIGNED NOT NULL,
    curtailed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (leave_application_id) REFERENCES leave_applications(id) ON DELETE CASCADE,
    FOREIGN KEY (curtailed_by) REFERENCES users(id)
);

CREATE TABLE signals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    leave_application_id INT UNSIGNED NOT NULL UNIQUE,
    signal_reference VARCHAR(40) NOT NULL UNIQUE,
    generated_by INT UNSIGNED NOT NULL,
    generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    printed_at DATETIME DEFAULT NULL,
    FOREIGN KEY (leave_application_id) REFERENCES leave_applications(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(id)
);

-- Supporting documents (e.g. a medical certificate for sick leave). Files
-- are stored outside the web-servable path and always served through
-- leave/attachment_download.php, which checks the requester is either the
-- submitter, an HOD/admin, or otherwise entitled — never linked directly.
CREATE TABLE leave_attachments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    leave_application_id INT UNSIGNED NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(64) NOT NULL UNIQUE,  -- random name on disk, unrelated to original_filename
    mime_type VARCHAR(100) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    uploaded_by INT UNSIGNED NOT NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (leave_application_id) REFERENCES leave_applications(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type ENUM('info','warning','danger','success') NOT NULL DEFAULT 'info',
    message VARCHAR(255) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- Audit / security
-- ---------------------------------------------------------------------

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    action VARCHAR(60) NOT NULL,           -- e.g. LEAVE_APPROVED, SIGNAL_PRINTED
    record_type VARCHAR(60) DEFAULT NULL,  -- e.g. leave_applications
    record_id INT UNSIGNED DEFAULT NULL,
    ip_address VARCHAR(45),
    description VARCHAR(255),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_audit_action (action),
    INDEX idx_audit_created (created_at)
);

CREATE TABLE login_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    username_attempted VARCHAR(60),
    success TINYINT(1) NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE system_settings (
    setting_key VARCHAR(60) PRIMARY KEY,
    setting_value VARCHAR(255)
);

-- =====================================================================
-- Seed data
-- =====================================================================

INSERT INTO roles (name, description) VALUES
('super_admin',   'Full system administration'),
('administrator', 'Manages personnel, lookup data, and reports'),
('i_admin',       'Receives leave applications from personnel, completes and submits the leave form'),
('administration_officer', 'Reviews submitted applications, records a recommendation, forwards to a PSO for final decision'),
('pso_i',         'Represents the Head of Department — final approve/disapprove authority'),
('pso_ii',        'Represents the Head of Department — final approve/disapprove authority'),
('read_only',     'Management/oversight — view only');

INSERT INTO leave_types (name, code, description) VALUES
('Annual Leave',        'ANNUAL',   'Standard yearly leave entitlement'),
('Proportionate Leave',  'PROP',     'Pro-rated leave for partial-year service'),
('Casual Leave',         'CASUAL',   'Short, informal leave'),
('Sick Leave',           'SICK',     'Leave for medical reasons'),
('Maternity Leave',      'MATERNITY','Leave for childbirth'),
('Paternity Leave',      'PATERNITY','Leave for new fathers'),
('Examination Leave',    'EXAM',     'Leave to sit examinations'),
('Study Leave',          'STUDY',    'Extended leave for study'),
('Sabbatical Leave',     'SABBATICAL','Extended leave for research/study'),
('Compassionate Leave',  'COMPASSIONATE', 'Leave for bereavement or family emergency'),
('Religious Leave',      'RELIGIOUS','Leave for religious observance/pilgrimage'),
('Leave of Absence',     'LOA',      'General leave of absence'),
('Pre-Retirement Leave', 'PRE_RETIREMENT', 'Leave preceding retirement');

INSERT INTO system_settings (setting_key, setting_value) VALUES
('app_name', 'NPF Leave Management System'),
('app_version', '0.2.0'),
('max_login_attempts', '5'),
('lockout_minutes', '15'),
('email_notifications_enabled', '0'),
('mail_driver', 'mail'),
('mail_from_address', 'no-reply@npf-lms.local'),
('mail_from_name', 'NPF Leave Management System'),
('cron_secret', ''),
('leave_calculation_mode', 'working_days');

-- ---------------------------------------------------------------------
-- Permissions & role_permissions — see database/migration_permissions_seed.sql
-- for the full explanation. Fresh installs get this seeded directly here
-- so require_permission() works immediately with no separate migration step.
-- ---------------------------------------------------------------------

INSERT INTO permissions (code, description) VALUES
('personnel.manage',        'Register, edit, and bulk-upload personnel records'),
('personnel.search',        'Search personnel to initiate a leave application'),
('leave.apply',              'Apply for leave on behalf of personnel (I-Admin), and record resumption'),
('leave.review_soa',         'Review a submitted application, record a recommendation, and forward it to a PSO (Administration Officer)'),
('leave.approve',            'Give the final approval or disapproval on a leave application (PSO I / PSO II)'),
('leave.cancel_curtail',     'Cancel or curtail a leave application'),
('administration.manage',    'Manage lookup data: ranks, departments, sections, commands, leave types, entitlements, public holidays'),
('administration.manage_users', 'Create accounts, assign roles, and manage role permissions'),
('audit.view',                'View the system audit log'),
('reports.view',              'View and export reports'),
('signals.view',              'View, print, and download Leave Approval Signals');

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.name = 'super_admin';

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.code IN (
    'personnel.manage', 'personnel.search', 'leave.apply', 'leave.review_soa', 'leave.approve',
    'leave.cancel_curtail', 'administration.manage', 'audit.view', 'reports.view', 'signals.view'
) WHERE r.name = 'administrator';

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.code IN (
    'personnel.search', 'leave.apply', 'leave.cancel_curtail', 'reports.view', 'signals.view'
) WHERE r.name = 'i_admin';

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.code IN (
    'leave.review_soa', 'leave.cancel_curtail', 'reports.view', 'signals.view'
) WHERE r.name = 'administration_officer';

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.code IN (
    'leave.approve', 'leave.cancel_curtail', 'reports.view', 'signals.view'
) WHERE r.name IN ('pso_i', 'pso_ii');

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.code IN ('reports.view', 'signals.view')
WHERE r.name = 'read_only';

-- ---------------------------------------------------------------------
-- Seed super administrator account
-- ---------------------------------------------------------------------
-- Do NOT insert a hand-typed hash — generate one on your own machine
-- (never trust a hash you didn't generate yourself) and paste it below:
--
--   php -r "echo password_hash('YourChosenPassword', PASSWORD_DEFAULT), PHP_EOL;"
--
-- INSERT INTO users (username, email, password_hash, full_name, status)
-- VALUES ('superadmin', 'superadmin@npf.gov.ng', '<PASTE_HASH_HERE>', 'System Administrator', 'active');
--
-- INSERT INTO user_roles (user_id, role_id)
-- SELECT u.id, r.id FROM users u, roles r
-- WHERE u.username = 'superadmin' AND r.name = 'super_admin';
