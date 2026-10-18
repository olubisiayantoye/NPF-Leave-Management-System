-- =====================================================================
-- Migration: I-Admin → Administration Officer (SOA) → PSO I / PSO II
-- approval chain, replacing the old Sectional Head → HOD model.
--
-- Existing roles are RENAMED, not dropped, so every existing user_roles
-- assignment survives automatically:
--   sectional_head  -> i_admin
--   hod             -> administration_officer   (reassign specific
--                       accounts to pso_i / pso_ii afterward via
--                       Administration → Users — there's no way to
--                       auto-split one role into three correctly)
--
-- pso_i and pso_ii are brand new roles with nobody in them until you
-- assign someone.
--
-- Existing applications with status 'pending' are moved to
-- 'pending_soa' (the nearest equivalent — they were awaiting a review/
-- decision, which is now the SOA's stage).
--
-- Safe to run once against an existing npf_leave_management database.
-- =====================================================================

USE npf_leave_management;

-- ---------------------------------------------------------------------
-- Roles
-- ---------------------------------------------------------------------
UPDATE roles SET name = 'i_admin', description = 'Receives leave applications from personnel, completes and submits the leave form'
    WHERE name = 'sectional_head';
UPDATE roles SET name = 'administration_officer', description = 'Reviews submitted applications, records a recommendation, forwards to a PSO for final decision'
    WHERE name = 'hod';

INSERT INTO roles (name, description)
SELECT 'pso_i', 'Represents the Head of Department — final approve/disapprove authority'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE name = 'pso_i');

INSERT INTO roles (name, description)
SELECT 'pso_ii', 'Represents the Head of Department — final approve/disapprove authority'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE name = 'pso_ii');

-- ---------------------------------------------------------------------
-- public_holidays
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public_holidays (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    holiday_date DATE NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL
);

-- ---------------------------------------------------------------------
-- leave_applications: new status values + SOA review columns
-- ---------------------------------------------------------------------

-- Step 1: widen the enum so both old and new values are valid at once.
ALTER TABLE leave_applications MODIFY status
    ENUM('draft','pending','pending_soa','pending_pso','approved','disapproved','cancelled','curtailed','completed')
    NOT NULL DEFAULT 'draft';

-- Step 2: move existing 'pending' rows to the new equivalent stage.
UPDATE leave_applications SET status = 'pending_soa' WHERE status = 'pending';

-- Step 3: narrow the enum again now nothing uses the old value.
ALTER TABLE leave_applications MODIFY status
    ENUM('draft','pending_soa','pending_pso','approved','disapproved','cancelled','curtailed','completed')
    NOT NULL DEFAULT 'draft';

ALTER TABLE leave_applications
    ADD COLUMN soa_reviewed_by INT UNSIGNED DEFAULT NULL AFTER submitted_at,
    ADD COLUMN soa_reviewed_at DATETIME DEFAULT NULL AFTER soa_reviewed_by,
    ADD COLUMN soa_remarks TEXT AFTER soa_reviewed_at,
    ADD COLUMN recommendation_decision ENUM('approve','disapprove') DEFAULT NULL AFTER soa_remarks,
    ADD COLUMN assigned_reviewer_id INT UNSIGNED DEFAULT NULL AFTER recommendation_decision,
    ADD CONSTRAINT fk_la_soa_reviewed_by FOREIGN KEY (soa_reviewed_by) REFERENCES users(id),
    ADD CONSTRAINT fk_la_assigned_reviewer FOREIGN KEY (assigned_reviewer_id) REFERENCES users(id),
    ADD INDEX idx_leaveapp_assigned (assigned_reviewer_id);

ALTER TABLE leave_approvals MODIFY action
    ENUM('recommended_approve','recommended_disapprove','approved','disapproved') NOT NULL;

-- ---------------------------------------------------------------------
-- Permissions: add leave.review_soa, rename role grants to match
-- ---------------------------------------------------------------------
INSERT INTO permissions (code, description)
SELECT 'leave.review_soa', 'Review a submitted application, record a recommendation, and forward it to a PSO (Administration Officer)'
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code = 'leave.review_soa');

-- administration_officer (renamed from hod) previously had leave.approve —
-- that authority now belongs to PSO I/II instead, and the SOA gets the new
-- review permission.
DELETE rp FROM role_permissions rp
JOIN roles r ON r.id = rp.role_id
JOIN permissions p ON p.id = rp.permission_id
WHERE r.name = 'administration_officer' AND p.code = 'leave.approve';

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'administration_officer' AND p.code = 'leave.review_soa'
  AND NOT EXISTS (SELECT 1 FROM role_permissions WHERE role_id = r.id AND permission_id = p.id);

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.code IN ('leave.approve', 'leave.cancel_curtail', 'reports.view', 'signals.view')
WHERE r.name IN ('pso_i', 'pso_ii')
  AND NOT EXISTS (SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.permission_id = p.id);

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'super_admin' AND p.code = 'leave.review_soa'
  AND NOT EXISTS (SELECT 1 FROM role_permissions WHERE role_id = r.id AND permission_id = p.id);

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'administrator' AND p.code = 'leave.review_soa'
  AND NOT EXISTS (SELECT 1 FROM role_permissions WHERE role_id = r.id AND permission_id = p.id);

-- ---------------------------------------------------------------------
-- Leave calculation default: Saturdays, Sundays AND public holidays
-- excluded, not just Sundays.
-- ---------------------------------------------------------------------
INSERT INTO system_settings (setting_key, setting_value) VALUES ('leave_calculation_mode', 'working_days')
ON DUPLICATE KEY UPDATE setting_value = 'working_days';
