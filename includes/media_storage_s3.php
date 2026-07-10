<?php

declare(strict_types=1);

function sf_mp_s3_config(): array {
    return [
        'access_key' => sf_mp_env('SF_MEDIA_S3_ACCESS_KEY'),
        'secret_key' => sf_mp_env('SF_MEDIA_S3_SECRET_KEY'),
        'session_token' => sf_mp_env('SF_MEDIA_S3_SESSION_TOKEN'),
        'bucket' => sf_mp_env('SF_MEDIA_S3_BUCKET'),
        'region' => sf_mp_env('SF_MEDIA_S3_REGION', 'us-east-1'),
        'endpoint' => rtrim(sf_mp_env('SF_MEDIA_S3_ENDPOINT', 'https://s3.amazonaws.com'), '/'),
        'path_style' => sf_mp_env_bool('SF_MEDIA_S3_PATH_STYLE', false),
        'cdn_base' => rtrim(sf_mp_env('SF_MEDIA_CDN_BASE_URL'), '/'),
    ];
}

function sf_mp_s3_ready(): bool {
    $c = sf_mp_s3_config();
    return $c['access_key'] !== '' && strlen($c['secret_key']) >= 16 && $c['bucket'] !== '' && preg_match('/^[a-z0-9][a-z0-9.-]{1,61}[a-z0-9]$/', $c['bucket']);
}

function sf_mp_aws_encode(string $value, bool $path = false): string {
    $encoded = rawurlencode($value);
    return $path ? str_replace('%2F', '/', $encoded) : $encoded;
}

function sf_mp_s3_endpoint_parts(string $key): array {
    $c = sf_mp_s3_config();
    $parsed = parse_url($c['endpoint']);
    $scheme = strtolower((string)($parsed['scheme'] ?? 'https')) === 'http' ? 'http' : 'https';
    $host = strtolower((string)($parsed['host'] ?? 's3.amazonaws.com'));
    $port = isset($parsed['port']) ? ':' . (int)$parsed['port'] : '';
    $basePath = trim((string)($parsed['path'] ?? ''), '/');
    $safeKey = sf_mp_safe_key($key);
    if ($c['path_style']) {
        $path = '/' . trim(($basePath !== '' ? $basePath . '/' : '') . $c['bucket'] . '/' . $safeKey, '/');
    } else {
        $host = $c['bucket'] . '.' . $host;
        $path = '/' . trim(($basePath !== '' ? $basePath . '/' : '') . $safeKey, '/');
    }
    return ['scheme'=>$scheme,'host'=>$host.$port,'path'=>$path,'config'=>$c];
}

function sf_mp_s3_presign(string $method, string $key, int $ttl = 900, array $extraQuery = []): string {
    if (!sf_mp_s3_ready()) return '';
    $parts = sf_mp_s3_endpoint_parts($key);
    $c = $parts['config'];
    $now = time();
    $amzDate = gmdate('Ymd\THis\Z', $now);
    $date = gmdate('Ymd', $now);
    $scope = $date . '/' . $c['region'] . '/s3/aws4_request';
    $query = array_merge([
        'X-Amz-Algorithm' => 'AWS4-HMAC-SHA256',
        'X-Amz-Credential' => $c['access_key'] . '/' . $scope,
        'X-Amz-Date' => $amzDate,
        'X-Amz-Expires' => (string)max(60, min(604800, $ttl)),
        'X-Amz-SignedHeaders' => 'host',
    ], $extraQuery);
    if ($c['session_token'] !== '') $query['X-Amz-Security-Token'] = $c['session_token'];
    ksort($query);
    $canonicalQuery = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    $canonicalRequest = strtoupper($method) . "\n" . sf_mp_aws_encode($parts['path'], true) . "\n" . $canonicalQuery . "\nhost:" . $parts['host'] . "\n\nhost\nUNSIGNED-PAYLOAD";
    $stringToSign = "AWS4-HMAC-SHA256\n{$amzDate}\n{$scope}\n" . hash('sha256', $canonicalRequest);
    $kDate = hash_hmac('sha256', $date, 'AWS4' . $c['secret_key'], true);
    $kRegion = hash_hmac('sha256', $c['region'], $kDate, true);
    $kService = hash_hmac('sha256', 's3', $kRegion, true);
    $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
    $query['X-Amz-Signature'] = hash_hmac('sha256', $stringToSign, $kSigning);
    return $parts['scheme'] . '://' . $parts['host'] . sf_mp_aws_encode($parts['path'], true) . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
}
