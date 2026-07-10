<?php
require_once __DIR__ . '/config.php';

sf_start_session();

function sf_db(): ?PDO
{
    static $pdo = false;
    global $database;

    if ($pdo !== false) {
        return $pdo;
    }

    $host = trim((string)($database['host'] ?? ''));
    $port = (int)($database['port'] ?? 3306);
    $name = trim((string)($database['name'] ?? ''));
    $user = trim((string)($database['user'] ?? ''));
    $pass = (string)($database['pass'] ?? '');
    $charset = strtolower(trim((string)($database['charset'] ?? 'utf8mb4')));

    if ($host === '' || $name === '' || $user === '') {
        $pdo = null;
        return null;
    }

    if (str_contains($host, ';') || str_contains($name, ';')) {
        error_log('Stonefellow database configuration rejected unsafe DSN characters.');
        $pdo = null;
        return null;
    }
    if ($port < 1 || $port > 65535) {
        $port = 3306;
    }
    if (!in_array($charset, ['utf8mb4', 'utf8'], true)) {
        $charset = 'utf8mb4';
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ];
    if (defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS')) {
        $options[PDO::MYSQL_ATTR_MULTI_STATEMENTS] = false;
    }

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (Throwable $e) {
        error_log('Stonefellow database connection failed [' . sf_security_request_id() . ']: ' . $e->getMessage());
        $pdo = null;
    }

    return $pdo;
}

function sf_current_user_id(): ?int
{
    foreach (['sf_user_id', 'user_id', 'member_id'] as $key) {
        if (!empty($_SESSION[$key]) && filter_var($_SESSION[$key], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            return (int)$_SESSION[$key];
        }
    }

    return null;
}

function sf_session_key(): string
{
    if (empty($_SESSION['sf_session_key']) || !is_string($_SESSION['sf_session_key'])) {
        $_SESSION['sf_session_key'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['sf_session_key'];
}

function sf_request_json(int $maxBytes = 1048576): array
{
    try {
        return sf_security_json_payload($maxBytes);
    } catch (LengthException $e) {
        sf_json_response(['ok' => false, 'error' => 'payload_too_large'], 413);
    } catch (InvalidArgumentException $e) {
        sf_json_response(['ok' => false, 'error' => 'invalid_json'], 400);
    }
}

function sf_json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    header('X-Request-Id: ' . sf_security_request_id());

    try {
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        http_response_code(500);
        echo '{"ok":false,"error":"response_encoding_failed"}';
    }
    exit;
}

function sf_client_hash(?string $value): ?string
{
    if ($value === null || $value === '') {
        return null;
    }

    global $security;
    $secret = trim((string)(
        getenv('SF_HASH_SALT')
        ?: getenv('SF_APP_KEY')
        ?: ($security['hash_salt'] ?? '')
        ?: ($security['app_key'] ?? '')
    ));

    if ($secret === '') {
        $lock = sf_install_lock_file();
        if (is_file($lock)) {
            $secret = hash('sha256', (string)file_get_contents($lock));
        }
    }

    if ($secret === '') {
        static $logged = false;
        if (!$logged) {
            error_log('Stonefellow client hashing is disabled because SF_HASH_SALT or SF_APP_KEY is missing.');
            $logged = true;
        }
        return null;
    }

    return hash_hmac('sha256', $value, $secret);
}

function sf_int_from_request(array $data, string $key, int $default = 0): int
{
    return isset($data[$key]) && is_numeric($data[$key]) ? (int)$data[$key] : $default;
}

function sf_float_from_request(array $data, string $key, float $default = 0.0): float
{
    return isset($data[$key]) && is_numeric($data[$key]) ? (float)$data[$key] : $default;
}
