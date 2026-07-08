-- Stonefellow Series Assets v1
-- Consistent reusable props, instruments, wardrobe, vehicles, locations, and objects.
-- Import after a database backup and after storyboarding_system_v1.sql.

CREATE TABLE IF NOT EXISTS story_series_assets (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  asset_name VARCHAR(190) NOT NULL,
  slug VARCHAR(220) NOT NULL,
  asset_type VARCHAR(80) NOT NULL DEFAULT 'prop',
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
  UNIQUE KEY uniq_story_series_asset_slug (slug),
  KEY idx_story_series_asset_type (asset_type),
  KEY idx_story_series_asset_status_order (status, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS story_character_series_assets (
  story_character_id BIGINT UNSIGNED NOT NULL,
  story_series_asset_id BIGINT UNSIGNED NOT NULL,
  assignment_notes TEXT NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (story_character_id, story_series_asset_id),
  KEY idx_story_character_asset_asset (story_series_asset_id),
  CONSTRAINT fk_story_character_asset_character FOREIGN KEY (story_character_id) REFERENCES story_characters(id) ON DELETE CASCADE,
  CONSTRAINT fk_story_character_asset_asset FOREIGN KEY (story_series_asset_id) REFERENCES story_series_assets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS storyboard_series_assets (
  storyboard_id BIGINT UNSIGNED NOT NULL,
  story_series_asset_id BIGINT UNSIGNED NOT NULL,
  usage_notes TEXT NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (storyboard_id, story_series_asset_id),
  KEY idx_storyboard_series_asset_asset (story_series_asset_id),
  CONSTRAINT fk_storyboard_series_asset_storyboard FOREIGN KEY (storyboard_id) REFERENCES storyboards(id) ON DELETE CASCADE,
  CONSTRAINT fk_storyboard_series_asset_asset FOREIGN KEY (story_series_asset_id) REFERENCES story_series_assets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS storyboard_scene_series_assets (
  storyboard_scene_id BIGINT UNSIGNED NOT NULL,
  story_series_asset_id BIGINT UNSIGNED NOT NULL,
  usage_notes TEXT NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (storyboard_scene_id, story_series_asset_id),
  KEY idx_storyboard_scene_asset_asset (story_series_asset_id),
  CONSTRAINT fk_storyboard_scene_asset_scene FOREIGN KEY (storyboard_scene_id) REFERENCES storyboard_scenes(id) ON DELETE CASCADE,
  CONSTRAINT fk_storyboard_scene_asset_asset FOREIGN KEY (story_series_asset_id) REFERENCES story_series_assets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO story_series_assets (asset_name, slug, asset_type, short_description, continuity_notes, image_path, status, sort_order)
SELECT 'Jax Guitar', 'jax-guitar', 'instrument', 'Primary guitar that reappears across the Stonefellow series.', 'Keep body shape, color, strap, wear marks, and performance handling consistent across scenes.', '', 'active', 10
WHERE NOT EXISTS (SELECT 1 FROM story_series_assets WHERE slug = 'jax-guitar');

INSERT INTO story_series_assets (asset_name, slug, asset_type, short_description, continuity_notes, image_path, status, sort_order)
SELECT 'Stonefellow Van', 'stonefellow-van', 'vehicle', 'Tour van / road vehicle used as a recurring visual anchor.', 'Keep exterior style, condition, decals, road wear, and interior prop layout consistent.', '', 'active', 20
WHERE NOT EXISTS (SELECT 1 FROM story_series_assets WHERE slug = 'stonefellow-van');
