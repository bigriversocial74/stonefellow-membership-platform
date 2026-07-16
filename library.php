<?php
require __DIR__ . '/includes/library.php';
require __DIR__ . '/includes/desertrio_theme.php';
$user = sf_require_login();
$member = sf_member_snapshot();
$items = sf_library_items((int)$user['id']);
$summary = sf_library_summary((int)$user['id']);
$pageTitle = 'My Library';
$pageDescription = 'DesertRio member library for saved episodes, videos, products, watchlist items, and completed content.';
$pageClass = 'member-dashboard-page membership-page desertrio-library-template';
$pageExtraStyles = ['css/desertrio-account.css'];
require __DIR__ . '/includes/header.php';
?>
<section class="sf-membership-shell">
  <section class="sf-member-hero">
    <div><span class="sf-panel-eyebrow">Member Library</span><h1>Your saved DesertRio world.</h1><p>One home for saved episodes, videos, products, favorites, watchlist items, and completed content.</p><div class="sf-episode-action-row"><a class="sf-primary-action" href="<?= sf_url('search.php') ?>">Discover More</a><a class="sf-secondary-action" href="<?= sf_url('watchlist.php') ?>">Open Watchlist</a><a class="sf-secondary-action" href="<?= sf_url('episodes.php') ?>">Browse Episodes</a></div></div>
    <article class="sf-member-status-card"><span>Current Access</span><strong><?= htmlspecialchars($member['access_label']) ?></strong><small><?= (int)$summary['total'] ?> library items · <?= (int)$summary['watchlist'] ?> watchlist</small><a href="<?= sf_url('account-billing.php') ?>">Manage Billing</a></article>
  </section>
  <section class="sf-member-grid">
    <article class="sf-member-panel"><span class="sf-panel-eyebrow">Saved</span><h2><?= (int)$summary['saved'] ?></h2><p>Episodes, videos, products, and available media saved to your member account.</p></article>
    <article class="sf-member-panel"><span class="sf-panel-eyebrow">Watchlist</span><h2><?= (int)$summary['watchlist'] ?></h2><p>Videos and episodes queued for your next DesertRio session.</p></article>
    <article class="sf-member-panel"><span class="sf-panel-eyebrow">Liked</span><h2><?= (int)$summary['liked'] ?></h2><p>Cast moments, episodes, and available content marked as favorites.</p></article>
  </section>
  <section class="sf-member-section">
    <div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Library</span><h2>All saved content</h2></div><a href="<?= sf_url('api/library.php') ?>">API</a></div>
    <?php if ($items): ?>
      <div class="sf-video-card-grid">
        <?php foreach ($items as $item): ?>
          <a class="sf-video-card" href="<?= htmlspecialchars($item['content_url'] ?? '#') ?>">
            <img src="<?= sf_asset($item['image_path'] ?? $desertRioAssets['story_truth']) ?>" alt="<?= htmlspecialchars($item['title'] ?? 'Library item') ?> artwork">
            <span><?= htmlspecialchars(ucfirst((string)($item['content_type'] ?? 'item'))) ?> · <?= htmlspecialchars(sf_access_label((string)($item['access_level'] ?? 'public'))) ?></span>
            <strong><?= htmlspecialchars($item['title'] ?? 'DesertRio') ?></strong>
            <small><?= htmlspecialchars(ucfirst((string)($item['library_status'] ?? 'saved'))) ?><?php if ((int)($item['progress_percent'] ?? 0) > 0): ?> · <?= (int)$item['progress_percent'] ?>% complete<?php endif; ?></small>
          </a>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <article class="sf-dashboard-empty sf-dashboard-empty-wide"><strong>Your library is empty</strong><p>Save an episode or video to begin building your DesertRio member library.</p><div><a href="<?= sf_url('episodes.php') ?>">Browse Episodes</a><a href="<?= sf_url('cast.php') ?>">Meet the Cast</a></div></article>
    <?php endif; ?>
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>