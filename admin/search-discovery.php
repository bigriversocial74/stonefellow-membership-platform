<?php
$pageTitle = 'Search Discovery';
$pageDescription = 'Admin search index and discovery diagnostics.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/search.php';
require __DIR__ . '/../includes/header.php';
$rows = sf_search_results('', '');
$facets = sf_search_facets($rows);
sf_admin_shell_start('Search Discovery', 'Discovery v1', 'Review searchable catalog coverage across songs, episodes, videos, albums, and merch.', 'search-discovery');
?>
<section class="sf-admin-card-grid">
  <a class="sf-admin-action-card" href="<?= sf_url('search.php') ?>"><span>Public</span><strong>Search Page</strong><small>Open discovery experience.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('api/search.php') ?>"><span>API</span><strong>Search JSON</strong><small>Search endpoint for future autocomplete.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('library.php') ?>"><span>Member</span><strong>Library</strong><small>Saved and watchlist content.</small></a>
</section>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Coverage</span><h2><?= count($rows) ?> searchable items</h2></div></div>
  <div class="sf-admin-roadmap"><?php foreach ($facets as $type=>$count): ?><div><span><?= (int)$count ?></span><strong><?= sf_admin_h(ucfirst($type)) ?></strong><p>Indexed and discoverable.</p></div><?php endforeach; ?></div>
</section>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Index Preview</span><h2>Top discovery records</h2></div></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Title</th><th>Type</th><th>Access</th><th>URL</th></tr></thead><tbody><?php foreach (array_slice($rows,0,100) as $row): ?><tr><td><strong><?= sf_admin_h($row['title'] ?? '') ?></strong><small><?= sf_admin_h($row['slug'] ?? '') ?></small></td><td><?= sf_admin_h($row['content_type'] ?? '') ?></td><td><?= sf_admin_h(sf_access_label((string)($row['access_level'] ?? 'public'))) ?></td><td><a href="<?= sf_admin_h($row['content_url'] ?? '#') ?>">Open</a></td></tr><?php endforeach; ?></tbody></table></div>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
