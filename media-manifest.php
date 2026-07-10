<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/media_delivery.php';
require_once __DIR__ . '/includes/media_pipeline.php';

$validation = sf_mp_validate_delivery_request('manifest',$_GET);
if (empty($validation['ok'])) { http_response_code(401); echo 'Media manifest unavailable.'; exit; }
$object=$validation['object'];$session=$validation['session'];$allowed=false;
if ($object['entity_type']==='video') { $record=sf_media_video_record((int)$object['entity_id']); $allowed=$record&&sf_media_user_can_access('video',$record,'stream'); }
if (!$allowed) { sf_mp_log_delivery($session,'denied',$object,403); http_response_code(403); echo 'Media manifest unavailable.'; exit; }
$started=microtime(true);$manifest=sf_mp_render_manifest($session,$object);
if (empty($manifest['ok'])) { sf_mp_log_delivery($session,'error',$object,404,0,(int)((microtime(true)-$started)*1000),['error'=>$manifest['error']??'manifest_error']); http_response_code(404); echo 'Media manifest unavailable.'; exit; }
$body=(string)$manifest['body'];sf_mp_log_delivery($session,'manifest',$object,200,strlen($body),(int)((microtime(true)-$started)*1000),['kind'=>$manifest['kind']??'unknown']);
header('Content-Type: application/vnd.apple.mpegurl');header('Cache-Control: private, no-store');header('X-Content-Type-Options: nosniff');echo $body;
