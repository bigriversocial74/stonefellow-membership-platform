<?php
require_once __DIR__ . '/../includes/revenue_dashboard.php';
$user = sf_auth_user();
if (!$user || (($user['role'] ?? '') !== 'admin' && sf_current_access_level() !== 'admin')) sf_json_response(['ok'=>false,'error'=>'admin_required'], 403);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') sf_json_response(['ok'=>sf_rev_save_snapshot(),'summary'=>sf_rev_summary(30)]);
sf_json_response(['ok'=>true,'summary'=>sf_rev_summary(isset($_GET['days']) ? (int)$_GET['days'] : 30),'plans'=>sf_rev_plan_breakdown(),'funnel'=>sf_rev_checkout_funnel(isset($_GET['days']) ? (int)$_GET['days'] : 30),'snapshots'=>sf_rev_recent_snapshots(14)]);
