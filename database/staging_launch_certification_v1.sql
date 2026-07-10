-- Stonefellow Staging Operations & Launch Certification v1
-- Import after the existing application schema and AI staging certification SQL.

CREATE TABLE IF NOT EXISTS staging_launch_certification_runs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  run_key CHAR(36) NOT NULL UNIQUE,
  run_label VARCHAR(190) NOT NULL,
  environment_key VARCHAR(40) NOT NULL DEFAULT 'unknown',
  target_branch VARCHAR(190) NOT NULL DEFAULT 'main',
  target_commit_sha CHAR(40) DEFAULT NULL,
  run_status ENUM('draft','in_progress','passed','failed','superseded') NOT NULL DEFAULT 'in_progress',
  required_checks INT UNSIGNED NOT NULL DEFAULT 0,
  passed_checks INT UNSIGNED NOT NULL DEFAULT 0,
  failed_checks INT UNSIGNED NOT NULL DEFAULT 0,
  pending_checks INT UNSIGNED NOT NULL DEFAULT 0,
  overall_score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  certification_notes TEXT DEFAULT NULL,
  started_by_user_id INT DEFAULT NULL,
  completed_by_user_id INT DEFAULT NULL,
  started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_staging_launch_runs_status (run_status, created_at),
  INDEX idx_staging_launch_runs_commit (target_commit_sha),
  CONSTRAINT fk_staging_launch_runs_started_user FOREIGN KEY (started_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_staging_launch_runs_completed_user FOREIGN KEY (completed_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staging_launch_certification_checks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  run_id BIGINT UNSIGNED NOT NULL,
  check_key VARCHAR(190) NOT NULL,
  stage_key VARCHAR(80) NOT NULL,
  check_label VARCHAR(255) NOT NULL,
  severity ENUM('critical','high','medium','low','info') NOT NULL DEFAULT 'high',
  is_required TINYINT(1) NOT NULL DEFAULT 1,
  is_automated TINYINT(1) NOT NULL DEFAULT 0,
  check_status ENUM('pending','running','passed','failed','skipped','not_applicable') NOT NULL DEFAULT 'pending',
  result_message TEXT DEFAULT NULL,
  evidence_json JSON DEFAULT NULL,
  evidence_hash CHAR(64) DEFAULT NULL,
  checked_by_user_id INT DEFAULT NULL,
  started_at DATETIME DEFAULT NULL,
  completed_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_staging_launch_run_check (run_id, check_key),
  INDEX idx_staging_launch_checks_stage (run_id, stage_key, check_status),
  INDEX idx_staging_launch_checks_status (check_status, severity),
  CONSTRAINT fk_staging_launch_checks_run FOREIGN KEY (run_id) REFERENCES staging_launch_certification_runs(id) ON DELETE CASCADE,
  CONSTRAINT fk_staging_launch_checks_user FOREIGN KEY (checked_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staging_launch_certification_evidence (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  run_id BIGINT UNSIGNED NOT NULL,
  check_key VARCHAR(190) DEFAULT NULL,
  evidence_type ENUM('note','url','file_hash','provider_event','browser_test','database_test','backup','restore','approval') NOT NULL DEFAULT 'note',
  evidence_label VARCHAR(255) NOT NULL,
  source_reference VARCHAR(1000) DEFAULT NULL,
  artifact_sha256 CHAR(64) DEFAULT NULL,
  metadata_json JSON DEFAULT NULL,
  verification_status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  submitted_by_user_id INT DEFAULT NULL,
  verified_by_user_id INT DEFAULT NULL,
  submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  verified_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_staging_launch_evidence_run (run_id, check_key, verification_status),
  INDEX idx_staging_launch_evidence_hash (artifact_sha256),
  CONSTRAINT fk_staging_launch_evidence_run FOREIGN KEY (run_id) REFERENCES staging_launch_certification_runs(id) ON DELETE CASCADE,
  CONSTRAINT fk_staging_launch_evidence_submitted_user FOREIGN KEY (submitted_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_staging_launch_evidence_verified_user FOREIGN KEY (verified_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staging_launch_certification_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  run_id BIGINT UNSIGNED NOT NULL,
  event_type VARCHAR(120) NOT NULL,
  event_message VARCHAR(1000) NOT NULL,
  actor_user_id INT DEFAULT NULL,
  metadata_json JSON DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_staging_launch_events_run (run_id, created_at),
  CONSTRAINT fk_staging_launch_events_run FOREIGN KEY (run_id) REFERENCES staging_launch_certification_runs(id) ON DELETE CASCADE,
  CONSTRAINT fk_staging_launch_events_user FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
