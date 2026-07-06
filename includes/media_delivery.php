<?php
require_once __DIR__ . '/membership.php';
require_once __DIR__ . '/data.php';

function sf_media_secret(): string {
  $env = getenv('SF_MEDIA_SIGNING_KEY') ?: getenv('SF_HASH_SALT');
  if ($env) {
    return (string)$env;
  }
  $lock = dirname(__DIR__) . '/storage/install.lock';
  if (is_file($lock)) {
    return hash('sha256', (string)file_get_contents($lock));
  }
  return 'stonefellow-local-media-signing-key-change-in-production';
}

function sf_media_token_payload(string $contentType, int $contentId, string $fileType, string $disposition, int $expiresAt, ?int $userId = null): array {
  return [
    'm' => $contentType,
    'id' => $contentId,
    'f' => $fileType,
    'd' => $disposition,
    'exp' => $expiresAt,
    'uid' => $userId ?: 0,
  ];
}

function sf_media_sign(array $payload): string {
  ksort($payload);
  return hash_hmac('sha256', http_build_query($payload), sf_media_secret());
}

function sf_media_signed_url(string $contentType, int $contentId, string $fileType = 'stream', string $disposition = 'stream', int $ttlSeconds = 900): string {
  $payload = sf_media_token_payload($contentType, $contentId, $fileType, $disposition, time() + max(60, $ttlSeconds), sf_current_user_id());
  $payload['sig'] = sf_media_sign($payload);
  $endpoint = $disposition === 'download' ? 'download.php' : 'stream.php';
  return sf_url($endpoint . '?' . http_build_query($payload));
}

function sf_media_validate_token(array $request): array {
  $payload = [
    'm' => (string)($request['m'] ?? ''),
    'id' => (int)($request['id'] ?? 0),
    'f' => (string)($request['f'] ?? 'stream'),
    'd' => (string)($request['d'] ?? 'stream'),
    'exp' => (int)($request['exp'] ?? 0),
    'uid' => (int)($request['uid'] ?? 0),
  ];
  $sig = (string)($request['sig'] ?? '');
  if ($payload['m'] === '' || $payload['id'] <= 0 || $payload['exp'] < time() || $sig === '') {
    return ['ok' => false, 'error' => 'invalid_or_expired_token'];
  }
  if (!hash_equals(sf_media_sign($payload), $sig)) {
    return ['ok' => false, 'error' => 'invalid_signature'];
  }
  return ['ok' => true, 'payload' => $payload];
}

function sf_media_static_video(int $id): ?array {
  global $videoCatalog;
  foreach ($videoCatalog as $video) {
    if ((int)($video['id'] ?? 0) === $id) {
      return $video;
    }
  }
  return null;
}

function sf_media_static_song(int $id): ?array {
  global $catalogSongs;
  foreach ($catalogSongs as $song) {
    if ((int)($song['id'] ?? 0) === $id) {
      return $song;
    }
  }
  return null;
}

function sf_media_video_record(int $id): ?array {
  $pdo = sf_db();
  if ($pdo) {
    try {
      $stmt = $pdo->prepare('SELECT * FROM videos WHERE id = ? LIMIT 1');
      $stmt->execute([$id]);
      $row = $stmt->fetch();
      if ($row) {
        return $row;
      }
    } catch (Throwable $e) {
      error_log('Media video lookup failed: ' . $e->getMessage());
    }
  }
  return sf_media_static_video($id);
}

function sf_media_song_record(int $id): ?array {
  $pdo = sf_db();
  if ($pdo) {
    try {
      $stmt = $pdo->prepare('SELECT * FROM songs WHERE id = ? LIMIT 1');
      $stmt->execute([$id]);
      $row = $stmt->fetch();
      if ($row) {
        return $row;
      }
    } catch (Throwable $e) {
      error_log('Media song lookup failed: ' . $e->getMessage());
    }
  }
  return sf_media_static_song($id);
}

function sf_media_video_file(array $video, string $fileType = 'stream'): ?array {
  $id = (int)($video['id'] ?? 0);
  $pdo = sf_db();
  if ($pdo && $id > 0) {
    try {
      $stmt = $pdo->prepare("SELECT * FROM video_files WHERE video_id = ? AND file_type = ? ORDER BY is_primary DESC, id ASC LIMIT 1");
      $stmt->execute([$id, $fileType]);
      $row = $stmt->fetch();
      if (!$row && $fileType === 'stream') {
        $stmt = $pdo->prepare("SELECT * FROM video_files WHERE video_id = ? AND file_type IN ('hd','mobile','trailer') ORDER BY is_primary DESC, id ASC LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
      }
      if ($row) {
        return $row;
      }
    } catch (Throwable $e) {
      error_log('Media video file lookup failed: ' . $e->getMessage());
    }
  }
  $path = $fileType === 'preview' ? ($video['preview_src'] ?? '') : ($video['stream_src'] ?? '');
  return $path ? ['file_path' => $path, 'mime_type' => 'video/mp4', 'file_type' => $fileType] : null;
}

function sf_media_song_file(array $song, string $fileType = 'full'): ?array {
  $id = (int)($song['id'] ?? 0);
  $pdo = sf_db();
  if ($pdo && $id > 0) {
    try {
      $stmt = $pdo->prepare('SELECT * FROM song_files WHERE song_id = ? AND file_type = ? ORDER BY is_primary DESC, id ASC LIMIT 1');
      $stmt->execute([$id, $fileType]);
      $row = $stmt->fetch();
      if ($row) {
        return $row;
      }
    } catch (Throwable $e) {
      error_log('Media song file lookup failed: ' . $e->getMessage());
    }
  }
  $path = $fileType === 'preview' ? ($song['preview_src'] ?? '') : ($song['full_src'] ?? '');
  return $path ? ['file_path' => $path, 'mime_type' => 'audio/wav', 'file_type' => $fileType] : null;
}

function sf_media_user_can_access(string $contentType, array $record, string $fileType = 'stream'): bool {
  $required = (string)($record['access_level'] ?? $record['access'] ?? 'subscriber');
  if ($fileType === 'preview' || $required === 'public') {
    return true;
  }
  if (($record['status'] ?? 'published') !== 'published' && ($record['status'] ?? 'published') !== 'active') {
    return sf_access_allows('admin');
  }
  $contentId = (int)($record['id'] ?? 0);
  if ($contentType === 'video' && sf_user_has_direct_grant('video', $contentId)) {
    return true;
  }
  if ($contentType === 'song' && sf_user_has_direct_grant('song', $contentId)) {
    return true;
  }
  if (!empty($record['episode_id']) && sf_user_has_direct_grant('episode', (int)$record['episode_id'])) {
    return true;
  }
  return sf_access_allows($required);
}

function sf_media_safe_path(?string $relative): ?string {
  $relative = trim((string)$relative);
  if ($relative === '' || strpos($relative, '..') !== false || preg_match('~^(https?:)?//~i', $relative)) {
    return null;
  }
  $root = realpath(dirname(__DIR__));
  if (!$root) {
    return null;
  }
  $candidates = [
    $root . '/storage/private_media/' . ltrim($relative, '/'),
    $root . '/assets/' . ltrim($relative, '/'),
  ];
  foreach ($candidates as $candidate) {
    $real = realpath($candidate);
    if ($real && is_file($real) && strpos($real, $root) === 0) {
      return $real;
    }
  }
  return null;
}

function sf_media_response_headers(string $filePath, string $mimeType, string $disposition = 'inline'): void {
  $name = basename($filePath);
  header('Content-Type: ' . ($mimeType ?: 'application/octet-stream'));
  header('Accept-Ranges: bytes');
  header('X-Content-Type-Options: nosniff');
  header('Cache-Control: private, no-store, max-age=0');
  header('Content-Disposition: ' . ($disposition === 'download' ? 'attachment' : 'inline') . '; filename="' . addslashes($name) . '"');
}

function sf_media_serve_file(string $filePath, string $mimeType = 'application/octet-stream', string $disposition = 'inline'): void {
  if (!is_file($filePath) || !is_readable($filePath)) {
    http_response_code(404);
    echo 'Media file not found.';
    exit;
  }
  $size = filesize($filePath);
  $start = 0;
  $end = $size - 1;
  sf_media_response_headers($filePath, $mimeType, $disposition);
  if (!empty($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $m)) {
    if ($m[1] !== '') $start = (int)$m[1];
    if ($m[2] !== '') $end = (int)$m[2];
    $start = max(0, min($start, $size - 1));
    $end = max($start, min($end, $size - 1));
    http_response_code(206);
    header("Content-Range: bytes {$start}-{$end}/{$size}");
  }
  $length = $end - $start + 1;
  header('Content-Length: ' . $length);
  $handle = fopen($filePath, 'rb');
  if (!$handle) {
    http_response_code(500);
    exit;
  }
  fseek($handle, $start);
  $sent = 0;
  while (!feof($handle) && $sent < $length) {
    $chunk = fread($handle, min(8192, $length - $sent));
    if ($chunk === false) break;
    $sent += strlen($chunk);
    echo $chunk;
    if (connection_aborted()) break;
  }
  fclose($handle);
  exit;
}

function sf_media_resolve_request(array $request): array {
  $validation = sf_media_validate_token($request);
  if (!$validation['ok']) {
    return $validation;
  }
  $p = $validation['payload'];
  $record = $p['m'] === 'song' ? sf_media_song_record((int)$p['id']) : sf_media_video_record((int)$p['id']);
  if (!$record) {
    return ['ok' => false, 'error' => 'content_not_found'];
  }
  if (!sf_media_user_can_access($p['m'], $record, $p['f'])) {
    return ['ok' => false, 'error' => 'access_denied'];
  }
  $file = $p['m'] === 'song' ? sf_media_song_file($record, $p['f']) : sf_media_video_file($record, $p['f']);
  if (!$file) {
    return ['ok' => false, 'error' => 'media_source_missing'];
  }
  $path = sf_media_safe_path($file['file_path'] ?? '');
  if (!$path) {
    return ['ok' => false, 'error' => 'media_file_missing', 'relative_path' => $file['file_path'] ?? ''];
  }
  return ['ok' => true, 'record' => $record, 'file' => $file, 'path' => $path, 'payload' => $p];
}

function sf_media_video_playback(array $video, bool $allowFull): array {
  $fileType = $allowFull ? 'stream' : 'preview';
  $file = sf_media_video_file($video, $fileType);
  $path = $file ? sf_media_safe_path($file['file_path'] ?? '') : null;
  return [
    'file_type' => $fileType,
    'file_path' => $file['file_path'] ?? '',
    'exists' => (bool)$path,
    'mime_type' => $file['mime_type'] ?? 'video/mp4',
    'url' => !empty($video['id']) && $file ? sf_media_signed_url('video', (int)$video['id'], $fileType, 'stream', 900) : '',
  ];
}

function sf_media_video_next(array $videos, array $current): ?array {
  $currentId = (int)($current['id'] ?? 0);
  $published = array_values(array_filter($videos, static fn($v) => ($v['status'] ?? '') === 'published' && ($v['video_type'] ?? '') === 'episode'));
  usort($published, static fn($a, $b) => [(int)($a['season'] ?? 1), (int)($a['episode_number'] ?? 999), (int)($a['id'] ?? 0)] <=> [(int)($b['season'] ?? 1), (int)($b['episode_number'] ?? 999), (int)($b['id'] ?? 0)]);
  foreach ($published as $i => $video) {
    if ((int)($video['id'] ?? 0) === $currentId) {
      return $published[$i + 1] ?? null;
    }
  }
  return null;
}
?>
