<?php

declare(strict_types=1);

function sf_mp_log_delivery(array $session,string $eventType,?array $object,int $statusCode,int $bytes=0,?int $durationMs=null,array $metadata=[]):void{
    $pdo=sf_db();if(!$pdo||!sf_mp_table_exists('media_delivery_events'))return;
    try{$stmt=$pdo->prepare('INSERT INTO media_delivery_events (delivery_session_id,event_type,object_id,storage_key,status_code,bytes_delivered,duration_ms,metadata_json) VALUES (?,?,?,?,?,?,?,?)');$stmt->execute([(int)$session['id'],$eventType,$object['id']??null,$object['storage_key']??null,$statusCode,$bytes,$durationMs,$metadata?json_encode($metadata,JSON_UNESCAPED_SLASHES):null]);
        if($eventType==='segment'&&$statusCode<400)$pdo->prepare('UPDATE media_delivery_sessions SET segment_count=segment_count+1,bytes_delivered=bytes_delivered+?,last_accessed_at=NOW() WHERE id=?')->execute([$bytes,(int)$session['id']]);
    }catch(Throwable $e){error_log('Stonefellow media delivery logging failed: '.$e->getMessage());}
}

function sf_mp_cdn_signed_url(array $object,int $ttl=300):string{
    $base=rtrim((string)($object['public_base_url']??sf_mp_env('SF_MEDIA_CDN_BASE_URL')),'/');$secret=sf_mp_env('SF_MEDIA_CDN_SIGNING_KEY');$key=sf_mp_safe_key((string)$object['storage_key']);
    if($base===''||strlen($secret)<32||$key==='')return'';$exp=time()+max(60,min(3600,$ttl));$path='/'.$key;$sig=hash_hmac('sha256',$path.'|'.$exp,$secret);return$base.$path.'?exp='.$exp.'&sig='.$sig;
}

function sf_mp_remote_object_url(array $object,int $ttl=300):string{
    $cdn=sf_mp_cdn_signed_url($object,$ttl);if($cdn!=='')return$cdn;
    return($object['driver']??'')==='s3'?sf_mp_s3_presign('GET',(string)$object['storage_key'],$ttl):'';
}

function sf_mp_serve_local_object(array $object,string $disposition='inline'):void{
    $path=sf_mp_local_path((string)$object['storage_key']);if(!$path||!is_file($path)||!is_readable($path)){http_response_code(404);echo'Media object missing.';exit;}
    $size=(int)filesize($path);$start=0;$end=max(0,$size-1);$mime=(string)($object['mime_type']??'application/octet-stream');
    header('Content-Type: '.$mime);header('Accept-Ranges: bytes');header('X-Content-Type-Options: nosniff');header('Cache-Control: private, no-store, max-age=0');header('Content-Disposition: '.($disposition==='download'?'attachment':'inline').'; filename="'.addslashes((string)($object['original_filename']?:basename($path))).'"');
    if(!empty($_SERVER['HTTP_RANGE'])&&preg_match('/bytes=(\d*)-(\d*)/',(string)$_SERVER['HTTP_RANGE'],$m)){$start=$m[1]!==''?(int)$m[1]:0;$end=$m[2]!==''?(int)$m[2]:$end;$start=max(0,min($start,$size-1));$end=max($start,min($end,$size-1));http_response_code(206);header("Content-Range: bytes {$start}-{$end}/{$size}");}
    $length=$end-$start+1;header('Content-Length: '.$length);$fh=fopen($path,'rb');if(!$fh){http_response_code(500);exit;}fseek($fh,$start);$sent=0;while(!feof($fh)&&$sent<$length){$chunk=fread($fh,min(1048576,$length-$sent));if($chunk===false)break;$sent+=strlen($chunk);echo$chunk;if(connection_aborted())break;}fclose($fh);exit;
}

function sf_mp_delivery_summary():array{$pdo=sf_db();if(!$pdo||!sf_mp_table_exists('media_delivery_sessions'))return[];try{$active=(int)$pdo->query("SELECT COUNT(*) FROM media_delivery_sessions WHERE status='active' AND expires_at>NOW()")->fetchColumn();$today=$pdo->query("SELECT COUNT(*) sessions,COALESCE(SUM(bytes_delivered),0) bytes,COALESCE(SUM(segment_count),0) segments FROM media_delivery_sessions WHERE created_at>=CURDATE()")->fetch()?:[];return['active_sessions'=>$active,'today_sessions'=>(int)($today['sessions']??0),'today_bytes'=>(int)($today['bytes']??0),'today_segments'=>(int)($today['segments']??0)];}catch(Throwable $e){return[];}}
