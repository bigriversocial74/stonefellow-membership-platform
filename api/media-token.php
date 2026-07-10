<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/media_delivery.php';
require_once __DIR__ . '/../includes/media_pipeline.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') sf_json_response(['ok'=>false,'error'=>'POST required'],405);
$data = sf_request_json();
$contentType = (string)($data['content_type'] ?? $data['media_type'] ?? 'video');
$contentId = sf_int_from_request($data, 'content_id', sf_int_from_request($data, 'id'));
$fileType = (string)($data['file_type'] ?? 'stream');
$disposition = (string)($data['disposition'] ?? 'stream');
if (!in_array($contentType,['video','song'],true) || $contentId<=0) sf_json_response(['ok'=>false,'error'=>'invalid_content_request'],422);
$record = $contentType==='song' ? sf_media_song_record($contentId) : sf_media_video_record($contentId);
if(!$record) sf_json_response(['ok'=>false,'error'=>'content_not_found'],404);
if(!sf_media_user_can_access($contentType,$record,$fileType)) sf_json_response(['ok'=>false,'error'=>'access_denied'],403);
$url='';$delivery='legacy_signed_range';$objectId=null;
if($contentType==='video' && $fileType!=='preview'){
    $manifest=sf_mp_ready_object('video',$contentId,'manifest');
    if($manifest){$url=sf_mp_manifest_url($manifest,sf_current_user_id(),1800);$delivery='signed_hls';$objectId=(int)$manifest['id'];}
}
if($contentType==='song'){
    $role=$fileType==='preview'?'preview':'stream';$object=sf_mp_ready_object('song',$contentId,$role);
    if($object){$url=sf_mp_object_url($object,900,$disposition==='download'?'download':'inline',sf_current_user_id());$delivery='signed_media_object';$objectId=(int)$object['id'];}
}
if($url==='')$url=sf_media_signed_url($contentType,$contentId,$fileType,$disposition,900);
sf_json_response(['ok'=>true,'url'=>$url,'expires_in'=>$delivery==='signed_hls'?1800:900,'content_type'=>$contentType,'content_id'=>$contentId,'file_type'=>$fileType,'delivery'=>$delivery,'media_object_id'=>$objectId]);
