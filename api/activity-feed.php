<?php
require_once __DIR__ . '/../includes/activity_ops.php';
$user = sf_auth_user();
if (!$user) { sf_json_response(['ok'=>false,'error'=>'login_required'], 401); }
$group = trim((string)($_GET['group'] ?? ''));
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 80;
sf_json_response(['ok'=>true,'summary'=>sf_activity_summary(30),'events'=>sf_activity_recent($limit, $group)]);
