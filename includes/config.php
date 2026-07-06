<?php
$site = [
  'name' => 'Stonefellow',
  'tagline' => 'Watch the show. Stream the music. Wear the story.',
  'base_url' => '',
  'support_email' => 'support@stonefellow.tv',
];



$database = [
  'host' => getenv('SF_DB_HOST') ?: '',
  'name' => getenv('SF_DB_NAME') ?: '',
  'user' => getenv('SF_DB_USER') ?: '',
  'pass' => getenv('SF_DB_PASS') ?: '',
  'charset' => getenv('SF_DB_CHARSET') ?: 'utf8mb4',
];

function sf_url(string $path = ''): string {
  global $site;
  $cleanPath = ltrim($path, '/');
  $base = trim((string)($site['base_url'] ?? ''), '/');

  // When base_url is blank, keep links relative so the site works inside
  // any folder, for example /stonefellow/index.php on a local server.
  // Pages inside one-level utility folders such as /admin need ../ paths.
  if ($base === '') {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $needsUpOne = preg_match('~/(admin)$~', $scriptDir) === 1;
    $prefix = $needsUpOne ? '../' : '';
    return $cleanPath === '' ? ($prefix === '' ? './' : $prefix) : $prefix . $cleanPath;
  }

  return '/' . $base . ($cleanPath !== '' ? '/' . $cleanPath : '');
}

function sf_asset(string $path): string {
  return sf_url('assets/' . ltrim($path, '/'));
}

function sf_current_page(): string {
  return basename($_SERVER['PHP_SELF'] ?? 'index.php');
}

function sf_is_active(string $file): string {
  return sf_current_page() === $file ? 'is-active' : '';
}
?>
