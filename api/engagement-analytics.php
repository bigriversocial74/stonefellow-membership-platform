<?php
require_once __DIR__ . '/../includes/feed_personalization.php';
$user = sf_auth_user();
if (!$user || (($user['role'] ?? '') !== 'admin' && sf_current_access_level() !== 'admin')) sf_json_response(['ok'=>false,'error'=>'admin_required'], 403);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  sf_json_response(['ok'=>sf_engagement_recalculate_member_scores(),'summary'=>sf_engagement_analytics_summary(30)]);
}
sf_json_response(['ok'=>true,'summary'=>sf_engagement_analytics_summary(isset($_GET['days']) ? (int)$_GET['days'] : 30)]);
