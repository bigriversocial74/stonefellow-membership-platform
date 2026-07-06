<?php
require_once __DIR__ . '/../includes/media_delivery.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  sf_json_response(['ok' => false, 'error' => 'POST required'], 405);
}

$data = sf_request_json();
$contentType = (string)($data['content_type'] ?? $data['media_type'] ?? 'video');
$contentId = sf_int_from_request($data, 'content_id', sf_int_from_request($data, 'id'));
$fileType = (string)($data['file_type'] ?? 'stream');
$disposition = (string)($data['disposition'] ?? 'stream');

if (!in_array($contentType, ['video','song'], true) || $contentId <= 0) {
  sf_json_response(['ok' => false, 'error' => 'invalid_content_request'], 422);
}

$record = $contentType === 'song' ? sf_media_song_record($contentId) : sf_media_video_record($contentId);
if (!$record) {
  sf_json_response(['ok' => false, 'error' => 'content_not_found'], 404);
}
if (!sf_media_user_can_access($contentType, $record, $fileType)) {
  sf_json_response(['ok' => false, 'error' => 'access_denied'], 403);
}

$url = sf_media_signed_url($contentType, $contentId, $fileType, $disposition, 900);
sf_json_response([
  'ok' => true,
  'url' => $url,
  'expires_in' => 900,
  'content_type' => $contentType,
  'content_id' => $contentId,
  'file_type' => $fileType,
]);
