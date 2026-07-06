<?php
require_once __DIR__ . '/../includes/notifications.php';

$payload = sf_request_json();
$provider = trim((string)($payload['provider'] ?? $_GET['provider'] ?? 'unknown'));
$eventType = trim((string)($payload['event_type'] ?? $payload['type'] ?? 'notification.event'));
$providerEventId = trim((string)($payload['event_id'] ?? $payload['id'] ?? ''));
$providerMessageId = trim((string)($payload['message_id'] ?? $payload['provider_message_id'] ?? $payload['sg_message_id'] ?? ''));
$status = 'received';
$error = null;
$logId = null;

try {
  if (sf_notify_table_exists('notification_logs') && $providerMessageId !== '') {
    $stmt = sf_db()->prepare('SELECT id FROM notification_logs WHERE provider_message_id = ? LIMIT 1');
    $stmt->execute([$providerMessageId]);
    $logId = $stmt->fetchColumn() ?: null;
  }

  if (sf_notify_table_exists('notification_webhook_events')) {
    $stmt = sf_db()->prepare("INSERT INTO notification_webhook_events
      (provider, event_type, provider_event_id, provider_message_id, notification_log_id, status, raw_payload_json)
      VALUES (?, ?, ?, ?, ?, 'received', ?)
      ON DUPLICATE KEY UPDATE status='received', raw_payload_json=VALUES(raw_payload_json), updated_at=CURRENT_TIMESTAMP");
    $stmt->execute([
      $provider,
      $eventType,
      $providerEventId !== '' ? $providerEventId : null,
      $providerMessageId !== '' ? $providerMessageId : null,
      $logId ? (int)$logId : null,
      json_encode($payload, JSON_UNESCAPED_SLASHES),
    ]);
  }

  if ($logId && sf_notify_table_exists('notification_logs')) {
    $eventLower = strtolower($eventType);
    if (str_contains($eventLower, 'bounce') || str_contains($eventLower, 'fail') || str_contains($eventLower, 'drop')) {
      sf_db()->prepare("UPDATE notification_logs SET status='failed', error_message=?, updated_at=NOW() WHERE id=?")->execute([$eventType, (int)$logId]);
    } elseif (str_contains($eventLower, 'deliver') || str_contains($eventLower, 'open') || str_contains($eventLower, 'click')) {
      sf_db()->prepare("UPDATE notification_logs SET status='sent', updated_at=NOW() WHERE id=?")->execute([(int)$logId]);
    }
  }

  $status = 'processed';
} catch (Throwable $e) {
  $status = 'failed';
  $error = $e->getMessage();
  error_log('Stonefellow notification webhook failed: ' . $error);
}

if (sf_notify_table_exists('notification_webhook_events') && $providerEventId !== '') {
  try {
    sf_db()->prepare('UPDATE notification_webhook_events SET status=?, error_message=?, processed_at=NOW() WHERE provider=? AND provider_event_id=?')->execute([$status, $error, $provider, $providerEventId]);
  } catch (Throwable $e) {}
}

sf_json_response([
  'ok' => $status === 'processed',
  'status' => $status,
  'provider' => $provider,
  'event_type' => $eventType,
  'notification_log_id' => $logId ? (int)$logId : null,
  'database' => sf_notify_ready() ? 'ready' : 'not_configured',
]);
