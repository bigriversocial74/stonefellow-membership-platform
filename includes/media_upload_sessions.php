<?php

declare(strict_types=1);

function sf_mp_upload_chunk_size(): int {
    return sf_mp_env_int('SF_MEDIA_UPLOAD_CHUNK_BYTES', 8388608, 1048576, 67108864);
}

function sf_mp_upload_session_ttl(): int {
    return sf_mp_env_int('SF_MEDIA_UPLOAD_SESSION_TTL_SECONDS', 86400, 900, 604800);
}

function sf_mp_upload_entity_types(): array {
    return ['song','video','episode','album','asset','series'];
}

function sf_mp_upload_target_roles(): array {
    return ['original','poster','thumbnail','caption'];
}

function sf_mp_create_upload_session(array $input, ?int $userId): array {
    $pdo = sf_db();
    if (!$pdo || !sf_mp_table_exists('media_upload_sessions')) return ['ok'=>false,'error'=>'media_schema_missing'];
    $entityType = strtolower(trim((string)($input['entity_type'] ?? '')));
    $entityId = (int)($input['entity_id'] ?? 0);
    $role = strtolower(trim((string)($input['target_role'] ?? 'original')));
    $filename = basename(trim((string)($input['filename'] ?? '')));
    $mime = strtolower(trim((string)($input['mime_type'] ?? 'application/octet-stream')));
    $size = (int)($input['size_bytes'] ?? 0);
    $checksum = strtolower(trim((string)($input['checksum_sha256'] ?? '')));
    $extension = sf_mp_safe_extension($filename);
    $kind = sf_mp_media_kind($extension, $mime);
    if (!in_array($entityType, sf_mp_upload_entity_types(), true) || $entityId <= 0) return ['ok'=>false,'error'=>'invalid_media_entity'];
    if (!in_array($role, sf_mp_upload_target_roles(), true)) return ['ok'=>false,'error'=>'invalid_target_role'];
    if ($filename === '' || $extension === '' || !$kind) return ['ok'=>false,'error'=>'unsupported_media_type'];
    $rules = sf_mp_quarantine_rules()[$kind];
    if ($size < 1 || $size > (int)$rules['max_bytes']) return ['ok'=>false,'error'=>'media_size_not_allowed','max_bytes'=>(int)$rules['max_bytes']];
    if ($checksum !== '' && !preg_match('/^[a-f0-9]{64}$/', $checksum)) return ['ok'=>false,'error'=>'invalid_checksum'];
    $provider = sf_mp_default_provider();
    if (!$provider) return ['ok'=>false,'error'=>'storage_provider_unavailable'];
    $token = sf_mp_random_key(32);
    $chunkSize = sf_mp_upload_chunk_size();
    $expectedChunks = (int)ceil($size / $chunkSize);
    $stagingRelative = 'sessions/' . gmdate('Y/m/d') . '/' . $token;
    $stagingPath = sf_mp_staging_root() . '/' . $stagingRelative;
    if (!is_dir($stagingPath) && !mkdir($stagingPath, 0750, true) && !is_dir($stagingPath)) return ['ok'=>false,'error'=>'staging_storage_unavailable'];
    @chmod($stagingPath, 0750);
    try {
        $expiresAt = gmdate('Y-m-d H:i:s', time() + sf_mp_upload_session_ttl());
        $stmt = $pdo->prepare('INSERT INTO media_upload_sessions (session_token,created_by_user_id,provider_id,entity_type,entity_id,target_role,original_filename,extension,declared_mime_type,expected_size_bytes,expected_checksum_sha256,chunk_size_bytes,expected_chunks,staging_path,status,expires_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$token,$userId,(int)$provider['id'],$entityType,$entityId,$role,$filename,$extension,$mime,$size,$checksum!==''?$checksum:null,$chunkSize,$expectedChunks,$stagingRelative,'created',$expiresAt]);
        return ['ok'=>true,'session_token'=>$token,'chunk_size_bytes'=>$chunkSize,'expected_chunks'=>$expectedChunks,'expires_in'=>sf_mp_upload_session_ttl(),'kind'=>$kind];
    } catch (Throwable $e) {
        error_log('Stonefellow media upload session creation failed: ' . $e->getMessage());
        return ['ok'=>false,'error'=>'upload_session_create_failed'];
    }
}
