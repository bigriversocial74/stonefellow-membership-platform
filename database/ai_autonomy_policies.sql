CREATE TABLE IF NOT EXISTS ai_autonomy_policies (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  policy_area VARCHAR(80) NOT NULL,
  route_key VARCHAR(120) NOT NULL,
  autonomy_level ENUM('observe_only','propose_only','draft_only','approval_required','auto_execute_low_risk','blocked') NOT NULL DEFAULT 'approval_required',
  requires_approval TINYINT(1) NOT NULL DEFAULT 1,
  is_blocked TINYINT(1) NOT NULL DEFAULT 0,
  risk_level ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  notes TEXT NULL,
  created_by_user_id INT UNSIGNED NULL,
  updated_by_user_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_ai_autonomy_policy_route (route_key),
  KEY idx_ai_autonomy_policies_area (policy_area),
  KEY idx_ai_autonomy_policies_level (autonomy_level),
  KEY idx_ai_autonomy_policies_blocked (is_blocked),
  KEY idx_ai_autonomy_policies_risk (risk_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
