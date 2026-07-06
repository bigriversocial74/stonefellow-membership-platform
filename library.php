<?php
require __DIR__ . '/includes/library.php';
$user = sf_require_login();
$member = sf_member_snapshot();
$items = sf_library_items((int)$user['id']);
$summary = sf_library_summary((int)$user['id']);
$pageTitle = 'My Library';
$pageDescription = 'Stonefellow member library for saved songs, episodes, videos, products, watchlist items, and completed content.';
$pageClass = 'member-dashboard-page membership-page';
require __DIR__ . '/includes/header.php';
?>
<section class="sf-membership-shell">
  <section class="sf-member-hero">
    <div><span class="sf-panel-eyebrow">Member Library</span><h1>Your saved Stonefellow world.</h1><p>One home for saved music, watchlist videos, episodes, merch, likes, and completed content.</p><div class="sf-episode-action-row"><a class="sf-primary-action" href="<?= sf_url('search.php') ?>">Discover More</a><a class="sf-secondary-action" href="<?= sf_url('watchlist.php') ?>">Open Watchlist</a><a class="sf-secondary-action" href="<?= sf_url('player.php') ?>">Open Player</a></div></div>
    <article class="sf-member-status-card"><span>Current Access</span><strong><?= htmlspecialchars($member['access_label']) ?></strong><small><?= (int)$summary['total'] ?> library items · <?= (int)$summary['watchlist'] ?> watchlist</small><a href="<?= sf_url('account-billing.php') ?>">Manage Billing</a></article>
  </section>
  <section class="sf-member-grid">
    <article class="sf-member-panel"><span class="sf-panel-eyebrow">Saved</span><h2><?= (int)$summary['saved'] ?></h2><p>Albums, songs, episodes, and merch saved to your member account.</p></article>
    <article class="sf-member-panel"><span class="sf-panel-eyebrow">Watchlist</span><h2><?= (int)$summary['watchlist'] ?></h2><p>Videos and episodes queued for the next session.</p></article>
    <article class="sf-member-panel"><span class="sf-panel-eyebrow">Liked</span><h2><?= (int)$summary['liked'] ?></h2><p>Tracks and moments marked as favorites.</p></article>
  </section>
  <section class="sf-member-section">
    <div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Library</span><h2>All saved content</h2></div><a href="<?= sf_url('api/library.php') ?>">API</a></div>
    <div class="sf-video-card-grid">
      <?php foreach ($items as $item): ?>
        <a class="sf-video-card" href="<?= htmlspecialchars($item['content_url'] ?? '#') ?>">
          <img src="<?= sf_asset($item['image_path'] ?? 'images/episodes/episode-01.png') ?>" alt="<?= htmlspecialchars($item['title'] ?? 'Library item') ?> artwork">
          <span><?= htmlspecialchars(ucfirst((string)($item['content_type'] ?? 'item'))) ?> · <?= htmlspecialchars(sf_access_label((string)($item['access_level'] ?? 'public'))) ?></span>
          <strong><?= htmlspecialchars($item['title'] ?? 'Stonefellow') ?></strong>
          <small><?= htmlspecialchars(ucfirst((string)($item['library_status'] ?? 'saved'))) ?><?php if ((int)($item['progress_percent'] ?? 0) > 0): ?> · <?= (int)$item['progress_percent'] ?>% complete<?php endif; ?></small>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
