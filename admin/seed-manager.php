<?php
$pageTitle = 'Seed Manager';
$pageDescription = 'Run starter content seeds and rollback import batches.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/importer.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string)($_POST['action'] ?? '');
  if (!sf_importer_tables_ready()) {
    sf_admin_flash('warning', 'Import tables are not ready. Run migration 011 first.');
    sf_admin_redirect(sf_url('admin/seed-manager.php'));
  }
  if ($action === 'run_seed') {
    $group = (string)($_POST['seed_group'] ?? '');
    $result = sf_importer_run_seed_group($group);
    sf_admin_flash(!empty($result['ok']) ? 'success' : 'error', $result['message'] ?? 'Seed run completed.');
    sf_admin_redirect(sf_url('admin/seed-manager.php'));
  }
  if ($action === 'rollback_batch') {
    $batchId = sf_admin_int($_POST['batch_id'] ?? null, 0) ?? 0;
    $result = sf_importer_rollback_batch($batchId);
    sf_admin_flash(!empty($result['ok']) ? 'success' : 'error', $result['message'] ?? 'Rollback completed.');
    sf_admin_redirect(sf_url('admin/seed-manager.php'));
  }
}

$groups = sf_importer_seed_groups();
$batches = sf_importer_batches(40);
$selectedBatchId = sf_admin_int($_GET['batch'] ?? null, 0) ?? 0;
$batchRows = $selectedBatchId > 0 ? sf_importer_batch_rows($selectedBatchId) : [];
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Seeds + Rollback', 'Content seed manager', 'Run starter catalog content, review import history, and rollback imported rows when needed.', 'seed-manager');
?>
<section class="sf-admin-card-grid">
  <a class="sf-admin-action-card" href="<?= sf_url('admin/import.php') ?>"><span>Import</span><strong>CSV/JSON Import</strong><small>Preview and commit custom content rows.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/demo-content.php') ?>"><span>Samples</span><strong>Demo Content</strong><small>View JSON samples and starter payloads.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('docs/CONTENT_IMPORT_SEED_MANAGER_V1.md') ?>"><span>Docs</span><strong>Import Guide</strong><small>Supported types, rollback rules, and SQL notes.</small></a>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Seed Groups</span><h2><?= count($groups) ?> available</h2></div></div>
  <div class="sf-admin-card-grid">
    <?php foreach ($groups as $key => $group): ?>
      <form class="sf-admin-action-card" method="post">
        <?= sf_csrf_field() ?>
        <input type="hidden" name="action" value="run_seed">
        <input type="hidden" name="seed_group" value="<?= sf_admin_h($key) ?>">
        <span><?= sf_admin_h($key) ?></span>
        <strong><?= sf_admin_h($group['label'] ?? $key) ?></strong>
        <small><?= sf_admin_h($group['description'] ?? '') ?></small>
        <button type="submit" onclick="return confirm('Run this seed group? Existing rows will be updated by slug/path keys.')"<?= sf_admin_form_disabled_attr() ?>>Run Seed</button>
      </form>
    <?php endforeach; ?>
  </div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Import History</span><h2><?= count($batches) ?> recent batches</h2></div></div>
  <div class="sf-admin-table-wrap">
    <table class="sf-admin-table">
      <thead><tr><th>Batch</th><th>Type</th><th>Status</th><th>Rows</th><th>Inserted</th><th>Updated</th><th>Skipped</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($batches as $batch): ?>
          <tr>
            <td><strong>#<?= (int)$batch['id'] ?></strong><small><?= sf_admin_h($batch['source_name'] ?? '') ?></small></td>
            <td><?= sf_admin_h($batch['import_type'] ?? '') ?></td>
            <td><?= sf_admin_status_badge((string)($batch['status'] ?? 'review')) ?></td>
            <td><?= (int)($batch['total_rows'] ?? 0) ?></td>
            <td><?= (int)($batch['inserted_count'] ?? 0) ?></td>
            <td><?= (int)($batch['updated_count'] ?? 0) ?></td>
            <td><?= (int)($batch['skipped_count'] ?? 0) ?></td>
            <td><a href="<?= sf_url('admin/seed-manager.php?batch=' . (int)$batch['id']) ?>">Rows</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<?php if ($selectedBatchId > 0): ?>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head">
    <div><span class="sf-panel-eyebrow">Batch Rows</span><h2>Batch #<?= (int)$selectedBatchId ?></h2></div>
    <form method="post">
      <?= sf_csrf_field() ?>
      <input type="hidden" name="action" value="rollback_batch">
      <input type="hidden" name="batch_id" value="<?= (int)$selectedBatchId ?>">
      <button class="sf-admin-danger" type="submit" onclick="return confirm('Rollback this import batch? Inserted rows will be deleted and updated rows restored.')">Rollback Batch</button>
    </form>
  </div>
  <div class="sf-admin-table-wrap">
    <table class="sf-admin-table">
      <thead><tr><th>Row</th><th>Action</th><th>Status</th><th>Table</th><th>Record</th><th>Unique Key</th><th>Error</th></tr></thead>
      <tbody>
        <?php foreach ($batchRows as $row): ?>
          <tr>
            <td>#<?= (int)$row['row_number'] ?></td>
            <td><?= sf_admin_h($row['import_action'] ?? '') ?></td>
            <td><?= sf_admin_status_badge((string)($row['import_status'] ?? 'info')) ?></td>
            <td><?= sf_admin_h($row['entity_table'] ?? '') ?></td>
            <td><?= (int)($row['entity_id'] ?? 0) ?></td>
            <td><small><?= sf_admin_h($row['unique_key_value'] ?? '') ?></small></td>
            <td><?= sf_admin_h($row['error_message'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php endif; ?>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
