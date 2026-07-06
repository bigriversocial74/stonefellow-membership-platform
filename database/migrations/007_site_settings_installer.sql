-- Stonefellow migration 007: site settings, installer checks, and runtime configuration.
-- Apply after migrations 001 through 006.

CREATE TABLE IF NOT EXISTS site_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(120) NOT NULL UNIQUE,
  setting_value TEXT DEFAULT NULL,
  setting_group VARCHAR(80) NOT NULL DEFAULT 'site',
  is_public TINYINT(1) NOT NULL DEFAULT 0,
  updated_by_user_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_site_settings_group (setting_group),
  FOREIGN KEY (updated_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS system_installation_checks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  check_key VARCHAR(120) NOT NULL UNIQUE,
  check_label VARCHAR(190) NOT NULL,
  status ENUM('pass','warning','fail','skipped') NOT NULL DEFAULT 'warning',
  detail TEXT DEFAULT NULL,
  checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO site_settings (setting_key, setting_value, setting_group, is_public)
VALUES
('site_name', 'Stonefellow', 'site', 1),
('site_tagline', 'Watch the show. Stream the music. Wear the story.', 'site', 1),
('base_url', '', 'site', 1),
('support_email', 'support@stonefellow.tv', 'site', 1),
('admin_email', 'support@stonefellow.tv', 'site', 0),
('uploads_public_base', 'assets/', 'runtime', 1),
('maintenance_mode', '0', 'runtime', 1),
('member_signup_enabled', '1', 'runtime', 1),
('checkout_enabled', '1', 'runtime', 1),
('payment_provider', 'sandbox', 'payments', 1),
('stripe_publishable_key', '', 'payments', 1),
('paypal_client_id', '', 'payments', 1)
ON DUPLICATE KEY UPDATE setting_group = VALUES(setting_group), is_public = VALUES(is_public);
