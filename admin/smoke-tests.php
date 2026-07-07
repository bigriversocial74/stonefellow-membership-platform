<?php
$pageTitle = 'Production Smoke Tests';
$pageDescription = 'Production smoke-test scenario matrix for auth, member runtime, media playback, commerce, admin operations, API contracts, monitoring, incidents, backups, releases, and final handoff.';
$pageClass = 'membership-page admin-catalog-page qa-page smoke-tests-page';
require __DIR__ . '/../includes/smoke_tests.php';
$checks = sf_smoke_checks();
$score = sf_smoke_score($checks);
$status = sf_smoke_status_text($checks);
$counts = sf_smoke_counts($checks);
$groups = sf_smoke_group_summary($checks);
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Smoke Tests', 'Production smoke-test matrix v1', 'Use this scenario matrix after install/migration/preflight to confirm real launch behavior across auth, member, player, watch, merch, admin ops, APIs, monitoring, incidents, backups, and releases.', 'smoke-tests');
?>
<section class="sf-admin-card-grid">
  <div class="sf-admin-action-card"><span>Smoke Score</span><strong><?= (int)$score ?>%</strong><small><?= sf_smoke_h(sf_smoke_grade($score)) ?> scenario readiness.</small></div>
  <div class="sf-admin-action-card"><span>Status</span><strong><?= sf_smoke_h(ucwords(str_replace('_', ' ', $status))) ?></strong><small><?= (int)$counts['fail'] ?> launch-blocking failures.</small></div>
  <div class="sf-admin-action-card"><span>Scenarios</span><strong><?= (int)$counts['total'] ?></strong><small><?= (int)$counts['manual'] ?> manual checks · <?= (int)$counts['warn'] ?> review items.</small></div>
  <a class="sf-admin-action-card" href="<?= sf_url('deploy/preflight.php') ?>"><span>Deploy</span><strong>Preflight</strong><small>Combined QA, package, and smoke-test gate.</small></a>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head">
    <div><span class="sf-panel-eyebrow">Scenario Groups</span><h2>Launch smoke coverage</h2></div>
    <span class="sf-admin-mini-pill">No SQL required</span>
  </div>
  <div class="sf-admin-roadmap">
    <?php foreach ($groups as $group): ?>
      <div><span><?= (int)$group['score'] ?>%</span><strong><?= sf_smoke_h($group['group']) ?></strong><p><?= (int)$group['count'] ?> scenarios · <?= (int)$group['fails'] ?> fails · <?= (int)$group['warnings'] ?> warnings · <?= (int)$group['manual'] ?> manual</p></div>
    <?php endforeach; ?>
  </div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head">
    <div><span class="sf-panel-eyebrow">Runner</span><h2>Production scenario matrix</h2></div>
    <a href="<?= sf_url('admin/package-readiness.php') ?>">Package Readiness</a>
  </div>
  <?php sf_smoke_render_table($checks); ?>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head">
    <div><span class="sf-panel-eyebrow">Handoff</span><h2>How to use this matrix</h2></div>
    <a href="<?= sf_url('docs/PHASE_38_SMOKE_TESTS.md') ?>">Phase Docs</a>
  </div>
  <div class="sf-admin-roadmap">
    <div><span>1</span><strong>Fix failures first</strong><p>Any missing route/API file is a launch blocker before packaging.</p></div>
    <div><span>2</span><strong>Review warnings</strong><p>Warnings usually mean the file exists but a JSON/helper contract was not detected.</p></div>
    <div><span>3</span><strong>Complete manual checks</strong><p>Payment webhooks, email delivery, protected media, backups, and releases require live verification.</p></div>
    <div><span>4</span><strong>Run preflight</strong><p>Preflight combines QA, package readiness, and smoke-test status into the final deploy gate.</p></div>
  </div>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
