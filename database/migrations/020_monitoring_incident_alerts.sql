-- Stonefellow migration 020: production monitoring / error log center v1 and system notifications + incident alerts v1.
-- Run after migration 019.

CREATE TABLE IF NOT EXISTS monitoring_health_snapshots (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  snapshot_key VARCHAR(120) NOT NULL UNIQUE,
  health_score INT NOT NULL DEFAULT 0,
  status ENUM('healthy','warning','critical','unknown') NOT NULL DEFAULT 'unknown',
  checks_total INT NOT NULL DEFAULT 0,
  checks_passed INT NOT NULL DEFAULT 0,
  checks_failed INT NOT NULL DEFAULT 0,
  failed_checks_json JSON DEFAULT NULL,
  metrics_json JSON DEFAULT NULL,
  created_by_user_id INT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_monitoring_health_status (status, created_at),
  CONSTRAINT fk_monitoring_health_user FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS monitoring_error_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_key VARCHAR(120) NOT NULL UNIQUE,
  source ENUM('php','application','database','payment','media','email','job','security','manual') NOT NULL DEFAULT 'application',
  severity ENUM('info','notice','warning','error','critical') NOT NULL DEFAULT 'error',
  status ENUM('new','triaged','linked','resolved','ignored') NOT NULL DEFAULT 'new',
  message TEXT NOT NULL,
  file_path VARCHAR(255) DEFAULT NULL,
  line_number INT DEFAULT NULL,
  route_path VARCHAR(190) DEFAULT NULL,
  request_method VARCHAR(12) DEFAULT NULL,
  ip_address VARCHAR(64) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  user_id INT DEFAULT NULL,
  fingerprint VARCHAR(128) DEFAULT NULL,
  metadata_json JSON DEFAULT NULL,
  first_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  occurrence_count INT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_monitoring_errors_status (status, severity, last_seen_at),
  INDEX idx_monitoring_errors_source (source, severity, created_at),
  INDEX idx_monitoring_errors_fingerprint (fingerprint, last_seen_at),
  CONSTRAINT fk_monitoring_errors_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS monitoring_service_checks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  check_key VARCHAR(120) NOT NULL UNIQUE,
  check_label VARCHAR(190) NOT NULL,
  check_group ENUM('runtime','database','queue','payments','media','email','security','release','custom') NOT NULL DEFAULT 'runtime',
  status ENUM('healthy','warning','critical','unknown') NOT NULL DEFAULT 'unknown',
  `last_value` VARCHAR(190) DEFAULT NULL,
  threshold_warning VARCHAR(120) DEFAULT NULL,
  threshold_critical VARCHAR(120) DEFAULT NULL,
  last_checked_at DATETIME DEFAULT NULL,
  detail TEXT DEFAULT NULL,
  metadata_json JSON DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_monitoring_checks_status (is_active, status, check_group),
  INDEX idx_monitoring_checks_group (check_group, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS incident_records (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  incident_key VARCHAR(120) NOT NULL UNIQUE,
  title VARCHAR(190) NOT NULL,
  summary TEXT DEFAULT NULL,
  severity ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  status ENUM('open','investigating','monitoring','resolved','closed') NOT NULL DEFAULT 'open',
  source ENUM('monitoring','alert','manual','security','deployment') NOT NULL DEFAULT 'monitoring',
  related_error_id BIGINT UNSIGNED DEFAULT NULL,
  related_health_snapshot_id BIGINT UNSIGNED DEFAULT NULL,
  assigned_admin_user_id INT DEFAULT NULL,
  detected_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  acknowledged_at DATETIME DEFAULT NULL,
  resolved_at DATETIME DEFAULT NULL,
  closed_at DATETIME DEFAULT NULL,
  impact_notes TEXT DEFAULT NULL,
  resolution_notes TEXT DEFAULT NULL,
  metadata_json JSON DEFAULT NULL,
  created_by_user_id INT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_incident_records_status (status, severity, detected_at),
  INDEX idx_incident_records_source (source, detected_at),
  CONSTRAINT fk_incidents_error FOREIGN KEY (related_error_id) REFERENCES monitoring_error_events(id) ON DELETE SET NULL,
  CONSTRAINT fk_incidents_snapshot FOREIGN KEY (related_health_snapshot_id) REFERENCES monitoring_health_snapshots(id) ON DELETE SET NULL,
  CONSTRAINT fk_incidents_assigned FOREIGN KEY (assigned_admin_user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_incidents_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS incident_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  incident_id BIGINT UNSIGNED NOT NULL,
  event_type VARCHAR(100) NOT NULL,
  event_status ENUM('info','success','warning','error') NOT NULL DEFAULT 'info',
  message TEXT NOT NULL,
  actor_user_id INT DEFAULT NULL,
  metadata_json JSON DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_incident_events_incident (incident_id, created_at),
  INDEX idx_incident_events_type (event_type, event_status, created_at),
  CONSTRAINT fk_incident_events_incident FOREIGN KEY (incident_id) REFERENCES incident_records(id) ON DELETE CASCADE,
  CONSTRAINT fk_incident_events_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS alert_rules (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rule_key VARCHAR(120) NOT NULL UNIQUE,
  rule_label VARCHAR(190) NOT NULL,
  description TEXT DEFAULT NULL,
  trigger_source ENUM('health','error','incident','queue','payment','media','security','custom') NOT NULL DEFAULT 'incident',
  min_severity ENUM('info','notice','warning','error','critical') NOT NULL DEFAULT 'warning',
  route_email TINYINT(1) NOT NULL DEFAULT 1,
  route_in_app TINYINT(1) NOT NULL DEFAULT 1,
  route_dashboard TINYINT(1) NOT NULL DEFAULT 1,
  status ENUM('active','paused','archived') NOT NULL DEFAULT 'active',
  metadata_json JSON DEFAULT NULL,
  created_by_user_id INT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_alert_rules_status (status, trigger_source, min_severity),
  CONSTRAINT fk_alert_rules_user FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_alert_notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  alert_key VARCHAR(120) NOT NULL UNIQUE,
  rule_id BIGINT UNSIGNED DEFAULT NULL,
  incident_id BIGINT UNSIGNED DEFAULT NULL,
  recipient_user_id INT DEFAULT NULL,
  recipient_email VARCHAR(190) DEFAULT NULL,
  channel ENUM('dashboard','email','in_app') NOT NULL DEFAULT 'dashboard',
  title VARCHAR(190) NOT NULL,
  body TEXT DEFAULT NULL,
  severity ENUM('info','notice','warning','error','critical') NOT NULL DEFAULT 'warning',
  delivery_status ENUM('queued','sent','failed','read','dismissed','skipped') NOT NULL DEFAULT 'queued',
  notification_log_id BIGINT UNSIGNED DEFAULT NULL,
  sent_at DATETIME DEFAULT NULL,
  read_at DATETIME DEFAULT NULL,
  error_message TEXT DEFAULT NULL,
  metadata_json JSON DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_admin_alerts_recipient (recipient_user_id, delivery_status, created_at),
  INDEX idx_admin_alerts_status (delivery_status, severity, created_at),
  INDEX idx_admin_alerts_incident (incident_id, created_at),
  CONSTRAINT fk_admin_alerts_rule FOREIGN KEY (rule_id) REFERENCES alert_rules(id) ON DELETE SET NULL,
  CONSTRAINT fk_admin_alerts_incident FOREIGN KEY (incident_id) REFERENCES incident_records(id) ON DELETE CASCADE,
  CONSTRAINT fk_admin_alerts_recipient FOREIGN KEY (recipient_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO monitoring_service_checks (check_key, check_label, check_group, status, threshold_warning, threshold_critical, detail) VALUES
  ('system_health_score','System health score','runtime','unknown','90','75','Overall system health score from runtime checks.'),
  ('failed_notifications_24h','Failed notifications 24h','email','unknown','5','20','Failed notification count in the past 24 hours.'),
  ('failed_jobs_24h','Failed ops jobs 24h','queue','unknown','3','10','Failed scheduler/job runs in the past 24 hours.'),
  ('failed_payments_24h','Failed payments 24h','payments','unknown','3','10','Failed payment records in the past 24 hours.'),
  ('open_incidents','Open incidents','custom','unknown','1','3','Open incidents by severity.'),
  ('recent_deployments','Recent deployments','release','unknown','1','1','Recent deployment/release events.')
ON DUPLICATE KEY UPDATE check_label=VALUES(check_label), check_group=VALUES(check_group), threshold_warning=VALUES(threshold_warning), threshold_critical=VALUES(threshold_critical), detail=VALUES(detail), is_active=1;

INSERT INTO alert_rules (rule_key, rule_label, description, trigger_source, min_severity, route_email, route_in_app, route_dashboard, status) VALUES
  ('critical_incident_admin_alert','Critical incident admin alert','Alert admins when a critical incident is opened.','incident','critical',1,1,1,'active'),
  ('health_score_warning','Health score warning','Alert admins when health score falls below warning threshold.','health','warning',1,1,1,'active'),
  ('failed_notifications_alert','Failed notification alert','Alert admins when failed notification counts cross threshold.','queue','warning',1,1,1,'active'),
  ('payment_failure_alert','Payment failure alert','Alert admins when payment failures cross threshold.','payment','error',1,1,1,'active')
ON DUPLICATE KEY UPDATE rule_label=VALUES(rule_label), description=VALUES(description), trigger_source=VALUES(trigger_source), min_severity=VALUES(min_severity), route_email=VALUES(route_email), route_in_app=VALUES(route_in_app), route_dashboard=VALUES(route_dashboard), status=VALUES(status);

INSERT INTO email_templates (template_key, name, category, subject, html_body, text_body, variables_json, status)
VALUES ('admin_incident_alert','Admin Incident Alert','admin','Stonefellow incident alert: {{incident_title}}','<h1>{{incident_title}}</h1><p>Severity: {{incident_severity}}</p><p>Status: {{incident_status}}</p><p>{{incident_summary}}</p><p><a href="{{incident_url}}">Open incident</a></p>','Stonefellow incident alert: {{incident_title}}\nSeverity: {{incident_severity}}\nStatus: {{incident_status}}\n{{incident_summary}}\n{{incident_url}}', JSON_ARRAY('incident_title','incident_severity','incident_status','incident_summary','incident_url'), 'active')
ON DUPLICATE KEY UPDATE name=VALUES(name), category=VALUES(category), subject=VALUES(subject), html_body=VALUES(html_body), text_body=VALUES(text_body), variables_json=VALUES(variables_json), status=VALUES(status), updated_at=NOW();
