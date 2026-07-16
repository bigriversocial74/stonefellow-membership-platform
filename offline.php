<?php
$pageTitle = 'Offline';
$pageDescription = 'DesertRio offline app shell fallback.';
$pageClass = 'membership-page pwa-offline-page';
$pageExtraStyles = ['css/desertrio-account.css'];
require __DIR__ . '/includes/header.php';
?>
<section class="sf-membership-shell sf-pwa-offline-shell">
  <section class="sf-member-hero">
    <div>
      <span class="sf-panel-eyebrow">Offline Mode</span>
      <h1>You are offline, but DesertRio is still here.</h1>
      <p>The app shell is cached. Previously opened pages and core assets may still load while your connection returns. Streaming media and account actions require a live connection.</p>
      <div class="sf-episode-action-row">
        <a class="sf-primary-action" href="<?= sf_url('index.php') ?>">Try Home</a>
        <a class="sf-secondary-action" href="<?= sf_url('episodes.php') ?>">Episodes</a>
        <a class="sf-secondary-action" href="<?= sf_url('cast.php') ?>">Cast</a>
      </div>
    </div>
    <article class="sf-member-status-card">
      <span>DesertRio App</span>
      <strong>Cached</strong>
      <small>Navigation, core styles, selected public pages, and essential theme assets.</small>
      <a href="<?= sf_url('manifest.webmanifest') ?>">View Manifest</a>
    </article>
  </section>
  <section class="sf-member-grid">
    <article class="sf-member-panel"><span class="sf-panel-eyebrow">Available</span><h2>Show Shell</h2><p>Core layout, cached public pages, and previously stored static assets can remain available offline.</p></article>
    <article class="sf-member-panel"><span class="sf-panel-eyebrow">Requires Network</span><h2>Episodes</h2><p>Signed video URLs, fresh membership checks, payments, and account changes need a live connection.</p></article>
    <article class="sf-member-panel"><span class="sf-panel-eyebrow">Next</span><h2>Reconnect</h2><p>Return to the show when your connection is restored. The app shell will refresh automatically.</p></article>
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
