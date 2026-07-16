<?php
$pageTitle = 'About the Series';
$pageDescription = 'Enter the world of DesertRio, where Arizona luxury, ambition, fashion, and private rivalries collide.';
$pageClass = 'series-app-template desertrio-series-template';

require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/desertrio_theme.php';
require __DIR__ . '/includes/header.php';
?>

<div class="dr-inner-page">
  <section class="dr-inner-hero" aria-labelledby="dr-series-title">
    <div class="dr-inner-hero-media">
      <img src="<?= sf_asset($desertRioAssets['hero']) ?>" alt="DesertRio cast at a luxury Arizona poolside setting" fetchpriority="high">
    </div>
    <div class="dr-inner-hero-shade" aria-hidden="true"></div>
    <div class="dr-inner-hero-copy">
      <p class="dr-eyebrow">An Original Microdrama Series</p>
      <h1 id="dr-series-title">Welcome to<br>DesertRio.</h1>
      <p>A world of beautiful faces, big ambitions, and private rivalries where every invitation has a price and every secret eventually reaches the sunlight.</p>
      <div class="dr-inner-actions">
        <a class="dr-button dr-button-primary" href="<?= sf_url('episodes.php') ?>">Watch the Series <span class="dr-button-play" aria-hidden="true">▷</span></a>
        <a class="dr-button" href="<?= sf_url('cast.php') ?>">Meet the Cast</a>
      </div>
    </div>
  </section>

  <section class="dr-about-intro" aria-labelledby="dr-premise-title">
    <div class="dr-about-image">
      <img src="<?= sf_asset($desertRioAssets['welcome']) ?>" alt="Luxury Scottsdale home in the Arizona desert">
    </div>
    <div class="dr-about-copy">
      <p class="dr-eyebrow">The Premise</p>
      <h2 id="dr-premise-title">The Desert’s Most<br><em>Exclusive Playground.</em></h2>
      <div class="dr-small-ornament" aria-hidden="true"><span></span><b>✦</b><span></span></div>
      <p>DesertRio follows models, creators, entrepreneurs, and social insiders navigating a world where personal brands are currency and the line between friendship and opportunity disappears after dark.</p>
      <p>Across pool parties, luxury launches, private dinners, and high-stakes collaborations, the cast must decide what they are willing to reveal—and what they are willing to sacrifice—to stay in the room.</p>
      <div class="dr-about-points">
        <article><strong>01</strong><span>Arizona Luxury</span></article>
        <article><strong>02</strong><span>Fashion &amp; Influence</span></article>
        <article><strong>03</strong><span>Private Rivalries</span></article>
        <article><strong>04</strong><span>Unfiltered Drama</span></article>
      </div>
    </div>
  </section>

  <section class="dr-about-news" id="news" aria-labelledby="dr-news-title">
    <header class="dr-section-head">
      <div><span></span><h2 id="dr-news-title">Inside DesertRio</h2><span></span></div>
      <p>Stories from behind the velvet rope.</p>
    </header>
    <div class="dr-editorial-grid">
      <article class="dr-editorial-card">
        <img src="<?= sf_asset($desertRioStories[0]['image']) ?>" alt="DesertRio episode scene">
        <div><small>Series Guide</small><h3>Who Arrives With Everything to Lose?</h3><p>Meet the personalities entering the DesertRio circle and the ambitions they are bringing with them.</p></div>
      </article>
      <article class="dr-editorial-card">
        <img src="<?= sf_asset($desertRioStories[1]['image']) ?>" alt="DesertRio relationship scene">
        <div><small>Relationships</small><h3>When Chemistry Becomes Competition</h3><p>Romance and opportunity collide when private attraction turns into public leverage.</p></div>
      </article>
      <article class="dr-editorial-card">
        <img src="<?= sf_asset($desertRioStories[3]['image']) ?>" alt="Arizona sunset and palm trees">
        <div><small>Arizona After Dark</small><h3>The Parties Everyone Talks About</h3><p>From sunset gatherings to private afterparties, every location becomes part of the story.</p></div>
      </article>
    </div>
  </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
