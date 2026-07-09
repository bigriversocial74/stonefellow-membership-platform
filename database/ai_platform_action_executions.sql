CREATE TABLE IF NOT EXISTS ai_platform_action_executions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  platform_action_id INT UNSIGNED NOT NULL,
  route_key VARCHAR(120) NOT NULL,
  execution_status ENUM('started','completed','failed','blocked') NOT NULL DEFAULT 'started',
  result_message TEXT NULL,
  result_json LONGTEXT NULL,
  executed_by_user_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME NULL,
  KEY idx_ai_platform_exec_action (platform_action_id),
  KEY idx_ai_platform_exec_route (route_key),
  KEY idx_ai_platform_exec_status (execution_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
