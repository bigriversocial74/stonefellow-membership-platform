-- Stonefellow migration 003: media upload/storage metadata.
-- Apply after database/stonefellow_streaming_platform.sql.
-- Installer-safe version: avoids DELIMITER/stored procedures so it can run through PDO.

ALTER TABLE media_assets
  ADD COLUMN IF NOT EXISTS `original_filename` VARCHAR(255) DEFAULT NULL AFTER `usage_key`,
  ADD COLUMN IF NOT EXISTS `mime_type` VARCHAR(120) DEFAULT NULL AFTER `original_filename`,
  ADD COLUMN IF NOT EXISTS `file_size_bytes` BIGINT DEFAULT NULL AFTER `mime_type`,
  ADD COLUMN IF NOT EXISTS `checksum_sha256` CHAR(64) DEFAULT NULL AFTER `file_size_bytes`,
  ADD COLUMN IF NOT EXISTS `storage_disk` VARCHAR(80) NOT NULL DEFAULT 'local_assets' AFTER `checksum_sha256`,
  ADD COLUMN IF NOT EXISTS `uploaded_by_user_id` INT DEFAULT NULL AFTER `storage_disk`,
  ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

CREATE INDEX idx_media_assets_type_usage ON media_assets (file_type, usage_key);
CREATE INDEX idx_media_assets_uploaded_by ON media_assets (uploaded_by_user_id);
