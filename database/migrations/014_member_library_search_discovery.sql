-- Stonefellow migration 014: member library, watchlist, and search discovery.
-- Run after migration 013.

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
