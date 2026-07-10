<?php
require_once __DIR__ . '/../includes/production_launch.php';

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    sf_json_response(['ok'=>false,'error'=>'method_not_allowed'],405);
}
if (sf_dor_env() !== 'production') {
    sf_json_response(['ok'=>false,'error'=>'production_only'],403);
}
$maxBytes=sf_delivery_env_int('SF_PRODUCTION_DEPLOYMENT_EVENT_MAX_BYTES',262144,1024,1048576);
if((int)($_SERVER['CONTENT_LENGTH']??0)>$maxBytes)sf_json_response(['ok'=>false,'error'=>'payload_too_large'],413);
$raw=(string)file_get_contents('php://input');
if(strlen($raw)>$maxBytes)sf_json_response(['ok'=>false,'error'=>'payload_too_large'],413);
$signature=(string)($_SERVER['HTTP_X_STONEFELLOW_DEPLOYMENT_SIGNATURE']??'');
if(!sf_prod_signature_valid($raw,$signature))sf_json_response(['ok'=>false,'error'=>'invalid_signature'],401);
try{$payload=json_decode($raw,true,32,JSON_THROW_ON_ERROR);}catch(Throwable $e){sf_json_response(['ok'=>false,'error'=>'invalid_json'],400);}
if(!is_array($payload))sf_json_response(['ok'=>false,'error'=>'invalid_payload'],400);
$promotionKey=trim((string)($payload['promotion_key']??''));
$promotion=$promotionKey!==''?sf_admin_fetch_one('SELECT * FROM production_launch_promotions WHERE promotion_key=? LIMIT 1',[$promotionKey]):null;
if(!$promotion)sf_json_response(['ok'=>false,'error'=>'promotion_not_found'],404);
$commit=strtolower(trim((string)($payload['commit_sha']??'')));
if(!preg_match('/^[a-f0-9]{40}$/',$commit)||!hash_equals((string)$promotion['target_commit_sha'],$commit))sf_json_response(['ok'=>false,'error'=>'commit_mismatch'],409);
$eventId=sf_delivery_clean_header((string)($payload['event_id']??''),190);
if($eventId==='')sf_json_response(['ok'=>false,'error'=>'event_id_required'],422);
$existing=sf_admin_fetch_one('SELECT id FROM production_launch_events WHERE source_event_id=? LIMIT 1',[$eventId]);
if($existing)sf_json_response(['ok'=>true,'status'=>'duplicate','event_id'=>$eventId]);
$result=sf_prod_ingest_deployment_event($promotion,$payload);
sf_json_response($result,!empty($result['ok'])?200:422);
