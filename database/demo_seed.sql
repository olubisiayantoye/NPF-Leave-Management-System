-- =====================================================================
-- NPF-LMS — DEMO / SAMPLE DATA
-- Run this AFTER database/npf_leave_management.sql has been imported.
--
-- Working logins for every role in the I-Admin -> Administration
-- Officer (SOA) -> PSO I / PSO II chain, personnel across several
-- departments, and leave applications at every stage of that chain.
--
-- ⚠ DEMO DATA ONLY. All demo accounts share the password below —
--   change every password (or drop these accounts entirely) before
--   this ever touches a production deployment.
--
--   Demo password for every account: Demo@12345
-- =====================================================================

USE npf_leave_management;

-- ---------------------------------------------------------------------
-- Ranks (seniority order)
-- ---------------------------------------------------------------------
INSERT INTO ranks (name, sort_order) VALUES
('Inspector General of Police', 1),
('Deputy Inspector General',    2),
('Assistant Inspector General', 3),
('Commissioner of Police',      4),
('Deputy Commissioner of Police', 5),
('Assistant Commissioner of Police', 6),
('Chief Superintendent of Police', 7),
('Superintendent of Police',    8),
('Deputy Superintendent of Police', 9),
('Assistant Superintendent of Police', 10),
('Inspector',                   11),
('Sergeant',                    12),
('Corporal',                    13),
('Constable',                   14);

-- ---------------------------------------------------------------------
-- Departments, Sections, Commands
-- ---------------------------------------------------------------------
INSERT INTO departments (name) VALUES
('IGP Secretariat'),
('Force Criminal Investigation Department'),
('Operations Department'),
('Signals & Telecommunications'),
('Legal Department'),
('Finance & Administration'),
('ICT Department'),
('Training & Development Department');

INSERT INTO sections (name, department_id) VALUES
('Special Duties',   (SELECT id FROM departments WHERE name = 'IGP Secretariat')),
('Protocol',         (SELECT id FROM departments WHERE name = 'IGP Secretariat')),
('Homicide Section',  (SELECT id FROM departments WHERE name = 'Force Criminal Investigation Department')),
('Fraud Section',     (SELECT id FROM departments WHERE name = 'Force Criminal Investigation Department')),
('Anti-Robbery',      (SELECT id FROM departments WHERE name = 'Operations Department')),
('Traffic',           (SELECT id FROM departments WHERE name = 'Operations Department')),
('Network Operations',(SELECT id FROM departments WHERE name = 'ICT Department'));

INSERT INTO commands (name) VALUES
('Lagos State Command'),
('FCT Abuja Command'),
('Rivers State Command'),
('Kano State Command'),
('Oyo State Command');

-- ---------------------------------------------------------------------
-- Public holidays — excluded, with Saturdays and Sundays, from
-- leave-day calculation
-- ---------------------------------------------------------------------
INSERT INTO public_holidays (holiday_date, name) VALUES
(DATE_FORMAT(CURDATE(), '%Y-01-01'), 'New Year''s Day'),
(DATE_FORMAT(CURDATE(), '%Y-05-01'), 'Workers'' Day'),
(DATE_FORMAT(CURDATE(), '%Y-06-12'), 'Democracy Day'),
(DATE_FORMAT(CURDATE(), '%Y-10-01'), 'Independence Day'),
(DATE_FORMAT(CURDATE(), '%Y-12-25'), 'Christmas Day');

-- ---------------------------------------------------------------------
-- Demo users (all roles) — password for every account: Demo@12345
-- Hash below is a real bcrypt hash (verified against PHP's
-- password_verify-compatible $2b$ format) — not a placeholder.
-- ---------------------------------------------------------------------
SET @demo_hash := '$2b$10$fz9hx/zvelCu41NYHR25WuYLG74Nhiqs74gp.d.VtBQBfuE0sZIti';

INSERT INTO users (username, email, password_hash, full_name, status) VALUES
('superadmin', 'superadmin@npf.gov.ng', @demo_hash, 'System Administrator', 'active'),
('j.bello',    'j.bello@npf.gov.ng',    @demo_hash, 'John Bello',           'active'),
('a.musa',     'a.musa@npf.gov.ng',     @demo_hash, 'Amina Musa',           'active'),
('o.kalu',     'o.kalu@npf.gov.ng',     @demo_hash, 'Okafor Kalu',          'active'),
('t.yaro',     't.yaro@npf.gov.ng',     @demo_hash, 'Tanko Yaro',           'active'),
('b.nnamdi',   'b.nnamdi@npf.gov.ng',   @demo_hash, 'Blessing Nnamdi',      'active'),
('c.eze',      'c.eze@npf.gov.ng',      @demo_hash, 'Chidi Eze',            'active');

INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u, roles r WHERE u.username = 'superadmin' AND r.name = 'super_admin';
INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u, roles r WHERE u.username = 'j.bello' AND r.name = 'administrator';
INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u, roles r WHERE u.username = 'a.musa' AND r.name = 'i_admin';
INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u, roles r WHERE u.username = 'o.kalu' AND r.name = 'administration_officer';
INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u, roles r WHERE u.username = 't.yaro' AND r.name = 'pso_i';
INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u, roles r WHERE u.username = 'b.nnamdi' AND r.name = 'pso_ii';
INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u, roles r WHERE u.username = 'c.eze' AND r.name = 'read_only';

-- ---------------------------------------------------------------------
-- Personnel
-- ---------------------------------------------------------------------
SET @r_insp   := (SELECT id FROM ranks WHERE name = 'Inspector');
SET @r_asp    := (SELECT id FROM ranks WHERE name = 'Assistant Superintendent of Police');
SET @r_dsp    := (SELECT id FROM ranks WHERE name = 'Deputy Superintendent of Police');
SET @r_sp     := (SELECT id FROM ranks WHERE name = 'Superintendent of Police');
SET @r_sgt    := (SELECT id FROM ranks WHERE name = 'Sergeant');
SET @r_cpl    := (SELECT id FROM ranks WHERE name = 'Corporal');
SET @r_const  := (SELECT id FROM ranks WHERE name = 'Constable');
SET @r_csp    := (SELECT id FROM ranks WHERE name = 'Chief Superintendent of Police');

SET @d_igp    := (SELECT id FROM departments WHERE name = 'IGP Secretariat');
SET @d_fcid   := (SELECT id FROM departments WHERE name = 'Force Criminal Investigation Department');
SET @d_ops    := (SELECT id FROM departments WHERE name = 'Operations Department');
SET @d_ict    := (SELECT id FROM departments WHERE name = 'ICT Department');
SET @d_legal  := (SELECT id FROM departments WHERE name = 'Legal Department');
SET @d_fin    := (SELECT id FROM departments WHERE name = 'Finance & Administration');

SET @s_special := (SELECT id FROM sections WHERE name = 'Special Duties');
SET @s_homicide := (SELECT id FROM sections WHERE name = 'Homicide Section');
SET @s_antirobbery := (SELECT id FROM sections WHERE name = 'Anti-Robbery');
SET @s_netops := (SELECT id FROM sections WHERE name = 'Network Operations');

SET @c_lagos  := (SELECT id FROM commands WHERE name = 'Lagos State Command');
SET @c_fct    := (SELECT id FROM commands WHERE name = 'FCT Abuja Command');
SET @c_rivers := (SELECT id FROM commands WHERE name = 'Rivers State Command');

INSERT INTO personnel
    (f_ap_number, first_name, middle_name, surname, gender, date_of_birth,
     rank_id, department_id, section_id, command_id, date_of_first_appointment,
     phone, email, status) VALUES
('315443', 'Adewale', NULL, 'Bello', 'M', '1988-04-12', @r_insp, @d_igp, @s_special, @c_lagos, '2012-01-15', '08012345001', 'a.bello.315443@npf.gov.ng', 'active'),
('318220', 'Ngozi', 'Chinwe', 'Okafor', 'F', '1990-09-03', @r_asp, @d_fcid, @s_homicide, @c_lagos, '2010-06-01', '08012345002', 'n.okafor.318220@npf.gov.ng', 'active'),
('321987', 'Ibrahim', NULL, 'Suleiman', 'M', '1985-01-20', @r_dsp, @d_ops, @s_antirobbery, @c_fct, '2006-03-10', '08012345003', 'i.suleiman.321987@npf.gov.ng', 'active'),
('324556', 'Blessing', 'Ada', 'Nwosu', 'F', '1992-11-08', @r_sgt, @d_fin, NULL, @c_lagos, '2015-08-22', '08012345004', 'b.nwosu.324556@npf.gov.ng', 'active'),
('327781', 'Emeka', NULL, 'Chukwu', 'M', '1983-07-17', @r_sp, @d_fcid, NULL, @c_rivers, '2004-02-14', '08012345005', 'e.chukwu.327781@npf.gov.ng', 'active'),
('330012', 'Fatima', NULL, 'Abdullahi', 'F', '1994-03-29', @r_cpl, @d_ops, NULL, @c_fct, '2017-05-05', '08012345006', 'f.abdullahi.330012@npf.gov.ng', 'active'),
('332290', 'Tunde', 'Michael', 'Adeyemi', 'M', '1987-12-01', @r_insp, @d_ict, @s_netops, @c_lagos, '2011-09-19', '08012345007', 't.adeyemi.332290@npf.gov.ng', 'active'),
('335674', 'Grace', NULL, 'Effiong', 'F', '1991-06-14', @r_const, @d_igp, NULL, @c_lagos, '2018-01-30', '08012345008', 'g.effiong.335674@npf.gov.ng', 'active'),
('338901', 'Chinedu', NULL, 'Okoro', 'M', '1980-10-05', @r_csp, @d_legal, NULL, @c_fct, '2001-04-11', '08012345009', 'c.okoro.338901@npf.gov.ng', 'active'),
('341123', 'Halima', 'Bintu', 'Yusuf', 'F', '1989-02-27', @r_asp, @d_ops, @s_antirobbery, @c_lagos, '2013-07-08', '08012345010', 'h.yusuf.341123@npf.gov.ng', 'active');

-- ---------------------------------------------------------------------
-- Leave entitlements — current year, Annual + Casual for everyone
-- ---------------------------------------------------------------------
SET @lt_annual := (SELECT id FROM leave_types WHERE code = 'ANNUAL');
SET @lt_casual := (SELECT id FROM leave_types WHERE code = 'CASUAL');
SET @this_year := YEAR(CURDATE());

INSERT INTO leave_entitlements (personnel_id, leave_type_id, year, entitled_days, used_days)
SELECT id, @lt_annual, @this_year, 30, 0 FROM personnel;
INSERT INTO leave_entitlements (personnel_id, leave_type_id, year, entitled_days, used_days)
SELECT id, @lt_casual, @this_year, 7, 0 FROM personnel;

-- ---------------------------------------------------------------------
-- Users referenced by the applications below
-- ---------------------------------------------------------------------
SET @u_iadmin := (SELECT id FROM users WHERE username = 'a.musa');
SET @u_soa    := (SELECT id FROM users WHERE username = 'o.kalu');
SET @u_pso1   := (SELECT id FROM users WHERE username = 't.yaro');
SET @u_pso2   := (SELECT id FROM users WHERE username = 'b.nnamdi');

-- ---------------------------------------------------------------------
-- Leave applications — one at every stage of the I-Admin -> SOA -> PSO
-- chain
-- ---------------------------------------------------------------------

-- 1. PENDING_SOA — just submitted by I-Admin, awaiting Administration
--    Officer review (Bello, Annual)
INSERT INTO leave_applications
    (application_no, personnel_id, leave_type_id, days_requested, proceeding_date,
     expected_resumption_date, home_town, leave_address, proceeding_with_wife,
     checked_by_name, checked_up_to, status, submitted_by, submitted_at)
VALUES
    ('NPF-LV-DEMO-000001', (SELECT id FROM personnel WHERE f_ap_number = '315443'), @lt_annual, 14,
     DATE_ADD(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 26 DAY),
     'Ibadan', '14 Ogunlana Drive, Ibadan', 1,
     'Inspr. Darlington Mike', DATE_ADD(CURDATE(), INTERVAL 90 DAY),
     'pending_soa', @u_iadmin, NOW());

-- 2. PENDING_SOA — just submitted, awaiting review (Nwosu, Casual)
INSERT INTO leave_applications
    (application_no, personnel_id, leave_type_id, days_requested, proceeding_date,
     expected_resumption_date, home_town, leave_address, status, submitted_by, submitted_at)
VALUES
    ('NPF-LV-DEMO-000002', (SELECT id FROM personnel WHERE f_ap_number = '324556'), @lt_casual, 3,
     DATE_ADD(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 8 DAY),
     'Owerri', '9 Wetheral Road, Owerri', 'pending_soa', @u_iadmin, NOW());

-- 3. DRAFT — not yet submitted (Adeyemi)
INSERT INTO leave_applications
    (application_no, personnel_id, leave_type_id, days_requested, proceeding_date,
     home_town, status, submitted_by)
VALUES
    ('NPF-LV-DEMO-000003', (SELECT id FROM personnel WHERE f_ap_number = '332290'), @lt_annual, 21,
     DATE_ADD(CURDATE(), INTERVAL 40 DAY), 'Akure', 'draft', @u_iadmin);

-- 4. PENDING_PSO — reviewed by the Administration Officer, who
--    recommends approval and forwards to PSO II (Abdullahi, Casual)
INSERT INTO leave_applications
    (application_no, personnel_id, leave_type_id, days_requested, proceeding_date,
     expected_resumption_date, home_town, status, submitted_by, submitted_at,
     soa_reviewed_by, soa_reviewed_at, soa_remarks, recommendation_decision,
     recommended_by_name, recommendation_date, assigned_reviewer_id)
VALUES
    ('NPF-LV-DEMO-000004', (SELECT id FROM personnel WHERE f_ap_number = '330012'), @lt_casual, 2,
     DATE_ADD(CURDATE(), INTERVAL 4 DAY), DATE_ADD(CURDATE(), INTERVAL 6 DAY),
     'Sokoto', 'pending_pso', @u_iadmin, DATE_SUB(NOW(), INTERVAL 1 DAY),
     @u_soa, NOW(), 'Routine casual leave request, no operational concerns.', 'approve',
     'Okafor Kalu', CURDATE(), @u_pso2);

INSERT INTO leave_approvals (leave_application_id, action, acted_by, remarks)
SELECT id, 'recommended_approve', @u_soa, soa_remarks FROM leave_applications WHERE application_no = 'NPF-LV-DEMO-000004';

-- 5. DISAPPROVED — Administration Officer recommended disapproval
--    (remarks mandatory), PSO I concurred (Yusuf, Annual — over balance)
INSERT INTO leave_applications
    (application_no, personnel_id, leave_type_id, days_requested, proceeding_date,
     expected_resumption_date, home_town, status, submitted_by, submitted_at,
     soa_reviewed_by, soa_reviewed_at, soa_remarks, recommendation_decision,
     recommended_by_name, recommendation_date, assigned_reviewer_id,
     disapproved_by, disapproved_at, approval_remarks)
VALUES
    ('NPF-LV-DEMO-000005', (SELECT id FROM personnel WHERE f_ap_number = '341123'), @lt_annual, 30,
     DATE_ADD(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 35 DAY),
     'Kaduna', 'disapproved', @u_iadmin, DATE_SUB(NOW(), INTERVAL 3 DAY),
     @u_soa, DATE_SUB(NOW(), INTERVAL 2 DAY),
     'Operational strength in Anti-Robbery Section is already reduced this month — recommend she resubmits for next month.',
     'disapprove', 'Okafor Kalu', DATE_SUB(CURDATE(), INTERVAL 2 DAY), @u_pso1,
     @u_pso1, NOW(), 'Concur with Administration Officer — disapproved, please resubmit for next month.');

INSERT INTO leave_approvals (leave_application_id, action, acted_by, remarks)
SELECT id, 'recommended_disapprove', @u_soa, soa_remarks FROM leave_applications WHERE application_no = 'NPF-LV-DEMO-000005';
INSERT INTO leave_approvals (leave_application_id, action, acted_by, remarks)
SELECT id, 'disapproved', @u_pso1, approval_remarks FROM leave_applications WHERE application_no = 'NPF-LV-DEMO-000005';

-- 6. APPROVED — currently on leave, signal generated (Chukwu). Full
--    chain: I-Admin -> SOA recommends approval -> PSO II approves.
INSERT INTO leave_applications
    (application_no, personnel_id, leave_type_id, days_requested, proceeding_date,
     expected_resumption_date, previous_leave_date, home_town, leave_address, proceeding_with_wife,
     status, submitted_by, submitted_at,
     soa_reviewed_by, soa_reviewed_at, soa_remarks, recommendation_decision,
     recommended_by_name, recommendation_date, assigned_reviewer_id,
     approved_by, approved_at, approval_remarks,
     checked_by_name, checked_up_to)
VALUES
    ('NPF-LV-DEMO-000006', (SELECT id FROM personnel WHERE f_ap_number = '327781'), @lt_annual, 21,
     DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 16 DAY),
     DATE_SUB(CURDATE(), INTERVAL 300 DAY), 'Port Harcourt', '22 Aba Road, Port Harcourt', 1,
     'approved', @u_iadmin, DATE_SUB(NOW(), INTERVAL 8 DAY),
     @u_soa, DATE_SUB(NOW(), INTERVAL 7 DAY), 'No concerns, recommend approval.', 'approve',
     'Okafor Kalu', DATE_SUB(CURDATE(), INTERVAL 7 DAY), @u_pso2,
     @u_pso2, DATE_SUB(NOW(), INTERVAL 6 DAY), 'Approved — resumption to be confirmed on schedule.',
     'Inspr. Darlington Mike', DATE_ADD(CURDATE(), INTERVAL 90 DAY));

SET @app6 := (SELECT id FROM leave_applications WHERE application_no = 'NPF-LV-DEMO-000006');
INSERT INTO leave_approvals (leave_application_id, action, acted_by, remarks)
VALUES (@app6, 'recommended_approve', @u_soa, 'No concerns, recommend approval.');
INSERT INTO leave_approvals (leave_application_id, action, acted_by, remarks)
VALUES (@app6, 'approved', @u_pso2, 'Approved — resumption to be confirmed on schedule.');
INSERT INTO leave_resumptions (leave_application_id, expected_resumption_date)
SELECT id, expected_resumption_date FROM leave_applications WHERE id = @app6;
INSERT INTO signals (leave_application_id, signal_reference, generated_by, generated_at)
VALUES (@app6, 'NPF-SGL-DEMO-000001', @u_pso2, DATE_SUB(NOW(), INTERVAL 6 DAY));
UPDATE personnel SET status = 'on_leave' WHERE id = (SELECT personnel_id FROM leave_applications WHERE id = @app6);
UPDATE leave_entitlements SET used_days = used_days + 21
    WHERE personnel_id = (SELECT personnel_id FROM leave_applications WHERE id = @app6) AND leave_type_id = @lt_annual AND year = @this_year;

-- 7. COMPLETED — full lifecycle including resumption recorded (Okafor)
INSERT INTO leave_applications
    (application_no, personnel_id, leave_type_id, days_requested, proceeding_date,
     expected_resumption_date, previous_leave_date, home_town, leave_address, proceeding_with_wife,
     status, submitted_by, submitted_at,
     soa_reviewed_by, soa_reviewed_at, soa_remarks, recommendation_decision,
     recommended_by_name, recommendation_date, assigned_reviewer_id,
     approved_by, approved_at, approval_remarks,
     checked_by_name, checked_up_to)
VALUES
    ('NPF-LV-DEMO-000007', (SELECT id FROM personnel WHERE f_ap_number = '318220'), @lt_annual, 10,
     DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_SUB(CURDATE(), INTERVAL 20 DAY),
     DATE_SUB(CURDATE(), INTERVAL 395 DAY), 'Enugu', '5 Zik Avenue, Enugu', 0,
     'completed', @u_iadmin, DATE_SUB(NOW(), INTERVAL 33 DAY),
     @u_soa, DATE_SUB(NOW(), INTERVAL 32 DAY), 'Approved for recommendation.', 'approve',
     'Okafor Kalu', DATE_SUB(CURDATE(), INTERVAL 32 DAY), @u_pso1,
     @u_pso1, DATE_SUB(NOW(), INTERVAL 31 DAY), 'Approved.',
     'Inspr. Darlington Mike', DATE_SUB(CURDATE(), INTERVAL 15 DAY));

SET @app7 := (SELECT id FROM leave_applications WHERE application_no = 'NPF-LV-DEMO-000007');
INSERT INTO leave_approvals (leave_application_id, action, acted_by, remarks)
VALUES (@app7, 'recommended_approve', @u_soa, 'Approved for recommendation.');
INSERT INTO leave_approvals (leave_application_id, action, acted_by, remarks)
VALUES (@app7, 'approved', @u_pso1, 'Approved.');
INSERT INTO leave_resumptions (leave_application_id, expected_resumption_date, actual_resumption_date, recorded_by, recorded_at)
SELECT id, expected_resumption_date, expected_resumption_date, @u_iadmin, DATE_SUB(NOW(), INTERVAL 20 DAY)
FROM leave_applications WHERE id = @app7;
INSERT INTO signals (leave_application_id, signal_reference, generated_by, generated_at, printed_at)
VALUES (@app7, 'NPF-SGL-DEMO-000002', @u_pso1, DATE_SUB(NOW(), INTERVAL 31 DAY), DATE_SUB(NOW(), INTERVAL 30 DAY));
UPDATE leave_entitlements SET used_days = used_days + 10
    WHERE personnel_id = (SELECT personnel_id FROM leave_applications WHERE id = @app7) AND leave_type_id = @lt_annual AND year = @this_year;

-- ---------------------------------------------------------------------
-- A few standalone audit/login entries so the Audit Log isn't empty
-- ---------------------------------------------------------------------
INSERT INTO audit_logs (user_id, action, record_type, record_id, ip_address, description) VALUES
(@u_iadmin, 'LEAVE_APPLICATION_SUBMITTED', 'leave_applications', @app6, '127.0.0.1', 'Demo data seed'),
(@u_soa,    'LEAVE_SOA_REVIEWED',          'leave_applications', @app6, '127.0.0.1', 'Demo data seed'),
(@u_pso2,   'LEAVE_APPROVED',              'leave_applications', @app6, '127.0.0.1', 'Demo data seed'),
(@u_pso2,   'SIGNAL_GENERATED',            'signals', NULL, '127.0.0.1', 'Demo data seed');
