<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/monitoring_alerts.php';
$user = sf_auth_user();
if (!$user || (($user['role'] ?? '') !== 'admin')) sf_json_response(['ok'=>false,'error'=>'admin_required'],403);
if (!sf_sec_can('admin.ops.manage')) sf_json_response(['ok'=>false,'error'=>'permission_denied'],403);
$method=$_SERVER['REQUEST_METHOD']??'GET';
if($method==='GET') sf_json_response(['ok'=>true,'summary'=>sf_mon_summary(),'incidents'=>sf_mon_incidents(isset($_GET['limit'])?(int)$_GET['limit']:100),'alerts'=>sf_mon_alerts(120),'rules'=>sf_mon_rules()]);
$data=sf_request_json(); if(!$data&&$_POST)$data=$_POST;
$action=(string)($data['action']??'create_incident');
if($action==='create_incident') sf_json_response(['ok'=>($id=sf_mon_create_incident((string)($data['title']??''),(string)($data['summary']??''),(string)($data['severity']??'medium'),'manual',(int)($data['error_id']??0),(int)($data['snapshot_id']??0)))>0,'incident_id'=>$id]);
if($action==='update_incident') sf_json_response(['ok'=>sf_mon_update_incident((int)($data['incident_id']??0),(string)($data['status']??'investigating'),(string)($data['severity']??''),(string)($data['resolution_notes']??''))]);
if($action==='event') sf_json_response(['ok'=>sf_mon_incident_event((int)($data['incident_id']??0),(string)($data['event_type']??'manual_update'),(string)($data['event_status']??'info'),(string)($data['message']??''),$data)]);
if($action==='route_alert') sf_json_response(['ok'=>true,'result'=>sf_mon_route_alert((int)($data['incident_id']??0))]);
if($action==='mark_alert') sf_json_response(['ok'=>sf_mon_mark_alert((int)($data['alert_id']??0),(string)($data['status']??'read'))]);
sf_json_response(['ok'=>false,'error'=>'unsupported_action'],422);
