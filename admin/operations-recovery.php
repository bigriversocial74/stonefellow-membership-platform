<?php
$pageTitle='Operations & Recovery';
$pageDescription='Stonefellow data integrity, migration drift, backup verification, release gates, monitoring, and recovery readiness.';
$pageClass='membership-page admin-catalog-page';
require __DIR__.'/../includes/admin_catalog.php';
require_once __DIR__.'/../includes/data_ops_recovery.php';
sf_sec_require('admin.ops.manage');
$checks=sf_dor_operations_checks();
$score=sf_dor_score($checks);
$sections=sf_dor_section_summary($checks);
$drift=sf_dor_migration_drift();
$backup=sf_dor_latest_verified_backup(24);
$release=sf_rel_releases(1)[0]??null;
$releaseGate=$release?sf_dor_release_gate((int)$release['id']):null;
require __DIR__.'/../includes/header.php';
sf_admin_shell_start('Operations','Data integrity, operations & recovery audit v1','Verify migration checksums, relational integrity, backup evidence, release gates, environment safety, monitoring, and recovery readiness.','operations-recovery');
?>
<section class="sf-admin-card-grid">
  <a class="sf-admin-action-card" href="<?= sf_url('admin/platform-certification.php') ?>"><span>Platform</span><strong>Certification</strong><small>Source score and deployed launch evidence.</small></a>
  <div class="sf-admin-action-card"><span>Audit Score</span><strong><?= (int)$score ?>%</strong><small><?= $score>=97?'10/10 static readiness':'Resolve blocking checks' ?></small></div>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/migration-checker.php') ?>"><span>Migrations</span><strong><?= count($drift) ?></strong><small>Checksum-controlled SQL files.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/backups.php') ?>"><span>Verified Backup</span><strong><?= $backup?'Ready':'Missing' ?></strong><small><?= $backup?sf_admin_h($backup['run_key']):'Required for production repair/release' ?></small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/releases.php') ?>"><span>Release Gate</span><strong><?= $releaseGate&&$releaseGate['ok']?'Pass':'Review' ?></strong><small><?= $release?sf_admin_h($release['release_label']):'No release record' ?></small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/monitoring.php') ?>"><span>Monitoring</span><strong>Open</strong><small>Errors, jobs, payments, and incidents.</small></a>
</section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Section Scores</span><h2>Operational readiness</h2></div></div><div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Section</th><th>Score</th><th>Checks</th><th>Failures</th><th>Review</th></tr></thead><tbody><?php foreach($sections as $section): ?><tr><td><strong><?= sf_admin_h($section['section']) ?></strong></td><td><?= (int)$section['score'] ?>%</td><td><?= (int)$section['count'] ?></td><td><?= (int)$section['fails'] ?></td><td><?= (int)$section['warnings'] ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Audit Checks</span><h2><?= count($checks) ?> checks</h2></div></div><div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Section</th><th>Check</th><th>Status</th><th>Detail</th></tr></thead><tbody><?php foreach($checks as $check): ?><tr><td><?= sf_admin_h($check['section']) ?></td><td><strong><?= sf_admin_h($check['label']) ?></strong><small><?= sf_admin_h($check['key']) ?></small></td><td><?= sf_qa_badge($check['status']) ?></td><td><?= sf_admin_h($check['detail']) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Migration Ledger</span><h2>Repository ↔ database checksums</h2></div></div><div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Key</th><th>File</th><th>Status</th><th>Applied</th></tr></thead><tbody><?php foreach($drift as $row): ?><tr><td><strong><?= sf_admin_h($row['key']) ?></strong></td><td><?= sf_admin_h($row['path']) ?></td><td><?= sf_qa_badge($row['status']==='current'?'pass':($row['status']==='pending'?'warn':'fail')) ?></td><td><?= sf_admin_h($row['applied']['applied_at']??'—') ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php sf_admin_shell_end(); require __DIR__.'/../includes/footer.php'; ?>
