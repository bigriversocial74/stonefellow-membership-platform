<?php
require_once __DIR__ . '/db.php';

function sf_settings_db(): ?PDO {
  return sf_db();
}

function sf_settings_table_exists(string $table): bool {
  $pdo = sf_settings_db();
  if (!$pdo) {
    return false;
  }
  try {
    $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
    $stmt->execute([$table]);
    return (bool)$stmt->fetchColumn();
  } catch (Throwable $e) {
    error_log('Stonefellow settings table check failed: ' . $e->getMessage());
    return false;
  }
}

function sf_settings_ready(): bool {
  return sf_settings_db() instanceof PDO && sf_settings_table_exists('site_settings');
}

function sf_setting_defaults(): array {
  global $site;
  return [
    'site_name' => $site['name'] ?? 'Stonefellow',
    'site_tagline' => $site['tagline'] ?? 'Watch the show. Stream the music. Wear the story.',
    'base_url' => $site['base_url'] ?? '',
    'support_email' => $site['support_email'] ?? 'support@stonefellow.tv',
    'admin_email' => getenv('SF_ADMIN_EMAIL') ?: ($site['support_email'] ?? 'support@stonefellow.tv'),
    'uploads_public_base' => 'assets/',
    'maintenance_mode' => '0',
    'member_signup_enabled' => '1',
    'checkout_enabled' => '1',
    'payment_provider' => getenv('SF_PAYMENT_PROVIDER') ?: 'sandbox',
    'stripe_publishable_key' => getenv('SF_STRIPE_PUBLISHABLE_KEY') ?: '',
    'paypal_client_id' => getenv('SF_PAYPAL_CLIENT_ID') ?: '',
  ];
}

function sf_get_setting(string $key, ?string $default = null): ?string {
  static $cache = null;
  if ($cache === null) {
    $cache = sf_setting_defaults();
    if (sf_settings_ready()) {
      try {
        $rows = sf_settings_db()->query("SELECT setting_key, setting_value FROM site_settings WHERE is_public = 1 OR setting_key IN ('site_name','site_tagline','base_url','support_email','admin_email','uploads_public_base','maintenance_mode','member_signup_enabled','checkout_enabled','payment_provider')")->fetchAll() ?: [];
        foreach ($rows as $row) {
          $cache[(string)$row['setting_key']] = (string)($row['setting_value'] ?? '');
        }
      } catch (Throwable $e) {
        error_log('Stonefellow settings load failed: ' . $e->getMessage());
      }
    }
  }
  return array_key_exists($key, $cache) ? $cache[$key] : $default;
}

function sf_update_setting(string $key, string $value, string $group = 'site', bool $isPublic = false): bool {
  if (!sf_settings_ready()) {
    return false;
  }
  try {
    $stmt = sf_settings_db()->prepare("INSERT INTO site_settings (setting_key, setting_value, setting_group, is_public, updated_by_user_id) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), setting_group=VALUES(setting_group), is_public=VALUES(is_public), updated_by_user_id=VALUES(updated_by_user_id), updated_at=NOW()");
    return $stmt->execute([$key, $value, $group, $isPublic ? 1 : 0, sf_current_user_id()]);
  } catch (Throwable $e) {
    error_log('Stonefellow setting update failed: ' . $e->getMessage());
    return false;
  }
}

function sf_load_site_settings(): void {
  global $site;
  $site['name'] = sf_get_setting('site_name', $site['name'] ?? 'Stonefellow') ?: 'Stonefellow';
  $site['tagline'] = sf_get_setting('site_tagline', $site['tagline'] ?? '') ?: ($site['tagline'] ?? '');
  $site['base_url'] = sf_get_setting('base_url', $site['base_url'] ?? '') ?: '';
  $site['support_email'] = sf_get_setting('support_email', $site['support_email'] ?? 'support@stonefellow.tv') ?: 'support@stonefellow.tv';
}

function sf_system_health_checks(): array {
  global $database;
  $root = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
  $checks = [];
  $checks[] = ['label' => 'PHP version 8.1+', 'ok' => version_compare(PHP_VERSION, '8.1.0', '>='), 'detail' => PHP_VERSION];
  $checks[] = ['label' => 'PDO extension', 'ok' => extension_loaded('pdo'), 'detail' => extension_loaded('pdo') ? 'Loaded' : 'Missing'];
  $checks[] = ['label' => 'PDO MySQL extension', 'ok' => extension_loaded('pdo_mysql'), 'detail' => extension_loaded('pdo_mysql') ? 'Loaded' : 'Missing'];
  $checks[] = ['label' => 'mbstring extension', 'ok' => extension_loaded('mbstring'), 'detail' => extension_loaded('mbstring') ? 'Loaded' : 'Optional fallback active'];
  $hasDb = !empty($database['host']) && !empty($database['name']) && !empty($database['user']);
  $checks[] = ['label' => 'Database credentials', 'ok' => $hasDb, 'detail' => $hasDb ? 'Configured' : 'Not configured'];
  $checks[] = ['label' => 'Database connection', 'ok' => sf_db() instanceof PDO, 'detail' => sf_db() instanceof PDO ? 'Connected' : 'Static/no-database mode'];
  foreach (['media_assets','users','subscription_plans','songs','episodes','videos','products','orders','email_templates','site_settings','schema_migrations'] as $table) {
    $checks[] = ['label' => 'Table: ' . $table, 'ok' => sf_settings_table_exists($table), 'detail' => sf_settings_table_exists($table) ? 'Installed' : 'Missing/not checked'];
  }
  foreach (['assets/images/uploads','assets/audio/uploads','assets/video/uploads','assets/documents/uploads','config','storage'] as $dir) {
    $path = $root . '/' . $dir;
    $checks[] = ['label' => 'Writable: ' . $dir, 'ok' => is_dir($path) && is_writable($path), 'detail' => is_dir($path) ? (is_writable($path) ? 'Writable' : 'Not writable') : 'Missing'];
  }
  $checks[] = ['label' => 'Installer lock', 'ok' => function_exists('sf_is_installed') ? sf_is_installed() : is_file($root . '/storage/install.lock'), 'detail' => is_file($root . '/storage/install.lock') ? 'Locked' : 'Installer open'];
  return $checks;
}

function sf_health_score(array $checks): int {
  if (!$checks) {
    return 0;
  }
  $ok = 0;
  foreach ($checks as $check) {
    $ok += !empty($check['ok']) ? 1 : 0;
  }
  return (int)round(($ok / count($checks)) * 100);
}

sf_load_site_settings();
?>
