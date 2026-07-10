<?php

declare(strict_types=1);
require __DIR__.'/../includes/catalog_operations.php';
if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){sf_json_response(['ok'=>false,'error'=>'POST required'],405);}
$expected=(string)(getenv('SF_CATALOG_RUNNER_SECRET')?:'');
$provided=(string)($_SERVER['HTTP_X_SF_CATALOG_SECRET']??'');
if(strlen($expected)<32||!hash_equals($expected,$provided)){sf_json_response(['ok'=>false,'error'=>'unauthorized'],401);}
$eventKey=(string)($_SERVER['HTTP_X_SF_IDEMPOTENCY_KEY']??'');
if(!preg_match('/^[a-zA-Z0-9._:-]{8,64}$/',$eventKey)){sf_json_response(['ok'=>false,'error'=>'invalid_idempotency_key'],422);}
$result=sf_lco_run_due($eventKey);
sf_json_response($result,!empty($result['ok'])?200:409);
