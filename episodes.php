<?php
$pageTitle = 'Episodes';
$pageDescription = 'Browse Stonefellow episodes, previews, watch progress, and behind-the-song content.';
$pageClass = 'episodes-template';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/theme_public.php';

$themeEpisodePosterPath = sf_theme_public_image('episode_poster', 'images/episodes/template-card-01.png');
$themeEpisodePoster = sf_asset($themeEpisodePosterPath);
$episodeCards = [
  ['num'=>'1','title'=>'First to Fall','time'=>'48 min','image'=>$themeEpisodePosterPath,'slug'=>'first-to-fall','video_slug'=>'first-to-fall-full-episode'],
  ['num'=>'2','title'=>'Riptide Hearts','time'=>'44 min','image'=>'images/episodes/template-card-02.png','slug'=>'riptide-hearts','video_slug'=>'riptide-hearts-full-episode'],
  ['num'=>'3','title'=>'The Long Road Home','time'=>'46 min','image'=>'images/episodes/template-card-03.png','slug'=>'the-long-road-home','video_slug'=>'the-long-road-home-full-episode'],
  ['num'=>'4','title'=>'Burn It Down','time'=>'47 min','image'=>'images/episodes/template-card-04.png','slug'=>'first-to-fall','video_slug'=>'first-to-fall-trailer'],
  ['num'=>'5','title'=>'Nothing Left','time'=>'43 min','image'=>'images/episodes/template-card-05.png','slug'=>'first-to-fall','video_slug'=>'first-to-fall-trailer'],
];
require __DIR__ . '/includes/header.php';
?>
<?= sf_theme_css_variables_tag(null, '.episodes-template') ?>
<section class="episodes-page">
  <section class="episodes-hero">
    <div class="episodes-hero-copy">
      <h1>Episodes</h1>
      <p>Every chapter tells the story.<br>Stream every episode. Live the journey.</p>
      <div class="episodes-actions">
        <a href="subscribe.php" class="ep-btn ep-primary"><span class="ep-play-small"></span>Start Watching</a>
        <a href="#pilot" class="ep-btn ep-outline"><span class="ep-circle-icon">▶</span>Trailer</a>
      </div>
    </div>
    <div class="episodes-hero-art">
      <img src="<?= htmlspecialchars($themeEpisodePoster) ?>" alt="Stonefellow band episode hero">
    </div>
  </section>

  <section id="pilot" class="pilot-feature-panel">
    <div class="pilot-image-wrap">
      <img src="<?= htmlspecialchars($themeEpisodePoster) ?>" alt="Pilot episode preview">
      <span class="large-play">▶</span>
    </div>
    <div class="pilot-copy">
      <span class="pilot-kicker">Pilot Episode</span>
      <h2>First to Fall</h2>
      <small>48 min</small>
      <p>The band’s rise begins in the ashes of everything they thought they were. One night. One decision. Everything changes.</p>
      <a href="<?= sf_url('watch.php?slug=first-to-fall-full-episode') ?>" class="ep-btn ep-primary compact">Watch Now</a>
    </div>
  </section>

  <section class="episodes-library-section">
    <div class="episodes-section-head">
      <h2>All Episodes</h2>
      <button type="button" class="season-select">Season 1⌄</button>
    </div>
    <div class="episodes-card-row">
      <?php foreach ($episodeCards as $ep): ?>
        <a class="episode-tile" href="<?= sf_url('episode.php?slug=' . urlencode($ep['slug'])) ?>">
          <div class="episode-thumb">
            <img src="<?= sf_asset($ep['image']) ?>" alt="<?= htmlspecialchars($ep['title']) ?> episode thumbnail">
            <span class="tile-play">▶</span>
          </div>
          <div class="episode-tile-body">
            <span><?= htmlspecialchars($ep['num']) ?></span>
            <h3><?= htmlspecialchars($ep['title']) ?></h3>
            <small><?= htmlspecialchars($ep['time']) ?></small>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="continue-section">
    <h2>Continue Watching</h2>
    <div class="continue-grid">
      <a class="continue-item" href="<?= sf_url('watch.php?slug=first-to-fall-full-episode') ?>">
        <div class="continue-thumb"><img src="<?= sf_asset('images/episodes/template-continue-01.png') ?>" alt="Burn it Down episode"><span class="tile-play">▶</span></div>
        <div class="continue-info">
          <h3>Burn It Down</h3>
          <p>Episode 4</p>
          <div class="watch-progress"><span style="width:56%"></span></div>
          <small>32 min left</small>
        </div>
      </a>
      <a class="continue-item" href="<?= sf_url('watch.php?slug=the-long-road-home-full-episode') ?>">
        <div class="continue-thumb"><img src="<?= sf_asset('images/episodes/template-continue-02.png') ?>" alt="Long Road Home episode"></div>
        <div class="continue-info">
          <h3>Long Road Home</h3>
          <p>Episode 3</p>
          <div class="watch-progress"><span style="width:70%"></span></div>
          <small>18 min left</small>
        </div>
      </a>
    </div>
  </section>

  <section class="behind-song-section">
    <div class="behind-copy">
      <h2>Behind the Song</h2>
      <p>Go deeper into the music with exclusive stories and studio sessions.</p>
      <a href="music.php" class="ep-btn ep-outline compact">View All</a>
    </div>
    <div class="behind-grid">
      <a href="music.php" class="behind-card"><img src="<?= sf_asset('images/episodes/template-behind-01.png') ?>" alt="Studio session"><span class="tile-play">▶</span></a>
      <a href="music.php" class="behind-card"><img src="<?= sf_asset('images/episodes/template-behind-02.png') ?>" alt="Mixing console"><span class="tile-play">▶</span></a>
      <a href="music.php" class="behind-card"><img src="<?= sf_asset('images/episodes/template-behind-03.png') ?>" alt="Songwriting session"></a>
    </div>
  </section>

  <section class="episodes-cta-banner">
    <img src="<?= sf_asset('images/episodes/template-cta-road.png') ?>" alt="Road banner background">
    <div class="episodes-cta-copy">
      <h2>Watch the Series. Stream the Soundtrack.</h2>
      <p>Join the journey of Stonefellow.</p>
      <a href="subscribe.php" class="ep-btn ep-primary compact">Join Now</a>
    </div>
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
