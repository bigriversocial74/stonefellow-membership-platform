-- Stonefellow streaming catalog foundation
-- Public music landing pages use preview files only. Subscriber pages should use full files after entitlement checks.

CREATE TABLE IF NOT EXISTS media_assets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(190) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  file_type ENUM('image','video','audio','document') NOT NULL DEFAULT 'image',
  alt_text VARCHAR(255) DEFAULT NULL,
  usage_key VARCHAR(120) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  display_name VARCHAR(120) DEFAULT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  status ENUM('active','inactive','banned') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscription_plans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(120) NOT NULL UNIQUE,
  price_cents INT NOT NULL,
  billing_interval ENUM('month','year') NOT NULL DEFAULT 'month',
  description VARCHAR(255) DEFAULT NULL,
  allows_full_music TINYINT(1) NOT NULL DEFAULT 1,
  allows_offline_downloads TINYINT(1) NOT NULL DEFAULT 0,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_subscriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  plan_id INT NOT NULL,
  status ENUM('active','trialing','past_due','canceled','expired') NOT NULL DEFAULT 'trialing',
  current_period_start DATETIME DEFAULT NULL,
  current_period_end DATETIME DEFAULT NULL,
  external_subscription_id VARCHAR(190) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_user_subscription_status (user_id, status),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS albums (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  artist VARCHAR(190) NOT NULL DEFAULT 'Stonefellow',
  description TEXT DEFAULT NULL,
  cover_asset_id INT DEFAULT NULL,
  release_date DATE DEFAULT NULL,
  status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (cover_asset_id) REFERENCES media_assets(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS songs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  album_id INT DEFAULT NULL,
  title VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  artist VARCHAR(190) NOT NULL DEFAULT 'Stonefellow',
  track_number INT DEFAULT NULL,
  duration_seconds INT DEFAULT NULL,
  cover_asset_id INT DEFAULT NULL,
  access_level ENUM('free_preview','subscriber','premium') NOT NULL DEFAULT 'subscriber',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_songs_album_track (album_id, track_number),
  INDEX idx_songs_status_access (status, access_level),
  FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE SET NULL,
  FOREIGN KEY (cover_asset_id) REFERENCES media_assets(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS song_files (
  id INT AUTO_INCREMENT PRIMARY KEY,
  song_id INT NOT NULL,
  file_type ENUM('preview','full','live','demo','acoustic') NOT NULL DEFAULT 'full',
  file_path VARCHAR(255) NOT NULL,
  duration_seconds INT DEFAULT NULL,
  preview_seconds INT DEFAULT NULL,
  bitrate_kbps INT DEFAULT NULL,
  mime_type VARCHAR(80) DEFAULT 'audio/wav',
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_song_file_type_primary (song_id, file_type, is_primary),
  INDEX idx_song_files_type (file_type),
  FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS episodes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  season_number INT NOT NULL DEFAULT 1,
  episode_number INT NOT NULL,
  title VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  short_description VARCHAR(500) DEFAULT NULL,
  runtime_minutes INT DEFAULT NULL,
  status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS song_episode_links (
  id INT AUTO_INCREMENT PRIMARY KEY,
  song_id INT NOT NULL,
  episode_id INT NOT NULL,
  scene_note VARCHAR(255) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  UNIQUE KEY unique_song_episode (song_id, episode_id),
  FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE,
  FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS playlists (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  title VARCHAR(190) NOT NULL,
  slug VARCHAR(190) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  visibility ENUM('private','public','system') NOT NULL DEFAULT 'private',
  cover_asset_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_playlists_user_visibility (user_id, visibility),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (cover_asset_id) REFERENCES media_assets(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS playlist_songs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  playlist_id INT NOT NULL,
  song_id INT NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_playlist_song (playlist_id, song_id),
  FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE,
  FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_saved_songs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  song_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_song (user_id, song_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_play_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  song_id INT NOT NULL,
  file_type ENUM('preview','full','live','demo','acoustic') NOT NULL DEFAULT 'preview',
  seconds_played INT NOT NULL DEFAULT 0,
  completed TINYINT(1) NOT NULL DEFAULT 0,
  ip_hash VARCHAR(128) DEFAULT NULL,
  user_agent_hash VARCHAR(128) DEFAULT NULL,
  played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_play_history_user_time (user_id, played_at),
  INDEX idx_play_history_song_time (song_id, played_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS streaming_entitlements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  entitlement_type ENUM('full_music','offline_downloads','premium_music','live_sessions') NOT NULL,
  source_type ENUM('subscription','purchase','admin_grant') NOT NULL DEFAULT 'subscription',
  starts_at DATETIME DEFAULT NULL,
  expires_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_entitlements_user_type (user_id, entitlement_type),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO albums (id, title, slug, artist, description, release_date, status)
VALUES (1, 'The Road Is Calling', 'the-road-is-calling', 'Stonefellow', 'Official soundtrack catalog for the Stonefellow original series.', NULL, 'published')
ON DUPLICATE KEY UPDATE title = VALUES(title), artist = VALUES(artist), description = VALUES(description), status = VALUES(status);

INSERT INTO episodes (id, season_number, episode_number, title, slug, short_description, runtime_minutes, status)
VALUES
(1, 1, 1, 'First to Fall', 'first-to-fall', 'The beginning of the band.', 48, 'published'),
(2, 1, 2, 'Blackout', 'blackout', 'Old wounds resurface.', 46, 'published'),
(3, 1, 3, 'Ghosts on the Coast', 'ghosts-on-the-coast', 'The road catches up.', 45, 'published'),
(4, 1, 4, 'Burn', 'burn', 'Fame comes at a cost.', 47, 'published'),
(5, 1, 5, 'Nothing Left', 'nothing-left', 'Choices define the road ahead.', 43, 'published')
ON DUPLICATE KEY UPDATE title = VALUES(title), short_description = VALUES(short_description), runtime_minutes = VALUES(runtime_minutes), status = VALUES(status);

INSERT INTO songs (id, album_id, title, slug, artist, track_number, duration_seconds, access_level, is_featured, status)
VALUES
(1, 1, 'Born to Burn', 'born-to-burn', 'Stonefellow', 1, 228, 'subscriber', 1, 'published'),
(2, 1, 'Blackout in the Rearview', 'blackout-in-the-rearview', 'Stonefellow', 2, 215, 'subscriber', 0, 'published'),
(3, 1, 'Tearing Down the Walls', 'tearing-down-the-walls', 'Stonefellow', 3, 242, 'subscriber', 0, 'published'),
(4, 1, 'Heart of a Loaded Gun', 'heart-of-a-loaded-gun', 'Stonefellow', 4, 237, 'subscriber', 0, 'published'),
(5, 1, 'Saint or Sinner', 'saint-or-sinner', 'Stonefellow', 5, 221, 'subscriber', 0, 'published'),
(6, 1, 'Riptide', 'riptide', 'Stonefellow', 6, 252, 'subscriber', 0, 'published'),
(7, 1, 'Long Road Home', 'long-road-home', 'Stonefellow', 7, 236, 'subscriber', 0, 'published'),
(8, 1, 'Burn It Down', 'burn-it-down', 'Stonefellow', 8, 265, 'subscriber', 0, 'published'),
(9, 1, 'Nothing Left', 'nothing-left', 'Stonefellow', 9, 224, 'subscriber', 0, 'published'),
(10, 1, 'The Road Is Calling', 'the-road-is-calling', 'Stonefellow', 10, 229, 'subscriber', 0, 'published')
ON DUPLICATE KEY UPDATE album_id = VALUES(album_id), track_number = VALUES(track_number), duration_seconds = VALUES(duration_seconds), access_level = VALUES(access_level), is_featured = VALUES(is_featured), status = VALUES(status);

INSERT INTO song_files (song_id, file_type, file_path, duration_seconds, preview_seconds, mime_type, is_primary)
VALUES
(1, 'preview', 'assets/audio/previews/born-to-burn-preview.wav', 30, 30, 'audio/wav', 1),
(2, 'preview', 'assets/audio/previews/blackout-in-the-rearview-preview.wav', 30, 30, 'audio/wav', 1),
(3, 'preview', 'assets/audio/previews/tearing-down-the-walls-preview.wav', 30, 30, 'audio/wav', 1),
(4, 'preview', 'assets/audio/previews/heart-of-a-loaded-gun-preview.wav', 30, 30, 'audio/wav', 1),
(5, 'preview', 'assets/audio/previews/saint-or-sinner-preview.wav', 30, 30, 'audio/wav', 1),
(6, 'preview', 'assets/audio/previews/riptide-preview.wav', 30, 30, 'audio/wav', 1),
(7, 'preview', 'assets/audio/previews/long-road-home-preview.wav', 30, 30, 'audio/wav', 1),
(8, 'preview', 'assets/audio/previews/burn-it-down-preview.wav', 30, 30, 'audio/wav', 1),
(9, 'preview', 'assets/audio/previews/nothing-left-preview.wav', 30, 30, 'audio/wav', 1),
(10, 'preview', 'assets/audio/previews/the-road-is-calling-preview.wav', 30, 30, 'audio/wav', 1)
ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), duration_seconds = VALUES(duration_seconds), preview_seconds = VALUES(preview_seconds), mime_type = VALUES(mime_type);

INSERT INTO song_episode_links (song_id, episode_id, scene_note, sort_order)
VALUES
(1, 1, 'Pilot theme track', 1),
(2, 1, 'Rearview scene', 2),
(3, 2, 'Conflict sequence', 1),
(4, 3, 'Coastal road sequence', 1),
(5, 4, 'Burn sequence', 1),
(9, 5, 'Final act sequence', 2),
(10, 1, 'Album title theme', 3)
ON DUPLICATE KEY UPDATE scene_note = VALUES(scene_note), sort_order = VALUES(sort_order);

INSERT INTO playlists (id, user_id, title, slug, description, visibility)
VALUES
(1, NULL, 'Road Songs', 'road-songs', 'System playlist for driving, escape, and bad decisions.', 'system'),
(2, NULL, 'Live Sessions', 'live-sessions', 'System playlist for live and acoustic performances.', 'system')
ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description), visibility = VALUES(visibility);

INSERT INTO playlist_songs (playlist_id, song_id, sort_order)
VALUES
(1, 1, 1), (1, 2, 2), (1, 4, 3), (1, 7, 4), (1, 9, 5), (1, 10, 6),
(2, 6, 1), (2, 7, 2), (2, 8, 3), (2, 9, 4)
ON DUPLICATE KEY UPDATE sort_order = VALUES(sort_order);

-- Auth/account recovery additions for signup, signin, forgot password, and reset password pages.
CREATE TABLE IF NOT EXISTS user_auth_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  selector VARCHAR(64) NOT NULL UNIQUE,
  token_hash VARCHAR(255) NOT NULL,
  token_type ENUM('remember_me','email_verify','password_reset') NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_auth_token_user_type (user_id, token_type),
  INDEX idx_auth_token_expires (expires_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) DEFAULT NULL,
  ip_address VARCHAR(64) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  success TINYINT(1) NOT NULL DEFAULT 0,
  attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_login_attempt_email_time (email, attempted_at),
  INDEX idx_login_attempt_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS email_verified_at DATETIME DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS last_login_at DATETIME DEFAULT NULL;

-- Ecommerce/store additions for merch.php, product.php, cart.php, checkout.php, and order-confirmation.php.
CREATE TABLE IF NOT EXISTS product_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(120) NOT NULL UNIQUE,
  description TEXT DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT DEFAULT NULL,
  name VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  short_description VARCHAR(255) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  price_cents INT NOT NULL,
  compare_at_price_cents INT DEFAULT NULL,
  sku VARCHAR(120) DEFAULT NULL,
  inventory_quantity INT NOT NULL DEFAULT 0,
  product_type ENUM('physical','digital','bundle') NOT NULL DEFAULT 'physical',
  access_level ENUM('public','subscriber','founding_fan') NOT NULL DEFAULT 'public',
  badge_label VARCHAR(80) DEFAULT NULL,
  primary_image_asset_id INT DEFAULT NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  is_limited_drop TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('draft','active','sold_out','archived') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_products_category_status (category_id, status),
  INDEX idx_products_featured (is_featured, status),
  FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE SET NULL,
  FOREIGN KEY (primary_image_asset_id) REFERENCES media_assets(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  media_asset_id INT NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (media_asset_id) REFERENCES media_assets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_variants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  variant_name VARCHAR(120) NOT NULL,
  sku VARCHAR(120) DEFAULT NULL,
  size VARCHAR(50) DEFAULT NULL,
  color VARCHAR(80) DEFAULT NULL,
  price_cents INT DEFAULT NULL,
  inventory_quantity INT NOT NULL DEFAULT 0,
  status ENUM('active','sold_out','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS carts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  session_id VARCHAR(190) DEFAULT NULL,
  status ENUM('active','converted','abandoned') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_carts_user_status (user_id, status),
  INDEX idx_carts_session_status (session_id, status),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cart_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cart_id INT NOT NULL,
  product_id INT NOT NULL,
  variant_id INT DEFAULT NULL,
  quantity INT NOT NULL DEFAULT 1,
  unit_price_cents INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_cart_product_variant (cart_id, product_id, variant_id),
  FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  order_number VARCHAR(80) NOT NULL UNIQUE,
  status ENUM('pending','paid','fulfilled','canceled','refunded') NOT NULL DEFAULT 'pending',
  subtotal_cents INT NOT NULL DEFAULT 0,
  shipping_cents INT NOT NULL DEFAULT 0,
  tax_cents INT NOT NULL DEFAULT 0,
  total_cents INT NOT NULL DEFAULT 0,
  customer_email VARCHAR(190) DEFAULT NULL,
  shipping_name VARCHAR(190) DEFAULT NULL,
  shipping_address_1 VARCHAR(190) DEFAULT NULL,
  shipping_address_2 VARCHAR(190) DEFAULT NULL,
  shipping_city VARCHAR(120) DEFAULT NULL,
  shipping_state VARCHAR(120) DEFAULT NULL,
  shipping_postal_code VARCHAR(40) DEFAULT NULL,
  shipping_country VARCHAR(80) DEFAULT 'US',
  external_payment_id VARCHAR(190) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_orders_user_status (user_id, status),
  INDEX idx_orders_email_created (customer_email, created_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  variant_id INT DEFAULT NULL,
  product_name VARCHAR(190) NOT NULL,
  variant_name VARCHAR(120) DEFAULT NULL,
  quantity INT NOT NULL,
  unit_price_cents INT NOT NULL,
  total_price_cents INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO product_categories (id, name, slug, description, sort_order)
VALUES
(1, 'Apparel', 'apparel', 'T-shirts, hoodies, and wearable Stonefellow gear.', 1),
(2, 'Music', 'music', 'Vinyl, soundtrack editions, and digital music products.', 2),
(3, 'Posters', 'posters', 'Tour-style posters and episode art.', 3),
(4, 'Accessories', 'accessories', 'Picks, lanyards, and small collectibles.', 4),
(5, 'Bundles', 'bundles', 'Limited launch and subscriber bundles.', 5)
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), sort_order = VALUES(sort_order);

INSERT INTO products (id, category_id, name, slug, short_description, description, price_cents, sku, inventory_quantity, product_type, access_level, badge_label, is_featured, is_limited_drop, status)
VALUES
(1, 1, 'Stonefellow Crest Tee', 'stonefellow-crest-tee', 'Soft black tour tee with the Stonefellow crest.', 'Soft black tour tee with the Stonefellow crest across the chest. Built for show nights, road trips, and founding fans.', 3400, 'SF-TEE-CREST', 250, 'physical', 'public', 'Featured', 1, 0, 'active'),
(2, 1, 'Pirate Rock Hoodie', 'pirate-rock-hoodie', 'Heavyweight hoodie with the pirate-rock skull mark.', 'Heavyweight hoodie with the pirate-rock skull mark, worn-in gold ink, and backstage pass attitude.', 7200, 'SF-HOOD-PIRATE', 120, 'physical', 'public', 'Limited', 1, 1, 'active'),
(3, 3, 'Live Sessions Poster', 'live-sessions-poster', 'Large-format live session poster.', 'Large-format live session poster with smoky stage lighting and Stonefellow tour-style typography.', 2800, 'SF-POST-LIVE', 180, 'physical', 'public', 'New', 0, 0, 'active'),
(4, 2, 'Official Soundtrack Vinyl', 'official-soundtrack-vinyl', 'Collector vinyl edition of The Road Is Calling.', 'Collector vinyl edition of The Road Is Calling with black-and-gold jacket art and digital download code.', 3900, 'SF-VINYL-ROAD', 300, 'physical', 'public', 'Preorder', 1, 0, 'active'),
(5, 4, 'SF Guitar Pick Set', 'sf-guitar-pick-set', 'Six-pick set with Stonefellow marks.', 'Six-pick set with crest, sword, and pirate-rock marks packaged like backstage memorabilia.', 1600, 'SF-PICK-SET', 500, 'physical', 'public', 'Accessory', 0, 0, 'active'),
(6, 5, 'Pilot Launch Bundle', 'pilot-launch-bundle', 'Tee, poster, pick set, and soundtrack preorder.', 'Tee, poster, pick set, and soundtrack preorder bundled for the pilot launch window.', 9900, 'SF-BUNDLE-PILOT', 100, 'bundle', 'subscriber', 'Bundle', 1, 1, 'active')
ON DUPLICATE KEY UPDATE category_id = VALUES(category_id), short_description = VALUES(short_description), description = VALUES(description), price_cents = VALUES(price_cents), inventory_quantity = VALUES(inventory_quantity), badge_label = VALUES(badge_label), is_featured = VALUES(is_featured), is_limited_drop = VALUES(is_limited_drop), status = VALUES(status);
