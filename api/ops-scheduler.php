<?php
require_once __DIR__ . '/../includes/ops_scheduler_messaging.php';
$method=strtoupper((string)($_SERVER['REQUEST_METHOD']??'GET'));
$user=function_exists('sf_auth_user')?sf_auth_user():null;
$isAdmin=$user&&($user['role']??'')==='admin'&&(!function_exists('sf_sec_can')||sf_sec_can('admin.ops.manage'));
$expected=trim((string)(getenv('SF_OPS_SCHEDULER_SECRET')?:''));
$provided=trim((string)($_SERVER['HTTP_X_STONEFELLOW_SCHEDULER_SECRET']??''));
$isCron=$method==='POST'&&strlen($expected)>=32&&$provided!==''&&hash_equals($expected,$provided);
if($method==='GET'){
  if(!$isAdmin)sf_json_response(['ok'=>false,'error'=>'admin_required'],403);
  sf_json_response(['ok'=>true,'summary'=>sf_sched_summary(),'jobs'=>sf_sched_jobs(),'runs'=>sf_sched_runs(50)]);
}
if($method!=='POST')sf_json_response(['ok'=>false,'error'=>'method_not_allowed'],405);
if($isCron){if(!sf_security_session_rate_limit('ops-scheduler-cron',12,3600)['allowed'])sf_json_response(['ok'=>false,'error'=>'rate_limited'],429);sf_json_response(['ok'=>true,'result'=>sf_sched_run_due(20)]);}
if(!$isAdmin)sf_json_response(['ok'=>false,'error'=>'admin_required'],403);
$data=sf_request_json(65536);if(!$data&&$_POST)$data=$_POST;$csrf=(string)($data['csrf_token']??$_SERVER['HTTP_X_CSRF_TOKEN']??'');if(!sf_verify_csrf($csrf))sf_json_response(['ok'=>false,'error'=>'csrf_failed'],403);
$action=(string)($data['action']??'run_due');
if($action==='save_job'){$id=sf_sched_save_job($data,(int)($data['id']??0));sf_json_response(['ok'=>$id>0,'id'=>$id],$id>0?200:422);}
if($action==='run_job'){$job=sf_sched_fetch_one('SELECT * FROM ops_scheduled_jobs WHERE id=? LIMIT 1',[(int)($data['id']??0)]);sf_json_response($job?array_merge(['ok'=>true],sf_sched_run_job($job,true)):['ok'=>false,'error'=>'job_not_found'],$job?200:404);}
if($action==='run_due')sf_json_response(['ok'=>true,'result'=>sf_sched_run_due(max(1,min(100,(int)($data['limit']??20))))]);
sf_json_response(['ok'=>false,'error'=>'unsupported_action'],422);
