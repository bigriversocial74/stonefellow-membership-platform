<?php
$pageTitle = 'Episodes';
$pageDescription = 'Watch Likenessing episodes, trailers, previews, and exclusive production material.';
$pageClass = 'episodes-template likenessing-episodes';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="<?= sf_asset('css/likenessing-media-v4.css?v=20260717') ?>">
<?php
$episodes = [
  ['number'=>'01','title'=>'Pilot','runtime'=>'28 min','description'=>'A struggling actor receives an offer that could change everything—including who owns him.','asset'=>'episode_pilot','slug'=>'first-to-fall'],
  ['number'=>'02','title'=>'Fine Print','runtime'=>'26 min','description'=>'One clause can make you rich. Another can own every version of you.','asset'=>'episode_fine_print','slug'=>'riptide-hearts'],
  ['number'=>'03','title'=>'Double Exposure','runtime'=>'29 min','description'=>'The first synthetic duplicate arrives with stronger reviews than the original.','asset'=>'episode_double_exposure','slug'=>'the-long-road-home'],
  ['number'=>'04','title'=>'Commercial Break','runtime'=>'25 min','description'=>'A likeness campaign sells soap, confidence, and somebody else’s dignity.','asset'=>'episode_commercial_break','slug'=>'first-to-fall'],
  ['number'=>'05','title'=>"Who's Driving?",'runtime'=>'31 min','description'=>'The AI books the role its owner wanted and refuses to give it back.','asset'=>'episode_whos_driving','slug'=>'first-to-fall'],
];
$featured = $episodes[0];
?>
<section class="lk-shell lk-page-title lk-episodes-title">
  <p class="lk-label">Season One</p>
  <h1>Every episode has<br>a rights holder.</h1>
  <p>Watch the contracts get longer, the performances get stranger, and the human talent become increasingly optional.</p>
</section>

<section class="lk-shell lk-episodes-feature">
  <div class="lk-episodes-feature-art">
    <img src="<?= lk_asset_url($featured['asset']) ?>" alt="Pilot episode still" fetchpriority="high" decoding="async">
    <a class="lk-feature-play" href="<?= sf_url('watch.php?slug=first-to-fall-full-episode') ?>" aria-label="Watch the pilot"><span class="lk-icon lk-icon-play" aria-hidden="true"></span></a>
  </div>
  <div class="lk-episodes-feature-copy">
    <p class="lk-label">Episode <?= htmlspecialchars($featured['number'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($featured['runtime'], ENT_QUOTES, 'UTF-8') ?></p>
    <h2><?= htmlspecialchars($featured['title'], ENT_QUOTES, 'UTF-8') ?></h2>
    <p><?= htmlspecialchars($featured['description'], ENT_QUOTES, 'UTF-8') ?></p>
    <div class="lk-actions"><a class="lk-button lk-button-gold" href="<?= sf_url('watch.php?slug=first-to-fall-full-episode') ?>">Watch Now</a><a class="lk-button lk-button-dark" href="<?= sf_url('watchlist.php') ?>">Add to My List</a></div>
  </div>
</section>

<section class="lk-shell lk-episode-library" aria-labelledby="lk-all-episodes">
  <div class="lk-section-head"><h2 id="lk-all-episodes">All Episodes</h2><p>Season 1</p></div>
  <div class="lk-episode-library-grid">
    <?php foreach ($episodes as $episode): ?>
      <article class="lk-library-card">
        <a class="lk-library-art" href="<?= sf_url('episode.php?slug=' . urlencode($episode['slug'])) ?>">
          <img src="<?= lk_asset_url($episode['asset']) ?>" alt="<?= htmlspecialchars($episode['title'], ENT_QUOTES, 'UTF-8') ?> episode still" loading="lazy" decoding="async">
          <span class="lk-library-number">EP <?= htmlspecialchars($episode['number'], ENT_QUOTES, 'UTF-8') ?></span>
          <span class="lk-library-play lk-icon lk-icon-play" aria-hidden="true"></span>
        </a>
        <div><small><?= htmlspecialchars($episode['runtime'], ENT_QUOTES, 'UTF-8') ?></small><h2><?= htmlspecialchars($episode['title'], ENT_QUOTES, 'UTF-8') ?></h2><p><?= htmlspecialchars($episode['description'], ENT_QUOTES, 'UTF-8') ?></p><a href="<?= sf_url('episode.php?slug=' . urlencode($episode['slug'])) ?>">Episode Details →</a></div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="lk-newsletter" aria-labelledby="lk-episode-newsletter-title">
  <div class="lk-shell lk-newsletter-inner">
    <div><h2 id="lk-episode-newsletter-title">Stay in the Loop</h2><p>Get episode announcements, production notes, and early access to new stories.</p></div>
    <form action="<?= sf_url('signup.php') ?>" method="get"><label class="sr-only" for="lk-episode-email">Email address</label><input id="lk-episode-email" type="email" name="email" placeholder="Enter your email" required><button type="submit">Subscribe</button></form>
    <img src="<?= lk_asset_url('newsletter') ?>" alt="Behind-the-scenes production photographs" loading="lazy" decoding="async">
    <p class="lk-signoff">See you<br>on set.</p>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
