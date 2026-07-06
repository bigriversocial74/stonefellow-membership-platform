<?php
$pageTitle = 'Checkout';
$pageDescription = 'Checkout for Stonefellow merch.';
$pageClass = 'shop-page checkout-page';
require __DIR__ . '/includes/store.php';
require_once __DIR__ . '/includes/payment_gateway.php';

$cartItems = sf_store_cart_items();
$totals = sf_store_cart_totals($cartItems);
$user = sf_auth_user();
$gatewayStatus = sf_payment_gateway_status();
$customer = sf_store_customer_from_post($_POST ?: [
  'email' => $user['email'] ?? '',
  'name' => $user['display_name'] ?? '',
  'country' => 'US',
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
    sf_store_flash('error', 'Security check failed. Refresh and try again.');
    sf_store_redirect('checkout.php');
  }
  $customer = sf_store_customer_from_post($_POST);
  $order = sf_store_create_order($customer);
  if ($order) {
    $query = 'order=' . urlencode((string)$order['order_number']);
    if (!empty($order['receipt_token'])) {
      $query .= '&key=' . urlencode((string)$order['receipt_token']);
    }
    sf_store_redirect('order-confirmation.php?' . $query);
  }
  $cartItems = sf_store_cart_items();
  $totals = sf_store_cart_totals($cartItems);
}

require __DIR__ . '/includes/header.php';
?>
<section class="shop-page-head shop-full-section">
  <span class="shop-kicker">Secure Checkout</span>
  <h1>Complete Your Order</h1>
  <p>Sandbox checkout creates a real Stonefellow order record, line items, payment transaction, inventory movement, and status history when the database is connected.</p>
</section>
<section class="checkout-layout shop-full-section">
  <div>
    <?php foreach (sf_store_flashes() as $flash): ?>
      <div class="sf-admin-alert sf-admin-alert-<?= sf_store_h($flash['type'] ?? 'info') ?>"><?= sf_store_h($flash['message'] ?? '') ?></div>
    <?php endforeach; ?>

    <?php if (!$cartItems): ?>
      <article class="cart-empty-state">
        <span class="shop-kicker">Checkout Paused</span>
        <h2>Your cart is empty.</h2>
        <p>Add merch before continuing to checkout.</p>
        <a class="shop-btn shop-btn-primary" href="<?= sf_url('merch.php') ?>">Shop Merch</a>
      </article>
    <?php else: ?>
      <form class="checkout-form" action="<?= sf_url('checkout.php') ?>" method="post">
        <?= sf_csrf_field() ?>
        <fieldset>
          <legend>Contact</legend>
          <label>Email <input type="email" name="email" value="<?= sf_store_h($customer['email'] ?? '') ?>" placeholder="you@example.com" required></label>
          <label>Full Name <input type="text" name="name" value="<?= sf_store_h($customer['name'] ?? '') ?>" placeholder="Your name" required></label>
          <label>Phone <input type="tel" name="phone" value="<?= sf_store_h($customer['phone'] ?? '') ?>" placeholder="Optional"></label>
        </fieldset>
        <fieldset>
          <legend>Shipping Address</legend>
          <label>Address <input type="text" name="address_1" value="<?= sf_store_h($customer['address_1'] ?? '') ?>" placeholder="Street address" <?= $totals['has_physical'] ? 'required' : '' ?>></label>
          <label>Apartment / Suite <input type="text" name="address_2" value="<?= sf_store_h($customer['address_2'] ?? '') ?>" placeholder="Optional"></label>
          <div class="checkout-row"><label>City <input type="text" name="city" value="<?= sf_store_h($customer['city'] ?? '') ?>" <?= $totals['has_physical'] ? 'required' : '' ?>></label><label>State <input type="text" name="state" value="<?= sf_store_h($customer['state'] ?? '') ?>" <?= $totals['has_physical'] ? 'required' : '' ?>></label><label>ZIP <input type="text" name="postal_code" value="<?= sf_store_h($customer['postal_code'] ?? '') ?>" <?= $totals['has_physical'] ? 'required' : '' ?>></label></div>
          <label>Country <input type="text" name="country" value="<?= sf_store_h($customer['country'] ?? 'US') ?>"></label>
        </fieldset>
        <fieldset>
          <legend>Payment</legend>
          <div class="payment-placeholder"><strong><?= sf_store_h($gatewayStatus['label'] ?? 'Sandbox') ?> payment adapter active.</strong><br><?= !empty($gatewayStatus['ready']) ? 'Order runtime is ready. Sandbox submit records a paid merch order and marks inventory against the order.' : 'Provider credentials are incomplete, so sandbox order completion remains active for testing.' ?></div>
        </fieldset>
        <fieldset>
          <legend>Order Notes</legend>
          <label>Notes <textarea name="notes" rows="3" placeholder="Gift note, delivery instruction, size note, etc."><?= sf_store_h($customer['notes'] ?? '') ?></textarea></label>
        </fieldset>
        <button class="shop-btn shop-btn-primary" type="submit">Place Sandbox Order</button>
      </form>
    <?php endif; ?>
  </div>

  <aside class="cart-summary-panel checkout-summary">
    <h2>Order Review</h2>
    <?php if (!$cartItems): ?>
      <div class="checkout-mini-item"><span>No items</span><strong>$0.00</strong></div>
    <?php endif; ?>
    <?php foreach ($cartItems as $item): ?>
      <div class="checkout-mini-item"><span><?= sf_store_h($item['product_name'] ?? '') ?> × <?= (int)($item['quantity'] ?? 0) ?></span><strong><?= sf_store_money((int)($item['unit_price_cents'] ?? 0) * (int)($item['quantity'] ?? 0)) ?></strong></div>
    <?php endforeach; ?>
    <div class="summary-line"><span>Subtotal</span><strong><?= sf_store_money((int)$totals['subtotal_cents']) ?></strong></div>
    <div class="summary-line"><span>Shipping</span><strong><?= $totals['shipping_cents'] > 0 ? sf_store_money((int)$totals['shipping_cents']) : 'Free' ?></strong></div>
    <div class="summary-line"><span>Tax</span><strong><?= sf_store_money((int)$totals['tax_cents']) ?></strong></div>
    <div class="summary-line summary-total"><span>Total</span><strong><?= sf_store_money((int)$totals['total_cents']) ?></strong></div>
    <a class="shop-text-link" href="<?= sf_url('cart.php') ?>">← Back to Cart</a>
  </aside>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
