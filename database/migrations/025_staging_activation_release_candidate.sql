-- Stonefellow migration 025: staging activation and exact-commit release candidates.
-- Compatible with MySQL 5.7+/MariaDB 10.2+; creates new tables only.

CREATE TABLE IF NOT EXISTS staging_activation_runs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  run_key CHAR(36) NOT NULL,
  run_label VARCHAR(190) NOT NULL,
  environment_key VARCHAR(40) NOT NULL DEFAULT 'staging',
  target_branch VARCHAR(190) NOT NULL DEFAULT 'main',
  target_commit_sha CHAR(40) NOT NULL,
  run_status ENUM('draft','running','passed','failed','superseded') NOT NULL DEFAULT 'draft',
  required_checks INT NOT NULL DEFAULT 0,
  passed_checks INT NOT NULL DEFAULT 0,
  failed_checks INT NOT NULL DEFAULT 0,
  pending_checks INT NOT NULL DEFAULT 0,
  overall_score DECIMAL(5,2) NOT NULL DEFAULT 0,
  activation_notes LONGTEXT DEFAULT NULL,
  started_by_user_id INT DEFAULT NULL,
  completed_by_user_id INT DEFAULT NULL,
  started_at DATETIME DEFAULT NULL,
  completed_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_staging_activation_run_key (run_key),
  INDEX idx_staging_activation_status (run_status, overall_score, created_at),
  INDEX idx_staging_activation_commit (target_commit_sha, run_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staging_activation_checks (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  run_id BIGINT NOT NULL,
  check_key VARCHAR(120) NOT NULL,
  section_key VARCHAR(80) NOT NULL,
  check_label VARCHAR(255) NOT NULL,
  severity ENUM('critical','high','medium','low') NOT NULL DEFAULT 'critical',
  is_required TINYINT(1) NOT NULL DEFAULT 1,
  is_automated TINYINT(1) NOT NULL DEFAULT 0,
  check_status ENUM('pending','running','passed','failed','skipped') NOT NULL DEFAULT 'pending',
  result_message LONGTEXT DEFAULT NULL,
  evidence_json LONGTEXT DEFAULT NULL,
  evidence_hash CHAR(64) DEFAULT NULL,
  checked_by_user_id INT DEFAULT NULL,
  started_at DATETIME DEFAULT NULL,
  completed_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_staging_activation_check (run_id, check_key),
  INDEX idx_staging_activation_check_status (run_id, section_key, check_status, severity),
  FOREIGN KEY (run_id) REFERENCES staging_activation_runs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staging_activation_evidence (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  run_id BIGINT NOT NULL,
  check_key VARCHAR(120) NOT NULL,
  evidence_type ENUM('note','url','provider_event','browser_test','database_test','transaction','backup','restore','artifact','approval') NOT NULL DEFAULT 'note',
  evidence_label VARCHAR(255) NOT NULL,
  source_reference VARCHAR(1000) NOT NULL,
  artifact_sha256 CHAR(64) DEFAULT NULL,
  metadata_json LONGTEXT DEFAULT NULL,
  verification_status ENUM('verified','rejected') NOT NULL DEFAULT 'verified',
  submitted_by_user_id INT DEFAULT NULL,
  verified_by_user_id INT DEFAULT NULL,
  verified_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_staging_activation_evidence (run_id, check_key, verification_status, created_at),
  FOREIGN KEY (run_id) REFERENCES staging_activation_runs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staging_release_candidates (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  candidate_key CHAR(36) NOT NULL,
  candidate_label VARCHAR(190) NOT NULL,
  activation_run_id BIGINT NOT NULL,
  catalog_snapshot_id BIGINT NOT NULL,
  launch_certification_run_id BIGINT NOT NULL,
  backup_run_id BIGINT NOT NULL,
  deployment_release_id BIGINT DEFAULT NULL,
  target_branch VARCHAR(190) NOT NULL DEFAULT 'main',
  target_commit_sha CHAR(40) NOT NULL,
  artifact_url VARCHAR(1000) NOT NULL,
  artifact_sha256 CHAR(64) NOT NULL,
  candidate_status ENUM('draft','ready','frozen','rejected','superseded') NOT NULL DEFAULT 'draft',
  freeze_notes LONGTEXT DEFAULT NULL,
  release_notes LONGTEXT DEFAULT NULL,
  created_by_user_id INT DEFAULT NULL,
  frozen_by_user_id INT DEFAULT NULL,
  frozen_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_staging_release_candidate_key (candidate_key),
  INDEX idx_staging_release_candidate_commit (target_commit_sha, candidate_status, created_at),
  INDEX idx_staging_release_candidate_activation (activation_run_id, candidate_status),
  FOREIGN KEY (activation_run_id) REFERENCES staging_activation_runs(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staging_release_candidate_events (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  candidate_id BIGINT NOT NULL,
  event_type VARCHAR(120) NOT NULL,
  event_status ENUM('recorded','verified','rejected') NOT NULL DEFAULT 'recorded',
  event_message VARCHAR(2000) NOT NULL,
  metadata_json LONGTEXT DEFAULT NULL,
  actor_user_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_staging_candidate_events (candidate_id, event_type, created_at),
  FOREIGN KEY (candidate_id) REFERENCES staging_release_candidates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
