-- Stonefellow migration 009: season manager, episode publishing metadata, and video release upgrades.
-- Safe to run after the base schema and migrations 001 through 008.

DROP PROCEDURE IF EXISTS sf_add_video_v2_column;
DROP PROCEDURE IF EXISTS sf_add_video_v2_index;
DELIMITER //
CREATE PROCEDURE sf_add_video_v2_column(IN table_name VARCHAR(64), IN column_name VARCHAR(64), IN column_definition TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = table_name AND COLUMN_NAME = column_name
  ) THEN
    SET @ddl = CONCAT('ALTER TABLE `', table_name, '` ADD COLUMN ', column_definition);
    PREPARE stmt FROM @ddl;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END //
CREATE PROCEDURE sf_add_video_v2_index(IN table_name VARCHAR(64), IN index_name VARCHAR(64), IN index_definition TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = table_name AND INDEX_NAME = index_name
  ) THEN
    SET @ddl = CONCAT('CREATE INDEX ', index_definition);
    PREPARE stmt FROM @ddl;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END //
DELIMITER ;

CREATE TABLE IF NOT EXISTS seasons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  season_number INT NOT NULL,
  title VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  description TEXT DEFAULT NULL,
  release_year INT DEFAULT NULL,
  poster_asset_id INT DEFAULT NULL,
  status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_seasons_status_number (status, season_number),
  FOREIGN KEY (poster_asset_id) REFERENCES media_assets(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO seasons (id, season_number, title, slug, description, release_year, status)
VALUES (1, 1, 'Season 1', 'season-1', 'The first Stonefellow season arc.', YEAR(CURDATE()), 'published')
ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description), status = VALUES(status);

CALL sf_add_video_v2_column('episodes', 'season_id', '`season_id` INT DEFAULT NULL AFTER `id`');
CALL sf_add_video_v2_column('episodes', 'production_code', '`production_code` VARCHAR(40) DEFAULT NULL AFTER `slug`');
CALL sf_add_video_v2_column('episodes', 'episode_summary', '`episode_summary` TEXT DEFAULT NULL AFTER `short_description`');
CALL sf_add_video_v2_column('episodes', 'poster_asset_id', '`poster_asset_id` INT DEFAULT NULL AFTER `runtime_minutes`');
CALL sf_add_video_v2_column('episodes', 'hero_asset_id', '`hero_asset_id` INT DEFAULT NULL AFTER `poster_asset_id`');
CALL sf_add_video_v2_column('episodes', 'release_at', '`release_at` DATETIME DEFAULT NULL AFTER `hero_asset_id`');
CALL sf_add_video_v2_column('episodes', 'access_level', '`access_level` ENUM(''public'',''free_account'',''subscriber'',''premium'',''founding_fan'') NOT NULL DEFAULT ''subscriber'' AFTER `release_at`');
CALL sf_add_video_v2_column('episodes', 'next_episode_id', '`next_episode_id` INT DEFAULT NULL AFTER `access_level`');
CALL sf_add_video_v2_column('episodes', 'is_featured', '`is_featured` TINYINT(1) NOT NULL DEFAULT 0 AFTER `next_episode_id`');
CALL sf_add_video_v2_index('episodes', 'idx_episodes_release_status', 'idx_episodes_release_status ON episodes (status, release_at, access_level)');
CALL sf_add_video_v2_index('episodes', 'idx_episodes_season_v2', 'idx_episodes_season_v2 ON episodes (season_id, season_number, episode_number)');

UPDATE episodes SET season_id = 1 WHERE season_id IS NULL;

CALL sf_add_video_v2_column('videos', 'publish_window_start', '`publish_window_start` DATETIME DEFAULT NULL AFTER `release_at`');
CALL sf_add_video_v2_column('videos', 'publish_window_end', '`publish_window_end` DATETIME DEFAULT NULL AFTER `publish_window_start`');
CALL sf_add_video_v2_column('videos', 'geo_policy', '`geo_policy` ENUM(''worldwide'',''us_only'',''manual'') NOT NULL DEFAULT ''worldwide'' AFTER `publish_window_end`');
CALL sf_add_video_v2_column('videos', 'download_enabled', '`download_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `geo_policy`');
CALL sf_add_video_v2_column('videos', 'watch_next_video_id', '`watch_next_video_id` INT DEFAULT NULL AFTER `download_enabled`');
CALL sf_add_video_v2_index('videos', 'idx_videos_window_status', 'idx_videos_window_status ON videos (status, release_at, publish_window_start, publish_window_end)');

CREATE TABLE IF NOT EXISTS video_chapters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  video_id INT NOT NULL,
  title VARCHAR(190) NOT NULL,
  starts_at_seconds INT NOT NULL DEFAULT 0,
  ends_at_seconds INT DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_video_chapters_video_sort (video_id, sort_order, starts_at_seconds),
  FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS sf_add_video_v2_column;
DROP PROCEDURE IF EXISTS sf_add_video_v2_index;
