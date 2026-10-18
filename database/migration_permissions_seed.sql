-- =====================================================================
-- Migration: seed permissions + role_permissions.
--
-- These tables existed in the schema from the start but nothing ever
-- populated or enforced them — every page checked a hardcoded role
-- name instead. This migration seeds real permission codes and maps
-- them to the 5 built-in roles EXACTLY matching what each role could
-- already do, so applying this migration changes nothing about who
-- can access what. What it does change: administration/roles.php can
-- now actually reassign what administrator/sectional_head/hod/
-- read_only are allowed to do, because every protected page now
-- checks these tables via require_permission() instead of a
-- hardcoded role list.
--
-- (super_admin is intentionally not editable via the UI — it always
-- gets every permission, to make it structurally impossible to lock
-- yourself out of the system.)
--
-- Safe to run once against an existing npf_leave_management database.
-- =====================================================================

USE npf_leave_management;

INSERT INTO permissions (code, description) VALUES
('personnel.manage',        'Register, edit, and bulk-upload personnel records'),
('personnel.search',        'Search personnel to initiate a leave application'),
('leave.apply',              'Apply for leave on behalf of personnel, and record resumption'),
('leave.approve',            'Approve or disapprove leave applications'),
('leave.cancel_curtail',     'Cancel or curtail a leave application'),
('administration.manage',    'Manage lookup data: ranks, departments, sections, commands, leave types, entitlements'),
('administration.manage_users', 'Create accounts, assign roles, and manage role permissions'),
('audit.view',                'View the system audit log'),
('reports.view',              'View and export reports'),
('signals.view',              'View, print, and download Leave Approval Signals')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- super_admin — every permission, always (enforced again in code, not just here)
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.name = 'super_admin'
ON DUPLICATE KEY UPDATE role_id = role_id;

-- administrator — everything except managing user accounts/roles
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.code IN (
    'personnel.manage', 'personnel.search', 'leave.apply', 'leave.approve',
    'leave.cancel_curtail', 'administration.manage', 'audit.view', 'reports.view', 'signals.view'
) WHERE r.name = 'administrator'
ON DUPLICATE KEY UPDATE role_id = role_id;

-- sectional_head — initiate/manage leave applications
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.code IN (
    'personnel.search', 'leave.apply', 'leave.cancel_curtail', 'reports.view', 'signals.view'
) WHERE r.name = 'sectional_head'
ON DUPLICATE KEY UPDATE role_id = role_id;

-- hod — review and decide on applications
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.code IN (
    'leave.approve', 'leave.cancel_curtail', 'reports.view', 'signals.view'
) WHERE r.name = 'hod'
ON DUPLICATE KEY UPDATE role_id = role_id;

-- read_only — view only
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.code IN ('reports.view', 'signals.view')
WHERE r.name = 'read_only'
ON DUPLICATE KEY UPDATE role_id = role_id;
