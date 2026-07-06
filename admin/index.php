<?php
$pageTitle = 'Admin Foundation';
$pageDescription = 'Stonefellow admin foundation for membership, catalog, analytics, deployment, payments, publishing, library, and discovery.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Admin Foundation', 'Operational build control', 'Manage catalog, streaming analytics, deployment, membership, payments, publishing, library, search, and delivery.', 'index');
?>
<section class="sf-admin-card-grid">
  <a class="sf-admin-action-card" href="<?= sf_url('admin/streaming-analytics.php') ?>"><span>Analytics v2</span><strong>Streaming Intelligence</strong><small>Engagement, conversion, library, and revenue signals.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('deploy/preflight.php') ?>"><span>Deploy</span><strong>Preflight</strong><small>Launch check output for hosting deployment.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('docs/PRODUCTION_DEPLOYMENT_PACKAGE_V1.md') ?>"><span>Package</span><strong>Deployment v1</strong><small>Once-and-done install and launch package notes.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/analytics.php') ?>"><span>Analytics</span><strong>Performance Dashboard</strong><small>Audio, video, members, playlists, and revenue.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('library.php') ?>"><span>Library</span><strong>Member Library</strong><small>Saved, liked, watchlist, and completed content.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/search-discovery.php') ?>"><span>Discovery</span><strong>Search Index</strong><small>Search coverage and discovery diagnostics.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/music.php') ?>"><span>Catalog</span><strong>Media Dashboard</strong><small>Counts, flow, and next controls.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('player.php') ?>"><span>Player</span><strong>Audio Player v2</strong><small>Signed queue playback and player state.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/episodes.php') ?>"><span>Series</span><strong>Episodes</strong><small>Season/episode records and publishing.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/videos.php') ?>"><span>Video</span><strong>Videos + Files</strong><small>Watch-page source files and gates.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/publishing.php') ?>"><span>Publishing</span><strong>Workflow</strong><small>Draft, scheduled, published, archived, and early access.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/media-delivery.php') ?>"><span>Delivery</span><strong>Secure Media</strong><small>Signed URLs, stream gate, and protected paths.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/entitlements.php') ?>"><span>Entitlements</span><strong>Access Enforcement</strong><small>Grace periods, grants, and tier snapshots.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/payment-gateways.php') ?>"><span>Gateways</span><strong>Production Pass</strong><small>Stripe Checkout, provider events, and readiness.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/qa.php') ?>"><span>QA</span><strong>Production Readiness</strong><small>Launch scoring, route checks, security checks, and content audit.</small></a>
</section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Streaming Platform Foundation</span><h2>What is now built</h2></div></div><div class="sf-admin-roadmap"><div><span>✓</span><strong>Streaming Analytics v2</strong><p>Engagement intelligence, conversion rate, library saves, top content, and revenue per member.</p></div><div><span>✓</span><strong>Deployment Package v1</strong><p>Environment template, deployment preflight, updated runbook, and launch gate docs.</p></div><div><span>✓</span><strong>Member Library + Search</strong><p>Saved, watchlist, discovery, and search API across the catalog.</p></div><div><span>✓</span><strong>Payments + Publishing</strong><p>Gateway production pass, publishing workflow, and scheduled release controls.</p></div><div><span>✓</span><strong>Secure Media Delivery</strong><p>Signed stream/download URLs, entitlement checks, range streaming, and protected source folders.</p></div><div><span>✓</span><strong>Installer + QA</strong><p>Web installer, SQL migrations, system health, route checks, security checks, and deployment runbook.</p></div></div></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Database Mode</span><h2>Runtime notes</h2></div><a href="<?= sf_url('docs/SQL_FILE_MAP.md') ?>">SQL Map</a></div><p class="sf-admin-copy">The installer runs the base schema plus migrations 001 through 013. Migration 013 includes publishing, member library, and search discovery tables. Analytics v2 uses existing tables and requires no new SQL.</p></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
