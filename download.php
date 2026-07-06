<?php
require __DIR__ . '/includes/media_delivery.php';

$request = $_GET;
$request['d'] = 'download';
$result = sf_media_resolve_request($request);
if (!$result['ok']) {
  $code = ($result['error'] ?? '') === 'access_denied' ? 403 : 404;
  if (in_array(($result['error'] ?? ''), ['invalid_or_expired_token','invalid_signature'], true)) {
    $code = 401;
  }
  http_response_code($code);
  header('Content-Type: text/plain; charset=utf-8');
  echo 'Stonefellow download unavailable: ' . ($result['error'] ?? 'unknown_error');
  exit;
}

$file = $result['file'];
sf_media_serve_file($result['path'], (string)($file['mime_type'] ?? 'application/octet-stream'), 'download');
