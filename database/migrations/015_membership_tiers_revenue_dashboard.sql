-- Stonefellow migration 015: membership tiers/access packaging v2 and launch revenue dashboard v1.
-- Run after migration 014.

DROP PROCEDURE IF EXISTS sf_add_tier_revenue_column;
DELIMITER //
CREATE PROCEDURE sf_add_tier_revenue_column(IN table_name VARCHAR(64), IN column_name VARCHAR(64), IN column_definition TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = table_name
      AND COLUMN_NAME = column_name
  ) THEN
    SET @ddl = CONCAT('ALTER TABLE `', table_name, '` ADD COLUMN ', column_definition);
    PREPARE stmt FROM @ddl;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END //
DELIMITER ;

CALL sf_add_tier_revenue_column('subscription_plans', 'access_label', '`access_label` VARCHAR(120) DEFAULT NULL AFTER `public_badge`');
CALL sf_add_tier_revenue_column('subscription_plans', 'benefit_matrix_json', '`benefit_matrix_json` JSON DEFAULT NULL AFTER `access_label`');
CALL sf_add_tier_revenue_column('subscription_plans', 'upgrade_path_json', '`upgrade_path_json` JSON DEFAULT NULL AFTER `benefit_matrix_json`');
CALL sf_add_tier_revenue_column('subscription_plans', 'launch_position', '`launch_position` ENUM(''entry'',''core'',''premium'',''founder'') NOT NULL DEFAULT ''core'' AFTER `sort_order`');
CALL sf_add_tier_revenue_column('subscription_plans', 'is_public', '`is_public` TINYINT(1) NOT NULL DEFAULT 1 AFTER `launch_position`');

CREATE TABLE IF NOT EXISTS membership_tier_benefits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  benefit_key VARCHAR(90) NOT NULL,
  benefit_label VARCHAR(190) NOT NULL,
  benefit_group VARCHAR(90) NOT NULL DEFAULT 'access',
  description TEXT DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 100,
  is_public TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_tier_benefit_key (benefit_key),
  INDEX idx_tier_benefits_public (is_public, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS membership_tier_benefit_map (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  plan_id INT NOT NULL,
  benefit_key VARCHAR(90) NOT NULL,
  value_label VARCHAR(190) DEFAULT NULL,
  is_enabled TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 100,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_plan_benefit (plan_id, benefit_key),
  INDEX idx_plan_benefit_sort (plan_id, is_enabled, sort_order),
  CONSTRAINT fk_tier_benefit_map_plan FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE CASCADE,
  CONSTRAINT fk_tier_benefit_map_key FOREIGN KEY (benefit_key) REFERENCES membership_tier_benefits(benefit_key) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS launch_revenue_snapshots (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  snapshot_date DATE NOT NULL,
  mrr_cents INT NOT NULL DEFAULT 0,
  arr_cents INT NOT NULL DEFAULT 0,
  subscription_revenue_cents INT NOT NULL DEFAULT 0,
  merch_revenue_cents INT NOT NULL DEFAULT 0,
  total_revenue_cents INT NOT NULL DEFAULT 0,
  active_subscriptions INT NOT NULL DEFAULT 0,
  paid_members INT NOT NULL DEFAULT 0,
  checkout_starts INT NOT NULL DEFAULT 0,
  checkout_completed INT NOT NULL DEFAULT 0,
  checkout_conversion_rate DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  grace_or_churn_risk INT NOT NULL DEFAULT 0,
  comments INT NOT NULL DEFAULT 0,
  reactions INT NOT NULL DEFAULT 0,
  feed_saves INT NOT NULL DEFAULT 0,
  metadata_json JSON DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_launch_revenue_snapshot_date (snapshot_date),
  INDEX idx_launch_revenue_snapshot_revenue (snapshot_date, total_revenue_cents)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS checkout_conversion_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  plan_id INT DEFAULT NULL,
  checkout_id INT DEFAULT NULL,
  event_type ENUM('view_pricing','start_checkout','complete_checkout','cancel_checkout','fail_checkout','upgrade_click','downgrade_click') NOT NULL,
  source VARCHAR(120) DEFAULT NULL,
  amount_cents INT NOT NULL DEFAULT 0,
  metadata_json JSON DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_checkout_conversion_event_type (event_type, created_at),
  INDEX idx_checkout_conversion_user (user_id, event_type, created_at),
  INDEX idx_checkout_conversion_plan (plan_id, event_type, created_at),
  CONSTRAINT fk_checkout_conversion_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_checkout_conversion_plan FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE SET NULL,
  CONSTRAINT fk_checkout_conversion_checkout FOREIGN KEY (checkout_id) REFERENCES subscription_checkouts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO membership_tier_benefits (benefit_key, benefit_label, benefit_group, description, sort_order) VALUES
  ('episodes','Episode streaming','content','Access to released Stonefellow episodes.',10),
  ('full_music','Full soundtrack streaming','music','Stream full soundtrack tracks instead of previews.',20),
  ('playlists','Private playlists','music','Save and manage member playlists.',30),
  ('comments','Fan comments and reactions','community','Comment, react, and join fan threads.',40),
  ('feed','Personalized feed','community','Follow creator/content categories and personalize posts.',50),
  ('downloads','Offline/download access','premium','Download-eligible content and offline-ready benefits.',60),
  ('founder','Founder recognition','premium','Founder badge, supporter wall, and launch collectibles.',70)
ON DUPLICATE KEY UPDATE benefit_label=VALUES(benefit_label), benefit_group=VALUES(benefit_group), description=VALUES(description), sort_order=VALUES(sort_order);

UPDATE subscription_plans SET
  access_label = COALESCE(access_label, CASE
    WHEN slug='monthly-access' THEN 'Core Member'
    WHEN slug='annual-access' THEN 'Premium Member'
    WHEN slug='founding-fan' THEN 'Founding Fan'
    ELSE name
  END),
  launch_position = CASE
    WHEN slug='monthly-access' THEN 'entry'
    WHEN slug='annual-access' THEN 'premium'
    WHEN slug='founding-fan' THEN 'founder'
    ELSE launch_position
  END,
  is_public = 1;

INSERT INTO membership_tier_benefit_map (plan_id, benefit_key, value_label, is_enabled, sort_order)
SELECT id, 'episodes', 'All released episodes', allows_video_streaming, 10 FROM subscription_plans
ON DUPLICATE KEY UPDATE value_label=VALUES(value_label), is_enabled=VALUES(is_enabled), sort_order=VALUES(sort_order);
INSERT INTO membership_tier_benefit_map (plan_id, benefit_key, value_label, is_enabled, sort_order)
SELECT id, 'full_music', 'Full soundtrack', allows_full_music, 20 FROM subscription_plans
ON DUPLICATE KEY UPDATE value_label=VALUES(value_label), is_enabled=VALUES(is_enabled), sort_order=VALUES(sort_order);
INSERT INTO membership_tier_benefit_map (plan_id, benefit_key, value_label, is_enabled, sort_order)
SELECT id, 'playlists', 'Private playlists', allows_playlists, 30 FROM subscription_plans
ON DUPLICATE KEY UPDATE value_label=VALUES(value_label), is_enabled=VALUES(is_enabled), sort_order=VALUES(sort_order);
INSERT INTO membership_tier_benefit_map (plan_id, benefit_key, value_label, is_enabled, sort_order)
SELECT id, 'comments', 'Comment and react', 1, 40 FROM subscription_plans
ON DUPLICATE KEY UPDATE value_label=VALUES(value_label), is_enabled=VALUES(is_enabled), sort_order=VALUES(sort_order);
INSERT INTO membership_tier_benefit_map (plan_id, benefit_key, value_label, is_enabled, sort_order)
SELECT id, 'feed', 'Personalized feed', 1, 50 FROM subscription_plans
ON DUPLICATE KEY UPDATE value_label=VALUES(value_label), is_enabled=VALUES(is_enabled), sort_order=VALUES(sort_order);
INSERT INTO membership_tier_benefit_map (plan_id, benefit_key, value_label, is_enabled, sort_order)
SELECT id, 'downloads', 'Downloads/offline eligible', allows_offline_downloads, 60 FROM subscription_plans
ON DUPLICATE KEY UPDATE value_label=VALUES(value_label), is_enabled=VALUES(is_enabled), sort_order=VALUES(sort_order);
INSERT INTO membership_tier_benefit_map (plan_id, benefit_key, value_label, is_enabled, sort_order)
SELECT id, 'founder', 'Founder recognition', IF(slug='founding-fan',1,0), 70 FROM subscription_plans
ON DUPLICATE KEY UPDATE value_label=VALUES(value_label), is_enabled=VALUES(is_enabled), sort_order=VALUES(sort_order);

DROP PROCEDURE IF EXISTS sf_add_tier_revenue_column;
