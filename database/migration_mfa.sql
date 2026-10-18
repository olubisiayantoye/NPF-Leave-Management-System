-- =====================================================================
-- Migration: add TOTP two-factor authentication columns to users
-- Safe to run once against an existing npf_leave_management database.
-- (New installs get these columns automatically via npf_leave_management.sql.)
-- =====================================================================

USE npf_leave_management;

ALTER TABLE users
    ADD COLUMN mfa_secret VARCHAR(32) DEFAULT NULL AFTER locked_until,
    ADD COLUMN mfa_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER mfa_secret;
