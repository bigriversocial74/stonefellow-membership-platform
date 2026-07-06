<?php
$pageTitle = 'Mobile PWA';
$pageDescription = 'Stonefellow mobile app shell and PWA readiness.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require __DIR__ . '/../includes/header.php';
$checks = [
  ['Manifest','manifest.webmanifest','Install metadata and shortcuts'],
  ['Service Worker','service-worker.js','Offline shell cache and navigation fallback'],
  ['Offline Page','offline.php','Offline public fallback page'],
  ['Runtime JS','assets/js/pwa-upload.js','Install prompt and upload previews'],
  ['Styles','assets/css/pwa-upload.css','PWA and upload styling'],
];
sf_admin_shell_start('Mobile PWA', 'Offline media shell v1', 'Installable app shell, mobile polish, offline fallback, service worker cache, and PWA readiness checks.', 'mobile-pwa');
?>
<section class="sf-admin-card-grid">
  <a class="sf-admin-action-card" href="<?= sf_url('manifest.webmanifest') ?>"><span>Manifest</span><strong>Installable</strong><small>App name, icons, shortcuts, and display mode.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('offline.php') ?>"><span>Offline</span><strong>Fallback</strong><small>Cached shell when navigation fails.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('player.php') ?>"><span>Mobile</span><strong>Mini Player</strong><small>Mobile now-playing helper.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/uploads.php') ?>"><span>Uploads</span><strong>Media UX v2</strong><small>Drag/drop upload and previews.</small></a>
</section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Readiness</span><h2>PWA file checks</h2></div></div><div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Check</th><th>Status</th><th>Path</th><th>Detail</th></tr></thead><tbody>
<?php foreach ($checks as $check): $exists = is_file(dirname(__DIR__) . '/' . $check[1]); ?><tr><td><strong><?= sf_admin_h($check[0]) ?></strong></td><td><?= sf_admin_status_badge($exists ? 'active' : 'canceled') ?></td><td><code><?= sf_admin_h($check[1]) ?></code></td><td><?= sf_admin_h($check[2]) ?></td></tr><?php endforeach; ?>
</tbody></table></div></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Notes</span><h2>Mobile shell behavior</h2></div></div><div class="sf-admin-roadmap"><div><span>01</span><strong>Install Prompt</strong><p>Supported browsers can show the install banner after the manifest and worker are accepted.</p></div><div><span>02</span><strong>Offline Fallback</strong><p>Navigation requests fall back to <code>offline.php</code> when the network is unavailable.</p></div><div><span>03</span><strong>Streaming</strong><p>Signed streams still need a connection and valid access checks.</p></div><div><span>04</span><strong>Mini Player</strong><p>Music pages show a small now-playing helper on mobile screens.</p></div></div></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
