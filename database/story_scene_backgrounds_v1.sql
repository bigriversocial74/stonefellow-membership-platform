-- Stonefellow Scene Backgrounds v1
-- Reusable background/location continuity catalog for storyboard scene cards.
-- Import after a database backup and after storyboarding_system_v1.sql.

CREATE TABLE IF NOT EXISTS story_scene_backgrounds (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  background_name VARCHAR(190) NOT NULL,
  slug VARCHAR(220) NOT NULL,
  background_type VARCHAR(80) NOT NULL DEFAULT 'location',
  location_label VARCHAR(190) DEFAULT '',
  time_of_day VARCHAR(80) DEFAULT '',
  short_description TEXT NULL,
  continuity_notes TEXT NULL,
  image_path VARCHAR(255) DEFAULT '',
  status VARCHAR(40) NOT NULL DEFAULT 'active',
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_by_user_id BIGINT UNSIGNED NULL,
  updated_by_user_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_story_scene_background_slug (slug),
  KEY idx_story_scene_background_type (background_type),
  KEY idx_story_scene_background_status_order (status, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS storyboard_scene_backgrounds (
  storyboard_scene_id BIGINT UNSIGNED NOT NULL,
  story_scene_background_id BIGINT UNSIGNED NOT NULL,
  usage_notes TEXT NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (storyboard_scene_id),
  KEY idx_storyboard_scene_background_background (story_scene_background_id),
  CONSTRAINT fk_storyboard_scene_background_scene FOREIGN KEY (storyboard_scene_id) REFERENCES storyboard_scenes(id) ON DELETE CASCADE,
  CONSTRAINT fk_storyboard_scene_background_background FOREIGN KEY (story_scene_background_id) REFERENCES story_scene_backgrounds(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO story_scene_backgrounds (background_name, slug, background_type, location_label, time_of_day, short_description, continuity_notes, image_path, status, sort_order)
SELECT 'Backstage Green Room', 'backstage-green-room', 'interior', 'Backstage green room', 'night', 'Recurring backstage room for band prep, private conversations, and pre-show tension.', 'Keep couch layout, warm practical lights, posters, cases, and clutter consistent across scene cards.', '', 'active', 10
WHERE NOT EXISTS (SELECT 1 FROM story_scene_backgrounds WHERE slug = 'backstage-green-room');

INSERT INTO story_scene_backgrounds (background_name, slug, background_type, location_label, time_of_day, short_description, continuity_notes, image_path, status, sort_order)
SELECT 'Desert Road Stop', 'desert-road-stop', 'exterior', 'Arizona desert road stop', 'golden hour', 'Recurring desert highway stop / road-trip visual environment.', 'Keep wide desert horizon, road texture, van placement area, and warm Arizona light consistent.', '', 'active', 20
WHERE NOT EXISTS (SELECT 1 FROM story_scene_backgrounds WHERE slug = 'desert-road-stop');
