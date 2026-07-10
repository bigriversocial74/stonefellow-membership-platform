-- Stonefellow Production Launch Promotion v1
-- Apply after the launch certification and staging integration matrix SQL files.

CREATE TABLE IF NOT EXISTS production_launch_promotions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  promotion_key CHAR(36) NOT NULL UNIQUE,
  promotion_label VARCHAR(190) NOT NULL,
  release_id BIGINT UNSIGNED NOT NULL,
  certification_run_id BIGINT UNSIGNED NOT NULL,
  backup_run_id BIGINT UNSIGNED NOT NULL,
  target_branch VARCHAR(190) NOT NULL,
  target_commit_sha CHAR(40) NOT NULL,
  artifact_reference VARCHAR(1000) DEFAULT NULL,
  artifact_sha256 CHAR(64) DEFAULT NULL,
  promotion_status ENUM('draft','approved','deploying','deployed','verified','failed','rolled_back','superseded') NOT NULL DEFAULT 'draft',
  freeze_at DATETIME DEFAULT NULL,
  deployment_started_at DATETIME DEFAULT NULL,
  deployed_at DATETIME DEFAULT NULL,
  verified_at DATETIME DEFAULT NULL,
  rolled_back_at DATETIME DEFAULT NULL,
  rollback_trigger TEXT DEFAULT NULL,
  rollback_procedure MEDIUMTEXT DEFAULT NULL,
  release_notes MEDIUMTEXT DEFAULT NULL,
  created_by_user_id INT DEFAULT NULL,
  updated_by_user_id INT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_production_launch_release (release_id),
  INDEX idx_production_launch_status (promotion_status, created_at),
  INDEX idx_production_launch_commit (target_commit_sha),
  CONSTRAINT fk_production_launch_release FOREIGN KEY (release_id) REFERENCES deployment_releases(id) ON DELETE RESTRICT,
  CONSTRAINT fk_production_launch_certification FOREIGN KEY (certification_run_id) REFERENCES staging_launch_certification_runs(id) ON DELETE RESTRICT,
  CONSTRAINT fk_production_launch_backup FOREIGN KEY (backup_run_id) REFERENCES backup_runs(id) ON DELETE RESTRICT,
  CONSTRAINT fk_production_launch_created_user FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_production_launch_updated_user FOREIGN KEY (updated_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS production_launch_approvals (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  promotion_id BIGINT UNSIGNED NOT NULL,
  approval_type ENUM('technical','operations','security','business') NOT NULL,
  approval_status ENUM('pending','approved','rejected','revoked') NOT NULL DEFAULT 'pending',
  decision_note TEXT DEFAULT NULL,
  evidence_reference VARCHAR(1000) DEFAULT NULL,
  evidence_sha256 CHAR(64) DEFAULT NULL,
  approver_user_id INT DEFAULT NULL,
  decided_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_production_launch_approval_type (promotion_id, approval_type),
  INDEX idx_production_launch_approval_status (promotion_id, approval_status),
  CONSTRAINT fk_production_launch_approval_promotion FOREIGN KEY (promotion_id) REFERENCES production_launch_promotions(id) ON DELETE CASCADE,
  CONSTRAINT fk_production_launch_approval_user FOREIGN KEY (approver_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS production_launch_checks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  promotion_id BIGINT UNSIGNED NOT NULL,
  phase_key ENUM('pre_deploy','deploy','post_deploy','rollback') NOT NULL,
  check_key VARCHAR(190) NOT NULL,
  check_label VARCHAR(255) NOT NULL,
  is_required TINYINT(1) NOT NULL DEFAULT 1,
  check_status ENUM('pending','running','passed','failed','skipped') NOT NULL DEFAULT 'pending',
  result_message TEXT DEFAULT NULL,
  evidence_reference VARCHAR(1000) DEFAULT NULL,
  evidence_sha256 CHAR(64) DEFAULT NULL,
  checked_by_user_id INT DEFAULT NULL,
  checked_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_production_launch_check (promotion_id, check_key),
  INDEX idx_production_launch_check_status (promotion_id, phase_key, check_status),
  CONSTRAINT fk_production_launch_check_promotion FOREIGN KEY (promotion_id) REFERENCES production_launch_promotions(id) ON DELETE CASCADE,
  CONSTRAINT fk_production_launch_check_user FOREIGN KEY (checked_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS production_launch_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  promotion_id BIGINT UNSIGNED NOT NULL,
  source_event_id VARCHAR(190) DEFAULT NULL,
  event_type VARCHAR(120) NOT NULL,
  event_status ENUM('received','verified','processed','rejected') NOT NULL DEFAULT 'received',
  event_message VARCHAR(1000) NOT NULL,
  payload_hash CHAR(64) DEFAULT NULL,
  redacted_payload_json JSON DEFAULT NULL,
  actor_user_id INT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  processed_at DATETIME DEFAULT NULL,
  UNIQUE KEY uniq_production_launch_source_event (source_event_id),
  INDEX idx_production_launch_event_promotion (promotion_id, created_at),
  CONSTRAINT fk_production_launch_event_promotion FOREIGN KEY (promotion_id) REFERENCES production_launch_promotions(id) ON DELETE CASCADE,
  CONSTRAINT fk_production_launch_event_user FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
