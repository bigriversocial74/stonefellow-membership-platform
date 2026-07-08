<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/data.php';

function sf_music_table_exists(string $table): bool {
  $pdo = sf_db();
  if (!$pdo) return false;
  try {
    $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
    $stmt->execute([$table]);
    return (bool)$stmt->fetchColumn();
  } catch (Throwable $e) {
    error_log('Stonefellow music table check failed for ' . $table . ': ' . $e->getMessage());
    return false;
  }
}

function sf_music_clean_asset_path(?string $path): string {
  $path = trim((string)$path);
  if ($path === '') return '';
  if (preg_match('~^(https?:)?//|^data:~i', $path)) return $path;
  $path = ltrim($path, '/');
  if (strpos($path, 'assets/') === 0) $path = substr($path, 7);
  return $path;
}

function sf_music_duration_label(int $seconds): string {
  $seconds = max(0, $seconds);
  $minutes = (int)floor($seconds / 60);
  $remaining = str_pad((string)($seconds % 60), 2, '0', STR_PAD_LEFT);
  return $minutes . ':' . $remaining;
}

function sf_music_static_song_by_slug_map(): array {
  global $catalogSongs;
  $map = [];
  foreach (($catalogSongs ?? []) as $song) {
    $slug = (string)($song['slug'] ?? '');
    if ($slug !== '') $map[$slug] = $song;
  }
  return $map;
}

function sf_music_catalog_rows(bool $publicOnly = true): array {
  $pdo = sf_db();
  if (!$pdo || !sf_music_table_exists('songs') || !sf_music_table_exists('song_files')) return [];
  $where = ["s.status = 'published'"];
  if ($publicOnly) $where[] = 's.is_featured = 1';
  $sql = "SELECT s.*, a.title AS album_title, a.slug AS album_slug, ma.file_path AS cover_path,
    sf_full.file_path AS full_file_path, sf_full.mime_type AS full_mime_type, sf_full.duration_seconds AS full_duration_seconds,
    sf_preview.file_path AS preview_file_path, sf_preview.mime_type AS preview_mime_type, sf_preview.duration_seconds AS preview_duration_seconds, sf_preview.preview_seconds AS preview_seconds
    FROM songs s
    LEFT JOIN albums a ON a.id = s.album_id
    LEFT JOIN media_assets ma ON ma.id = s.cover_asset_id
    LEFT JOIN song_files sf_full ON sf_full.id = (SELECT id FROM song_files WHERE song_id = s.id AND file_type = 'full' ORDER BY is_primary DESC, id ASC LIMIT 1)
    LEFT JOIN song_files sf_preview ON sf_preview.id = (SELECT id FROM song_files WHERE song_id = s.id AND file_type = 'preview' ORDER BY is_primary DESC, id ASC LIMIT 1)
    WHERE " . implode(' AND ', $where) . "
    ORDER BY COALESCE(s.track_number, 999999), s.id ASC";
  try {
    $rows = $pdo->query($sql)->fetchAll() ?: [];
    if (!$rows && $publicOnly) return sf_music_catalog_rows(false);
    return $rows;
  } catch (Throwable $e) {
    error_log('Stonefellow public music catalog query failed: ' . $e->getMessage());
    return [];
  }
}

function sf_music_catalog_songs(bool $publicOnly = true): array {
  global $catalogSongs;
  $rows = sf_music_catalog_rows($publicOnly);
  if (!$rows) return $catalogSongs ?? [];
  $staticBySlug = sf_music_static_song_by_slug_map();
  $songs = [];
  foreach ($rows as $row) {
    $slug = (string)($row['slug'] ?? '');
    $fallback = $staticBySlug[$slug] ?? [];
    $durationSeconds = (int)($row['duration_seconds'] ?? 0);
    if ($durationSeconds <= 0) $durationSeconds = (int)($row['full_duration_seconds'] ?? 0);
    if ($durationSeconds <= 0) $durationSeconds = (int)($fallback['duration_seconds'] ?? 0);
    if ($durationSeconds <= 0 && !empty($fallback['duration'])) {
      $parts = array_map('intval', explode(':', (string)$fallback['duration']));
      $durationSeconds = count($parts) === 2 ? ($parts[0] * 60 + $parts[1]) : 0;
    }
    $fullPath = sf_music_clean_asset_path($row['full_file_path'] ?? '');
    if ($fullPath === '') $fullPath = sf_music_clean_asset_path($fallback['full_src'] ?? '');
    $previewPath = sf_music_clean_asset_path($row['preview_file_path'] ?? '');
    if ($previewPath === '') $previewPath = sf_music_clean_asset_path($fallback['preview_src'] ?? '');
    if ($fullPath === '') $fullPath = $previewPath;
    if ($previewPath === '') $previewPath = $fullPath;
    $coverPath = sf_music_clean_asset_path($row['cover_path'] ?? '');
    if ($coverPath === '') $coverPath = sf_music_clean_asset_path($fallback['cover'] ?? 'images/music/soundtrack-cover.png');
    $trackNumber = (int)($row['track_number'] ?? 0);
    $songs[] = [
      'id' => (int)($row['id'] ?? 0),
      'track' => $trackNumber > 0 ? str_pad((string)$trackNumber, 2, '0', STR_PAD_LEFT) : (string)($fallback['track'] ?? ''),
      'track_number' => $trackNumber ?: (int)($fallback['track_number'] ?? 0),
      'title' => (string)($row['title'] ?? $fallback['title'] ?? 'Stonefellow Track'),
      'slug' => $slug,
      'artist' => (string)($row['artist'] ?? $fallback['artist'] ?? 'Stonefellow'),
      'duration' => $durationSeconds > 0 ? sf_music_duration_label($durationSeconds) : (string)($fallback['duration'] ?? '0:00'),
      'duration_seconds' => $durationSeconds,
      'episode' => (string)($fallback['episode'] ?? ($row['album_title'] ?? 'Stonefellow Catalog')),
      'episode_short' => (string)($fallback['episode_short'] ?? ($row['album_title'] ?? 'Catalog')),
      'album_title' => (string)($row['album_title'] ?? $fallback['album_title'] ?? 'The Road Is Calling'),
      'album_slug' => (string)($row['album_slug'] ?? $fallback['album_slug'] ?? 'the-road-is-calling'),
      'cover' => $coverPath,
      'preview_src' => $previewPath,
      'full_src' => $fullPath,
      'public_src' => $fullPath,
      'access' => (string)($row['access_level'] ?? $fallback['access'] ?? 'free_preview'),
      'access_level' => (string)($row['access_level'] ?? $fallback['access_level'] ?? 'free_preview'),
      'preview_seconds' => (int)($row['preview_seconds'] ?? $fallback['preview_seconds'] ?? 30),
      'is_featured' => !empty($row['is_featured']),
      'source_mode' => $fullPath !== '' ? 'full' : 'preview',
    ];
  }
  return $songs;
}

function sf_music_public_catalog_songs(): array { return sf_music_catalog_songs(true); }

if (!function_exists('sf_song_by_slug')) {
  function sf_song_by_slug(array $songs, string $slug): ?array {
    foreach ($songs as $song) if ((string)($song['slug'] ?? '') === $slug) return $song;
    return null;
  }
}
?>
