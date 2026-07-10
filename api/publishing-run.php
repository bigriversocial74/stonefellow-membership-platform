<?php
require_once __DIR__ . '/../includes/publishing.php';
if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') sf_json_response(['ok'=>false,'error'=>'method_not_allowed'],405);
$expected=trim((string)(getenv('SF_PUBLISHING_RUN_SECRET')?:''));
$provided=trim((string)($_SERVER['HTTP_X_STONEFELLOW_PUBLISHING_SECRET']??$_POST['secret']??''));
if(strlen($expected)<32||$provided===''||!hash_equals($expected,$provided))sf_json_response(['ok'=>false,'error'=>'unauthorized'],401);
if(!sf_content_rate_limit('publishing-run',6,300))sf_json_response(['ok'=>false,'error'=>'rate_limited'],429);
$result=sf_publish_run_due();
sf_json_response($result,!empty($result['ok'])?200:503);
