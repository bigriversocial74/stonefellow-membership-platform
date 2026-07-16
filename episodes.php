<?php
$pageTitle = 'Episodes';
$pageDescription = 'Browse DesertRio episodes, previews, and subscriber access.';
$pageClass = 'episodes-template desertrio-episodes-template';

require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/desertrio_theme.php';
require __DIR__ . '/includes/header.php';

$featuredEpisode = $desertRioEpisodes[0];
?>

<div class="dr-inner-page">
  <section class="dr-episodes-layout" aria-labelledby="dr-episodes-title">
    <aside class="dr-episode-list-panel">
      <div class="dr-episode-list-head">
        <h1 id="dr-episodes-title">Episodes</h1>
        <label>
          <span class="sr-only">Choose season</span>
          <select class="dr-season-select" aria-label="Choose season">
            <option>Season 1</option>
          </select>
        </label>
      </div>

      <div class="dr-episode-list" role="list">
        <?php foreach ($desertRioEpisodes as $index => $episode): ?>
          <button
            class="dr-episode-list-item<?= $index === 0 ? ' is-active' : '' ?>"
            type="button"
            role="listitem"
            data-dr-episode
            data-index="<?= $index ?>"
            data-title="<?= htmlspecialchars($episode['title'], ENT_QUOTES, 'UTF-8') ?>"
            data-season="<?= htmlspecialchars($episode['season'], ENT_QUOTES, 'UTF-8') ?>"
            data-runtime="<?= htmlspecialchars($episode['runtime'], ENT_QUOTES, 'UTF-8') ?>"
            data-description="<?= htmlspecialchars($episode['description'], ENT_QUOTES, 'UTF-8') ?>"
            data-image="<?= sf_asset($episode['image']) ?>"
            data-watch-url="<?= sf_url('watch.php?slug=' . urlencode($episode['video_slug'])) ?>"
            data-detail-url="<?= sf_url('episode.php?slug=' . urlencode($episode['slug'])) ?>"
            aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>"
          >
            <strong><?= htmlspecialchars($episode['number'], ENT_QUOTES, 'UTF-8') ?></strong>
            <img src="<?= sf_asset($episode['image']) ?>" alt="" loading="lazy" decoding="async">
            <span><h2><?= htmlspecialchars($episode['title'], ENT_QUOTES, 'UTF-8') ?></h2><small><?= htmlspecialchars($episode['runtime'], ENT_QUOTES, 'UTF-8') ?></small></span>
          </button>
        <?php endforeach; ?>
      </div>
    </aside>

    <section class="dr-episode-feature" aria-live="polite">
      <div class="dr-episode-feature-media">
        <img data-dr-feature-image src="<?= sf_asset($featuredEpisode['image']) ?>" alt="<?= htmlspecialchars($featuredEpisode['title'], ENT_QUOTES, 'UTF-8') ?> episode artwork">
        <a data-dr-feature-watch class="dr-episode-feature-play" href="<?= sf_url('watch.php?slug=' . urlencode($featuredEpisode['video_slug'])) ?>" aria-label="Watch <?= htmlspecialchars($featuredEpisode['title'], ENT_QUOTES, 'UTF-8') ?>">▷</a>
      </div>
      <div class="dr-episode-feature-copy">
        <div>
          <small data-dr-feature-season><?= htmlspecialchars($featuredEpisode['season'], ENT_QUOTES, 'UTF-8') ?></small>
          <h2 data-dr-feature-title><?= htmlspecialchars($featuredEpisode['title'], ENT_QUOTES, 'UTF-8') ?></h2>
          <p data-dr-feature-description><?= htmlspecialchars($featuredEpisode['description'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="dr-episode-feature-runtime" data-dr-feature-runtime><?= htmlspecialchars($featuredEpisode['runtime'], ENT_QUOTES, 'UTF-8') ?></div>
        <div class="dr-episode-feature-actions">
          <a data-dr-feature-watch-button class="dr-button dr-button-primary dr-button-square" href="<?= sf_url('watch.php?slug=' . urlencode($featuredEpisode['video_slug'])) ?>">Watch Episode <span class="dr-button-play" aria-hidden="true">▷</span></a>
          <a data-dr-feature-detail class="dr-button dr-button-square" href="<?= sf_url('episode.php?slug=' . urlencode($featuredEpisode['slug'])) ?>">Episode Details</a>
        </div>
      </div>
    </section>
  </section>

  <section class="dr-gallery-section" id="gallery" aria-labelledby="dr-gallery-title">
    <header class="dr-section-head">
      <div><span></span><h2 id="dr-gallery-title">From the Series</h2><span></span></div>
      <p>Poolside promises, private conversations, and Arizona after dark.</p>
    </header>
    <div class="dr-gallery-grid">
      <?php foreach ($desertRioStories as $story): ?>
        <a href="<?= sf_url($story['href']) ?>" aria-label="<?= htmlspecialchars($story['title'], ENT_QUOTES, 'UTF-8') ?>">
          <img src="<?= sf_asset($story['image']) ?>" alt="<?= htmlspecialchars($story['title'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async">
        </a>
      <?php endforeach; ?>
    </div>
  </section>
</div>

<script>
(function () {
  const items = Array.from(document.querySelectorAll('[data-dr-episode]'));
  const image = document.querySelector('[data-dr-feature-image]');
  const season = document.querySelector('[data-dr-feature-season]');
  const title = document.querySelector('[data-dr-feature-title]');
  const description = document.querySelector('[data-dr-feature-description]');
  const runtime = document.querySelector('[data-dr-feature-runtime]');
  const watchLinks = document.querySelectorAll('[data-dr-feature-watch], [data-dr-feature-watch-button]');
  const detailLink = document.querySelector('[data-dr-feature-detail]');

  if (!items.length || !image || !title) return;

  items.forEach((item) => {
    item.addEventListener('click', () => {
      items.forEach((candidate) => {
        const active = candidate === item;
        candidate.classList.toggle('is-active', active);
        candidate.setAttribute('aria-pressed', active ? 'true' : 'false');
      });

      image.src = item.dataset.image || image.src;
      image.alt = (item.dataset.title || 'DesertRio') + ' episode artwork';
      season.textContent = item.dataset.season || '';
      title.textContent = item.dataset.title || '';
      description.textContent = item.dataset.description || '';
      runtime.textContent = item.dataset.runtime || '';
      watchLinks.forEach((link) => { link.href = item.dataset.watchUrl || '#'; });
      if (detailLink) detailLink.href = item.dataset.detailUrl || '#';
    });
  });
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
