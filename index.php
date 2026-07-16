<?php
$pageTitle = 'Home';
$pageDescription = 'Likenessing is an original dark comedy about actors, agents, studios, artificial intelligence, and the contracts that can turn a face into a business model.';
$pageClass = 'home-template likenessing-home';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/header.php';

$cast = [
  ['title' => 'The Movie Star', 'name' => 'Charming. Bankable.', 'description' => 'Always has a deal on the table.', 'position' => '0%'],
  ['title' => 'The Agent', 'name' => 'Ruthless negotiator.', 'description' => 'Will sell ice to an Eskimo.', 'position' => '16.666%'],
  ['title' => 'The Manager', 'name' => 'Numbers guy.', 'description' => 'Sees opportunity everywhere.', 'position' => '33.333%'],
  ['title' => 'The Lawyer', 'name' => 'Protects the talent.', 'description' => 'And their future. For a fee.', 'position' => '50%'],
  ['title' => 'The Newcomer', 'name' => 'Big dreams.', 'description' => 'No idea what he is really signing.', 'position' => '66.666%'],
  ['title' => 'The Studio Exec', 'name' => 'Owns the game.', 'description' => 'Makes the rules. Changes them daily.', 'position' => '83.333%'],
  ['title' => 'The Publicist', 'name' => 'Controls the narrative.', 'description' => 'And the damage control.', 'position' => '100%'],
];

$episodes = [
  ['number' => 'EP 1', 'title' => 'Pilot', 'description' => 'A struggling actor gets an offer that could change everything.', 'position' => '0%'],
  ['number' => 'EP 2', 'title' => 'Fine Print', 'description' => 'One clause can make you rich. Another can own your soul.', 'position' => '25%'],
  ['number' => 'EP 3', 'title' => 'Double Exposure', 'description' => 'Two versions of you. One problem.', 'position' => '50%'],
  ['number' => 'EP 4', 'title' => 'Commercial Break', 'description' => 'Your likeness sells soap. And your dignity.', 'position' => '75%'],
  ['number' => 'EP 5', 'title' => "Who's Driving?", 'description' => 'When your AI books the role you wanted.', 'position' => '100%'],
];
?>
<section class="lk-hero" aria-labelledby="lk-home-title">
  <img class="lk-hero-image" src="<?= lk_asset_url('hero') ?>" alt="Actors and representatives meeting in a Hollywood office overlooking Los Angeles">
  <div class="lk-hero-shade" aria-hidden="true"></div>
  <div class="lk-hero-content">
    <p class="lk-eyebrow">Fame. Fortune.<br>Full control.</p>
    <h1 id="lk-home-title"><span>Likeness</span><strong>ing</strong></h1>
    <h2>Your face. Your voice. Their contract.</h2>
    <p>In a world where AI can be you, a group of actors and agents navigate the wild, ridiculous, and sometimes shady business of licensing their likenesses—and trying not to lose themselves in the fine print.</p>
    <div class="lk-actions">
      <a class="lk-button lk-button-gold" href="<?= sf_url('episodes.php') ?>"><span class="lk-icon lk-icon-play" aria-hidden="true"></span>Watch Trailer</a>
      <a class="lk-button lk-button-dark" href="<?= sf_url('watchlist.php') ?>"><span class="lk-icon lk-icon-plus" aria-hidden="true"></span>My List</a>
    </div>
  </div>
</section>

<section class="lk-shell lk-premise" aria-labelledby="lk-premise-title">
  <div class="lk-premise-image"><img src="<?= lk_asset_url('premise') ?>" alt="A performer standing on a studio lot beneath a digital artificial intelligence likeness"></div>
  <div class="lk-premise-copy">
    <p class="lk-label">The Premise</p>
    <h2 id="lk-premise-title">Every opportunity comes with a contract.</h2>
    <p>Studios, brands, and technology companies want more than your talent. They want you. Forever. From blockbuster roles to voice clones, from ad campaigns to digital influencers—AI can work 24/7. The question is: what are you willing to license?</p>
    <div class="lk-benefits">
      <article><span class="lk-icon lk-icon-account" aria-hidden="true"></span><strong>More Roles</strong><span>Work anywhere.</span></article>
      <article><span class="lk-icon lk-icon-star" aria-hidden="true"></span><strong>Passive Income</strong><span>Get paid while you sleep.</span></article>
      <article><span class="lk-icon lk-icon-lock" aria-hidden="true"></span><strong>Keep Control</strong><span>Own your rights.</span></article>
      <article><span class="lk-icon lk-icon-info" aria-hidden="true"></span><strong>Play It Risky</strong><span>Every deal has a price.</span></article>
    </div>
  </div>
</section>

<section class="lk-shell lk-section" aria-labelledby="lk-cast-title">
  <div class="lk-section-head"><h2 id="lk-cast-title">The Cast</h2><p>A cast of characters. A world of egos.</p></div>
  <div class="lk-cast-grid">
    <?php foreach ($cast as $person): ?>
      <a class="lk-cast-card" href="<?= sf_url('cast.php') ?>">
        <div class="lk-cast-photo" style="background-image:url('<?= lk_asset_url('cast') ?>');background-position:<?= $person['position'] ?> center" role="img" aria-label="<?= htmlspecialchars($person['title'], ENT_QUOTES, 'UTF-8') ?>"></div>
        <div><strong><?= htmlspecialchars($person['title'], ENT_QUOTES, 'UTF-8') ?></strong><p><?= htmlspecialchars($person['name'], ENT_QUOTES, 'UTF-8') ?><br><?= htmlspecialchars($person['description'], ENT_QUOTES, 'UTF-8') ?></p></div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="lk-shell lk-section" aria-labelledby="lk-episodes-title">
  <div class="lk-section-head"><h2 id="lk-episodes-title">Latest Episodes</h2><a href="<?= sf_url('episodes.php') ?>">View All Episodes <span aria-hidden="true">›</span></a></div>
  <div class="lk-episode-grid">
    <?php foreach ($episodes as $episode): ?>
      <a class="lk-episode-card" href="<?= sf_url('episodes.php') ?>">
        <div class="lk-episode-art"><div class="lk-episode-photo" style="background-image:url('<?= lk_asset_url('episodes') ?>');background-position:<?= $episode['position'] ?> center" role="img" aria-label="<?= htmlspecialchars($episode['title'], ENT_QUOTES, 'UTF-8') ?> episode still"></div><span><?= htmlspecialchars($episode['number'], ENT_QUOTES, 'UTF-8') ?></span></div>
        <div class="lk-episode-body"><strong><?= htmlspecialchars($episode['title'], ENT_QUOTES, 'UTF-8') ?></strong><p><?= htmlspecialchars($episode['description'], ENT_QUOTES, 'UTF-8') ?></p><span class="lk-card-play lk-icon lk-icon-play" aria-label="Play"></span></div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="lk-newsletter" aria-labelledby="lk-newsletter-title">
  <div class="lk-shell lk-newsletter-inner">
    <div><h2 id="lk-newsletter-title">Stay in the Loop</h2><p>Get exclusive content, behind-the-scenes gossip, and early access to new episodes.</p></div>
    <form action="<?= sf_url('signup.php') ?>" method="get"><label class="sr-only" for="lk-news-email">Email address</label><input id="lk-news-email" type="email" name="email" placeholder="Enter your email" required><button type="submit">Subscribe</button></form>
    <img src="<?= lk_asset_url('newsletter') ?>" alt="Behind-the-scenes Likenessing production photographs">
    <p class="lk-signoff">See you<br>on set.</p>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
