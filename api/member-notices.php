<?php
require_once __DIR__ . '/../includes/ops_scheduler_messaging.php';
$user = sf_auth_user();
if (!$user || (($user['role'] ?? '') !== 'admin' && sf_current_access_level() !== 'admin')) sf_json_response(['ok'=>false,'error'=>'admin_required'],403);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET') sf_json_response(['ok'=>true,'summary'=>sf_msg_summary(),'campaigns'=>sf_msg_campaigns(100)]);
$data = sf_request_json();
if (!$data && $_POST) $data = $_POST;
$action = (string)($data['action'] ?? 'save_campaign');
if ($action === 'save_campaign') sf_json_response(['ok'=>($id=sf_msg_save_campaign($data,(int)($data['id']??0)))>0,'id'=>$id]);
if ($action === 'seed_recipients') sf_json_response(['ok'=>true,'added'=>sf_msg_seed_recipients((int)($data['campaign_id']??0))]);
if ($action === 'process_campaign') sf_json_response(['ok'=>true,'result'=>sf_msg_send_campaign((int)($data['campaign_id']??0),isset($data['limit'])?(int)$data['limit']:500)]);
if ($action === 'process_due') sf_json_response(['ok'=>true,'result'=>sf_msg_send_due(isset($data['limit'])?(int)$data['limit']:20)]);
sf_json_response(['ok'=>false,'error'=>'unsupported_action'],422);
