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
$memberPlaylists = $memberPlaylists ?? [];

$videoQueue = array_values(array_filter($libraryItems, static fn($item) => in_array(($item['content_type'] ?? ''), ['video','episode'], true)));
$songQueue = array_values(array_filter($libraryItems, static fn($item) => in_array(($item['content_type'] ?? ''), ['song','album'], true)));
if (!$songQueue && !empty($catalogSongs)) {
  foreach (array_slice($catalogSongs, 0, 4) as $song) {
    $songQueue[] = [
      'content_type' => 'song',
      'title' => $song['title'] ?? 'Stonefellow Song',
      'image_path' => $song['cover'] ?? 'images/music/soundtrack-cover.png',
      'content_url' => sf_url('song.php?slug=' . urlencode((string)($song['slug'] ?? ''))),
      'progress_percent' => 0,
      'meta' => $song['episode_short'] ?? ($song['episode'] ?? 'Stonefellow'),
    ];
  }
}

$periodEnd = $member['period_end'] ?? null;
$billingLabel = $periodEnd ? ('Renews ' . date('M j, Y', strtotime((string)$periodEnd))) : 'Billing date not set';
$memberName = trim((string)($member['display_name'] ?? '')) ?: 'Stonefellow Member';
$statCards = [
  ['label' => 'Messages', 'value' => (int)$messageUnread, 'text' => 'Official updates', 'href' => 'messages.php', 'cta' => 'Open'],
  ['label' => 'Notifications', 'value' => (int)$notificationSummary['unread'], 'text' => 'Unread alerts', 'href' => 'notifications.php', 'cta' => 'Inbox'],
  ['label' => 'Comments', 'value' => (int)$commentSummary['comments'], 'text' => 'Fan threads', 'href' => 'comments.php', 'cta' => 'View'],
  ['label' => 'Library', 'value' => (int)$librarySummary['total'], 'text' => 'Saved items', 'href' => 'library.php', 'cta' => 'Library'],
  ['label' => 'Watchlist', 'value' => (int)$librarySummary['watchlist'], 'text' => 'Next up', 'href' => 'watchlist.php', 'cta' => 'Watch'],
  ['label' => 'Playlists', 'value' => count($memberPlaylists), 'text' => 'Private lists', 'href' => 'playlists.php', 'cta' => 'Manage'],
];
$pageTitle = 'Member Dashboard';
$pageDescription = 'Stonefellow member dashboard for messages, notifications, comments, library, watchlist, video progress, audio history, playlists, and access status.';
$pageClass = 'member-dashboard-page membership-page';
require __DIR__ . '/includes/header.php';
?>
<section class="sf-membership-shell sf-member-dashboard">
  <section class="sf-member-overview">
    <div class="sf-member-welcome-card">
      <span class="sf-panel-eyebrow">Member Home</span>
      <h1>Welcome back, <?= htmlspecialchars($memberName) ?></h1>
      <p>Continue watching, pick up recent music, manage private playlists, and keep your Stonefellow access in one clean dashboard.</p>
    </div>
    <article class="sf-member-access-card">
      <span>Current Access</span>
      <strong><?= htmlspecialchars($member['access_label']) ?></strong>
      <small><?= htmlspecialchars(ucfirst((string)($member['status'] ?? 'active'))) ?> · <?= htmlspecialchars($billingLabel) ?></small>
      <div><a href="<?= sf_url('account-billing.php') ?>">Billing</a><a href="<?= sf_url('support.php') ?>">Support</a></div>
    </article>
  </section>

  <section class="sf-member-stat-grid" aria-label="Member dashboard stats">
    <?php foreach ($statCards as $card): ?>
      <article class="sf-dashboard-stat">
        <span><?= htmlspecialchars($card['label']) ?></span>
        <strong><?= (int)$card['value'] ?></strong>
        <small><?= htmlspecialchars($card['text']) ?></small>
        <a href="<?= sf_url($card['href']) ?>"><?= htmlspecialchars($card['cta']) ?></a>
      </article>
    <?php endforeach; ?>
  </section>

  <section class="sf-member-section sf-dashboard-continue">
    <div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Continue Watching</span><h2>Resume queue</h2></div><a href="<?= sf_url('watchlist.php') ?>">Watchlist</a></div>
    <div class="sf-video-card-grid">
      <?php foreach (array_slice($videoQueue, 0, 4) as $item): ?>
        <a class="sf-video-card" href="<?= htmlspecialchars($item['content_url'] ?? '#') ?>"><img src="<?= sf_asset($item['image_path'] ?? 'images/episodes/episode-01.png') ?>" alt="<?= htmlspecialchars($item['title'] ?? 'Video') ?> poster"><span><?= htmlspecialchars(ucfirst((string)($item['content_type'] ?? 'video'))) ?></span><strong><?= htmlspecialchars($item['title'] ?? 'Stonefellow') ?></strong><small><?= (int)($item['progress_percent'] ?? 0) ?>% watched</small></a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="sf-dashboard-content-grid">
    <section class="sf-member-section sf-dashboard-panel">
      <div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Recently Played</span><h2>Music queue</h2></div><a href="<?= sf_url('player.php') ?>">Open Player</a></div>
      <div class="sf-recent-music-list">
        <?php foreach (array_slice($songQueue, 0, 4) as $item): ?>
          <a class="sf-recent-music-row" href="<?= htmlspecialchars($item['content_url'] ?? '#') ?>">
            <img src="<?= sf_asset($item['image_path'] ?? 'images/music/soundtrack-cover.png') ?>" alt="<?= htmlspecialchars($item['title'] ?? 'Song') ?> cover">
            <span><strong><?= htmlspecialchars($item['title'] ?? 'Stonefellow') ?></strong><small><?= htmlspecialchars((string)($item['meta'] ?? ucfirst((string)($item['content_type'] ?? 'song')))) ?></small></span>
            <b>▶</b>
          </a>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="sf-member-section sf-dashboard-panel">
      <div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Private Playlists</span><h2>Member playlists</h2></div><a href="<?= sf_url('playlists.php') ?>">Create Playlist</a></div>
      <div class="sf-playlist-grid"><?php foreach ($memberPlaylists as $playlist): ?><article class="sf-member-playlist-card"><img src="<?= sf_asset($playlist['cover']) ?>" alt="<?= htmlspecialchars($playlist['title']) ?> cover"><div><strong><?= htmlspecialchars($playlist['title']) ?></strong><span><?= (int)$playlist['song_count'] ?> saved items · <?= htmlspecialchars($playlist['visibility']) ?></span></div></article><?php endforeach; ?></div>
    </section>
  </section>

  <section class="sf-member-section sf-dashboard-actions">
    <div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Account & Access</span><h2>Member controls</h2></div><a href="<?= sf_url('account.php') ?>">Account Settings</a></div>
    <div class="sf-dashboard-action-grid">
      <a href="<?= sf_url('library.php') ?>"><strong>Library</strong><span>Saved songs, videos, merch, and completed content.</span></a>
      <a href="<?= sf_url('account-billing.php') ?>"><strong>Billing</strong><span><?= htmlspecialchars($member['access_label']) ?> access · <?= htmlspecialchars($billingLabel) ?>.</span></a>
      <a href="<?= sf_url('messages.php') ?>"><strong>Messages</strong><span><?= (int)$messageUnread ?> unread official member messages.</span></a>
      <a href="<?= sf_url('support.php') ?>"><strong>Support</strong><span>Get help with access, billing, streaming, or account issues.</span></a>
    </div>
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>