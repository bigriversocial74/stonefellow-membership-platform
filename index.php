<?php
$pageTitle = 'Home';
$pageDescription = 'Watch the Stonefellow series, stream the soundtrack, meet the band, and join the story.';
$pageClass = 'home-template sf-reskin-home';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/theme_public.php';

$homeHero = sf_theme_public_image_src('home_hero', 'images/home/hero-reference-crop.png');
$homeEpisodePoster = sf_theme_public_image_src('episode_poster', 'images/home/pilot-reference-card.png');
$homeMusicHero = sf_theme_public_image_src('music_hero', 'images/home/live-reference-card.png');

$homeCast = [
  ['name' => 'Jax Stonefellow', 'role' => 'The Voice', 'image' => 'images/cast/cast-jax.png'],
  ['name' => 'Cash Hawthorne', 'role' => 'The Rebel', 'image' => 'images/cast/cast-cash.png'],
  ['name' => 'Violet Graves', 'role' => 'The Anchor', 'image' => 'images/cast/cast-violet.png'],
  ['name' => 'Sawyer Creed', 'role' => 'The Pulse', 'image' => 'images/cast/cast-sawyer.png'],
  ['name' => 'Luke Mercer', 'role' => 'The Soul', 'image' => 'images/cast/cast-luke.png'],
];

$homeStories = [
  ['kicker' => 'Pilot Episode', 'title' => 'First to Fall', 'image' => 'images/episodes/template-card-01.png', 'href' => 'episode.php?slug=first-to-fall'],
  ['kicker' => 'Trust Is Earned', 'title' => 'Riptide Hearts', 'image' => 'images/episodes/template-card-02.png', 'href' => 'episode.php?slug=riptide-hearts'],
  ['kicker' => 'The Road Calls', 'title' => 'The Long Road Home', 'image' => 'images/episodes/template-card-03.png', 'href' => 'episode.php?slug=the-long-road-home'],
  ['kicker' => 'Featured Music', 'title' => 'Born to Burn', 'image' => 'images/home/live-reference-card.png', 'href' => 'music.php'],
];

require __DIR__ . '/includes/header.php';
?>
<?= sf_theme_css_variables_tag(null, '.home-template') ?>

<section class="sf-lux-hero" aria-labelledby="sf-home-title">
  <div class="sf-lux-hero-media">
    <img src="<?= htmlspecialchars($homeHero, ENT_QUOTES, 'UTF-8') ?>" alt="Stonefellow performing live on stage" fetchpriority="high">
  </div>
  <div class="sf-lux-hero-wash" aria-hidden="true"></div>
  <div class="sf-lux-hero-copy">
    <p class="sf-lux-eyebrow">THE ROAD IS LOUD.<br>THE TRUTH IS LOUDER.</p>
    <h1 id="sf-home-title">Stonefellow</h1>
    <div class="sf-lux-ornament" aria-hidden="true"><span></span><b>✦</b><span></span></div>
    <h2>ROCK. BROTHERHOOD. DRAMA.</h2>
    <p class="sf-lux-tagline">EVERY SONG LEAVES A SCAR.</p>
    <div class="sf-lux-actions">
      <a class="sf-lux-btn sf-lux-btn-primary" href="<?= sf_url('subscribe.php') ?>">STREAM NOW <span aria-hidden="true">▷</span></a>
      <button class="sf-lux-btn sf-lux-btn-outline" type="button" data-home-video-open data-video-src="https://www.youtube.com/embed/jMlQMre7LcA?start=14&amp;autoplay=1&amp;rel=0&amp;modestbranding=1" aria-haspopup="dialog" aria-controls="home-video-modal">WATCH TRAILER <span aria-hidden="true">▷</span></button>
    </div>
  </div>
</section>

<section class="sf-lux-welcome" id="about" aria-labelledby="sf-welcome-title">
  <div class="sf-lux-welcome-art">
    <img src="<?= htmlspecialchars($homeMusicHero, ENT_QUOTES, 'UTF-8') ?>" alt="Stonefellow live performance">
    <span class="sf-lux-script">Born on the road</span>
  </div>
  <div class="sf-lux-welcome-copy">
    <p class="sf-lux-section-kicker">WELCOME TO</p>
    <h2 id="sf-welcome-title">A Story Written<br><em>In Sound and Fire.</em></h2>
    <div class="sf-lux-small-ornament" aria-hidden="true"><span></span><b>✦</b><span></span></div>
    <p>Follow five musicians bound by brotherhood, broken by ambition, and pulled forward by the music they cannot leave behind.</p>
    <div class="sf-lux-feature-grid">
      <a href="<?= sf_url('music.php') ?>"><b aria-hidden="true">♫</b><span>ORIGINAL MUSIC</span></a>
      <a href="<?= sf_url('episodes.php') ?>"><b aria-hidden="true">▷</b><span>FULL EPISODES</span></a>
      <a href="<?= sf_url('cast.php') ?>"><b aria-hidden="true">✦</b><span>THE BAND</span></a>
      <a href="<?= sf_url('merch.php') ?>"><b aria-hidden="true">◆</b><span>OFFICIAL MERCH</span></a>
    </div>
  </div>
</section>

<section class="sf-lux-section sf-lux-cast" id="cast" aria-labelledby="sf-cast-title">
  <header class="sf-lux-section-head">
    <div><span></span><h2 id="sf-cast-title">THE BAND</h2><span></span></div>
    <p>FIVE LIVES. ONE SOUND. NO EASY WAY HOME.</p>
  </header>
  <div class="sf-lux-cast-grid">
    <?php foreach ($homeCast as $member): ?>
      <article class="sf-lux-cast-card">
        <a href="<?= sf_url('cast.php') ?>" aria-label="View <?= htmlspecialchars($member['name'], ENT_QUOTES, 'UTF-8') ?> in the cast">
          <img src="<?= sf_asset($member['image']) ?>" alt="<?= htmlspecialchars($member['name'], ENT_QUOTES, 'UTF-8') ?>">
          <div><h3><?= htmlspecialchars($member['name'], ENT_QUOTES, 'UTF-8') ?></h3><p><?= htmlspecialchars($member['role'], ENT_QUOTES, 'UTF-8') ?></p><span aria-hidden="true">+</span></div>
        </a>
      </article>
    <?php endforeach; ?>
  </div>
  <a class="sf-lux-text-link" href="<?= sf_url('cast.php') ?>">MEET THE FULL CAST <span aria-hidden="true">→</span></a>
</section>

<section class="sf-lux-section sf-lux-drama" id="episodes" aria-labelledby="sf-drama-title">
  <header class="sf-lux-section-head">
    <div><span></span><h2 id="sf-drama-title">THE STORY RUNS DEEP</h2><span></span></div>
    <p>SONGS. SECRETS. BROKEN PROMISES.</p>
  </header>
  <div class="sf-lux-story-grid">
    <?php foreach ($homeStories as $story): ?>
      <article class="sf-lux-story-card">
        <a href="<?= sf_url($story['href']) ?>">
          <img src="<?= sf_asset($story['image']) ?>" alt="<?= htmlspecialchars($story['title'], ENT_QUOTES, 'UTF-8') ?>">
          <div><small><?= htmlspecialchars($story['kicker'], ENT_QUOTES, 'UTF-8') ?></small><h3><?= htmlspecialchars($story['title'], ENT_QUOTES, 'UTF-8') ?></h3><span aria-hidden="true">▷</span></div>
        </a>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="sf-lux-membership" aria-labelledby="sf-membership-title">
  <div>
    <p class="sf-lux-section-kicker">THE COMPLETE EXPERIENCE</p>
    <h2 id="sf-membership-title">Watch the Series.<br><em>Stream the Soundtrack.</em></h2>
    <p>Unlock every episode, the complete music catalog, member playlists, progress tracking, exclusive releases, and behind-the-scenes content.</p>
  </div>
  <div class="sf-lux-membership-actions">
    <a class="sf-lux-btn sf-lux-btn-primary" href="<?= sf_url('subscribe.php') ?>">JOIN STONEFELLOW</a>
    <a class="sf-lux-btn sf-lux-btn-outline" href="<?= sf_url('series.php') ?>">EXPLORE THE SERIES</a>
  </div>
  <img src="<?= htmlspecialchars($homeEpisodePoster, ENT_QUOTES, 'UTF-8') ?>" alt="Stonefellow series artwork">
</section>

<div class="home-video-modal" id="home-video-modal" role="dialog" aria-modal="true" aria-label="Stonefellow trailer" hidden data-home-video-modal>
  <div class="home-video-backdrop" data-home-video-close></div>
  <div class="home-video-dialog" role="document">
    <button class="home-video-close" type="button" aria-label="Close trailer" data-home-video-close>×</button>
    <div class="home-video-frame-wrap"><iframe data-home-video-frame title="Stonefellow trailer" src="" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div>
  </div>
</div>

<script>
(function(){
  const openButton = document.querySelector('[data-home-video-open]');
  const modal = document.querySelector('[data-home-video-modal]');
  const frame = document.querySelector('[data-home-video-frame]');
  const closeButtons = document.querySelectorAll('[data-home-video-close]');
  if (!openButton || !modal || !frame) return;
  let previousFocus = null;
  function openVideoModal(){
    previousFocus = document.activeElement;
    frame.src = openButton.dataset.videoSrc || '';
    modal.hidden = false;
    document.body.classList.add('home-video-modal-open');
    const closeButton = modal.querySelector('.home-video-close');
    if (closeButton) closeButton.focus();
  }
  function closeVideoModal(){
    modal.hidden = true;
    frame.src = '';
    document.body.classList.remove('home-video-modal-open');
    if (previousFocus && typeof previousFocus.focus === 'function') previousFocus.focus();
  }
  openButton.addEventListener('click', openVideoModal);
  closeButtons.forEach((button) => button.addEventListener('click', closeVideoModal));
  document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !modal.hidden) closeVideoModal(); });
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>