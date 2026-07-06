<?php
require __DIR__ . '/includes/library.php';
require_once __DIR__ . '/includes/engagement.php';
require_once __DIR__ . '/includes/ops_scheduler_messaging.php';

$user = sf_require_login();
$member = sf_member_snapshot();
$librarySummary = sf_library_summary((int)$user['id']);
$libraryItems = sf_library_items((int)$user['id']);
$notificationSummary = sf_member_notification_summary((int)$user['id']);
$commentSummary = sf_comment_summary();
$messageUnread = count(sf_msg_member_messages((int)$user['id'], 'unread', 200));
$pageTitle = 'Member Dashboard';
$pageDescription = 'Stonefellow member dashboard for messages, notifications, comments, library, watchlist, video progress, audio history, playlists, and access status.';
$pageClass = 'member-dashboard-page membership-page';
require __DIR__ . '/includes/header.php';
?>
<section class="sf-membership-shell">
  <section class="sf-member-hero">
    <div>
      <span class="sf-panel-eyebrow">Membership</span>
      <h1>Your Stonefellow home base</h1>
      <p>One place for member access, messages, notifications, fan comments, saved library items, watchlist, episode progress, audio tracking, private playlists, and premium unlocks.</p>
      <div class="sf-episode-action-row">
        <a class="sf-primary-action" href="<?= sf_url('library.php') ?>">My Library</a>
        <a class="sf-secondary-action" href="<?= sf_url('messages.php') ?>">Messages</a>
        <a class="sf-secondary-action" href="<?= sf_url('notifications.php') ?>">Notifications</a>
        <a class="sf-secondary-action" href="<?= sf_url('account-billing.php') ?>">Billing</a>
      </div>
    </div>
    <article class="sf-member-status-card">
      <span>Current Access</span>
      <strong><?= htmlspecialchars($member['access_label']) ?></strong>
      <small><?= (int)$messageUnread ?> unread messages · <?= (int)$notificationSummary['unread'] ?> unread notifications</small>
      <a href="<?= sf_url('messages.php') ?>">Open Messages</a>
    </article>
  </section>

  <section class="sf-member-grid">
    <article class="sf-member-panel"><span class="sf-panel-eyebrow">Messages</span><h2><?= (int)$messageUnread ?></h2><p>Official member updates, launch notices, and admin messages.</p><a href="<?= sf_url('messages.php') ?>">Open Messages</a></article>
    <article class="sf-member-panel"><span class="sf-panel-eyebrow">Notifications</span><h2><?= (int)$notificationSummary['unread'] ?></h2><p>Unread member center updates for comments, account, billing, and streaming.</p><a href="<?= sf_url('notifications.php') ?>">Open Inbox</a></article>
    <article class="sf-member-panel"><span class="sf-panel-eyebrow">Comments</span><h2><?= (int)$commentSummary['comments'] ?></h2><p>Fan threads and reactions across episodes, videos, songs, and posts.</p><a href="<?= sf_url('comments.php') ?>">Open Comments</a></article>
    <article class="sf-member-panel"><span class="sf-panel-eyebrow">Library</span><h2><?= (int)$librarySummary['total'] ?></h2><p>Saved songs, videos, albums, episodes, merch, and completed content.</p><a href="<?= sf_url('library.php') ?>">Open Library</a></article>
    <article class="sf-member-panel"><span class="sf-panel-eyebrow">Watchlist</span><h2><?= (int)$librarySummary['watchlist'] ?></h2><p>Your next episodes, videos, and live sessions.</p><a href="<?= sf_url('watchlist.php') ?>">Open Watchlist</a></article>
    <article class="sf-member-panel"><span class="sf-panel-eyebrow">Playlists</span><h2><?= count($memberPlaylists) ?></h2><p>Private playlists are limited to signed-in members.</p><a href="<?= sf_url('playlists.php') ?>">Manage Playlists</a></article>
  </section>

  <section class="sf-member-section">
    <div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Continue Watching</span><h2>Resume queue</h2></div><a href="<?= sf_url('watchlist.php') ?>">Watchlist</a></div>
    <div class="sf-video-card-grid">
      <?php foreach (array_slice(array_filter($libraryItems, fn($item) => in_array(($item['content_type'] ?? ''), ['video','episode'], true)), 0, 4) as $item): ?>
        <a class="sf-video-card" href="<?= htmlspecialchars($item['content_url'] ?? '#') ?>"><img src="<?= sf_asset($item['image_path'] ?? 'images/episodes/episode-01.png') ?>" alt="<?= htmlspecialchars($item['title'] ?? 'Video') ?> poster"><span><?= htmlspecialchars(ucfirst((string)($item['content_type'] ?? 'video'))) ?></span><strong><?= htmlspecialchars($item['title'] ?? 'Stonefellow') ?></strong><small><?= (int)($item['progress_percent'] ?? 0) ?>% watched</small></a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="sf-member-section">
    <div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Private Playlists</span><h2>Member playlists</h2></div><a href="<?= sf_url('playlists.php') ?>">Create Playlist</a></div>
    <div class="sf-playlist-grid"><?php foreach ($memberPlaylists as $playlist): ?><article class="sf-member-playlist-card"><img src="<?= sf_asset($playlist['cover']) ?>" alt="<?= htmlspecialchars($playlist['title']) ?> cover"><div><strong><?= htmlspecialchars($playlist['title']) ?></strong><span><?= (int)$playlist['song_count'] ?> saved items · <?= htmlspecialchars($playlist['visibility']) ?></span></div></article><?php endforeach; ?></div>
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
