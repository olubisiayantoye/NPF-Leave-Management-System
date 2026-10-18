-- =====================================================================
-- Migration: bring leave_applications in line with the real NPF
-- "Application for Leave" paper form — adds the recommending officer,
-- recommendation date, and the checked-by / checked-up-to fields used
-- during approval sign-off.
--
-- previous_leave_date is NOT renamed — its column name is unchanged,
-- but as of this update the app populates it with what the paper form
-- calls "Date Resumed Duty from Last Leave" (the officer's actual
-- resumption date from their last completed leave) rather than a
-- proceeding date, which is the more accurate reading of that field.
-- No schema change was needed for that part.
--
-- Safe to run once against an existing npf_leave_management database.
-- =====================================================================

USE npf_leave_management;

ALTER TABLE leave_applications
    ADD COLUMN recommended_by_name VARCHAR(150) DEFAULT NULL AFTER accumulation_authority,
    ADD COLUMN recommendation_date DATE DEFAULT NULL AFTER recommended_by_name,
    ADD COLUMN checked_by_name VARCHAR(150) DEFAULT NULL AFTER approval_remarks,
    ADD COLUMN checked_up_to DATE DEFAULT NULL AFTER checked_by_name;
