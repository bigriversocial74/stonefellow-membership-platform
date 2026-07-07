-- Stonefellow migration 019: production backup / restore manager v1 and deployment release manager v1.
-- Run after migration 018.

CREATE TABLE IF NOT EXISTS backup_profiles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  profile_key VARCHAR(120) NOT NULL UNIQUE,
  profile_label VARCHAR(190) NOT NULL,
  description TEXT DEFAULT NULL,
  backup_scope SET('database','uploads','config','docs','logs') NOT NULL DEFAULT 'database,uploads,config',
  storage_target ENUM('manual','local','s3','external') NOT NULL DEFAULT 'manual',
  retention_days INT NOT NULL DEFAULT 30,
  status ENUM('active','paused','archived') NOT NULL DEFAULT 'active',
  created_by_user_id INT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_backup_profiles_status (status, profile_label),
  CONSTRAINT fk_backup_profiles_user FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS backup_runs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  profile_id BIGINT UNSIGNED DEFAULT NULL,
  run_key VARCHAR(120) NOT NULL UNIQUE,
  run_type ENUM('manual','scheduled','pre_release','post_release') NOT NULL DEFAULT 'manual',
  run_status ENUM('planned','running','completed','failed','verified','archived') NOT NULL DEFAULT 'planned',
  database_status ENUM('not_started','exported','skipped','failed','verified') NOT NULL DEFAULT 'not_started',
  uploads_status ENUM('not_started','exported','skipped','failed','verified') NOT NULL DEFAULT 'not_started',
  config_status ENUM('not_started','exported','skipped','failed','verified') NOT NULL DEFAULT 'not_started',
  manifest_json JSON DEFAULT NULL,
  storage_location VARCHAR(255) DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  started_at DATETIME DEFAULT NULL,
  finished_at DATETIME DEFAULT NULL,
  verified_at DATETIME DEFAULT NULL,
  created_by_user_id INT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_backup_runs_status (run_status, created_at),
  INDEX idx_backup_runs_profile (profile_id, created_at),
  CONSTRAINT fk_backup_runs_profile FOREIGN KEY (profile_id) REFERENCES backup_profiles(id) ON DELETE SET NULL,
  CONSTRAINT fk_backup_runs_user FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS restore_readiness_checks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  backup_run_id BIGINT UNSIGNED DEFAULT NULL,
  check_key VARCHAR(120) NOT NULL,
  check_label VARCHAR(190) NOT NULL,
  check_group ENUM('database','uploads','config','release','security','manual') NOT NULL DEFAULT 'manual',
  status ENUM('pending','passed','failed','waived') NOT NULL DEFAULT 'pending',
  detail TEXT DEFAULT NULL,
  checked_by_user_id INT DEFAULT NULL,
  checked_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_restore_checks_backup (backup_run_id, status),
  INDEX idx_restore_checks_group (check_group, status),
  CONSTRAINT fk_restore_checks_backup FOREIGN KEY (backup_run_id) REFERENCES backup_runs(id) ON DELETE CASCADE,
  CONSTRAINT fk_restore_checks_user FOREIGN KEY (checked_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS deployment_releases (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  release_key VARCHAR(120) NOT NULL UNIQUE,
  release_label VARCHAR(190) NOT NULL,
  release_version VARCHAR(60) DEFAULT NULL,
  git_branch VARCHAR(120) DEFAULT NULL,
  git_sha VARCHAR(80) DEFAULT NULL,
  deployment_environment ENUM('local','staging','production') NOT NULL DEFAULT 'production',
  release_status ENUM('draft','ready','deploying','deployed','rolled_back','failed','archived') NOT NULL DEFAULT 'draft',
  backup_run_id BIGINT UNSIGNED DEFAULT NULL,
  migration_from VARCHAR(20) DEFAULT NULL,
  migration_to VARCHAR(20) DEFAULT NULL,
  deploy_notes TEXT DEFAULT NULL,
  rollback_notes TEXT DEFAULT NULL,
  scheduled_at DATETIME DEFAULT NULL,
  deployed_at DATETIME DEFAULT NULL,
  rolled_back_at DATETIME DEFAULT NULL,
  created_by_user_id INT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_deployment_releases_status (release_status, deployment_environment, created_at),
  INDEX idx_deployment_releases_version (release_version),
  CONSTRAINT fk_deployment_releases_backup FOREIGN KEY (backup_run_id) REFERENCES backup_runs(id) ON DELETE SET NULL,
  CONSTRAINT fk_deployment_releases_user FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS deployment_release_tasks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  release_id BIGINT UNSIGNED NOT NULL,
  task_key VARCHAR(120) NOT NULL,
  task_label VARCHAR(190) NOT NULL,
  task_group ENUM('preflight','backup','migrations','deploy','verify','rollback') NOT NULL DEFAULT 'preflight',
  status ENUM('pending','passed','failed','waived') NOT NULL DEFAULT 'pending',
  detail TEXT DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 100,
  checked_by_user_id INT DEFAULT NULL,
  checked_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_release_task (release_id, task_key),
  INDEX idx_release_tasks_status (release_id, status, sort_order),
  CONSTRAINT fk_release_tasks_release FOREIGN KEY (release_id) REFERENCES deployment_releases(id) ON DELETE CASCADE,
  CONSTRAINT fk_release_tasks_user FOREIGN KEY (checked_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS deployment_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  release_id BIGINT UNSIGNED DEFAULT NULL,
  event_type VARCHAR(100) NOT NULL,
  event_status ENUM('info','success','warning','error') NOT NULL DEFAULT 'info',
  title VARCHAR(190) NOT NULL,
  detail TEXT DEFAULT NULL,
  actor_user_id INT DEFAULT NULL,
  metadata_json JSON DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_deployment_events_release (release_id, created_at),
  INDEX idx_deployment_events_type (event_type, event_status, created_at),
  CONSTRAINT fk_deployment_events_release FOREIGN KEY (release_id) REFERENCES deployment_releases(id) ON DELETE SET NULL,
  CONSTRAINT fk_deployment_events_user FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO backup_profiles (profile_key, profile_label, description, backup_scope, storage_target, retention_days, status)
VALUES ('production_full_manual','Production Full Manual','Manual production backup checklist for database, uploads, config, docs, and launch artifacts.','database,uploads,config,docs','manual',30,'active')
ON DUPLICATE KEY UPDATE profile_label=VALUES(profile_label), description=VALUES(description), backup_scope=VALUES(backup_scope), storage_target=VALUES(storage_target), retention_days=VALUES(retention_days), status=VALUES(status);
