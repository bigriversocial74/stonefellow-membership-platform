<?php

declare(strict_types=1);

$pageTitle = 'Checkout';
$pageDescription = 'Secure checkout for DesertRio merchandise.';
$pageClass = 'shop-page checkout-page desertrio-checkout-template';
$pageExtraStyles = ['css/desertrio-commerce.css'];
require __DIR__ . '/includes/live_commerce.php';

$items = sf_store_cart_items();
$user = sf_auth_user();
$customer = sf_store_customer_from_post($_POST ?: [
    'email' => $user['email'] ?? '',
    'name' => $user['display_name'] ?? '',
    'country' => 'US',
]);
$discountCode = trim((string)($_POST['discount_code'] ?? ''));
$totals = sf_commerce_totals($items, $discountCode);
$ready = sf_commerce_checkout_ready();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
        sf_store_flash('error', 'Security check failed. Refresh and try again.');
        sf_store_redirect('checkout.php');
    }
    $customer = sf_store_customer_from_post($_POST);
    if (!$ready) {
        sf_store_flash('error', 'Checkout is unavailable until Stripe Connect onboarding is complete.');
        sf_store_redirect('checkout.php');
    }
    $result = sf_commerce_create_pending_checkout($customer, $discountCode);
    if (!empty($result['ok']) && !empty($result['checkout_url'])) {
        header('Location: ' . $result['checkout_url'], true, 303);
        exit;
    }
    sf_store_flash('error', (string)($result['error'] ?? 'Checkout could not be started.'));
    $items = sf_store_cart_items();
    $totals = sf_commerce_totals($items, $discountCode);
}

require __DIR__ . '/includes/header.php';
?>
<section class="shop-page-head shop-full-section">
  <span class="shop-kicker">Secure DesertRio Checkout</span>
  <h1>Complete Your Order</h1>
  <p><?= $ready ? 'Review your details, then continue to Stripe for secure payment.' : 'Checkout is temporarily unavailable while the merchant payment account is being connected.' ?></p>
</section>
<section class="checkout-layout shop-full-section">
  <div>
    <?php foreach (sf_store_flashes() as $flash): ?><div class="sf-admin-alert"><?= sf_store_h($flash['message'] ?? '') ?></div><?php endforeach; ?>
    <?php if (!$items): ?>
      <article class="cart-empty-state"><h2>Your cart is empty.</h2><p>Return to the DesertRio collection to choose your favorites.</p><a class="shop-btn shop-btn-primary" href="<?= sf_url('merch.php') ?>">Explore the Shop</a></article>
    <?php elseif (!$ready): ?>
      <article class="cart-empty-state"><h2>Secure checkout is not connected yet.</h2><p>Your cart remains saved. An administrator must complete Stripe Connect onboarding before payments can be accepted.</p><a class="shop-btn shop-btn-primary" href="<?= sf_url('cart.php') ?>">Return to Cart</a></article>
    <?php else: ?>
      <form class="checkout-form" method="post" autocomplete="on">
        <?= sf_csrf_field() ?>
        <fieldset><legend>Contact Details</legend>
          <label>Email <input type="email" name="email" value="<?= sf_store_h($customer['email'] ?? '') ?>" autocomplete="email" required></label>
          <label>Full Name <input type="text" name="name" value="<?= sf_store_h($customer['name'] ?? '') ?>" autocomplete="name" required maxlength="190"></label>
          <label>Phone <input type="tel" name="phone" value="<?= sf_store_h($customer['phone'] ?? '') ?>" autocomplete="tel" maxlength="40"></label>
        </fieldset>
        <fieldset><legend>Shipping Address</legend>
          <label>Address <input name="address_1" value="<?= sf_store_h($customer['address_1'] ?? '') ?>" autocomplete="shipping address-line1" required maxlength="190"></label>
          <label>Address 2 <input name="address_2" value="<?= sf_store_h($customer['address_2'] ?? '') ?>" autocomplete="shipping address-line2" maxlength="190"></label>
          <label>City <input name="city" value="<?= sf_store_h($customer['city'] ?? '') ?>" autocomplete="shipping address-level2" required maxlength="120"></label>
          <label>State <input name="state" value="<?= sf_store_h($customer['state'] ?? '') ?>" autocomplete="shipping address-level1" required maxlength="120"></label>
          <label>ZIP <input name="postal_code" value="<?= sf_store_h($customer['postal_code'] ?? '') ?>" autocomplete="shipping postal-code" required maxlength="40"></label>
          <input type="hidden" name="country" value="US">
        </fieldset>
        <fieldset><legend>Member or Promotion Code</legend>
          <label>Discount Code <input name="discount_code" value="<?= sf_store_h($discountCode) ?>" maxlength="80" autocapitalize="characters"></label>
          <?php if ($discountCode !== '' && empty($totals['discount']['valid'])): ?><p role="alert"><?= sf_store_h($totals['discount']['message'] ?? 'Discount code is invalid.') ?></p><?php endif; ?>
        </fieldset>
        <div class="payment-placeholder"><strong>Stripe Secure Checkout</strong><p>You will continue to Stripe to enter payment details. DesertRio never stores card numbers.</p></div>
        <button class="shop-btn shop-btn-primary" type="submit">Continue to Stripe · <?= sf_store_money((int)$totals['total_cents']) ?></button>
      </form>
    <?php endif; ?>
  </div>
  <aside class="cart-summary-panel"><h2>Order Review</h2>
    <?php foreach ($items as $item): ?><div class="checkout-mini-item"><span><?= sf_store_h($item['product_name'] ?? '') ?> × <?= (int)($item['quantity'] ?? 0) ?></span><strong><?= sf_store_money((int)($item['unit_price_cents'] ?? 0) * (int)($item['quantity'] ?? 0)) ?></strong></div><?php endforeach; ?>
    <div class="summary-line"><span>Subtotal</span><strong><?= sf_store_money((int)$totals['subtotal_cents']) ?></strong></div>
    <?php if ((int)$totals['discount_cents'] > 0): ?><div class="summary-line"><span>Discount</span><strong>−<?= sf_store_money((int)$totals['discount_cents']) ?></strong></div><?php endif; ?>
    <div class="summary-line"><span>Shipping</span><strong><?= sf_store_money((int)$totals['shipping_cents']) ?></strong></div>
    <div class="summary-line"><span>Tax</span><strong><?= sf_store_money((int)$totals['tax_cents']) ?></strong></div>
    <div class="summary-line summary-total"><span>Total</span><strong><?= sf_store_money((int)$totals['total_cents']) ?></strong></div>
    <small>Final totals are recalculated on the server when checkout starts.</small>
  </aside>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>