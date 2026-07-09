<?php
$pageTitle = 'Theme Preview';
$pageDescription = 'Private admin preview for a draft or active show theme before publishing.';
$pageClass = 'membership-page admin-catalog-page theme-preview-page';
require __DIR__ . '/../includes/theme_public.php';
$user = sf_auth_user();
if (!$user || ($user['role'] ?? '') !== 'admin') { sf_auth_flash('warning', 'Admin access required.'); sf_redirect(sf_url('signin.php')); }

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) { sf_admin_flash('error', 'Security check failed. Refresh and try again.'); sf_admin_redirect('theme-preview.php'); }
  $action = (string)($_POST['action'] ?? '');
  $postThemeId = (int)($_POST['theme_id'] ?? 0);
  if ($action === 'publish_theme' && $postThemeId) { $ok = sf_theme_publish($postThemeId); sf_admin_flash($ok ? 'success' : 'error', $ok ? 'Theme published as the official public theme.' : 'Theme could not be published.'); sf_admin_redirect('theme-preview.php?theme_id=' . $postThemeId); }
  if ($action === 'unpublish_theme' && $postThemeId) { $ok = sf_theme_unpublish($postThemeId); sf_admin_flash($ok ? 'success' : 'error', $ok ? 'Theme moved back to preview.' : 'Theme publication status could not be updated.'); sf_admin_redirect('theme-preview.php?theme_id=' . $postThemeId); }
}

$themeId = (int)($_GET['theme_id'] ?? 0);
$theme = $themeId > 0 ? sf_theme_find($themeId) : sf_theme_active();
$palette = $theme ? sf_theme_palette($theme) : sf_theme_default_palette();
$images = $theme ? sf_theme_images((int)$theme['id']) : [];
$imageByKey = [];
foreach ($images as $image) $imageByKey[(string)$image['image_key']] = $image;
$hero = $imageByKey['home_hero'] ?? ($images[0] ?? []);
$heroPath = $hero ? (sf_theme_image_path($hero, 'approved_path') ?: sf_theme_image_path($hero, 'generated_path') ?: sf_theme_image_path($hero, 'current_path')) : '';
$heroSrc = $heroPath !== '' ? sf_asset($heroPath) : '';
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Theme Preview', $theme ? (string)$theme['theme_name'] : 'Theme Preview', 'Private preview workspace. This page does not publish or alter public templates until Publish Theme is clicked.', 'theme-images');
?>
<style>
.theme-live-preview{--theme-bg:<?= sf_theme_h($palette['background']) ?>;--theme-panel:<?= sf_theme_h($palette['panel']) ?>;--theme-accent:<?= sf_theme_h($palette['accent']) ?>;--theme-accent-2:<?= sf_theme_h($palette['accent_secondary']) ?>;--theme-text:<?= sf_theme_h($palette['text']) ?>;--theme-muted:<?= sf_theme_h($palette['muted']) ?>;background:radial-gradient(circle at 75% 0, color-mix(in srgb, var(--theme-accent) 20%, transparent), transparent 35%),linear-gradient(180deg,var(--theme-bg),#050403);border:1px solid rgba(255,255,255,.09);border-radius:24px;overflow:hidden;color:var(--theme-text)}.theme-live-hero{min-height:390px;display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:center;padding:48px;background:linear-gradient(90deg,rgba(0,0,0,.76),rgba(0,0,0,.12)),var(--theme-bg)}.theme-live-hero-image{min-height:320px;border-radius:24px;background:linear-gradient(135deg,rgba(255,255,255,.08),rgba(255,255,255,.02));background-size:cover;background-position:center;box-shadow:0 30px 90px rgba(0,0,0,.32)}.theme-live-hero h2{font-family:Bebas Neue,Impact,sans-serif;font-size:clamp(56px,8vw,108px);line-height:.9;margin:0;color:#fff}.theme-live-hero p{color:var(--theme-muted);font-size:18px;max-width:560px}.theme-live-pill{display:inline-flex;border:1px solid color-mix(in srgb, var(--theme-accent) 52%, transparent);border-radius:999px;padding:8px 14px;color:var(--theme-accent);font-weight:900;text-transform:uppercase;letter-spacing:.14em;font-size:11px}.theme-preview-swatches{display:grid;grid-template-columns:repeat(6,1fr);gap:12px;padding:24px;background:var(--theme-panel)}.theme-preview-swatch{border:1px solid rgba(255,255,255,.1);border-radius:14px;overflow:hidden;background:rgba(255,255,255,.035)}.theme-preview-swatch span{display:block;height:70px}.theme-preview-swatch small{display:block;padding:10px;color:var(--theme-muted);word-break:break-all}.theme-preview-image-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;padding:24px}.theme-preview-image-card{border:1px solid rgba(255,255,255,.1);border-radius:16px;overflow:hidden;background:rgba(255,255,255,.035)}.theme-preview-image-card img{width:100%;height:170px;object-fit:cover;display:block}.theme-preview-image-card figure{height:170px;margin:0;display:flex;align-items:center;justify-content:center;color:var(--theme-muted);background:rgba(255,255,255,.04)}.theme-preview-image-card div{padding:12px}.theme-preview-image-card strong{display:block;color:#fff}.theme-preview-image-card small{display:block;color:var(--theme-muted);word-break:break-all}.theme-publish-actions{display:flex;flex-wrap:wrap;gap:10px;align-items:center}.theme-publish-actions form{margin:0}@media(max-width:1000px){.theme-live-hero{grid-template-columns:1fr}.theme-preview-swatches,.theme-preview-image-grid{grid-template-columns:1fr}}
</style>
<?php if (!$theme): ?>
  <div class="sf-admin-alert sf-admin-alert-warning">No theme record found. Create one in Theme Image Map first.</div>
<?php else: ?>
  <section class="sf-admin-card-grid">
    <a class="sf-admin-action-card" href="<?= sf_url('admin/theme-images.php?theme_id=' . (int)$theme['id']) ?>"><span>Edit</span><strong>Theme Image Map</strong><small>Return to images, uploads, prompts, approval, and current paths.</small></a>
    <div class="sf-admin-action-card"><span>Status</span><strong><?= sf_theme_h($theme['status'] ?? 'draft') ?></strong><small><?= !empty($theme['is_active']) ? 'Currently published public theme.' : 'Private preview only.' ?></small></div>
    <div class="sf-admin-action-card"><span>Image Slots</span><strong><?= count($images) ?></strong><small>Approved images are preferred in this private preview.</small></div>
  </section>
  <section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Publish Control</span><h2>Official theme switch</h2><p>Publishing changes the one active public theme. Public templates will use this once they are wired to the theme helpers.</p></div><div class="theme-publish-actions"><form method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="publish_theme"><input type="hidden" name="theme_id" value="<?= (int)$theme['id'] ?>"><button type="submit">Publish Theme</button></form><?php if (!empty($theme['is_active'])): ?><form method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="unpublish_theme"><input type="hidden" name="theme_id" value="<?= (int)$theme['id'] ?>"><button type="submit">Move Back to Preview</button></form><?php endif; ?></div></div></section>
  <section class="theme-live-preview">
    <div class="theme-live-hero">
      <div><span class="theme-live-pill">Private Preview</span><h2><?= sf_theme_h($theme['theme_name']) ?></h2><p><?= sf_theme_h($theme['description'] ?: 'Preview how this show/theme direction feels before it is published as the official public theme.') ?></p><p><?= sf_theme_h($theme['mood_prompt'] ?? '') ?></p></div>
      <div class="theme-live-hero-image" style="<?= $heroSrc ? 'background-image:url(' . sf_theme_h($heroSrc) . ')' : '' ?>"></div>
    </div>
    <div class="theme-preview-swatches">
      <?php foreach ($palette as $name => $value): ?>
        <div class="theme-preview-swatch"><span style="background:<?= sf_theme_h($value) ?>"></span><small><?= sf_theme_h($name) ?><br><?= sf_theme_h($value) ?></small></div>
      <?php endforeach; ?>
    </div>
    <div class="theme-preview-image-grid">
      <?php foreach ($images as $image): $path = sf_theme_image_path($image, 'approved_path') ?: sf_theme_image_path($image, 'generated_path') ?: sf_theme_image_path($image, 'current_path'); $src = $path !== '' ? sf_asset($path) : ''; ?>
        <article class="theme-preview-image-card"><?php if ($src): ?><img src="<?= sf_theme_h($src) ?>" alt="<?= sf_theme_h($image['title']) ?>"><?php else: ?><figure>No image</figure><?php endif; ?><div><strong><?= sf_theme_h($image['title']) ?></strong><small><?= sf_theme_h($image['image_key']) ?> · <?= sf_theme_h($path) ?></small></div></article>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
