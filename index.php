<?php
$pageTitle = 'DesertRio';
$pageDescription = 'Stream DesertRio, an Arizona-set reality-style drama about fashion, lifestyle, ambition, and the secrets that surface under the desert sun.';
$pageClass = 'home-template desertrio-home-template';

require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/desertrio_theme.php';
require __DIR__ . '/includes/header.php';
?>

<section class="dr-home-hero" aria-labelledby="dr-home-title">
  <div class="dr-home-hero-media">
    <img src="<?= sf_asset($desertRioAssets['hero']) ?>" alt="DesertRio cast at an exclusive Arizona poolside retreat" fetchpriority="high">
  </div>
  <div class="dr-home-hero-shade" aria-hidden="true"></div>
  <div class="dr-home-hero-copy">
    <p class="dr-home-intro">ARIZONA IS HOT.<br>FAME IS HOTTER.</p>
    <h1 id="dr-home-title">DesertRio</h1>
    <div class="dr-ornament" aria-hidden="true"><span></span><b>✦</b><span></span></div>
    <h2>FASHION. LIFESTYLE. DRAMA.</h2>
    <p class="dr-home-sub">NOTHING STAYS HIDDEN IN THE DESERT.</p>
    <div class="dr-home-actions">
      <a class="dr-button dr-button-primary" href="<?= sf_url('episodes.php') ?>">Stream Now <span class="dr-button-play" aria-hidden="true">▷</span></a>
      <button
        class="dr-button"
        type="button"
        data-home-video-open
        data-video-src="https://www.youtube.com/embed/jMlQMre7LcA?start=14&amp;autoplay=1&amp;rel=0&amp;modestbranding=1"
        aria-haspopup="dialog"
        aria-controls="home-video-modal"
      >Watch Trailer <span class="dr-button-play" aria-hidden="true">▷</span></button>
    </div>
  </div>
</section>

<section class="dr-welcome" id="about" aria-labelledby="dr-welcome-title">
  <div class="dr-welcome-image">
    <img src="<?= sf_asset($desertRioAssets['welcome']) ?>" alt="Exclusive Scottsdale desert residence and pool">
    <span class="dr-welcome-script">Scottsdale, Arizona</span>
  </div>
  <div class="dr-welcome-copy">
    <p class="dr-eyebrow">Welcome To</p>
    <h2 id="dr-welcome-title">The Desert’s Most<br><em>Exclusive Playground.</em></h2>
    <div class="dr-small-ornament" aria-hidden="true"><span></span><b>✦</b><span></span></div>
    <p>Follow a group of rising models, influencers, and industry insiders as they chase dreams, break hearts, and burn bridges—under the Arizona sun and beyond the velvet rope.</p>
    <div class="dr-feature-grid">
      <a href="<?= sf_url('series.php') ?>"><b aria-hidden="true">◇</b><span>High Fashion</span></a>
      <a href="<?= sf_url('episodes.php') ?>"><b aria-hidden="true">⌁</b><span>Pool Parties</span></a>
      <a href="<?= sf_url('cast.php') ?>"><b aria-hidden="true">♧</b><span>Arizona Luxury</span></a>
      <a href="<?= sf_url('episodes.php') ?>"><b aria-hidden="true">☾</b><span>Late-Night Drama</span></a>
    </div>
  </div>
</section>

<section class="dr-home-section" id="cast" aria-labelledby="dr-cast-title">
  <header class="dr-section-head">
    <div><span></span><h2 id="dr-cast-title">The Cast</h2><span></span></div>
    <p>Beautiful faces. Complicated lives.</p>
  </header>
  <div class="dr-cast-grid">
    <?php foreach ($desertRioCast as $member): ?>
      <article class="dr-cast-card">
        <a href="<?= sf_url('cast.php') ?>" aria-label="Meet <?= htmlspecialchars($member['name'], ENT_QUOTES, 'UTF-8') ?>">
          <img src="<?= sf_asset($member['image']) ?>" alt="<?= htmlspecialchars($member['name'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async">
          <div class="dr-cast-card-body">
            <h3><?= htmlspecialchars($member['name'], ENT_QUOTES, 'UTF-8') ?></h3>
            <p><?= htmlspecialchars($member['role'], ENT_QUOTES, 'UTF-8') ?></p>
            <span class="dr-cast-card-plus" aria-hidden="true">+</span>
          </div>
        </a>
      </article>
    <?php endforeach; ?>
  </div>
  <a class="dr-home-link" href="<?= sf_url('cast.php') ?>">Meet the Full Cast <span aria-hidden="true">→</span></a>
</section>

<section class="dr-home-section dr-home-drama" id="episodes" aria-labelledby="dr-drama-title">
  <header class="dr-section-head">
    <div><span></span><h2 id="dr-drama-title">Drama Runs Deep</h2><span></span></div>
    <p>Secrets. Betrayals. Obsessions.</p>
  </header>
  <div class="dr-story-grid">
    <?php foreach ($desertRioStories as $story): ?>
      <article class="dr-story-card">
        <a href="<?= sf_url($story['href']) ?>" aria-label="<?= htmlspecialchars($story['title'], ENT_QUOTES, 'UTF-8') ?>">
          <img src="<?= sf_asset($story['image']) ?>" alt="<?= htmlspecialchars($story['title'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async">
          <span class="dr-story-card-overlay" aria-hidden="true"></span>
          <div class="dr-story-card-copy">
            <small><?= htmlspecialchars($story['eyebrow'], ENT_QUOTES, 'UTF-8') ?></small>
            <h3><?= htmlspecialchars($story['title'], ENT_QUOTES, 'UTF-8') ?></h3>
            <span class="dr-story-card-play" aria-hidden="true">▷</span>
          </div>
        </a>
      </article>
    <?php endforeach; ?>
  </div>
  <a class="dr-home-link" href="<?= sf_url('episodes.php') ?>">View All Episodes <span aria-hidden="true">→</span></a>
</section>

<div class="home-video-modal" id="home-video-modal" role="dialog" aria-modal="true" aria-label="DesertRio trailer" hidden data-home-video-modal>
  <div class="home-video-backdrop" data-home-video-close></div>
  <div class="home-video-dialog" role="document">
    <button class="home-video-close" type="button" aria-label="Close trailer" data-home-video-close>×</button>
    <div class="home-video-frame-wrap">
      <iframe
        data-home-video-frame
        title="DesertRio trailer"
        src=""
        loading="lazy"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
        allowfullscreen
      ></iframe>
    </div>
  </div>
</div>

<script>
(function () {
  const openButton = document.querySelector('[data-home-video-open]');
  const modal = document.querySelector('[data-home-video-modal]');
  const frame = document.querySelector('[data-home-video-frame]');
  const closeButtons = document.querySelectorAll('[data-home-video-close]');
  if (!openButton || !modal || !frame) return;

  let previousFocus = null;

  function openVideoModal() {
    previousFocus = document.activeElement;
    frame.src = openButton.dataset.videoSrc || '';
    modal.hidden = false;
    document.body.classList.add('home-video-modal-open');
    const closeButton = modal.querySelector('.home-video-close');
    if (closeButton) closeButton.focus();
  }

  function closeVideoModal() {
    modal.hidden = true;
    frame.src = '';
    document.body.classList.remove('home-video-modal-open');
    if (previousFocus && typeof previousFocus.focus === 'function') previousFocus.focus();
  }

  openButton.addEventListener('click', openVideoModal);
  closeButtons.forEach((button) => button.addEventListener('click', closeVideoModal));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !modal.hidden) closeVideoModal();
  });
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
