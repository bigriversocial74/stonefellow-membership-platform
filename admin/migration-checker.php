<?php
$pageTitle='Migration Checker';
$pageDescription='Verify Stonefellow SQL files, checksums, required tables, columns, and safe repair readiness.';
$pageClass='membership-page admin-catalog-page qa-page';
require __DIR__.'/../includes/qa.php';
require_once __DIR__.'/../includes/data_ops_recovery.php';
sf_sec_require('admin.settings.manage');

if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'&&($_POST['action']??'')==='repair_schema'){
    $result=sf_dor_schema_repair((string)($_POST['confirmation']??''));
    sf_admin_flash(!empty($result['ok'])?'success':'error',(string)($result['message']??'Schema repair finished.'));
    sf_admin_redirect(sf_url('admin/migration-checker.php'));
}
$checks=sf_qa_migration_checks();
$score=sf_qa_score($checks);
$drift=sf_dor_migration_drift();
$sqlResults=$_SESSION['sf_install_sql_results']??[];
global $database;$confirmation='REPAIR '.(string)($database['name']??'database');
require __DIR__.'/../includes/header.php';
sf_admin_shell_start('Migration Checker','Checksum-controlled schema readiness','Verify repository SQL checksums, database migration records, required tables, and columns. Repair applies only new migrations and never deletes the migration ledger.','migration-checker');
?>
<section class="sf-admin-card-grid">
  <div class="sf-admin-action-card"><span>Schema Score</span><strong><?= (int)$score ?>%</strong><small><?= sf_qa_h(sf_qa_grade($score)) ?></small></div>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/operations-recovery.php') ?>"><span>Operations Audit</span><strong><?= (int)sf_dor_score(sf_dor_operations_checks()) ?>%</strong><small>Backup and release gates.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('docs/SQL_FILE_MAP.md') ?>"><span>Docs</span><strong>SQL Map</strong><small>Review install order.</small></a>
</section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Controlled Repair</span><h2>Apply pending migrations</h2></div></div>
<p>Repair requires <code>SF_ALLOW_SCHEMA_REPAIR=1</code>. Production also requires maintenance mode and a fully verified backup from the previous 24 hours. A MySQL advisory lock prevents concurrent repair runs. Applied migrations with changed checksums are blocked rather than re-run.</p>
<form method="post" class="sf-admin-form"><?= sf_csrf_field() ?><input type="hidden" name="action" value="repair_schema"><label>Confirmation phrase<input name="confirmation" autocomplete="off" placeholder="<?= sf_qa_h($confirmation) ?>" required></label><div class="sf-admin-form-actions"><button type="submit" onclick="return confirm('Apply pending migrations under the protected repair gate?')">Run Protected Repair</button></div></form></section>
<?php if($sqlResults): ?><section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Last Repair</span><h2>Migration results</h2></div></div><div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Key</th><th>Status</th><th>Detail</th></tr></thead><tbody><?php foreach($sqlResults as $row): ?><tr><td><strong><?= sf_qa_h($row['key']??'') ?></strong><small><?= sf_qa_h($row['label']??'') ?></small></td><td><?= sf_qa_badge(in_array(($row['status']??''),['applied','skipped'],true)?'pass':'fail') ?></td><td><?= sf_qa_h($row['detail']??'') ?></td></tr><?php endforeach; ?></tbody></table></div></section><?php endif; ?>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Migration Ledger</span><h2>Repository files and applied checksums</h2></div></div><div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Key</th><th>File</th><th>Status</th><th>Detail</th></tr></thead><tbody><?php foreach($drift as $row): ?><tr><td><strong><?= sf_qa_h($row['key']) ?></strong></td><td><?= sf_qa_h($row['path']) ?></td><td><?= sf_qa_badge($row['status']==='current'?'pass':($row['status']==='pending'?'warn':'fail')) ?></td><td><?= sf_qa_h($row['detail']) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Schema Contract</span><h2>Required tables and columns</h2></div></div><?php sf_qa_render_check_table($checks); ?></section>
<?php sf_admin_shell_end(); require __DIR__.'/../includes/footer.php'; ?>