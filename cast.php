<?php
$pageTitle = 'Cast';
$pageDescription = 'Meet the DesertRio cast and the personalities shaping the series.';
$pageClass = 'cast-template desertrio-cast-template';

require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/desertrio_theme.php';
require __DIR__ . '/includes/header.php';
?>

<div class="dr-inner-page">
  <section class="dr-inner-hero" aria-labelledby="dr-cast-page-title">
    <div class="dr-inner-hero-media">
      <img src="<?= sf_asset($desertRioAssets['hero']) ?>" alt="DesertRio cast at a poolside Arizona setting" fetchpriority="high">
    </div>
    <div class="dr-inner-hero-shade" aria-hidden="true"></div>
    <div class="dr-inner-hero-copy">
      <p class="dr-eyebrow">Beautiful Faces. Big Personalities.</p>
      <h1 id="dr-cast-page-title">The Cast.</h1>
      <p>Everyone arrives with a plan. Everyone has something to protect. In DesertRio, the most polished image can hide the most dangerous truth.</p>
      <div class="dr-inner-actions">
        <a class="dr-button dr-button-primary" href="<?= sf_url('episodes.php') ?>">Watch the Series <span class="dr-button-play" aria-hidden="true">▷</span></a>
      </div>
    </div>
  </section>

  <section class="dr-cast-page-intro" aria-labelledby="dr-cast-grid-title">
    <header class="dr-section-head">
      <div><span></span><h2 id="dr-cast-grid-title">Inside the Circle</h2><span></span></div>
      <p>Ambition, attraction, and alliances under the Arizona sun.</p>
    </header>

    <div class="dr-cast-page-grid">
      <?php foreach ($desertRioCast as $member): ?>
        <article class="dr-cast-profile">
          <img src="<?= sf_asset($member['image']) ?>" alt="<?= htmlspecialchars($member['name'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async">
          <div class="dr-cast-profile-body">
            <h2><?= htmlspecialchars($member['name'], ENT_QUOTES, 'UTF-8') ?></h2>
            <div class="dr-cast-profile-role"><?= htmlspecialchars($member['role'], ENT_QUOTES, 'UTF-8') ?></div>
            <p><?= htmlspecialchars($member['quote'], ENT_QUOTES, 'UTF-8') ?></p>
            <a href="<?= sf_url('episodes.php') ?>">See Their Story <span aria-hidden="true">→</span></a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="dr-cast-relationships" aria-labelledby="dr-relationships-title">
    <div>
      <p class="dr-eyebrow">Nothing Stays Private</p>
      <h2 id="dr-relationships-title">Every Connection<br>Changes the Game.</h2>
      <div class="dr-small-ornament" aria-hidden="true"><span></span><b>✦</b><span></span></div>
      <p>Friendships become partnerships, attraction becomes competition, and one private conversation can reshape the entire group before the night is over.</p>
      <a class="dr-button dr-button-primary" href="<?= sf_url('episodes.php') ?>">Start Watching</a>
    </div>
    <img src="<?= sf_asset($desertRioStories[1]['image']) ?>" alt="DesertRio cast relationship scene">
  </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
