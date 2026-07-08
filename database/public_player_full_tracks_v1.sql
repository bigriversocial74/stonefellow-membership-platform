-- Stonefellow Public Full-Track Player v1
-- Use after a database backup and after stonefellow_streaming_platform.sql.
-- This keeps the demo catalog as editable real sample data and makes the public player use full-track rows.

UPDATE song_files
SET file_path = SUBSTRING(file_path, 8)
WHERE file_path LIKE 'assets/audio/%';

UPDATE songs
SET is_featured = 1,
    access_level = 'free_preview',
    status = 'published'
WHERE slug IN (
  'born-to-burn',
  'blackout-in-the-rearview',
  'tearing-down-the-walls',
  'heart-of-a-loaded-gun',
  'saint-or-sinner',
  'riptide',
  'long-road-home',
  'burn-it-down',
  'nothing-left',
  'the-road-is-calling'
);

INSERT INTO song_files (song_id, file_type, file_path, duration_seconds, preview_seconds, mime_type, is_primary)
SELECT s.id, 'full', 'audio/full/born-to-burn.wav', s.duration_seconds, NULL, 'audio/wav', 1 FROM songs s WHERE s.slug = 'born-to-burn' AND NOT EXISTS (SELECT 1 FROM song_files f WHERE f.song_id = s.id AND f.file_type = 'full');
INSERT INTO song_files (song_id, file_type, file_path, duration_seconds, preview_seconds, mime_type, is_primary)
SELECT s.id, 'full', 'audio/full/blackout-in-the-rearview.wav', s.duration_seconds, NULL, 'audio/wav', 1 FROM songs s WHERE s.slug = 'blackout-in-the-rearview' AND NOT EXISTS (SELECT 1 FROM song_files f WHERE f.song_id = s.id AND f.file_type = 'full');
INSERT INTO song_files (song_id, file_type, file_path, duration_seconds, preview_seconds, mime_type, is_primary)
SELECT s.id, 'full', 'audio/full/tearing-down-the-walls.wav', s.duration_seconds, NULL, 'audio/wav', 1 FROM songs s WHERE s.slug = 'tearing-down-the-walls' AND NOT EXISTS (SELECT 1 FROM song_files f WHERE f.song_id = s.id AND f.file_type = 'full');
INSERT INTO song_files (song_id, file_type, file_path, duration_seconds, preview_seconds, mime_type, is_primary)
SELECT s.id, 'full', 'audio/full/heart-of-a-loaded-gun.wav', s.duration_seconds, NULL, 'audio/wav', 1 FROM songs s WHERE s.slug = 'heart-of-a-loaded-gun' AND NOT EXISTS (SELECT 1 FROM song_files f WHERE f.song_id = s.id AND f.file_type = 'full');
INSERT INTO song_files (song_id, file_type, file_path, duration_seconds, preview_seconds, mime_type, is_primary)
SELECT s.id, 'full', 'audio/full/saint-or-sinner.wav', s.duration_seconds, NULL, 'audio/wav', 1 FROM songs s WHERE s.slug = 'saint-or-sinner' AND NOT EXISTS (SELECT 1 FROM song_files f WHERE f.song_id = s.id AND f.file_type = 'full');
INSERT INTO song_files (song_id, file_type, file_path, duration_seconds, preview_seconds, mime_type, is_primary)
SELECT s.id, 'full', 'audio/full/riptide.wav', s.duration_seconds, NULL, 'audio/wav', 1 FROM songs s WHERE s.slug = 'riptide' AND NOT EXISTS (SELECT 1 FROM song_files f WHERE f.song_id = s.id AND f.file_type = 'full');
INSERT INTO song_files (song_id, file_type, file_path, duration_seconds, preview_seconds, mime_type, is_primary)
SELECT s.id, 'full', 'audio/full/long-road-home.wav', s.duration_seconds, NULL, 'audio/wav', 1 FROM songs s WHERE s.slug = 'long-road-home' AND NOT EXISTS (SELECT 1 FROM song_files f WHERE f.song_id = s.id AND f.file_type = 'full');
INSERT INTO song_files (song_id, file_type, file_path, duration_seconds, preview_seconds, mime_type, is_primary)
SELECT s.id, 'full', 'audio/full/burn-it-down.wav', s.duration_seconds, NULL, 'audio/wav', 1 FROM songs s WHERE s.slug = 'burn-it-down' AND NOT EXISTS (SELECT 1 FROM song_files f WHERE f.song_id = s.id AND f.file_type = 'full');
INSERT INTO song_files (song_id, file_type, file_path, duration_seconds, preview_seconds, mime_type, is_primary)
SELECT s.id, 'full', 'audio/full/nothing-left.wav', s.duration_seconds, NULL, 'audio/wav', 1 FROM songs s WHERE s.slug = 'nothing-left' AND NOT EXISTS (SELECT 1 FROM song_files f WHERE f.song_id = s.id AND f.file_type = 'full');
INSERT INTO song_files (song_id, file_type, file_path, duration_seconds, preview_seconds, mime_type, is_primary)
SELECT s.id, 'full', 'audio/full/the-road-is-calling.wav', s.duration_seconds, NULL, 'audio/wav', 1 FROM songs s WHERE s.slug = 'the-road-is-calling' AND NOT EXISTS (SELECT 1 FROM song_files f WHERE f.song_id = s.id AND f.file_type = 'full');
