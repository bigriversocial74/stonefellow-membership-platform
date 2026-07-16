<?php
$pageTitle = 'Extras';
$pageDescription = 'Behind-the-scenes extras, contract files, and bonus content from Likenessing.';
$pageClass = 'likenessing-extras';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/header.php';
$extras = [
 ['title'=>'Inside the Contract Room','text'=>'See how the show turns legal fine print into character comedy.','image'=>'images/likenessing/premise-ai-studio-v2.webp'],
 ['title'=>'The Faces Behind the Faces','text'=>'A closer look at the cast, their doubles, and the technology changing the business.','image'=>'images/likenessing/hero-ensemble-v2.webp'],
 ['title'=>'Hollywood After Hours','text'=>'Photos, table reads, and production moments from the world of Likenessing.','image'=>'images/likenessing/newsletter-collage-v2.webp'],
];
?>
<section class="lk-page-title lk-shell"><p class="lk-label">Extras</p><h1>More access.<br>More secrets.</h1><p>Bonus material from the show where every face has a price.</p></section>
<section class="lk-extra-grid lk-shell"><?php foreach($extras as $extra): ?><article><img src="<?= sf_asset($extra['image']) ?>" alt="<?= htmlspecialchars($extra['title'], ENT_QUOTES, 'UTF-8') ?>"><div><h2><?= htmlspecialchars($extra['title'], ENT_QUOTES, 'UTF-8') ?></h2><p><?= htmlspecialchars($extra['text'], ENT_QUOTES, 'UTF-8') ?></p><a href="<?= sf_url('signup.php') ?>">Unlock Extra <span>›</span></a></div></article><?php endforeach; ?></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
