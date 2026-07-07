<?php
$pageTitle = 'Package Readiness';
$pageDescription = 'Verify Stonefellow script package completeness, deploy manifest, route coverage, SQL files, docs, assets, smoke-test coverage, and production handoff readiness.';
$pageClass = 'membership-page admin-catalog-page qa-page package-readiness-page';
require __DIR__ . '/../includes/package_readiness.php';
$checks = sf_pkg_checks();
$score = sf_pkg_score($checks);
$status = sf_pkg_status_text($checks);
$fails = count(array_filter($checks, static fn($check) => in_array(($check['status'] ?? ''), ['fail','missing'], true)));
$reviews = count(array_filter($checks, static fn($check) => in_array(($check['status'] ?? ''), ['warn','preview','manual'], true)));
$manifest = sf_pkg_manifest_summary();
$groups = sf_pkg_group_summary($checks);
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Package Readiness', 'Script package readiness v1', 'Verify the deployable script package before upload: files, folders, SQL order, route registry, docs, preflight, smoke tests, monitoring, incidents, backup, release, and launch handoff.', 'package-readiness');
?>
<section class="sf-admin-card-grid">
  <div class="sf-admin-action-card"><span>Package Score</span><strong><?= (int)$score ?>%</strong><small><?= sf_pkg_h(sf_pkg_grade($score)) ?> package readiness.</small></div>
  <div class="sf-admin-action-card"><span>Status</span><strong><?= sf_pkg_h(ucwords(str_replace('_', ' ', $status))) ?></strong><small><?= $fails ? 'Resolve failures before packaging.' : 'No blocking package failures detected.' ?></small></div>
  <div class="sf-admin-action-card"><span>Files</span><strong><?= (int)$manifest['present'] ?> / <?= (int)$manifest['total'] ?></strong><small><?= (int)$manifest['missing'] ?> required files missing.</small></div>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/smoke-tests.php') ?>"><span>Smoke</span><strong>Tests</strong><small>Scenario matrix before final preflight.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('deploy/preflight.php') ?>"><span>Deploy</span><strong>Preflight</strong><small>Plain text CLI/browser output for final check.</small></a>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head">
    <div><span class="sf-panel-eyebrow">Package Manifest</span><h2>Required package groups</h2></div>
    <span class="sf-admin-mini-pill">Target migration 020</span>
  </div>
  <div class="sf-admin-roadmap">
    <?php foreach ($groups as $group): ?>
      <div><span><?= (int)$group['score'] ?>%</span><strong><?= sf_pkg_h($group['group']) ?></strong><p><?= (int)$group['count'] ?> checks · <?= (int)$group['fails'] ?> fails · <?= (int)$group['warnings'] ?> review items</p></div>
    <?php endforeach; ?>
  </div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head">
    <div><span class="sf-panel-eyebrow">Manifest Files</span><h2>Deployable file list</h2></div>
    <span class="sf-admin-mini-pill"><?= sf_pkg_h(sf_admin_format_bytes($manifest['bytes'])) ?></span>
  </div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Group</th><th>Path</th><th>Status</th><th>Size</th><th>SHA-256</th></tr></thead><tbody>
    <?php foreach (sf_pkg_file_manifest() as $row): ?>
      <tr><td><?= sf_pkg_h($row['group']) ?></td><td><strong><?= sf_pkg_h($row['path']) ?></strong></td><td><?= sf_pkg_badge($row['exists'] ? 'pass' : 'fail') ?></td><td><?= sf_pkg_h(sf_admin_format_bytes($row['size'])) ?></td><td><small><?= sf_pkg_h($row['sha256'] ? substr($row['sha256'], 0, 16) . '…' : '—') ?></small></td></tr>
    <?php endforeach; ?>
  </tbody></table></div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head">
    <div><span class="sf-panel-eyebrow">Checks</span><h2>Package readiness report</h2></div>
    <a href="<?= sf_url('admin/qa.php') ?>">Full QA</a>
  </div>
  <?php sf_pkg_render_check_table($checks); ?>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head">
    <div><span class="sf-panel-eyebrow">Handoff</span><h2>Next release actions</h2></div>
    <a href="<?= sf_url('docs/PHASE_38_SMOKE_TESTS.md') ?>">Smoke Docs</a>
  </div>
  <div class="sf-admin-roadmap">
    <div><span>1</span><strong>Confirm SQL</strong><p>Existing installs apply missing migrations sequentially through migration 020.</p></div>
    <div><span>2</span><strong>Run smoke tests</strong><p>Open admin/smoke-tests.php and resolve missing route/API failures before final preflight.</p></div>
    <div><span>3</span><strong>Run preflight</strong><p>Use deploy/preflight.php from browser or CLI and resolve blocking failures.</p></div>
    <div><span>4</span><strong>Create backup/release</strong><p>Record backup readiness plus release branch, SHA, migration range, notes, rollback plan, and smoke-test status.</p></div>
  </div>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
