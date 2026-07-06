-- Stonefellow migration 003: media upload/storage metadata.
-- Apply after database/stonefellow_streaming_platform.sql.
-- This migration keeps the original media_assets table compatible while adding
-- optional metadata used by Admin Media Upload + Storage v1.

DROP PROCEDURE IF EXISTS sf_add_media_column;
DROP PROCEDURE IF EXISTS sf_add_media_index;
DELIMITER //
CREATE PROCEDURE sf_add_media_column(IN column_name VARCHAR(64), IN column_definition TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'media_assets'
      AND COLUMN_NAME = column_name
  ) THEN
    SET @ddl = CONCAT('ALTER TABLE media_assets ADD COLUMN ', column_definition);
    PREPARE stmt FROM @ddl;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END //

CREATE PROCEDURE sf_add_media_index(IN index_name VARCHAR(64), IN index_definition TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'media_assets'
      AND INDEX_NAME = index_name
  ) THEN
    SET @ddl = CONCAT('CREATE INDEX ', index_definition);
    PREPARE stmt FROM @ddl;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END //
DELIMITER ;

CALL sf_add_media_column('original_filename', '`original_filename` VARCHAR(255) DEFAULT NULL AFTER `usage_key`');
CALL sf_add_media_column('mime_type', '`mime_type` VARCHAR(120) DEFAULT NULL AFTER `original_filename`');
CALL sf_add_media_column('file_size_bytes', '`file_size_bytes` BIGINT DEFAULT NULL AFTER `mime_type`');
CALL sf_add_media_column('checksum_sha256', '`checksum_sha256` CHAR(64) DEFAULT NULL AFTER `file_size_bytes`');
CALL sf_add_media_column('storage_disk', '`storage_disk` VARCHAR(80) NOT NULL DEFAULT ''local_assets'' AFTER `checksum_sha256`');
CALL sf_add_media_column('uploaded_by_user_id', '`uploaded_by_user_id` INT DEFAULT NULL AFTER `storage_disk`');
CALL sf_add_media_column('updated_at', '`updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`');

CALL sf_add_media_index('idx_media_assets_type_usage', 'idx_media_assets_type_usage ON media_assets (file_type, usage_key)');
CALL sf_add_media_index('idx_media_assets_uploaded_by', 'idx_media_assets_uploaded_by ON media_assets (uploaded_by_user_id)');

DROP PROCEDURE IF EXISTS sf_add_media_column;
DROP PROCEDURE IF EXISTS sf_add_media_index;
