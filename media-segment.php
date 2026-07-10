<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/media_delivery.php';
require_once __DIR__ . '/includes/media_pipeline.php';

$validation=sf_mp_validate_delivery_request('segment',$_GET);
if(empty($validation['ok'])){http_response_code(401);echo'Media segment unavailable.';exit;}
$object=$validation['object'];$session=$validation['session'];$allowed=false;
if($object['entity_type']==='video'){ $record=sf_media_video_record((int)$object['entity_id']);$allowed=$record&&sf_media_user_can_access('video',$record,'stream');}
if(!$allowed){sf_mp_log_delivery($session,'denied',$object,403);http_response_code(403);echo'Media segment unavailable.';exit;}
$size=(int)($object['size_bytes']??0);sf_mp_log_delivery($session,'segment',$object,200,$size);
if(($object['driver']??'local')==='local')sf_mp_serve_local_object($object,'inline');
$url=sf_mp_remote_object_url($object,300);if($url===''){http_response_code(503);echo'Media segment unavailable.';exit;}header('Cache-Control: private, no-store');header('Location: '.$url,true,302);exit;
