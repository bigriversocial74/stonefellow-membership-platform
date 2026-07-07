-- Stonefellow Production Readiness + QA Harness v1
-- Run after base schema and migrations 001 through 009.

CREATE TABLE IF NOT EXISTS qa_runs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  run_type VARCHAR(40) NOT NULL DEFAULT 'manual',
  score TINYINT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('passed','review','failed') NOT NULL DEFAULT 'review',
  summary_json JSON NULL,
  created_by_user_id INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_qa_runs_created (created_at),
  INDEX idx_qa_runs_status (status),
  CONSTRAINT fk_qa_runs_user FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS qa_check_results (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  qa_run_id BIGINT UNSIGNED NOT NULL,
  section VARCHAR(80) NOT NULL,
  check_key VARCHAR(160) NOT NULL,
  status ENUM('pass','ready','ok','warn','preview','manual','info','fail','missing') NOT NULL DEFAULT 'info',
  message VARCHAR(255) NOT NULL,
  detail_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_qa_results_run (qa_run_id),
  INDEX idx_qa_results_section (section),
  INDEX idx_qa_results_status (status),
  CONSTRAINT fk_qa_results_run FOREIGN KEY (qa_run_id) REFERENCES qa_runs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
