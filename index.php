<?php
$pageTitle = 'DesertRio';
$pageDescription = 'Stream DesertRio, an Arizona-set reality drama about fashion, ambition, attraction, and the secrets that surface under the desert sun.';
$pageClass = 'home-template desertrio-home-template desertrio-index-v3';
$pageExtraStyles = ['css/desertrio-index-v3.css'];

require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/desertrio_theme.php';
require __DIR__ . '/includes/header.php';
?>

<section class="dr3-hero" aria-labelledby="dr3-home-title">
  <img
    class="dr3-hero-image"
    src="<?= sf_asset($desertRioAssets['hero']) ?>"
    alt="DesertRio cast at a private Arizona resort"
    fetchpriority="high"
    decoding="async"
  >
  <div class="dr3-hero-wash" aria-hidden="true"></div>
  <div class="dr3-shell dr3-hero-inner">
    <div class="dr3-hero-copy">
      <p class="dr3-kicker">Arizona is hot.<br>Fame is hotter.</p>
      <h1 id="dr3-home-title">DesertRio</h1>
      <div class="dr3-rule" aria-hidden="true"><span></span><b>✦</b><span></span></div>
      <p class="dr3-tagline">Fashion. Lifestyle. Drama.</p>
      <p class="dr3-subtitle">Nothing stays hidden in the desert.</p>
      <div class="dr3-actions">
        <a class="dr3-button dr3-button-gold" href="<?= sf_url('episodes.php') ?>">
          Stream Now <span aria-hidden="true">▷</span>
        </a>
        <button
          class="dr3-button dr3-button-light"
          type="button"
          data-home-video-open
          data-video-src="https://www.youtube.com/embed/jMlQMre7LcA?start=14&amp;autoplay=1&amp;rel=0&amp;modestbranding=1"
          aria-haspopup="dialog"
          aria-controls="home-video-modal"
        >
          Watch Trailer <span aria-hidden="true">▷</span>
        </button>
      </div>
    </div>
  </div>
</section>

<section class="dr3-welcome" id="about" aria-labelledby="dr3-welcome-title">
  <div class="dr3-shell dr3-welcome-grid">
    <figure class="dr3-welcome-media">
      <img
        src="<?= sf_asset($desertRioAssets['welcome']) ?>"
        alt="Private desert residence with a pool and mountain views"
        loading="lazy"
        decoding="async"
      >
      <figcaption>Scottsdale, Arizona</figcaption>
    </figure>

    <div class="dr3-welcome-copy">
      <p class="dr3-eyebrow">Welcome To</p>
      <h2 id="dr3-welcome-title">The Desert’s Most<br><em>Exclusive Playground.</em></h2>
      <div class="dr3-mini-rule" aria-hidden="true"><span></span><b>✦</b><span></span></div>
      <p class="dr3-body-copy">
        Follow a group of rising models, influencers, and industry insiders as they chase dreams,
        break hearts, and burn bridges—under the Arizona sun and beyond the velvet rope.
      </p>

      <div class="dr3-feature-row" aria-label="Series themes">
        <a href="<?= sf_url('series.php') ?>"><b>◇</b><span>High Fashion</span></a>
        <a href="<?= sf_url('episodes.php') ?>"><b>⌁</b><span>Pool Parties</span></a>
        <a href="<?= sf_url('cast.php') ?>"><b>♧</b><span>Arizona Luxury</span></a>
        <a href="<?= sf_url('episodes.php') ?>"><b>☾</b><span>Late-Night Drama</span></a>
      </div>
    </div>
  </div>
</section>

<section class="dr3-section dr3-cast-section" id="cast" aria-labelledby="dr3-cast-title">
  <div class="dr3-shell">
    <header class="dr3-section-heading">
      <div><span></span><h2 id="dr3-cast-title">The Cast</h2><span></span></div>
      <p>Beautiful faces. Complicated lives.</p>
    </header>

    <div class="dr3-cast-grid">
      <?php foreach ($desertRioCast as $member): ?>
        <article class="dr3-cast-card">
          <a href="<?= sf_url('cast.php') ?>" aria-label="Meet <?= htmlspecialchars($member['name'], ENT_QUOTES, 'UTF-8') ?>">
            <img
              src="<?= sf_asset($member['image']) ?>"
              alt="<?= htmlspecialchars($member['name'], ENT_QUOTES, 'UTF-8') ?>"
              loading="lazy"
              decoding="async"
            >
            <div class="dr3-cast-meta">
              <div>
                <h3><?= htmlspecialchars($member['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p><?= htmlspecialchars($member['role'], ENT_QUOTES, 'UTF-8') ?></p>
              </div>
              <span aria-hidden="true">+</span>
            </div>
          </a>
        </article>
      <?php endforeach; ?>
    </div>

    <a class="dr3-text-link" href="<?= sf_url('cast.php') ?>">Meet the Full Cast <span aria-hidden="true">→</span></a>
  </div>
</section>

<section class="dr3-section dr3-drama-section" id="episodes" aria-labelledby="dr3-drama-title">
  <div class="dr3-shell">
    <header class="dr3-section-heading">
      <div><span></span><h2 id="dr3-drama-title">Drama Runs Deep</h2><span></span></div>
      <p>Secrets. Betrayals. Obsessions.</p>
    </header>

    <div class="dr3-story-grid">
      <?php foreach ($desertRioStories as $story): ?>
        <article class="dr3-story-card">
          <a href="<?= sf_url($story['href']) ?>" aria-label="<?= htmlspecialchars($story['title'], ENT_QUOTES, 'UTF-8') ?>">
            <img
              src="<?= sf_asset($story['image']) ?>"
              alt="<?= htmlspecialchars($story['title'], ENT_QUOTES, 'UTF-8') ?>"
              loading="lazy"
              decoding="async"
            >
            <span class="dr3-story-shade" aria-hidden="true"></span>
            <div class="dr3-story-copy">
              <small><?= htmlspecialchars($story['eyebrow'], ENT_QUOTES, 'UTF-8') ?></small>
              <h3><?= htmlspecialchars($story['title'], ENT_QUOTES, 'UTF-8') ?></h3>
              <span class="dr3-story-play" aria-hidden="true">▷</span>
            </div>
          </a>
        </article>
      <?php endforeach; ?>
    </div>

    <a class="dr3-text-link" href="<?= sf_url('episodes.php') ?>">View All Episodes <span aria-hidden="true">→</span></a>
  </div>
</section>

<div
  class="home-video-modal"
  id="home-video-modal"
  role="dialog"
  aria-modal="true"
  aria-label="DesertRio trailer"
  hidden
  data-home-video-modal
>
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

    if (previousFocus && typeof previousFocus.focus === 'function') {
      previousFocus.focus();
    }
  }

  openButton.addEventListener('click', openVideoModal);
  closeButtons.forEach((button) => button.addEventListener('click', closeVideoModal));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !modal.hidden) closeVideoModal();
  });
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
