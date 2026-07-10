<?php

declare(strict_types=1);

function sf_mp_s3_put_file(string $source, string $storageKey, string $mime): array {
    if (!is_file($source) || !sf_mp_s3_ready()) return ['ok'=>false,'error'=>'s3_not_ready'];
    $url = sf_mp_s3_presign('PUT', $storageKey, 900);
    if ($url === '' || !function_exists('curl_init')) return ['ok'=>false,'error'=>'s3_transport_unavailable'];
    $handle = fopen($source, 'rb');
    if (!$handle) return ['ok'=>false,'error'=>'source_open_failed'];
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_UPLOAD=>true,CURLOPT_INFILE=>$handle,CURLOPT_INFILESIZE=>(int)filesize($source),CURLOPT_HTTPHEADER=>['Content-Type: '.$mime],CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>3600,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
    $response = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    fclose($handle);
    if ($response === false || $status < 200 || $status >= 300) return ['ok'=>false,'error'=>'s3_upload_failed','status'=>$status,'detail'=>$error];
    return ['ok'=>true,'size_bytes'=>(int)filesize($source),'checksum_sha256'=>sf_mp_hash_file($source)];
}

function sf_mp_store_assembled_upload(array $session, string $assembled, string $mime, string $checksum): array {
    $provider = sf_mp_provider_by_id((int)$session['provider_id']);
    if (!$provider) return ['ok'=>false,'error'=>'provider_not_found'];
    $key = sf_mp_storage_key((string)$session['entity_type'], (int)$session['entity_id'], (string)$session['target_role'], (string)$session['extension']);
    $stored = ($provider['driver'] ?? 'local') === 's3'
        ? sf_mp_s3_put_file($assembled, $key, $mime)
        : sf_mp_copy_local($assembled, $key);
    if (empty($stored['ok'])) return $stored;
    return array_merge($stored, ['storage_key'=>$key,'provider'=>$provider,'checksum_sha256'=>$checksum]);
}

function sf_mp_complete_upload_session(string $token): array {
    $pdo = sf_db();
    if (!$pdo) return ['ok'=>false,'error'=>'database_unavailable'];
    try {
        $pdo->beginTransaction();
        $session = sf_mp_upload_session($token, true);
        if (!$session) throw new RuntimeException('upload_session_not_found');
        if (!in_array($session['status'], ['created','uploading'], true)) throw new RuntimeException('upload_session_not_completable');
        if (strtotime((string)$session['expires_at']) < time()) throw new RuntimeException('upload_session_expired');
        $stmt = $pdo->prepare('SELECT * FROM media_upload_chunks WHERE upload_session_id=? ORDER BY chunk_number ASC FOR UPDATE');
        $stmt->execute([(int)$session['id']]);
        $chunks = $stmt->fetchAll() ?: [];
        if (count($chunks) !== (int)$session['expected_chunks']) throw new RuntimeException('upload_chunks_incomplete');
        $stmt = $pdo->prepare("UPDATE media_upload_sessions SET status='assembling' WHERE id=?");
        $stmt->execute([(int)$session['id']]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return ['ok'=>false,'error'=>$e->getMessage()];
    }

    $root = sf_mp_upload_session_root($session);
    if (!$root) return ['ok'=>false,'error'=>'staging_path_invalid'];
    $assembled = $root . '/assembled-' . sf_mp_random_key(8) . '.' . $session['extension'];
    $output = fopen($assembled, 'wb');
    if (!$output) return ['ok'=>false,'error'=>'assembly_open_failed'];
    $written = 0;
    foreach ($chunks as $chunk) {
        $path = sf_mp_staging_root() . '/' . sf_mp_safe_key((string)$chunk['staging_path']);
        $input = fopen($path, 'rb');
        if (!$input) { fclose($output); @unlink($assembled); return ['ok'=>false,'error'=>'chunk_missing']; }
        $copied = stream_copy_to_stream($input, $output);
        fclose($input);
        if ($copied === false) { fclose($output); @unlink($assembled); return ['ok'=>false,'error'=>'assembly_failed']; }
        $written += (int)$copied;
    }
    fflush($output);
    fclose($output);
    if ($written !== (int)$session['expected_size_bytes']) { @unlink($assembled); return ['ok'=>false,'error'=>'assembled_size_mismatch']; }
    $checksum = sf_mp_hash_file($assembled);
    if (!empty($session['expected_checksum_sha256']) && !hash_equals((string)$session['expected_checksum_sha256'], $checksum)) { @unlink($assembled); return ['ok'=>false,'error'=>'assembled_checksum_mismatch']; }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = strtolower((string)$finfo->file($assembled));
    $kind = sf_mp_media_kind((string)$session['extension'], $mime);
    if (!$kind) { @unlink($assembled); return ['ok'=>false,'error'=>'assembled_type_rejected','detected_mime'=>$mime]; }

    try {
        $dup = $pdo->prepare("SELECT * FROM media_objects WHERE checksum_sha256=? AND size_bytes=? AND entity_type=? AND entity_id=? AND role=? AND status='ready' ORDER BY id DESC LIMIT 1");
        $dup->execute([$checksum,$written,$session['entity_type'],(int)$session['entity_id'],$session['target_role']]);
        $existing = $dup->fetch();
        if ($existing) {
            $stmt = $pdo->prepare("UPDATE media_upload_sessions SET status='completed',completed_at=NOW() WHERE id=?");
            $stmt->execute([(int)$session['id']]);
            sf_mp_cleanup_upload_files($session, $chunks, $assembled);
            return ['ok'=>true,'duplicate'=>true,'object_id'=>(int)$existing['id'],'checksum_sha256'=>$checksum];
        }
    } catch (Throwable $e) {
        error_log('Stonefellow media duplicate lookup failed: ' . $e->getMessage());
    }

    $stored = sf_mp_store_assembled_upload($session, $assembled, $mime, $checksum);
    if (empty($stored['ok'])) { @unlink($assembled); return $stored; }
    $object = sf_mp_create_object([
        'provider_id'=>(int)$session['provider_id'],
        'entity_type'=>$session['entity_type'],
        'entity_id'=>(int)$session['entity_id'],
        'role'=>$session['target_role'],
        'storage_key'=>$stored['storage_key'],
        'original_filename'=>$session['original_filename'],
        'extension'=>$session['extension'],
        'mime_type'=>$mime,
        'size_bytes'=>$written,
        'checksum_sha256'=>$checksum,
        'visibility'=>'private',
        'status'=>'quarantined',
        'is_primary'=>1,
        'metadata'=>['kind'=>$kind,'upload_session_id'=>(int)$session['id']],
    ]);
    if (empty($object['ok'])) { @unlink($assembled); return $object; }
    sf_mp_set_primary((int)$object['id']);
    $queued = sf_mp_enqueue_default_jobs((int)$object['id'], $kind, (string)$session['target_role']);
    try {
        $stmt = $pdo->prepare("UPDATE media_upload_sessions SET status='completed',completed_at=NOW() WHERE id=?");
        $stmt->execute([(int)$session['id']]);
    } catch (Throwable $e) {
        error_log('Stonefellow upload completion update failed: ' . $e->getMessage());
    }
    sf_mp_cleanup_upload_files($session, $chunks, $assembled);
    return ['ok'=>true,'object_id'=>(int)$object['id'],'checksum_sha256'=>$checksum,'detected_mime'=>$mime,'queued_jobs'=>$queued];
}

function sf_mp_cleanup_upload_files(array $session, array $chunks, string $assembled = ''): void {
    foreach ($chunks as $chunk) {
        $key = sf_mp_safe_key((string)($chunk['staging_path'] ?? ''));
        if ($key !== '') @unlink(sf_mp_staging_root() . '/' . $key);
    }
    if ($assembled !== '') @unlink($assembled);
    $root = sf_mp_upload_session_root($session);
    if ($root && is_dir($root)) @rmdir($root);
}

function sf_mp_expire_upload_sessions(int $limit = 500): int {
    $pdo = sf_db();
    if (!$pdo || !sf_mp_table_exists('media_upload_sessions')) return 0;
    $limit = max(1, min(5000, $limit));
    try {
        $rows = $pdo->query("SELECT * FROM media_upload_sessions WHERE status IN ('created','uploading','assembling') AND expires_at<NOW() ORDER BY id ASC LIMIT {$limit}")->fetchAll() ?: [];
        foreach ($rows as $row) {
            $chunksStmt = $pdo->prepare('SELECT * FROM media_upload_chunks WHERE upload_session_id=?');
            $chunksStmt->execute([(int)$row['id']]);
            sf_mp_cleanup_upload_files($row, $chunksStmt->fetchAll() ?: []);
            $stmt = $pdo->prepare("UPDATE media_upload_sessions SET status='expired',error_message='Upload session expired before completion.' WHERE id=?");
            $stmt->execute([(int)$row['id']]);
        }
        return count($rows);
    } catch (Throwable $e) {
        return 0;
    }
}
