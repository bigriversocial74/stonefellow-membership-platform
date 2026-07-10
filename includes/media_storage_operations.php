<?php

declare(strict_types=1);

function sf_mp_object_token(array $object, int $expiresAt, string $disposition = 'inline', ?int $userId = null): string {
    $payload = [
        'oid' => (int)$object['id'],
        'exp' => $expiresAt,
        'uid' => $userId ?: 0,
        'd' => $disposition === 'download' ? 'download' : 'inline',
    ];
    ksort($payload);
    $secret = sf_mp_env('SF_MEDIA_SIGNING_KEY', sf_mp_env('SF_HASH_SALT'));
    if (strlen($secret) < 32) return '';
    $payload['sig'] = hash_hmac('sha256', http_build_query($payload), $secret);
    return rtrim(strtr(base64_encode(json_encode($payload, JSON_UNESCAPED_SLASHES)), '+/', '-_'), '=');
}

function sf_mp_validate_object_token(string $token): array {
    $decoded = base64_decode(strtr($token, '-_', '+/'), true);
    $payload = is_string($decoded) ? json_decode($decoded, true) : null;
    if (!is_array($payload)) return ['ok'=>false,'error'=>'invalid_media_token'];
    $sig = (string)($payload['sig'] ?? '');
    unset($payload['sig']);
    if ((int)($payload['oid']??0) <= 0 || (int)($payload['exp']??0) < time() || !preg_match('/^[a-f0-9]{64}$/', $sig)) return ['ok'=>false,'error'=>'expired_or_invalid_media_token'];
    ksort($payload);
    $secret = sf_mp_env('SF_MEDIA_SIGNING_KEY', sf_mp_env('SF_HASH_SALT'));
    if (strlen($secret) < 32 || !hash_equals(hash_hmac('sha256', http_build_query($payload), $secret), $sig)) return ['ok'=>false,'error'=>'invalid_media_signature'];
    return ['ok'=>true,'payload'=>$payload];
}

function sf_mp_object_url(array $object, int $ttl = 900, string $disposition = 'inline', ?int $userId = null): string {
    $token = sf_mp_object_token($object, time() + max(60, min(3600, $ttl)), $disposition, $userId);
    return $token === '' ? '' : sf_url('media-object.php?t=' . rawurlencode($token));
}

function sf_mp_provider_summary(): array {
    $provider = sf_mp_default_provider();
    $driver = sf_mp_driver();
    $localRoot = sf_mp_local_root();
    $binary = sf_mp_binary_status();
    $schema = ['media_storage_providers','media_objects','media_upload_sessions','media_upload_chunks','media_processing_jobs','media_delivery_sessions','media_delivery_events','media_storage_health_runs'];
    $missing = array_values(array_filter($schema, static fn(string $table): bool => !sf_mp_table_exists($table)));
    return [
        'driver' => $driver,
        'mode' => sf_mp_mode(),
        'provider' => $provider,
        'schema_ready' => !$missing,
        'missing_tables' => $missing,
        'storage_ready' => $driver === 'local' ? (is_dir($localRoot) ? is_writable($localRoot) : is_writable(dirname($localRoot))) : sf_mp_s3_ready(),
        'ffmpeg_ready' => $binary['ffmpeg'],
        'ffprobe_ready' => $binary['ffprobe'],
        'audiowaveform_ready' => $binary['audiowaveform'],
    ];
}

function sf_mp_s3_delete_key(string $storageKey): array {
    if (!sf_mp_s3_ready() || !function_exists('curl_init')) return ['ok'=>false,'error'=>'s3_not_ready'];
    $url = sf_mp_s3_presign('DELETE',$storageKey,300);
    $ch = curl_init($url);
    curl_setopt_array($ch,[CURLOPT_CUSTOMREQUEST=>'DELETE',CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>120,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
    $response=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$error=curl_error($ch);curl_close($ch);
    return $response!==false&&in_array($status,[200,202,204],true)?['ok'=>true,'status'=>$status]:['ok'=>false,'error'=>'s3_delete_failed','status'=>$status,'detail'=>$error];
}

function sf_mp_s3_get_string(string $storageKey): array {
    if (!sf_mp_s3_ready() || !function_exists('curl_init')) return ['ok'=>false,'error'=>'s3_not_ready'];
    $url=sf_mp_s3_presign('GET',$storageKey,300);$ch=curl_init($url);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>120,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
    $body=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$error=curl_error($ch);curl_close($ch);
    return is_string($body)&&$status>=200&&$status<300?['ok'=>true,'body'=>$body,'status'=>$status]:['ok'=>false,'error'=>'s3_read_failed','status'=>$status,'detail'=>$error];
}

function sf_mp_run_storage_health_check(): array {
    $pdo=sf_db();$provider=sf_mp_default_provider();
    if(!$pdo||!$provider||!sf_mp_table_exists('media_storage_health_runs'))return['ok'=>false,'error'=>'media_schema_or_provider_missing'];
    $runKey=sf_mp_random_key(24);$started=microtime(true);$testBody='stonefellow-media-health-'.sf_mp_random_key(12);$key='health/'.gmdate('Y/m/d').'/'.$runKey.'.txt';
    try{$pdo->prepare("INSERT INTO media_storage_health_runs (run_key,provider_id,status,started_at) VALUES (?,?,'running',NOW())")->execute([$runKey,(int)$provider['id']]);}catch(Throwable $e){return['ok'=>false,'error'=>'health_run_create_failed'];}
    $write=false;$read=false;$delete=false;$details=[];
    if(($provider['driver']??'local')==='local'){
        $path=sf_mp_local_path($key);if($path){$write=file_put_contents($path,$testBody,LOCK_EX)===strlen($testBody);$read=$write&&file_get_contents($path)===$testBody;$delete=$read&&@unlink($path);$details=['path'=>$key];}
    }else{
        $dir=sf_mp_staging_root().'/health';if(!is_dir($dir))@mkdir($dir,0750,true);$tmp=$dir.'/'.$runKey.'.txt';file_put_contents($tmp,$testBody);
        $put=sf_mp_s3_put_file($tmp,$key,'text/plain');$write=!empty($put['ok']);$get=$write?sf_mp_s3_get_string($key):['ok'=>false];$read=!empty($get['ok'])&&hash_equals($testBody,(string)($get['body']??''));$del=$read?sf_mp_s3_delete_key($key):['ok'=>false];$delete=!empty($del['ok']);@unlink($tmp);$details=['put'=>$put,'get_status'=>$get['status']??null,'delete'=>$del];
    }
    $status=$write&&$read&&$delete?'healthy':(($write||$read||$delete)?'degraded':'failed');$latency=(int)round((microtime(true)-$started)*1000);
    try{$stmt=$pdo->prepare('UPDATE media_storage_health_runs SET status=?,read_test_status=?,write_test_status=?,delete_test_status=?,latency_ms=?,detail_json=?,completed_at=NOW() WHERE run_key=?');$stmt->execute([$status,$read?'passed':'failed',$write?'passed':'failed',$delete?'passed':'failed',$latency,json_encode($details,JSON_UNESCAPED_SLASHES),$runKey]);$pdo->prepare('UPDATE media_storage_providers SET last_health_status=?,last_health_at=NOW(),status=IF(?="failed","degraded",status) WHERE id=?')->execute([$status,$status,(int)$provider['id']]);}catch(Throwable $e){error_log('Stonefellow media health persistence failed: '.$e->getMessage());}
    return['ok'=>$status==='healthy','run_key'=>$runKey,'status'=>$status,'write'=>$write,'read'=>$read,'delete'=>$delete,'latency_ms'=>$latency];
}

function sf_mp_storage_usage_summary(): array {
    $pdo=sf_db();if(!$pdo||!sf_mp_table_exists('media_objects'))return[];
    try{$totals=$pdo->query("SELECT COUNT(*) objects,COALESCE(SUM(size_bytes),0) bytes,SUM(status='ready') ready,SUM(status='failed') failed,SUM(status='quarantined') quarantined FROM media_objects WHERE status<>'deleted'")->fetch()?:[];$roles=$pdo->query("SELECT role,COUNT(*) objects,COALESCE(SUM(size_bytes),0) bytes FROM media_objects WHERE status<>'deleted' GROUP BY role ORDER BY bytes DESC")->fetchAll()?:[];return['objects'=>(int)($totals['objects']??0),'bytes'=>(int)($totals['bytes']??0),'ready'=>(int)($totals['ready']??0),'failed'=>(int)($totals['failed']??0),'quarantined'=>(int)($totals['quarantined']??0),'roles'=>$roles];}catch(Throwable $e){return[];}
}
