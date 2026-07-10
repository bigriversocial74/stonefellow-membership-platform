<?php

declare(strict_types=1);

$pageTitle = 'Merch Orders';
$pageDescription = 'Manage Stripe payments, merchandise fulfillment, refunds, and order history.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/live_commerce.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
        sf_admin_flash('error', 'Security check failed. Refresh and try again.');
        sf_admin_redirect(sf_url('admin/orders.php'));
    }
    $action = (string)($_POST['action'] ?? '');
    $orderId = sf_admin_int($_POST['order_id'] ?? null, 0) ?? 0;
    if ($action === 'update_status') {
        $result = sf_commerce_update_order_status($orderId, (string)($_POST['status'] ?? ''), trim((string)($_POST['note'] ?? '')));
        sf_admin_flash(!empty($result['ok']) ? 'success' : 'error', !empty($result['ok']) ? 'Order status updated.' : (string)($result['error'] ?? 'Order status could not be updated.'));
        sf_admin_redirect(sf_url('admin/orders.php?view=' . $orderId));
    }
    if ($action === 'refund_order') {
        $confirmation = trim((string)($_POST['confirmation'] ?? ''));
        $order = sf_store_order_by_id($orderId);
        if (!$order || !hash_equals((string)($order['order_number'] ?? ''), $confirmation)) {
            sf_admin_flash('error', 'Enter the exact order number to request the refund.');
        } else {
            $result = sf_commerce_request_full_refund($orderId);
            sf_admin_flash(!empty($result['ok']) ? 'success' : 'error', !empty($result['ok']) ? (string)$result['message'] : (string)($result['error'] ?? 'Refund request failed.'));
        }
        sf_admin_redirect(sf_url('admin/orders.php?view=' . $orderId));
    }
}

$orders = sf_store_recent_orders(100);
$viewId = sf_admin_int($_GET['view'] ?? null, 0) ?? 0;
$selectedOrder = $viewId > 0 ? sf_store_order_by_id($viewId) : ($orders[0] ?? null);
$history = $selectedOrder ? sf_store_order_history((int)($selectedOrder['id'] ?? 0)) : [];
$totals = ['gross' => 0, 'paid' => 0, 'pending' => 0, 'fulfilled' => 0, 'disputed' => 0];
foreach ($orders as $order) {
    if (($order['payment_status'] ?? '') === 'paid') $totals['gross'] += (int)($order['total_cents'] ?? 0);
    $status = (string)($order['status'] ?? '');
    if (isset($totals[$status])) $totals[$status]++;
    if (($order['payment_status'] ?? '') === 'disputed') $totals['disputed']++;
}

require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Merch Commerce', 'Orders + Payments', 'Review Stripe settlement, fulfillment, refunds, disputes, and immutable order history.', 'orders');
?>
<section class="sf-admin-stats-grid">
  <div><span>Total Orders</span><strong><?= count($orders) ?></strong><small>Latest 100 shown</small></div>
  <div><span>Settled Gross</span><strong><?= sf_store_money((int)$totals['gross']) ?></strong><small>Paid orders only</small></div>
  <div><span>Paid</span><strong><?= (int)$totals['paid'] ?></strong><small>Eligible for fulfillment</small></div>
  <div><span>Disputed</span><strong><?= (int)$totals['disputed'] ?></strong><small>Immediate review required</small></div>
</section>
<section class="sf-admin-card-grid">
  <a class="sf-admin-action-card" href="<?= sf_url('admin/products.php') ?>"><span>Products</span><strong>Manage Merch</strong><small>Pricing, inventory, variants, and access gates.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/payment-gateways.php') ?>"><span>Providers</span><strong>Stripe Connect</strong><small>Merchant onboarding, charges, payouts, and provider mode.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/payment-reconciliation.php') ?>"><span>Evidence</span><strong>Reconciliation</strong><small>Find webhook, order, transaction, and reservation mismatches.</small></a>
</section>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Order Queue</span><h2>Recent merchandise orders</h2></div></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Order</th><th>Customer</th><th>Payment</th><th>Fulfillment</th><th>Total</th><th>Created</th><th></th></tr></thead><tbody>
  <?php if (!$orders): ?><tr><td colspan="7">No orders yet.</td></tr><?php endif; ?>
  <?php foreach ($orders as $order): ?><tr><td><strong><?= sf_admin_h($order['order_number'] ?? '') ?></strong><br><small><?= sf_admin_h($order['payment_provider'] ?? 'legacy') ?> · <?= sf_admin_h($order['external_payment_id'] ?? '') ?></small></td><td><?= sf_admin_h($order['shipping_name'] ?? '') ?><br><small><?= sf_admin_h($order['customer_email'] ?? $order['user_email'] ?? '') ?></small></td><td><?= sf_admin_status_badge($order['payment_status'] ?? 'unpaid') ?></td><td><?= sf_admin_status_badge($order['fulfillment_status'] ?? 'unfulfilled') ?></td><td><?= sf_store_money((int)($order['total_cents'] ?? 0)) ?></td><td><?= sf_admin_h($order['created_at'] ?? '') ?></td><td><a href="<?= sf_url('admin/orders.php?view=' . (int)$order['id']) ?>">Review</a></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>
<?php if ($selectedOrder): ?>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Order Detail</span><h2><?= sf_admin_h($selectedOrder['order_number'] ?? '') ?></h2></div><div><a href="<?= sf_url('order-confirmation.php?order=' . urlencode((string)$selectedOrder['order_number']) . '&key=' . urlencode((string)($selectedOrder['receipt_token'] ?? ''))) ?>">Receipt</a> · <a href="<?= sf_url('order-receipt.php?order=' . urlencode((string)$selectedOrder['order_number']) . '&key=' . urlencode((string)($selectedOrder['receipt_token'] ?? '')) . '&download=1') ?>">Download</a></div></div>
  <?php if (($selectedOrder['payment_status'] ?? '') === 'disputed'): ?><div class="sf-admin-alert">This order has an active Stripe dispute. Pause fulfillment and review the Stripe evidence.</div><?php endif; ?>
  <div class="order-detail-grid admin-order-detail-grid"><article class="order-detail-panel"><h3>Customer</h3><p><strong><?= sf_admin_h($selectedOrder['shipping_name'] ?? '') ?></strong><br><?= sf_admin_h($selectedOrder['customer_email'] ?? '') ?><br><?= sf_admin_h($selectedOrder['customer_phone'] ?? '') ?></p><p><?= sf_admin_h($selectedOrder['shipping_address_1'] ?? '') ?><br><?php if (!empty($selectedOrder['shipping_address_2'])): ?><?= sf_admin_h($selectedOrder['shipping_address_2']) ?><br><?php endif; ?><?= sf_admin_h($selectedOrder['shipping_city'] ?? '') ?>, <?= sf_admin_h($selectedOrder['shipping_state'] ?? '') ?> <?= sf_admin_h($selectedOrder['shipping_postal_code'] ?? '') ?><br><?= sf_admin_h($selectedOrder['shipping_country'] ?? 'US') ?></p></article><article class="order-detail-panel"><h3>Payment Evidence</h3><p><strong><?= sf_admin_h(strtoupper((string)($selectedOrder['payment_provider'] ?? ''))) ?></strong><br>Payment: <?= sf_admin_h($selectedOrder['external_payment_id'] ?? 'Pending') ?><br>Checkout: <?= sf_admin_h($selectedOrder['provider_checkout_id'] ?? 'Pending') ?><br>Paid: <?= sf_admin_h($selectedOrder['paid_at'] ?? 'Not settled') ?></p><div class="summary-line"><span>Subtotal</span><strong><?= sf_store_money((int)($selectedOrder['subtotal_cents'] ?? 0)) ?></strong></div><div class="summary-line"><span>Shipping</span><strong><?= sf_store_money((int)($selectedOrder['shipping_cents'] ?? 0)) ?></strong></div><div class="summary-line"><span>Tax</span><strong><?= sf_store_money((int)($selectedOrder['tax_cents'] ?? 0)) ?></strong></div><div class="summary-line summary-total"><span>Total</span><strong><?= sf_store_money((int)($selectedOrder['total_cents'] ?? 0)) ?></strong></div></article></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Item</th><th>Variant</th><th>Qty</th><th>Unit</th><th>Total</th></tr></thead><tbody><?php foreach (($selectedOrder['items'] ?? []) as $item): ?><tr><td><?= sf_admin_h($item['product_name'] ?? '') ?></td><td><?= sf_admin_h($item['variant_name'] ?? '') ?></td><td><?= (int)($item['quantity'] ?? 0) ?></td><td><?= sf_store_money((int)($item['unit_price_cents'] ?? 0)) ?></td><td><?= sf_store_money((int)($item['total_price_cents'] ?? 0)) ?></td></tr><?php endforeach; ?></tbody></table></div>
  <form class="sf-admin-form" method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="update_status"><input type="hidden" name="order_id" value="<?= (int)$selectedOrder['id'] ?>"><div class="sf-admin-form-grid"><label>Status<?= sf_admin_select('status', sf_store_order_status_options(), $selectedOrder['status'] ?? 'pending') ?></label><label>Internal Note<input name="note" placeholder="Tracking, fulfillment, or cancellation note"<?= sf_admin_form_disabled_attr() ?>></label></div><div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>>Update Operational Status</button></div></form>
  <?php if (($selectedOrder['payment_provider'] ?? '') === 'stripe' && ($selectedOrder['payment_status'] ?? '') === 'paid'): ?><form class="sf-admin-form" method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="refund_order"><input type="hidden" name="order_id" value="<?= (int)$selectedOrder['id'] ?>"><div class="sf-admin-form-grid"><label>Confirm Full Refund<input name="confirmation" placeholder="Type <?= sf_admin_h($selectedOrder['order_number'] ?? '') ?>" required></label></div><p>A refund is requested through Stripe. Inventory is restored only after Stripe confirms the full refund by signed webhook.</p><div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>>Request Full Stripe Refund</button></div></form><?php endif; ?>
</section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Audit Trail</span><h2>Status history</h2></div></div><div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>From</th><th>To</th><th>Note</th><th>Changed By</th><th>Date</th></tr></thead><tbody><?php if (!$history): ?><tr><td colspan="5">No order history entries yet.</td></tr><?php endif; ?><?php foreach ($history as $row): ?><tr><td><?= sf_admin_h($row['from_status'] ?? '') ?></td><td><?= sf_admin_h($row['to_status'] ?? '') ?></td><td><?= sf_admin_h($row['note'] ?? '') ?></td><td><?= sf_admin_h($row['display_name'] ?? 'System') ?></td><td><?= sf_admin_h($row['created_at'] ?? '') ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php endif; ?>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
