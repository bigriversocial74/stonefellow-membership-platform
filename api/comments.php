<?php
require_once __DIR__ . '/../includes/engagement.php';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET') {
  $type = trim((string)($_GET['content_type'] ?? 'episode'));
  $id = (int)($_GET['content_id'] ?? 0);
  $slug = trim((string)($_GET['slug'] ?? ''));
  sf_json_response(['ok'=>true,'summary'=>sf_comment_summary(),'comments'=>sf_comments_for($type, $id, $slug, 'approved')]);
}
$user = sf_auth_user();
if (!$user) sf_json_response(['ok'=>false,'error'=>'login_required'], 401);
$data = sf_request_json();
if (!$data && $_POST) $data = $_POST;
$action = (string)($data['action'] ?? 'comment');
if ($action === 'react') {
  sf_json_response(sf_comment_react((int)$user['id'], (string)($data['target_type'] ?? 'comment'), (int)($data['target_id'] ?? 0), (string)($data['reaction_type'] ?? 'like')));
}
$result = sf_comment_create((int)$user['id'], (string)($data['content_type'] ?? 'episode'), (int)($data['content_id'] ?? 0), (string)($data['slug'] ?? ''), (string)($data['body'] ?? ''), isset($data['parent_comment_id']) ? (int)$data['parent_comment_id'] : null);
sf_json_response($result, !empty($result['ok']) ? 200 : 422);
