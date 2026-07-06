<?php
$pageTitle = 'Migration Checker';
$pageDescription = 'Verify Stonefellow base SQL, migrations, required tables, and required columns.';
$pageClass = 'membership-page admin-catalog-page qa-page';
require __DIR__ . '/../includes/qa.php';
$checks = sf_qa_migration_checks();
$score = sf_qa_score($checks);
$plan = sf_qa_migration_plan();
$requiredColumns = sf_qa_required_columns();
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Migration Checker', 'Schema readiness', 'Confirm the SQL install order, migration files, required tables, and required columns for launch.', 'migration-checker');
?>
<section class="sf-admin-card-grid">
  <div class="sf-admin-action-card"><span>Schema Score</span><strong><?= (int)$score ?>%</strong><small><?= sf_qa_h(sf_qa_grade($score)) ?> scoped schema readiness.</small></div>
  <a class="sf-admin-action-card" href="<?= sf_url('docs/SQL_FILE_MAP.md') ?>"><span>Docs</span><strong>SQL Map</strong><small>See the complete SQL file map.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/qa.php') ?>"><span>QA</span><strong>Full Report</strong><small>Back to production readiness.</small></a>
</section>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Install Order</span><h2>Base SQL + migrations</h2></div></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Order</th><th>File</th><th>Tables / Purpose</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($plan as $migration): ?>
      <?php $fileOk = sf_qa_file_exists($migration['file']); $missing = []; if (sf_qa_db_ready()) { foreach ($migration['tables'] as $table) { if (!sf_qa_table_exists($table)) { $missing[] = $table; } } } ?>
      <tr><td><strong><?= sf_qa_h($migration['key']) ?></strong></td><td><strong><?= sf_qa_h($migration['file']) ?></strong><small><?= sf_qa_h($migration['label']) ?></small></td><td><?= sf_qa_h($migration['notes']) ?><small><?= sf_qa_h(implode(', ', $migration['tables'])) ?></small></td><td><?= sf_qa_badge($fileOk && (!$missing || !sf_qa_db_ready()) ? (sf_qa_db_ready() ? 'pass' : 'preview') : 'fail') ?></td></tr>
    <?php endforeach; ?>
  </tbody></table></div>
</section>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Checks</span><h2>Table and column status</h2></div></div>
  <?php sf_qa_render_check_table($checks); ?>
</section>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Expected Columns</span><h2>Core column contract</h2></div></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Table</th><th>Required columns</th><th>Installed columns</th></tr></thead><tbody>
    <?php foreach ($requiredColumns as $table => $columns): ?><tr><td><strong><?= sf_qa_h($table) ?></strong></td><td><?= sf_qa_h(implode(', ', $columns)) ?></td><td><?= sf_qa_h(sf_qa_db_ready() ? implode(', ', sf_qa_columns($table)) : 'Database not connected') ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
