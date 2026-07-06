<?php
$pageTitle = 'Stonefellow Installer';
$pageDescription = 'Install and verify the Stonefellow membership platform foundation.';
$pageClass = 'membership-page admin-catalog-page install-page';
require __DIR__ . '/includes/settings.php';
$checks = sf_system_health_checks();
$score = sf_health_score($checks);
require __DIR__ . '/includes/header.php';
?>
<section class="sf-admin-shell sf-installer-shell">
  <section class="sf-admin-main sf-installer-main">
    <section class="sf-admin-hero">
      <div><span class="sf-panel-eyebrow">Installer + Health Check</span><h1>Stonefellow platform setup</h1><p>Verify PHP, database, SQL migrations, writable media folders, and runtime settings before going live.</p></div>
      <div class="sf-admin-db-card"><span>Install Score</span><strong><?= (int)$score ?>%</strong><small><?= sf_db() instanceof PDO ? 'Database mode' : 'Static preview mode' ?></small></div>
    </section>
    <section class="sf-admin-card-grid">
      <a class="sf-admin-action-card" href="<?= sf_url('docs/SQL_FILE_MAP.md') ?>"><span>Step 1</span><strong>Run SQL files</strong><small>Base schema, then migrations 001 through 010.</small></a>
      <a class="sf-admin-action-card" href="<?= sf_url('admin/settings.php') ?>"><span>Step 2</span><strong>Save site settings</strong><small>Name, emails, toggles, and payment provider.</small></a>
      <a class="sf-admin-action-card" href="<?= sf_url('admin/uploads.php') ?>"><span>Step 3</span><strong>Test uploads</strong><small>Confirm image, audio, video, and document folders.</small></a>
      <a class="sf-admin-action-card" href="<?= sf_url('admin/system-health.php') ?>"><span>Step 4</span><strong>Admin health</strong><small>Review detailed checks in the admin dashboard.</small></a>
    </section>
    <section class="sf-admin-panel">
      <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">System Checks</span><h2>Readiness checklist</h2></div></div>
      <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Check</th><th>Status</th><th>Detail</th></tr></thead><tbody>
        <?php foreach ($checks as $check): ?><tr><td><strong><?= htmlspecialchars($check['label']) ?></strong></td><td><?= !empty($check['ok']) ? '<span class="sf-admin-status sf-admin-status-published">Pass</span>' : '<span class="sf-admin-status sf-admin-status-draft">Review</span>' ?></td><td><?= htmlspecialchars($check['detail'] ?? '') ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
    </section>
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
