<?php
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  sf_json_response(['ok' => false, 'error' => 'POST required'], 405);
}

$data = sf_request_json();
$songId = sf_int_from_request($data, 'song_id');
$eventType = (string)($data['event_type'] ?? 'play');
$allowedEvents = ['play','pause','seek','progress','complete','skip','replay','error'];
if (!in_array($eventType, $allowedEvents, true)) {
  $eventType = 'play';
}
if ($songId <= 0) {
  sf_json_response(['ok' => false, 'error' => 'song_id required'], 422);
}

$position = max(0, sf_int_from_request($data, 'position_seconds'));
$secondsPlayed = max(0, sf_int_from_request($data, 'seconds_played'));
$percent = max(0, min(100, sf_float_from_request($data, 'percent_complete')));
$sourcePage = substr((string)($data['source_page'] ?? ($_SERVER['HTTP_REFERER'] ?? '')), 0, 120);
$userId = sf_current_user_id();
$sessionKey = sf_session_key();
$pdo = sf_db();

if (!$pdo) {
  sf_json_response([
    'ok' => true,
    'stored' => false,
    'mode' => 'no_database_configured',
    'message' => 'Tracking payload accepted. Configure SF_DB_* environment variables to persist events.',
  ]);
}

try {
  $stmt = $pdo->prepare("\n    INSERT INTO audio_play_events\n      (user_id, song_id, session_key, event_type, position_seconds, seconds_played, percent_complete, source_page, ip_hash, user_agent_hash)\n    VALUES\n      (:user_id, :song_id, :session_key, :event_type, :position_seconds, :seconds_played, :percent_complete, :source_page, :ip_hash, :user_agent_hash)\n  ");
  $stmt->execute([
    ':user_id' => $userId,
    ':song_id' => $songId,
    ':session_key' => $sessionKey,
    ':event_type' => $eventType,
    ':position_seconds' => $position,
    ':seconds_played' => $secondsPlayed,
    ':percent_complete' => $percent,
    ':source_page' => $sourcePage,
    ':ip_hash' => sf_client_hash($_SERVER['REMOTE_ADDR'] ?? null),
    ':user_agent_hash' => sf_client_hash($_SERVER['HTTP_USER_AGENT'] ?? null),
  ]);

  if ($userId) {
    $progress = $pdo->prepare("\n      INSERT INTO user_song_progress\n        (user_id, song_id, last_position_seconds, total_seconds_played, play_count, completed_count, last_event_type, last_played_at)\n      VALUES\n        (:user_id, :song_id, :position, :seconds_played, :play_inc, :complete_inc, :event_type, NOW())\n      ON DUPLICATE KEY UPDATE\n        last_position_seconds = VALUES(last_position_seconds),\n        total_seconds_played = total_seconds_played + VALUES(total_seconds_played),\n        play_count = play_count + VALUES(play_count),\n        completed_count = completed_count + VALUES(completed_count),\n        last_event_type = VALUES(last_event_type),\n        last_played_at = NOW()\n    ");
    $progress->execute([
      ':user_id' => $userId,
      ':song_id' => $songId,
      ':position' => $position,
      ':seconds_played' => $secondsPlayed,
      ':play_inc' => $eventType === 'play' ? 1 : 0,
      ':complete_inc' => $eventType === 'complete' ? 1 : 0,
      ':event_type' => $eventType,
    ]);
  }

  sf_json_response(['ok' => true, 'stored' => true, 'user_progress_updated' => (bool)$userId]);
} catch (Throwable $e) {
  error_log('Audio tracking failed: ' . $e->getMessage());
  sf_json_response(['ok' => false, 'error' => 'tracking_failed'], 500);
}
