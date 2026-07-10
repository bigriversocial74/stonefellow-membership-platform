<?php

declare(strict_types=1);

function sf_mp_upload_session(string $token, bool $lock = false): ?array {
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) return null;
    $pdo = sf_db();
    if (!$pdo) return null;
    try {
        $sql = 'SELECT * FROM media_upload_sessions WHERE session_token=? LIMIT 1' . ($lock ? ' FOR UPDATE' : '');
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function sf_mp_upload_session_root(array $session): ?string {
    $relative = sf_mp_safe_key((string)($session['staging_path'] ?? ''));
    if ($relative === '') return null;
    $root = rtrim(sf_mp_staging_root(), '/\\');
    $path = $root . '/' . $relative;
    $normalizedRoot = rtrim(str_replace('\\','/',$root), '/') . '/';
    $normalizedPath = str_replace('\\','/',$path);
    return str_starts_with($normalizedPath, $normalizedRoot) ? $path : null;
}

function sf_mp_receive_upload_chunk(string $token, int $chunkNumber, string $body, string $declaredChecksum = ''): array {
    $pdo = sf_db();
    $session = sf_mp_upload_session($token);
    if (!$pdo || !$session) return ['ok'=>false,'error'=>'upload_session_not_found'];
    if (!in_array($session['status'], ['created','uploading'], true) || strtotime((string)$session['expires_at']) < time()) return ['ok'=>false,'error'=>'upload_session_unavailable'];
    $expectedChunks = (int)$session['expected_chunks'];
    if ($chunkNumber < 0 || $chunkNumber >= $expectedChunks) return ['ok'=>false,'error'=>'invalid_chunk_number'];
    $size = strlen($body);
    $max = (int)$session['chunk_size_bytes'];
    $isLast = $chunkNumber === $expectedChunks - 1;
    if ($size < 1 || $size > $max || (!$isLast && $size !== $max)) return ['ok'=>false,'error'=>'invalid_chunk_size'];
    $checksum = hash('sha256', $body);
    if ($declaredChecksum !== '' && (!preg_match('/^[a-f0-9]{64}$/', $declaredChecksum) || !hash_equals($declaredChecksum, $checksum))) return ['ok'=>false,'error'=>'chunk_checksum_mismatch'];
    $root = sf_mp_upload_session_root($session);
    if (!$root) return ['ok'=>false,'error'=>'staging_path_invalid'];
    if (!is_dir($root) && !mkdir($root, 0750, true) && !is_dir($root)) return ['ok'=>false,'error'=>'staging_storage_unavailable'];
    $path = $root . '/chunk-' . str_pad((string)$chunkNumber, 8, '0', STR_PAD_LEFT) . '.part';
    $tmp = $path . '.tmp-' . sf_mp_random_key(6);
    if (file_put_contents($tmp, $body, LOCK_EX) !== $size || !rename($tmp, $path)) { @unlink($tmp); return ['ok'=>false,'error'=>'chunk_write_failed']; }
    @chmod($path, 0640);
    $relative = sf_mp_safe_key((string)$session['staging_path']) . '/' . basename($path);
    try {
        $pdo->beginTransaction();
        $existing = $pdo->prepare('SELECT size_bytes FROM media_upload_chunks WHERE upload_session_id=? AND chunk_number=? FOR UPDATE');
        $existing->execute([(int)$session['id'],$chunkNumber]);
        $oldSize = (int)($existing->fetchColumn() ?: 0);
        $stmt = $pdo->prepare('INSERT INTO media_upload_chunks (upload_session_id,chunk_number,size_bytes,checksum_sha256,staging_path) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE size_bytes=VALUES(size_bytes),checksum_sha256=VALUES(checksum_sha256),staging_path=VALUES(staging_path)');
        $stmt->execute([(int)$session['id'],$chunkNumber,$size,$checksum,$relative]);
        $stmt = $pdo->prepare("UPDATE media_upload_sessions SET status='uploading',received_size_bytes=received_size_bytes-?+?,received_chunks=(SELECT COUNT(*) FROM media_upload_chunks WHERE upload_session_id=?) WHERE id=?");
        $stmt->execute([$oldSize,$size,(int)$session['id'],(int)$session['id']]);
        $pdo->commit();
        return ['ok'=>true,'chunk_number'=>$chunkNumber,'size_bytes'=>$size,'checksum_sha256'=>$checksum];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Stonefellow media chunk registration failed: ' . $e->getMessage());
        return ['ok'=>false,'error'=>'chunk_register_failed'];
    }
}
