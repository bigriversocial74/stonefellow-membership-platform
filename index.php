<?php
$pageTitle = 'Home';
$pageDescription = 'Watch the Stonefellow series, stream the soundtrack, and subscribe for access.';
$pageClass = 'home-template';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/header.php';

$homeTracks = [
  ['n' => '1', 'title' => 'Born to Burn', 'time' => '3:48'],
  ['n' => '2', 'title' => 'Blackout in the Rearview', 'time' => '3:35'],
  ['n' => '3', 'title' => 'Tearing Down the Walls', 'time' => '4:02'],
  ['n' => '4', 'title' => 'Heart of a Loaded Gun', 'time' => '3:57'],
  ['n' => '5', 'title' => 'Saint or Sinner', 'time' => '3:41'],
];
?>
<section class="home-full home-hero-full">
  <div class="home-shell">
    <section class="home-hero-grid">
    <div class="home-copy">
      <h1>Watch the Series. Stream the Soundtrack.</h1>
      <p>Stonefellow is a rock &amp; roll drama about brotherhood, betrayal, and the price of greatness. Stream every episode. Listen to every song. Live the story.</p>
      <div class="home-actions">
        <a class="home-btn home-btn-primary" href="subscribe.php"><span class="icon-play"></span>Subscribe to Watch</a>
        <a class="home-btn home-btn-outline" href="music.php"><span class="icon-wave"></span>Stream the Music</a>
      </div>
    </div>
    <div class="home-hero-image">
      <img src="<?= sf_asset('images/home/hero-reference-crop.png') ?>" alt="Stonefellow performing live on stage">
    </div>
    </section>
  </div>
</section>

<section class="home-shell">
  <section class="home-featured-section">
    <div class="home-section-title"><span>Featured</span></div>
    <div class="home-feature-grid">
      <a class="feature-card" href="episodes.php">
        <div class="feature-image"><img src="<?= sf_asset('images/home/pilot-reference-card.png') ?>" alt="Pilot episode still"></div>
        <div class="feature-info">
          <h3>Pilot Episode</h3>
          <p>Watch the beginning.</p>
        </div>
      </a>
      <a class="feature-card feature-card-soundtrack" href="music.php">
        <div class="feature-image soundtrack-art"><img src="<?= sf_asset('images/home/soundtrack-reference-card.png') ?>" alt="Official soundtrack cover"></div>
        <div class="feature-info">
          <h3>Official Soundtrack</h3>
          <p>Listen to every song.</p>
        </div>
      </a>
      <a class="feature-card" href="music.php">
        <div class="feature-image"><img src="<?= sf_asset('images/home/live-reference-card.png') ?>" alt="Stonefellow live sessions"></div>
        <div class="feature-info">
          <h3>Live Sessions</h3>
          <p>Acoustic &amp; live performances.</p>
        </div>
      </a>
    </div>
  </section>

  <section class="home-player-panel">
    <div class="album-column">
      <div class="album-art-wrap">
        <img src="<?= sf_asset('images/music/soundtrack-cover.png') ?>" alt="Born to Burn artwork">
      </div>
    </div>
    <div class="player-column">
      <div class="now-playing-label">Now Playing</div>
      <h2>Born to Burn</h2>
      <div class="artist-line">Stonefellow</div>
      <div class="time-row"><span>1:24</span><span>3:48</span></div>
      <div class="progress-line"><span></span></div>
      <div class="player-controls">
        <button type="button">↺</button>
        <button type="button">◀</button>
        <button type="button" class="play-circle">❚❚</button>
        <button type="button">▶</button>
        <button type="button">↻</button>
      </div>
    </div>
    <div class="track-column">
      <ol class="home-tracklist">
        <?php foreach ($homeTracks as $track): ?>
          <li>
            <span class="track-number"><?= htmlspecialchars($track['n']) ?></span>
            <span class="track-title"><?= htmlspecialchars($track['title']) ?></span>
            <span class="track-time"><?= htmlspecialchars($track['time']) ?></span>
          </li>
        <?php endforeach; ?>
      </ol>
      <a class="track-link" href="music.php">View Full Soundtrack →</a>
    </div>
  </section>

  <section class="home-access-panel">
    <div class="access-price-block">
      <div class="access-kicker">Choose Your Access</div>
      <div class="access-price"><span class="currency">$</span>9<span class="cents">99</span><span class="period">/ Month</span></div>
      <a class="home-btn home-btn-primary access-btn" href="subscribe.php">Subscribe Now</a>
      <div class="access-note">Cancel anytime.</div>
    </div>
    <ul class="access-list">
      <li>Watch every episode in HD</li>
      <li>Stream the full soundtrack</li>
      <li>Exclusive content &amp; behind-the-scenes</li>
      <li>Early access to new episodes</li>
    </ul>
  </section>

  <section class="home-app-panel">
    <div class="app-image-col">
      <img src="<?= sf_asset('images/app/app-promo.png') ?>" alt="Stonefellow mobile app promo">
    </div>
    <div class="app-copy-col">
      <h2>Take Stonefellow<br>Wherever You Go</h2>
      <p>Stream the series. Listen to the music.<br>Download the app for iOS &amp; Android.</p>
    </div>
    <div class="store-buttons-col">
      <a href="app.php" class="store-badge">Download on the App Store</a>
      <a href="app.php" class="store-badge">Get it on Google Play</a>
    </div>
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
