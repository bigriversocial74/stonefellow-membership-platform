<?php

declare(strict_types=1);
require_once __DIR__.'/../includes/production_cutover.php';
header('Content-Type: application/json; charset=utf-8');
if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){http_response_code(405);echo json_encode(['ok'=>false,'error'=>'POST required']);exit;}
$raw=(string)file_get_contents('php://input');$sig=(string)($_SERVER['HTTP_X_STONEFELLOW_SIGNATURE']??'');if(!sf_pch_signature_valid($raw,$sig,sf_pch_event_secret())){http_response_code(401);echo json_encode(['ok'=>false,'error'=>'Invalid signature']);exit;}
$payload=json_decode($raw,true);if(!is_array($payload)){http_response_code(400);echo json_encode(['ok'=>false,'error'=>'Invalid JSON']);exit;}
$run=sf_pch_run((int)($payload['run_id']??0));if(!$run){http_response_code(404);echo json_encode(['ok'=>false,'error'=>'Run not found']);exit;}
$result=sf_pch_ingest_event($run,$payload);http_response_code(!empty($result['ok'])?200:422);echo json_encode($result,JSON_UNESCAPED_SLASHES);
