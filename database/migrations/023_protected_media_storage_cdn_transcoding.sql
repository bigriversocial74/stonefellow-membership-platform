-- Stonefellow migration 023: protected media storage, resumable ingestion,
-- processing/transcoding jobs, signed HLS delivery, and operational evidence.
-- Compatible with MySQL 5.7+/MariaDB 10.2+; no ADD COLUMN IF NOT EXISTS syntax.

CREATE TABLE IF NOT EXISTS media_storage_providers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  provider_key VARCHAR(80) NOT NULL,
  driver ENUM('local','s3') NOT NULL DEFAULT 'local',
  mode ENUM('test','live') NOT NULL DEFAULT 'test',
  bucket_name VARCHAR(190) DEFAULT NULL,
  region_name VARCHAR(80) DEFAULT NULL,
  endpoint_url VARCHAR(500) DEFAULT NULL,
  public_base_url VARCHAR(500) DEFAULT NULL,
  status ENUM('active','inactive','degraded') NOT NULL DEFAULT 'active',
  config_json JSON DEFAULT NULL,
  last_health_status ENUM('unknown','healthy','degraded','failed') NOT NULL DEFAULT 'unknown',
  last_health_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_media_storage_provider (provider_key, mode),
  INDEX idx_media_storage_provider_status (driver, mode, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media_objects (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  object_key CHAR(48) NOT NULL,
  provider_id INT NOT NULL,
  entity_type ENUM('song','video','episode','album','asset','series') NOT NULL,
  entity_id INT NOT NULL,
  role ENUM('original','preview','stream','download','poster','thumbnail','waveform','manifest','segment','caption') NOT NULL,
  parent_object_id BIGINT DEFAULT NULL,
  storage_key VARCHAR(700) NOT NULL,
  original_filename VARCHAR(255) DEFAULT NULL,
  extension VARCHAR(20) DEFAULT NULL,
  mime_type VARCHAR(120) NOT NULL DEFAULT 'application/octet-stream',
  size_bytes BIGINT NOT NULL DEFAULT 0,
  checksum_sha256 CHAR(64) DEFAULT NULL,
  duration_seconds DECIMAL(12,3) DEFAULT NULL,
  width_pixels INT DEFAULT NULL,
  height_pixels INT DEFAULT NULL,
  bitrate_kbps INT DEFAULT NULL,
  codec_name VARCHAR(80) DEFAULT NULL,
  visibility ENUM('private','member','public') NOT NULL DEFAULT 'private',
  status ENUM('quarantined','ingesting','processing','ready','failed','deleted') NOT NULL DEFAULT 'quarantined',
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  metadata_json JSON DEFAULT NULL,
  ready_at DATETIME DEFAULT NULL,
  deleted_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_media_object_key (object_key),
  UNIQUE KEY unique_media_storage_key (provider_id, storage_key),
  INDEX idx_media_objects_entity (entity_type, entity_id, role, status, is_primary),
  INDEX idx_media_objects_parent (parent_object_id, role, status),
  INDEX idx_media_objects_checksum (checksum_sha256, size_bytes),
  FOREIGN KEY (provider_id) REFERENCES media_storage_providers(id),
  FOREIGN KEY (parent_object_id) REFERENCES media_objects(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media_upload_sessions (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  session_token CHAR(64) NOT NULL,
  created_by_user_id INT DEFAULT NULL,
  provider_id INT NOT NULL,
  entity_type ENUM('song','video','episode','album','asset','series') NOT NULL,
  entity_id INT NOT NULL,
  target_role ENUM('original','poster','thumbnail','caption') NOT NULL DEFAULT 'original',
  original_filename VARCHAR(255) NOT NULL,
  extension VARCHAR(20) NOT NULL,
  declared_mime_type VARCHAR(120) DEFAULT NULL,
  expected_size_bytes BIGINT NOT NULL,
  received_size_bytes BIGINT NOT NULL DEFAULT 0,
  expected_checksum_sha256 CHAR(64) DEFAULT NULL,
  chunk_size_bytes INT NOT NULL,
  expected_chunks INT NOT NULL,
  received_chunks INT NOT NULL DEFAULT 0,
  staging_path VARCHAR(700) NOT NULL,
  status ENUM('created','uploading','assembling','completed','expired','failed','canceled') NOT NULL DEFAULT 'created',
  error_message VARCHAR(1000) DEFAULT NULL,
  expires_at DATETIME NOT NULL,
  completed_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_media_upload_session (session_token),
  INDEX idx_media_upload_session_status (status, expires_at),
  INDEX idx_media_upload_session_user (created_by_user_id, created_at),
  FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (provider_id) REFERENCES media_storage_providers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media_upload_chunks (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  upload_session_id BIGINT NOT NULL,
  chunk_number INT NOT NULL,
  size_bytes INT NOT NULL,
  checksum_sha256 CHAR(64) NOT NULL,
  staging_path VARCHAR(700) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_media_upload_chunk (upload_session_id, chunk_number),
  INDEX idx_media_upload_chunks_session (upload_session_id, created_at),
  FOREIGN KEY (upload_session_id) REFERENCES media_upload_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media_processing_jobs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  job_key CHAR(48) NOT NULL,
  object_id BIGINT NOT NULL,
  job_type ENUM('probe','audio_preview','audio_stream','audio_waveform','video_hls','video_preview','video_poster','integrity_check','storage_copy') NOT NULL,
  status ENUM('queued','running','retry','completed','failed','canceled') NOT NULL DEFAULT 'queued',
  priority INT NOT NULL DEFAULT 100,
  progress_percent INT NOT NULL DEFAULT 0,
  attempts INT NOT NULL DEFAULT 0,
  max_attempts INT NOT NULL DEFAULT 3,
  run_after DATETIME NOT NULL,
  lock_token CHAR(64) DEFAULT NULL,
  locked_until DATETIME DEFAULT NULL,
  command_summary VARCHAR(1000) DEFAULT NULL,
  output_json JSON DEFAULT NULL,
  error_message VARCHAR(2000) DEFAULT NULL,
  started_at DATETIME DEFAULT NULL,
  completed_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_media_processing_job (job_key),
  INDEX idx_media_processing_queue (status, run_after, priority, id),
  INDEX idx_media_processing_object (object_id, job_type, status),
  FOREIGN KEY (object_id) REFERENCES media_objects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media_delivery_sessions (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  session_token CHAR(64) NOT NULL,
  user_id INT DEFAULT NULL,
  object_id BIGINT NOT NULL,
  manifest_object_id BIGINT DEFAULT NULL,
  ip_hash CHAR(64) DEFAULT NULL,
  user_agent_hash CHAR(64) DEFAULT NULL,
  status ENUM('active','expired','revoked','completed') NOT NULL DEFAULT 'active',
  expires_at DATETIME NOT NULL,
  last_accessed_at DATETIME DEFAULT NULL,
  segment_count INT NOT NULL DEFAULT 0,
  bytes_delivered BIGINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_media_delivery_session (session_token),
  INDEX idx_media_delivery_session_user (user_id, status, expires_at),
  INDEX idx_media_delivery_session_object (object_id, status, created_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (object_id) REFERENCES media_objects(id) ON DELETE CASCADE,
  FOREIGN KEY (manifest_object_id) REFERENCES media_objects(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media_delivery_events (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  delivery_session_id BIGINT NOT NULL,
  event_type ENUM('manifest','segment','object','download','denied','error') NOT NULL,
  object_id BIGINT DEFAULT NULL,
  storage_key VARCHAR(700) DEFAULT NULL,
  status_code INT NOT NULL DEFAULT 200,
  bytes_delivered BIGINT NOT NULL DEFAULT 0,
  duration_ms INT DEFAULT NULL,
  metadata_json JSON DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_media_delivery_events_session (delivery_session_id, created_at),
  INDEX idx_media_delivery_events_object (object_id, event_type, created_at),
  FOREIGN KEY (delivery_session_id) REFERENCES media_delivery_sessions(id) ON DELETE CASCADE,
  FOREIGN KEY (object_id) REFERENCES media_objects(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media_storage_health_runs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  run_key CHAR(48) NOT NULL,
  provider_id INT NOT NULL,
  status ENUM('running','healthy','degraded','failed') NOT NULL DEFAULT 'running',
  read_test_status ENUM('pending','passed','failed') NOT NULL DEFAULT 'pending',
  write_test_status ENUM('pending','passed','failed') NOT NULL DEFAULT 'pending',
  delete_test_status ENUM('pending','passed','failed') NOT NULL DEFAULT 'pending',
  latency_ms INT DEFAULT NULL,
  detail_json JSON DEFAULT NULL,
  started_at DATETIME NOT NULL,
  completed_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_media_storage_health_run (run_key),
  INDEX idx_media_storage_health_provider (provider_id, status, started_at),
  FOREIGN KEY (provider_id) REFERENCES media_storage_providers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO media_storage_providers
(provider_key, driver, mode, bucket_name, region_name, endpoint_url, public_base_url, status, config_json)
SELECT 'local_private', 'local', 'test', NULL, NULL, NULL, NULL, 'active', JSON_OBJECT('root','storage/private_media_v2')
WHERE NOT EXISTS (
  SELECT 1 FROM media_storage_providers WHERE provider_key='local_private' AND mode='test'
);

INSERT INTO media_storage_providers
(provider_key, driver, mode, bucket_name, region_name, endpoint_url, public_base_url, status, config_json)
SELECT 'local_private', 'local', 'live', NULL, NULL, NULL, NULL, 'active', JSON_OBJECT('root','storage/private_media_v2')
WHERE NOT EXISTS (
  SELECT 1 FROM media_storage_providers WHERE provider_key='local_private' AND mode='live'
);

INSERT INTO media_storage_providers
(provider_key, driver, mode, bucket_name, region_name, endpoint_url, public_base_url, status, config_json)
SELECT 's3_compatible', 's3', 'test', NULL, NULL, NULL, NULL, 'active', JSON_OBJECT('credentials','environment_only')
WHERE NOT EXISTS (
  SELECT 1 FROM media_storage_providers WHERE provider_key='s3_compatible' AND mode='test'
);

INSERT INTO media_storage_providers
(provider_key, driver, mode, bucket_name, region_name, endpoint_url, public_base_url, status, config_json)
SELECT 's3_compatible', 's3', 'live', NULL, NULL, NULL, NULL, 'active', JSON_OBJECT('credentials','environment_only')
WHERE NOT EXISTS (
  SELECT 1 FROM media_storage_providers WHERE provider_key='s3_compatible' AND mode='live'
);
