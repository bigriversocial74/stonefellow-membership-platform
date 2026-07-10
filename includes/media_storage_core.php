<?php

declare(strict_types=1);

function sf_mp_env(string $name, string $default = ''): string {
    $value = getenv($name);
    return $value === false || $value === '' ? $default : trim((string)$value);
}

function sf_mp_env_int(string $name, int $default, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): int {
    $raw = sf_mp_env($name, (string)$default);
    $value = filter_var($raw, FILTER_VALIDATE_INT);
    if ($value === false) $value = $default;
    return max($min, min($max, (int)$value));
}

function sf_mp_env_bool(string $name, bool $default = false): bool {
    $raw = sf_mp_env($name, $default ? '1' : '0');
    return in_array(strtolower($raw), ['1','true','yes','on'], true);
}

function sf_mp_table_exists(string $table): bool {
    $pdo = sf_db();
    if (!$pdo || !preg_match('/^[a-z0-9_]+$/i', $table)) return false;
    try {
        $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$table]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('Stonefellow media table check failed: ' . $e->getMessage());
        return false;
    }
}

function sf_mp_random_key(int $bytes = 24): string {
    return bin2hex(random_bytes(max(16, min(32, $bytes))));
}

function sf_mp_provider_registry(): array {
    return [
        'local_private' => [
            'driver' => 'local',
            'implemented' => true,
            'label' => 'Local Private Storage',
            'supports_direct_upload' => false,
            'supports_cdn' => false,
        ],
        's3_compatible' => [
            'driver' => 's3',
            'implemented' => true,
            'label' => 'S3-Compatible Object Storage',
            'supports_direct_upload' => true,
            'supports_cdn' => true,
        ],
    ];
}

function sf_mp_mode(): string {
    return strtolower(sf_mp_env('SF_MEDIA_STORAGE_MODE', sf_mp_env('SF_PAYMENT_MODE', 'test'))) === 'live' ? 'live' : 'test';
}

function sf_mp_driver(): string {
    $driver = strtolower(sf_mp_env('SF_MEDIA_STORAGE_DRIVER', 'local'));
    return in_array($driver, ['local','s3'], true) ? $driver : 'local';
}

function sf_mp_local_root(): string {
    $configured = sf_mp_env('SF_MEDIA_LOCAL_ROOT', 'storage/private_media_v2');
    $base = realpath(dirname(__DIR__)) ?: dirname(__DIR__);
    if (str_starts_with($configured, '/') || preg_match('/^[A-Za-z]:[\\\/]/', $configured)) {
        return rtrim($configured, '/\\');
    }
    return $base . '/' . trim(str_replace('\\', '/', $configured), '/');
}

function sf_mp_staging_root(): string {
    $configured = sf_mp_env('SF_MEDIA_STAGING_ROOT', 'storage/media_staging');
    $base = realpath(dirname(__DIR__)) ?: dirname(__DIR__);
    if (str_starts_with($configured, '/') || preg_match('/^[A-Za-z]:[\\\/]/', $configured)) {
        return rtrim($configured, '/\\');
    }
    return $base . '/' . trim(str_replace('\\', '/', $configured), '/');
}

function sf_mp_safe_key(string $key): string {
    $key = trim(str_replace('\\', '/', $key));
    $key = preg_replace('~/+~', '/', $key) ?: '';
    $key = ltrim($key, '/');
    if ($key === '' || strlen($key) > 700 || str_contains($key, '..') || preg_match('/[\x00-\x1F\x7F]/', $key)) return '';
    if (!preg_match('~^[A-Za-z0-9._/\-]+$~', $key)) return '';
    return $key;
}

function sf_mp_safe_extension(string $filename): string {
    $ext = strtolower((string)pathinfo($filename, PATHINFO_EXTENSION));
    return preg_match('/^[a-z0-9]{1,12}$/', $ext) ? $ext : '';
}

function sf_mp_storage_key(string $entityType, int $entityId, string $role, string $extension): string {
    $entityType = preg_match('/^[a-z_]+$/', $entityType) ? $entityType : 'asset';
    $role = preg_match('/^[a-z_]+$/', $role) ? $role : 'original';
    $extension = preg_match('/^[a-z0-9]{1,12}$/', $extension) ? $extension : 'bin';
    return sprintf('%s/%d/%s/%s/%s.%s', $entityType, max(0, $entityId), $role, gmdate('Y/m'), sf_mp_random_key(20), $extension);
}

function sf_mp_local_path(string $storageKey): ?string {
    $key = sf_mp_safe_key($storageKey);
    if ($key === '') return null;
    $root = sf_mp_local_root();
    $path = $root . '/' . $key;
    $parent = dirname($path);
    if (!is_dir($parent) && !mkdir($parent, 0750, true) && !is_dir($parent)) return null;
    $normalizedRoot = rtrim(str_replace('\\', '/', $root), '/') . '/';
    $normalizedPath = str_replace('\\', '/', $path);
    return str_starts_with($normalizedPath, $normalizedRoot) ? $path : null;
}

function sf_mp_quarantine_rules(): array {
    return [
        'audio' => [
            'extensions' => ['wav','mp3','m4a','aac','flac','ogg'],
            'mimes' => ['audio/wav','audio/x-wav','audio/mpeg','audio/mp4','video/mp4','audio/aac','audio/flac','audio/x-flac','audio/ogg','application/octet-stream'],
            'max_bytes' => sf_mp_env_int('SF_MEDIA_MAX_AUDIO_BYTES', 5368709120, 1048576, 21474836480),
        ],
        'video' => [
            'extensions' => ['mp4','mov','m4v','webm','mkv'],
            'mimes' => ['video/mp4','video/quicktime','video/webm','video/x-matroska','application/octet-stream'],
            'max_bytes' => sf_mp_env_int('SF_MEDIA_MAX_VIDEO_BYTES', 21474836480, 1048576, 107374182400),
        ],
        'image' => [
            'extensions' => ['jpg','jpeg','png','webp'],
            'mimes' => ['image/jpeg','image/png','image/webp'],
            'max_bytes' => sf_mp_env_int('SF_MEDIA_MAX_IMAGE_BYTES', 52428800, 1024, 536870912),
        ],
        'caption' => [
            'extensions' => ['vtt','srt'],
            'mimes' => ['text/vtt','application/x-subrip','text/plain'],
            'max_bytes' => sf_mp_env_int('SF_MEDIA_MAX_CAPTION_BYTES', 5242880, 1024, 52428800),
        ],
    ];
}

function sf_mp_media_kind(string $extension, string $mime): ?string {
    $extension = strtolower($extension);
    $mime = strtolower(trim($mime));
    foreach (sf_mp_quarantine_rules() as $kind => $rule) {
        if (in_array($extension, $rule['extensions'], true) && in_array($mime, $rule['mimes'], true)) return $kind;
    }
    return null;
}
