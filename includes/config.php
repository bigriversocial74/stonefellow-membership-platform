<?php
require_once __DIR__ . '/security.php';

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

$security = [
    'environment' => getenv('SF_ENV') ?: 'production',
    'allowed_hosts' => getenv('SF_ALLOWED_HOSTS') ?: '',
    'trust_proxy' => sf_env_bool('SF_TRUST_PROXY', false),
    'session_name' => getenv('SF_SESSION_NAME') ?: 'stonefellow_session',
    'app_key' => getenv('SF_APP_KEY') ?: '',
    'hash_salt' => getenv('SF_HASH_SALT') ?: '',
];

$localConfigPath = dirname(__DIR__) . '/config/local.php';
if (is_file($localConfigPath)) {
    $localConfig = require $localConfigPath;
    if (is_array($localConfig)) {
        if (!empty($localConfig['site']) && is_array($localConfig['site'])) $site = array_merge($site, $localConfig['site']);
        if (!empty($localConfig['database']) && is_array($localConfig['database'])) $database = array_merge($database, $localConfig['database']);
        if (!empty($localConfig['security']) && is_array($localConfig['security'])) $security = array_merge($security, $localConfig['security']);
    }
}

if (!getenv('SF_APP_KEY') && !empty($security['app_key'])) putenv('SF_APP_KEY=' . (string)$security['app_key']);
if (!getenv('SF_HASH_SALT')) {
    $configuredSalt = trim((string)($security['hash_salt'] ?? $security['app_key'] ?? ''));
    if ($configuredSalt !== '') putenv('SF_HASH_SALT=' . $configuredSalt);
}

function sf_install_lock_file(): string { return dirname(__DIR__) . '/storage/install.lock'; }
function sf_is_installed(): bool { return is_file(sf_install_lock_file()); }
function sf_base_path(): string {
    global $site;
    $base = trim((string)($site['base_url'] ?? ''));
    if ($base !== '') {
        if (preg_match('~^https?://~i', $base)) {
            $path = (string)(parse_url($base, PHP_URL_PATH) ?: '');
            return $path === '' ? '' : '/' . trim($path, '/');
        }
        return '/' . trim($base, '/');
    }
    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/'));
    $directory = rtrim(str_replace('\\', '/', dirname($scriptName)), '/.');
    $last = basename($directory);
    if (in_array($last, ['admin', 'api', 'deploy'], true)) $directory = rtrim(str_replace('\\', '/', dirname($directory)), '/.');
    return $directory === '' || $directory === '/' ? '' : '/' . trim($directory, '/');
}
function sf_url(string $path = ''): string {
    global $site;
    $cleanPath = ltrim(trim($path), '/');
    $base = trim((string)($site['base_url'] ?? ''));
    if ($base !== '' && preg_match('~^https?://~i', $base)) return rtrim($base, '/') . ($cleanPath !== '' ? '/' . $cleanPath : '');
    $basePath = sf_base_path();
    if ($cleanPath === '') return $basePath === '' ? '/' : $basePath . '/';
    return ($basePath === '' ? '' : $basePath) . '/' . $cleanPath;
}
function sf_absolute_url(string $path = ''): string {
    $url = sf_url($path);
    if (preg_match('~^https?://~i', $url)) return $url;
    return (sf_is_https() ? 'https' : 'http') . '://' . sf_security_request_host() . '/' . ltrim($url, '/');
}
function sf_asset(string $path): string {
    $path = trim($path);
    if ($path === '') return sf_url('assets/');
    if (preg_match('~^https?://~i', $path) || str_starts_with($path, '//') || str_starts_with($path, 'data:')) return $path;
    $cleanPath = ltrim($path, '/');
    if (!str_starts_with(strtolower($cleanPath), 'assets/')) $cleanPath = 'assets/' . $cleanPath;
    return sf_url($cleanPath);
}
function sf_current_page(): string { return basename((string)($_SERVER['PHP_SELF'] ?? 'index.php')); }
function sf_is_active(string $file): string { return sf_current_page() === $file ? 'is-active' : ''; }
function sf_install_redirect_if_needed(): void {
    if (PHP_SAPI === 'cli' || sf_env_bool('SF_SKIP_INSTALL_REDIRECT', false)) return;
    $script = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($script === 'install.php' || sf_is_installed()) return;
    header('Cache-Control: no-store, private');
    header('Location: ' . sf_url('install.php'), true, 302);
    exit;
}

sf_security_bootstrap();
require_once __DIR__ . '/security_headers_hardening.php';
sf_security_send_hardened_headers();
require_once __DIR__ . '/runtime_guards.php';
require_once __DIR__ . '/admin_uploads.php';
sf_install_redirect_if_needed();
