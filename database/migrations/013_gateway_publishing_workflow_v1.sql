-- Stonefellow migration 013: gateway production pass, publishing workflow, member library, and search discovery.
-- Run after migration 012.

CREATE TABLE IF NOT EXISTS publishing_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  content_type VARCHAR(60) NOT NULL,
  content_id INT NOT NULL,
  event_type VARCHAR(80) NOT NULL DEFAULT 'publish_update',
  status VARCHAR(40) DEFAULT NULL,
  metadata_json JSON DEFAULT NULL,
  created_by_user_id INT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_publishing_events_content (content_type, content_id, created_at),
  INDEX idx_publishing_events_type (event_type, status, created_at),
  CONSTRAINT fk_publishing_events_user FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_release_rules (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  content_type VARCHAR(60) NOT NULL,
  content_id INT NOT NULL,
  rule_type ENUM('release_window','early_access','geo_policy','feature_pin') NOT NULL DEFAULT 'release_window',
  access_level ENUM('public','free_account','subscriber','premium','founding_fan') DEFAULT NULL,
  starts_at DATETIME DEFAULT NULL,
  ends_at DATETIME DEFAULT NULL,
  status ENUM('active','inactive','archived') NOT NULL DEFAULT 'active',
  metadata_json JSON DEFAULT NULL,
  created_by_user_id INT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_content_release_rule (content_type, content_id, rule_type),
  INDEX idx_content_release_rules_window (status, starts_at, ends_at, access_level),
  CONSTRAINT fk_content_release_rules_user FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_library_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  content_type ENUM('song','album','video','episode','playlist','product') NOT NULL,
  content_id INT NOT NULL,
  slug VARCHAR(190) DEFAULT NULL,
  title VARCHAR(190) NOT NULL,
  image_path VARCHAR(255) DEFAULT NULL,
  content_url VARCHAR(255) DEFAULT NULL,
  library_status ENUM('saved','watchlist','liked','completed') NOT NULL DEFAULT 'saved',
  progress_percent INT NOT NULL DEFAULT 0,
  position_seconds INT NOT NULL DEFAULT 0,
  metadata_json JSON DEFAULT NULL,
  last_interaction_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_member_library_status (user_id, content_type, content_id, library_status),
  INDEX idx_member_library_user_status (user_id, library_status, last_interaction_at),
  INDEX idx_member_library_content (content_type, content_id),
  CONSTRAINT fk_member_library_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_search_index (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  content_type ENUM('song','album','video','episode','product') NOT NULL,
  content_id INT NOT NULL,
  title VARCHAR(190) NOT NULL,
  slug VARCHAR(190) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  keywords TEXT DEFAULT NULL,
  image_path VARCHAR(255) DEFAULT NULL,
  content_url VARCHAR(255) DEFAULT NULL,
  access_level ENUM('public','free_account','subscriber','premium','founding_fan') NOT NULL DEFAULT 'public',
  status ENUM('draft','scheduled','published','archived') NOT NULL DEFAULT 'published',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  weight INT NOT NULL DEFAULT 50,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_content_search_index (content_type, content_id),
  FULLTEXT KEY ft_content_search (title, description, keywords),
  INDEX idx_content_search_filter (status, content_type, access_level, is_featured, weight)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO payment_gateway_settings (provider, mode, public_key, webhook_endpoint, status, metadata_json)
VALUES
  ('stripe', 'live', NULL, 'api/payment-webhook.php?provider=stripe', 'inactive', JSON_OBJECT('notes','Set SF_STRIPE_SECRET_KEY and SF_STRIPE_WEBHOOK_SECRET before enabling live mode.')),
  ('paypal', 'live', NULL, 'api/payment-webhook.php?provider=paypal', 'inactive', JSON_OBJECT('notes','Set PayPal credentials before enabling live mode.'))
ON DUPLICATE KEY UPDATE webhook_endpoint = VALUES(webhook_endpoint), metadata_json = VALUES(metadata_json);
