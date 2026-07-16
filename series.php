<?php
$pageTitle = 'About';
$pageDescription = 'Discover the premise, world, and themes behind Likenessing.';
$pageClass = 'series-app-template likenessing-about';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/header.php';
?>
<section class="lk-page-hero lk-shell">
  <div><p class="lk-label">About the Series</p><h1>Everyone wants your likeness.<br>Few people read the contract.</h1><p>Likenessing is a dark workplace comedy about fame, ownership, identity, and the absurd new economy built around artificial intelligence.</p><a class="lk-button lk-button-gold" href="<?= sf_url('episodes.php') ?>"><span class="lk-icon lk-icon-play" aria-hidden="true"></span>Watch the Series</a></div>
  <img src="<?= sf_asset('images/likenessing/hero-ensemble-v2.webp') ?>" alt="The cast of Likenessing in a Hollywood office">
</section>
<section class="lk-story-grid lk-shell">
  <article><p class="lk-label">The Setup</p><h2>A new kind of Hollywood gold rush.</h2><p>Studios can generate performances around the clock. Brands can hire a face without hiring the person. Actors can earn while they sleep—assuming they still own the rights to themselves.</p></article>
  <img src="<?= sf_asset('images/likenessing/premise-ai-studio-v2.webp') ?>" alt="Digital likeness towering above a film studio">
</section>
<section class="lk-values lk-shell">
  <article><span class="lk-icon lk-icon-account" aria-hidden="true"></span><h3>Identity</h3><p>What makes a performance yours when a machine can reproduce it?</p></article>
  <article><span class="lk-icon lk-icon-lock" aria-hidden="true"></span><h3>Ownership</h3><p>Every contract redraws the boundary between talent and product.</p></article>
  <article><span class="lk-icon lk-icon-star" aria-hidden="true"></span><h3>Ambition</h3><p>Everyone wants the next role, even when the role no longer needs them.</p></article>
  <article><span class="lk-icon lk-icon-info" aria-hidden="true"></span><h3>Comedy</h3><p>The future is terrifying, profitable, and unbelievably awkward.</p></article>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
