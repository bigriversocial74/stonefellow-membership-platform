-- Stonefellow Storyboarding System v1
-- Season -> Episode -> Scene Sheet -> Scene Card planning layer.
-- Import after a database backup. This migration is additive and does not replace
-- the existing public seasons/episodes tables or the AI storyboard builder tables.

CREATE TABLE IF NOT EXISTS story_seasons (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  season_number INT UNSIGNED NOT NULL DEFAULT 1,
  title VARCHAR(190) NOT NULL,
  slug VARCHAR(220) NOT NULL,
  logline VARCHAR(500) DEFAULT '',
  description TEXT NULL,
  theme_notes TEXT NULL,
  arc_notes TEXT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'draft',
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_by_user_id BIGINT UNSIGNED NULL,
  updated_by_user_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_story_season_slug (slug),
  KEY idx_story_season_order (sort_order, season_number),
  KEY idx_story_season_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS story_episodes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  story_season_id BIGINT UNSIGNED NOT NULL,
  episode_number INT UNSIGNED NOT NULL DEFAULT 1,
  title VARCHAR(190) NOT NULL,
  slug VARCHAR(220) NOT NULL,
  logline VARCHAR(500) DEFAULT '',
  synopsis TEXT NULL,
  runtime_target_minutes INT UNSIGNED NULL,
  production_status VARCHAR(60) NOT NULL DEFAULT 'outline',
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_by_user_id BIGINT UNSIGNED NULL,
  updated_by_user_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_story_episode_slug (slug),
  KEY idx_story_episode_season_order (story_season_id, sort_order, episode_number),
  KEY idx_story_episode_status (production_status),
  CONSTRAINT fk_story_episode_season FOREIGN KEY (story_season_id) REFERENCES story_seasons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS story_characters (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  character_name VARCHAR(190) NOT NULL,
  slug VARCHAR(220) NOT NULL,
  actor_name VARCHAR(190) DEFAULT '',
  role_type VARCHAR(80) NOT NULL DEFAULT 'supporting',
  short_bio TEXT NULL,
  motivation TEXT NULL,
  personality_notes TEXT NULL,
  relationship_notes TEXT NULL,
  season_arc TEXT NULL,
  image_path VARCHAR(255) DEFAULT '',
  status VARCHAR(40) NOT NULL DEFAULT 'active',
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_by_user_id BIGINT UNSIGNED NULL,
  updated_by_user_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_story_character_slug (slug),
  KEY idx_story_character_role (role_type),
  KEY idx_story_character_status_order (status, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS story_scene_sheets (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  story_episode_id BIGINT UNSIGNED NOT NULL,
  scene_number INT UNSIGNED NOT NULL DEFAULT 1,
  scene_title VARCHAR(190) NOT NULL,
  location_label VARCHAR(190) DEFAULT '',
  time_of_day VARCHAR(80) DEFAULT '',
  scene_summary TEXT NULL,
  scene_purpose TEXT NULL,
  emotional_beat TEXT NULL,
  conflict_notes TEXT NULL,
  production_notes TEXT NULL,
  scene_status VARCHAR(40) NOT NULL DEFAULT 'draft',
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_by_user_id BIGINT UNSIGNED NULL,
  updated_by_user_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_story_scene_episode_order (story_episode_id, sort_order, scene_number),
  KEY idx_story_scene_status (scene_status),
  CONSTRAINT fk_story_scene_episode FOREIGN KEY (story_episode_id) REFERENCES story_episodes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS story_scene_cards (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  story_scene_sheet_id BIGINT UNSIGNED NOT NULL,
  card_type VARCHAR(60) NOT NULL DEFAULT 'beat',
  card_title VARCHAR(190) NOT NULL,
  card_body TEXT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_by_user_id BIGINT UNSIGNED NULL,
  updated_by_user_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_story_card_scene_order (story_scene_sheet_id, sort_order, id),
  KEY idx_story_card_type (card_type),
  CONSTRAINT fk_story_card_scene FOREIGN KEY (story_scene_sheet_id) REFERENCES story_scene_sheets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS story_scene_sheet_characters (
  story_scene_sheet_id BIGINT UNSIGNED NOT NULL,
  story_character_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (story_scene_sheet_id, story_character_id),
  KEY idx_story_scene_character_character (story_character_id),
  CONSTRAINT fk_story_scene_character_scene FOREIGN KEY (story_scene_sheet_id) REFERENCES story_scene_sheets(id) ON DELETE CASCADE,
  CONSTRAINT fk_story_scene_character_character FOREIGN KEY (story_character_id) REFERENCES story_characters(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS story_scene_card_characters (
  story_scene_card_id BIGINT UNSIGNED NOT NULL,
  story_character_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (story_scene_card_id, story_character_id),
  KEY idx_story_card_character_character (story_character_id),
  CONSTRAINT fk_story_card_character_card FOREIGN KEY (story_scene_card_id) REFERENCES story_scene_cards(id) ON DELETE CASCADE,
  CONSTRAINT fk_story_card_character_character FOREIGN KEY (story_character_id) REFERENCES story_characters(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO story_seasons (season_number, title, slug, logline, description, theme_notes, arc_notes, status, sort_order)
SELECT 1, 'Season 1', 'season-1-story-bible', 'The band rebuilds itself while every secret threatens the comeback.', 'Primary Stonefellow season planning container.', 'Found family, second chances, music as confession.', 'A comeback begins as old wounds reopen.', 'active', 10
WHERE NOT EXISTS (SELECT 1 FROM story_seasons WHERE slug = 'season-1-story-bible');

INSERT INTO story_episodes (story_season_id, episode_number, title, slug, logline, synopsis, runtime_target_minutes, production_status, sort_order)
SELECT s.id, 1, 'First to Fall', 'first-to-fall-story-outline', 'A forgotten band gets one last shot, but the past refuses to stay quiet.', 'Pilot episode planning outline for Stonefellow.', 48, 'outline', 10
FROM story_seasons s
WHERE s.slug = 'season-1-story-bible'
  AND NOT EXISTS (SELECT 1 FROM story_episodes WHERE slug = 'first-to-fall-story-outline');

INSERT INTO story_characters (character_name, slug, actor_name, role_type, short_bio, motivation, personality_notes, relationship_notes, season_arc, image_path, status, sort_order)
SELECT 'Jax Stonefellow', 'jax-stonefellow', '', 'lead', 'Singer and guitarist carrying the weight of the band’s past.', 'Wants the music to mean something again.', 'Charismatic, guarded, funny under pressure.', 'Complicated history with the band and everyone who believed in him.', 'Learns whether redemption is possible when fame returns.', 'images/cast/cast-jax.png', 'active', 10
WHERE NOT EXISTS (SELECT 1 FROM story_characters WHERE slug = 'jax-stonefellow');

INSERT INTO story_characters (character_name, slug, actor_name, role_type, short_bio, motivation, personality_notes, relationship_notes, season_arc, image_path, status, sort_order)
SELECT 'Violet Graves', 'violet-graves', '', 'supporting', 'Keys player with sharp instincts and a long memory.', 'Wants truth without losing the music.', 'Direct, stylish, emotionally observant.', 'Sees through Jax faster than anyone else.', 'Becomes the moral pressure point of the comeback.', 'images/cast/cast-violet.png', 'active', 20
WHERE NOT EXISTS (SELECT 1 FROM story_characters WHERE slug = 'violet-graves');