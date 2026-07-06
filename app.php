<?php
$pageTitle = 'App';
$pageDescription = 'Take Stonefellow with you on the mobile app.';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div>
    <span class="eyebrow">Mobile App</span>
    <h1>Take the band on the road.</h1>
    <p class="lead">A companion app for watching episodes, streaming songs, saving favorites, and catching subscriber-only drops.</p>
    <div class="hero-actions"><a class="btn btn-primary" href="#">Download App</a><a class="btn btn-ghost" href="subscribe.php">Subscribe First</a></div>
  </div>
  <div class="hero-art"><img src="<?= sf_asset('images/app/app-hero.png') ?>" alt="Stonefellow mobile app interface"></div>
</section>
<section class="section grid-3">
  <div class="panel"><span class="eyebrow">Watch</span><h3>Episodes anywhere</h3><p>Continue watching and save your place across devices.</p></div>
  <div class="panel"><span class="eyebrow">Stream</span><h3>Soundtrack access</h3><p>Play episode tracks, demos, and live sessions.</p></div>
  <div class="panel"><span class="eyebrow">Collect</span><h3>Merch alerts</h3><p>Get early access to drops and founding fan bundles.</p></div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
