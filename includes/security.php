<?php
/**
 * Stonefellow runtime security bootstrap.
 *
 * This file intentionally has no project dependencies so it can be loaded
 * before configuration, database, authentication, or installer helpers.
 */

if (defined('SF_SECURITY_BOOTSTRAPPED_FILE')) {
    return;
}
define('SF_SECURITY_BOOTSTRAPPED_FILE', true);

function sf_env_bool(string $name, bool $default = false): bool
{
    $value = getenv($name);
    if ($value === false || trim((string)$value) === '') {
        return $default;
    }

    $parsed = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
    return $parsed ?? $default;
}

function sf_environment(): string
{
    global $security;
    $value = strtolower(trim((string)(getenv('SF_ENV') ?: ($security['environment'] ?? 'production'))));
    return in_array($value, ['local', 'development', 'testing', 'staging', 'production'], true)
        ? $value
        : 'production';
}

function sf_is_production(): bool
{
    return sf_environment() === 'production';
}

function sf_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443) {
        return true;
    }

    global $security;
    $trustProxy = sf_env_bool('SF_TRUST_PROXY', (bool)($security['trust_proxy'] ?? false));
    if ($trustProxy) {
        $forwarded = strtolower(trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0] ?? ''));
        return $forwarded === 'https';
    }

    return false;
}

function sf_security_allowed_hosts(): array
{
    global $security;
    $raw = (string)(getenv('SF_ALLOWED_HOSTS') ?: ($security['allowed_hosts'] ?? ''));
    $hosts = array_filter(array_map(static function (string $host): string {
        $host = strtolower(trim($host));
        return preg_replace('/[^a-z0-9.\-:\[\]]/i', '', $host) ?: '';
    }, explode(',', $raw)));

    return array_values(array_unique($hosts));
}

function sf_security_request_host(): string
{
    $host = strtolower(trim((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost')));
    $host = preg_replace('/[\x00-\x20\x7f].*/', '', $host) ?: 'localhost';
    if (!preg_match('/^(?:\[[0-9a-f:]+\]|[a-z0-9.-]+)(?::\d{1,5})?$/i', $host)) {
        $host = 'localhost';
    }

    $allowed = sf_security_allowed_hosts();
    if ($allowed !== [] && !in_array($host, $allowed, true)) {
        $hostWithoutPort = preg_replace('/:\d+$/', '', $host) ?: $host;
        foreach ($allowed as $allowedHost) {
            $allowedWithoutPort = preg_replace('/:\d+$/', '', $allowedHost) ?: $allowedHost;
            if (hash_equals($allowedWithoutPort, $hostWithoutPort)) {
                return $allowedHost;
            }
        }
        return $allowed[0];
    }

    return $host;
}

function sf_security_cookie_path(): string
{
    global $site;
    $base = trim((string)($site['base_url'] ?? ''));
    if ($base !== '' && preg_match('~^https?://~i', $base)) {
        $base = (string)(parse_url($base, PHP_URL_PATH) ?: '/');
    }
    if ($base === '') {
        return '/';
    }

    return '/' . trim($base, '/') . '/';
}

function sf_start_session(): void
{
    if (PHP_SAPI === 'cli' || session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    global $security;
    $sessionName = trim((string)(getenv('SF_SESSION_NAME') ?: ($security['session_name'] ?? 'stonefellow_session')));
    if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $sessionName)) {
        $sessionName = 'stonefellow_session';
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_name($sessionName);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => sf_security_cookie_path(),
        'domain' => '',
        'secure' => sf_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();

    $now = time();
    $regenerateEvery = max(300, (int)(getenv('SF_SESSION_REGENERATE_SECONDS') ?: 1800));
    if (empty($_SESSION['sf_session_started_at'])) {
        $_SESSION['sf_session_started_at'] = $now;
        $_SESSION['sf_session_regenerated_at'] = $now;
    } elseif (($now - (int)($_SESSION['sf_session_regenerated_at'] ?? 0)) >= $regenerateEvery) {
        session_regenerate_id(true);
        $_SESSION['sf_session_regenerated_at'] = $now;
    }
}

function sf_security_request_id(): string
{
    static $requestId;
    if (is_string($requestId)) {
        return $requestId;
    }

    $incoming = trim((string)($_SERVER['HTTP_X_REQUEST_ID'] ?? ''));
    $requestId = preg_match('/^[A-Za-z0-9._-]{8,100}$/', $incoming)
        ? $incoming
        : bin2hex(random_bytes(12));

    return $requestId;
}

function sf_send_security_headers(): void
{
    if (PHP_SAPI === 'cli' || headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(self)');
    header('Cross-Origin-Opener-Policy: same-origin-allow-popups');
    header('X-Permitted-Cross-Domain-Policies: none');
    header('X-Request-Id: ' . sf_security_request_id());

    if (sf_is_production() && sf_is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function sf_security_bootstrap(): void
{
    sf_start_session();
    sf_send_security_headers();

    if (PHP_SAPI === 'cli') {
        return;
    }

    $allowed = sf_security_allowed_hosts();
    if ($allowed !== []) {
        $rawHost = strtolower(trim((string)($_SERVER['HTTP_HOST'] ?? '')));
        $safeHost = sf_security_request_host();
        $rawHost = preg_replace('/[\x00-\x20\x7f].*/', '', $rawHost) ?: '';
        if ($rawHost !== '' && !hash_equals($safeHost, $rawHost)) {
            http_response_code(400);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Invalid request host.';
            exit;
        }
    }
}

function sf_security_safe_redirect(?string $target, string $fallback): string
{
    $target = trim((string)$target);
    if ($target === '' || preg_match('/[\x00-\x1f\x7f\\\\]/', $target)) {
        return $fallback;
    }

    $parts = parse_url($target);
    if ($parts === false || isset($parts['scheme'], $parts['host']) || isset($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
        return $fallback;
    }
    if (str_starts_with($target, '//') || preg_match('/^[a-z][a-z0-9+.-]*:/i', $target)) {
        return $fallback;
    }

    return $target;
}

function sf_security_raw_body(int $maxBytes = 1048576): string
{
    static $loaded = false;
    static $body = '';

    if ($loaded) {
        return $body;
    }
    $loaded = true;

    $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($contentLength > $maxBytes) {
        throw new LengthException('Request body is too large.');
    }

    $stream = fopen('php://input', 'rb');
    if (!$stream) {
        return '';
    }
    $body = (string)stream_get_contents($stream, $maxBytes + 1);
    fclose($stream);

    if (strlen($body) > $maxBytes) {
        throw new LengthException('Request body is too large.');
    }

    return $body;
}

function sf_security_json_payload(int $maxBytes = 1048576): array
{
    $raw = sf_security_raw_body($maxBytes);
    if ($raw === '') {
        return $_POST ?: [];
    }

    try {
        $decoded = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        throw new InvalidArgumentException('Invalid JSON request body.', 0, $e);
    }

    if (!is_array($decoded)) {
        throw new InvalidArgumentException('JSON request body must be an object.');
    }

    return $decoded;
}

function sf_security_require_method(string ...$allowed): void
{
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $allowed = array_values(array_unique(array_map('strtoupper', $allowed)));
    if (in_array($method, $allowed, true)) {
        return;
    }

    if (!headers_sent()) {
        header('Allow: ' . implode(', ', $allowed));
    }
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed'], JSON_UNESCAPED_SLASHES);
    exit;
}

function sf_security_secret(string $environmentName, string $configKey = ''): string
{
    $value = trim((string)(getenv($environmentName) ?: ''));
    if ($value !== '') {
        return $value;
    }

    global $security;
    if ($configKey !== '' && !empty($security[$configKey])) {
        return trim((string)$security[$configKey]);
    }

    return '';
}

function sf_security_session_rate_limit(string $bucket, int $limit, int $windowSeconds): array
{
    sf_start_session();
    $key = hash('sha256', $bucket);
    $now = time();
    $windowSeconds = max(1, $windowSeconds);
    $limit = max(1, $limit);
    $entries = array_values(array_filter(
        (array)($_SESSION['sf_rate_limits'][$key] ?? []),
        static fn($timestamp): bool => is_numeric($timestamp) && ((int)$timestamp + $windowSeconds) > $now
    ));

    $allowed = count($entries) < $limit;
    if ($allowed) {
        $entries[] = $now;
        $_SESSION['sf_rate_limits'][$key] = $entries;
    }

    $oldest = $entries[0] ?? $now;
    return [
        'allowed' => $allowed,
        'remaining' => max(0, $limit - count($entries)),
        'retry_after' => max(1, ($oldest + $windowSeconds) - $now),
    ];
}
