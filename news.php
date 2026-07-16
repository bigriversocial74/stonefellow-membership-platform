<?php
$pageTitle = 'News';
$pageDescription = 'Likenessing production news, episode updates, interviews, and press.';
$pageClass = 'likenessing-news';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/header.php';
$stories = [
  ['category'=>'Production','title'=>'The contract is signed. Production begins.','summary'=>'The cast steps onto the studio floor while legal discovers three new definitions of forever.','asset'=>'newsletter'],
  ['category'=>'Episode Guide','title'=>'Inside “Fine Print”','summary'=>'A clause nobody read becomes the most valuable character in the room.','asset'=>'episodes'],
  ['category'=>'Technology','title'=>'When the backup actor is also you','summary'=>'The series explores what happens when performance, identity, and ownership become separate products.','asset'=>'premise'],
  ['category'=>'Cast','title'=>'Meet the people selling the future','summary'=>'Actors, agents, lawyers, managers, executives, and one newcomer with a pen.','asset'=>'hero'],
];
?>
<section class="lk-shell lk-page-title"><p class="lk-label">News &amp; Press</p><h1>Every headline has<br>a rights holder.</h1><p>Production announcements, episode notes, cast interviews, and updates from the world of Likenessing.</p></section>
<section class="lk-shell lk-news-grid">
  <?php foreach ($stories as $story): ?><article><img src="<?= lk_asset_url($story['asset']) ?>" alt="<?= htmlspecialchars($story['title'], ENT_QUOTES, 'UTF-8') ?>"><div><p class="lk-label"><?= htmlspecialchars($story['category'], ENT_QUOTES, 'UTF-8') ?></p><h2><?= htmlspecialchars($story['title'], ENT_QUOTES, 'UTF-8') ?></h2><p><?= htmlspecialchars($story['summary'], ENT_QUOTES, 'UTF-8') ?></p><a href="<?= sf_url('signup.php') ?>">Read More →</a></div></article><?php endforeach; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
