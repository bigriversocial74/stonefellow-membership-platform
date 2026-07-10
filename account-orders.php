<?php

declare(strict_types=1);

$pageTitle = 'My Orders';
$pageDescription = 'Stonefellow merchandise order history.';
$pageClass = 'membership-page account-orders-page';
require __DIR__ . '/includes/live_commerce.php';
$user = sf_auth_user();
if (!$user) sf_redirect(sf_url('signin.php?next=' . urlencode('account-orders.php')));
$orders = sf_commerce_orders_for_user((int)$user['id']);
require __DIR__ . '/includes/header.php';
?>
<section class="shop-page-head shop-full-section"><span class="shop-kicker">Account</span><h1>My Orders</h1><p>Review payment, fulfillment, and receipt details for your Stonefellow purchases.</p></section>
<section class="shop-full-section sf-admin-panel">
  <?php if (!$orders): ?><p>No merchandise orders are connected to this account yet.</p><?php else: ?>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Order</th><th>Payment</th><th>Fulfillment</th><th>Total</th><th>Date</th><th></th></tr></thead><tbody>
  <?php foreach ($orders as $order): ?><tr>
    <td><strong><?= sf_store_h($order['order_number'] ?? '') ?></strong></td>
    <td><?= sf_store_h(ucwords(str_replace('_', ' ', (string)($order['payment_status'] ?? 'unpaid')))) ?></td>
    <td><?= sf_store_h(ucwords(str_replace('_', ' ', (string)($order['fulfillment_status'] ?? 'unfulfilled')))) ?></td>
    <td><?= sf_store_money((int)($order['total_cents'] ?? 0)) ?></td>
    <td><?= sf_store_h($order['created_at'] ?? '') ?></td>
    <td><a href="<?= sf_url('order-confirmation.php?order=' . urlencode((string)$order['order_number'])) ?>">View</a> · <a href="<?= sf_url('order-receipt.php?order=' . urlencode((string)$order['order_number']) . '&download=1') ?>">Download</a></td>
  </tr><?php endforeach; ?>
  </tbody></table></div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
