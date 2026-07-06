<?php
$pageTitle = 'Admin Foundation';
$pageDescription = 'Stonefellow admin foundation for membership, catalog, playback tracking, playlists, payments, and publishing.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require __DIR__ . '/../includes/header.php';

sf_admin_shell_start('Admin Foundation', 'Operational build control', 'Manage the media catalog, membership access, payments, publishing workflow, and streaming delivery for the full Stonefellow platform.', 'index');
?>
<section class="sf-admin-card-grid">
  <a class="sf-admin-action-card" href="<?= sf_url('admin/music.php') ?>"><span>Catalog</span><strong>Media Dashboard</strong><small>Counts, flow, and next controls.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/music-albums.php') ?>"><span>Music</span><strong>Albums</strong><small>Album containers and cover artwork.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/music-songs.php') ?>"><span>Audio</span><strong>Songs + Files</strong><small>Tracks, previews, full files, access levels.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('player.php') ?>"><span>Player</span><strong>Audio Player v2</strong><small>Signed queue playback and player state.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/episodes.php') ?>"><span>Series</span><strong>Episodes</strong><small>Season/episode records and publishing.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/videos.php') ?>"><span>Video</span><strong>Videos + Files</strong><small>Watch-page source files and gates.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/publishing.php') ?>"><span>Publishing</span><strong>Workflow</strong><small>Draft, scheduled, published, archived, and early access.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/media-delivery.php') ?>"><span>Delivery</span><strong>Secure Media</strong><small>Signed URLs, stream gate, and protected paths.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/entitlements.php') ?>"><span>Entitlements</span><strong>Access Enforcement</strong><small>Grace periods, grants, and tier snapshots.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/payment-gateways.php') ?>"><span>Gateways</span><strong>Production Pass</strong><small>Stripe Checkout, provider events, and readiness.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/seasons.php') ?>"><span>Seasons</span><strong>Season Manager</strong><small>Season arcs, poster art, and release status.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/release-schedule.php') ?>"><span>Schedule</span><strong>Release Schedule</strong><small>Episode/video publish windows and access timing.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/members.php') ?>"><span>Members</span><strong>Members + Plans</strong><small>Users, roles, status, and subscriptions.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/analytics.php') ?>"><span>Analytics</span><strong>Performance Dashboard</strong><small>Audio, video, members, playlists, and revenue.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/billing.php') ?>"><span>Billing</span><strong>Subscriptions + Payments</strong><small>Checkouts, invoices, transactions, and webhooks.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/settings.php') ?>"><span>Settings</span><strong>Site Settings</strong><small>Runtime toggles, support emails, and public config.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/system-health.php') ?>"><span>Health</span><strong>System Health</strong><small>Installer, schema, uploads, and PHP checks.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/qa.php') ?>"><span>QA</span><strong>Production Readiness</strong><small>Launch scoring, route checks, security checks, and content audit.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/notifications.php') ?>"><span>Email</span><strong>Notifications</strong><small>Transactional email queue, logs, tests, and webhooks.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/products.php') ?>"><span>Merch</span><strong>Products + Inventory</strong><small>Store products, variants, prices, and subscriber drops.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/orders.php') ?>"><span>Orders</span><strong>Order Runtime</strong><small>Paid orders, status history, and fulfillment queue.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/media-access.php') ?>"><span>Access</span><strong>Access Rules</strong><small>Plans and direct content grants.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/uploads.php') ?>"><span>Assets</span><strong>Media Assets</strong><small>Cover, poster, file, and CDN path records.</small></a>
</section>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Streaming Platform Foundation</span><h2>What is now built</h2></div></div>
  <div class="sf-admin-roadmap">
    <div><span>✓</span><strong>Gateway Production Pass</strong><p>Stripe Checkout session creation, webhook verification, provider checkout activation, and lifecycle events.</p></div>
    <div><span>✓</span><strong>Publishing Workflow</strong><p>Draft, scheduled, published, archived, early-access, featured, and due-runner publishing controls.</p></div>
    <div><span>✓</span><strong>Audio Player v2</strong><p>Signed audio track payloads, queue metadata, preview/full mode, and player state API.</p></div>
    <div><span>✓</span><strong>Subscription Enforcement v2</strong><p>Grace periods, expired lockout, direct grant overrides, and tier-ranked access snapshots.</p></div>
    <div><span>✓</span><strong>Secure Media Delivery</strong><p>Signed stream/download URLs, entitlement checks, range streaming, and protected source folder guidance.</p></div>
    <div><span>✓</span><strong>Installer + QA</strong><p>Web installer, SQL migrations, system health, route checks, security checks, and deployment runbook.</p></div>
  </div>
</section>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Database Mode</span><h2>Runtime notes</h2></div><a href="<?= sf_url('docs/SQL_FILE_MAP.md') ?>">SQL Map</a></div>
  <p class="sf-admin-copy">With the web installer, upload the files, visit the site URL, enter DB details, and run the base SQL plus migrations 001 through 013 automatically.</p>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
