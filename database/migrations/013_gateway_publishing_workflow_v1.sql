-- Stonefellow migration 013: gateway production pass, publishing workflow, library, search, activity ops, notifications, comments, and creator posts.
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

CREATE TABLE IF NOT EXISTS member_activity_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  actor_name VARCHAR(190) DEFAULT NULL,
  event_type VARCHAR(90) NOT NULL,
  event_group ENUM('member','stream','library','commerce','notification','payment','publish','admin','activity') NOT NULL DEFAULT 'activity',
  content_type VARCHAR(60) DEFAULT NULL,
  content_id INT DEFAULT NULL,
  title VARCHAR(190) NOT NULL,
  detail TEXT DEFAULT NULL,
  action_url VARCHAR(255) DEFAULT NULL,
  severity ENUM('info','warning','critical') NOT NULL DEFAULT 'info',
  metadata_json JSON DEFAULT NULL,
  occurred_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_member_activity_events_user (user_id, occurred_at),
  INDEX idx_member_activity_events_group (event_group, occurred_at),
  INDEX idx_member_activity_events_type (event_type, severity, occurred_at),
  CONSTRAINT fk_member_activity_events_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_ops_tasks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  task_key VARCHAR(120) NOT NULL,
  task_type ENUM('content','media','commerce','payment','notification','publishing','qa','admin') NOT NULL DEFAULT 'admin',
  priority ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  title VARCHAR(190) NOT NULL,
  detail TEXT DEFAULT NULL,
  action_url VARCHAR(255) DEFAULT NULL,
  status ENUM('open','in_progress','done','dismissed') NOT NULL DEFAULT 'open',
  assigned_user_id INT DEFAULT NULL,
  metadata_json JSON DEFAULT NULL,
  due_at DATETIME DEFAULT NULL,
  completed_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_content_ops_task_key (task_key),
  INDEX idx_content_ops_tasks_status (status, priority, due_at),
  CONSTRAINT fk_content_ops_tasks_user FOREIGN KEY (assigned_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  notification_type VARCHAR(80) NOT NULL DEFAULT 'system',
  title VARCHAR(190) NOT NULL,
  body TEXT DEFAULT NULL,
  action_url VARCHAR(255) DEFAULT NULL,
  status ENUM('unread','read','dismissed') NOT NULL DEFAULT 'unread',
  metadata_json JSON DEFAULT NULL,
  read_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_member_notifications_user_status (user_id, status, created_at),
  INDEX idx_member_notifications_type (notification_type, created_at),
  CONSTRAINT fk_member_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fan_comments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  parent_comment_id BIGINT UNSIGNED DEFAULT NULL,
  content_type ENUM('episode','video','song','album','post','product') NOT NULL,
  content_id INT NOT NULL DEFAULT 0,
  content_slug VARCHAR(190) DEFAULT NULL,
  body TEXT NOT NULL,
  status ENUM('pending','approved','rejected','hidden','spam') NOT NULL DEFAULT 'pending',
  is_pinned TINYINT(1) NOT NULL DEFAULT 0,
  reaction_count INT NOT NULL DEFAULT 0,
  ip_address VARCHAR(64) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  moderated_by_user_id INT DEFAULT NULL,
  moderated_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_fan_comments_content (content_type, content_id, content_slug, status, created_at),
  INDEX idx_fan_comments_user (user_id, created_at),
  INDEX idx_fan_comments_parent (parent_comment_id),
  CONSTRAINT fk_fan_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_fan_comments_parent FOREIGN KEY (parent_comment_id) REFERENCES fan_comments(id) ON DELETE CASCADE,
  CONSTRAINT fk_fan_comments_moderator FOREIGN KEY (moderated_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fan_reactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  target_type ENUM('comment','episode','video','song','album','post','product') NOT NULL,
  target_id INT NOT NULL,
  reaction_type ENUM('like','love','fire','laugh','wow') NOT NULL DEFAULT 'like',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_fan_reaction (user_id, target_type, target_id),
  INDEX idx_fan_reactions_target (target_type, target_id, reaction_type),
  CONSTRAINT fk_fan_reactions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comment_moderation_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  comment_id BIGINT UNSIGNED NOT NULL,
  moderator_user_id INT DEFAULT NULL,
  action VARCHAR(60) NOT NULL,
  note TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_comment_moderation_events_comment (comment_id, created_at),
  CONSTRAINT fk_comment_moderation_comment FOREIGN KEY (comment_id) REFERENCES fan_comments(id) ON DELETE CASCADE,
  CONSTRAINT fk_comment_moderation_user FOREIGN KEY (moderator_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS creator_posts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  author_user_id INT DEFAULT NULL,
  post_type ENUM('news','episode','music','merch','behind_scenes') NOT NULL DEFAULT 'news',
  title VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL,
  excerpt TEXT DEFAULT NULL,
  body LONGTEXT NOT NULL,
  image_path VARCHAR(255) DEFAULT NULL,
  status ENUM('draft','scheduled','published','archived') NOT NULL DEFAULT 'draft',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  published_at DATETIME DEFAULT NULL,
  linked_content_type ENUM('episode','video','song','album','product') DEFAULT NULL,
  linked_content_id INT DEFAULT NULL,
  linked_content_slug VARCHAR(190) DEFAULT NULL,
  metadata_json JSON DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_creator_posts_slug (slug),
  INDEX idx_creator_posts_status (status, post_type, published_at, is_featured),
  FULLTEXT KEY ft_creator_posts (title, excerpt, body),
  CONSTRAINT fk_creator_posts_author FOREIGN KEY (author_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS creator_post_media (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id BIGINT UNSIGNED NOT NULL,
  media_type ENUM('image','audio','video','document','embed') NOT NULL DEFAULT 'image',
  media_path VARCHAR(255) NOT NULL,
  caption VARCHAR(255) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  metadata_json JSON DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_creator_post_media_post (post_id, sort_order),
  CONSTRAINT fk_creator_post_media_post FOREIGN KEY (post_id) REFERENCES creator_posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO payment_gateway_settings (provider, mode, public_key, webhook_endpoint, status, metadata_json)
VALUES
  ('stripe', 'live', NULL, 'api/payment-webhook.php?provider=stripe', 'inactive', JSON_OBJECT('notes','Set SF_STRIPE_SECRET_KEY and SF_STRIPE_WEBHOOK_SECRET before enabling live mode.')),
  ('paypal', 'live', NULL, 'api/payment-webhook.php?provider=paypal', 'inactive', JSON_OBJECT('notes','Set PayPal credentials before enabling live mode.'))
ON DUPLICATE KEY UPDATE webhook_endpoint = VALUES(webhook_endpoint), metadata_json = VALUES(metadata_json);
