<?php

declare(strict_types=1);

$pageTitle = 'Payment Status';
$pageDescription = 'Stonefellow Stripe payment status.';
$pageClass = 'shop-page order-page';
require __DIR__ . '/includes/live_commerce.php';
$token = trim((string)($_GET['token'] ?? ''));
$key = trim((string)($_GET['key'] ?? ''));
$checkout = sf_commerce_checkout_by_token($token);
$authorized = $checkout && sf_commerce_checkout_authorized($checkout, $key);
if (!$authorized) $checkout = null;
$order = $checkout ? sf_store_order_lookup_authorized((string)$checkout['order_number'], $key) : null;
require __DIR__ . '/includes/header.php';
?>
<section class="order-confirmation shop-full-section">
<?php if (!$checkout): ?>
  <span class="shop-kicker">Payment Status</span><h1>Checkout not found.</h1><p>The payment link is invalid or does not belong to this account.</p>
<?php elseif (($checkout['status'] ?? '') === 'completed' && $order): ?>
  <span class="shop-kicker">Payment Confirmed</span><h1>Your order is complete.</h1><p>Stripe confirmed the payment and Stonefellow has reserved the order for fulfillment.</p>
  <div class="order-card"><strong>Order #<?= sf_store_h($order['order_number'] ?? '') ?></strong><span>Status: Paid · Total: <?= sf_store_money((int)($order['total_cents'] ?? 0)) ?></span></div>
  <p><a class="shop-btn shop-btn-primary" href="<?= sf_url('order-confirmation.php?order=' . urlencode((string)$order['order_number']) . '&key=' . urlencode($key)) ?>">View Receipt</a></p>
<?php elseif (in_array((string)($checkout['status'] ?? ''), ['failed','expired','canceled'], true)): ?>
  <span class="shop-kicker">Payment Not Completed</span><h1>Your order was not charged.</h1><p>The Stripe session failed, expired, or was canceled. Released inventory can be purchased again from your cart.</p><a class="shop-btn shop-btn-primary" href="<?= sf_url('cart.php') ?>">Return to Cart</a>
<?php else: ?>
  <span class="shop-kicker">Payment Processing</span><h1>Stripe is confirming your payment.</h1><p>The signed Stripe webhook is the source of truth. Refresh this page shortly; do not submit a second payment.</p>
  <div class="order-card"><strong>Order #<?= sf_store_h($checkout['order_number'] ?? '') ?></strong><span>Status: <?= sf_store_h(ucwords(str_replace('_', ' ', (string)($checkout['status'] ?? 'pending')))) ?></span></div>
<?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
