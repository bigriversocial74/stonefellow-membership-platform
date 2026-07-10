<?php

declare(strict_types=1);

function sf_mp_manifest_url(array $manifestObject, ?int $userId = null, int $ttl = 1800): string {
    $session = sf_mp_create_delivery_session($manifestObject,$userId,$ttl);
    if (empty($session['ok'])) return '';
    return sf_mp_delivery_url('manifest',(string)$session['session_token'],(int)$manifestObject['id'],(int)$session['expires_at']);
}

function sf_mp_object_content(array $object): array {
    if (($object['driver'] ?? 'local') === 'local') {
        $path = sf_mp_local_path((string)$object['storage_key']);
        if (!$path || !is_file($path) || !is_readable($path)) return ['ok'=>false,'error'=>'media_object_file_missing'];
        $body = file_get_contents($path);
        return is_string($body) ? ['ok'=>true,'body'=>$body,'path'=>$path] : ['ok'=>false,'error'=>'media_object_read_failed'];
    }
    if (($object['driver'] ?? '') === 's3' && sf_mp_s3_ready() && function_exists('curl_init')) {
        $url = sf_mp_s3_presign('GET',(string)$object['storage_key'],300);
        $ch = curl_init($url);
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>120,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
        $body = curl_exec($ch); $status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE); curl_close($ch);
        return is_string($body)&&$status>=200&&$status<300?['ok'=>true,'body'=>$body]:['ok'=>false,'error'=>'remote_media_read_failed','status'=>$status];
    }
    return ['ok'=>false,'error'=>'media_provider_unavailable'];
}

function sf_mp_child_objects(int $parentId, string $role): array {
    $pdo = sf_db();
    if (!$pdo || $parentId <= 0) return [];
    try {
        $stmt = $pdo->prepare("SELECT mo.*,msp.driver,msp.provider_key,msp.mode,msp.bucket_name,msp.region_name,msp.endpoint_url,msp.public_base_url FROM media_objects mo JOIN media_storage_providers msp ON msp.id=mo.provider_id WHERE mo.parent_object_id=? AND mo.role=? AND mo.status='ready' ORDER BY mo.id ASC");
        $stmt->execute([$parentId,$role]);
        return $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function sf_mp_metadata(array $object): array {
    $value = $object['metadata_json'] ?? null;
    if (is_array($value)) return $value;
    $decoded = is_string($value) ? json_decode($value,true) : null;
    return is_array($decoded) ? $decoded : [];
}

function sf_mp_render_manifest(array $session, array $object): array {
    if (($object['role'] ?? '') !== 'manifest') return ['ok'=>false,'error'=>'not_a_manifest'];
    $children = sf_mp_child_objects((int)$object['id'],'manifest');
    $expiresAt = min(strtotime((string)$session['expires_at']), time()+1800);
    if ($children) {
        $body = "#EXTM3U\n#EXT-X-VERSION:3\n";
        foreach ($children as $child) {
            $meta = sf_mp_metadata($child);
            $profile = $meta['profile'] ?? [];
            $width = (int)($profile['width'] ?? 0);
            $height = (int)($profile['height'] ?? 0);
            $bandwidth = (int)($profile['bandwidth'] ?? 1000000);
            $body .= '#EXT-X-STREAM-INF:BANDWIDTH=' . max(128000,$bandwidth);
            if ($width>0&&$height>0) $body .= ',RESOLUTION='.$width.'x'.$height;
            $body .= "\n" . sf_mp_delivery_url('manifest',(string)$session['session_token'],(int)$child['id'],$expiresAt) . "\n";
        }
        return ['ok'=>true,'body'=>$body,'kind'=>'master'];
    }
    $content = sf_mp_object_content($object);
    if (empty($content['ok'])) return $content;
    $segments = sf_mp_child_objects((int)$object['id'],'segment');
    usort($segments,static function(array $a,array $b):int{return (int)(sf_mp_metadata($a)['sequence']??$a['id']) <=> (int)(sf_mp_metadata($b)['sequence']??$b['id']);});
    $lines = preg_split('/\r\n|\r|\n/',(string)$content['body']) ?: [];
    $index = 0;
    foreach ($lines as &$line) {
        if ($line === '' || str_starts_with($line,'#')) continue;
        if (!isset($segments[$index])) return ['ok'=>false,'error'=>'manifest_segment_registry_incomplete'];
        $line = sf_mp_delivery_url('segment',(string)$session['session_token'],(int)$segments[$index]['id'],$expiresAt);
        $index++;
    }
    unset($line);
    return ['ok'=>true,'body'=>implode("\n",$lines)."\n",'kind'=>'variant','segments'=>$index];
}
