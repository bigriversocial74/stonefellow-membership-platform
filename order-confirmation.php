<?php

declare(strict_types=1);

$pageTitle = 'Order Confirmation';
$pageDescription = 'Stonefellow order confirmation and receipt.';
$pageClass = 'shop-page order-page';
require __DIR__ . '/includes/live_commerce.php';
$number = (string)($_GET['order'] ?? '');
$key = (string)($_GET['key'] ?? '');
$order = sf_store_order_lookup_authorized($number, $key);
$pageTitle = $order ? 'Order ' . ($order['order_number'] ?? 'Confirmation') : 'Order Confirmation';
require __DIR__ . '/includes/header.php';
?>
<section class="order-confirmation shop-full-section">
<?php if (!$order): ?>
  <span class="shop-kicker">Order Lookup</span><h1>Order not found.</h1><p>The confirmation link is missing a valid receipt key, or the signed-in account does not own this order.</p><a class="shop-btn shop-btn-primary" href="<?= sf_url('merch.php') ?>">Back to Store</a>
<?php else: ?>
  <span class="shop-kicker"><?= ($order['payment_status'] ?? '') === 'paid' ? 'Payment Confirmed' : 'Order Status' ?></span>
  <h1><?= ($order['payment_status'] ?? '') === 'paid' ? 'Welcome to the Road Crew.' : 'Your order is being processed.' ?></h1>
  <div class="order-card"><strong>Order #<?= sf_store_h($order['order_number'] ?? '') ?></strong><span>Payment: <?= sf_store_h(ucwords(str_replace('_', ' ', (string)($order['payment_status'] ?? 'unpaid')))) ?> · Fulfillment: <?= sf_store_h(ucwords(str_replace('_', ' ', (string)($order['fulfillment_status'] ?? 'unfulfilled')))) ?> · Total: <?= sf_store_money((int)($order['total_cents'] ?? 0)) ?></span></div>
  <?php if (($order['payment_status'] ?? '') === 'disputed'): ?><p role="alert">This payment is under review. Contact support before taking further action.</p><?php endif; ?>
  <?php if (($order['payment_status'] ?? '') === 'failed'): ?><p role="alert">The payment did not complete. No order is treated as paid without Stripe confirmation.</p><?php endif; ?>
  <section class="order-detail-grid"><article class="order-detail-panel"><h2>Items</h2><?php foreach (($order['items'] ?? []) as $item): ?><div class="checkout-mini-item"><span><?= sf_store_h($item['product_name'] ?? '') ?> × <?= (int)($item['quantity'] ?? 0) ?></span><strong><?= sf_store_money((int)($item['total_price_cents'] ?? 0)) ?></strong></div><?php endforeach; ?></article><article class="order-detail-panel"><h2>Ship To</h2><p><strong><?= sf_store_h($order['shipping_name'] ?? '') ?></strong><br><?= sf_store_h($order['shipping_address_1'] ?? '') ?><br><?php if (!empty($order['shipping_address_2'])): ?><?= sf_store_h($order['shipping_address_2']) ?><br><?php endif; ?><?= sf_store_h($order['shipping_city'] ?? '') ?>, <?= sf_store_h($order['shipping_state'] ?? '') ?> <?= sf_store_h($order['shipping_postal_code'] ?? '') ?></p><p><?= sf_store_h($order['customer_email'] ?? '') ?></p></article></section>
  <div class="sf-episode-action-row"><a class="shop-btn shop-btn-primary" href="<?= sf_url('order-receipt.php?order=' . urlencode((string)$order['order_number']) . ($key !== '' ? '&key=' . urlencode($key) : '') . '&download=1') ?>">Download Receipt</a><a class="shop-btn" href="<?= sf_url('account-orders.php') ?>">My Orders</a><a class="shop-btn" href="<?= sf_url('merch.php') ?>">Back to Store</a></div>
<?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
