<?php
$pageTitle = 'Offline';
$pageDescription = 'Stonefellow offline app shell fallback.';
$pageClass = 'membership-page pwa-offline-page';
require __DIR__ . '/includes/header.php';
?>
<section class="sf-membership-shell sf-pwa-offline-shell">
  <section class="sf-member-hero">
    <div>
      <span class="sf-panel-eyebrow">Offline Mode</span>
      <h1>You are offline, but Stonefellow is still here.</h1>
      <p>The app shell is cached. Previously opened pages and core assets may still load while your connection returns. Streaming media and account actions need a live connection.</p>
      <div class="sf-episode-action-row">
        <a class="sf-primary-action" href="<?= sf_url('index.php') ?>">Try Home</a>
        <a class="sf-secondary-action" href="<?= sf_url('player.php') ?>">Music Player</a>
        <a class="sf-secondary-action" href="<?= sf_url('episodes.php') ?>">Episodes</a>
      </div>
    </div>
    <article class="sf-member-status-card">
      <span>App Shell</span>
      <strong>Cached</strong>
      <small>Navigation fallback, core styles, player scripts, and brand assets.</small>
      <a href="<?= sf_url('manifest.webmanifest') ?>">Manifest</a>
    </article>
  </section>
  <section class="sf-member-grid">
    <article class="sf-member-panel"><span class="sf-panel-eyebrow">Available</span><h2>Shell</h2><p>Core layout, cached public pages, and cached static assets can appear offline.</p></article>
    <article class="sf-member-panel"><span class="sf-panel-eyebrow">Requires Network</span><h2>Streams</h2><p>Signed media URLs, fresh entitlement checks, payments, and admin writes need a live connection.</p></article>
    <article class="sf-member-panel"><span class="sf-panel-eyebrow">Next</span><h2>Sync</h2><p>Future phases can queue member activity and sync it when the device reconnects.</p></article>
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
