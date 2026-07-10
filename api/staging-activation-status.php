<?php

declare(strict_types=1);

require_once __DIR__.'/../includes/staging_activation.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

$secret=(string)(getenv('SF_STAGING_ACTIVATION_SECRET')?:'');
$timestamp=(string)($_GET['ts']??'');
$signature=preg_replace('/^sha256=/i','',trim((string)($_GET['sig']??'')))??'';
$valid=strlen($secret)>=32&&ctype_digit($timestamp)&&abs(time()-(int)$timestamp)<=300&&strlen($signature)===64&&hash_equals(hash_hmac('sha256',$timestamp,$secret),$signature);
if(!$valid){http_response_code(401);echo json_encode(['ok'=>false,'error'=>'invalid_signature']);exit;}

$summary=sf_sa_activation_summary();
$run=$summary['latest_run']??null;
$candidate=$summary['latest_candidate']??null;
echo json_encode([
  'ok'=>true,
  'schema_ready'=>(bool)($summary['schema_ready']??false),
  'operationally_ready'=>(bool)($summary['operationally_ready']??false),
  'release_commit'=>$summary['release_commit']??'',
  'activation'=>$run?['run_key'=>$run['run_key'],'status'=>$run['run_status'],'score'=>(float)$run['overall_score'],'target_commit_sha'=>$run['target_commit_sha'],'completed_at'=>$run['completed_at']]:null,
  'candidate'=>$candidate?['candidate_key'=>$candidate['candidate_key'],'status'=>$candidate['candidate_status'],'target_commit_sha'=>$candidate['target_commit_sha'],'artifact_sha256'=>$candidate['artifact_sha256'],'frozen_at'=>$candidate['frozen_at']]:null,
],JSON_UNESCAPED_SLASHES);
