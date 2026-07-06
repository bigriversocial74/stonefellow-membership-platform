-- Stonefellow migration 002: align runtime seed data with the PHP membership/watch pages.
-- Apply after database/stonefellow_streaming_platform.sql and 001_membership_video_tracking.sql.

INSERT INTO episodes (id, season_number, episode_number, title, slug, short_description, runtime_minutes, status)
VALUES
(1, 1, 1, 'First to Fall', 'first-to-fall', 'Stonefellow reunites for a comeback show, but the first night back exposes every secret they tried to bury.', 48, 'published'),
(2, 1, 2, 'Riptide Hearts', 'riptide-hearts', 'A new song unlocks an old secret, and the band must decide whether fame is worth reopening the wound.', 44, 'published'),
(3, 1, 3, 'The Long Road Home', 'the-long-road-home', 'A dangerous offer puts the band on the road, forcing every member to choose between loyalty and escape.', 46, 'published')
ON DUPLICATE KEY UPDATE
  title = VALUES(title),
  slug = VALUES(slug),
  short_description = VALUES(short_description),
  runtime_minutes = VALUES(runtime_minutes),
  status = VALUES(status);

INSERT INTO videos (id, episode_id, title, slug, video_type, short_description, runtime_seconds, access_level, is_featured, status)
VALUES
(1, 1, 'First to Fall', 'first-to-fall-full-episode', 'episode', 'Stonefellow reunites for a comeback show, but the first night back exposes every secret they tried to bury.', 2880, 'subscriber', 1, 'published'),
(2, 1, 'First to Fall Trailer', 'first-to-fall-trailer', 'trailer', 'Official public trailer for the Stonefellow pilot.', 90, 'public', 1, 'published'),
(3, 2, 'Riptide Hearts', 'riptide-hearts-full-episode', 'episode', 'A new song unlocks an old secret, and the band must decide whether fame is worth reopening the wound.', 2640, 'subscriber', 0, 'draft'),
(4, 3, 'The Long Road Home', 'the-long-road-home-full-episode', 'episode', 'A dangerous offer puts the band on the road, forcing every member to choose between loyalty and escape.', 2760, 'premium', 0, 'draft'),
(5, NULL, 'Long Road Home Live Session', 'long-road-home-live-session', 'live_session', 'Subscriber live session archive from the Stonefellow soundstage.', 360, 'premium', 0, 'draft')
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
(3, 'stream', 'video/episodes/riptide-hearts.mp4', 'video/mp4', 2640, '1080p', 1),
(3, 'preview', 'video/previews/riptide-hearts-preview.mp4', 'video/mp4', 120, '720p', 0),
(4, 'stream', 'video/episodes/the-long-road-home.mp4', 'video/mp4', 2760, '1080p', 1),
(4, 'preview', 'video/previews/the-long-road-home-preview.mp4', 'video/mp4', 120, '720p', 0),
(5, 'stream', 'video/live/long-road-home-session.mp4', 'video/mp4', 360, '1080p', 1),
(5, 'preview', 'video/previews/long-road-home-session-preview.mp4', 'video/mp4', 60, '720p', 0)
ON DUPLICATE KEY UPDATE
  file_path = VALUES(file_path),
  mime_type = VALUES(mime_type),
  duration_seconds = VALUES(duration_seconds),
  resolution_label = VALUES(resolution_label),
  is_primary = VALUES(is_primary);
