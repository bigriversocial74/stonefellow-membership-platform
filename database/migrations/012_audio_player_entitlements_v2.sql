-- Stonefellow Audio Player v2 + Subscription Enforcement v2
-- Run after migration 011.

CREATE TABLE IF NOT EXISTS user_player_state (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  player_type ENUM('audio','video') NOT NULL DEFAULT 'audio',
  state_json LONGTEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL,
  UNIQUE KEY uniq_user_player_state (user_id, player_type),
  INDEX idx_user_player_state_user (user_id),
  CONSTRAINT fk_user_player_state_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
