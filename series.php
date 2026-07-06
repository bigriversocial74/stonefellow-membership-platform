<?php
$pageTitle = 'Series';
$pageDescription = 'Take Stonefellow anywhere with the series, music, downloads, and exclusive app content.';
$pageClass = 'series-app-template';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/header.php';
?>
<section class="series-app-page">
  <section class="series-app-hero">
    <div class="series-app-copy">
      <h1>Stonefellow<br>Anywhere.</h1>
      <div class="series-app-rule" aria-hidden="true"></div>
      <p class="series-app-lead">The show. The music. In your pocket.</p>
      <div class="series-feature-list">
        <article>
          <span class="series-feature-icon">▶</span>
          <div><h2>Watch all episodes</h2><p>Stream on any device.</p></div>
        </article>
        <article>
          <span class="series-feature-icon">≋</span>
          <div><h2>Stream the soundtrack</h2><p>Listen anytime, anywhere.</p></div>
        </article>
        <article>
          <span class="series-feature-icon">↓</span>
          <div><h2>Download &amp; go</h2><p>Watch offline. No limits.</p></div>
        </article>
        <article>
          <span class="series-feature-icon">▭</span>
          <div><h2>Continue watching</h2><p>Pick up right where you left off.</p></div>
        </article>
        <article>
          <span class="series-feature-icon">★</span>
          <div><h2>Exclusive content</h2><p>Only in the app.</p></div>
        </article>
      </div>
    </div>
    <div class="series-phone-art">
      <img src="<?= sf_asset('images/series/series-phone-mockups.png') ?>" alt="Stonefellow mobile app screens">
    </div>
  </section>

  <section class="series-download-block">
    <div class="series-divider"><span>Download the App</span></div>
    <div class="series-store-row">
      <a href="app.php" class="series-store-badge"><span></span><strong>Download on the<br>App Store</strong></a>
      <a href="app.php" class="series-store-badge"><span>▶</span><strong>Get it on<br>Google Play</strong></a>
    </div>
  </section>

  <section class="series-stage-section">
    <img src="<?= sf_asset('images/series/series-stage-band.png') ?>" alt="Stonefellow full band performance">
    <div class="series-stage-mark left">SF</div>
    <div class="series-stage-mark right">SF</div>
  </section>

  <section class="series-benefit-bar">
    <article><span>♫</span><div><h3>Listen to Every Song</h3><p>The official soundtrack.</p></div></article>
    <article><span>▷</span><div><h3>Watch Every Episode</h3><p>Stream the series.</p></div></article>
    <article><span>★</span><div><h3>Exclusive Extras</h3><p>Behind the scenes &amp; more.</p></div></article>
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
