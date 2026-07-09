<?php
require __DIR__ . '/includes/public_characters.php';
require __DIR__ . '/includes/theme_public.php';
$slug = trim((string)($_GET['slug'] ?? 'jax-mercer'));
$character = sf_public_character_by_slug($slug) ?: (sf_public_character_rows('active')[0] ?? null);
if (!$character) { http_response_code(404); echo 'Character not found.'; exit; }
$slug = trim((string)($character['slug'] ?? '')) ?: sf_public_character_slug((string)($character['character_name'] ?? 'character'));
$name = (string)($character['character_name'] ?? 'Character');
$tagline = sf_public_character_tagline($character);
$image = sf_public_character_image($character);
$themeCharacterPortrait = sf_theme_public_image_src('character_portrait_main', $image);
$traits = sf_public_character_traits($character);
$episodes = sf_public_character_episodes($character);
$related = sf_public_related_characters($character, 4);
$songs = sf_public_character_songs($character);
$pageTitle = $name;
$pageDescription = $name . ' character profile from the Stonefellow series.';
$pageClass = 'character-profile-page stonefellow-character-public-page';
require __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="<?= sf_asset('css/public-characters.css') ?>">
<?= sf_theme_css_variables_tag(null, '.character-profile-page') ?>
<section class="sf-character-profile">
  <nav class="sf-character-breadcrumb"><a href="<?= sf_url('index.php') ?>">Home</a><span>/</span><a href="<?= sf_url('cast.php') ?>">Cast</a><span>/</span><a href="<?= sf_url('series-characters.php') ?>">Series Characters</a><span>/</span><strong><?= htmlspecialchars($name) ?></strong></nav>

  <section class="sf-profile-hero">
    <div class="sf-profile-hero-copy">
      <div class="sf-character-eyebrow">Main Cast</div>
      <h1><?= htmlspecialchars($name) ?></h1>
      <h2><?= htmlspecialchars($tagline) ?></h2>
      <p><?= htmlspecialchars($character['short_bio'] ?? 'A Stonefellow character with a story to tell.') ?></p>
      <div class="sf-profile-stat-row">
        <span><b>Role</b><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string)($character['role_type'] ?? 'lead')))) ?></span>
        <span><b>First Seen</b>S1, Episode 1<br>“Dust & Devotion”</span>
        <span><b>Alignment</b>Chaotic Good</span>
        <span><b>Status</b><?= htmlspecialchars(ucfirst((string)($character['status'] ?? 'active'))) ?></span>
      </div>
    </div>
    <div class="sf-profile-portrait" style="background-image:url('<?= htmlspecialchars($themeCharacterPortrait) ?>')"></div>
  </section>

  <section class="sf-profile-top-grid">
    <article class="sf-profile-panel sf-overview-panel">
      <div class="sf-character-section-label">Overview</div>
      <p><?= nl2br(htmlspecialchars($character['season_arc'] ?? $character['short_bio'] ?? 'This character carries one of the emotional threads of the Stonefellow story.')) ?></p>
      <h3>Traits</h3>
      <div class="sf-trait-row"><?php foreach ($traits as $trait): ?><span><?= htmlspecialchars($trait) ?></span><?php endforeach; ?></div>
    </article>
    <article class="sf-profile-quote-card"><div>“</div><p>I write the songs so others don’t have to say the words out loud.</p><span>— <?= htmlspecialchars($name) ?></span></article>
    <article class="sf-profile-panel sf-details-panel">
      <div class="sf-character-section-label">Character Details</div>
      <dl>
        <dt>Archetype</dt><dd><?= htmlspecialchars($tagline) ?></dd>
        <dt>Occupation</dt><dd><?= htmlspecialchars($character['actor_name'] ?: 'Stonefellow Story Character') ?></dd>
        <dt>Home Base</dt><dd>On the Road / Anywhere</dd>
        <dt>Season Debut</dt><dd>Season 1</dd>
        <dt>Signature Item</dt><dd>Worn Leather Journal</dd>
        <dt>Relationships</dt><dd><?= nl2br(htmlspecialchars(str_replace(';', "\n", (string)($character['relationship_notes'] ?? 'Stonefellow ensemble')))) ?></dd>
      </dl>
    </article>
  </section>

  <section class="sf-profile-panel sf-story-arc-panel">
    <div class="sf-character-section-label">Story Arc</div>
    <div class="sf-story-timeline">
      <?php foreach (array_slice($episodes, 0, 5) as $episode): ?>
        <article><span></span><h3><?= htmlspecialchars($episode['episode']) ?><br><?= htmlspecialchars($episode['title']) ?></h3><p><?= htmlspecialchars($episode['role']) ?></p></article>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="sf-profile-main-grid">
    <article class="sf-profile-panel sf-appearances-panel">
      <div class="sf-character-section-label">Appearances</div>
      <div class="sf-appearance-table">
        <div class="sf-appearance-head"><span>Episode</span><span>Title</span><span>Role in Episode</span><span>Watch</span></div>
        <?php foreach ($episodes as $episode): ?>
          <div class="sf-appearance-row"><span><?= htmlspecialchars($episode['episode']) ?></span><strong><?= htmlspecialchars($episode['title']) ?></strong><p><?= htmlspecialchars($episode['role']) ?></p><a href="<?= sf_url('episodes.php') ?>"><?= htmlspecialchars($episode['action']) ?></a></div>
        <?php endforeach; ?>
      </div>
    </article>
    <aside class="sf-profile-panel sf-related-panel">
      <div class="sf-related-head"><div class="sf-character-section-label">Related Characters</div><a href="<?= sf_url('series-characters.php') ?>">View All</a></div>
      <?php foreach ($related as $item): $relatedSlug = trim((string)($item['slug'] ?? '')) ?: sf_public_character_slug((string)($item['character_name'] ?? 'character')); ?>
        <a class="sf-related-character" href="<?= sf_url('character.php?slug=' . urlencode($relatedSlug)) ?>"><img src="<?= sf_asset(sf_public_character_image($item)) ?>" alt="<?= htmlspecialchars($item['character_name'] ?? 'Character') ?>"><span><strong><?= htmlspecialchars($item['character_name'] ?? 'Character') ?></strong><em><?= htmlspecialchars(sf_public_character_tagline($item)) ?></em><small><?= htmlspecialchars($item['short_bio'] ?? '') ?></small></span><b>→</b></a>
      <?php endforeach; ?>
    </aside>
  </section>

  <section class="sf-profile-main-grid sf-profile-lower-grid">
    <article class="sf-profile-panel sf-gallery-panel">
      <div class="sf-character-section-label">Gallery / Visual Moments</div>
      <div class="sf-gallery-strip">
        <img src="<?= htmlspecialchars($themeCharacterPortrait) ?>" alt="<?= htmlspecialchars($name) ?> portrait">
        <img src="<?= sf_asset('images/episodes/template-card-01.png') ?>" alt="Stonefellow scene moment">
        <img src="<?= sf_asset('images/episodes/template-card-03.png') ?>" alt="Stonefellow scene moment">
        <img src="<?= sf_asset('images/music/music-live-02.png') ?>" alt="Stonefellow live moment">
      </div>
    </article>
    <article class="sf-profile-panel sf-songs-panel">
      <div class="sf-character-section-label">Songs Tied to <?= htmlspecialchars(strtok($name, ' ') ?: $name) ?></div>
      <div class="sf-character-song-list">
        <?php foreach ($songs as $song): ?><div><button type="button">▶</button><strong><?= htmlspecialchars($song['title']) ?></strong><span><?= htmlspecialchars($song['artist']) ?></span><em><?= htmlspecialchars($song['duration']) ?></em><a href="<?= sf_url('music.php') ?>">Listen</a></div><?php endforeach; ?>
      </div>
    </article>
  </section>

  <section class="sf-profile-banner"><div><h2>The songs are all we leave behind.</h2><p>Every scar, every mile, every truth — it’s all in the songs.</p></div><img src="<?= htmlspecialchars($themeCharacterPortrait) ?>" alt="<?= htmlspecialchars($name) ?> performance moment"></section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
