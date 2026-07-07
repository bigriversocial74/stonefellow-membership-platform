<?php
$pageTitle = 'Release Candidate';
$pageDescription = 'Final deploy handoff for Stonefellow release candidate, deploy ZIP, migration target, QA, package readiness, smoke tests, backup, release record, and launch decision.';
$pageClass = 'membership-page admin-catalog-page qa-page release-candidate-page';
require __DIR__ . '/../includes/release_candidate.php';
$checks = sf_rc_checks();
$summary = sf_rc_summary();
$counts = sf_rc_counts($checks);
$groups = sf_rc_group_summary($checks);
$status = sf_rc_status_text($checks);
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Release Candidate', 'Final deploy handoff v1', 'Use this as the final release candidate gate before uploading the main ZIP, applying migrations, creating backup/release records, and launching production.', 'release-candidate');
?>
<section class="sf-admin-card-grid">
  <div class="sf-admin-action-card"><span>RC Score</span><strong><?= (int)$summary['release_candidate_score'] ?>%</strong><small><?= sf_rc_h(sf_rc_grade((int)$summary['release_candidate_score'])) ?> deploy readiness.</small></div>
  <div class="sf-admin-action-card"><span>Status</span><strong><?= sf_rc_h(ucwords(str_replace('_', ' ', $status))) ?></strong><small><?= (int)$counts['fail'] ?> fails · <?= (int)$counts['warn'] ?> warnings · <?= (int)$counts['manual'] ?> manual.</small></div>
  <div class="sf-admin-action-card"><span>Migration</span><strong><?= sf_rc_h($summary['migration_target']) ?></strong><small>Base schema plus migrations 001 through <?= sf_rc_h($summary['migration_target']) ?>.</small></div>
  <a class="sf-admin-action-card" href="<?= sf_rc_h($summary['zip_url']) ?>"><span>Deploy ZIP</span><strong>Main</strong><small><?= sf_rc_h($summary['branch']) ?> branch archive.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('deploy/preflight.php') ?>"><span>Deploy</span><strong>Preflight</strong><small>Combined QA/package/smoke/RC gate.</small></a>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head">
    <div><span class="sf-panel-eyebrow">Release Candidate Scores</span><h2>Combined launch gates</h2></div>
    <span class="sf-admin-mini-pill"><?= sf_rc_h($summary['repo']) ?></span>
  </div>
  <div class="sf-admin-roadmap">
    <div><span><?= (int)$summary['qa_score'] ?>%</span><strong>Production QA</strong><p>Environment, migrations, routes, security, and content readiness.</p><a href="<?= sf_url('admin/qa.php') ?>">Open QA</a></div>
    <div><span><?= (int)$summary['package_score'] ?>%</span><strong>Package Readiness</strong><p>Required files, docs, SQL files, preflight, assets, and manifest coverage.</p><a href="<?= sf_url('admin/package-readiness.php') ?>">Open Package</a></div>
    <div><span><?= (int)$summary['smoke_score'] ?>%</span><strong>Smoke Tests</strong><p>Auth, member runtime, media playback, commerce, admin ops, API contracts, and manual checks.</p><a href="<?= sf_url('admin/smoke-tests.php') ?>">Open Smoke Tests</a></div>
    <div><span><?= (int)$summary['release_candidate_score'] ?>%</span><strong>Release Candidate</strong><p>Final deployment gate, backup/release records, deploy ZIP, and launch decision.</p><a href="<?= sf_url('admin/releases.php') ?>">Open Releases</a></div>
  </div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head">
    <div><span class="sf-panel-eyebrow">Gate Summary</span><h2>Release candidate checks</h2></div>
    <a href="<?= sf_url('docs/PHASE_39_RELEASE_CANDIDATE.md') ?>">Phase Docs</a>
  </div>
  <?php sf_rc_render_table($checks); ?>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head">
    <div><span class="sf-panel-eyebrow">Deploy Handoff</span><h2>Final sequence</h2></div>
    <a href="<?= sf_url('docs/DEPLOYMENT_RUNBOOK.md') ?>">Runbook</a>
  </div>
  <div class="sf-admin-roadmap">
    <div><span>1</span><strong>Merge to main</strong><p>Confirm the release candidate PR is merged into main and use the main ZIP archive.</p></div>
    <div><span>2</span><strong>Backup first</strong><p>Create or verify a completed backup record before replacing production files.</p></div>
    <div><span>3</span><strong>Apply SQL</strong><p>Fresh installs run the installer. Existing installs apply only missing migrations through 020.</p></div>
    <div><span>4</span><strong>Preflight + smoke</strong><p>Run deploy/preflight.php and complete manual smoke tests before opening launch traffic.</p></div>
    <div><span>5</span><strong>Record release</strong><p>Set release status, branch, SHA, backup link, migration range, deploy notes, rollback notes, and task results.</p></div>
  </div>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
