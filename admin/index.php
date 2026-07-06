<?php
$pageTitle = 'Admin Foundation';
$pageDescription = 'Stonefellow admin foundation for membership, catalog, playback tracking, playlists, and publishing.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require __DIR__ . '/../includes/header.php';

sf_admin_shell_start('Admin Foundation', 'Operational build control', 'Manage the media catalog, membership access, audio/video files, and publishing workflow for the full Stonefellow membership site.', 'index');
?>
<section class="sf-admin-card-grid">
  <a class="sf-admin-action-card" href="<?= sf_url('admin/music.php') ?>"><span>Catalog</span><strong>Media Dashboard</strong><small>Counts, flow, and next controls.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/music-albums.php') ?>"><span>Music</span><strong>Albums</strong><small>Album containers and cover artwork.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/music-songs.php') ?>"><span>Audio</span><strong>Songs + Files</strong><small>Tracks, previews, full files, access levels.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/episodes.php') ?>"><span>Series</span><strong>Episodes</strong><small>Season/episode records and publishing.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/videos.php') ?>"><span>Video</span><strong>Videos + Files</strong><small>Watch-page source files and gates.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/media-delivery.php') ?>"><span>Delivery</span><strong>Secure Media</strong><small>Signed URLs, stream gate, and protected paths.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/seasons.php') ?>"><span>Seasons</span><strong>Season Manager</strong><small>Season arcs, poster art, and release status.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/release-schedule.php') ?>"><span>Schedule</span><strong>Release Schedule</strong><small>Episode/video publish windows and access timing.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/members.php') ?>"><span>Members</span><strong>Members + Plans</strong><small>Users, roles, status, and subscriptions.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/analytics.php') ?>"><span>Analytics</span><strong>Performance Dashboard</strong><small>Audio, video, members, playlists, and revenue.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/billing.php') ?>"><span>Billing</span><strong>Subscriptions + Payments</strong><small>Checkouts, invoices, transactions, and webhooks.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/payment-gateways.php') ?>"><span>Gateways</span><strong>Payment Adapters</strong><small>Sandbox, Stripe, and PayPal adapter readiness.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/settings.php') ?>"><span>Settings</span><strong>Site Settings</strong><small>Runtime toggles, support emails, and public config.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/system-health.php') ?>"><span>Health</span><strong>System Health</strong><small>Installer, schema, uploads, and PHP checks.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/qa.php') ?>"><span>QA</span><strong>Production Readiness</strong><small>Launch scoring, route checks, security checks, and content audit.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/notifications.php') ?>"><span>Email</span><strong>Notifications</strong><small>Transactional email queue, logs, tests, and webhooks.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/email-templates.php') ?>"><span>Templates</span><strong>Email Templates</strong><small>Manage subject lines, HTML bodies, and variables.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/products.php') ?>"><span>Merch</span><strong>Products + Inventory</strong><small>Store products, variants, prices, and subscriber drops.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/orders.php') ?>"><span>Orders</span><strong>Order Runtime</strong><small>Paid orders, status history, and fulfillment queue.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/media-access.php') ?>"><span>Access</span><strong>Access Rules</strong><small>Plans and direct content grants.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/uploads.php') ?>"><span>Assets</span><strong>Media Assets</strong><small>Cover, poster, file, and CDN path records.</small></a>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head">
    <div><span class="sf-panel-eyebrow">Streaming Platform Foundation</span><h2>What is now built</h2></div>
  </div>
  <div class="sf-admin-roadmap">
    <div><span>✓</span><strong>Albums CRUD</strong><p>Create, edit, publish, archive, and connect cover assets.</p></div>
    <div><span>✓</span><strong>Songs CRUD</strong><p>Manage song metadata, track order, access levels, and audio file variants.</p></div>
    <div><span>✓</span><strong>Episodes CRUD</strong><p>Manage season/episode records used by episode and watch pages.</p></div>
    <div><span>✓</span><strong>Videos CRUD</strong><p>Manage trailers, full episodes, clips, live sessions, and video file variants.</p></div>
    <div><span>✓</span><strong>Secure Media Delivery</strong><p>Signed stream/download URLs, entitlement checks, range streaming, and protected source folder guidance.</p></div>
    <div><span>✓</span><strong>Access Controls</strong><p>Manage subscription plans and direct member access grants.</p></div>
    <div><span>✓</span><strong>Upload Storage</strong><p>Upload files, preview assets, and register local/CDN media paths.</p></div>
    <div><span>✓</span><strong>Analytics Dashboard</strong><p>Review audio plays, video watch time, member activity, playlists, and commerce performance.</p></div>
    <div><span>✓</span><strong>Billing + Entitlements</strong><p>Run sandbox checkout, create invoices, record payments, activate subscriptions, and prepare processor webhooks.</p></div>
    <div><span>✓</span><strong>Merch Cart + Orders</strong><p>Run store cart, checkout, order creation, payment transaction records, inventory movement, and fulfillment status updates.</p></div>
    <div><span>✓</span><strong>Email + Notifications</strong><p>Send welcome, reset, billing, order, fulfillment, admin alert, and playlist notification events through a logged queue.</p></div>
    <div><span>✓</span><strong>Installer + Settings</strong><p>Site settings, launch health checks, migration map, upload folder checks, and public installer.</p></div>
    <div><span>✓</span><strong>Payment Adapter Boundary</strong><p>Sandbox runtime remains active while Stripe and PayPal adapters are isolated for production gateway work.</p></div>
    <div><span>✓</span><strong>Episode/Video Admin v2</strong><p>Seasons, release scheduling, episode posters, access windows, watch-next routing, and video chapters.</p></div>
    <div><span>✓</span><strong>Production QA Harness</strong><p>Launch scoring, route checks, security checks, content audit, and deployment runbook.</p></div>
  </div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head">
    <div><span class="sf-panel-eyebrow">Database Mode</span><h2>Runtime notes</h2></div>
    <a href="<?= sf_url('docs/SQL_FILE_MAP.md') ?>">SQL Map</a>
  </div>
  <p class="sf-admin-copy">The admin pages are safe to open before the database is configured. Without DB credentials they show static preview data and disable save/delete buttons. With the web installer, upload the files, visit the site URL, enter DB details, and run the base SQL plus migrations 001 through 011 automatically.</p>
</section>
<?php
sf_admin_shell_end();
require __DIR__ . '/../includes/footer.php';
?>
