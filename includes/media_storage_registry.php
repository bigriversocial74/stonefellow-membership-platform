<?php

declare(strict_types=1);

function sf_mp_default_provider(): ?array {
    $pdo = sf_db();
    if (!$pdo || !sf_mp_table_exists('media_storage_providers')) return null;
    $driver = sf_mp_driver();
    $mode = sf_mp_mode();
    try {
        $stmt = $pdo->prepare("SELECT * FROM media_storage_providers WHERE driver=? AND mode=? AND status='active' ORDER BY id ASC LIMIT 1");
        $stmt->execute([$driver, $mode]);
        $row = $stmt->fetch();
        if ($row) return $row;
        $stmt = $pdo->prepare("SELECT * FROM media_storage_providers WHERE provider_key='local_private' AND mode='test' LIMIT 1");
        $stmt->execute();
        return $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        error_log('Stonefellow media provider lookup failed: ' . $e->getMessage());
        return null;
    }
}

function sf_mp_provider_by_id(int $id): ?array {
    $pdo = sf_db();
    if (!$pdo || $id <= 0 || !sf_mp_table_exists('media_storage_providers')) return null;
    try {
        $stmt = $pdo->prepare('SELECT * FROM media_storage_providers WHERE id=? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function sf_mp_object_by_id(int $id): ?array {
    $pdo = sf_db();
    if (!$pdo || $id <= 0 || !sf_mp_table_exists('media_objects')) return null;
    try {
        $stmt = $pdo->prepare('SELECT mo.*,msp.driver,msp.provider_key,msp.mode,msp.bucket_name,msp.region_name,msp.endpoint_url,msp.public_base_url FROM media_objects mo JOIN media_storage_providers msp ON msp.id=mo.provider_id WHERE mo.id=? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function sf_mp_ready_object(string $entityType, int $entityId, string $role, bool $primaryOnly = true): ?array {
    $pdo = sf_db();
    if (!$pdo || $entityId <= 0 || !sf_mp_table_exists('media_objects')) return null;
    $sql = "SELECT mo.*,msp.driver,msp.provider_key,msp.mode,msp.bucket_name,msp.region_name,msp.endpoint_url,msp.public_base_url FROM media_objects mo JOIN media_storage_providers msp ON msp.id=mo.provider_id WHERE mo.entity_type=? AND mo.entity_id=? AND mo.role=? AND mo.status='ready'";
    if ($primaryOnly) $sql .= ' AND mo.is_primary=1';
    $sql .= ' ORDER BY mo.is_primary DESC,mo.id DESC LIMIT 1';
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$entityType, $entityId, $role]);
        $row = $stmt->fetch();
        if ($row || !$primaryOnly) return $row ?: null;
        return sf_mp_ready_object($entityType, $entityId, $role, false);
    } catch (Throwable $e) {
        return null;
    }
}

function sf_mp_create_object(array $data): array {
    $pdo = sf_db();
    if (!$pdo || !sf_mp_table_exists('media_objects')) return ['ok'=>false,'error'=>'media_schema_missing'];
    $providerId = (int)($data['provider_id'] ?? 0);
    $storageKey = sf_mp_safe_key((string)($data['storage_key'] ?? ''));
    if ($providerId <= 0 || $storageKey === '') return ['ok'=>false,'error'=>'invalid_media_object'];
    $objectKey = sf_mp_random_key(24);
    try {
        $stmt = $pdo->prepare('INSERT INTO media_objects (object_key,provider_id,entity_type,entity_id,role,parent_object_id,storage_key,original_filename,extension,mime_type,size_bytes,checksum_sha256,visibility,status,is_primary,metadata_json,ready_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $objectKey,$providerId,(string)$data['entity_type'],(int)$data['entity_id'],(string)$data['role'],
            !empty($data['parent_object_id'])?(int)$data['parent_object_id']:null,$storageKey,$data['original_filename']??null,
            $data['extension']??null,$data['mime_type']??'application/octet-stream',(int)($data['size_bytes']??0),
            $data['checksum_sha256']??null,$data['visibility']??'private',$data['status']??'quarantined',
            !empty($data['is_primary'])?1:0,isset($data['metadata'])?json_encode($data['metadata'],JSON_UNESCAPED_SLASHES):null,
            ($data['status']??'')==='ready'?gmdate('Y-m-d H:i:s'):null,
        ]);
        return ['ok'=>true,'id'=>(int)$pdo->lastInsertId(),'object_key'=>$objectKey];
    } catch (Throwable $e) {
        error_log('Stonefellow media object creation failed: ' . $e->getMessage());
        return ['ok'=>false,'error'=>'media_object_create_failed'];
    }
}

function sf_mp_set_primary(int $objectId): bool {
    $pdo = sf_db();
    $object = sf_mp_object_by_id($objectId);
    if (!$pdo || !$object) return false;
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE media_objects SET is_primary=0 WHERE entity_type=? AND entity_id=? AND role=?');
        $stmt->execute([$object['entity_type'],$object['entity_id'],$object['role']]);
        $stmt = $pdo->prepare('UPDATE media_objects SET is_primary=1 WHERE id=?');
        $stmt->execute([$objectId]);
        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return false;
    }
}

function sf_mp_mark_object(int $objectId, string $status, array $metadata = []): bool {
    $allowed = ['quarantined','ingesting','processing','ready','failed','deleted'];
    if (!in_array($status, $allowed, true)) return false;
    $pdo = sf_db();
    if (!$pdo) return false;
    try {
        $stmt = $pdo->prepare('UPDATE media_objects SET status=?,metadata_json=COALESCE(?,metadata_json),ready_at=IF(?="ready",NOW(),ready_at),deleted_at=IF(?="deleted",NOW(),deleted_at) WHERE id=?');
        return $stmt->execute([$status,$metadata?json_encode($metadata,JSON_UNESCAPED_SLASHES):null,$status,$status,$objectId]);
    } catch (Throwable $e) {
        return false;
    }
}

function sf_mp_hash_file(string $path): string {
    if (!is_file($path)) return '';
    $hash = hash_file('sha256', $path);
    return is_string($hash) && preg_match('/^[a-f0-9]{64}$/', $hash) ? $hash : '';
}

function sf_mp_copy_local(string $source, string $storageKey): array {
    if (!is_file($source) || !is_readable($source)) return ['ok'=>false,'error'=>'source_missing'];
    $destination = sf_mp_local_path($storageKey);
    if (!$destination) return ['ok'=>false,'error'=>'storage_path_invalid'];
    $tmp = $destination . '.partial-' . sf_mp_random_key(8);
    if (!copy($source, $tmp)) return ['ok'=>false,'error'=>'storage_copy_failed'];
    @chmod($tmp, 0640);
    if (!rename($tmp, $destination)) { @unlink($tmp); return ['ok'=>false,'error'=>'storage_finalize_failed']; }
    return ['ok'=>true,'path'=>$destination,'size_bytes'=>(int)filesize($destination),'checksum_sha256'=>sf_mp_hash_file($destination)];
}
