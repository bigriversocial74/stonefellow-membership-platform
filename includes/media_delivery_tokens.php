<?php

declare(strict_types=1);

function sf_mp_delivery_secret(): string {
    return sf_mp_env('SF_MEDIA_DELIVERY_SESSION_SECRET', sf_mp_env('SF_MEDIA_SIGNING_KEY', sf_mp_env('SF_HASH_SALT')));
}

function sf_mp_request_fingerprint(): array {
    $salt = sf_mp_env('SF_HASH_SALT', sf_mp_delivery_secret());
    $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    $ua = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
    return [
        'ip_hash' => $ip === '' ? null : hash_hmac('sha256', $ip, $salt),
        'user_agent_hash' => $ua === '' ? null : hash_hmac('sha256', $ua, $salt),
    ];
}

function sf_mp_create_delivery_session(array $object, ?int $userId, int $ttl = 1800): array {
    $pdo = sf_db();
    if (!$pdo || !sf_mp_table_exists('media_delivery_sessions')) return ['ok'=>false,'error'=>'media_schema_missing'];
    $ttl = max(60, min(7200, $ttl));
    $token = sf_mp_random_key(32);
    $fingerprint = sf_mp_request_fingerprint();
    $manifestId = ($object['role'] ?? '') === 'manifest' ? (int)$object['id'] : null;
    try {
        $expiresAt = gmdate('Y-m-d H:i:s', time() + $ttl);
        $stmt = $pdo->prepare('INSERT INTO media_delivery_sessions (session_token,user_id,object_id,manifest_object_id,ip_hash,user_agent_hash,status,expires_at,last_accessed_at) VALUES (?,?,?,?,?,?,"active",?,NOW())');
        $stmt->execute([$token,$userId,(int)$object['id'],$manifestId,$fingerprint['ip_hash'],$fingerprint['user_agent_hash'],$expiresAt]);
        return ['ok'=>true,'session_token'=>$token,'expires_in'=>$ttl,'expires_at'=>time()+$ttl];
    } catch (Throwable $e) {
        error_log('Stonefellow media delivery session creation failed: ' . $e->getMessage());
        return ['ok'=>false,'error'=>'delivery_session_create_failed'];
    }
}

function sf_mp_delivery_session(string $token, bool $touch = false): ?array {
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) return null;
    $pdo = sf_db();
    if (!$pdo || !sf_mp_table_exists('media_delivery_sessions')) return null;
    try {
        $stmt = $pdo->prepare("SELECT * FROM media_delivery_sessions WHERE session_token=? AND status='active' AND expires_at>NOW() LIMIT 1");
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        if (!$row) return null;
        $currentUser = sf_current_user_id();
        if ((int)($row['user_id'] ?? 0) > 0 && (int)$row['user_id'] !== (int)$currentUser) return null;
        if (sf_mp_env_bool('SF_MEDIA_BIND_SESSION_FINGERPRINT', true)) {
            $fp = sf_mp_request_fingerprint();
            if (!empty($row['ip_hash']) && !empty($fp['ip_hash']) && !hash_equals((string)$row['ip_hash'], (string)$fp['ip_hash'])) return null;
            if (!empty($row['user_agent_hash']) && !empty($fp['user_agent_hash']) && !hash_equals((string)$row['user_agent_hash'], (string)$fp['user_agent_hash'])) return null;
        }
        if ($touch) {
            $pdo->prepare('UPDATE media_delivery_sessions SET last_accessed_at=NOW() WHERE id=?')->execute([(int)$row['id']]);
        }
        return $row;
    } catch (Throwable $e) {
        return null;
    }
}

function sf_mp_delivery_signature(string $sessionToken, int $objectId, int $expiresAt, string $kind): string {
    $secret = sf_mp_delivery_secret();
    if (strlen($secret) < 32) return '';
    return hash_hmac('sha256', $kind . '|' . $sessionToken . '|' . $objectId . '|' . $expiresAt, $secret);
}

function sf_mp_delivery_url(string $kind, string $sessionToken, int $objectId, int $expiresAt): string {
    $sig = sf_mp_delivery_signature($sessionToken, $objectId, $expiresAt, $kind);
    if ($sig === '') return '';
    $path = $kind === 'manifest' ? 'media-manifest.php' : 'media-segment.php';
    return sf_url($path . '?' . http_build_query(['s'=>$sessionToken,'oid'=>$objectId,'exp'=>$expiresAt,'sig'=>$sig]));
}

function sf_mp_validate_delivery_request(string $kind, array $request): array {
    $sessionToken = trim((string)($request['s'] ?? ''));
    $objectId = (int)($request['oid'] ?? 0);
    $expiresAt = (int)($request['exp'] ?? 0);
    $signature = strtolower(trim((string)($request['sig'] ?? '')));
    if (!preg_match('/^[a-f0-9]{64}$/', $sessionToken) || $objectId <= 0 || $expiresAt < time() || !preg_match('/^[a-f0-9]{64}$/', $signature)) return ['ok'=>false,'error'=>'invalid_delivery_token'];
    $expected = sf_mp_delivery_signature($sessionToken,$objectId,$expiresAt,$kind);
    if ($expected === '' || !hash_equals($expected,$signature)) return ['ok'=>false,'error'=>'invalid_delivery_signature'];
    $session = sf_mp_delivery_session($sessionToken, true);
    if (!$session) return ['ok'=>false,'error'=>'delivery_session_expired'];
    $object = sf_mp_object_by_id($objectId);
    if (!$object || ($object['status'] ?? '') !== 'ready') return ['ok'=>false,'error'=>'media_object_unavailable'];
    $masterId = (int)($session['manifest_object_id'] ?? 0);
    if ($masterId > 0 && $objectId !== $masterId && (int)($object['parent_object_id'] ?? 0) !== $masterId) {
        $parent = sf_mp_object_by_id((int)($object['parent_object_id'] ?? 0));
        if (!$parent || (int)($parent['parent_object_id'] ?? 0) !== $masterId) return ['ok'=>false,'error'=>'object_outside_delivery_session'];
    }
    return ['ok'=>true,'session'=>$session,'object'=>$object];
}
