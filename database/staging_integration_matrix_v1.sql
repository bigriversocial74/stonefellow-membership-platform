-- Stonefellow Staging Integration Matrix v1
-- Apply after database/staging_launch_certification_v1.sql.

CREATE TABLE IF NOT EXISTS staging_integration_executions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  certification_run_id BIGINT UNSIGNED NOT NULL,
  execution_key CHAR(36) NOT NULL UNIQUE,
  scenario_key VARCHAR(160) NOT NULL,
  scenario_label VARCHAR(255) NOT NULL,
  execution_status ENUM('draft','running','passed','failed','aborted') NOT NULL DEFAULT 'running',
  test_account_reference VARCHAR(255) DEFAULT NULL,
  correlation_id VARCHAR(190) NOT NULL,
  environment_key VARCHAR(40) NOT NULL DEFAULT 'staging',
  started_by_user_id INT DEFAULT NULL,
  completed_by_user_id INT DEFAULT NULL,
  started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME DEFAULT NULL,
  summary TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_staging_integration_run_scenario_correlation (certification_run_id, scenario_key, correlation_id),
  INDEX idx_staging_integration_execution_status (certification_run_id, execution_status, created_at),
  CONSTRAINT fk_staging_integration_execution_run FOREIGN KEY (certification_run_id) REFERENCES staging_launch_certification_runs(id) ON DELETE CASCADE,
  CONSTRAINT fk_staging_integration_execution_started_user FOREIGN KEY (started_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_staging_integration_execution_completed_user FOREIGN KEY (completed_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staging_integration_assertions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  execution_id BIGINT UNSIGNED NOT NULL,
  assertion_key VARCHAR(190) NOT NULL,
  assertion_label VARCHAR(255) NOT NULL,
  is_required TINYINT(1) NOT NULL DEFAULT 1,
  assertion_status ENUM('pending','running','passed','failed','skipped') NOT NULL DEFAULT 'pending',
  result_message TEXT DEFAULT NULL,
  source_reference VARCHAR(1000) DEFAULT NULL,
  evidence_sha256 CHAR(64) DEFAULT NULL,
  evidence_json JSON DEFAULT NULL,
  checked_by_user_id INT DEFAULT NULL,
  checked_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_staging_integration_execution_assertion (execution_id, assertion_key),
  INDEX idx_staging_integration_assertion_status (execution_id, assertion_status),
  CONSTRAINT fk_staging_integration_assertion_execution FOREIGN KEY (execution_id) REFERENCES staging_integration_executions(id) ON DELETE CASCADE,
  CONSTRAINT fk_staging_integration_assertion_user FOREIGN KEY (checked_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staging_integration_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  execution_id BIGINT UNSIGNED NOT NULL,
  source_event_id VARCHAR(190) DEFAULT NULL,
  event_type VARCHAR(120) NOT NULL,
  provider VARCHAR(80) DEFAULT NULL,
  event_status ENUM('received','verified','rejected','processed') NOT NULL DEFAULT 'received',
  assertion_key VARCHAR(190) DEFAULT NULL,
  payload_hash CHAR(64) NOT NULL,
  redacted_payload_json JSON DEFAULT NULL,
  actor_user_id INT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  processed_at DATETIME DEFAULT NULL,
  UNIQUE KEY uniq_staging_integration_source_event (provider, source_event_id),
  INDEX idx_staging_integration_event_execution (execution_id, created_at),
  CONSTRAINT fk_staging_integration_event_execution FOREIGN KEY (execution_id) REFERENCES staging_integration_executions(id) ON DELETE CASCADE,
  CONSTRAINT fk_staging_integration_event_user FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
