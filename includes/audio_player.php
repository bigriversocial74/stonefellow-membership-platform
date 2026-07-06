<?php
require_once __DIR__ . '/media_delivery.php';

function sf_audio_file_type(array $song, array $member): string {
  $required = (string)($song['access'] ?? $song['access_level'] ?? 'subscriber');
  return (!empty($member['can_stream_full_music']) && sf_access_allows($required, $member['access_level'] ?? null)) ? 'full' : 'preview';
}

function sf_audio_track_payload(array $song, array $member): array {
  $fileType = sf_audio_file_type($song, $member);
  $songId = (int)($song['id'] ?? 0);
  $file = $songId > 0 ? sf_media_song_file($song, $fileType) : null;
  $signed = ($songId > 0 && $file) ? sf_media_signed_url('song', $songId, $fileType, 'stream', 900) : '';
  $fallback = sf_asset(sf_song_audio_src($song, $fileType === 'full'));
  return [
    'id' => $songId,
    'title' => (string)($song['title'] ?? 'Stonefellow'),
    'artist' => (string)($song['artist'] ?? 'Stonefellow'),
    'src' => $signed ?: $fallback,
    'source_mode' => $fileType,
    'cover' => sf_asset((string)($song['cover'] ?? 'images/music/soundtrack-cover.png')),
    'url' => sf_url('song.php?slug=' . urlencode((string)($song['slug'] ?? ''))),
    'duration' => (string)($song['duration'] ?? '0:30'),
    'duration_seconds' => (int)($song['duration_seconds'] ?? 30),
    'preview_seconds' => (int)($song['preview_seconds'] ?? 30),
    'access' => (string)($song['access'] ?? $song['access_level'] ?? 'subscriber'),
  ];
}

function sf_audio_tracks_payload(array $songs, array $member): array {
  return array_values(array_map(static fn($song) => sf_audio_track_payload($song, $member), $songs));
}

function sf_audio_track_map(array $songs, array $member): array {
  $map = [];
  foreach ($songs as $song) {
    $payload = sf_audio_track_payload($song, $member);
    $map[(int)$payload['id']] = $payload;
  }
  return $map;
}

function sf_audio_player_state(?int $userId = null): array {
  $userId = $userId ?: sf_current_user_id();
  $default = ['queue' => [], 'current_song_id' => null, 'position_seconds' => 0, 'shuffle' => false, 'repeat_mode' => 'off', 'updated_at' => null];
  $pdo = sf_db();
  if (!$pdo || !$userId) return $default;
  try {
    $stmt = $pdo->prepare("SELECT state_json FROM user_player_state WHERE user_id = ? AND player_type = 'audio' LIMIT 1");
    $stmt->execute([$userId]);
    $json = $stmt->fetchColumn();
    $state = $json ? json_decode((string)$json, true) : [];
    return is_array($state) ? array_merge($default, $state) : $default;
  } catch (Throwable $e) {
    return $default;
  }
}

function sf_audio_save_player_state(int $userId, array $state): bool {
  $pdo = sf_db();
  if (!$pdo || !$userId) return false;
  $safe = [
    'queue' => array_values(array_filter(array_map('intval', $state['queue'] ?? []))),
    'current_song_id' => isset($state['current_song_id']) ? (int)$state['current_song_id'] : null,
    'position_seconds' => max(0, (int)($state['position_seconds'] ?? 0)),
    'shuffle' => !empty($state['shuffle']),
    'repeat_mode' => in_array(($state['repeat_mode'] ?? 'off'), ['off','one','all'], true) ? $state['repeat_mode'] : 'off',
    'updated_at' => date('c'),
  ];
  try {
    $stmt = $pdo->prepare("INSERT INTO user_player_state (user_id, player_type, state_json, updated_at) VALUES (?, 'audio', ?, NOW()) ON DUPLICATE KEY UPDATE state_json=VALUES(state_json), updated_at=NOW()");
    return $stmt->execute([$userId, json_encode($safe, JSON_UNESCAPED_SLASHES)]);
  } catch (Throwable $e) {
    error_log('Stonefellow audio player state save failed: ' . $e->getMessage());
    return false;
  }
}
?>
