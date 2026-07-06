<?php
require_once __DIR__ . '/../includes/ops_scheduler_messaging.php';
$user = sf_auth_user();
if (!$user || (($user['role'] ?? '') !== 'admin' && sf_current_access_level() !== 'admin')) sf_json_response(['ok'=>false,'error'=>'admin_required'],403);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET') sf_json_response(['ok'=>true,'summary'=>sf_sched_summary(),'jobs'=>sf_sched_jobs(),'runs'=>sf_sched_runs(50)]);
$data = sf_request_json();
if (!$data && $_POST) $data = $_POST;
$action = (string)($data['action'] ?? 'run_due');
if ($action === 'save_job') sf_json_response(['ok'=>($id=sf_sched_save_job($data,(int)($data['id']??0)))>0,'id'=>$id]);
if ($action === 'run_job') { $job=sf_sched_fetch_one('SELECT * FROM ops_scheduled_jobs WHERE id=? LIMIT 1',[(int)($data['id']??0)]); sf_json_response($job?array_merge(['ok'=>true],sf_sched_run_job($job)):['ok'=>false,'error'=>'job_not_found'], $job?200:404); }
sf_json_response(['ok'=>true,'result'=>sf_sched_run_due(isset($data['limit'])?(int)$data['limit']:20)]);
