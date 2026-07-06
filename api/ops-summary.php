<?php
require_once __DIR__ . '/../includes/activity_ops.php';
$user = sf_auth_user();
if (!$user || (($user['role'] ?? '') !== 'admin' && sf_current_access_level() !== 'admin')) { sf_json_response(['ok'=>false,'error'=>'admin_required'], 403); }
sf_json_response(['ok'=>true,'counts'=>sf_ops_metric_counts(),'tasks'=>sf_ops_tasks(),'activity'=>sf_activity_summary(30)]);
