-- Stonefellow migration 001: membership, audio analytics, video access, and episode tracking.
-- Apply after database/stonefellow_streaming_platform.sql.

ALTER TABLE subscription_plans
  ADD COLUMN IF NOT EXISTS allows_video_streaming TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS allows_episode_tracking TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS allows_playlists TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS max_playlists INT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS max_playlist_tracks INT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS plan_tier ENUM('free','monthly','annual','founding_fan','admin') NOT NULL DEFAULT 'monthly';

ALTER TABLE streaming_entitlements
  MODIFY entitlement_type ENUM(
    'full_music',
    'offline_downloads',
    'premium_music',
    'live_sessions',
    'video_streaming',
    'episode_tracking',
    'premium_video',
    'founding_fan',
    'subscriber_merch'
  ) NOT NULL;

INSERT INTO subscription_plans (
  id,
  name,
  slug,
  price_cents,
  billing_interval,
  description,
  allows_full_music,
  allows_offline_downloads,
  allows_video_streaming,
  allows_episode_tracking,
  allows_playlists,
  max_playlists,
  max_playlist_tracks,
  plan_tier,
  is_featured,
  status
)
VALUES
(1, 'Monthly Access', 'monthly-access', 799, 'month', 'Monthly member access to released episodes, full soundtrack streaming, playlists, and behind-the-scenes clips.', 1, 0, 1, 1, 1, 25, 500, 'monthly', 0, 'active'),
(2, 'Annual Access', 'annual-access', 7999, 'year', 'Annual member access with early episode access, live session archive, full music streaming, and subscriber merch drops.', 1, 0, 1, 1, 1, 100, 1000, 'annual', 1, 'active'),
(3, 'Founding Fan', 'founding-fan', 14999, 'year', 'Founding fan access with annual benefits, premium drops, VIP assets, supporter credit, and founding-fan content.', 1, 1, 1, 1, 1, NULL, NULL, 'founding_fan', 0, 'active')
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  price_cents = VALUES(price_cents),
  billing_interval = VALUES(billing_interval),
  description = VALUES(description),
  allows_full_music = VALUES(allows_full_music),
  allows_offline_downloads = VALUES(allows_offline_downloads),
  allows_video_streaming = VALUES(allows_video_streaming),
  allows_episode_tracking = VALUES(allows_episode_tracking),
  allows_playlists = VALUES(allows_playlists),
  max_playlists = VALUES(max_playlists),
  max_playlist_tracks = VALUES(max_playlist_tracks),
  plan_tier = VALUES(plan_tier),
  is_featured = VALUES(is_featured),
  status = VALUES(status);

CREATE TABLE IF NOT EXISTS content_access_grants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  content_type ENUM('album','song','playlist','episode','video','product','site_feature') NOT NULL,
  content_id INT DEFAULT NULL,
  grant_type ENUM('subscription','purchase','admin_grant','promo','founding_fan') NOT NULL DEFAULT 'subscription',
  access_level ENUM('public','free_account','subscriber','premium','founding_fan','admin') NOT NULL DEFAULT 'subscriber',
  starts_at DATETIME DEFAULT NULL,
  expires_at DATETIME DEFAULT NULL,
  created_by_user_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_content_grants_user_content (user_id, content_type, content_id),
  INDEX idx_content_grants_access (access_level, grant_type),
  INDEX idx_content_grants_expiry (expires_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS videos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  episode_id INT DEFAULT NULL,
  title VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  video_type ENUM('episode','trailer','clip','behind_scenes','live_session','music_video','bonus') NOT NULL DEFAULT 'episode',
  short_description VARCHAR(500) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  runtime_seconds INT DEFAULT NULL,
  poster_asset_id INT DEFAULT NULL,
  access_level ENUM('public','free_account','subscriber','premium','founding_fan') NOT NULL DEFAULT 'subscriber',
  release_at DATETIME DEFAULT NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_videos_episode_type (episode_id, video_type),
  INDEX idx_videos_status_access (status, access_level),
  INDEX idx_videos_release (release_at),
  FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE SET NULL,
  FOREIGN KEY (poster_asset_id) REFERENCES media_assets(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS video_files (
  id INT AUTO_INCREMENT PRIMARY KEY,
  video_id INT NOT NULL,
  file_type ENUM('preview','stream','download','trailer','mobile','hd','subtitle') NOT NULL DEFAULT 'stream',
  file_path VARCHAR(255) NOT NULL,
  mime_type VARCHAR(120) DEFAULT 'video/mp4',
  duration_seconds INT DEFAULT NULL,
  resolution_label VARCHAR(40) DEFAULT NULL,
  bitrate_kbps INT DEFAULT NULL,
  language_code VARCHAR(20) DEFAULT NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_video_file_variant (video_id, file_type, file_path),
  INDEX idx_video_files_type (video_id, file_type),
  FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audio_play_events (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  song_id INT NOT NULL,
  song_file_id INT DEFAULT NULL,
  session_key VARCHAR(128) DEFAULT NULL,
  event_type ENUM('play','pause','seek','progress','complete','skip','replay','error') NOT NULL DEFAULT 'play',
  position_seconds INT NOT NULL DEFAULT 0,
  seconds_played INT NOT NULL DEFAULT 0,
  percent_complete DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  source_page VARCHAR(120) DEFAULT NULL,
  ip_hash VARCHAR(128) DEFAULT NULL,
  user_agent_hash VARCHAR(128) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audio_events_user_time (user_id, created_at),
  INDEX idx_audio_events_song_time (song_id, created_at),
  INDEX idx_audio_events_session (session_key),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE,
  FOREIGN KEY (song_file_id) REFERENCES song_files(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_song_progress (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  song_id INT NOT NULL,
  last_position_seconds INT NOT NULL DEFAULT 0,
  total_seconds_played INT NOT NULL DEFAULT 0,
  play_count INT NOT NULL DEFAULT 0,
  completed_count INT NOT NULL DEFAULT 0,
  last_event_type ENUM('play','pause','seek','progress','complete','skip','replay','error') DEFAULT NULL,
  last_played_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_song_progress (user_id, song_id),
  INDEX idx_song_progress_last_played (last_played_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS video_watch_events (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  video_id INT NOT NULL,
  episode_id INT DEFAULT NULL,
  video_file_id INT DEFAULT NULL,
  session_key VARCHAR(128) DEFAULT NULL,
  event_type ENUM('play','pause','seek','progress','complete','rewatch','error') NOT NULL DEFAULT 'play',
  position_seconds INT NOT NULL DEFAULT 0,
  seconds_watched INT NOT NULL DEFAULT 0,
  percent_complete DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  source_page VARCHAR(120) DEFAULT NULL,
  ip_hash VARCHAR(128) DEFAULT NULL,
  user_agent_hash VARCHAR(128) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_video_events_user_time (user_id, created_at),
  INDEX idx_video_events_video_time (video_id, created_at),
  INDEX idx_video_events_episode_time (episode_id, created_at),
  INDEX idx_video_events_session (session_key),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
  FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE SET NULL,
  FOREIGN KEY (video_file_id) REFERENCES video_files(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_video_progress (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  video_id INT NOT NULL,
  last_position_seconds INT NOT NULL DEFAULT 0,
  total_seconds_watched INT NOT NULL DEFAULT 0,
  watch_count INT NOT NULL DEFAULT 0,
  completed_count INT NOT NULL DEFAULT 0,
  last_event_type ENUM('play','pause','seek','progress','complete','rewatch','error') DEFAULT NULL,
  last_watched_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_video_progress (user_id, video_id),
  INDEX idx_video_progress_last_watched (last_watched_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_episode_progress (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  episode_id INT NOT NULL,
  primary_video_id INT DEFAULT NULL,
  last_position_seconds INT NOT NULL DEFAULT 0,
  total_seconds_watched INT NOT NULL DEFAULT 0,
  completed TINYINT(1) NOT NULL DEFAULT 0,
  completed_at DATETIME DEFAULT NULL,
  last_watched_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_episode_progress (user_id, episode_id),
  INDEX idx_episode_progress_last_watched (last_watched_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE,
  FOREIGN KEY (primary_video_id) REFERENCES videos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_audit_log (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  admin_user_id INT DEFAULT NULL,
  action VARCHAR(120) NOT NULL,
  entity_type VARCHAR(80) DEFAULT NULL,
  entity_id INT DEFAULT NULL,
  before_json LONGTEXT DEFAULT NULL,
  after_json LONGTEXT DEFAULT NULL,
  ip_address VARCHAR(64) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_admin_audit_user_time (admin_user_id, created_at),
  INDEX idx_admin_audit_entity (entity_type, entity_id),
  FOREIGN KEY (admin_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO videos (id, episode_id, title, slug, video_type, short_description, runtime_seconds, access_level, is_featured, status)
VALUES
(1, 1, 'First to Fall', 'first-to-fall-full-episode', 'episode', 'Stonefellow reunites for a coastal comeback show, but the past refuses to stay quiet.', 2880, 'subscriber', 1, 'published'),
(2, 1, 'First to Fall Trailer', 'first-to-fall-trailer', 'trailer', 'Official trailer for the pilot episode.', 90, 'public', 1, 'published'),
(3, 2, 'Blackout', 'blackout-full-episode', 'episode', 'Old wounds resurface as the road begins to close in.', 2760, 'subscriber', 0, 'draft'),
(4, NULL, 'Stonefellow Live Session: Long Road Home', 'stonefellow-live-session-long-road-home', 'live_session', 'Subscriber live session performance archive.', 360, 'premium', 0, 'draft')
ON DUPLICATE KEY UPDATE
  episode_id = VALUES(episode_id),
  title = VALUES(title),
  video_type = VALUES(video_type),
  short_description = VALUES(short_description),
  runtime_seconds = VALUES(runtime_seconds),
  access_level = VALUES(access_level),
  is_featured = VALUES(is_featured),
  status = VALUES(status);

INSERT INTO video_files (video_id, file_type, file_path, mime_type, duration_seconds, resolution_label, is_primary)
VALUES
(1, 'stream', 'video/episodes/first-to-fall.mp4', 'video/mp4', 2880, '1080p', 1),
(1, 'preview', 'video/previews/first-to-fall-preview.mp4', 'video/mp4', 120, '720p', 0),
(2, 'trailer', 'video/trailers/first-to-fall-trailer.mp4', 'video/mp4', 90, '1080p', 1),
(3, 'stream', 'video/episodes/blackout.mp4', 'video/mp4', 2760, '1080p', 1),
(4, 'stream', 'video/live/long-road-home-session.mp4', 'video/mp4', 360, '1080p', 1)
ON DUPLICATE KEY UPDATE
  mime_type = VALUES(mime_type),
  duration_seconds = VALUES(duration_seconds),
  resolution_label = VALUES(resolution_label),
  is_primary = VALUES(is_primary);
