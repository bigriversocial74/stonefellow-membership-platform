<?php
$relative = (string)($_GET['file'] ?? '');
$relative = rawurldecode($relative);
$relative = str_replace('\\', '/', $relative);
$relative = ltrim($relative, '/');

if (stripos($relative, 'assets/images/') === 0) {
  $relative = substr($relative, strlen('assets/'));
}

if (stripos($relative, 'images/') !== 0 || strpos($relative, '..') !== false || preg_match('~[\x00-\x1F]~', $relative)) {
  http_response_code(400);
  exit('Invalid image path.');
}

$extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
$mimeTypes = [
  'png' => 'image/png',
  'jpg' => 'image/jpeg',
  'jpeg' => 'image/jpeg',
  'gif' => 'image/gif',
  'webp' => 'image/webp',
  'svg' => 'image/svg+xml',
];

if (!isset($mimeTypes[$extension])) {
  http_response_code(415);
  exit('Unsupported image type.');
}

$baseDir = realpath(__DIR__ . '/assets/images');
$filePath = realpath(__DIR__ . '/assets/' . $relative);

if (!$baseDir || !$filePath || strpos($filePath, $baseDir . DIRECTORY_SEPARATOR) !== 0 || !is_file($filePath)) {
  http_response_code(404);
  exit('Image not found.');
}

$etag = '"' . md5($filePath . '|' . filesize($filePath) . '|' . filemtime($filePath)) . '"';
if (trim((string)($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $etag) {
  header('ETag: ' . $etag);
  http_response_code(304);
  exit;
}

header('Content-Type: ' . $mimeTypes[$extension]);
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: public, max-age=604800');
header('ETag: ' . $etag);
readfile($filePath);
exit;
