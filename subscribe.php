<?php
$pageTitle = 'Subscribe';
$pageDescription = 'Subscribe to watch Stonefellow, stream the music, and unlock fan access.';
$pageClass = 'subscribe-template';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/billing.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
    sf_auth_flash('error', 'Security check failed. Refresh and try again.');
  } elseif (!sf_auth_user()) {
    sf_auth_flash('warning', 'Create an account or sign in before choosing a membership plan.');
    sf_redirect(sf_url('signup.php?next=' . urlencode(sf_url('subscribe.php'))));
  } else {
    $planId = (int)($_POST['plan_id'] ?? 0);
    $token = $planId > 0 ? sf_billing_start_checkout($planId) : null;
    if ($token) {
      sf_redirect(sf_url('billing-checkout.php?token=' . urlencode($token)));
    }
  }
}

$dbPlans = sf_db_plan_options();
$subscribePlans = $dbPlans ? array_map(static function(array $plan): array {
  $price = (int)($plan['price_cents'] ?? 0);
  return [
    'id' => (int)$plan['id'],
    'name' => $plan['name'],
    'price' => (string)floor($price / 100),
    'cents' => str_pad((string)($price % 100), 2, '0', STR_PAD_LEFT),
    'period' => '/ ' . ($plan['billing_interval'] ?? 'month'),
    'note' => ($plan['billing_interval'] ?? 'month') === 'year' ? 'Billed annually' : '',
    'badge' => !empty($plan['is_featured']) ? 'Most Popular' : '',
    'cta' => 'Continue to Checkout',
    'features' => array_filter([
      !empty($plan['allows_full_music']) ? 'Full soundtrack streaming' : null,
      !empty($plan['allows_video_streaming']) ? 'Episode video access' : null,
      !empty($plan['allows_playlists']) ? 'Private member playlists' : null,
      !empty($plan['allows_offline_downloads']) ? 'Offline/download access' : null,
      $plan['description'] ?? null,
    ]),
  ];
}, $dbPlans) : [
  [
    'id' => 0,
    'name' => 'Monthly Access',
    'price' => '7',
    'cents' => '99',
    'period' => '/ month',
    'note' => '',
    'badge' => '',
    'cta' => 'Start Watching',
    'features' => ['Watch all episodes', 'Stream the soundtrack', 'Behind-the-scenes content', 'Ad-free experience'],
  ],
  [
    'id' => 0,
    'name' => 'Annual Access',
    'price' => '79',
    'cents' => '99',
    'period' => '/ year',
    'note' => 'Billed annually',
    'badge' => 'Most Popular',
    'cta' => 'Start Watching',
    'features' => ['Everything in Monthly', 'Early episode access', 'Exclusive live sessions', 'Download to watch offline'],
  ],
  [
    'id' => 0,
    'name' => 'Founding Fan',
    'price' => '149',
    'cents' => '99',
    'period' => '/ year',
    'note' => 'Billed annually',
    'badge' => '',
    'cta' => 'Become a Founder',
    'features' => ['Everything in Annual', 'VIP behind-the-scenes', 'Limited merch drops', 'Your name in the credits'],
  ],
];
$benefits = [
  ['icon' => '▻', 'title' => 'Watch Anywhere', 'text' => 'Stream on all your devices.'],
  ['icon' => '≋', 'title' => 'Stream the Music', 'text' => 'Listen on the official soundtrack.'],
  ['icon' => '⇩', 'title' => 'Download & Go', 'text' => 'Watch offline, on your terms.'],
  ['icon' => '☷', 'title' => 'Exclusive Access', 'text' => 'Content you won’t find anywhere else.'],
  ['icon' => '⊗', 'title' => 'Ad-Free Experience', 'text' => 'All story. No interruptions.'],
];
require __DIR__ . '/includes/header.php';
?>
<section class="subscribe-page">
  <section class="subscribe-hero">
    <div class="subscribe-hero-copy">
      <h1>Watch the Show.<br>Stream the Music.</h1>
      <div class="subscribe-rule"></div>
      <h2>One band. One story. One sound.</h2>
      <p>Watch every episode of Stonefellow and stream the official soundtrack.<br>Live the story. Hear the sound.</p>
    </div>
    <div class="subscribe-hero-art">
      <img src="<?= sf_asset('images/subscribe/subscribe-hero-band.png') ?>" alt="Stonefellow band under stage lights">
    </div>
  </section>

  <section class="billing-toggle-wrap">
    <div class="billing-toggle" role="group" aria-label="Billing period">
      <button class="is-active" type="button">Monthly</button>
      <button type="button">Annual <span>Save 20%</span></button>
    </div>
    <p><?= $dbPlans ? 'Secure membership checkout is enabled in sandbox mode. Connect your processor when ready for production.' : 'Run the SQL and configure DB credentials to activate live plans.' ?></p>
  </section>

  <section class="pricing-grid-template">
    <?php foreach ($subscribePlans as $plan): ?>
      <article class="subscribe-plan-card <?= $plan['badge'] ? 'is-featured' : '' ?>">
        <?php if ($plan['badge']): ?><div class="plan-ribbon"><?= htmlspecialchars($plan['badge']) ?></div><?php endif; ?>
        <h3><?= htmlspecialchars($plan['name']) ?></h3>
        <div class="plan-price"><span class="dollar">$</span><?= htmlspecialchars($plan['price']) ?><span class="cents"><?= htmlspecialchars($plan['cents']) ?></span><em><?= htmlspecialchars($plan['period']) ?></em></div>
        <?php if ($plan['note']): ?><p class="plan-note"><?= htmlspecialchars($plan['note']) ?></p><?php endif; ?>
        <ul>
          <?php foreach ($plan['features'] as $feature): ?><li><?= htmlspecialchars($feature) ?></li><?php endforeach; ?>
        </ul>
        <?php if (!empty($plan['id'])): ?>
          <form method="post" action="<?= sf_url('subscribe.php') ?>" class="sf-plan-form">
            <?= sf_csrf_field() ?>
            <input type="hidden" name="plan_id" value="<?= (int)$plan['id'] ?>">
            <button type="submit" class="plan-cta <?= $plan['badge'] ? 'solid' : '' ?>"><?= htmlspecialchars($plan['cta']) ?></button>
          </form>
        <?php else: ?>
          <a href="<?= sf_url('signup.php') ?>" class="plan-cta <?= $plan['badge'] ? 'solid' : '' ?>"><?= htmlspecialchars($plan['cta']) ?></a>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </section>

  <section class="part-of-band-banner">
    <div class="banner-mark">SF</div>
    <h2>You’re not just watching.<br>You’re part of the band.</h2>
  </section>

  <section class="benefit-row-template">
    <?php foreach ($benefits as $benefit): ?>
      <article>
        <div class="benefit-icon"><?= htmlspecialchars($benefit['icon']) ?></div>
        <h3><?= htmlspecialchars($benefit['title']) ?></h3>
        <p><?= htmlspecialchars($benefit['text']) ?></p>
      </article>
    <?php endforeach; ?>
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
