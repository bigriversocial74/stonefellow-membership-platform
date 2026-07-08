-- Stonefellow Show Theme Image Map v1
-- Run after backing up the database.

CREATE TABLE IF NOT EXISTS show_themes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  theme_name VARCHAR(160) NOT NULL,
  theme_slug VARCHAR(190) NOT NULL UNIQUE,
  description TEXT NULL,
  mood_prompt TEXT NULL,
  palette_json JSON NULL,
  image_model VARCHAR(80) NOT NULL DEFAULT 'gpt-image-1',
  image_quality VARCHAR(40) NOT NULL DEFAULT 'high',
  is_active TINYINT(1) NOT NULL DEFAULT 0,
  status VARCHAR(40) NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS show_theme_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  theme_id INT NOT NULL,
  image_key VARCHAR(120) NOT NULL,
  title VARCHAR(190) NOT NULL,
  page_location VARCHAR(190) NULL,
  current_path VARCHAR(255) NULL,
  generated_path VARCHAR(255) NULL,
  approved_path VARCHAR(255) NULL,
  aspect_ratio VARCHAR(40) NULL,
  recommended_size VARCHAR(80) NULL,
  prompt TEXT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'draft',
  sort_order INT NOT NULL DEFAULT 100,
  last_generated_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_theme_image_key (theme_id, image_key),
  KEY idx_theme_images_theme (theme_id),
  CONSTRAINT fk_show_theme_images_theme FOREIGN KEY (theme_id) REFERENCES show_themes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS show_theme_image_jobs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  theme_id INT NOT NULL,
  theme_image_id INT NULL,
  action_type VARCHAR(60) NOT NULL DEFAULT 'generate_one',
  status VARCHAR(40) NOT NULL DEFAULT 'queued',
  request_payload JSON NULL,
  generated_path VARCHAR(255) NULL,
  error_message TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME NULL,
  KEY idx_theme_jobs_theme (theme_id),
  KEY idx_theme_jobs_image (theme_image_id),
  CONSTRAINT fk_show_theme_jobs_theme FOREIGN KEY (theme_id) REFERENCES show_themes(id) ON DELETE CASCADE,
  CONSTRAINT fk_show_theme_jobs_image FOREIGN KEY (theme_image_id) REFERENCES show_theme_images(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO show_themes (theme_name, theme_slug, description, mood_prompt, palette_json, image_model, image_quality, is_active, status)
VALUES (
  'Stonefellow',
  'stonefellow',
  'Default Stonefellow cinematic western rock drama theme.',
  'Dark cinematic western rock drama, black and charcoal backgrounds, warm gold accents, desert road, smoky bars, stage lights, worn leather, premium streaming-series photography.',
  JSON_OBJECT('background','#030302','panel','#0b0907','accent','#d6ad6c','accent_secondary','#c79a52','text','#ead8bc','muted','#b09b79','border','rgba(214,173,108,.18)'),
  'gpt-image-1',
  'high',
  1,
  'active'
);

INSERT IGNORE INTO show_theme_images (theme_id, image_key, title, page_location, current_path, aspect_ratio, recommended_size, prompt, status, sort_order)
SELECT id, 'home_hero', 'Home Hero', 'index.php hero', 'images/home/home-hero.jpg', '16:9', '1920x1080', 'Main cinematic brand hero for the show. Dark western rock drama scene with desert road, stage lights, guitar case, smoky atmosphere, warm gold highlights, premium streaming series poster feel.', 'active', 10 FROM show_themes WHERE theme_slug='stonefellow';
INSERT IGNORE INTO show_theme_images (theme_id, image_key, title, page_location, current_path, aspect_ratio, recommended_size, prompt, status, sort_order)
SELECT id, 'series_characters_hero', 'Series Characters Hero', 'series-characters.php hero', 'images/cast/cast-template-hero.png', '21:9', '1920x900', 'Cinematic ensemble cast banner, dark western rock mood, silhouettes of musicians and drifters, warm gold rim light, desert and stage blend.', 'active', 20 FROM show_themes WHERE theme_slug='stonefellow';
INSERT IGNORE INTO show_theme_images (theme_id, image_key, title, page_location, current_path, aspect_ratio, recommended_size, prompt, status, sort_order)
SELECT id, 'character_portrait_jax', 'Jax Character Portrait', 'character.php hero/profile', 'images/cast/cast-jax.png', '3:4', '1200x1600', 'Rugged male frontman, dark wavy hair, light stubble, leather jacket, western rock style, moody desert light, cinematic streaming-series portrait.', 'active', 30 FROM show_themes WHERE theme_slug='stonefellow';
INSERT IGNORE INTO show_theme_images (theme_id, image_key, title, page_location, current_path, aspect_ratio, recommended_size, prompt, status, sort_order)
SELECT id, 'music_hero', 'Music Hero', 'music.php hero', 'images/music/music-hero-guitar.png', '16:10', '1600x1000', 'Dramatic guitar and stage-light scene, black and gold color palette, smoky music venue, premium rock drama soundtrack mood.', 'active', 40 FROM show_themes WHERE theme_slug='stonefellow';
INSERT IGNORE INTO show_theme_images (theme_id, image_key, title, page_location, current_path, aspect_ratio, recommended_size, prompt, status, sort_order)
SELECT id, 'album_cover', 'Album Cover', 'album/music/player pages', 'images/music/soundtrack-cover.png', '1:1', '1400x1400', 'Stonefellow album cover, dark road, worn guitar, desert horizon, gold typography mood, cinematic music poster.', 'active', 50 FROM show_themes WHERE theme_slug='stonefellow';
INSERT IGNORE INTO show_theme_images (theme_id, image_key, title, page_location, current_path, aspect_ratio, recommended_size, prompt, status, sort_order)
SELECT id, 'episode_poster', 'Episode Poster', 'episodes.php cards', 'images/episodes/template-card-01.png', '16:9', '1600x900', 'Cinematic episode still, desert road and backstage tension, warm noir lighting, premium streaming series card art.', 'active', 60 FROM show_themes WHERE theme_slug='stonefellow';
INSERT IGNORE INTO show_theme_images (theme_id, image_key, title, page_location, current_path, aspect_ratio, recommended_size, prompt, status, sort_order)
SELECT id, 'merch_hero', 'Merch Hero', 'merch.php hero', 'images/merch/merch-hero.png', '16:9', '1920x1080', 'Premium merch campaign image for a dark western rock drama, hoodie and apparel flatlay, warm studio lighting, black and gold palette.', 'active', 70 FROM show_themes WHERE theme_slug='stonefellow';
