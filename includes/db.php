<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function sf_db(): ?PDO {
  static $pdo = false;
  global $database;

  if ($pdo !== false) {
    return $pdo;
  }

  $host = $database['host'] ?? '';
  $port = $database['port'] ?? '3306';
  $name = $database['name'] ?? '';
  $user = $database['user'] ?? '';
  $pass = $database['pass'] ?? '';
  $charset = $database['charset'] ?? 'utf8mb4';

  if ($host === '' || $name === '' || $user === '') {
    $pdo = null;
    return null;
  }

  $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
  try {
    $pdo = new PDO($dsn, $user, $pass, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => true,
    ]);
  } catch (Throwable $e) {
    error_log('Stonefellow database connection failed: ' . $e->getMessage());
    $pdo = null;
  }

  return $pdo;
}

function sf_current_user_id(): ?int {
  foreach (['sf_user_id', 'user_id', 'member_id'] as $key) {
    if (!empty($_SESSION[$key]) && is_numeric($_SESSION[$key])) {
      return (int)$_SESSION[$key];
    }
  }
  return null;
}

function sf_session_key(): string {
  if (empty($_SESSION['sf_session_key'])) {
    $_SESSION['sf_session_key'] = bin2hex(random_bytes(16));
  }
  return $_SESSION['sf_session_key'];
}

function sf_request_json(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw ?: '', true);
  if (!is_array($data)) {
    $data = $_POST ?: [];
  }
  return $data;
}

function sf_json_response(array $payload, int $status = 200): void {
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($payload, JSON_UNESCAPED_SLASHES);
  exit;
}

function sf_client_hash(?string $value): ?string {
  if (!$value) {
    return null;
  }
  return hash('sha256', $value . '|' . (getenv('SF_HASH_SALT') ?: 'stonefellow-local-salt'));
}

function sf_int_from_request(array $data, string $key, int $default = 0): int {
  return isset($data[$key]) && is_numeric($data[$key]) ? (int)$data[$key] : $default;
}

function sf_float_from_request(array $data, string $key, float $default = 0.0): float {
  return isset($data[$key]) && is_numeric($data[$key]) ? (float)$data[$key] : $default;
}
?>
