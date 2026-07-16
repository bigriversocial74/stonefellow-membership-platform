<?php
$pageTitle = 'Home';
$pageDescription = 'Likenessing is an original dark comedy about actors, agents, studios, and the contracts that let artificial intelligence become anyone.';
$pageClass = 'home-template likenessing-home';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/header.php';

$castCards = [
  ['name' => 'The Movie Star', 'role' => 'Charmingly bankable. Always has a deal on the table.', 'image' => 'images/likenessing/cast-movie-star-v2.webp'],
  ['name' => 'The Agent', 'role' => 'Ruthless negotiator. Will sell ice to an Eskimo.', 'image' => 'images/likenessing/cast-agent-v2.webp'],
  ['name' => 'The Manager', 'role' => 'Numbers guy. Sees opportunity everywhere.', 'image' => 'images/likenessing/cast-manager-v2.webp'],
  ['name' => 'The Lawyer', 'role' => 'Protects the talent. And their future. For a fee.', 'image' => 'images/likenessing/cast-lawyer-v2.webp'],
  ['name' => 'The Newcomer', 'role' => 'Big dreams. No idea what he is really signing.', 'image' => 'images/likenessing/cast-newcomer-v2.webp'],
  ['name' => 'The Studio Exec', 'role' => 'Owns the game. Makes the rules. Changes them daily.', 'image' => 'images/likenessing/cast-studio-exec-v2.webp'],
  ['name' => 'The Publicist', 'role' => 'Controls the narrative. And the damage control.', 'image' => 'images/likenessing/cast-publicist-v2.webp'],
];

$episodeCards = [
  ['number' => 'EP 1', 'title' => 'Pilot', 'description' => 'A struggling actor gets an offer that could change everything.', 'image' => 'images/likenessing/episode-pilot-v2.webp'],
  ['number' => 'EP 2', 'title' => 'Fine Print', 'description' => 'One clause can make you rich. Another can own your soul.', 'image' => 'images/likenessing/episode-fine-print-v2.webp'],
  ['number' => 'EP 3', 'title' => 'Double Exposure', 'description' => 'Two versions of you. One problem.', 'image' => 'images/likenessing/episode-double-exposure-v2.webp'],
  ['number' => 'EP 4', 'title' => 'Commercial Break', 'description' => 'Your likeness sells soap. And your dignity.', 'image' => 'images/likenessing/episode-commercial-break-v2.webp'],
  ['number' => 'EP 5', 'title' => "Who's Driving?", 'description' => 'When your AI books the role you wanted.', 'image' => 'images/likenessing/episode-whos-driving-v2.webp'],
];

$listUrl = $sfHeaderUser ? sf_url('watchlist.php') : sf_url('signup.php');
?>
<section class="lk-hero" aria-labelledby="lk-hero-title">
  <img class="lk-hero-image" src="<?= sf_asset('images/likenessing/hero-ensemble-v2.webp') ?>" alt="Actors, agents, and executives meeting in a Hollywood office" fetchpriority="high" decoding="async">
  <div class="lk-hero-shade" aria-hidden="true"></div>
  <div class="lk-hero-content">
    <p class="lk-eyebrow">Fame. Fortune.<br>Full control.</p>
    <h1 id="lk-hero-title"><span>Likeness</span><strong>ing</strong></h1>
    <h2>Your face. Your voice. Their contract.</h2>
    <p>In a world where AI can be you, a group of actors and agents navigate the wild, ridiculous, and sometimes shady business of licensing their likenesses—and trying not to lose themselves in the fine print.</p>
    <div class="lk-actions">
      <a class="lk-button lk-button-gold" href="<?= sf_url('episodes.php') ?>"><span class="lk-icon lk-icon-play" aria-hidden="true"></span>Watch Trailer</a>
      <a class="lk-button lk-button-dark" href="<?= $listUrl ?>"><span class="lk-icon lk-icon-plus" aria-hidden="true"></span>My List</a>
    </div>
  </div>
</section>

<section class="lk-premise lk-shell" aria-labelledby="lk-premise-title">
  <div class="lk-premise-image"><img src="<?= sf_asset('images/likenessing/premise-ai-studio-v2.webp') ?>" alt="A digital face hovering above a production studio"></div>
  <div class="lk-premise-copy">
    <p class="lk-label">The Premise</p>
    <h2 id="lk-premise-title">Every opportunity comes with a contract.</h2>
    <p>Studios, brands, and tech companies want more than your talent. They want you. Forever. From blockbuster roles to voice clones, from ad campaigns to digital influencers—AI can work 24/7. The question is: what are you willing to license?</p>
    <div class="lk-benefits">
      <article><span class="lk-icon lk-icon-account" aria-hidden="true"></span><strong>More Roles</strong><span>Work anywhere.</span></article>
      <article><span class="lk-icon lk-icon-clock" aria-hidden="true"></span><strong>Passive Income</strong><span>Get paid while you sleep.</span></article>
      <article><span class="lk-icon lk-icon-lock" aria-hidden="true"></span><strong>Keep Control</strong><span>Own your rights.</span></article>
      <article><span class="lk-icon lk-icon-star" aria-hidden="true"></span><strong>Play It Risky</strong><span>Every deal has a price.</span></article>
    </div>
  </div>
</section>

<section class="lk-section lk-shell" aria-labelledby="lk-cast-title">
  <div class="lk-section-head"><h2 id="lk-cast-title">The Cast</h2><p>A cast of characters. A world of egos.</p></div>
  <div class="lk-cast-grid">
    <?php foreach ($castCards as $card): ?>
      <a class="lk-cast-card" href="<?= sf_url('cast.php') ?>">
        <img src="<?= sf_asset($card['image']) ?>" alt="<?= htmlspecialchars($card['name'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async">
        <div><strong><?= htmlspecialchars($card['name'], ENT_QUOTES, 'UTF-8') ?></strong><p><?= htmlspecialchars($card['role'], ENT_QUOTES, 'UTF-8') ?></p></div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="lk-section lk-shell" aria-labelledby="lk-episodes-title">
  <div class="lk-section-head"><h2 id="lk-episodes-title">Latest Episodes</h2><a href="<?= sf_url('episodes.php') ?>">View All Episodes <span>›</span></a></div>
  <div class="lk-episode-grid">
    <?php foreach ($episodeCards as $episode): ?>
      <a class="lk-episode-card" href="<?= sf_url('episodes.php') ?>">
        <div class="lk-episode-art"><img src="<?= sf_asset($episode['image']) ?>" alt="<?= htmlspecialchars($episode['title'], ENT_QUOTES, 'UTF-8') ?> episode still" loading="lazy" decoding="async"><span><?= htmlspecialchars($episode['number'], ENT_QUOTES, 'UTF-8') ?></span></div>
        <div class="lk-episode-body"><strong><?= htmlspecialchars($episode['title'], ENT_QUOTES, 'UTF-8') ?></strong><p><?= htmlspecialchars($episode['description'], ENT_QUOTES, 'UTF-8') ?></p><span class="lk-card-play lk-icon lk-icon-play" aria-label="Play episode"></span></div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="lk-newsletter" aria-labelledby="lk-newsletter-title">
  <div class="lk-shell lk-newsletter-inner">
    <div><h2 id="lk-newsletter-title">Stay in the loop</h2><p>Get exclusive content, behind-the-scenes gossip, and early access to new episodes.</p></div>
    <form action="<?= sf_url('signup.php') ?>" method="get"><label class="sr-only" for="lk-email">Email address</label><input id="lk-email" type="email" name="email" placeholder="Enter your email" required><button type="submit">Subscribe</button></form>
    <img src="<?= sf_asset('images/likenessing/newsletter-collage-v2.webp') ?>" alt="Hollywood cast moments and Los Angeles skyline" loading="lazy" decoding="async">
    <p class="lk-signoff">See you<br>on set.</p>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
