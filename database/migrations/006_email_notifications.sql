-- Stonefellow Email + Notification Runtime v1
-- Apply after base SQL and migrations 001 through 005.

CREATE TABLE IF NOT EXISTS email_templates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  template_key VARCHAR(120) NOT NULL UNIQUE,
  name VARCHAR(190) NOT NULL,
  category VARCHAR(80) NOT NULL DEFAULT 'transactional',
  subject VARCHAR(255) NOT NULL,
  html_body MEDIUMTEXT NOT NULL,
  text_body MEDIUMTEXT NULL,
  variables_json JSON NULL,
  status ENUM('active','draft','archived') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  last_sent_at TIMESTAMP NULL,
  INDEX idx_email_templates_category_status (category, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  recipient_email VARCHAR(190) NOT NULL,
  recipient_name VARCHAR(190) NULL,
  channel ENUM('email','sms','in_app','webhook') NOT NULL DEFAULT 'email',
  notification_type VARCHAR(80) NOT NULL DEFAULT 'transactional',
  template_key VARCHAR(120) NULL,
  subject VARCHAR(255) NULL,
  rendered_html MEDIUMTEXT NULL,
  rendered_text MEDIUMTEXT NULL,
  body_preview VARCHAR(500) NULL,
  status ENUM('queued','sent','failed','skipped','canceled') NOT NULL DEFAULT 'queued',
  provider VARCHAR(80) NOT NULL DEFAULT 'log',
  provider_message_id VARCHAR(190) NULL,
  attempts INT UNSIGNED NOT NULL DEFAULT 0,
  error_message TEXT NULL,
  metadata_json JSON NULL,
  scheduled_at TIMESTAMP NULL,
  sent_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_notification_logs_user (user_id),
  INDEX idx_notification_logs_status_schedule (status, scheduled_at, created_at),
  INDEX idx_notification_logs_template (template_key),
  INDEX idx_notification_logs_recipient (recipient_email),
  INDEX idx_notification_logs_provider_message (provider, provider_message_id),
  CONSTRAINT fk_notification_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_preferences (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  preference_key VARCHAR(120) NOT NULL,
  channel ENUM('email','sms','in_app','webhook') NOT NULL DEFAULT 'email',
  is_enabled TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_notification_preference (user_id, preference_key, channel),
  CONSTRAINT fk_notification_preferences_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_webhook_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider VARCHAR(80) NOT NULL DEFAULT 'unknown',
  event_type VARCHAR(120) NOT NULL,
  provider_event_id VARCHAR(190) NULL,
  provider_message_id VARCHAR(190) NULL,
  notification_log_id BIGINT UNSIGNED NULL,
  status ENUM('received','processed','failed','ignored') NOT NULL DEFAULT 'received',
  raw_payload_json JSON NULL,
  error_message TEXT NULL,
  processed_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_notification_webhook_event (provider, provider_event_id),
  INDEX idx_notification_webhook_message (provider_message_id),
  INDEX idx_notification_webhook_status (status, created_at),
  CONSTRAINT fk_notification_webhook_log FOREIGN KEY (notification_log_id) REFERENCES notification_logs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default transactional templates are also seedable from admin/email-templates.php.
INSERT INTO email_templates (template_key, name, category, subject, html_body, text_body, variables_json, status)
VALUES
('welcome', 'Welcome Email', 'auth', 'Welcome to {{site_name}}', '<h1>Welcome to {{site_name}}, {{recipient_name}}.</h1><p>Your account is active. You can now stream music, watch episodes, build playlists, and follow the Stonefellow story.</p><p><a href="{{member_url}}">Open your member dashboard</a></p>', 'Welcome to {{site_name}}, {{recipient_name}}. Open your member dashboard: {{member_url}}', JSON_ARRAY('site_name','recipient_name','member_url'), 'active'),
('password_reset', 'Password Reset', 'auth', 'Reset your {{site_name}} password', '<h1>Password reset request</h1><p>Use this secure link within {{expires_minutes}} minutes:</p><p><a href="{{reset_url}}">Reset password</a></p><p>If you did not request this, you can ignore this message.</p>', 'Reset your {{site_name}} password within {{expires_minutes}} minutes: {{reset_url}}', JSON_ARRAY('site_name','expires_minutes','reset_url'), 'active'),
('subscription_started', 'Subscription Started', 'billing', 'Your {{plan_name}} membership is active', '<h1>Your membership is active.</h1><p>Thanks for joining {{site_name}}. Your {{plan_name}} access is active through {{period_end}}.</p><p><a href="{{member_url}}">Open member dashboard</a></p>', 'Your {{plan_name}} membership is active through {{period_end}}. {{member_url}}', JSON_ARRAY('plan_name','period_end','member_url'), 'active'),
('subscription_canceled', 'Subscription Canceled', 'billing', 'Your {{site_name}} membership was canceled', '<h1>Membership canceled</h1><p>Your membership status changed to {{subscription_status}}. Access remains available until {{period_end}} when applicable.</p>', 'Your membership status changed to {{subscription_status}}. Access until: {{period_end}}', JSON_ARRAY('subscription_status','period_end'), 'active'),
('payment_receipt', 'Payment Receipt', 'billing', '{{site_name}} receipt {{invoice_number}}', '<h1>Payment receipt</h1><p>Invoice {{invoice_number}} was paid for {{amount}}.</p><p>Plan: {{plan_name}}</p><p><a href="{{billing_url}}">View billing</a></p>', 'Receipt {{invoice_number}} paid for {{amount}}. Plan: {{plan_name}}. {{billing_url}}', JSON_ARRAY('invoice_number','amount','plan_name','billing_url'), 'active'),
('merch_order_confirmation', 'Merch Order Confirmation', 'commerce', 'Stonefellow order {{order_number}} received', '<h1>Order received.</h1><p>Thanks, {{recipient_name}}. Your order {{order_number}} total is {{order_total}}.</p><p><a href="{{receipt_url}}">View receipt</a></p>', 'Order {{order_number}} received. Total: {{order_total}}. Receipt: {{receipt_url}}', JSON_ARRAY('recipient_name','order_number','order_total','receipt_url'), 'active'),
('order_fulfilled', 'Order Fulfilled', 'commerce', 'Stonefellow order {{order_number}} was fulfilled', '<h1>Your order was fulfilled.</h1><p>Order {{order_number}} has been marked fulfilled.</p><p>{{fulfillment_note}}</p><p><a href="{{receipt_url}}">View receipt</a></p>', 'Order {{order_number}} was fulfilled. {{fulfillment_note}} {{receipt_url}}', JSON_ARRAY('order_number','fulfillment_note','receipt_url'), 'active'),
('admin_new_order', 'Admin New Order Alert', 'admin', 'New Stonefellow order {{order_number}}', '<h1>New merch order</h1><p>{{recipient_name}} placed order {{order_number}} for {{order_total}}.</p><p><a href="{{admin_order_url}}">Review order</a></p>', 'New merch order {{order_number}} for {{order_total}}. Review: {{admin_order_url}}', JSON_ARRAY('recipient_name','order_number','order_total','admin_order_url'), 'active'),
('admin_failed_payment', 'Admin Failed Payment Alert', 'admin', 'Stonefellow payment alert: {{payment_status}}', '<h1>Payment alert</h1><p>Status: {{payment_status}}</p><p>Provider ref: {{provider_payment_id}}</p><p>{{error_message}}</p>', 'Payment alert {{payment_status}}. Provider ref: {{provider_payment_id}}. {{error_message}}', JSON_ARRAY('payment_status','provider_payment_id','error_message'), 'active'),
('playlist_share', 'Playlist Share', 'member', '{{recipient_name}} shared a Stonefellow playlist', '<h1>A Stonefellow playlist was shared with you.</h1><p>{{sender_name}} shared {{playlist_name}}.</p><p><a href="{{playlist_url}}">Open playlist</a></p>', '{{sender_name}} shared {{playlist_name}}: {{playlist_url}}', JSON_ARRAY('sender_name','playlist_name','playlist_url'), 'active')
ON DUPLICATE KEY UPDATE name=VALUES(name), category=VALUES(category), subject=VALUES(subject), html_body=VALUES(html_body), text_body=VALUES(text_body), variables_json=VALUES(variables_json), status='active', updated_at=CURRENT_TIMESTAMP;
