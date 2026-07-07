-- Stonefellow Content Import + Seed Manager v1
-- Run after base schema and migrations 001 through 010.

CREATE TABLE IF NOT EXISTS content_import_batches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  import_key VARCHAR(80) NOT NULL UNIQUE,
  import_type VARCHAR(80) NOT NULL,
  source_name VARCHAR(190) DEFAULT NULL,
  status ENUM('preview','processing','completed','failed','rolled_back') NOT NULL DEFAULT 'processing',
  total_rows INT UNSIGNED NOT NULL DEFAULT 0,
  inserted_count INT UNSIGNED NOT NULL DEFAULT 0,
  updated_count INT UNSIGNED NOT NULL DEFAULT 0,
  skipped_count INT UNSIGNED NOT NULL DEFAULT 0,
  error_count INT UNSIGNED NOT NULL DEFAULT 0,
  dry_run TINYINT(1) NOT NULL DEFAULT 0,
  created_by_user_id INT DEFAULT NULL,
  summary_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME DEFAULT NULL,
  rolled_back_at DATETIME DEFAULT NULL,
  INDEX idx_content_import_type (import_type),
  INDEX idx_content_import_status (status),
  INDEX idx_content_import_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_import_rows (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  batch_id BIGINT UNSIGNED NOT NULL,
  `row_number` INT UNSIGNED NOT NULL DEFAULT 0,
  entity_table VARCHAR(80) NOT NULL,
  entity_id INT DEFAULT NULL,
  import_action ENUM('insert','update','skip') NOT NULL DEFAULT 'insert',
  import_status ENUM('success','skipped','failed','rolled_back') NOT NULL DEFAULT 'success',
  unique_key_value VARCHAR(255) DEFAULT NULL,
  source_json LONGTEXT NULL,
  before_json LONGTEXT NULL,
  after_json LONGTEXT NULL,
  error_message VARCHAR(500) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_content_import_rows_batch (batch_id),
  INDEX idx_content_import_rows_entity (entity_table, entity_id),
  INDEX idx_content_import_rows_status (import_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
