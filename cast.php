<?php
$pageTitle = 'Cast';
$pageDescription = 'Meet the actors, agents, managers, lawyers, executives, and publicists of Likenessing.';
$pageClass = 'cast-template likenessing-cast';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="<?= sf_asset('css/likenessing-media-v4.css?v=20260717') ?>">
<?php
$castCards = [
  ['name'=>'The Movie Star','role'=>'Actor / Licensed Likeness','quote'=>'Always has a deal on the table.','asset'=>'cast_movie_star'],
  ['name'=>'The Agent','role'=>'Representation / Negotiation','quote'=>'Ruthless, persuasive, and paid on percentage.','asset'=>'cast_agent'],
  ['name'=>'The Manager','role'=>'Career Strategy','quote'=>'Sees opportunity everywhere—and numbers in everyone.','asset'=>'cast_manager'],
  ['name'=>'The Lawyer','role'=>'Rights / Contracts','quote'=>'Protects the talent, the future, and the billable hour.','asset'=>'cast_lawyer'],
  ['name'=>'The Newcomer','role'=>'Actor / First Contract','quote'=>'Big dreams. No idea what he is really signing.','asset'=>'cast_newcomer'],
  ['name'=>'The Studio Exec','role'=>'Content / Ownership','quote'=>'Owns the game, makes the rules, changes them daily.','asset'=>'cast_studio_exec'],
  ['name'=>'The Publicist','role'=>'Narrative / Damage Control','quote'=>'Controls the story until the story controls her.','asset'=>'cast_publicist'],
];
?>
<section class="lk-shell lk-page-title"><p class="lk-label">The Cast</p><h1>A cast of characters.<br>A world of egos.</h1><p>Everyone has a client, a percentage, a secret, or a synthetic backup plan.</p></section>
<section class="lk-shell lk-profile-grid">
  <?php foreach ($castCards as $card): ?>
    <article class="lk-profile-card"><img src="<?= lk_asset_url($card['asset']) ?>" alt="<?= htmlspecialchars($card['name'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async"><div><p class="lk-label"><?= htmlspecialchars($card['role'], ENT_QUOTES, 'UTF-8') ?></p><h2><?= htmlspecialchars($card['name'], ENT_QUOTES, 'UTF-8') ?></h2><blockquote>“<?= htmlspecialchars($card['quote'], ENT_QUOTES, 'UTF-8') ?>”</blockquote><a href="<?= sf_url('series-characters.php') ?>">View Profile →</a></div></article>
  <?php endforeach; ?>
</section>
<section class="lk-cast-cta"><div class="lk-shell"><div><p class="lk-label">Their faces are the product</p><h2>Meet them before the contract changes them.</h2><p>Explore character histories, relationships, and the decisions behind every licensed likeness.</p><a class="lk-button lk-button-gold" href="<?= sf_url('episodes.php') ?>">Start Watching</a></div><img src="<?= lk_asset_url('newsletter') ?>" alt="Likenessing cast members behind the scenes" loading="lazy" decoding="async"></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
