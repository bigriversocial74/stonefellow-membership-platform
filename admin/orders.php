<?php
$pageTitle = 'Merch Orders';
$pageDescription = 'Manage Stonefellow merch orders, payment status, fulfillment status, and order history.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require __DIR__ . '/../includes/store.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
    sf_admin_flash('error', 'Security check failed. Refresh and try again.');
    sf_admin_redirect(sf_url('admin/orders.php'));
  }
  $action = (string)($_POST['action'] ?? '');
  if ($action === 'update_status') {
    if (!sf_admin_db_ready()) {
      sf_admin_flash('warning', 'Database is not configured. Order updates are disabled in static preview mode.');
    } else {
      $orderId = sf_admin_int($_POST['order_id'] ?? null, 0) ?? 0;
      $status = (string)($_POST['status'] ?? '');
      $note = (string)($_POST['note'] ?? '');
      if (sf_store_update_order_status($orderId, $status, $note)) {
        sf_admin_flash('success', 'Order status updated.');
      } else {
        sf_admin_flash('error', 'Order status could not be updated.');
      }
    }
    sf_admin_redirect(sf_url('admin/orders.php?view=' . (int)($_POST['order_id'] ?? 0)));
  }
}

$orders = sf_store_recent_orders(100);
$viewId = sf_admin_int($_GET['view'] ?? null, 0) ?? 0;
$selectedOrder = $viewId > 0 ? sf_store_order_by_id($viewId) : ($orders[0] ?? null);
$history = $selectedOrder ? sf_store_order_history((int)($selectedOrder['id'] ?? 0)) : [];
$totals = [
  'gross' => 0,
  'paid' => 0,
  'pending' => 0,
  'fulfilled' => 0,
];
foreach ($orders as $order) {
  $totals['gross'] += (int)($order['total_cents'] ?? 0);
  $status = (string)($order['status'] ?? '');
  if (isset($totals[$status])) {
    $totals[$status]++;
  }
}

require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Merch Runtime', 'Orders + Fulfillment', 'Review merch orders, update fulfillment states, audit status history, and verify sandbox payment records.', 'orders');
?>
<section class="sf-admin-stats-grid">
  <div><span>Total Orders</span><strong><?= count($orders) ?></strong><small>Latest 100 shown</small></div>
  <div><span>Gross Merch</span><strong><?= sf_store_money((int)$totals['gross']) ?></strong><small>Paid and pending included</small></div>
  <div><span>Paid</span><strong><?= (int)$totals['paid'] ?></strong><small>Ready for fulfillment</small></div>
  <div><span>Fulfilled</span><strong><?= (int)$totals['fulfilled'] ?></strong><small>Marked complete</small></div>
</section>

<section class="sf-admin-card-grid">
  <a class="sf-admin-action-card" href="<?= sf_url('admin/products.php') ?>"><span>Products</span><strong>Manage Merch</strong><small>Pricing, inventory, variants, access gates.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('checkout.php') ?>"><span>Checkout</span><strong>Test Checkout</strong><small>Create a sandbox order from the active cart.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/billing.php') ?>"><span>Payments</span><strong>Billing Logs</strong><small>Review merch payment transactions.</small></a>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Order Queue</span><h2>Recent merch orders</h2></div></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Order</th><th>Customer</th><th>Status</th><th>Total</th><th>Created</th><th></th></tr></thead><tbody>
    <?php if (!$orders): ?><tr><td colspan="6">No orders yet. Place a sandbox order through the store checkout.</td></tr><?php endif; ?>
    <?php foreach ($orders as $order): ?>
      <tr><td><strong><?= sf_admin_h($order['order_number'] ?? '') ?></strong><br><small><?= sf_admin_h($order['external_payment_id'] ?? '') ?></small></td><td><?= sf_admin_h($order['shipping_name'] ?? '') ?><br><small><?= sf_admin_h($order['customer_email'] ?? $order['user_email'] ?? '') ?></small></td><td><?= sf_admin_status_badge($order['status'] ?? 'pending') ?><?php if (!empty($order['payment_status'])): ?><br><small>Payment: <?= sf_admin_h($order['payment_status']) ?></small><?php endif; ?><?php if (!empty($order['fulfillment_status'])): ?><br><small>Fulfillment: <?= sf_admin_h($order['fulfillment_status']) ?></small><?php endif; ?></td><td><?= sf_store_money((int)($order['total_cents'] ?? 0)) ?></td><td><?= sf_admin_h($order['created_at'] ?? '') ?></td><td><a href="<?= sf_url('admin/orders.php?view=' . (int)($order['id'] ?? 0)) ?>">Review</a></td></tr>
    <?php endforeach; ?>
  </tbody></table></div>
</section>

<?php if ($selectedOrder): ?>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Order Detail</span><h2><?= sf_admin_h($selectedOrder['order_number'] ?? '') ?></h2></div><a href="<?= sf_url('order-confirmation.php?order=' . urlencode((string)($selectedOrder['order_number'] ?? '')) . (!empty($selectedOrder['receipt_token']) ? '&key=' . urlencode((string)$selectedOrder['receipt_token']) : '')) ?>">Receipt</a></div>
  <div class="order-detail-grid admin-order-detail-grid">
    <article class="order-detail-panel"><h3>Customer</h3><p><strong><?= sf_admin_h($selectedOrder['shipping_name'] ?? '') ?></strong><br><?= sf_admin_h($selectedOrder['customer_email'] ?? '') ?><br><?= sf_admin_h($selectedOrder['customer_phone'] ?? '') ?></p><p><?= sf_admin_h($selectedOrder['shipping_address_1'] ?? '') ?><br><?php if (!empty($selectedOrder['shipping_address_2'])): ?><?= sf_admin_h($selectedOrder['shipping_address_2']) ?><br><?php endif; ?><?= sf_admin_h($selectedOrder['shipping_city'] ?? '') ?>, <?= sf_admin_h($selectedOrder['shipping_state'] ?? '') ?> <?= sf_admin_h($selectedOrder['shipping_postal_code'] ?? '') ?><br><?= sf_admin_h($selectedOrder['shipping_country'] ?? 'US') ?></p></article>
    <article class="order-detail-panel"><h3>Totals</h3><div class="summary-line"><span>Subtotal</span><strong><?= sf_store_money((int)($selectedOrder['subtotal_cents'] ?? 0)) ?></strong></div><div class="summary-line"><span>Shipping</span><strong><?= sf_store_money((int)($selectedOrder['shipping_cents'] ?? 0)) ?></strong></div><div class="summary-line"><span>Tax</span><strong><?= sf_store_money((int)($selectedOrder['tax_cents'] ?? 0)) ?></strong></div><div class="summary-line summary-total"><span>Total</span><strong><?= sf_store_money((int)($selectedOrder['total_cents'] ?? 0)) ?></strong></div></article>
  </div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Item</th><th>Variant</th><th>Qty</th><th>Unit</th><th>Total</th></tr></thead><tbody>
    <?php foreach (($selectedOrder['items'] ?? []) as $item): ?><tr><td><?= sf_admin_h($item['product_name'] ?? '') ?></td><td><?= sf_admin_h($item['variant_name'] ?? '') ?></td><td><?= (int)($item['quantity'] ?? 0) ?></td><td><?= sf_store_money((int)($item['unit_price_cents'] ?? 0)) ?></td><td><?= sf_store_money((int)($item['total_price_cents'] ?? 0)) ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
  <form class="sf-admin-form" action="<?= sf_url('admin/orders.php') ?>" method="post">
    <?= sf_csrf_field() ?>
    <input type="hidden" name="action" value="update_status">
    <input type="hidden" name="order_id" value="<?= (int)($selectedOrder['id'] ?? 0) ?>">
    <div class="sf-admin-form-grid"><label>Status <?= sf_admin_select('status', sf_store_order_status_options(), $selectedOrder['status'] ?? 'pending') ?></label><label>Internal Note <input name="note" placeholder="Tracking added, refunded, fulfilled, etc."<?= sf_admin_form_disabled_attr() ?>></label></div>
    <div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>>Update Order</button></div>
  </form>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Audit Trail</span><h2>Status history</h2></div></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>From</th><th>To</th><th>Note</th><th>Changed By</th><th>Date</th></tr></thead><tbody>
    <?php if (!$history): ?><tr><td colspan="5">No order history entries yet.</td></tr><?php endif; ?>
    <?php foreach ($history as $row): ?><tr><td><?= sf_admin_h($row['from_status'] ?? '') ?></td><td><?= sf_admin_h($row['to_status'] ?? '') ?></td><td><?= sf_admin_h($row['note'] ?? '') ?></td><td><?= sf_admin_h($row['display_name'] ?? 'System') ?></td><td><?= sf_admin_h($row['created_at'] ?? '') ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>
<?php endif; ?>
<?php
sf_admin_shell_end();
require __DIR__ . '/../includes/footer.php';
?>
