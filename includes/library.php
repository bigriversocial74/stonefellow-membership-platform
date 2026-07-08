<?php
require_once __DIR__ . '/membership.php';
require_once __DIR__ . '/data.php';

function sf_library_table_exists(string $table): bool {
  $pdo = sf_db();
  if (!$pdo) return false;
  try {
    $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
    $stmt->execute([$table]);
    return (bool)$stmt->fetchColumn();
  } catch (Throwable $e) {
    return false;
  }
}

function sf_library_runtime_active(): bool {
  return (bool)(sf_db() && sf_library_table_exists('member_library_items'));
}

function sf_library_statuses(): array {
  return ['saved' => 'Saved', 'watchlist' => 'Watchlist', 'liked' => 'Liked', 'completed' => 'Completed'];
}

function sf_library_content_url(string $type, string $slug): string {
  if ($type === 'video') return sf_url('watch.php?slug=' . urlencode($slug));
  if ($type === 'episode') return sf_url('episode.php?slug=' . urlencode($slug));
  if ($type === 'song') return sf_url('song.php?slug=' . urlencode($slug));
  if ($type === 'album') return sf_url('album.php?slug=' . urlencode($slug));
  if ($type === 'product') return sf_url('product.php?slug=' . urlencode($slug));
  return '#';
}

function sf_library_catalog_runtime(string $type, int $contentId): int {
  global $catalogSongs, $videoCatalog, $episodes;
  if ($type === 'video') {
    foreach (($videoCatalog ?? []) as $video) {
      if ((int)($video['id'] ?? 0) === $contentId) return max(1, (int)($video['runtime_seconds'] ?? 2880));
    }
  }
  if ($type === 'song') {
    foreach (($catalogSongs ?? []) as $song) {
      if ((int)($song['id'] ?? 0) === $contentId) return max(1, (int)($song['duration_seconds'] ?? 30));
    }
  }
  if ($type === 'episode') {
    foreach (($episodes ?? []) as $index => $episode) {
      $episodeId = (int)($episode['id'] ?? ($index + 1));
      if ($episodeId === $contentId && preg_match('/(\d+)/', (string)($episode['runtime'] ?? ''), $match)) {
        return max(1, (int)$match[1] * 60);
      }
    }
  }
  return $type === 'song' ? 30 : 2880;
}

function sf_library_percent_from_position(string $type, int $contentId, int $position): int {
  $runtime = sf_library_catalog_runtime($type, $contentId);
  return min(100, max(0, (int)round(($position / $runtime) * 100)));
}

function sf_library_catalog_item(string $type, int $contentId, string $status = 'saved', array $overrides = []): ?array {
  global $catalogSongs, $videoCatalog, $episodes, $musicAlbum, $products;
  $status = array_key_exists($status, sf_library_statuses()) ? $status : 'saved';
  $item = null;

  if ($type === 'video') {
    foreach (($videoCatalog ?? []) as $video) {
      if ((int)($video['id'] ?? 0) === $contentId) {
        $item = [
          'content_type' => 'video',
          'content_id' => $contentId,
          'title' => $video['title'] ?? 'Video',
          'slug' => $video['slug'] ?? '',
          'image_path' => $video['poster'] ?? 'images/episodes/episode-01.png',
          'content_url' => sf_library_content_url('video', (string)($video['slug'] ?? '')),
          'library_status' => $status,
          'progress_percent' => (int)($video['resume_percent'] ?? 0),
          'position_seconds' => 0,
          'access_level' => $video['access_level'] ?? 'subscriber',
        ];
        break;
      }
    }
  }

  if ($type === 'episode') {
    foreach (($episodes ?? []) as $index => $episode) {
      $episodeId = (int)($episode['id'] ?? ($index + 1));
      if ($episodeId === $contentId) {
        $item = [
          'content_type' => 'episode',
          'content_id' => $episodeId,
          'title' => $episode['title'] ?? 'Episode',
          'slug' => $episode['slug'] ?? '',
          'image_path' => $episode['image'] ?? 'images/episodes/episode-01.png',
          'content_url' => sf_library_content_url('episode', (string)($episode['slug'] ?? '')),
          'library_status' => $status,
          'progress_percent' => 0,
          'position_seconds' => 0,
          'access_level' => 'subscriber',
        ];
        break;
      }
    }
  }

  if ($type === 'song') {
    foreach (($catalogSongs ?? []) as $song) {
      if ((int)($song['id'] ?? 0) === $contentId) {
        $item = [
          'content_type' => 'song',
          'content_id' => $contentId,
          'title' => $song['title'] ?? 'Song',
          'slug' => $song['slug'] ?? '',
          'image_path' => $song['cover'] ?? 'images/music/soundtrack-cover.png',
          'content_url' => sf_library_content_url('song', (string)($song['slug'] ?? '')),
          'library_status' => $status,
          'progress_percent' => 0,
          'position_seconds' => 0,
          'access_level' => $song['access'] ?? 'subscriber',
          'metadata' => ['artist' => $song['artist'] ?? 'Stonefellow', 'episode' => $song['episode_short'] ?? ($song['episode'] ?? '')],
        ];
        break;
      }
    }
  }

  if ($type === 'album' && $contentId === 1 && !empty($musicAlbum)) {
    $item = [
      'content_type' => 'album',
      'content_id' => 1,
      'title' => $musicAlbum['title'] ?? 'Album',
      'slug' => $musicAlbum['slug'] ?? '',
      'image_path' => $musicAlbum['cover'] ?? 'images/music/soundtrack-cover.png',
      'content_url' => sf_library_content_url('album', (string)($musicAlbum['slug'] ?? '')),
      'library_status' => $status,
      'progress_percent' => 0,
      'position_seconds' => 0,
      'access_level' => 'subscriber',
    ];
  }

  if ($type === 'product') {
    foreach (($products ?? []) as $product) {
      if ((int)($product['id'] ?? 0) === $contentId) {
        $item = [
          'content_type' => 'product',
          'content_id' => $contentId,
          'title' => $product['name'] ?? 'Product',
          'slug' => $product['slug'] ?? '',
          'image_path' => $product['image'] ?? 'images/merch/merch-hero.png',
          'content_url' => sf_library_content_url('product', (string)($product['slug'] ?? '')),
          'library_status' => $status,
          'progress_percent' => 0,
          'position_seconds' => 0,
          'access_level' => 'public',
        ];
        break;
      }
    }
  }

  if (!$item && !empty($overrides['title'])) {
    $item = [
      'content_type' => $type,
      'content_id' => $contentId,
      'title' => (string)$overrides['title'],
      'slug' => (string)($overrides['slug'] ?? ''),
      'image_path' => (string)($overrides['image_path'] ?? 'images/brand/logo-mark.png'),
      'content_url' => (string)($overrides['content_url'] ?? '#'),
      'library_status' => $status,
      'progress_percent' => 0,
      'position_seconds' => 0,
      'access_level' => (string)($overrides['access_level'] ?? 'subscriber'),
    ];
  }

  if (!$item) return null;
  foreach ($overrides as $key => $value) {
    if ($value !== null && $value !== '') $item[$key] = $value;
  }
  $item['library_status'] = $status;
  $item['progress_percent'] = min(100, max(0, (int)($item['progress_percent'] ?? 0)));
  $item['position_seconds'] = max(0, (int)($item['position_seconds'] ?? 0));
  return $item;
}

function sf_library_static_items(): array {
  global $catalogSongs, $videoCatalog, $episodes, $musicAlbum, $products;
  $items = [];
  foreach (array_slice($videoCatalog ?? [], 0, 6) as $video) {
    $item = sf_library_catalog_item('video', (int)($video['id'] ?? 0), 'watchlist');
    if ($item) $items[] = $item;
  }
  foreach (array_slice($catalogSongs ?? [], 0, 6) as $song) {
    $item = sf_library_catalog_item('song', (int)($song['id'] ?? 0), !empty($song['is_featured']) ? 'liked' : 'saved');
    if ($item) $items[] = $item;
  }
  foreach (array_slice($episodes ?? [], 0, 4) as $index => $episode) {
    $item = sf_library_catalog_item('episode', (int)($episode['id'] ?? ($index + 1)), 'watchlist');
    if ($item) $items[] = $item;
  }
  if (!empty($musicAlbum)) {
    $item = sf_library_catalog_item('album', 1, 'saved');
    if ($item) $items[] = $item;
  }
  foreach (array_slice($products ?? [], 0, 4) as $product) {
    $item = sf_library_catalog_item('product', (int)($product['id'] ?? 0), 'saved');
    if ($item) $items[] = $item;
  }
  return $items;
}

function sf_library_normalize_db_item(array $row): array {
  $type = (string)($row['content_type'] ?? 'item');
  $contentId = (int)($row['content_id'] ?? 0);
  $slug = (string)($row['slug'] ?? '');
  $metadata = [];
  if (!empty($row['metadata_json'])) {
    $decoded = json_decode((string)$row['metadata_json'], true);
    $metadata = is_array($decoded) ? $decoded : [];
  }
  $row['content_type'] = $type;
  $row['content_id'] = $contentId;
  $row['content_url'] = (string)($row['content_url'] ?? '') ?: ($slug !== '' ? sf_library_content_url($type, $slug) : '#');
  $row['image_path'] = (string)($row['image_path'] ?? '') ?: 'images/brand/logo-mark.png';
  $row['library_status'] = (string)($row['library_status'] ?? 'saved');
  $row['progress_percent'] = min(100, max(0, (int)($row['progress_percent'] ?? 0)));
  $row['position_seconds'] = max(0, (int)($row['position_seconds'] ?? 0));
  $row['metadata'] = $metadata;
  return $row;
}

function sf_library_progress_items(?int $userId = null): array {
  $userId = $userId ?: sf_current_user_id();
  $pdo = sf_db();
  if (!$pdo || !$userId) return [];

  $items = [];
  try {
    if (sf_library_table_exists('user_video_progress')) {
      $stmt = $pdo->prepare('SELECT * FROM user_video_progress WHERE user_id = ? ORDER BY last_watched_at DESC LIMIT 100');
      $stmt->execute([$userId]);
      foreach ($stmt->fetchAll() ?: [] as $row) {
        $position = (int)($row['last_position_seconds'] ?? 0);
        $videoId = (int)($row['video_id'] ?? 0);
        $status = ((int)($row['completed_count'] ?? 0) > 0) ? 'completed' : 'watchlist';
        $item = sf_library_catalog_item('video', $videoId, $status, [
          'position_seconds' => $position,
          'progress_percent' => sf_library_percent_from_position('video', $videoId, $position),
        ]);
        if ($item) $items[] = $item;
      }
    }

    if (sf_library_table_exists('user_episode_progress')) {
      $stmt = $pdo->prepare('SELECT * FROM user_episode_progress WHERE user_id = ? ORDER BY last_watched_at DESC LIMIT 100');
      $stmt->execute([$userId]);
      foreach ($stmt->fetchAll() ?: [] as $row) {
        $position = (int)($row['last_position_seconds'] ?? 0);
        $episodeId = (int)($row['episode_id'] ?? 0);
        $status = !empty($row['completed']) ? 'completed' : 'watchlist';
        $item = sf_library_catalog_item('episode', $episodeId, $status, [
          'position_seconds' => $position,
          'progress_percent' => sf_library_percent_from_position('episode', $episodeId, $position),
        ]);
        if ($item) $items[] = $item;
      }
    }

    if (sf_library_table_exists('user_song_progress')) {
      $stmt = $pdo->prepare('SELECT * FROM user_song_progress WHERE user_id = ? ORDER BY last_played_at DESC LIMIT 100');
      $stmt->execute([$userId]);
      foreach ($stmt->fetchAll() ?: [] as $row) {
        $position = (int)($row['last_position_seconds'] ?? 0);
        $songId = (int)($row['song_id'] ?? 0);
        $status = ((int)($row['completed_count'] ?? 0) > 0) ? 'completed' : 'saved';
        $item = sf_library_catalog_item('song', $songId, $status, [
          'position_seconds' => $position,
          'progress_percent' => sf_library_percent_from_position('song', $songId, $position),
        ]);
        if ($item) $items[] = $item;
      }
    }
  } catch (Throwable $e) {
    error_log('Stonefellow progress library lookup failed: ' . $e->getMessage());
  }
  return $items;
}

function sf_library_dedupe_items(array $items): array {
  $seen = [];
  $out = [];
  foreach ($items as $item) {
    $key = ($item['content_type'] ?? 'item') . ':' . (int)($item['content_id'] ?? 0) . ':' . ($item['library_status'] ?? 'saved');
    if (isset($seen[$key])) continue;
    $seen[$key] = true;
    $out[] = $item;
  }
  return $out;
}

function sf_library_items(?int $userId = null, string $status = ''): array {
  $userId = $userId ?: sf_current_user_id();
  $pdo = sf_db();
  $runtimeActive = $pdo && $userId && sf_library_table_exists('member_library_items');
  $rows = [];

  if ($runtimeActive) {
    try {
      $where = 'WHERE user_id = ?';
      $params = [$userId];
      if ($status !== '') {
        $where .= ' AND library_status = ?';
        $params[] = $status;
      }
      $stmt = $pdo->prepare("SELECT * FROM member_library_items {$where} ORDER BY last_interaction_at DESC, id DESC LIMIT 200");
      $stmt->execute($params);
      $rows = array_map('sf_library_normalize_db_item', $stmt->fetchAll() ?: []);
    } catch (Throwable $e) {
      error_log('Stonefellow library lookup failed: ' . $e->getMessage());
    }
  }

  $progress = ($status === '' || in_array($status, ['watchlist', 'completed', 'saved'], true)) ? sf_library_progress_items($userId) : [];
  $items = sf_library_dedupe_items(array_merge($rows, $progress));
  if ($status !== '') {
    $items = array_values(array_filter($items, static fn($item) => ($item['library_status'] ?? '') === $status));
  }

  return $runtimeActive ? $items : ($items ?: sf_library_static_items());
}

function sf_library_summary(?int $userId = null): array {
  $items = sf_library_items($userId);
  $summary = ['saved' => 0, 'watchlist' => 0, 'liked' => 0, 'completed' => 0, 'total' => count($items)];
  foreach ($items as $item) {
    $status = (string)($item['library_status'] ?? 'saved');
    $summary[$status] = ($summary[$status] ?? 0) + 1;
  }
  return $summary;
}

function sf_library_save_item(int $userId, array $item): bool {
  $pdo = sf_db();
  if (!$pdo || !sf_library_table_exists('member_library_items')) return false;
  $type = (string)($item['content_type'] ?? '');
  $contentId = (int)($item['content_id'] ?? 0);
  $status = (string)($item['library_status'] ?? 'saved');
  if ($type === '' || $contentId <= 0) return false;
  try {
    $stmt = $pdo->prepare("INSERT INTO member_library_items (user_id, content_type, content_id, slug, title, image_path, content_url, library_status, progress_percent, position_seconds, metadata_json, last_interaction_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE slug = VALUES(slug), title = VALUES(title), image_path = VALUES(image_path), content_url = VALUES(content_url), progress_percent = VALUES(progress_percent), position_seconds = VALUES(position_seconds), metadata_json = VALUES(metadata_json), last_interaction_at = NOW()");
    return $stmt->execute([
      $userId,
      $type,
      $contentId,
      (string)($item['slug'] ?? ''),
      (string)($item['title'] ?? ''),
      (string)($item['image_path'] ?? ''),
      (string)($item['content_url'] ?? ''),
      $status,
      (int)($item['progress_percent'] ?? 0),
      (int)($item['position_seconds'] ?? 0),
      json_encode($item['metadata'] ?? [], JSON_UNESCAPED_SLASHES),
    ]);
  } catch (Throwable $e) {
    error_log('Stonefellow library save failed: ' . $e->getMessage());
    return false;
  }
}

function sf_library_remove_item(int $userId, string $type, int $contentId, string $status = 'saved'): bool {
  $pdo = sf_db();
  if (!$pdo || !sf_library_table_exists('member_library_items')) return false;
  try {
    $stmt = $pdo->prepare('DELETE FROM member_library_items WHERE user_id = ? AND content_type = ? AND content_id = ? AND library_status = ?');
    return $stmt->execute([$userId, $type, $contentId, $status]);
  } catch (Throwable $e) {
    return false;
  }
}

function sf_member_playlists(?int $userId = null): array {
  global $memberPlaylists;
  $userId = $userId ?: sf_current_user_id();
  $pdo = sf_db();
  $runtimeActive = $pdo && $userId && sf_library_table_exists('playlists') && sf_library_table_exists('playlist_songs');
  if (!$runtimeActive) {
    return $memberPlaylists ?? [];
  }

  try {
    $stmt = $pdo->prepare('SELECT p.*, COUNT(ps.id) AS song_count FROM playlists p LEFT JOIN playlist_songs ps ON ps.playlist_id = p.id WHERE p.user_id = ? GROUP BY p.id ORDER BY p.updated_at DESC, p.id DESC LIMIT 100');
    $stmt->execute([$userId]);
    $rows = [];
    foreach ($stmt->fetchAll() ?: [] as $row) {
      $rows[] = [
        'id' => (int)$row['id'],
        'title' => (string)$row['title'],
        'description' => (string)($row['description'] ?? ''),
        'song_count' => (int)($row['song_count'] ?? 0),
        'cover' => 'images/music/soundtrack-cover.png',
        'visibility' => (string)($row['visibility'] ?? 'private'),
        'url' => 'playlists.php?playlist_id=' . (int)$row['id'],
      ];
    }
    return $rows;
  } catch (Throwable $e) {
    error_log('Stonefellow playlist lookup failed: ' . $e->getMessage());
    return [];
  }
}
?>