<?php
declare(strict_types=1);
$assets = [];
foreach (['bundle-a.php', 'bundle-b.php', 'bundle-c.php', 'bundle-d.php'] as $bundle) {
  $data = require __DIR__ . '/includes/likenessing_assets/' . $bundle;
  if (is_array($data)) $assets += $data;
}
$name = preg_replace('/[^a-z0-9_-]/i', '', (string)($_GET['name'] ?? ''));
if (!isset($assets[$name])) { http_response_code(404); header('Content-Type: text/plain; charset=utf-8'); echo 'Image not found'; exit; }
$asset = $assets[$name];
$binary = base64_decode($asset['data'], true);
if ($binary === false) { http_response_code(500); exit; }
$etag = '"' . sha1($binary) . '"';
header('Content-Type: ' . $asset['type']);
header('Cache-Control: public, max-age=31536000, immutable');
header('ETag: ' . $etag);
header('X-Content-Type-Options: nosniff');
if (trim((string)($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $etag) { http_response_code(304); exit; }
header('Content-Length: ' . strlen($binary));
echo $binary;
