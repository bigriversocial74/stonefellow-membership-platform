<?php
$pageTitle = 'Admin Foundation';
$pageDescription = 'Stonefellow admin foundation for membership, catalog, playback tracking, playlists, payments, publishing, library, and discovery.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require __DIR__ . '/../includes/header.php';

sf_admin_shell_start('Admin Foundation', 'Operational build control', 'Manage the media catalog, membership access, payments, publishing workflow, member library, search, and streaming delivery.', 'index');
?>
<section class="sf-admin-card-grid">
  <a class="sf-admin-action-card" href="<?= sf_url('admin/music.php') ?>"><span>Catalog</span><strong>Media Dashboard</strong><small>Counts, flow, and next controls.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/music-albums.php') ?>"><span>Music</span><strong>Albums</strong><small>Album containers and cover artwork.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/music-songs.php') ?>"><span>Audio</span><strong>Songs + Files</strong><small>Tracks, previews, full files, access levels.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('library.php') ?>"><span>Library</span><strong>Member Library</strong><small>Saved, liked, watchlist, and completed content.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/search-discovery.php') ?>"><span>Discovery</span><strong>Search Index</strong><small>Search coverage and discovery diagnostics.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('search.php') ?>"><span>Search</span><strong>Public Search</strong><small>Songs, videos, episodes, albums, and merch.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('player.php') ?>"><span>Player</span><strong>Audio Player v2</strong><small>Signed queue playback and player state.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/episodes.php') ?>"><span>Series</span><strong>Episodes</strong><small>Season/episode records and publishing.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/videos.php') ?>"><span>Video</span><strong>Videos + Files</strong><small>Watch-page source files and gates.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/publishing.php') ?>"><span>Publishing</span><strong>Workflow</strong><small>Draft, scheduled, published, archived, and early access.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/media-delivery.php') ?>"><span>Delivery</span><strong>Secure Media</strong><small>Signed URLs, stream gate, and protected paths.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/entitlements.php') ?>"><span>Entitlements</span><strong>Access Enforcement</strong><small>Grace periods, grants, and tier snapshots.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/payment-gateways.php') ?>"><span>Gateways</span><strong>Production Pass</strong><small>Stripe Checkout, provider events, and readiness.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/release-schedule.php') ?>"><span>Schedule</span><strong>Release Schedule</strong><small>Episode/video publish windows and access timing.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/members.php') ?>"><span>Members</span><strong>Members + Plans</strong><small>Users, roles, status, and subscriptions.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/analytics.php') ?>"><span>Analytics</span><strong>Performance Dashboard</strong><small>Audio, video, members, playlists, and revenue.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/billing.php') ?>"><span>Billing</span><strong>Subscriptions + Payments</strong><small>Checkouts, invoices, transactions, and webhooks.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/settings.php') ?>"><span>Settings</span><strong>Site Settings</strong><small>Runtime toggles, support emails, and public config.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/qa.php') ?>"><span>QA</span><strong>Production Readiness</strong><small>Launch scoring, route checks, security checks, and content audit.</small></a>
</section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Streaming Platform Foundation</span><h2>What is now built</h2></div></div><div class="sf-admin-roadmap"><div><span>✓</span><strong>Member Library</strong><p>Saved, watchlist, liked, completed, progress-aware member content collections.</p></div><div><span>✓</span><strong>Search + Discovery</strong><p>Unified search across songs, videos, episodes, albums, and merch with API support.</p></div><div><span>✓</span><strong>Gateway Production Pass</strong><p>Stripe checkout, webhook verification, provider checkout activation, and lifecycle events.</p></div><div><span>✓</span><strong>Publishing Workflow</strong><p>Draft, scheduled, published, archived, early-access, featured, and due-runner controls.</p></div><div><span>✓</span><strong>Secure Media Delivery</strong><p>Signed stream/download URLs, entitlement checks, range streaming, and protected source folders.</p></div><div><span>✓</span><strong>Installer + QA</strong><p>Web installer, SQL migrations, system health, route checks, security checks, and deployment runbook.</p></div></div></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Database Mode</span><h2>Runtime notes</h2></div><a href="<?= sf_url('docs/SQL_FILE_MAP.md') ?>">SQL Map</a></div><p class="sf-admin-copy">With the web installer, upload the files, visit the site URL, enter DB details, and run the base SQL plus migrations 001 through 014 automatically.</p></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
