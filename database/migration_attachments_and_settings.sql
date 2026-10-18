-- =====================================================================
-- Migration: leave attachments table + email/cron system settings.
-- Safe to run once against an existing npf_leave_management database.
-- =====================================================================

USE npf_leave_management;

CREATE TABLE IF NOT EXISTS leave_attachments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    leave_application_id INT UNSIGNED NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(64) NOT NULL UNIQUE,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    uploaded_by INT UNSIGNED NOT NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (leave_application_id) REFERENCES leave_applications(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

INSERT INTO system_settings (setting_key, setting_value) VALUES
('email_notifications_enabled', '0'),
('mail_driver', 'mail'),
('mail_from_address', 'no-reply@npf-lms.local'),
('mail_from_name', 'NPF Leave Management System'),
('cron_secret', '')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
