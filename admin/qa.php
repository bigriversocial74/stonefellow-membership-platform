<?php
$pageTitle = 'Production QA';
$pageDescription = 'Stonefellow production readiness, launch scoring, and QA harness.';
$pageClass = 'membership-page admin-catalog-page qa-page';
require __DIR__ . '/../includes/qa.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
    sf_admin_flash('error', 'Security check failed. Refresh and try again.');
    sf_admin_redirect();
  }
  $runId = sf_qa_persist_run('manual');
  if ($runId) {
    sf_admin_flash('success', 'QA run saved with ID #' . $runId . '.');
  } else {
    sf_admin_flash('warning', 'QA report generated in preview mode. Run migration 010 with a database connection to persist QA history.');
  }
  sf_admin_redirect();
}

$sections = sf_qa_all_checks();
$flat = sf_qa_flatten($sections);
$score = sf_qa_score($flat);
$summary = sf_qa_section_summary();
$recentRuns = sf_qa_recent_runs();

require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Production QA', 'Readiness harness', 'Run launch checks across environment, migrations, routes, security, and content before deployment.', 'qa');
?>
<section class="sf-admin-card-grid">
  <div class="sf-admin-action-card"><span>Overall Score</span><strong><?= (int)$score ?>%</strong><small>Scoped grade: <?= sf_qa_h(sf_qa_grade($score)) ?></small></div>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/migration-checker.php') ?>"><span>Schema</span><strong>Migrations</strong><small>Base SQL plus migrations 001–010.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/routes-checker.php') ?>"><span>Routes</span><strong>Pages + APIs</strong><small>Public, admin, and JSON endpoint matrix.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/security-check.php') ?>"><span>Security</span><strong>Hardening</strong><small>Admin gates, CSRF, tokens, uploads, webhooks.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/content-audit.php') ?>"><span>Content</span><strong>Asset Audit</strong><small>Missing images, audio, video, posters, and merch media.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('docs/DEPLOYMENT_RUNBOOK.md') ?>"><span>Launch</span><strong>Runbook</strong><small>Install order, checks, rollback, and handoff notes.</small></a>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head">
    <div><span class="sf-panel-eyebrow">Run Controls</span><h2>Save a QA run</h2></div>
  </div>
  <form class="sf-admin-form" method="post">
    <?= sf_csrf_field() ?>
    <p class="sf-admin-copy">This page always calculates the live report. With database migration <code>010_production_readiness_qa_harness.sql</code> installed, it also persists the run and individual check results for launch history.</p>
    <div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>>Save QA Run</button></div>
  </form>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Section Scores</span><h2>Readiness by area</h2></div></div>
  <div class="sf-admin-card-grid sf-admin-qa-mini-grid">
    <?php foreach ($summary as $row): ?>
      <a class="sf-admin-action-card" href="<?= sf_url('admin/' . (strtolower($row['section']) === 'migrations' ? 'migration-checker' : (strtolower($row['section']) === 'routes' ? 'routes-checker' : (strtolower($row['section']) === 'security' ? 'security-check' : (strtolower($row['section']) === 'content' ? 'content-audit' : 'qa')))) . '.php') ?>">
        <span><?= sf_qa_h($row['section']) ?></span>
        <strong><?= (int)$row['score'] ?>%</strong>
        <small><?= (int)$row['count'] ?> checks · <?= (int)$row['fails'] ?> fails · <?= (int)$row['warnings'] ?> review items</small>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Current Report</span><h2>All checks</h2></div></div>
  <?php sf_qa_render_check_table($flat, true); ?>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">History</span><h2>Recent saved runs</h2></div></div>
  <?php if ($recentRuns): ?>
    <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>ID</th><th>Type</th><th>Score</th><th>Status</th><th>Created</th></tr></thead><tbody>
      <?php foreach ($recentRuns as $run): ?><tr><td>#<?= (int)$run['id'] ?></td><td><?= sf_qa_h($run['run_type'] ?? '') ?></td><td><strong><?= (int)($run['score'] ?? 0) ?>%</strong></td><td><?= sf_admin_status_badge(($run['status'] ?? '') === 'passed' ? 'active' : 'draft') ?></td><td><?= sf_qa_h($run['created_at'] ?? '') ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
  <?php else: ?>
    <p class="sf-admin-copy">No saved QA runs yet. This is expected in static/no-database mode or before migration 010 is installed.</p>
  <?php endif; ?>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
