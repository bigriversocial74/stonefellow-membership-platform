<?php

declare(strict_types=1);
putenv('SF_SKIP_INSTALL_REDIRECT=1');
putenv('SF_ENV=testing');
putenv('SF_HASH_SALT='.str_repeat('b',64));
putenv('SF_MEDIA_SIGNING_KEY='.str_repeat('c',64));
putenv('SF_MEDIA_DELIVERY_SESSION_SECRET='.str_repeat('d',64));
putenv('SF_MEDIA_S3_ACCESS_KEY=AKIATESTKEY');
putenv('SF_MEDIA_S3_SECRET_KEY='.str_repeat('s',40));
putenv('SF_MEDIA_S3_BUCKET=stonefellow-test');
putenv('SF_MEDIA_S3_REGION=us-east-1');
putenv('SF_MEDIA_S3_ENDPOINT=https://s3.amazonaws.com');
require_once dirname(__DIR__).'/includes/media_pipeline.php';
$fail=[];$assert=static function(bool $ok,string $message)use(&$fail):void{if(!$ok)$fail[]=$message;};
$assert(sf_mp_safe_key('video/12/original/file.mp4')==='video/12/original/file.mp4','Safe storage key rejected.');
$assert(sf_mp_safe_key('../secret')==='','Traversal storage key accepted.');
$assert(sf_mp_safe_extension('master.MP4')==='mp4','Extension normalization failed.');
$storageKey=sf_mp_storage_key('video',12,'original','mp4');
$assert(str_starts_with($storageKey,'video/12/original/'),'Storage key namespace failed.');
$registry=sf_mp_provider_registry();
$assert(!empty($registry['local_private']['implemented'])&&!empty($registry['s3_compatible']['implemented']),'Storage provider registry incomplete.');
$assert(count(sf_mp_job_catalog())===9,'Processing job catalog must contain nine bounded jobs.');
$assert(sf_mp_media_kind('wav','audio/wav')==='audio','Audio quarantine detection failed.');
$assert(sf_mp_media_kind('php','application/x-php')===null,'Executable upload type was accepted.');
$object=['id'=>44];$token=sf_mp_object_token($object,time()+300,'inline',7);$valid=sf_mp_validate_object_token($token);
$assert(!empty($valid['ok'])&&(int)$valid['payload']['oid']===44&&(int)$valid['payload']['uid']===7,'Media object token validation failed.');
$sig=sf_mp_delivery_signature(str_repeat('a',64),44,time()+300,'segment');
$assert(preg_match('/^[a-f0-9]{64}$/',$sig)===1,'Delivery signature format failed.');
$presigned=sf_mp_s3_presign('GET','video/12/stream/master.m3u8',300);
$assert(str_contains($presigned,'X-Amz-Signature=')&&str_contains($presigned,'X-Amz-Credential='),'S3 SigV4 presigning failed.');
$root=dirname(__DIR__);$markers=[
 'database/migrations/023_protected_media_storage_cdn_transcoding.sql'=>['media_upload_sessions','media_processing_jobs','media_delivery_sessions','media_storage_health_runs'],
 'api/media-upload-init.php'=>['sf_mp_create_upload_session','csrf_failed'],
 'api/media-upload-chunk.php'=>['X_STONEFELLOW_CSRF','sf_mp_receive_upload_chunk'],
 'api/media-processing-worker.php'=>['SF_MEDIA_WORKER_SECRET','hash_hmac','sf_mp_run_worker'],
 'media-manifest.php'=>['sf_mp_render_manifest','application/vnd.apple.mpegurl'],
 'media-segment.php'=>['sf_mp_validate_delivery_request','sf_mp_serve_local_object'],
 'admin/media-pipeline.php'=>['Resumable Ingestion','Processing Queue','Provider Health'],
];
foreach($markers as$file=>$needles){$body=(string)file_get_contents($root.'/'.$file);foreach($needles as$needle)$assert(stripos($body,$needle)!==false,$file.' missing '.$needle);}
if($fail){fwrite(STDERR,"Protected media pipeline smoke failures:\n- ".implode("\n- ",$fail)."\n");exit(1);}echo"Protected media storage, CDN, and transcoding smoke: PASS\n";
