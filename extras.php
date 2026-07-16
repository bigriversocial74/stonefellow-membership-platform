<?php
$pageTitle = 'Extras';
$pageDescription = 'Behind-the-scenes content, interviews, soundtrack material, and production extras from Likenessing.';
$pageClass = 'likenessing-extras';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="<?= sf_asset('css/likenessing-media-v4.css?v=20260717') ?>">
<section class="lk-shell lk-page-title"><p class="lk-label">Extras</p><h1>The content behind<br>the content machine.</h1><p>Interviews, production notes, soundtrack selections, deleted negotiations, and everything legal hoped would stay off camera.</p></section>
<section class="lk-shell lk-extra-grid">
  <article><img src="<?= lk_asset_url('newsletter') ?>" alt="Behind-the-scenes production photographs" loading="lazy" decoding="async"><div><h2>Behind the Scenes</h2><p>Production photography, cast conversations, and the real work behind the synthetic performances.</p><a href="<?= sf_url('news.php') ?>">Explore Stories →</a></div></article>
  <article><img src="<?= lk_asset_url('premise') ?>" alt="Artificial intelligence and film production" loading="lazy" decoding="async"><div><h2>The Technology</h2><p>A closer look at the tools, contracts, and ethical disasters driving the series.</p><a href="<?= sf_url('series.php') ?>">Read the Premise →</a></div></article>
  <article><img src="<?= lk_asset_url('episode_commercial_break') ?>" alt="A tense production meeting" loading="lazy" decoding="async"><div><h2>Sound &amp; Screen</h2><p>Stream the soundtrack and revisit the campaign moments nobody agreed to.</p><a href="<?= sf_url('music.php') ?>">Open Soundtrack →</a></div></article>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
