-- Stonefellow migration 024: launch content and catalog operations.
-- Compatible with MySQL 5.7+/MariaDB 10.2+; creates new tables only.

CREATE TABLE IF NOT EXISTS catalog_series (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL,
  description TEXT DEFAULT NULL,
  primary_image_asset_id INT DEFAULT NULL,
  release_at DATETIME DEFAULT NULL,
  release_timezone VARCHAR(80) NOT NULL DEFAULT 'America/Phoenix',
  status VARCHAR(32) NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_catalog_series_slug (slug),
  INDEX idx_catalog_series_status (status, release_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS catalog_seo_metadata (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  entity_type VARCHAR(40) NOT NULL,
  entity_id INT NOT NULL,
  seo_title VARCHAR(190) NOT NULL,
  meta_description VARCHAR(320) NOT NULL,
  canonical_path VARCHAR(500) NOT NULL,
  social_image_asset_id INT DEFAULT NULL,
  robots_noindex TINYINT(1) NOT NULL DEFAULT 0,
  updated_by_user_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_catalog_seo_entity (entity_type, entity_id),
  INDEX idx_catalog_seo_indexable (robots_noindex, entity_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS catalog_readiness_snapshots (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  snapshot_key CHAR(48) NOT NULL,
  target_commit_sha CHAR(40) DEFAULT NULL,
  overall_score INT NOT NULL DEFAULT 0,
  snapshot_status VARCHAR(32) NOT NULL DEFAULT 'created',
  item_count INT NOT NULL DEFAULT 0,
  ready_count INT NOT NULL DEFAULT 0,
  blocker_count INT NOT NULL DEFAULT 0,
  summary_json LONGTEXT DEFAULT NULL,
  created_by_user_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_catalog_readiness_snapshot (snapshot_key),
  INDEX idx_catalog_readiness_score (snapshot_status, overall_score, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS catalog_readiness_items (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  snapshot_id BIGINT NOT NULL,
  entity_type VARCHAR(40) NOT NULL,
  entity_id INT NOT NULL,
  display_label VARCHAR(255) NOT NULL,
  readiness_score INT NOT NULL DEFAULT 0,
  readiness_status VARCHAR(32) NOT NULL DEFAULT 'blocked',
  checks_json LONGTEXT NOT NULL,
  blockers_json LONGTEXT DEFAULT NULL,
  warnings_json LONGTEXT DEFAULT NULL,
  source_updated_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_catalog_snapshot_item (snapshot_id, entity_type, entity_id),
  INDEX idx_catalog_readiness_items (entity_type, readiness_status, readiness_score),
  FOREIGN KEY (snapshot_id) REFERENCES catalog_readiness_snapshots(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS catalog_publication_batches (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  batch_key CHAR(48) NOT NULL,
  action_type VARCHAR(40) NOT NULL,
  batch_status VARCHAR(32) NOT NULL DEFAULT 'processing',
  item_count INT NOT NULL DEFAULT 0,
  success_count INT NOT NULL DEFAULT 0,
  failure_count INT NOT NULL DEFAULT 0,
  confirmation_digest CHAR(64) DEFAULT NULL,
  summary_json LONGTEXT DEFAULT NULL,
  created_by_user_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME DEFAULT NULL,
  rolled_back_at DATETIME DEFAULT NULL,
  UNIQUE KEY unique_catalog_publication_batch (batch_key),
  INDEX idx_catalog_publication_batches (action_type, batch_status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS catalog_publication_actions (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  batch_id BIGINT NOT NULL,
  entity_type VARCHAR(40) NOT NULL,
  entity_id INT NOT NULL,
  action_type VARCHAR(40) NOT NULL,
  action_status VARCHAR(32) NOT NULL DEFAULT 'pending',
  before_json LONGTEXT DEFAULT NULL,
  after_json LONGTEXT DEFAULT NULL,
  error_message VARCHAR(1000) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_catalog_publication_actions (batch_id, action_status, id),
  INDEX idx_catalog_publication_entity (entity_type, entity_id, created_at),
  FOREIGN KEY (batch_id) REFERENCES catalog_publication_batches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS catalog_sample_flags (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  entity_type VARCHAR(40) NOT NULL,
  entity_id INT NOT NULL,
  reason_code VARCHAR(80) NOT NULL,
  confidence_percent INT NOT NULL DEFAULT 0,
  flag_status VARCHAR(32) NOT NULL DEFAULT 'open',
  evidence_json LONGTEXT DEFAULT NULL,
  resolved_by_user_id INT DEFAULT NULL,
  resolved_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_catalog_sample_flag (entity_type, entity_id, reason_code),
  INDEX idx_catalog_sample_open (flag_status, confidence_percent, entity_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS catalog_export_runs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  export_key CHAR(48) NOT NULL,
  entity_type VARCHAR(40) NOT NULL,
  export_format VARCHAR(20) NOT NULL DEFAULT 'csv',
  row_count INT NOT NULL DEFAULT 0,
  content_sha256 CHAR(64) NOT NULL,
  created_by_user_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_catalog_export_run (export_key),
  INDEX idx_catalog_export_type (entity_type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS catalog_operation_events (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  event_key CHAR(64) NOT NULL,
  event_type VARCHAR(80) NOT NULL,
  entity_type VARCHAR(40) DEFAULT NULL,
  entity_id INT DEFAULT NULL,
  event_status VARCHAR(32) NOT NULL DEFAULT 'completed',
  metadata_json LONGTEXT DEFAULT NULL,
  created_by_user_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_catalog_operation_event (event_key),
  INDEX idx_catalog_operation_events (event_type, event_status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
