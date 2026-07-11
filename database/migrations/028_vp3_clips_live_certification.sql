-- Stonefellow migration 028: live VP3 Clips integration certification records.
-- Certification is bound to the current central bridge credential.

CREATE TABLE IF NOT EXISTS vp3_clip_certifications (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  certification_uuid CHAR(36) DEFAULT NULL,
  bridge_uuid CHAR(36) NOT NULL,
  status ENUM('running','passed','failed','approved','revoked','not_started') NOT NULL DEFAULT 'running',
  publishing_mode ENUM('certification','live') NOT NULL DEFAULT 'certification',
  checks_json JSON NOT NULL,
  central_response_json JSON DEFAULT NULL,
  failure_summary VARCHAR(1000) DEFAULT NULL,
  started_at DATETIME NOT NULL,
  completed_at DATETIME DEFAULT NULL,
  approved_at DATETIME DEFAULT NULL,
  expires_at DATETIME DEFAULT NULL,
  created_by_user_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_vp3_clip_certification_uuid (certification_uuid),
  INDEX idx_vp3_clip_certification_bridge (bridge_uuid,status,created_at),
  INDEX idx_vp3_clip_certification_mode (publishing_mode,expires_at),
  FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
