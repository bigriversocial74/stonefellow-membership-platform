<?php
require_once __DIR__ . '/show_theme.php';

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
  $path = $isPreview
    ? (sf_theme_image_path($image, 'approved_path') ?: sf_theme_image_path($image, 'generated_path') ?: sf_theme_image_path($image, 'current_path'))
    : (sf_theme_image_path($image, 'current_path') ?: sf_theme_image_path($image, 'approved_path'));
  return $path !== '' ? $path : $fallback;
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
