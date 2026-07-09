<?php
$pageTitle = 'Launch Readiness';
$pageDescription = 'Permanent Stonefellow launch readiness dashboard for install lock, schema, settings, uploads, payments, email, backups, and public routes.';
$pageClass = 'membership-page admin-catalog-page launch-readiness-page';
require __DIR__ . '/../includes/qa.php';

function sflr_badge(string $status): string {
  $map = ['pass'=>'active','ready'=>'active','ok'=>'active','warn'=>'draft','manual'=>'draft','preview'=>'draft','info'=>'draft','fail'=>'canceled','missing'=>'canceled'];
  return sf_admin_status_badge($map[$status] ?? $status);
}
function sflr_status_label(string $status): string {
  return ['pass'=>'Ready','ready'=>'Ready','ok'=>'Ready','warn'=>'Review','manual'=>'Manual','preview'=>'Preview','info'=>'Info','fail'=>'Fix','missing'=>'Missing'][$status] ?? ucfirst($status);
}
function sflr_card(string $status, string $title, string $detail, ?string $href = null, ?string $cta = null): array {
  return ['status'=>$status,'title'=>$title,'detail'=>$detail,'href'=>$href,'cta'=>$cta];
}
function sflr_count_statuses(array $checks, array $statuses): int {
  return count(array_filter($checks, static fn($check) => in_array((string)($check['status'] ?? ''), $statuses, true)));
}
function sflr_db_count(string $table, string $where = '1=1', array $params = []): int {
  if (!sf_qa_db_ready() || !sf_admin_table_exists($table)) return 0;
  $safeTable = str_replace('`', '', $table);
  $row = sf_admin_fetch_one('SELECT COUNT(*) AS total FROM `' . $safeTable . '` WHERE ' . $where, $params);
  return (int)($row['total'] ?? 0);
}
function sflr_upload_dirs(): array {
  $dirs = ['config','storage','assets/images/uploads','assets/audio/uploads','assets/video/uploads','assets/documents/uploads'];
  $rows = [];
  foreach ($dirs as $dir) {
    $path = sf_qa_file_path($dir);
    $exists = is_dir($path);
    $writable = $exists && is_writable($path);
    $rows[] = ['dir'=>$dir,'status'=>$writable ? 'pass' : ($exists ? 'warn' : 'fail'),'detail'=>$exists ? ($writable ? 'Writable' : 'Exists but is not writable') : 'Missing'];
  }
  return $rows;
}
function sflr_critical_routes(): array {
  $routes = [
    ['index.php','Public home'], ['admin/index.php','Admin dashboard'], ['admin/settings.php','Site settings'],
    ['admin/migration-checker.php','Migration checker'], ['admin/qa.php','Production QA'], ['admin/uploads.php','Asset uploads'],
    ['admin/payment-gateways.php','Payment gateways'], ['admin/email-templates.php','Email templates'], ['merch.php','Merch'],
    ['checkout.php','Checkout'], ['signin.php','Sign in'], ['install.php','Installer']
  ];
  $rows = [];
  foreach ($routes as $route) {
    $rows[] = ['path'=>$route[0],'label'=>$route[1],'status'=>sf_qa_file_exists($route[0]) ? 'pass' : 'fail','detail'=>sf_qa_file_exists($route[0]) ? 'Route file present' : 'Route file missing'];
  }
  return $rows;
}
function sflr_installer_media(): array {
  $files = ['vp3-dashboard.png','vp3-devices.png','vp3-readiness.png','vp3-music-player.png','vp3-memberships.png','vp3-merch.png'];
  $rows = [];
  foreach ($files as $file) {
    $path = 'assets/images/installer/' . $file;
    $rows[] = ['path'=>$path,'status'=>sf_qa_file_exists($path) ? 'pass' : 'warn','detail'=>sf_qa_file_exists($path) ? 'Present in repo/server path' : 'Missing from repo; preserve server copy during ZIP deploy'];
  }
  return $rows;
}
function sflr_setting_value(string $key, string $default = ''): string {
  return trim((string)sf_get_setting($key, $default));
}
function sflr_launch_cards(): array {
  $root = sf_qa_root();
  $lockPath = $root . '/storage/install.lock';
  $adminCount = sflr_db_count('users', "role = 'admin'");
  $migrationChecks = sf_qa_migration_checks();
  $migrationScore = sf_qa_score($migrationChecks);
  $migrationFails = sflr_count_statuses($migrationChecks, ['fail','missing']);
  $uploadRows = sflr_upload_dirs();
  $uploadIssues = count(array_filter($uploadRows, static fn($row) => ($row['status'] ?? '') !== 'pass'));
  $routes = sflr_critical_routes();
  $routeIssues = count(array_filter($routes, static fn($row) => ($row['status'] ?? '') !== 'pass'));
  $mediaRows = sflr_installer_media();
  $mediaMissing = count(array_filter($mediaRows, static fn($row) => ($row['status'] ?? '') !== 'pass'));

  $siteName = sflr_setting_value('site_name', '');
  $baseUrl = sflr_setting_value('base_url', '');
  $supportEmail = sflr_setting_value('support_email', '');
  $settingsReady = sf_admin_table_exists('site_settings') && $siteName !== '' && $baseUrl !== '' && filter_var($supportEmail, FILTER_VALIDATE_EMAIL);

  $provider = strtolower(sflr_setting_value('payment_provider', 'sandbox'));
  $checkoutEnabled = sflr_setting_value('checkout_enabled', '1') === '1';
  $paymentTableReady = sf_admin_table_exists('payment_gateway_settings');
  $paymentStatus = !$checkoutEnabled ? 'manual' : (($paymentTableReady && $provider !== '' && $provider !== 'sandbox') ? 'pass' : 'warn');

  $emailTemplateCount = sflr_db_count('email_templates');
  $emailStatus = filter_var($supportEmail, FILTER_VALIDATE_EMAIL) && $emailTemplateCount > 0 ? 'pass' : 'warn';

  $backupTablesReady = sf_admin_table_exists('backup_profiles') && sf_admin_table_exists('backup_runs');
  $backupRuns = sflr_db_count('backup_runs');
  $backupStatus = $backupRuns > 0 ? 'pass' : ($backupTablesReady ? 'manual' : 'warn');

  return [
    sflr_card(is_file($lockPath) ? 'pass' : 'fail', 'Installer Lock', is_file($lockPath) ? 'storage/install.lock is present and the installer is locked.' : 'Installer lock is missing. Create/restore storage/install.lock after install.', 'install.php', 'Installer'),
    sflr_card(sf_qa_db_ready() && $adminCount > 0 ? 'pass' : 'fail', 'Admin Account', sf_qa_db_ready() ? ($adminCount > 0 ? $adminCount . ' admin account(s) found.' : 'No admin account found in users table.') : 'Database connection is unavailable.', 'admin/index.php', 'Admin'),
    sflr_card($migrationFails === 0 ? 'pass' : 'fail', 'Schema + Migrations', $migrationScore . '% schema readiness. ' . ($migrationFails === 0 ? 'No migration failures detected.' : $migrationFails . ' migration check(s) need repair.'), 'admin/migration-checker.php', 'Migrations'),
    sflr_card($settingsReady ? 'pass' : 'warn', 'Site Settings', $settingsReady ? 'Site name, base URL, and support email are configured.' : 'Review site name, base URL, support email, and runtime toggles.', 'admin/settings.php', 'Settings'),
    sflr_card($uploadIssues === 0 ? 'pass' : 'fail', 'Media / Upload Folders', $uploadIssues === 0 ? 'All required upload/config/storage folders are writable.' : $uploadIssues . ' folder(s) need permission or path review.', 'admin/uploads.php', 'Uploads'),
    sflr_card($paymentStatus, 'Payments + Checkout', $paymentStatus === 'pass' ? 'Checkout is enabled with a non-sandbox provider setting.' : ($checkoutEnabled ? 'Checkout is enabled, but payment settings should be reviewed before launch.' : 'Checkout is currently disabled.'), 'admin/payment-gateways.php', 'Payments'),
    sflr_card($emailStatus, 'Email / Notifications', $emailStatus === 'pass' ? 'Support email and email templates are available.' : 'Confirm support email and email templates before launch notifications.', 'admin/email-templates.php', 'Email'),
    sflr_card($backupStatus, 'Backups', $backupRuns > 0 ? $backupRuns . ' backup run record(s) found.' : ($backupTablesReady ? 'Backup tables are installed; create the first backup before launch.' : 'Backup tables are not fully detected.'), sf_qa_file_exists('admin/backups.php') ? 'admin/backups.php' : null, sf_qa_file_exists('admin/backups.php') ? 'Backups' : null),
    sflr_card($routeIssues === 0 ? 'pass' : 'fail', 'Critical Routes', $routeIssues === 0 ? 'Core public and admin routes are present.' : $routeIssues . ' critical route file(s) are missing.', 'admin/routes-checker.php', 'Routes'),
    sflr_card($mediaMissing === 0 ? 'pass' : 'warn', 'Installer Preview Media', $mediaMissing === 0 ? 'Installer preview images are present.' : $mediaMissing . ' installer image(s) are not committed; preserve server copies during ZIP deploy.', null, null),
  ];
}

$sections = sf_qa_all_checks();
$flatChecks = sf_qa_flatten($sections);
$overallScore = sf_qa_score($flatChecks);
$summary = sf_qa_section_summary();
$cards = sflr_launch_cards();
$readyCount = count(array_filter($cards, static fn($card) => ($card['status'] ?? '') === 'pass'));
$reviewCount = count($cards) - $readyCount;
$uploadRows = sflr_upload_dirs();
$routeRows = sflr_critical_routes();
$mediaRows = sflr_installer_media();

require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Launch Readiness', 'Admin launch readiness', 'Permanent operational checklist for moving Stonefellow from installed to launch-ready.', 'launch-readiness');
?>
<style>
.launch-readiness-page .sflr-hero{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(260px,.8fr);gap:16px;margin-bottom:18px}.launch-readiness-page .sflr-panel{padding:20px;border:1px solid rgba(232,198,127,.16);border-radius:22px;background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.025));box-shadow:0 20px 56px rgba(0,0,0,.22)}.launch-readiness-page .sflr-hero h2{margin:8px 0;color:#fff;font-size:clamp(34px,5vw,62px);letter-spacing:-.055em;line-height:.95}.launch-readiness-page .sflr-hero p,.launch-readiness-page .sflr-panel p{color:rgba(255,255,255,.68);line-height:1.55}.launch-readiness-page .sflr-kpi-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}.launch-readiness-page .sflr-kpi strong{display:block;color:#fff;font-size:34px;margin-top:8px}.launch-readiness-page .sflr-kpi span,.launch-readiness-page .sflr-card span{display:block;color:rgba(232,198,127,.84);font-size:11px;font-weight:950;letter-spacing:.1em;text-transform:uppercase}.launch-readiness-page .sflr-card-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin-bottom:18px}.launch-readiness-page .sflr-card{min-height:190px;padding:16px;border:1px solid rgba(232,198,127,.14);border-radius:18px;background:rgba(255,255,255,.04)}.launch-readiness-page .sflr-card h3{color:#fff;margin:12px 0 8px;font-size:18px}.launch-readiness-page .sflr-card p{font-size:13px;margin:0}.launch-readiness-page .sflr-card a{display:inline-flex;margin-top:13px;color:#f5d98d;font-weight:950;text-decoration:none}.launch-readiness-page .sflr-section-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px}.launch-readiness-page .sflr-section-card strong{display:block;color:#fff;font-size:28px;margin-top:8px}.launch-readiness-page .sflr-mini-table{margin-top:12px}.launch-readiness-page .sflr-actions{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px}.launch-readiness-page .sflr-actions a{padding:14px;border:1px solid rgba(232,198,127,.16);border-radius:16px;background:rgba(255,255,255,.035);color:#f5d98d;font-weight:950;text-align:center;text-decoration:none}@media(max-width:1300px){.launch-readiness-page .sflr-card-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.launch-readiness-page .sflr-kpi-grid,.launch-readiness-page .sflr-section-grid,.launch-readiness-page .sflr-actions{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:760px){.launch-readiness-page .sflr-hero,.launch-readiness-page .sflr-card-grid,.launch-readiness-page .sflr-kpi-grid,.launch-readiness-page .sflr-section-grid,.launch-readiness-page .sflr-actions{grid-template-columns:1fr}}
</style>

<section class="sflr-hero">
  <div class="sflr-panel"><span class="sf-panel-eyebrow">Launch Command</span><h2><?= (int)$overallScore ?>%</h2><p>Overall launch readiness score from environment, migrations, routes, security, and content checks. Grade: <strong><?= sf_qa_h(sf_qa_grade($overallScore)) ?></strong>.</p></div>
  <div class="sflr-panel"><span class="sf-panel-eyebrow">Checklist Status</span><h2><?= (int)$readyCount ?>/<?= count($cards) ?></h2><p><?= (int)$readyCount ?> ready item(s), <?= (int)$reviewCount ?> item(s) needing review before launch handoff.</p></div>
</section>

<section class="sflr-kpi-grid">
  <div class="sflr-panel sflr-kpi"><span>Database</span><strong><?= sf_qa_db_ready() ? 'Ready' : 'Preview' ?></strong><p><?= sf_qa_db_ready() ? 'Connected through configured PDO.' : 'No database connection available.' ?></p></div>
  <div class="sflr-panel sflr-kpi"><span>Installer Lock</span><strong><?= is_file(sf_qa_root() . '/storage/install.lock') ? 'Locked' : 'Open' ?></strong><p><?= is_file(sf_qa_root() . '/storage/install.lock') ? 'storage/install.lock found.' : 'Lock file needs review.' ?></p></div>
  <div class="sflr-panel sflr-kpi"><span>QA Checks</span><strong><?= count($flatChecks) ?></strong><p><?= sflr_count_statuses($flatChecks, ['fail','missing']) ?> fail(s), <?= sflr_count_statuses($flatChecks, ['warn','manual','preview']) ?> review item(s).</p></div>
  <div class="sflr-panel sflr-kpi"><span>Launch Cards</span><strong><?= (int)$reviewCount ?></strong><p>Items still marked review, manual, missing, or fix.</p></div>
</section>

<section class="sflr-card-grid">
  <?php foreach ($cards as $card): ?>
    <article class="sflr-card">
      <?= sflr_badge($card['status']) ?>
      <span><?= sf_qa_h(sflr_status_label($card['status'])) ?></span>
      <h3><?= sf_qa_h($card['title']) ?></h3>
      <p><?= sf_qa_h($card['detail']) ?></p>
      <?php if (!empty($card['href'])): ?><a href="<?= sf_url((string)$card['href']) ?>"><?= sf_qa_h($card['cta'] ?? 'Open') ?></a><?php endif; ?>
    </article>
  <?php endforeach; ?>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Section Scores</span><h2>Readiness by area</h2></div></div>
  <div class="sflr-section-grid">
    <?php foreach ($summary as $row): ?>
      <a class="sf-admin-action-card sflr-section-card" href="<?= sf_url('admin/qa.php') ?>">
        <span><?= sf_qa_h($row['section']) ?></span>
        <strong><?= (int)$row['score'] ?>%</strong>
        <small><?= (int)$row['count'] ?> checks · <?= (int)$row['fails'] ?> fails · <?= (int)$row['warnings'] ?> review</small>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Required Folders</span><h2>Writable paths</h2></div></div>
  <div class="sf-admin-table-wrap sflr-mini-table"><table class="sf-admin-table"><thead><tr><th>Path</th><th>Status</th><th>Detail</th></tr></thead><tbody>
    <?php foreach ($uploadRows as $row): ?><tr><td><strong><?= sf_qa_h($row['dir']) ?></strong></td><td><?= sflr_badge($row['status']) ?></td><td><?= sf_qa_h($row['detail']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Critical Routes</span><h2>Launch route file check</h2></div></div>
  <div class="sf-admin-table-wrap sflr-mini-table"><table class="sf-admin-table"><thead><tr><th>Route</th><th>Status</th><th>Detail</th></tr></thead><tbody>
    <?php foreach ($routeRows as $row): ?><tr><td><strong><?= sf_qa_h($row['label']) ?></strong><small><?= sf_qa_h($row['path']) ?></small></td><td><?= sflr_badge($row['status']) ?></td><td><?= sf_qa_h($row['detail']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Installer Media</span><h2>Server-only image preservation</h2></div></div>
  <p class="sf-admin-copy">The installer can render fallbacks for missing VP3 preview images, but production deploys should preserve <code>/assets/images/installer/</code> on the server unless those PNGs are later committed.</p>
  <div class="sf-admin-table-wrap sflr-mini-table"><table class="sf-admin-table"><thead><tr><th>Image</th><th>Status</th><th>Detail</th></tr></thead><tbody>
    <?php foreach ($mediaRows as $row): ?><tr><td><strong><?= sf_qa_h($row['path']) ?></strong></td><td><?= sflr_badge($row['status']) ?></td><td><?= sf_qa_h($row['detail']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Next Actions</span><h2>Launch tools</h2></div></div>
  <div class="sflr-actions">
    <a href="<?= sf_url('admin/qa.php') ?>">Production QA</a>
    <a href="<?= sf_url('admin/migration-checker.php') ?>">Migration Checker</a>
    <a href="<?= sf_url('admin/settings.php') ?>">Site Settings</a>
    <a href="<?= sf_url('admin/payment-gateways.php') ?>">Payments</a>
    <a href="<?= sf_url('docs/DEPLOYMENT_RUNBOOK.md') ?>">Runbook</a>
  </div>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
