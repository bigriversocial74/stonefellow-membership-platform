<?php
require_once __DIR__ . '/../includes/engagement.php';
$user = sf_auth_user();
if (!$user) sf_json_response(['ok'=>false,'error'=>'login_required'], 401);
$userId = (int)$user['id'];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET') {
  $status = trim((string)($_GET['status'] ?? ''));
  sf_json_response(['ok'=>true,'summary'=>sf_member_notification_summary($userId),'notifications'=>sf_member_notifications($userId, $status)]);
}
$data = sf_request_json();
$action = (string)($data['action'] ?? $_POST['action'] ?? 'mark_read');
$id = (int)($data['id'] ?? $_POST['id'] ?? 0);
if ($action === 'mark_all_read') sf_json_response(['ok'=>sf_member_notifications_mark_all_read($userId),'summary'=>sf_member_notification_summary($userId)]);
if ($action === 'dismiss') sf_json_response(['ok'=>sf_member_notification_update($userId, $id, 'dismissed'),'summary'=>sf_member_notification_summary($userId)]);
if ($action === 'mark_unread') sf_json_response(['ok'=>sf_member_notification_update($userId, $id, 'unread'),'summary'=>sf_member_notification_summary($userId)]);
sf_json_response(['ok'=>sf_member_notification_update($userId, $id, 'read'),'summary'=>sf_member_notification_summary($userId)]);
