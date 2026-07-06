<?php
$pageTitle = 'Order Confirmation';
$pageDescription = 'Stonefellow order confirmation.';
$pageClass = 'shop-page order-page';
require __DIR__ . '/includes/store.php';

$orderNumber = (string)($_GET['order'] ?? '');
$key = (string)($_GET['key'] ?? '');
$order = sf_store_order_by_number($orderNumber, $key);
$pageTitle = $order ? 'Order ' . ($order['order_number'] ?? 'Confirmation') : 'Order Confirmation';
require __DIR__ . '/includes/header.php';
?>
<section class="order-confirmation shop-full-section">
  <?php if (!$order): ?>
    <span class="shop-kicker">Order Lookup</span>
    <h1>Order not found.</h1>
    <p>The confirmation link may be expired or missing its receipt key. Check your account order history or return to the store.</p>
    <div class="shop-actions"><a class="shop-btn shop-btn-primary" href="<?= sf_url('merch.php') ?>">Back to Store</a><a class="shop-btn shop-btn-outline" href="<?= sf_url('cart.php') ?>">View Cart</a></div>
  <?php else: ?>
    <span class="shop-kicker">Order Received</span>
    <h1>Welcome to the Road Crew.</h1>
    <p>Your Stonefellow merch order has been placed. Save this page for your records while email receipt delivery is wired to the production mail service.</p>
    <div class="order-card">
      <strong>Order #<?= sf_store_h($order['order_number'] ?? '') ?></strong>
      <span>Status: <?= sf_store_h(ucfirst((string)($order['status'] ?? 'paid'))) ?> · Total: <?= sf_store_money((int)($order['total_cents'] ?? 0)) ?></span>
      <small>Estimated fulfillment: Pilot launch window</small>
    </div>

    <section class="order-detail-grid">
      <article class="order-detail-panel">
        <h2>Items</h2>
        <?php foreach (($order['items'] ?? []) as $item): ?>
          <div class="checkout-mini-item">
            <span><?= sf_store_h($item['product_name'] ?? '') ?><?= !empty($item['variant_name']) ? ' · ' . sf_store_h($item['variant_name']) : '' ?> × <?= (int)($item['quantity'] ?? 0) ?></span>
            <strong><?= sf_store_money((int)($item['total_price_cents'] ?? ((int)($item['unit_price_cents'] ?? 0) * (int)($item['quantity'] ?? 0)))) ?></strong>
          </div>
        <?php endforeach; ?>
        <div class="summary-line"><span>Subtotal</span><strong><?= sf_store_money((int)($order['subtotal_cents'] ?? 0)) ?></strong></div>
        <div class="summary-line"><span>Shipping</span><strong><?= sf_store_money((int)($order['shipping_cents'] ?? 0)) ?></strong></div>
        <div class="summary-line"><span>Tax</span><strong><?= sf_store_money((int)($order['tax_cents'] ?? 0)) ?></strong></div>
        <div class="summary-line summary-total"><span>Total</span><strong><?= sf_store_money((int)($order['total_cents'] ?? 0)) ?></strong></div>
      </article>
      <article class="order-detail-panel">
        <h2>Ship To</h2>
        <p><strong><?= sf_store_h($order['shipping_name'] ?? '') ?></strong><br>
        <?= sf_store_h($order['shipping_address_1'] ?? '') ?><br>
        <?php if (!empty($order['shipping_address_2'])): ?><?= sf_store_h($order['shipping_address_2']) ?><br><?php endif; ?>
        <?= sf_store_h($order['shipping_city'] ?? '') ?>, <?= sf_store_h($order['shipping_state'] ?? '') ?> <?= sf_store_h($order['shipping_postal_code'] ?? '') ?><br>
        <?= sf_store_h($order['shipping_country'] ?? 'US') ?></p>
        <p><?= sf_store_h($order['customer_email'] ?? '') ?></p>
      </article>
    </section>
    <div class="shop-actions"><a class="shop-btn shop-btn-primary" href="<?= sf_url('merch.php') ?>">Back to Store</a><a class="shop-btn shop-btn-outline" href="<?= sf_url('music.php') ?>">Stream the Soundtrack</a></div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
