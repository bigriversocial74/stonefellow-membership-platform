<?php
require __DIR__ . '/includes/media_delivery.php';

$result = sf_media_resolve_request($_GET);
if (!$result['ok']) {
  $code = ($result['error'] ?? '') === 'access_denied' ? 403 : 404;
  if (in_array(($result['error'] ?? ''), ['invalid_or_expired_token','invalid_signature'], true)) {
    $code = 401;
  }
  http_response_code($code);
  header('Content-Type: text/plain; charset=utf-8');
  echo 'Stonefellow media unavailable: ' . ($result['error'] ?? 'unknown_error');
  exit;
}

$payload = $result['payload'];
$file = $result['file'];
sf_media_serve_file($result['path'], (string)($file['mime_type'] ?? 'application/octet-stream'), ($payload['d'] ?? 'stream') === 'download' ? 'download' : 'inline');
