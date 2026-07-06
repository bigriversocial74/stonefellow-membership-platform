-- Stonefellow migration 017: admin ops scheduler v1 and member messaging v1.
-- Run after migration 016.

CREATE TABLE IF NOT EXISTS ops_scheduled_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_key VARCHAR(120) NOT NULL UNIQUE,
  job_type ENUM('dispatch_notifications','lifecycle_churn_scan','support_sla_scan','revenue_snapshot','engagement_score_refresh','custom') NOT NULL DEFAULT 'custom',
  title VARCHAR(190) NOT NULL,
  description TEXT DEFAULT NULL,
  frequency ENUM('hourly','daily','weekly','monthly','manual') NOT NULL DEFAULT 'daily',
  schedule_time TIME DEFAULT NULL,
  status ENUM('active','paused','archived') NOT NULL DEFAULT 'active',
  last_run_at DATETIME DEFAULT NULL,
  next_run_at DATETIME DEFAULT NULL,
  run_count INT NOT NULL DEFAULT 0,
  failure_count INT NOT NULL DEFAULT 0,
  metadata_json JSON DEFAULT NULL,
  created_by_user_id INT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_ops_jobs_due (status, next_run_at, frequency),
  INDEX idx_ops_jobs_type (job_type, status),
  CONSTRAINT fk_ops_jobs_user FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ops_job_runs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_id BIGINT UNSIGNED DEFAULT NULL,
  job_key VARCHAR(120) NOT NULL,
  run_status ENUM('started','success','failed','skipped') NOT NULL DEFAULT 'started',
  started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  finished_at DATETIME DEFAULT NULL,
  processed_count INT NOT NULL DEFAULT 0,
  success_count INT NOT NULL DEFAULT 0,
  failed_count INT NOT NULL DEFAULT 0,
  result_summary TEXT DEFAULT NULL,
  error_message TEXT DEFAULT NULL,
  metadata_json JSON DEFAULT NULL,
  INDEX idx_ops_job_runs_job (job_id, started_at),
  INDEX idx_ops_job_runs_status (run_status, started_at),
  CONSTRAINT fk_ops_job_runs_job FOREIGN KEY (job_id) REFERENCES ops_scheduled_jobs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_message_threads (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  subject VARCHAR(190) NOT NULL,
  thread_type ENUM('notice','support','system','admin') NOT NULL DEFAULT 'notice',
  status ENUM('open','read','archived') NOT NULL DEFAULT 'open',
  last_message_at DATETIME DEFAULT NULL,
  metadata_json JSON DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_member_message_threads_user (user_id, status, last_message_at),
  CONSTRAINT fk_member_message_threads_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  thread_id BIGINT UNSIGNED DEFAULT NULL,
  user_id INT NOT NULL,
  sender_user_id INT DEFAULT NULL,
  sender_type ENUM('admin','system','member') NOT NULL DEFAULT 'admin',
  subject VARCHAR(190) NOT NULL,
  body TEXT NOT NULL,
  action_url VARCHAR(255) DEFAULT NULL,
  message_type ENUM('notice','retention','support','system','promotion') NOT NULL DEFAULT 'notice',
  status ENUM('unread','read','archived','dismissed') NOT NULL DEFAULT 'unread',
  read_at DATETIME DEFAULT NULL,
  metadata_json JSON DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_member_messages_user_status (user_id, status, created_at),
  INDEX idx_member_messages_type (message_type, created_at),
  CONSTRAINT fk_member_messages_thread FOREIGN KEY (thread_id) REFERENCES member_message_threads(id) ON DELETE SET NULL,
  CONSTRAINT fk_member_messages_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_member_messages_sender FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_message_campaigns (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  campaign_key VARCHAR(120) NOT NULL UNIQUE,
  title VARCHAR(190) NOT NULL,
  subject VARCHAR(190) NOT NULL,
  body TEXT NOT NULL,
  audience_filter ENUM('all_members','active_subscribers','free_members','churn_risk','engaged_members','support_open','manual') NOT NULL DEFAULT 'all_members',
  channel_email TINYINT(1) NOT NULL DEFAULT 1,
  channel_in_app TINYINT(1) NOT NULL DEFAULT 1,
  honors_preferences TINYINT(1) NOT NULL DEFAULT 1,
  status ENUM('draft','scheduled','queued','sending','sent','paused','archived') NOT NULL DEFAULT 'draft',
  scheduled_at DATETIME DEFAULT NULL,
  sent_at DATETIME DEFAULT NULL,
  action_url VARCHAR(255) DEFAULT NULL,
  created_by_user_id INT DEFAULT NULL,
  metadata_json JSON DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_message_campaigns_status (status, scheduled_at),
  INDEX idx_message_campaigns_audience (audience_filter, status),
  CONSTRAINT fk_message_campaigns_user FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_message_recipients (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  campaign_id BIGINT UNSIGNED NOT NULL,
  user_id INT NOT NULL,
  email VARCHAR(190) DEFAULT NULL,
  delivery_status ENUM('queued','sent','failed','skipped') NOT NULL DEFAULT 'queued',
  email_log_id BIGINT UNSIGNED DEFAULT NULL,
  member_message_id BIGINT UNSIGNED DEFAULT NULL,
  error_message TEXT DEFAULT NULL,
  sent_at DATETIME DEFAULT NULL,
  opened_at DATETIME DEFAULT NULL,
  clicked_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_message_recipient (campaign_id, user_id),
  INDEX idx_message_recipients_status (campaign_id, delivery_status),
  INDEX idx_message_recipients_user (user_id, created_at),
  CONSTRAINT fk_message_recipients_campaign FOREIGN KEY (campaign_id) REFERENCES member_message_campaigns(id) ON DELETE CASCADE,
  CONSTRAINT fk_message_recipients_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_message_recipients_message FOREIGN KEY (member_message_id) REFERENCES member_messages(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO ops_scheduled_jobs (job_key, job_type, title, description, frequency, schedule_time, status, next_run_at, created_by_user_id)
VALUES
  ('dispatch_notifications_hourly','dispatch_notifications','Dispatch queued notifications','Send queued notifications that are scheduled and ready.', 'hourly', NULL, 'active', NOW(), NULL),
  ('lifecycle_churn_scan_daily','lifecycle_churn_scan','Lifecycle churn-risk scan','Create retention tasks for members past due, canceling, or ending soon.', 'daily', '08:00:00', 'active', DATE_ADD(NOW(), INTERVAL 1 DAY), NULL),
  ('support_sla_scan_daily','support_sla_scan','Support SLA scan','Create admin follow-up tasks for older open support tickets.', 'daily', '09:00:00', 'active', DATE_ADD(NOW(), INTERVAL 1 DAY), NULL),
  ('revenue_snapshot_daily','revenue_snapshot','Revenue snapshot','Save daily launch revenue dashboard snapshot.', 'daily', '23:00:00', 'active', DATE_ADD(NOW(), INTERVAL 1 DAY), NULL),
  ('engagement_score_refresh_weekly','engagement_score_refresh','Engagement score refresh','Recalculate member engagement scores weekly.', 'weekly', '07:00:00', 'active', DATE_ADD(NOW(), INTERVAL 7 DAY), NULL)
ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description), job_type=VALUES(job_type), frequency=VALUES(frequency), schedule_time=VALUES(schedule_time), status=VALUES(status);

INSERT INTO email_templates (template_key, name, category, subject, html_body, text_body, variables_json, status)
VALUES ('member_message_notice','Member Message Notice','member','{{message_subject}}','<h1>{{message_subject}}</h1><p>{{message_body}}</p><p><a href="{{action_url}}">Open message</a></p>','{{message_subject}}\n\n{{message_text}}\n\n{{action_url}}', JSON_ARRAY('message_subject','message_body','message_text','action_url'), 'active')
ON DUPLICATE KEY UPDATE name=VALUES(name), category=VALUES(category), subject=VALUES(subject), html_body=VALUES(html_body), text_body=VALUES(text_body), variables_json=VALUES(variables_json), status=VALUES(status), updated_at=NOW();
