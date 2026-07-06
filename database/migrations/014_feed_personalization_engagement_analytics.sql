-- Stonefellow migration 014: feed personalization, follow system, and engagement analytics v2.
-- Run after migration 013.

CREATE TABLE IF NOT EXISTS member_follows (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  target_type ENUM('creator','post_type','content_type','episode','video','song','album','post','product') NOT NULL,
  target_id BIGINT UNSIGNED DEFAULT NULL,
  target_slug VARCHAR(190) DEFAULT NULL,
  label VARCHAR(190) DEFAULT NULL,
  status ENUM('following','muted') NOT NULL DEFAULT 'following',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_member_follow_target (user_id, target_type, target_id, target_slug),
  INDEX idx_member_follows_target (target_type, target_id, target_slug, status),
  INDEX idx_member_follows_user_status (user_id, status, created_at),
  CONSTRAINT fk_member_follows_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_feed_preferences (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  preference_key VARCHAR(90) NOT NULL,
  preference_value VARCHAR(190) DEFAULT NULL,
  weight INT NOT NULL DEFAULT 50,
  metadata_json JSON DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_member_feed_preference (user_id, preference_key),
  INDEX idx_member_feed_preferences_weight (user_id, weight),
  CONSTRAINT fk_member_feed_preferences_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_feed_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  post_id BIGINT UNSIGNED NOT NULL,
  feed_status ENUM('shown','saved','hidden','dismissed') NOT NULL DEFAULT 'shown',
  personalization_score INT NOT NULL DEFAULT 0,
  reason VARCHAR(190) DEFAULT NULL,
  shown_at DATETIME DEFAULT NULL,
  clicked_at DATETIME DEFAULT NULL,
  saved_at DATETIME DEFAULT NULL,
  hidden_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_member_feed_item (user_id, post_id),
  INDEX idx_member_feed_items_user_status (user_id, feed_status, personalization_score),
  INDEX idx_member_feed_items_post (post_id, feed_status),
  CONSTRAINT fk_member_feed_items_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_member_feed_items_post FOREIGN KEY (post_id) REFERENCES creator_posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS engagement_analytics_daily (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  metric_date DATE NOT NULL,
  content_type VARCHAR(60) NOT NULL,
  content_id BIGINT UNSIGNED DEFAULT NULL,
  content_slug VARCHAR(190) DEFAULT NULL,
  title VARCHAR(190) DEFAULT NULL,
  impressions INT NOT NULL DEFAULT 0,
  clicks INT NOT NULL DEFAULT 0,
  comments INT NOT NULL DEFAULT 0,
  reactions INT NOT NULL DEFAULT 0,
  saves INT NOT NULL DEFAULT 0,
  hides INT NOT NULL DEFAULT 0,
  follows INT NOT NULL DEFAULT 0,
  notification_opens INT NOT NULL DEFAULT 0,
  conversion_events INT NOT NULL DEFAULT 0,
  score INT NOT NULL DEFAULT 0,
  metadata_json JSON DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_engagement_analytics_daily (metric_date, content_type, content_id, content_slug),
  INDEX idx_engagement_analytics_score (metric_date, score),
  INDEX idx_engagement_analytics_content (content_type, content_id, metric_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_engagement_scores (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  score INT NOT NULL DEFAULT 0,
  comment_count INT NOT NULL DEFAULT 0,
  reaction_count INT NOT NULL DEFAULT 0,
  save_count INT NOT NULL DEFAULT 0,
  follow_count INT NOT NULL DEFAULT 0,
  stream_count INT NOT NULL DEFAULT 0,
  last_engaged_at DATETIME DEFAULT NULL,
  metadata_json JSON DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_member_engagement_scores_user (user_id),
  INDEX idx_member_engagement_scores_score (score, last_engaged_at),
  CONSTRAINT fk_member_engagement_scores_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
