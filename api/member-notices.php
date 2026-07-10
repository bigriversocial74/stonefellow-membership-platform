<?php
require_once __DIR__ . '/../includes/ops_scheduler_messaging.php';
$user=function_exists('sf_auth_user')?sf_auth_user():null;
$isAdmin=$user&&($user['role']??'')==='admin'&&(!function_exists('sf_sec_can')||sf_sec_can('admin.members.manage'));
if(!$isAdmin)sf_json_response(['ok'=>false,'error'=>'admin_required'],403);
$method=strtoupper((string)($_SERVER['REQUEST_METHOD']??'GET'));
if($method==='GET')sf_json_response(['ok'=>true,'summary'=>sf_msg_summary(),'campaigns'=>sf_msg_campaigns(100)]);
if($method!=='POST')sf_json_response(['ok'=>false,'error'=>'method_not_allowed'],405);
$data=sf_request_json(65536);if(!$data&&$_POST)$data=$_POST;$csrf=(string)($data['csrf_token']??$_SERVER['HTTP_X_CSRF_TOKEN']??'');if(!sf_verify_csrf($csrf))sf_json_response(['ok'=>false,'error'=>'csrf_failed'],403);
$action=(string)($data['action']??'save_campaign');
if($action==='save_campaign'){$id=sf_msg_save_campaign($data,(int)($data['id']??0));sf_json_response(['ok'=>$id>0,'id'=>$id],$id>0?200:422);}
if($action==='seed_recipients'){$id=(int)($data['campaign_id']??0);sf_json_response(['ok'=>$id>0,'added'=>$id>0?sf_msg_seed_recipients($id):0],$id>0?200:422);}
if($action==='process_campaign'){$id=(int)($data['campaign_id']??0);if($id<=0)sf_json_response(['ok'=>false,'error'=>'campaign_required'],422);sf_json_response(['ok'=>true,'result'=>sf_msg_send_campaign($id,max(1,min(1000,(int)($data['limit']??500))))]);}
if($action==='process_due')sf_json_response(['ok'=>true,'result'=>sf_msg_send_due(max(1,min(100,(int)($data['limit']??20))))]);
sf_json_response(['ok'=>false,'error'=>'unsupported_action'],422);
