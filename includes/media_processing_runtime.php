<?php

declare(strict_types=1);

function sf_mp_run_process(array $command, int $timeoutSeconds = 3600, ?string $cwd = null): array {
    if (!$command || !is_string($command[0] ?? null)) return ['ok'=>false,'exit_code'=>-1,'stderr'=>'invalid command'];
    $allowed = array_filter([sf_mp_binary_path('ffmpeg'),sf_mp_binary_path('ffprobe'),sf_mp_binary_path('audiowaveform')]);
    if (!in_array($command[0], $allowed, true)) return ['ok'=>false,'exit_code'=>-1,'stderr'=>'binary not allowed'];
    $descriptors = [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']];
    $process = proc_open($command, $descriptors, $pipes, $cwd ?: null, ['PATH'=>'/usr/bin:/bin','LANG'=>'C']);
    if (!is_resource($process)) return ['ok'=>false,'exit_code'=>-1,'stderr'=>'process start failed'];
    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $stdout = '';
    $stderr = '';
    $started = microtime(true);
    $timedOut = false;
    while (true) {
        $status = proc_get_status($process);
        $stdout .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);
        if (!$status['running']) break;
        if ((microtime(true) - $started) > $timeoutSeconds) {
            proc_terminate($process, 15);
            usleep(250000);
            $status = proc_get_status($process);
            if ($status['running']) proc_terminate($process, 9);
            $timedOut = true;
            break;
        }
        usleep(100000);
    }
    $stdout .= stream_get_contents($pipes[1]);
    $stderr .= stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $statusExit = isset($status['exitcode']) ? (int)$status['exitcode'] : -1;
    $exit = proc_close($process);
    if ($exit === -1 && $statusExit >= 0) $exit = $statusExit;
    if (strlen($stdout) > 1048576) $stdout = substr($stdout, -1048576);
    if (strlen($stderr) > 1048576) $stderr = substr($stderr, -1048576);
    return ['ok'=>!$timedOut && $exit===0,'exit_code'=>$exit,'stdout'=>$stdout,'stderr'=>$stderr,'timed_out'=>$timedOut,'duration_ms'=>(int)round((microtime(true)-$started)*1000)];
}

function sf_mp_source_path(array $object): array {
    if (($object['driver'] ?? 'local') === 'local') {
        $path = sf_mp_local_path((string)$object['storage_key']);
        return $path && is_file($path) ? ['ok'=>true,'path'=>$path,'temporary'=>false] : ['ok'=>false,'error'=>'source_file_missing'];
    }
    if (($object['driver'] ?? '') !== 's3' || !sf_mp_s3_ready() || !function_exists('curl_init')) return ['ok'=>false,'error'=>'remote_source_unavailable'];
    $url = sf_mp_s3_presign('GET', (string)$object['storage_key'], 1800);
    $dir = sf_mp_staging_root() . '/worker';
    if (!is_dir($dir) && !mkdir($dir,0750,true) && !is_dir($dir)) return ['ok'=>false,'error'=>'worker_staging_unavailable'];
    $path = $dir . '/' . sf_mp_random_key(20) . '.' . ($object['extension'] ?: 'bin');
    $fh = fopen($path, 'wb');
    if (!$fh) return ['ok'=>false,'error'=>'worker_staging_open_failed'];
    $ch = curl_init($url);
    curl_setopt_array($ch,[CURLOPT_FILE=>$fh,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>3600,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
    $ok = curl_exec($ch);
    $status = (int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    fclose($fh);
    if (!$ok || $status<200 || $status>=300) { @unlink($path); return ['ok'=>false,'error'=>'remote_source_download_failed','status'=>$status]; }
    return ['ok'=>true,'path'=>$path,'temporary'=>true];
}

function sf_mp_probe_file(string $path): array {
    $ffprobe = sf_mp_binary_path('ffprobe');
    if ($ffprobe === '') return ['ok'=>false,'error'=>'ffprobe_unavailable'];
    $result = sf_mp_run_process([$ffprobe,'-v','error','-print_format','json','-show_format','-show_streams',$path], 120);
    if (empty($result['ok'])) return ['ok'=>false,'error'=>'probe_failed','detail'=>$result['stderr']??''];
    $json = json_decode((string)$result['stdout'], true);
    if (!is_array($json)) return ['ok'=>false,'error'=>'probe_invalid_json'];
    $video = null; $audio = null;
    foreach (($json['streams'] ?? []) as $stream) {
        if (($stream['codec_type'] ?? '') === 'video' && !$video) $video = $stream;
        if (($stream['codec_type'] ?? '') === 'audio' && !$audio) $audio = $stream;
    }
    $duration = (float)($json['format']['duration'] ?? $video['duration'] ?? $audio['duration'] ?? 0);
    return ['ok'=>true,'duration_seconds'=>$duration,'format'=>$json['format']??[],'video'=>$video,'audio'=>$audio,'raw'=>$json];
}

function sf_mp_output_temp_dir(int $objectId, string $jobType): ?string {
    $root = sf_mp_staging_root() . '/processing/' . $objectId . '/' . $jobType . '-' . sf_mp_random_key(8);
    return (!is_dir($root) && !mkdir($root,0750,true) && !is_dir($root)) ? null : $root;
}

function sf_mp_store_generated_file(array $sourceObject, string $path, string $role, string $mime, string $extension, array $metadata = [], bool $primary = true): array {
    if (!is_file($path)) return ['ok'=>false,'error'=>'generated_file_missing'];
    $provider = sf_mp_provider_by_id((int)$sourceObject['provider_id']);
    if (!$provider) return ['ok'=>false,'error'=>'provider_not_found'];
    $key = sf_mp_storage_key((string)$sourceObject['entity_type'],(int)$sourceObject['entity_id'],$role,$extension);
    $stored = ($provider['driver'] ?? 'local') === 's3' ? sf_mp_s3_put_file($path,$key,$mime) : sf_mp_copy_local($path,$key);
    if (empty($stored['ok'])) return $stored;
    $parentObjectId = isset($metadata['_parent_object_id']) ? (int)$metadata['_parent_object_id'] : (int)$sourceObject['id'];
    unset($metadata['_parent_object_id']);
    $object = sf_mp_create_object([
        'provider_id'=>(int)$sourceObject['provider_id'],'entity_type'=>$sourceObject['entity_type'],'entity_id'=>(int)$sourceObject['entity_id'],
        'role'=>$role,'parent_object_id'=>$parentObjectId,'storage_key'=>$key,'original_filename'=>basename($path),'extension'=>$extension,
        'mime_type'=>$mime,'size_bytes'=>(int)$stored['size_bytes'],'checksum_sha256'=>$stored['checksum_sha256'],'visibility'=>$role==='preview'||$role==='poster'||$role==='thumbnail'?'public':'member','status'=>'ready','is_primary'=>$primary,'metadata'=>$metadata,
    ]);
    if (!empty($object['ok']) && $primary) sf_mp_set_primary((int)$object['id']);
    return $object;
}

function sf_mp_update_probe_metadata(array $object, array $probe): void {
    $pdo = sf_db();
    if (!$pdo) return;
    $video = $probe['video'] ?? [];
    $audio = $probe['audio'] ?? [];
    $codec = (string)($video['codec_name'] ?? $audio['codec_name'] ?? '');
    $bitrate = (int)round(((int)($probe['format']['bit_rate'] ?? 0)) / 1000);
    try {
        $stmt = $pdo->prepare('UPDATE media_objects SET duration_seconds=?,width_pixels=?,height_pixels=?,bitrate_kbps=?,codec_name=?,metadata_json=? WHERE id=?');
        $stmt->execute([(float)$probe['duration_seconds'] ?: null,(int)($video['width']??0)?:null,(int)($video['height']??0)?:null,$bitrate?:null,$codec?:null,json_encode(['probe'=>$probe['raw']],JSON_UNESCAPED_SLASHES),(int)$object['id']]);
        if ($object['entity_type']==='song' && sf_mp_table_exists('songs')) {
            $stmt=$pdo->prepare('UPDATE songs SET duration_seconds=COALESCE(duration_seconds,?) WHERE id=?');$stmt->execute([(int)round((float)$probe['duration_seconds']),(int)$object['entity_id']]);
        }
        if ($object['entity_type']==='video' && sf_mp_table_exists('videos')) {
            $stmt=$pdo->prepare('UPDATE videos SET runtime_seconds=COALESCE(runtime_seconds,?) WHERE id=?');$stmt->execute([(int)round((float)$probe['duration_seconds']),(int)$object['entity_id']]);
        }
    } catch (Throwable $e) {
        error_log('Stonefellow media probe metadata update failed: '.$e->getMessage());
    }
}
