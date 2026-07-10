<?php

declare(strict_types=1);

$pageTitle = 'Payment Reconciliation';
$pageDescription = 'Reconcile Stripe checkout, order, transaction, webhook, and inventory evidence.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/live_commerce.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
        sf_admin_flash('error', 'Security check failed.');
        sf_admin_redirect(sf_url('admin/payment-reconciliation.php'));
    }
    if (($_POST['action'] ?? '') === 'cleanup_expired') {
        $released = sf_commerce_release_expired_reservations(2000);
        sf_admin_flash('success', $released . ' expired reservation(s) released.');
        sf_admin_redirect(sf_url('admin/payment-reconciliation.php'));
    }
}

$summary = sf_commerce_reconciliation_summary();
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Payment Reconciliation', 'Stripe commerce evidence', 'Find webhook failures, stale checkout sessions, transaction mismatches, and expired inventory reservations.', 'payments');
?>
<section class="sf-admin-stats-grid">
<?php foreach ($summary['counts'] as $key => $count): ?><div><span><?= sf_admin_h(ucwords(str_replace('_', ' ', $key))) ?></span><strong><?= (int)$count ?></strong><small><?= $count ? 'Requires review' : 'No current discrepancy' ?></small></div><?php endforeach; ?>
</section>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Maintenance</span><h2>Reservation cleanup</h2></div><form method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="cleanup_expired"><button type="submit">Release Expired Reservations</button></form></div>
  <p>Checkout inventory is reserved for 30 minutes and consumed only after Stripe confirms payment by signed webhook.</p>
</section>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Exceptions</span><h2>Reconciliation issues</h2></div><a href="<?= sf_url('admin/orders.php') ?>">Open Orders</a></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Type</th><th>Reference</th><th>Detail</th><th>Created</th></tr></thead><tbody>
  <?php if (!$summary['issues']): ?><tr><td colspan="4">No payment reconciliation exceptions are currently detected.</td></tr><?php endif; ?>
  <?php foreach ($summary['issues'] as $issue): ?><tr><td><?= sf_admin_h(ucwords(str_replace('_', ' ', (string)$issue['type']))) ?></td><td><?= sf_admin_h($issue['reference'] ?? '') ?></td><td><?= sf_admin_h($issue['detail'] ?? '') ?></td><td><?= sf_admin_h($issue['created_at'] ?? '') ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
