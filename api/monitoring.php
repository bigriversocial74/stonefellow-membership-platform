<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/monitoring_alerts.php';
$user = sf_auth_user();
if (!$user || (($user['role'] ?? '') !== 'admin')) sf_json_response(['ok'=>false,'error'=>'admin_required'],403);
if (!sf_sec_can('admin.ops.manage') && !sf_sec_can('admin.analytics.view')) sf_json_response(['ok'=>false,'error'=>'permission_denied'],403);
$method=$_SERVER['REQUEST_METHOD']??'GET';
if($method==='GET') sf_json_response(['ok'=>true,'summary'=>sf_mon_summary(),'metrics'=>sf_mon_metrics(),'checks'=>sf_mon_service_checks(),'snapshots'=>sf_mon_snapshots(isset($_GET['limit'])?(int)$_GET['limit']:50),'errors'=>sf_mon_recent_errors(100)]);
$data=sf_request_json(); if(!$data&&$_POST)$data=$_POST;
$action=(string)($data['action']??'snapshot');
if($action==='snapshot') sf_json_response(['ok'=>($id=sf_mon_take_snapshot())>0,'snapshot_id'=>$id]);
if($action==='capture_error') sf_json_response(['ok'=>($id=sf_mon_capture_error((string)($data['source']??'manual'),(string)($data['severity']??'error'),(string)($data['message']??''),$data))>0,'error_id'=>$id]);
if($action==='error_status') sf_json_response(['ok'=>sf_mon_update_error((int)($data['error_id']??0),(string)($data['status']??'triaged'))]);
sf_json_response(['ok'=>false,'error'=>'unsupported_action'],422);
