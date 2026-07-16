<?php
$pageTitle = 'Cast';
$pageDescription = 'Meet the actors, agents, executives, and professional chaos behind Likenessing.';
$pageClass = 'cast-template likenessing-cast-page';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/header.php';
$castCards = [
  ['name'=>'The Movie Star','role'=>'The Talent','quote'=>'Every deal looks good before someone reads it.','slot'=>0],
  ['name'=>'The Agent','role'=>'The Negotiator','quote'=>'I do not sell people. I monetize potential.','slot'=>1],
  ['name'=>'The Manager','role'=>'The Strategist','quote'=>'A crisis is just a revenue stream without a spreadsheet.','slot'=>2],
  ['name'=>'The Lawyer','role'=>'The Protector','quote'=>'You can keep your soul. I need the rights in perpetuity.','slot'=>3],
  ['name'=>'The Newcomer','role'=>'The Dreamer','quote'=>'They said this was a standard form. It has ninety pages.','slot'=>4],
  ['name'=>'The Studio Exec','role'=>'The Buyer','quote'=>'We are not replacing actors. We are scaling them.','slot'=>5],
  ['name'=>'The Publicist','role'=>'The Fixer','quote'=>'Nothing is a scandal until it starts trending.','slot'=>6],
];
?>
<section class="lk-page-title lk-shell"><p class="lk-label">The Cast</p><h1>A cast of characters.<br>A world of egos.</h1><p>Meet the people trying to stay human while the entertainment business turns identity into inventory.</p></section>
<section class="lk-profile-grid lk-shell">
<?php foreach ($castCards as $card): ?>
  <article class="lk-profile-card"><span class="lk-profile-photo lk-profile-photo-<?= (int)$card['slot'] ?>" role="img" aria-label="<?= htmlspecialchars($card['name'], ENT_QUOTES, 'UTF-8') ?>"></span><div><p class="lk-label"><?= htmlspecialchars($card['role'], ENT_QUOTES, 'UTF-8') ?></p><h2><?= htmlspecialchars($card['name'], ENT_QUOTES, 'UTF-8') ?></h2><blockquote>“<?= htmlspecialchars($card['quote'], ENT_QUOTES, 'UTF-8') ?>”</blockquote><a href="<?= sf_url('episodes.php') ?>">Key Episodes <span>›</span></a></div></article>
<?php endforeach; ?>
</section>
<section class="lk-cast-cta"><div class="lk-shell"><div><p class="lk-label">Now Casting</p><h2>Everybody wants a piece of the future.</h2><p>Watch the contracts, betrayals, and identity crises unfold.</p><a class="lk-button lk-button-gold" href="<?= sf_url('episodes.php') ?>"><span class="lk-icon lk-icon-play" aria-hidden="true"></span>Start Watching</a></div><img src="<?= sf_asset('images/likenessing/newsletter-collage-v2.webp') ?>" alt="The cast celebrating in Los Angeles"></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
