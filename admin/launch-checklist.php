<?php
$pageTitle = 'Launch Checklist';
$pageDescription = 'Post-install Stonefellow launch checklist and final production QA pass.';
$pageClass = 'membership-page admin-catalog-page qa-page';
require __DIR__ . '/../includes/qa.php';
$sections = sf_qa_all_checks();
$flat = sf_qa_flatten($sections);
$score = sf_qa_score($flat);
$fails = count(array_filter($flat, static fn($check) => in_array(($check['status'] ?? ''), ['fail','missing'], true)));
$reviews = count(array_filter($flat, static fn($check) => in_array(($check['status'] ?? ''), ['warn','preview','manual'], true)));
$launchItems = sf_qa_launch_checklist();
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Launch Checklist', 'Final install QA pass', 'Use this post-install checklist after upload, SQL install, admin creation, content import, payment setup, and media protection.', 'launch-checklist');
?>
<section class="sf-admin-card-grid">
  <div class="sf-admin-action-card"><span>QA Score</span><strong><?= (int)$score ?>%</strong><small><?= sf_qa_h(sf_qa_grade($score)) ?> scoped readiness.</small></div>
  <div class="sf-admin-action-card"><span>Failures</span><strong><?= (int)$fails ?></strong><small>Must be zero before public launch.</small></div>
  <div class="sf-admin-action-card"><span>Review Items</span><strong><?= (int)$reviews ?></strong><small>Manual/preview/warning items.</small></div>
  <a class="sf-admin-action-card" href="<?= sf_url('deploy/preflight.php') ?>"><span>Deploy</span><strong>Preflight</strong><small>Browser/CLI launch check output.</small></a>
</section>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Post-install path</span><h2>One clean sequence</h2></div><a href="<?= sf_url('docs/DEPLOYMENT_RUNBOOK.md') ?>">Runbook</a></div>
  <div class="sf-admin-roadmap">
    <?php foreach ($launchItems as $index => $item): ?>
      <div><span><?= (int)($index + 1) ?></span><strong><?= sf_qa_h($item['title']) ?></strong><p><?= sf_qa_h($item['detail']) ?></p><a href="<?= sf_qa_h(sf_url($item['url'])) ?>"><?= sf_qa_h($item['cta']) ?></a></div>
    <?php endforeach; ?>
  </div>
</section>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Final QA</span><h2>Current report</h2></div><a href="<?= sf_url('admin/qa.php') ?>">Full QA</a></div>
  <?php sf_qa_render_check_table($flat, true); ?>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
