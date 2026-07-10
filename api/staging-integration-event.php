<?php
require_once __DIR__ . '/../includes/staging_integration_matrix.php';

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    sf_json_response(['ok'=>false,'error'=>'method_not_allowed'],405);
}
if (sf_slc_env() !== 'staging') {
    sf_json_response(['ok'=>false,'error'=>'staging_only'],403);
}
$maxBytes=sf_delivery_env_int('SF_STAGING_INTEGRATION_EVENT_MAX_BYTES',262144,1024,1048576);
if((int)($_SERVER['CONTENT_LENGTH']??0)>$maxBytes)sf_json_response(['ok'=>false,'error'=>'payload_too_large'],413);
$raw=(string)file_get_contents('php://input');
if(strlen($raw)>$maxBytes)sf_json_response(['ok'=>false,'error'=>'payload_too_large'],413);
$signature=(string)($_SERVER['HTTP_X_STONEFELLOW_INTEGRATION_SIGNATURE']??'');
if(!sf_sim_event_signature_valid($raw,$signature))sf_json_response(['ok'=>false,'error'=>'invalid_signature'],401);
try{$payload=json_decode($raw,true,32,JSON_THROW_ON_ERROR);}catch(Throwable $e){sf_json_response(['ok'=>false,'error'=>'invalid_json'],400);}
if(!is_array($payload))sf_json_response(['ok'=>false,'error'=>'invalid_payload'],400);
$executionKey=trim((string)($payload['execution_key']??''));
$execution=sf_sim_execution_by_key($executionKey);
if(!$execution)sf_json_response(['ok'=>false,'error'=>'execution_not_found'],404);
$result=sf_sim_ingest_event(
    $execution,
    (string)($payload['event_id']??''),
    (string)($payload['event_type']??''),
    (string)($payload['provider']??'generic'),
    (string)($payload['assertion_key']??''),
    $payload
);
sf_json_response($result,!empty($result['ok'])?200:422);
