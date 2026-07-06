<?php
$pageTitle = 'Subscribe';
$pageDescription = 'Subscribe to watch Stonefellow, stream the music, unlock fan access, and choose the right membership tier.';
$pageClass = 'subscribe-template';
require __DIR__ . '/includes/membership_tiers.php';
require_once __DIR__ . '/includes/revenue_dashboard.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
    sf_auth_flash('error', 'Security check failed. Refresh and try again.');
  } elseif (!sf_auth_user()) {
    sf_auth_flash('warning', 'Create an account or sign in before choosing a membership plan.');
    sf_redirect(sf_url('signup.php?next=' . urlencode(sf_url('subscribe.php'))));
  } else {
    $planId = (int)($_POST['plan_id'] ?? 0);
    $plan = $planId > 0 ? sf_billing_plan($planId) : null;
    sf_rev_record_conversion('start_checkout', $planId, 0, (int)($plan['price_cents'] ?? 0), 'subscribe_page');
    $token = $planId > 0 ? sf_billing_start_checkout($planId) : null;
    if ($token) sf_redirect(sf_url('billing-checkout.php?token=' . urlencode($token)));
  }
}

sf_rev_record_conversion('view_pricing', 0, 0, 0, 'subscribe_page');
$subscribePlans = sf_tiers_public_plans();
$matrix = sf_tiers_benefit_matrix();
$benefits = [
  ['icon' => '▻', 'title' => 'Watch Anywhere', 'text' => 'Stream released Stonefellow episodes across your devices.'],
  ['icon' => '≋', 'title' => 'Stream the Music', 'text' => 'Listen to the official soundtrack through the member player.'],
  ['icon' => '☷', 'title' => 'Join the Feed', 'text' => 'Follow creator updates, comment, react, and personalize your feed.'],
  ['icon' => '⇧', 'title' => 'Upgrade Path', 'text' => 'Move from monthly to premium or Founding Fan when ready.'],
  ['icon' => '⊗', 'title' => 'Launch Access', 'text' => 'Support the Stonefellow launch and unlock gated benefits.'],
];
require __DIR__ . '/includes/header.php';
?>
<section class="subscribe-page">
  <section class="subscribe-hero">
    <div class="subscribe-hero-copy"><h1>Choose Your<br>Stonefellow Access.</h1><div class="subscribe-rule"></div><h2>Watch the show. Stream the music. Join the fan layer.</h2><p>Pick the tier that matches how deep you want to go into the Stonefellow story, soundtrack, feed, and member community.</p></div>
    <div class="subscribe-hero-art"><img src="<?= sf_asset('images/subscribe/subscribe-hero-band.png') ?>" alt="Stonefellow band under stage lights"></div>
  </section>
  <section class="billing-toggle-wrap"><div class="billing-toggle" role="group" aria-label="Billing period"><button class="is-active" type="button">Launch Tiers</button><button type="button">Upgrade Anytime <span>Member path</span></button></div><p><?= sf_tiers_table_exists('subscription_plans') ? 'Live membership packages are loaded from the tier manager.' : 'Run SQL and migration 015 to activate editable tier packaging.' ?></p></section>
  <section class="pricing-grid-template">
    <?php foreach ($subscribePlans as $plan): $price=(int)($plan['price_cents'] ?? 0); $badge=(string)($plan['public_badge'] ?? (!empty($plan['is_featured'])?'Featured':'')); ?>
      <article class="subscribe-plan-card <?= $badge ? 'is-featured' : '' ?>">
        <?php if ($badge): ?><div class="plan-ribbon"><?= htmlspecialchars($badge) ?></div><?php endif; ?>
        <h3><?= htmlspecialchars($plan['name'] ?? 'Stonefellow Access') ?></h3>
        <p class="plan-note"><?= htmlspecialchars($plan['access_label'] ?? $plan['description'] ?? '') ?></p>
        <div class="plan-price"><span class="dollar">$</span><?= (int)floor($price / 100) ?><span class="cents"><?= str_pad((string)($price % 100), 2, '0', STR_PAD_LEFT) ?></span><em>/ <?= htmlspecialchars($plan['billing_interval'] ?? 'month') ?></em></div>
        <?php if (!empty($plan['description'])): ?><p class="plan-note"><?= htmlspecialchars($plan['description']) ?></p><?php endif; ?>
        <ul><?php foreach (sf_tiers_plan_features($plan) as $feature): ?><li><?= htmlspecialchars($feature) ?></li><?php endforeach; ?></ul>
        <?php if (!empty($plan['id'])): ?><form method="post" action="<?= sf_url('subscribe.php') ?>" class="sf-plan-form"><?= sf_csrf_field() ?><input type="hidden" name="plan_id" value="<?= (int)$plan['id'] ?>"><button type="submit" class="plan-cta <?= $badge ? 'solid' : '' ?>">Continue to Checkout</button></form><?php else: ?><a href="<?= sf_url('signup.php') ?>" class="plan-cta <?= $badge ? 'solid' : '' ?>">Create Account</a><?php endif; ?>
      </article>
    <?php endforeach; ?>
  </section>
  <section class="sf-admin-panel sf-billing-table-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Benefit Matrix</span><h2>Compare member access</h2></div><a href="<?= sf_url('api/tier-packages.php') ?>">Tier API</a></div><div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Benefit</th><?php foreach($matrix['plans'] as $plan): ?><th><?= htmlspecialchars($plan['name'] ?? '') ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach($matrix['benefits'] as $benefit): ?><tr><td><strong><?= htmlspecialchars($benefit['label']) ?></strong></td><?php foreach($matrix['plans'] as $plan): $cell=$benefit['plans'][$plan['slug']??$plan['name']]??['enabled'=>false,'value'=>'—']; ?><td><?= !empty($cell['enabled']) ? '✓ ' . htmlspecialchars($cell['value']) : '—' ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div></section>
  <section class="part-of-band-banner"><div class="banner-mark">SF</div><h2>You’re not just watching.<br>You’re part of the band.</h2></section>
  <section class="benefit-row-template"><?php foreach ($benefits as $benefit): ?><article><div class="benefit-icon"><?= htmlspecialchars($benefit['icon']) ?></div><h3><?= htmlspecialchars($benefit['title']) ?></h3><p><?= htmlspecialchars($benefit['text']) ?></p></article><?php endforeach; ?></section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
