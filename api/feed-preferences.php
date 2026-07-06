<?php
require_once __DIR__ . '/../includes/feed_personalization.php';
$user = sf_auth_user();
if (!$user) sf_json_response(['ok'=>false,'error'=>'login_required'], 401);
$userId = (int)$user['id'];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET') {
  sf_json_response(['ok'=>true,'preferences'=>sf_feed_member_preferences($userId),'follows'=>sf_feed_member_follows($userId),'posts'=>sf_feed_personalized_posts($userId, isset($_GET['limit']) ? (int)$_GET['limit'] : 40)]);
}
$data = sf_request_json();
if (!$data && $_POST) $data = $_POST;
$action = (string)($data['action'] ?? 'preferences');
if ($action === 'follow') sf_json_response(['ok'=>sf_feed_follow_save($userId,(string)($data['target_type'] ?? 'creator'),(string)($data['target_slug'] ?? ''),(int)($data['target_id'] ?? 0),(string)($data['label'] ?? ''),'following'),'follows'=>sf_feed_member_follows($userId)]);
if ($action === 'unfollow') sf_json_response(['ok'=>sf_feed_follow_remove($userId,(string)($data['target_type'] ?? 'creator'),(string)($data['target_slug'] ?? ''),(int)($data['target_id'] ?? 0)),'follows'=>sf_feed_member_follows($userId)]);
if ($action === 'save_item' || $action === 'hide_item' || $action === 'dismiss_item' || $action === 'click_item') {
  $status = $action === 'save_item' ? 'saved' : ($action === 'click_item' ? 'clicked' : ($action === 'dismiss_item' ? 'dismissed' : 'hidden'));
  sf_json_response(['ok'=>sf_feed_item_status($userId,(int)($data['post_id'] ?? 0),$status,(int)($data['score'] ?? 0),(string)($data['reason'] ?? 'API feed action'))]);
}
sf_json_response(['ok'=>sf_feed_save_preferences($userId,(array)($data['preferences'] ?? [])),'preferences'=>sf_feed_member_preferences($userId)]);
