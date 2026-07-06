<?php
require_once __DIR__ . '/../includes/membership.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  sf_json_response(['ok' => false, 'error' => 'POST required'], 405);
}

$member = sf_member_snapshot();
$userId = sf_current_user_id();
if (!$userId) {
  sf_json_response(['ok' => false, 'error' => 'login_required', 'message' => 'Sign in as a paying member to save private playlists.'], 401);
}
if (empty($member['can_manage_playlists'])) {
  sf_json_response(['ok' => false, 'error' => 'subscription_required', 'message' => 'A paid membership is required for private playlists.'], 403);
}

$pdo = sf_db();
if (!$pdo) {
  sf_json_response(['ok' => false, 'error' => 'database_not_configured'], 503);
}

$data = sf_request_json();
$action = (string)($data['action'] ?? 'create');

try {
  if ($action === 'create') {
    $title = trim((string)($data['title'] ?? ''));
    $description = trim((string)($data['description'] ?? ''));
    if ($title === '') {
      sf_json_response(['ok' => false, 'error' => 'title_required'], 422);
    }
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-')) . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
    $stmt = $pdo->prepare("INSERT INTO playlists (user_id, title, slug, description, visibility) VALUES (?, ?, ?, ?, 'private')");
    $stmt->execute([$userId, $title, $slug, $description]);
    sf_json_response(['ok' => true, 'playlist_id' => (int)$pdo->lastInsertId(), 'title' => $title]);
  }

  if ($action === 'add_song') {
    $playlistId = sf_int_from_request($data, 'playlist_id');
    $songId = sf_int_from_request($data, 'song_id');
    if ($playlistId <= 0 || $songId <= 0) {
      sf_json_response(['ok' => false, 'error' => 'playlist_id_and_song_id_required'], 422);
    }
    $owner = $pdo->prepare('SELECT id FROM playlists WHERE id = ? AND user_id = ? LIMIT 1');
    $owner->execute([$playlistId, $userId]);
    if (!$owner->fetch()) {
      sf_json_response(['ok' => false, 'error' => 'playlist_not_found'], 404);
    }
    $stmt = $pdo->prepare("INSERT IGNORE INTO playlist_songs (playlist_id, song_id, sort_order) VALUES (?, ?, 0)");
    $stmt->execute([$playlistId, $songId]);
    sf_json_response(['ok' => true, 'playlist_id' => $playlistId, 'song_id' => $songId]);
  }

  sf_json_response(['ok' => false, 'error' => 'unknown_action'], 400);
} catch (Throwable $e) {
  error_log('Playlist API failed: ' . $e->getMessage());
  sf_json_response(['ok' => false, 'error' => 'playlist_action_failed'], 500);
}
