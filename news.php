<?php
$pageTitle = 'News';
$pageDescription = 'Production news, episode notes, and announcements from Likenessing.';
$pageClass = 'likenessing-news';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/header.php';
$stories = [
 ['date'=>'July 16, 2026','title'=>'Likenessing enters production','text'=>'The original comedy series begins production with a sharp look at AI, fame, and ownership.','image'=>'images/likenessing/hero-ensemble-v2.webp'],
 ['date'=>'July 16, 2026','title'=>'Meet the characters behind the contracts','text'=>'Actors, agents, managers, lawyers, and executives collide in a business where identity is the product.','image'=>'images/likenessing/newsletter-collage-v2.webp'],
 ['date'=>'July 16, 2026','title'=>'First episode titles revealed','text'=>'Pilot, Fine Print, Double Exposure, Commercial Break, and Who’s Driving? introduce the first wave of chaos.','image'=>'images/likenessing/premise-ai-studio-v2.webp'],
];
?>
<section class="lk-page-title lk-shell"><p class="lk-label">News</p><h1>From the set.<br>Before the spin.</h1><p>Production updates and official announcements from Likenessing.</p></section>
<section class="lk-news-grid lk-shell"><?php foreach($stories as $story): ?><article><img src="<?= sf_asset($story['image']) ?>" alt="<?= htmlspecialchars($story['title'], ENT_QUOTES, 'UTF-8') ?>"><div><time><?= htmlspecialchars($story['date'], ENT_QUOTES, 'UTF-8') ?></time><h2><?= htmlspecialchars($story['title'], ENT_QUOTES, 'UTF-8') ?></h2><p><?= htmlspecialchars($story['text'], ENT_QUOTES, 'UTF-8') ?></p><a href="<?= sf_url('signup.php') ?>">Read Update <span>›</span></a></div></article><?php endforeach; ?></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
