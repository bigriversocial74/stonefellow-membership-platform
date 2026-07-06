<?php
require_once __DIR__ . '/../includes/library.php';
$userId = sf_current_user_id();
if (!$userId) { sf_json_response(['ok'=>false,'error'=>'login_required'], 401); }
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
  sf_json_response(['ok'=>true,'summary'=>sf_library_summary($userId),'items'=>sf_library_items($userId, (string)($_GET['status'] ?? ''))]);
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { sf_json_response(['ok'=>false,'error'=>'method_not_allowed'],405); }
$data = sf_request_json();
$action = (string)($data['action'] ?? 'save');
if ($action === 'remove') {
  $ok = sf_library_remove_item($userId, (string)($data['content_type'] ?? ''), (int)($data['content_id'] ?? 0), (string)($data['library_status'] ?? 'saved'));
  sf_json_response(['ok'=>$ok,'summary'=>sf_library_summary($userId)]);
}
$ok = sf_library_save_item($userId, $data);
sf_json_response(['ok'=>$ok,'summary'=>sf_library_summary($userId),'items'=>sf_library_items($userId)]);
