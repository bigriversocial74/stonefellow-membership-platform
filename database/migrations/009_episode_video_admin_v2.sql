-- Stonefellow migration 009: season manager, episode publishing metadata, and video release upgrades.
-- Safe to run after the base schema and migrations 001 through 008.
-- Installer-safe version: avoids DELIMITER/stored procedures so it can run through PDO.

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

ALTER TABLE episodes
  ADD COLUMN IF NOT EXISTS `season_id` INT DEFAULT NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `production_code` VARCHAR(40) DEFAULT NULL AFTER `slug`,
  ADD COLUMN IF NOT EXISTS `episode_summary` TEXT DEFAULT NULL AFTER `short_description`,
  ADD COLUMN IF NOT EXISTS `poster_asset_id` INT DEFAULT NULL AFTER `runtime_minutes`,
  ADD COLUMN IF NOT EXISTS `hero_asset_id` INT DEFAULT NULL AFTER `poster_asset_id`,
  ADD COLUMN IF NOT EXISTS `release_at` DATETIME DEFAULT NULL AFTER `hero_asset_id`,
  ADD COLUMN IF NOT EXISTS `access_level` ENUM('public','free_account','subscriber','premium','founding_fan') NOT NULL DEFAULT 'subscriber' AFTER `release_at`,
  ADD COLUMN IF NOT EXISTS `next_episode_id` INT DEFAULT NULL AFTER `access_level`,
  ADD COLUMN IF NOT EXISTS `is_featured` TINYINT(1) NOT NULL DEFAULT 0 AFTER `next_episode_id`;

CREATE INDEX idx_episodes_release_status ON episodes (status, release_at, access_level);
CREATE INDEX idx_episodes_season_v2 ON episodes (season_id, season_number, episode_number);

UPDATE episodes SET season_id = 1 WHERE season_id IS NULL;

ALTER TABLE videos
  ADD COLUMN IF NOT EXISTS `publish_window_start` DATETIME DEFAULT NULL AFTER `release_at`,
  ADD COLUMN IF NOT EXISTS `publish_window_end` DATETIME DEFAULT NULL AFTER `publish_window_start`,
  ADD COLUMN IF NOT EXISTS `geo_policy` ENUM('worldwide','us_only','manual') NOT NULL DEFAULT 'worldwide' AFTER `publish_window_end`,
  ADD COLUMN IF NOT EXISTS `download_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `geo_policy`,
  ADD COLUMN IF NOT EXISTS `watch_next_video_id` INT DEFAULT NULL AFTER `download_enabled`;

CREATE INDEX idx_videos_window_status ON videos (status, release_at, publish_window_start, publish_window_end);

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
