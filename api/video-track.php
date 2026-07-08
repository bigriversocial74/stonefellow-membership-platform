<?php
require_once __DIR__ . '/../includes/library.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  sf_json_response(['ok' => false, 'error' => 'POST required'], 405);
}

$data = sf_request_json();
$videoId = sf_int_from_request($data, 'video_id');
$episodeId = sf_int_from_request($data, 'episode_id');
$eventType = (string)($data['event_type'] ?? 'play');
$allowedEvents = ['play','pause','seek','progress','complete','rewatch','error'];
if (!in_array($eventType, $allowedEvents, true)) {
  $eventType = 'play';
}
if ($videoId <= 0) {
  sf_json_response(['ok' => false, 'error' => 'video_id required'], 422);
}

$position = max(0, sf_int_from_request($data, 'position_seconds'));
$secondsWatched = max(0, sf_int_from_request($data, 'seconds_watched'));
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
  $stmt = $pdo->prepare("\n    INSERT INTO video_watch_events\n      (user_id, video_id, episode_id, session_key, event_type, position_seconds, seconds_watched, percent_complete, source_page, ip_hash, user_agent_hash)\n    VALUES\n      (:user_id, :video_id, :episode_id, :session_key, :event_type, :position_seconds, :seconds_watched, :percent_complete, :source_page, :ip_hash, :user_agent_hash)\n  ");
  $stmt->execute([
    ':user_id' => $userId,
    ':video_id' => $videoId,
    ':episode_id' => $episodeId ?: null,
    ':session_key' => $sessionKey,
    ':event_type' => $eventType,
    ':position_seconds' => $position,
    ':seconds_watched' => $secondsWatched,
    ':percent_complete' => $percent,
    ':source_page' => $sourcePage,
    ':ip_hash' => sf_client_hash($_SERVER['REMOTE_ADDR'] ?? null),
    ':user_agent_hash' => sf_client_hash($_SERVER['HTTP_USER_AGENT'] ?? null),
  ]);

  if ($userId) {
    $progress = $pdo->prepare("\n      INSERT INTO user_video_progress\n        (user_id, video_id, last_position_seconds, total_seconds_watched, watch_count, completed_count, last_event_type, last_watched_at)\n      VALUES\n        (:user_id, :video_id, :position, :seconds_watched, :watch_inc, :complete_inc, :event_type, NOW())\n      ON DUPLICATE KEY UPDATE\n        last_position_seconds = VALUES(last_position_seconds),\n        total_seconds_watched = total_seconds_watched + VALUES(total_seconds_watched),\n        watch_count = watch_count + VALUES(watch_count),\n        completed_count = completed_count + VALUES(completed_count),\n        last_event_type = VALUES(last_event_type),\n        last_watched_at = NOW()\n    ");
    $progress->execute([
      ':user_id' => $userId,
      ':video_id' => $videoId,
      ':position' => $position,
      ':seconds_watched' => $secondsWatched,
      ':watch_inc' => $eventType === 'play' ? 1 : 0,
      ':complete_inc' => $eventType === 'complete' ? 1 : 0,
      ':event_type' => $eventType,
    ]);

    if ($episodeId > 0) {
      $episodeProgress = $pdo->prepare("\n        INSERT INTO user_episode_progress\n          (user_id, episode_id, primary_video_id, last_position_seconds, total_seconds_watched, completed, completed_at, last_watched_at)\n        VALUES\n          (:user_id, :episode_id, :video_id, :position, :seconds_watched, :completed, :completed_at, NOW())\n        ON DUPLICATE KEY UPDATE\n          primary_video_id = VALUES(primary_video_id),\n          last_position_seconds = VALUES(last_position_seconds),\n          total_seconds_watched = total_seconds_watched + VALUES(total_seconds_watched),\n          completed = GREATEST(completed, VALUES(completed)),\n          completed_at = COALESCE(VALUES(completed_at), completed_at),\n          last_watched_at = NOW()\n      ");
      $completed = $eventType === 'complete' || $percent >= 90;
      $episodeProgress->execute([
        ':user_id' => $userId,
        ':episode_id' => $episodeId,
        ':video_id' => $videoId,
        ':position' => $position,
        ':seconds_watched' => $secondsWatched,
        ':completed' => $completed ? 1 : 0,
        ':completed_at' => $completed ? date('Y-m-d H:i:s') : null,
      ]);
    }

    $status = ($eventType === 'complete' || $percent >= 90) ? 'completed' : 'watchlist';
    $item = sf_library_catalog_item('video', $videoId, $status, [
      'progress_percent' => (int)round($percent),
      'position_seconds' => $position,
      'metadata' => ['episode_id' => $episodeId, 'last_event_type' => $eventType],
    ]);
    if ($item) {
      sf_library_save_item((int)$userId, $item);
    }
  }

  sf_json_response(['ok' => true, 'stored' => true, 'user_progress_updated' => (bool)$userId]);
} catch (Throwable $e) {
  error_log('Video tracking failed: ' . $e->getMessage());
  sf_json_response(['ok' => false, 'error' => 'tracking_failed'], 500);
}
