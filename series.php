<?php
$pageTitle = 'About';
$pageDescription = 'The premise, themes, and creative world behind Likenessing.';
$pageClass = 'series-app-template likenessing-about';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/header.php';
?>
<section class="lk-shell lk-page-hero">
  <div>
    <p class="lk-label">About the Series</p>
    <h1>Everyone wants your likeness.<br>Someone owns the fine print.</h1>
    <p>Likenessing is a dark workplace comedy set inside the new economy of synthetic performers. Actors, agents, lawyers, publicists, and studio executives all want the benefits of artificial intelligence—until the technology starts negotiating for itself.</p>
    <a class="lk-button lk-button-gold" href="<?= sf_url('episodes.php') ?>">Watch the Series</a>
  </div>
  <img src="<?= lk_asset_url('hero') ?>" alt="The Likenessing cast in a Hollywood office">
</section>
<section class="lk-shell lk-story-grid">
  <article>
    <p class="lk-label">The Premise</p>
    <h2>Every opportunity comes with a contract.</h2>
    <p>When a breakthrough licensing platform lets performers sell digital versions of themselves, the entertainment business discovers an endless supply of talent. The people behind those faces discover that ownership, identity, and consent are harder to automate.</p>
  </article>
  <img src="<?= lk_asset_url('premise') ?>" alt="A digital likeness appearing above a working film studio">
</section>
<section class="lk-shell lk-values">
  <article><span class="lk-icon lk-icon-account" aria-hidden="true"></span><h3>Identity</h3><p>What remains personal when your face can perform without you?</p></article>
  <article><span class="lk-icon lk-icon-lock" aria-hidden="true"></span><h3>Ownership</h3><p>Every clause protects someone. It is rarely the person signing.</p></article>
  <article><span class="lk-icon lk-icon-star" aria-hidden="true"></span><h3>Ambition</h3><p>Fame is easier to scale when the star never needs sleep.</p></article>
  <article><span class="lk-icon lk-icon-info" aria-hidden="true"></span><h3>Comedy</h3><p>The future is absurd, especially when legal approves it.</p></article>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
