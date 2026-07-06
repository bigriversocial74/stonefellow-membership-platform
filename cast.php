<?php
$pageTitle = 'Cast';
$pageDescription = 'Meet the Stonefellow band members and characters.';
$pageClass = 'cast-template';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/header.php';

$castCards = [
  ['name'=>'Jax Stonefellow','role'=>'Lead Vocals / Guitar','quote'=>'I write the songs so we don’t forget who we are.','image'=>'images/cast/cast-jax.png'],
  ['name'=>'Cash Hawthorne','role'=>'Lead Guitar','quote'=>'Some roads are meant to be taken alone.','image'=>'images/cast/cast-cash.png'],
  ['name'=>'Violet Graves','role'=>'Bass','quote'=>'Keep your heart open, but your distance closer.','image'=>'images/cast/cast-violet.png'],
  ['name'=>'Sawyer Creed','role'=>'Drums','quote'=>'Time keeps the beat. I keep the time.','image'=>'images/cast/cast-sawyer.png'],
  ['name'=>'Luke Mercer','role'=>'Keys / Vocals','quote'=>'There’s beauty in every broken chord.','image'=>'images/cast/cast-luke.png'],
];

$keyEpisodes = [
  ['ep'=>'S1 · E1','title'=>'First to Fall','note'=>'The beginning of the band.','image'=>'images/episodes/template-card-01.png'],
  ['ep'=>'S1 · E4','title'=>'Riptide','note'=>'Old wounds resurface.','image'=>'images/episodes/template-card-02.png'],
  ['ep'=>'S1 · E7','title'=>'Burn It Down','note'=>'Fame comes at a cost.','image'=>'images/episodes/template-card-04.png'],
  ['ep'=>'S1 · E10','title'=>'The Road Is Calling','note'=>'Choices define the road ahead.','image'=>'images/episodes/template-card-03.png'],
  ['ep'=>'S1 · E13','title'=>'Long Road Home','note'=>'Home isn’t always where you start.','image'=>'images/episodes/template-card-05.png'],
];
?>
<section class="cast-page">
  <section class="cast-hero">
    <div class="cast-hero-copy">
      <h1>The Band.<br>The Brothers.</h1>
      <div class="cast-ornament"></div>
      <p class="cast-kicker">Every scar has a story.</p>
      <p>Stonefellow is more than a band — they’re brothers bound by blood, broken by fame, and built on a sound that won’t be forgotten.</p>
      <a class="cast-outline-btn" href="episodes.php">Watch the Series</a>
    </div>
    <div class="cast-hero-art">
      <img src="<?= sf_asset('images/cast/cast-template-hero.png') ?>" alt="Stonefellow band members">
      <span class="cast-ghost-mark">SF</span>
    </div>
  </section>

  <section class="cast-brothers">
    <div class="cast-divider-title"><span>The Stonefellow Brothers</span></div>
    <div class="cast-member-grid">
      <?php foreach ($castCards as $card): ?>
        <article class="cast-member-card">
          <div class="cast-member-image"><img src="<?= sf_asset($card['image']) ?>" alt="<?= htmlspecialchars($card['name']) ?>"></div>
          <div class="cast-member-body">
            <h2><?= htmlspecialchars($card['name']) ?></h2>
            <div class="cast-role"><?= htmlspecialchars($card['role']) ?></div>
            <p>“<?= htmlspecialchars($card['quote']) ?>”</p>
            <a href="#">View Profile <span>→</span></a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="cast-panels">
    <div class="cast-music-panel cast-panel-box">
      <div class="cast-panel-title"><span>Featured Music</span></div>
      <div class="cast-music-flex">
        <div class="cast-album"><img src="<?= sf_asset('images/music/soundtrack-cover.png') ?>" alt="Stonefellow soundtrack"></div>
        <div class="cast-player-copy">
          <span>Now Playing</span>
          <h3>Born to Burn</h3>
          <p>Stonefellow</p>
          <div class="cast-track-progress"><span></span></div>
          <div class="cast-small-controls"><b>↺</b><b>◀</b><b class="pause">Ⅱ</b><b>▶</b><b>↻</b></div>
        </div>
      </div>
      <a class="cast-wide-btn" href="music.php">Listen to Full Soundtrack</a>
      <ol class="cast-mini-tracklist">
        <?php foreach (array_slice($homeTracks ?? $songs,0,5) as $i => $song): ?>
          <li><span><?= $i + 1 ?></span><strong><?= htmlspecialchars($song['title']) ?></strong><em><?= htmlspecialchars($song['duration'] ?? '3:48') ?></em></li>
        <?php endforeach; ?>
      </ol>
      <a class="cast-link" href="music.php">View Full Soundtrack →</a>
    </div>

    <div class="cast-episodes-panel cast-panel-box">
      <div class="cast-panel-title"><span>Key Episodes</span></div>
      <div class="cast-key-list">
        <?php foreach ($keyEpisodes as $episode): ?>
          <a class="cast-key-item" href="episodes.php">
            <img src="<?= sf_asset($episode['image']) ?>" alt="<?= htmlspecialchars($episode['title']) ?>">
            <span><small><?= htmlspecialchars($episode['ep']) ?></small><strong><?= htmlspecialchars($episode['title']) ?></strong><em><?= htmlspecialchars($episode['note']) ?></em></span>
            <b>▶</b>
          </a>
        <?php endforeach; ?>
      </div>
      <a class="cast-link" href="episodes.php">View All Episodes →</a>
    </div>
  </section>

  <section class="cast-bottom-cta">
    <div>
      <h2>Want to Know Them<br>Before the World Did?</h2>
      <p>Explore the full cast, behind-the-scenes stories, and exclusive interviews.</p>
      <a class="cast-outline-btn" href="#">Explore the Cast</a>
    </div>
    <img src="<?= sf_asset('images/cast/cast-cta-stage.png') ?>" alt="Stonefellow on stage">
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
