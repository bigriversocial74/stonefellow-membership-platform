-- Stonefellow Storyboarding Season/Episode Bridge v1
-- Purpose: map the existing AI storyboard rows into a real Season 1 / Episode 1 producer workflow.
-- Import after PR deploy and after database/storyboarding_system_v1.sql has been imported.
-- Compatibility: avoids ALTER TABLE ... ADD COLUMN IF NOT EXISTS and CREATE INDEX IF NOT EXISTS
-- so it works on older MySQL/MariaDB versions.

SET @db_name := DATABASE();

SET @sql := IF(
  EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'story_episodes' AND COLUMN_NAME = 'episode_outline'),
  'SELECT 1',
  'ALTER TABLE story_episodes ADD COLUMN episode_outline MEDIUMTEXT NULL AFTER synopsis'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'story_episodes' AND COLUMN_NAME = 'setting_label'),
  'SELECT 1',
  'ALTER TABLE story_episodes ADD COLUMN setting_label VARCHAR(190) DEFAULT '''' AFTER episode_outline'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'story_episodes' AND COLUMN_NAME = 'ai_outline_prompt'),
  'SELECT 1',
  'ALTER TABLE story_episodes ADD COLUMN ai_outline_prompt MEDIUMTEXT NULL AFTER setting_label'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'story_episodes' AND COLUMN_NAME = 'ai_outline_result_json'),
  'SELECT 1',
  'ALTER TABLE story_episodes ADD COLUMN ai_outline_result_json LONGTEXT NULL AFTER ai_outline_prompt'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'story_episodes' AND COLUMN_NAME = 'ai_outline_provider'),
  'SELECT 1',
  'ALTER TABLE story_episodes ADD COLUMN ai_outline_provider VARCHAR(80) DEFAULT '''' AFTER ai_outline_result_json'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'story_episodes' AND COLUMN_NAME = 'ai_outline_status'),
  'SELECT 1',
  'ALTER TABLE story_episodes ADD COLUMN ai_outline_status VARCHAR(40) NOT NULL DEFAULT ''not_started'' AFTER ai_outline_provider'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'story_episodes' AND COLUMN_NAME = 'ai_outline_generated_at'),
  'SELECT 1',
  'ALTER TABLE story_episodes ADD COLUMN ai_outline_generated_at DATETIME NULL AFTER ai_outline_status'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'storyboards' AND COLUMN_NAME = 'story_season_id'),
  'SELECT 1',
  'ALTER TABLE storyboards ADD COLUMN story_season_id BIGINT UNSIGNED NULL AFTER id'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'storyboards' AND COLUMN_NAME = 'story_episode_id'),
  'SELECT 1',
  'ALTER TABLE storyboards ADD COLUMN story_episode_id BIGINT UNSIGNED NULL AFTER story_season_id'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'storyboards' AND COLUMN_NAME = 'producer_scene_order'),
  'SELECT 1',
  'ALTER TABLE storyboards ADD COLUMN producer_scene_order INT UNSIGNED NOT NULL DEFAULT 0 AFTER story_episode_id'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'storyboards' AND COLUMN_NAME = 'producer_scene_status'),
  'SELECT 1',
  'ALTER TABLE storyboards ADD COLUMN producer_scene_status VARCHAR(40) NOT NULL DEFAULT ''outline'' AFTER producer_scene_order'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'storyboards' AND INDEX_NAME = 'idx_storyboards_episode_order'),
  'SELECT 1',
  'CREATE INDEX idx_storyboards_episode_order ON storyboards (story_episode_id, producer_scene_order, id)'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'storyboards' AND INDEX_NAME = 'idx_storyboards_season'),
  'SELECT 1',
  'CREATE INDEX idx_storyboards_season ON storyboards (story_season_id)'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS story_episode_characters (
  story_episode_id BIGINT UNSIGNED NOT NULL,
  story_character_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (story_episode_id, story_character_id),
  KEY idx_story_episode_character_character (story_character_id),
  CONSTRAINT fk_story_episode_character_episode FOREIGN KEY (story_episode_id) REFERENCES story_episodes(id) ON DELETE CASCADE,
  CONSTRAINT fk_story_episode_character_character FOREIGN KEY (story_character_id) REFERENCES story_characters(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO story_seasons (
  season_number,
  title,
  slug,
  logline,
  description,
  theme_notes,
  arc_notes,
  status,
  sort_order
)
VALUES (
  1,
  'Season 1',
  'stonefellow-season-1',
  'Season 1 follows the Stonefellow world as the band, crew, and local characters turn small music moments into a connected story.',
  'Season 1 is the main container for the current Stonefellow storyboards. Because the project was built backwards from visual storyboards, this migration creates the season foundation first, then attaches the existing storyboard scenes to Episode 1.',
  'Music, community, second chances, late nights, and local characters crossing paths around the Stonefellow world.',
  'The season arc begins with a set of standalone scene/storyboard moments that can be organized into a stronger episodic structure as production develops.',
  'active',
  10
)
ON DUPLICATE KEY UPDATE
  title = VALUES(title),
  logline = VALUES(logline),
  description = VALUES(description),
  theme_notes = VALUES(theme_notes),
  arc_notes = VALUES(arc_notes),
  status = VALUES(status),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO story_episodes (
  story_season_id,
  episode_number,
  title,
  slug,
  logline,
  synopsis,
  episode_outline,
  setting_label,
  runtime_target_minutes,
  production_status,
  sort_order
)
SELECT
  s.id,
  1,
  'Episode 1',
  'stonefellow-season-1-episode-1',
  'Episode 1 gathers the current Stonefellow storyboard scenes into the first real episode workflow.',
  'The current AI storyboard records are treated as the first episode scene list. Each row opens the builder, where the scene sheet and visual scene cards are managed.',
  'Episode 1 starts as a producer organization pass. The current storyboard items become the episode scenes, and each scene can be expanded through the existing builder into scene cards, prompts, image direction, dialogue, and continuity notes.',
  'Stonefellow music-world locations, bars, stages, backstage spaces, roads, and community scenes.',
  48,
  'outline',
  10
FROM story_seasons s
WHERE s.slug = 'stonefellow-season-1'
ON DUPLICATE KEY UPDATE
  story_season_id = VALUES(story_season_id),
  logline = VALUES(logline),
  synopsis = VALUES(synopsis),
  episode_outline = VALUES(episode_outline),
  setting_label = VALUES(setting_label),
  runtime_target_minutes = VALUES(runtime_target_minutes),
  production_status = VALUES(production_status),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

UPDATE storyboards sb
JOIN story_seasons s ON s.slug = 'stonefellow-season-1'
JOIN story_episodes e ON e.slug = 'stonefellow-season-1-episode-1'
SET
  sb.story_season_id = s.id,
  sb.story_episode_id = e.id,
  sb.producer_scene_order = IF(sb.producer_scene_order > 0, sb.producer_scene_order, sb.id * 10),
  sb.producer_scene_status = IF(sb.producer_scene_status <> '', sb.producer_scene_status, 'outline'),
  sb.updated_at = NOW()
WHERE sb.story_episode_id IS NULL OR sb.story_episode_id = 0;

INSERT IGNORE INTO story_episode_characters (story_episode_id, story_character_id)
SELECT e.id, c.id
FROM story_episodes e
JOIN story_characters c ON c.status = 'active'
WHERE e.slug = 'stonefellow-season-1-episode-1'
ORDER BY c.sort_order ASC
LIMIT 6;