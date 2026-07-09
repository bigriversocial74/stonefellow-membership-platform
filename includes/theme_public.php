<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if (!function_exists('sf_theme_h')) {
  function sf_theme_h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('sf_theme_slug')) {
  function sf_theme_slug(string $name): string { $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name) ?: 'theme', '-')); return $slug ?: 'theme'; }
}

if (!function_exists('sf_theme_db')) {
  function sf_theme_db(): ?PDO { return sf_db(); }
}

if (!function_exists('sf_theme_table_exists')) {
  function sf_theme_table_exists(string $table): bool { $pdo = sf_theme_db(); if (!$pdo) return false; try { $stmt = $pdo->prepare('SHOW TABLES LIKE ?'); $stmt->execute([$table]); return (bool)$stmt->fetchColumn(); } catch (Throwable $e) { error_log('Theme table check failed: ' . $e->getMessage()); return false; } }
}

if (!function_exists('sf_theme_ready')) {
  function sf_theme_ready(): bool { return sf_theme_table_exists('show_themes') && sf_theme_table_exists('show_theme_images'); }
}

if (!function_exists('sf_theme_default_palette')) {
  function sf_theme_default_palette(): array { return ['background'=>'#030302','panel'=>'#0b0907','accent'=>'#d6ad6c','accent_secondary'=>'#c79a52','text'=>'#ead8bc','muted'=>'#b09b79','border'=>'rgba(214,173,108,.18)']; }
}

if (!function_exists('sf_theme_find')) {
  function sf_theme_find(int $id): ?array { if (!sf_theme_ready() || $id <= 0) return null; try { $stmt = sf_theme_db()->prepare('SELECT * FROM show_themes WHERE id=? LIMIT 1'); $stmt->execute([$id]); $row = $stmt->fetch(); return $row ?: null; } catch (Throwable $e) { return null; } }
}

if (!function_exists('sf_theme_active')) {
  function sf_theme_active(): ?array { if (!sf_theme_ready()) return null; try { $row = sf_theme_db()->query('SELECT * FROM show_themes WHERE is_active = 1 ORDER BY id DESC LIMIT 1')->fetch(); return $row ?: null; } catch (Throwable $e) { return null; } }
}

if (!function_exists('sf_theme_images')) {
  function sf_theme_images(int $themeId): array { if (!sf_theme_ready() || $themeId <= 0) return []; try { $stmt = sf_theme_db()->prepare('SELECT * FROM show_theme_images WHERE theme_id=? ORDER BY sort_order ASC, id ASC'); $stmt->execute([$themeId]); return $stmt->fetchAll() ?: []; } catch (Throwable $e) { return []; } }
}

if (!function_exists('sf_theme_image_by_key')) {
  function sf_theme_image_by_key(int $themeId, string $imageKey): ?array { if (!sf_theme_ready() || $themeId <= 0 || trim($imageKey) === '') return null; try { $stmt = sf_theme_db()->prepare('SELECT * FROM show_theme_images WHERE theme_id=? AND image_key=? LIMIT 1'); $stmt->execute([$themeId, $imageKey]); $row = $stmt->fetch(); return $row ?: null; } catch (Throwable $e) { return null; } }
}

if (!function_exists('sf_theme_palette')) {
  function sf_theme_palette(array $theme): array { $json = $theme['palette_json'] ?? ''; $data = is_string($json) ? json_decode($json, true) : (is_array($json) ? $json : []); return array_merge(sf_theme_default_palette(), is_array($data) ? $data : []); }
}

if (!function_exists('sf_theme_image_path')) {
  function sf_theme_image_path(array $image, string $field): string { return trim((string)($image[$field] ?? '')); }
}

function sf_theme_public_local_image_exists(string $path): bool {
  $path = trim($path);
  if ($path === '') return false;
  if (preg_match('~^(https?:)?//|^data:~i', $path)) return true;
  $relative = ltrim($path, '/');
  if (strpos($relative, 'assets/') === 0) $relative = substr($relative, 7);
  return is_file(dirname(__DIR__) . '/assets/' . $relative);
}

function sf_theme_published(): ?array { return sf_theme_active(); }

function sf_theme_admin_preview_allowed(): bool {
  if (!function_exists('sf_auth_user')) return false;
  $user = sf_auth_user();
  return $user && ($user['role'] ?? '') === 'admin';
}

function sf_theme_preview_from_request(): ?array {
  if (!sf_theme_admin_preview_allowed()) return null;
  $themeId = (int)($_GET['preview_theme_id'] ?? $_GET['theme_id'] ?? 0);
  if ($themeId > 0) return sf_theme_find($themeId);
  $slug = trim((string)($_GET['preview_theme'] ?? ''));
  if ($slug === '' || !sf_theme_ready()) return null;
  try {
    $stmt = sf_theme_db()->prepare('SELECT * FROM show_themes WHERE theme_slug=? LIMIT 1');
    $stmt->execute([sf_theme_slug($slug)]);
    $theme = $stmt->fetch();
    return $theme ?: null;
  } catch (Throwable $e) { return null; }
}

function sf_theme_public_current(): ?array { return sf_theme_preview_from_request() ?: sf_theme_published(); }

function sf_theme_publish(int $themeId): bool {
  if (!sf_theme_ready() || $themeId <= 0) return false;
  try {
    $pdo = sf_theme_db();
    $pdo->beginTransaction();
    $pdo->exec("UPDATE show_themes SET is_active=0, status=CASE WHEN status='published' THEN 'archived' ELSE status END");
    $stmt = $pdo->prepare("UPDATE show_themes SET is_active=1, status='published' WHERE id=?");
    $ok = $stmt->execute([$themeId]);
    $pdo->commit();
    return $ok;
  } catch (Throwable $e) { if (sf_theme_db()?->inTransaction()) sf_theme_db()->rollBack(); return false; }
}

function sf_theme_unpublish(int $themeId): bool {
  if (!sf_theme_ready() || $themeId <= 0) return false;
  try {
    $stmt = sf_theme_db()->prepare("UPDATE show_themes SET is_active=0, status='preview' WHERE id=?");
    return $stmt->execute([$themeId]);
  } catch (Throwable $e) { return false; }
}

function sf_theme_public_palette(?array $theme = null): array { return $theme ? sf_theme_palette($theme) : sf_theme_default_palette(); }
function sf_theme_public_color(string $token, string $fallback = '', ?array $theme = null): string { $palette = sf_theme_public_palette($theme ?: sf_theme_public_current()); return trim((string)($palette[$token] ?? $fallback)); }

function sf_theme_public_image(string $imageKey, string $fallback = '', ?array $theme = null): string {
  $theme = $theme ?: sf_theme_public_current();
  if (!$theme) return $fallback;
  $image = sf_theme_image_by_key((int)$theme['id'], $imageKey);
  if (!$image) return $fallback;
  $isPreview = sf_theme_admin_preview_allowed() && (sf_theme_preview_from_request() !== null);
  $paths = $isPreview
    ? [sf_theme_image_path($image, 'approved_path'), sf_theme_image_path($image, 'generated_path'), sf_theme_image_path($image, 'current_path')]
    : [sf_theme_image_path($image, 'current_path'), sf_theme_image_path($image, 'approved_path')];
  foreach ($paths as $path) {
    if ($path !== '' && sf_theme_public_local_image_exists($path)) return $path;
  }
  return $fallback;
}

function sf_theme_public_image_src(string $imageKey, string $fallback = '', ?array $theme = null): string {
  $path = sf_theme_public_image($imageKey, $fallback, $theme);
  return $path !== '' ? sf_asset($path) : '';
}

function sf_theme_css_variables(?array $theme = null, string $selector = ':root'): string {
  $palette = sf_theme_public_palette($theme ?: sf_theme_public_current());
  $map = ['background'=>'--sf-theme-bg','panel'=>'--sf-theme-panel','accent'=>'--sf-theme-accent','accent_secondary'=>'--sf-theme-accent-secondary','text'=>'--sf-theme-text','muted'=>'--sf-theme-muted','border'=>'--sf-theme-border'];
  $css = $selector . '{';
  foreach ($map as $key => $var) $css .= $var . ':' . sf_theme_h($palette[$key] ?? '') . ';';
  return $css . '}';
}

function sf_theme_css_variables_tag(?array $theme = null, string $selector = ':root'): string { return '<style>' . sf_theme_css_variables($theme, $selector) . '</style>'; }
?>
