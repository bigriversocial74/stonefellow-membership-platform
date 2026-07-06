<?php
require_once __DIR__ . '/../includes/posts.php';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET') {
  $slug = trim((string)($_GET['slug'] ?? ''));
  if ($slug !== '') sf_json_response(['ok'=>true,'post'=>sf_post_by_slug($slug)]);
  sf_json_response(['ok'=>true,'posts'=>sf_posts_all((string)($_GET['status'] ?? 'published'), isset($_GET['limit']) ? (int)$_GET['limit'] : 80)]);
}
$user = sf_auth_user();
if (!$user || (($user['role'] ?? '') !== 'admin' && sf_current_access_level() !== 'admin')) sf_json_response(['ok'=>false,'error'=>'admin_required'], 403);
$data = sf_request_json();
if (!$data && $_POST) $data = $_POST;
$id = (int)($data['id'] ?? 0);
$action = (string)($data['action'] ?? 'save');
if ($action === 'delete') sf_json_response(['ok'=>sf_post_delete($id)]);
if ($action === 'status') sf_json_response(['ok'=>sf_post_update_status($id, (string)($data['status'] ?? 'draft'))]);
$newId = sf_post_save($data, $id);
sf_json_response(['ok'=>$newId>0,'id'=>$newId]);
