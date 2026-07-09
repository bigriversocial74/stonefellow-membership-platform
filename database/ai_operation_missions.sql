CREATE TABLE IF NOT EXISTS ai_operation_missions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  mission_title VARCHAR(190) NOT NULL,
  mission_area VARCHAR(80) NOT NULL DEFAULT 'ops',
  mission_status ENUM('draft','reviewing','approved','in_progress','completed','blocked','cancelled') NOT NULL DEFAULT 'draft',
  risk_level ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  objective TEXT NULL,
  source_context VARCHAR(120) NULL,
  created_by_ai TINYINT(1) NOT NULL DEFAULT 1,
  created_by_user_id INT UNSIGNED NULL,
  approved_by_user_id INT UNSIGNED NULL,
  approved_at DATETIME NULL,
  completed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_ai_operation_missions_area (mission_area),
  KEY idx_ai_operation_missions_status (mission_status),
  KEY idx_ai_operation_missions_risk (risk_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_operation_mission_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  mission_id INT UNSIGNED NOT NULL,
  platform_action_id INT UNSIGNED NOT NULL,
  item_order INT UNSIGNED NOT NULL DEFAULT 10,
  item_status ENUM('pending','ready','running','completed','blocked','failed','skipped') NOT NULL DEFAULT 'pending',
  stop_on_failure TINYINT(1) NOT NULL DEFAULT 1,
  last_result_message TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_ai_operation_mission_action (mission_id, platform_action_id),
  KEY idx_ai_operation_mission_items_mission (mission_id),
  KEY idx_ai_operation_mission_items_action (platform_action_id),
  KEY idx_ai_operation_mission_items_status (item_status),
  KEY idx_ai_operation_mission_items_order (mission_id, item_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
