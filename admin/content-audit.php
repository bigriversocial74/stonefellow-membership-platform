<?php
$pageTitle = 'Content Audit';
$pageDescription = 'Audit Stonefellow catalog content and local media file references.';
$pageClass = 'membership-page admin-catalog-page qa-page';
require __DIR__ . '/../includes/qa.php';
$items = sf_qa_content_audit();
$checks = sf_qa_content_checks();
$score = sf_qa_score($checks);
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Content Audit', 'Media readiness', 'Find missing album covers, audio paths, video paths, episode posters, and merch product images before launch.', 'content-audit');
?>
<section class="sf-admin-card-grid">
  <div class="sf-admin-action-card"><span>Content Score</span><strong><?= (int)$score ?>%</strong><small><?= sf_qa_h(sf_qa_grade($score)) ?> content readiness.</small></div>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/uploads.php') ?>"><span>Assets</span><strong>Upload Manager</strong><small>Add or replace missing media.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/music.php') ?>"><span>Catalog</span><strong>Media Admin</strong><small>Update albums, songs, episodes, and video records.</small></a>
</section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Checks</span><h2>Catalog completeness</h2></div></div><?php sf_qa_render_check_table($checks); ?></section>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Asset References</span><h2>Local media paths</h2></div></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Section</th><th>Item</th><th>Field</th><th>Path</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($items as $item): ?><tr><td><?= sf_qa_h($item['section'] ?? '') ?></td><td><strong><?= sf_qa_h($item['title'] ?? '') ?></strong></td><td><?= sf_qa_h($item['field'] ?? '') ?></td><td><small><?= sf_qa_h($item['path'] ?? '') ?></small></td><td><?= sf_qa_badge((string)($item['status'] ?? 'info')) ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
