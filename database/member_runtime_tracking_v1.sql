-- Stonefellow Member Runtime Tracking v1
-- Adds the persistence tables used by member library saves, watch progress,
-- audio progress, and private playlist actions.

CREATE TABLE IF NOT EXISTS member_library_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  content_type VARCHAR(40) NOT NULL,
  content_id BIGINT UNSIGNED NOT NULL,
  slug VARCHAR(190) DEFAULT '',
  title VARCHAR(255) NOT NULL,
  image_path VARCHAR(255) DEFAULT '',
  content_url VARCHAR(255) DEFAULT '',
  library_status VARCHAR(40) NOT NULL DEFAULT 'saved',
  progress_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
  position_seconds INT UNSIGNED NOT NULL DEFAULT 0,
  metadata_json TEXT NULL,
  last_interaction_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_member_library_item (user_id, content_type, content_id, library_status),
  KEY idx_member_library_user_status (user_id, library_status),
  KEY idx_member_library_interaction (last_interaction_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS video_watch_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NULL,
  video_id BIGINT UNSIGNED NOT NULL,
  episode_id BIGINT UNSIGNED NULL,
  session_key VARCHAR(64) NOT NULL,
  event_type VARCHAR(30) NOT NULL,
  position_seconds INT UNSIGNED NOT NULL DEFAULT 0,
  seconds_watched INT UNSIGNED NOT NULL DEFAULT 0,
  percent_complete DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  source_page VARCHAR(120) DEFAULT '',
  ip_hash CHAR(64) NULL,
  user_agent_hash CHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_video_watch_user (user_id, video_id),
  KEY idx_video_watch_session (session_key),
  KEY idx_video_watch_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_video_progress (
  user_id BIGINT UNSIGNED NOT NULL,
  video_id BIGINT UNSIGNED NOT NULL,
  last_position_seconds INT UNSIGNED NOT NULL DEFAULT 0,
  total_seconds_watched INT UNSIGNED NOT NULL DEFAULT 0,
  watch_count INT UNSIGNED NOT NULL DEFAULT 0,
  completed_count INT UNSIGNED NOT NULL DEFAULT 0,
  last_event_type VARCHAR(30) NOT NULL DEFAULT 'play',
  last_watched_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, video_id),
  KEY idx_user_video_last (last_watched_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_episode_progress (
  user_id BIGINT UNSIGNED NOT NULL,
  episode_id BIGINT UNSIGNED NOT NULL,
  primary_video_id BIGINT UNSIGNED NULL,
  last_position_seconds INT UNSIGNED NOT NULL DEFAULT 0,
  total_seconds_watched INT UNSIGNED NOT NULL DEFAULT 0,
  completed TINYINT(1) NOT NULL DEFAULT 0,
  completed_at DATETIME NULL,
  last_watched_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, episode_id),
  KEY idx_user_episode_last (last_watched_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audio_play_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NULL,
  song_id BIGINT UNSIGNED NOT NULL,
  session_key VARCHAR(64) NOT NULL,
  event_type VARCHAR(30) NOT NULL,
  position_seconds INT UNSIGNED NOT NULL DEFAULT 0,
  seconds_played INT UNSIGNED NOT NULL DEFAULT 0,
  percent_complete DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  source_page VARCHAR(120) DEFAULT '',
  ip_hash CHAR(64) NULL,
  user_agent_hash CHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audio_play_user (user_id, song_id),
  KEY idx_audio_play_session (session_key),
  KEY idx_audio_play_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_song_progress (
  user_id BIGINT UNSIGNED NOT NULL,
  song_id BIGINT UNSIGNED NOT NULL,
  last_position_seconds INT UNSIGNED NOT NULL DEFAULT 0,
  total_seconds_played INT UNSIGNED NOT NULL DEFAULT 0,
  play_count INT UNSIGNED NOT NULL DEFAULT 0,
  completed_count INT UNSIGNED NOT NULL DEFAULT 0,
  last_event_type VARCHAR(30) NOT NULL DEFAULT 'play',
  last_played_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, song_id),
  KEY idx_user_song_last (last_played_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS playlists (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(190) NOT NULL,
  slug VARCHAR(220) NOT NULL,
  description TEXT NULL,
  visibility VARCHAR(40) NOT NULL DEFAULT 'private',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_playlist_slug (slug),
  KEY idx_playlists_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS playlist_songs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  playlist_id BIGINT UNSIGNED NOT NULL,
  song_id BIGINT UNSIGNED NOT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_playlist_song (playlist_id, song_id),
  KEY idx_playlist_songs_playlist (playlist_id),
  KEY idx_playlist_songs_song (song_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
