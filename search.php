<?php
require __DIR__ . '/includes/search.php';
$q = trim((string)($_GET['q'] ?? ''));
$type = trim((string)($_GET['type'] ?? ''));
$results = sf_search_results($q, $type);
$facets = sf_search_facets(sf_search_results($q, ''));
$pageTitle = 'Search';
$pageDescription = 'Search Stonefellow songs, episodes, videos, albums, and merch.';
$pageClass = 'member-dashboard-page membership-page search-page';
require __DIR__ . '/includes/header.php';
?>
<section class="sf-membership-shell">
  <section class="sf-member-hero">
    <div><span class="sf-panel-eyebrow">Search + Discovery</span><h1>Find the next thing to watch, stream, or collect.</h1><p>Search songs, episodes, videos, albums, and merch from one discovery layer.</p></div>
    <article class="sf-member-status-card"><span>Results</span><strong><?= count($results) ?></strong><small><?= $q !== '' ? 'Matching “' . htmlspecialchars($q) . '”' : 'Featured catalog' ?></small><a href="<?= sf_url('library.php') ?>">My Library</a></article>
  </section>
  <section class="sf-member-section">
    <form class="sf-admin-form" method="get"><div class="sf-install-grid"><label>Search<input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="songs, episodes, merch..."></label><label>Type<select name="type"><option value="">All</option><?php foreach (['song'=>'Songs','video'=>'Videos','episode'=>'Episodes','album'=>'Albums','product'=>'Merch'] as $key=>$label): ?><option value="<?= htmlspecialchars($key) ?>"<?= $type===$key?' selected':'' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?></select></label></div><div class="sf-admin-form-actions"><button type="submit">Search</button><a href="<?= sf_url('search.php') ?>">Reset</a></div></form>
  </section>
  <section class="sf-member-grid">
    <?php foreach (['song'=>'Songs','video'=>'Videos','episode'=>'Episodes','album'=>'Albums','product'=>'Merch'] as $key=>$label): ?><a class="sf-member-panel" href="<?= sf_url('search.php?type=' . urlencode($key) . ($q!=='' ? '&q=' . urlencode($q) : '')) ?>"><span class="sf-panel-eyebrow"><?= htmlspecialchars($label) ?></span><h2><?= (int)($facets[$key] ?? 0) ?></h2><p>Filter discovery results.</p></a><?php endforeach; ?>
  </section>
  <section class="sf-member-section">
    <div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Results</span><h2><?= $type ? htmlspecialchars(ucfirst($type)) : 'All content' ?></h2></div><a href="<?= sf_url('api/search.php?q=' . urlencode($q) . '&type=' . urlencode($type)) ?>">API</a></div>
    <div class="sf-video-card-grid">
      <?php foreach ($results as $item): ?>
        <a class="sf-video-card" href="<?= htmlspecialchars($item['content_url'] ?? '#') ?>"><img src="<?= sf_asset($item['image_path'] ?? 'images/episodes/episode-01.png') ?>" alt="<?= htmlspecialchars($item['title'] ?? 'Search item') ?> artwork"><span><?= htmlspecialchars(ucfirst((string)($item['content_type'] ?? 'item'))) ?> · <?= htmlspecialchars(sf_access_label((string)($item['access_level'] ?? 'public'))) ?></span><strong><?= htmlspecialchars($item['title'] ?? 'Stonefellow') ?></strong><small><?= htmlspecialchars(substr((string)($item['description'] ?? ''),0,90)) ?></small></a>
      <?php endforeach; ?>
    </div>
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
