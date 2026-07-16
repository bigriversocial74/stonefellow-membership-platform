<?php
require __DIR__ . '/includes/library.php';
require_once __DIR__ . '/includes/engagement.php';
require_once __DIR__ . '/includes/ops_scheduler_messaging.php';
require __DIR__ . '/includes/desertrio_theme.php';

$user = sf_require_login();
$member = sf_member_snapshot();
$librarySummary = sf_library_summary((int)$user['id']);
$libraryItems = sf_library_items((int)$user['id']);
$notificationSummary = sf_member_notification_summary((int)$user['id']);
$commentSummary = sf_comment_summary();
$messageUnread = count(sf_msg_member_messages((int)$user['id'], 'unread', 200));
$memberPlaylists = sf_member_playlists((int)$user['id']);

$videoQueue = array_values(array_filter($libraryItems, static fn($item) => in_array(($item['content_type'] ?? ''), ['video','episode'], true)));
$songQueue = array_values(array_filter($libraryItems, static fn($item) => in_array(($item['content_type'] ?? ''), ['song','album'], true)));

$periodEnd = $member['period_end'] ?? null;
$billingLabel = $periodEnd ? ('Renews ' . date('M j, Y', strtotime((string)$periodEnd))) : 'Billing date not set';
$memberName = trim((string)($member['display_name'] ?? '')) ?: 'DesertRio Member';
$statCards = [
  ['label' => 'Messages', 'value' => (int)$messageUnread, 'text' => 'Official updates', 'href' => 'messages.php', 'cta' => 'Open'],
  ['label' => 'Notifications', 'value' => (int)$notificationSummary['unread'], 'text' => 'Unread alerts', 'href' => 'notifications.php', 'cta' => 'Inbox'],
  ['label' => 'Comments', 'value' => (int)$commentSummary['comments'], 'text' => 'Viewer conversations', 'href' => 'comments.php', 'cta' => 'View'],
  ['label' => 'Library', 'value' => (int)$librarySummary['total'], 'text' => 'Saved items', 'href' => 'library.php', 'cta' => 'Library'],
  ['label' => 'Watchlist', 'value' => (int)$librarySummary['watchlist'], 'text' => 'Next up', 'href' => 'watchlist.php', 'cta' => 'Watch'],
  ['label' => 'Playlists', 'value' => count($memberPlaylists), 'text' => 'Private lists', 'href' => 'playlists.php', 'cta' => 'Manage'],
];
$pageTitle = 'Member Dashboard';
$pageDescription = 'DesertRio member dashboard for messages, notifications, comments, library, watchlist, progress, purchases, and access status.';
$pageClass = 'member-dashboard-page membership-page desertrio-member-template';
$pageExtraStyles = ['css/desertrio-account.css'];
require __DIR__ . '/includes/header.php';
?>
<section class="sf-membership-shell sf-member-dashboard">
  <section class="sf-member-overview">
    <div class="sf-member-welcome-card">
      <span class="sf-panel-eyebrow">DesertRio Member Home</span>
      <h1>Welcome back, <?= htmlspecialchars($memberName) ?></h1>
      <p>Continue watching, review your saved moments, manage private collections, and keep your DesertRio access in one polished dashboard.</p>
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
      <article class="sf-dashboard-stat"><span><?= htmlspecialchars($card['label']) ?></span><strong><?= (int)$card['value'] ?></strong><small><?= htmlspecialchars($card['text']) ?></small><a href="<?= sf_url($card['href']) ?>"><?= htmlspecialchars($card['cta']) ?></a></article>
    <?php endforeach; ?>
  </section>

  <section class="sf-member-section sf-dashboard-continue">
    <div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Continue Watching</span><h2>Your DesertRio queue</h2></div><a href="<?= sf_url('watchlist.php') ?>">Watchlist</a></div>
    <div class="sf-video-card-grid">
      <?php if ($videoQueue): ?>
        <?php foreach (array_slice($videoQueue, 0, 4) as $item): ?>
          <a class="sf-video-card" href="<?= htmlspecialchars($item['content_url'] ?? '#') ?>"><img src="<?= sf_asset($item['image_path'] ?? $desertRioAssets['story_truth']) ?>" alt="<?= htmlspecialchars($item['title'] ?? 'Video') ?> poster"><span><?= htmlspecialchars(ucfirst((string)($item['content_type'] ?? 'video'))) ?></span><strong><?= htmlspecialchars($item['title'] ?? 'DesertRio') ?></strong><small><?= (int)($item['progress_percent'] ?? 0) ?>% watched</small></a>
        <?php endforeach; ?>
      <?php else: ?>
        <article class="sf-dashboard-empty"><strong>No watch progress yet</strong><p>Start an episode or save a video to build your Continue Watching queue.</p><a href="<?= sf_url('episodes.php') ?>">Browse Episodes</a></article>
      <?php endif; ?>
    </div>
  </section>

  <section class="sf-dashboard-content-grid">
    <section class="sf-member-section sf-dashboard-panel">
      <div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Recently Played</span><h2>Media activity</h2></div><a href="<?= sf_url('player.php') ?>">Open Player</a></div>
      <div class="sf-recent-music-list">
        <?php if ($songQueue): ?>
          <?php foreach (array_slice($songQueue, 0, 4) as $item): ?>
            <a class="sf-recent-music-row" href="<?= htmlspecialchars($item['content_url'] ?? '#') ?>"><img src="<?= sf_asset($item['image_path'] ?? $desertRioAssets['story_afterparty']) ?>" alt="<?= htmlspecialchars($item['title'] ?? 'Media') ?> artwork"><span><strong><?= htmlspecialchars($item['title'] ?? 'DesertRio') ?></strong><small><?= htmlspecialchars((string)($item['metadata']['episode'] ?? ucfirst((string)($item['content_type'] ?? 'media')))) ?></small></span><b>▶</b></a>
          <?php endforeach; ?>
        <?php else: ?>
          <article class="sf-dashboard-empty"><strong>No media activity yet</strong><p>Your saved audio and additional media activity will appear here.</p><a href="<?= sf_url('episodes.php') ?>">Browse Episodes</a></article>
        <?php endif; ?>
      </div>
    </section>

    <section class="sf-member-section sf-dashboard-panel">
      <div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Private Collections</span><h2>Member playlists</h2></div><a href="<?= sf_url('playlists.php') ?>">Create Playlist</a></div>
      <div class="sf-playlist-grid">
        <?php if ($memberPlaylists): ?>
          <?php foreach ($memberPlaylists as $playlist): ?><article class="sf-member-playlist-card"><img src="<?= sf_asset($playlist['cover']) ?>" alt="<?= htmlspecialchars($playlist['title']) ?> cover"><div><strong><?= htmlspecialchars($playlist['title']) ?></strong><span><?= (int)$playlist['song_count'] ?> saved items · <?= htmlspecialchars($playlist['visibility']) ?></span></div></article><?php endforeach; ?>
        <?php else: ?>
          <article class="sf-dashboard-empty"><strong>No collections yet</strong><p>Create a private playlist or collection, then add available media from the member tools.</p><a href="<?= sf_url('playlists.php') ?>">Create Playlist</a></article>
        <?php endif; ?>
      </div>
    </section>
  </section>

  <section class="sf-member-section sf-dashboard-actions">
    <div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Account & Access</span><h2>Member controls</h2></div><a href="<?= sf_url('account.php') ?>">Account Settings</a></div>
    <div class="sf-dashboard-action-grid">
      <a href="<?= sf_url('library.php') ?>"><strong>Library</strong><span>Saved episodes, videos, products, and completed content.</span></a>
      <a href="<?= sf_url('account-billing.php') ?>"><strong>Billing</strong><span><?= htmlspecialchars($member['access_label']) ?> access · <?= htmlspecialchars($billingLabel) ?>.</span></a>
      <a href="<?= sf_url('messages.php') ?>"><strong>Messages</strong><span><?= (int)$messageUnread ?> unread official member messages.</span></a>
      <a href="<?= sf_url('support.php') ?>"><strong>Support</strong><span>Get help with access, billing, streaming, purchases, or account issues.</span></a>
    </div>
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>