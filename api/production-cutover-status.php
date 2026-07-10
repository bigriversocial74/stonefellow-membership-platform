<?php

declare(strict_types=1);
require_once __DIR__.'/../includes/production_cutover.php';
header('Content-Type: application/json; charset=utf-8');
$timestamp=(string)($_SERVER['HTTP_X_STONEFELLOW_TIMESTAMP']??'');$signature=(string)($_SERVER['HTTP_X_STONEFELLOW_SIGNATURE']??'');$secret=sf_pch_status_secret();if(!ctype_digit($timestamp)||abs(time()-(int)$timestamp)>300||!sf_pch_signature_valid($timestamp,$signature,$secret)){http_response_code(401);echo json_encode(['ok'=>false,'error'=>'Invalid or expired signature']);exit;}
$runId=(int)($_GET['run_id']??0);$run=$runId?sf_pch_run($runId):sf_pch_latest_completed();if(!$run){http_response_code(404);echo json_encode(['ok'=>false,'error'=>'Run not found']);exit;}
$payload=['ok'=>true,'run_key'=>$run['run_key'],'status'=>$run['run_status'],'score'=>(float)$run['overall_score'],'commit'=>$run['target_commit_sha'],'traffic_percent'=>(int)$run['traffic_percent'],'rollback_recommended'=>(bool)$run['rollback_recommended'],'rollback_reason'=>$run['rollback_reason'],'sections'=>sf_pch_section_summary((int)$run['id']),'checkpoints'=>sf_pch_checkpoints((int)$run['id']),'certificate'=>sf_pch_certificate_for_run((int)$run['id']),'generated_at'=>gmdate('c')];echo json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);
