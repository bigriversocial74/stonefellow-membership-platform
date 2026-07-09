<?php
$pageTitle = 'Series Characters';
$pageDescription = 'Meet the outlaws, dreamers, drifters, and voices behind the Stonefellow story.';
$pageClass = 'series-characters-page stonefellow-character-public-page';
require __DIR__ . '/includes/public_characters.php';
require __DIR__ . '/includes/theme_public.php';
$characters = sf_public_character_rows('active');
$featuredCharacters = array_slice($characters, 0, 3);
$seriesCharactersHero = sf_theme_public_image_src('series_characters_hero', 'images/cast/cast-template-hero.png');
require __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="<?= sf_asset('css/public-characters.css') ?>">
<?= sf_theme_css_variables_tag(null, '.series-characters-page') ?>
<section class="sf-character-directory">
  <section class="sf-character-hero sf-character-directory-hero" style="background:linear-gradient(90deg,rgba(4,3,2,.96),rgba(4,3,2,.68) 48%,rgba(4,3,2,.18)),url('<?= htmlspecialchars($seriesCharactersHero) ?>') center right/cover no-repeat;">
    <div class="sf-character-hero-copy">
      <div class="sf-character-eyebrow">The Story. The Soul.</div>
      <h1>Series Characters</h1>
      <div class="sf-character-gold-rule"></div>
      <p>Meet the outlaws, dreamers, drifters, and voices behind the Stonefellow story.</p>
    </div>
    <div class="sf-character-hero-silhouettes" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></div>
  </section>

  <section class="sf-character-directory-shell">
    <div class="sf-character-filters">
      <label class="sf-character-search"><span>⌕</span><input type="search" placeholder="Search characters..." aria-label="Search characters"></label>
      <a class="is-active" href="<?= sf_url('series-characters.php') ?>">All Characters</a>
      <a href="<?= sf_url('series-characters.php#all-characters') ?>">Main Cast</a>
      <a href="<?= sf_url('music.php') ?>">Musicians</a>
      <button type="button">Season 1 <span>⌄</span></button>
      <button type="button">Episode 1 <span>⌄</span></button>
    </div>

    <section class="sf-featured-character-strip">
      <div class="sf-character-section-label">Featured Characters</div>
      <div class="sf-featured-character-grid">
        <?php foreach ($featuredCharacters as $character): $slug = trim((string)($character['slug'] ?? '')) ?: sf_public_character_slug((string)($character['character_name'] ?? 'character')); $image = sf_public_character_image($character); ?>
          <article class="sf-featured-character-card">
            <div class="sf-featured-character-image" style="background-image:url('<?= htmlspecialchars(sf_asset($image)) ?>')"></div>
            <div class="sf-featured-character-copy">
              <span><?= htmlspecialchars($character['character_name'] ?? 'Character') ?></span>
              <h2><?= htmlspecialchars(sf_public_character_tagline($character)) ?></h2>
              <p><?= htmlspecialchars($character['short_bio'] ?? 'A Stonefellow character with a story to tell.') ?></p>
              <a href="<?= sf_url('character.php?slug=' . urlencode($slug)) ?>">View Profile <b>→</b></a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

    <section id="all-characters" class="sf-all-characters-section">
      <div class="sf-character-section-label">All Characters</div>
      <div class="sf-character-card-grid">
        <?php foreach ($characters as $character): $slug = trim((string)($character['slug'] ?? '')) ?: sf_public_character_slug((string)($character['character_name'] ?? 'character')); $image = sf_public_character_image($character); ?>
          <article class="sf-character-card">
            <a class="sf-character-card-image" href="<?= sf_url('character.php?slug=' . urlencode($slug)) ?>" style="background-image:url('<?= htmlspecialchars(sf_asset($image)) ?>')"></a>
            <div class="sf-character-card-body">
              <h2><?= htmlspecialchars($character['character_name'] ?? 'Character') ?></h2>
              <span><?= htmlspecialchars(sf_public_character_tagline($character)) ?></span>
              <p><?= htmlspecialchars($character['short_bio'] ?? 'A Stonefellow character with a story to tell.') ?></p>
              <a href="<?= sf_url('character.php?slug=' . urlencode($slug)) ?>">View Character <b>→</b></a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
