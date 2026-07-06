<?php
$pageTitle = 'Admin Foundation';
$pageDescription = 'Stonefellow admin foundation for membership, engagement, comments, notifications, catalog, analytics, deployment, payments, publishing, activity, content ops, PWA, upload UX, library, and discovery.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Admin Foundation', 'Operational build control', 'Manage engagement, notifications, comments, catalog, content ops, mobile PWA, uploads, analytics, membership, payments, publishing, library, search, and delivery.', 'index');
?>
<section class="sf-admin-card-grid">
  <a class="sf-admin-action-card" href="<?= sf_url('admin/engagement.php') ?>"><span>Engagement</span><strong>Fan Dashboard</strong><small>Comments, reactions, member notifications, moderation.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/comments.php') ?>"><span>Moderation</span><strong>Comments</strong><small>Approve, hide, reject, spam, and audit fan replies.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('notifications.php') ?>"><span>Member</span><strong>Notifications</strong><small>Unread/read/dismissed member notification center.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('comments.php') ?>"><span>Fan</span><strong>Public Threads</strong><small>Comment threads for content and posts.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/mobile-pwa.php') ?>"><span>PWA</span><strong>Mobile Shell</strong><small>Manifest, service worker, offline fallback, install behavior.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/uploads.php') ?>"><span>Upload UX</span><strong>Media Assets v2</strong><small>Drag/drop, previews, validation buckets, recent uploads.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/activity-feed.php') ?>"><span>Activity</span><strong>Member Feed</strong><small>Signups, streams, saves, orders, payments, notifications.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/content-ops.php') ?>"><span>Ops</span><strong>Content Command</strong><small>Tasks, missing media, drafts, orders, notification failures.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/streaming-analytics.php') ?>"><span>Analytics v2</span><strong>Streaming Intelligence</strong><small>Engagement, conversion, library, and revenue signals.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('deploy/preflight.php') ?>"><span>Deploy</span><strong>Preflight</strong><small>Launch check output for hosting deployment.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('library.php') ?>"><span>Library</span><strong>Member Library</strong><small>Saved, liked, watchlist, and completed content.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/search-discovery.php') ?>"><span>Discovery</span><strong>Search Index</strong><small>Search coverage and discovery diagnostics.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/music.php') ?>"><span>Catalog</span><strong>Media Dashboard</strong><small>Counts, flow, and next controls.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('player.php') ?>"><span>Player</span><strong>Audio Player v2</strong><small>Signed queue playback and player state.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/publishing.php') ?>"><span>Publishing</span><strong>Workflow</strong><small>Draft, scheduled, published, archived, and early access.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/qa.php') ?>"><span>QA</span><strong>Production Readiness</strong><small>Launch scoring, route checks, security checks, and content audit.</small></a>
</section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Streaming Platform Foundation</span><h2>What is now built</h2></div></div><div class="sf-admin-roadmap"><div><span>✓</span><strong>Member Notification Center</strong><p>Unread/read/dismissed member inbox, notification summary, and JSON API.</p></div><div><span>✓</span><strong>Comments / Fan Engagement</strong><p>Content threads, reactions, moderation queue, and engagement dashboard.</p></div><div><span>✓</span><strong>Mobile/PWA Offline Shell</strong><p>Installable app metadata, service worker, offline fallback, mobile mini-player, and cached shell assets.</p></div><div><span>✓</span><strong>Admin Media Upload UX v2</strong><p>Drag/drop upload, client previews, validation buckets, recent upload cards, and safer form flow.</p></div><div><span>✓</span><strong>Creator/Admin Content Ops</strong><p>Daily operations command center for tasks, drafts, missing media, orders, payment checks, and notification failures.</p></div><div><span>✓</span><strong>Installer + QA</strong><p>Web installer, SQL migrations, system health, route checks, security checks, and deployment runbook.</p></div></div></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Database Mode</span><h2>Runtime notes</h2></div><a href="<?= sf_url('docs/SQL_FILE_MAP.md') ?>">SQL Map</a></div><p class="sf-admin-copy">The installer runs the base schema plus migrations 001 through 013. Migration 013 now includes publishing, library, search, activity ops, member notifications, comments, reactions, and moderation tables.</p></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
