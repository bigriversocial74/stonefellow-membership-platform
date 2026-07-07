<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/backup_release.php';
$user = sf_auth_user();
if (!$user || (($user['role'] ?? '') !== 'admin')) sf_json_response(['ok'=>false,'error'=>'admin_required'],403);
if (!sf_sec_can('admin.ops.manage')) sf_json_response(['ok'=>false,'error'=>'permission_denied'],403);
$method=$_SERVER['REQUEST_METHOD']??'GET';
if($method==='GET') sf_json_response(['ok'=>true,'summary'=>sf_br_summary(),'releases'=>sf_rel_releases(isset($_GET['limit'])?(int)$_GET['limit']:100),'events'=>sf_rel_events(0,80),'migrations'=>sf_br_schema_status()]);
$data=sf_request_json(); if(!$data&&$_POST)$data=$_POST;
$action=(string)($data['action']??'save_release');
if($action==='save_release') sf_json_response(['ok'=>($id=sf_rel_create_or_update($data,(int)($data['release_id']??0)))>0,'id'=>$id]);
if($action==='update_task') sf_json_response(['ok'=>sf_rel_update_task((int)($data['task_id']??0),(string)($data['status']??'pending'),(string)($data['detail']??''))]);
if($action==='event') sf_json_response(['ok'=>sf_rel_event((int)($data['release_id']??0),(string)($data['event_type']??'manual_event'),(string)($data['event_status']??'info'),(string)($data['title']??'Release event'),(string)($data['detail']??''))]);
sf_json_response(['ok'=>false,'error'=>'unsupported_action'],422);
