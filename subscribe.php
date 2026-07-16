<?php
$pageTitle = 'Subscribe';
$pageDescription = 'Choose DesertRio streaming membership access.';
$pageClass = 'subscribe-template desertrio-subscribe-template';
$pageExtraStyles = ['css/desertrio-commerce.css'];
require __DIR__ . '/includes/membership_tiers.php';
require_once __DIR__ . '/includes/billing_checkout_runtime.php';
require_once __DIR__ . '/includes/revenue_dashboard.php';
require __DIR__ . '/includes/desertrio_theme.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
        sf_auth_flash('error', 'Security check failed.');
    } elseif (!sf_auth_user()) {
        sf_auth_flash('warning', 'Sign in before choosing a plan.');
        sf_redirect(sf_url('signin.php?next=' . urlencode('subscribe.php')));
    } else {
        $planId = (int)($_POST['plan_id'] ?? 0);
        $plan = $planId ? sf_billing_plan($planId) : null;
        sf_rev_record_conversion('start_checkout', $planId, 0, (int)($plan['price_cents'] ?? 0), 'subscribe_page');
        $token = $planId ? sf_billing_start_checkout_secure($planId) : null;
        if ($token) sf_redirect(sf_url('billing-checkout.php?token=' . urlencode($token)));
    }
}

sf_rev_record_conversion('view_pricing', 0, 0, 0, 'subscribe_page');
$plans = sf_tiers_public_plans();
$matrix = sf_tiers_benefit_matrix();
require __DIR__ . '/includes/header.php';
?>
<section class="subscribe-page">
  <section class="subscribe-hero">
    <div class="subscribe-hero-copy">
      <span class="subscribe-kicker">DesertRio Membership</span>
      <h1>Go Beyond<br>the Velvet Rope.</h1>
      <div class="subscribe-rule"></div>
      <h2>Watch every episode. Follow every reveal. Stay close to the cast.</h2>
      <p>Provider-verified checkout protects every membership activation while the existing access, billing, and entitlement systems remain unchanged.</p>
    </div>
    <div class="subscribe-hero-art"><img src="<?= sf_asset($desertRioAssets['story_allies']) ?>" alt="DesertRio cast at an exclusive Arizona gathering"></div>
  </section>

  <section class="pricing-grid-template">
    <?php foreach ($plans as $plan): $price = (int)($plan['price_cents'] ?? 0); $badge = (string)($plan['public_badge'] ?? ''); ?>
      <article class="subscribe-plan-card <?= $badge ? 'is-featured' : '' ?>">
        <?php if ($badge): ?><div class="plan-ribbon"><?= htmlspecialchars($badge) ?></div><?php endif; ?>
        <h3><?= htmlspecialchars($plan['name'] ?? 'Membership') ?></h3>
        <div class="plan-price"><span class="dollar">$</span><?= (int)floor($price / 100) ?><span class="cents"><?= str_pad((string)($price % 100), 2, '0', STR_PAD_LEFT) ?></span><em>/ <?= htmlspecialchars($plan['billing_interval'] ?? 'month') ?></em></div>
        <p class="plan-note"><?= htmlspecialchars($plan['description'] ?? '') ?></p>
        <ul><?php foreach (sf_tiers_plan_features($plan) as $feature): ?><li><?= htmlspecialchars($feature) ?></li><?php endforeach; ?></ul>
        <form method="post"><?= sf_csrf_field() ?><input type="hidden" name="plan_id" value="<?= (int)($plan['id'] ?? 0) ?>"><button type="submit" class="plan-cta <?= $badge ? 'solid' : '' ?>">Continue to Checkout</button></form>
      </article>
    <?php endforeach; ?>
  </section>

  <section class="sf-admin-panel sf-billing-table-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Membership Comparison</span><h2>Choose your level of access</h2></div></div>
    <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Benefit</th><?php foreach ($matrix['plans'] as $plan): ?><th><?= htmlspecialchars($plan['name'] ?? '') ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach ($matrix['benefits'] as $benefit): ?><tr><td><?= htmlspecialchars($benefit['label']) ?></td><?php foreach ($matrix['plans'] as $plan): $cell = $benefit['plans'][$plan['slug'] ?? $plan['name']] ?? ['enabled' => false, 'value' => '—']; ?><td><?= !empty($cell['enabled']) ? '✓ ' . htmlspecialchars($cell['value']) : '—' ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div>
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>