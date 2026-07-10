<?php
require_once __DIR__ . '/../includes/notifications.php';

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    sf_json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

$maxBytes = sf_delivery_env_int('SF_NOTIFICATION_WEBHOOK_MAX_BYTES', 262144, 1024, 1048576);
$contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > $maxBytes) {
    sf_json_response(['ok' => false, 'error' => 'payload_too_large'], 413);
}

$raw = (string)file_get_contents('php://input');
if (strlen($raw) > $maxBytes) {
    sf_json_response(['ok' => false, 'error' => 'payload_too_large'], 413);
}

try {
    $payload = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    sf_json_response(['ok' => false, 'error' => 'invalid_json'], 400);
}
if (!is_array($payload)) {
    sf_json_response(['ok' => false, 'error' => 'invalid_payload'], 400);
}

$provider = strtolower(trim((string)(
    $payload['provider']
    ?? $_GET['provider']
    ?? $_SERVER['HTTP_X_STONEFELLOW_PROVIDER']
    ?? 'generic'
)));
if (!preg_match('/^[a-z0-9_-]{1,40}$/', $provider)) {
    sf_json_response(['ok' => false, 'error' => 'invalid_provider'], 422);
}

$signature = (string)(
    $_SERVER['HTTP_X_STONEFELLOW_SIGNATURE']
    ?? $_SERVER['HTTP_X_WEBHOOK_SIGNATURE']
    ?? ''
);
if (!sf_delivery_webhook_signature_valid($provider, $raw, $signature)) {
    sf_json_response(['ok' => false, 'error' => 'invalid_signature'], 401);
}

$eventType = sf_delivery_clean_header((string)($payload['event_type'] ?? $payload['type'] ?? ''), 120);
$eventId = sf_delivery_clean_header((string)($payload['event_id'] ?? $payload['id'] ?? ''), 190);
$messageId = sf_delivery_clean_header(
    (string)($payload['message_id'] ?? $payload['provider_message_id'] ?? $payload['sg_message_id'] ?? ''),
    190
);
if ($eventType === '' || $eventId === '') {
    sf_json_response(['ok' => false, 'error' => 'event_identity_required'], 422);
}
if (!sf_notify_table_exists('notification_webhook_events')) {
    sf_json_response(['ok' => false, 'error' => 'webhook_storage_unavailable'], 503);
}

$redact = null;
$redact = static function ($value) use (&$redact) {
    if (!is_array($value)) {
        return is_scalar($value) ? sf_delivery_clean_text((string)$value, 1000) : null;
    }

    $output = [];
    foreach (array_slice($value, 0, 100, true) as $key => $item) {
        $normalizedKey = strtolower((string)$key);
        if (preg_match('/(email|recipient|address|authorization|token|secret|header|body|html|text)/', $normalizedKey)) {
            $output[$key] = '[redacted]';
            continue;
        }
        $output[$key] = $redact($item);
    }
    return $output;
};

$pdo = sf_db();
if (!$pdo instanceof PDO) {
    sf_json_response(['ok' => false, 'error' => 'database_unavailable'], 503);
}

$logId = null;
$status = 'processed';
$error = null;

try {
    $pdo->beginTransaction();

    $duplicate = $pdo->prepare(
        'SELECT id, status, notification_log_id
         FROM notification_webhook_events
         WHERE provider = ? AND provider_event_id = ?
         FOR UPDATE'
    );
    $duplicate->execute([$provider, $eventId]);
    $existing = $duplicate->fetch();
    if ($existing) {
        $pdo->commit();
        sf_json_response([
            'ok' => true,
            'status' => 'duplicate',
            'notification_log_id' => $existing['notification_log_id']
                ? (int)$existing['notification_log_id']
                : null,
        ]);
    }

    if ($messageId !== '') {
        $lookup = $pdo->prepare('SELECT id FROM notification_logs WHERE provider_message_id = ? LIMIT 1');
        $lookup->execute([$messageId]);
        $logId = $lookup->fetchColumn() ?: null;
    }

    $insert = $pdo->prepare(
        "INSERT INTO notification_webhook_events
         (provider, event_type, provider_event_id, provider_message_id,
          notification_log_id, status, raw_payload_json)
         VALUES (?, ?, ?, ?, ?, 'received', ?)"
    );
    $insert->execute([
        $provider,
        $eventType,
        $eventId,
        $messageId !== '' ? $messageId : null,
        $logId ? (int)$logId : null,
        json_encode($redact($payload), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ]);

    if ($logId) {
        $event = strtolower($eventType);
        if (preg_match('/bounce|fail|drop|reject|complaint|spam/', $event)) {
            $update = $pdo->prepare(
                "UPDATE notification_logs
                 SET status='failed', error_message=?, updated_at=NOW()
                 WHERE id=? AND status<>'canceled'"
            );
            $update->execute([$eventType, (int)$logId]);
        } elseif (preg_match('/deliver|sent/', $event)) {
            $update = $pdo->prepare(
                "UPDATE notification_logs
                 SET status='sent', sent_at=COALESCE(sent_at,NOW()), updated_at=NOW()
                 WHERE id=? AND status IN ('queued','failed','sent')"
            );
            $update->execute([(int)$logId]);
        }
    }

    $processed = $pdo->prepare(
        "UPDATE notification_webhook_events
         SET status='processed', processed_at=NOW(), updated_at=NOW()
         WHERE provider=? AND provider_event_id=?"
    );
    $processed->execute([$provider, $eventId]);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $status = 'failed';
    $error = $e->getMessage();
    error_log('Stonefellow notification webhook failed: ' . $error);
}

sf_json_response([
    'ok' => $status === 'processed',
    'status' => $status,
    'provider' => $provider,
    'event_type' => $eventType,
    'notification_log_id' => $logId ? (int)$logId : null,
], $status === 'processed' ? 200 : 500);
