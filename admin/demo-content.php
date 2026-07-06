<?php
$pageTitle = 'Demo Content Samples';
$pageDescription = 'Review starter seed payloads and import examples for the Stonefellow platform.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/importer.php';

$types = sf_importer_types();
$groups = sf_importer_seed_groups();
$starterSeed = sf_importer_starter_seed();
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Demo Content', 'Import samples and seed payloads', 'Use these examples to format CSV/JSON imports and understand the starter seed data.', 'demo-content');
?>
<section class="sf-admin-card-grid">
  <a class="sf-admin-action-card" href="<?= sf_url('admin/import.php') ?>"><span>Import</span><strong>Open Importer</strong><small>Upload CSV/JSON or paste rows.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/seed-manager.php') ?>"><span>Seeds</span><strong>Run Seeds</strong><small>Commit starter catalog content and rollback batches.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('database/seeds/starter_catalog.json') ?>"><span>JSON</span><strong>Starter Seed File</strong><small>Open the checked-in starter payload.</small></a>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Supported Types</span><h2><?= count($types) ?> import types</h2></div></div>
  <div class="sf-admin-table-wrap">
    <table class="sf-admin-table">
      <thead><tr><th>Type</th><th>Label</th><th>Sample JSON</th></tr></thead>
      <tbody>
        <?php foreach ($types as $type => $label): ?>
          <tr>
            <td><strong><?= sf_admin_h($type) ?></strong></td>
            <td><?= sf_admin_h($label) ?></td>
            <td><pre class="sf-admin-table-pre"><?= sf_admin_h(json_encode(sf_importer_sample_rows($type), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Seed Payloads</span><h2>Starter catalog payload</h2></div></div>
  <?php foreach ($groups as $key => $group): ?>
    <article class="sf-admin-subpanel">
      <h3><?= sf_admin_h($group['label'] ?? $key) ?></h3>
      <p><?= sf_admin_h($group['description'] ?? '') ?></p>
    </article>
  <?php endforeach; ?>
  <pre class="sf-admin-code"><code><?= sf_admin_h(json_encode($starterSeed['rows'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></code></pre>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
