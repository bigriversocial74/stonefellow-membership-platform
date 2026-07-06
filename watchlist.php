<?php
require __DIR__ . '/includes/library.php';
$user = sf_require_login();
$items = array_values(array_filter(sf_library_items((int)$user['id']), fn($item) => ($item['library_status'] ?? '') === 'watchlist' || in_array(($item['content_type'] ?? ''), ['video','episode'], true)));
$pageTitle = 'Watchlist';
$pageDescription = 'Stonefellow watchlist for queued episodes, videos, live sessions, and member-only streams.';
$pageClass = 'member-dashboard-page membership-page';
require __DIR__ . '/includes/header.php';
?>
<section class="sf-membership-shell">
  <section class="sf-member-hero">
    <div><span class="sf-panel-eyebrow">Watchlist</span><h1>Continue the next chapter.</h1><p>Queue episodes, full videos, trailers, and live sessions from search, episode pages, and the member library.</p><div class="sf-episode-action-row"><a class="sf-primary-action" href="<?= sf_url('episodes.php') ?>">Browse Episodes</a><a class="sf-secondary-action" href="<?= sf_url('library.php') ?>">My Library</a></div></div>
    <article class="sf-member-status-card"><span>Queued</span><strong><?= count($items) ?></strong><small>Episode and video records ready for playback.</small><a href="<?= sf_url('search.php?type=video') ?>">Find Videos</a></article>
  </section>
  <section class="sf-member-section">
    <div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Queue</span><h2>Watch next</h2></div></div>
    <div class="sf-video-card-grid">
      <?php foreach ($items as $item): ?>
        <a class="sf-video-card" href="<?= htmlspecialchars($item['content_url'] ?? '#') ?>"><img src="<?= sf_asset($item['image_path'] ?? 'images/episodes/episode-01.png') ?>" alt="<?= htmlspecialchars($item['title'] ?? 'Watchlist item') ?> artwork"><span><?= htmlspecialchars(ucfirst((string)($item['content_type'] ?? 'video'))) ?></span><strong><?= htmlspecialchars($item['title'] ?? 'Stonefellow') ?></strong><small><?= (int)($item['progress_percent'] ?? 0) ?>% watched</small></a>
      <?php endforeach; ?>
    </div>
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
