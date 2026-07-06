<?php
$pageTitle = 'Routes Checker';
$pageDescription = 'Verify Stonefellow public pages, admin pages, and API endpoint files.';
$pageClass = 'membership-page admin-catalog-page qa-page';
require __DIR__ . '/../includes/qa.php';
$checks = sf_qa_route_checks();
$score = sf_qa_score($checks);
$public = sf_qa_public_routes();
$admin = sf_qa_admin_routes();
$api = sf_qa_api_routes();
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Routes Checker', 'Route matrix', 'Confirm public pages, member/billing pages, admin sections, and JSON APIs are present and wired into the correct page contracts.', 'routes-checker');
?>
<section class="sf-admin-card-grid">
  <div class="sf-admin-action-card"><span>Route Score</span><strong><?= (int)$score ?>%</strong><small><?= sf_qa_h(sf_qa_grade($score)) ?> route readiness.</small></div>
  <div class="sf-admin-action-card"><span>Public</span><strong><?= count($public) ?></strong><small>Public/member/commerce page routes.</small></div>
  <div class="sf-admin-action-card"><span>Admin</span><strong><?= count($admin) ?></strong><small>Admin management pages.</small></div>
  <div class="sf-admin-action-card"><span>API</span><strong><?= count($api) ?></strong><small>JSON endpoint contracts.</small></div>
</section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Checks</span><h2>Route status</h2></div></div><?php sf_qa_render_check_table($checks); ?></section>
<section class="sf-admin-two-col sf-admin-two-col-wide">
  <article class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Public</span><h2>Page routes</h2></div></div><div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Route</th><th>Label</th><th>Type</th><th>Status</th></tr></thead><tbody><?php foreach ($public as $r): ?><tr><td><strong><?= sf_qa_h($r['path']) ?></strong></td><td><?= sf_qa_h($r['label']) ?></td><td><?= sf_qa_h($r['type']) ?></td><td><?= sf_qa_badge(sf_qa_file_exists($r['path']) ? 'pass' : 'fail') ?></td></tr><?php endforeach; ?></tbody></table></div></article>
  <aside class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">API</span><h2>JSON endpoints</h2></div></div><div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Endpoint</th><th>Method</th><th>Status</th></tr></thead><tbody><?php foreach ($api as $r): ?><tr><td><strong><?= sf_qa_h($r['path']) ?></strong><small><?= sf_qa_h($r['label']) ?></small></td><td><?= sf_qa_h($r['method']) ?></td><td><?= sf_qa_badge(sf_qa_file_exists($r['path']) ? 'pass' : 'fail') ?></td></tr><?php endforeach; ?></tbody></table></div></aside>
</section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Admin</span><h2>Management pages</h2></div></div><div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Route</th><th>Label</th><th>Protection</th></tr></thead><tbody><?php foreach ($admin as $r): ?><tr><td><strong><?= sf_qa_h($r['path']) ?></strong></td><td><?= sf_qa_h($r['label']) ?></td><td><?= sf_qa_badge(sf_qa_file_exists($r['path']) ? 'pass' : 'fail') ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
