<?php
$site = [
  'name' => 'Stonefellow',
  'tagline' => 'Watch the show. Stream the music. Wear the story.',
  'base_url' => '',
  'support_email' => 'support@stonefellow.tv',
];

$database = [
  'host' => getenv('SF_DB_HOST') ?: '',
  'port' => getenv('SF_DB_PORT') ?: '3306',
  'name' => getenv('SF_DB_NAME') ?: '',
  'user' => getenv('SF_DB_USER') ?: '',
  'pass' => getenv('SF_DB_PASS') ?: '',
  'charset' => getenv('SF_DB_CHARSET') ?: 'utf8mb4',
];

$localConfigPath = dirname(__DIR__) . '/config/local.php';
if (is_file($localConfigPath)) {
  $localConfig = require $localConfigPath;
  if (is_array($localConfig)) {
    if (!empty($localConfig['site']) && is_array($localConfig['site'])) {
      $site = array_merge($site, $localConfig['site']);
    }
    if (!empty($localConfig['database']) && is_array($localConfig['database'])) {
      $database = array_merge($database, $localConfig['database']);
    }
  }
}

function sf_install_lock_file(): string {
  return dirname(__DIR__) . '/storage/install.lock';
}

function sf_is_installed(): bool {
  return is_file(sf_install_lock_file());
}

function sf_url(string $path = ''): string {
  global $site;
  $cleanPath = ltrim($path, '/');
  $base = trim((string)($site['base_url'] ?? ''), '/');

  if ($base === '') {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $needsUpOne = preg_match('~/(admin|api)$~', $scriptDir) === 1;
    $prefix = $needsUpOne ? '../' : '';
    return $cleanPath === '' ? ($prefix === '' ? './' : $prefix) : $prefix . $cleanPath;
  }

  if (preg_match('~^https?://~i', $base)) {
    return rtrim($base, '/') . ($cleanPath !== '' ? '/' . $cleanPath : '');
  }

  return '/' . $base . ($cleanPath !== '' ? '/' . $cleanPath : '');
}

function sf_asset(string $path): string {
  global $site;
  $cleanPath = ltrim($path, '/');

  if (preg_match('~^(https?:)?//~i', $path)) {
    return $path;
  }

  $base = trim((string)($site['base_url'] ?? ''), '/');
  if ($base !== '') {
    if (preg_match('~^https?://~i', $base)) {
      return rtrim($base, '/') . '/assets/' . $cleanPath;
    }

    return '/' . $base . '/assets/' . $cleanPath;
  }

  return '/assets/' . $cleanPath;
}

function sf_current_page(): string {
  return basename($_SERVER['PHP_SELF'] ?? 'index.php');
}

function sf_is_active(string $file): string {
  return sf_current_page() === $file ? 'is-active' : '';
}

function sf_install_redirect_if_needed(): void {
  if (PHP_SAPI === 'cli' || getenv('SF_SKIP_INSTALL_REDIRECT')) {
    return;
  }
  $script = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
  if ($script === 'install.php') {
    return;
  }
  if (!sf_is_installed()) {
    header('Location: ' . sf_url('install.php'));
    exit;
  }
}

sf_install_redirect_if_needed();
?>
