<?php
$pageTitle = 'System Health';
$pageDescription = 'Stonefellow installer, database, uploads, and runtime health checks.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/settings.php';
require __DIR__ . '/../includes/header.php';
$checks = sf_system_health_checks();
$score = sf_health_score($checks);
sf_admin_shell_start('System Health', 'Launch readiness checks', 'Verify database tables, upload permissions, PHP extensions, environment settings, and runtime readiness.', 'health');
?>
<section class="sf-admin-card-grid">
  <div class="sf-admin-action-card"><span>Health Score</span><strong><?= (int)$score ?>%</strong><small><?= $score >= 90 ? 'Launch-ready foundation' : 'Review missing items' ?></small></div>
  <a class="sf-admin-action-card" href="<?= sf_url('install.php') ?>"><span>Installer</span><strong>Public Setup Page</strong><small>Open the installer checklist.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/settings.php') ?>"><span>Settings</span><strong>Runtime Settings</strong><small>Configure identity and toggles.</small></a>
</section>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Checks</span><h2>Environment and schema</h2></div></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Check</th><th>Status</th><th>Detail</th></tr></thead><tbody>
    <?php foreach ($checks as $check): ?><tr><td><strong><?= sf_admin_h($check['label']) ?></strong></td><td><?= !empty($check['ok']) ? sf_admin_status_badge('published') : sf_admin_status_badge('draft') ?></td><td><?= sf_admin_h($check['detail'] ?? '') ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
