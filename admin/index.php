<?php
$pageTitle = 'Admin Foundation';
$pageDescription = 'Stonefellow admin foundation for membership, personalized feed, engagement analytics, creator posts, comments, notifications, catalog, analytics, deployment, payments, publishing, activity, content ops, PWA, upload UX, library, and discovery.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Admin Foundation', 'Operational build control', 'Manage personalized feed, follow system, engagement analytics, creator posts, inline comments, notifications, catalog, content ops, mobile PWA, uploads, analytics, membership, payments, publishing, library, search, and delivery.', 'index');
?>
<section class="sf-admin-card-grid">
  <a class="sf-admin-action-card" href="<?= sf_url('admin/engagement-analytics.php') ?>"><span>Analytics v2</span><strong>Engagement</strong><small>Feed saves, hides, follows, comments, reactions, top members.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('feed.php') ?>"><span>Personalized</span><strong>Member Feed</strong><small>Follow system, preferences, saved and hidden feed items.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/posts.php') ?>"><span>Creator</span><strong>Posts Manager</strong><small>News, updates, media-linked posts, and publish states.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/engagement.php') ?>"><span>Engagement</span><strong>Fan Dashboard</strong><small>Comments, reactions, member notifications, moderation.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/comments.php') ?>"><span>Moderation</span><strong>Comments</strong><small>Approve, hide, reject, and audit fan replies.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('notifications.php') ?>"><span>Member</span><strong>Notifications</strong><small>Unread/read/dismissed member notification center.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/mobile-pwa.php') ?>"><span>PWA</span><strong>Mobile Shell</strong><small>Manifest, service worker, offline fallback, install behavior.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/uploads.php') ?>"><span>Upload UX</span><strong>Media Assets v2</strong><small>Drag/drop, previews, validation buckets, recent uploads.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/activity-feed.php') ?>"><span>Activity</span><strong>Member Feed</strong><small>Signups, streams, saves, orders, payments, notifications.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/content-ops.php') ?>"><span>Ops</span><strong>Content Command</strong><small>Tasks, missing media, drafts, orders, notification failures.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/streaming-analytics.php') ?>"><span>Streaming</span><strong>Analytics</strong><small>Engagement, conversion, library, and revenue signals.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('library.php') ?>"><span>Library</span><strong>Member Library</strong><small>Saved, liked, watchlist, and completed content.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/search-discovery.php') ?>"><span>Discovery</span><strong>Search Index</strong><small>Search coverage and discovery diagnostics.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/music.php') ?>"><span>Catalog</span><strong>Media Dashboard</strong><small>Counts, flow, and next controls.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/qa.php') ?>"><span>QA</span><strong>Production Readiness</strong><small>Launch scoring, route checks, security checks, and content audit.</small></a>
</section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Streaming Platform Foundation</span><h2>What is now built</h2></div></div><div class="sf-admin-roadmap"><div><span>✓</span><strong>Feed Personalization / Follow System</strong><p>Follow creators/content types, tune preferences, save/hide feed items, and rank posts per member.</p></div><div><span>✓</span><strong>Member Engagement Analytics v2</strong><p>Track feed saves/hides, comments, reactions, follows, top content, and top engaged members.</p></div><div><span>✓</span><strong>Creator Posts / News Feed</strong><p>Public feed, post detail pages, admin post manager, media links, publish states, and APIs.</p></div><div><span>✓</span><strong>Inline Comment Widgets</strong><p>Episode, watch, song, album, and post pages show embedded fan threads and quick-comment forms.</p></div><div><span>✓</span><strong>Member Notification Center</strong><p>Unread/read/dismissed member inbox, notification summary, and JSON API.</p></div><div><span>✓</span><strong>Installer + QA</strong><p>Web installer, SQL migrations, system health, route checks, security checks, and deployment runbook.</p></div></div></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Database Mode</span><h2>Runtime notes</h2></div><a href="<?= sf_url('docs/SQL_FILE_MAP.md') ?>">SQL Map</a></div><p class="sf-admin-copy">The installer runs the base schema plus migrations 001 through 014. Migration 014 adds feed personalization, follows, preferences, saved/hidden feed items, engagement rollups, and member engagement scores.</p></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
