<?php
$pageTitle = 'Billing Admin';
$pageDescription = 'Manage Stonefellow subscription billing, checkouts, invoices, and payment events.';
$pageClass = 'membership-page admin-catalog-page billing-page';
require __DIR__ . '/../includes/admin_catalog.php';
require __DIR__ . '/../includes/billing.php';
require __DIR__ . '/../includes/header.php';

$summary = sf_billing_admin_summary();
$checkouts = sf_admin_table_exists('subscription_checkouts') ? sf_admin_fetch_all("\n  SELECT sc.*, u.email, u.display_name, sp.name AS plan_name\n  FROM subscription_checkouts sc\n  LEFT JOIN users u ON u.id = sc.user_id\n  LEFT JOIN subscription_plans sp ON sp.id = sc.plan_id\n  ORDER BY sc.created_at DESC, sc.id DESC\n  LIMIT 100\n") : [];
$subscriptions = sf_admin_table_exists('user_subscriptions') ? sf_admin_fetch_all("\n  SELECT us.*, u.email, u.display_name, sp.name AS plan_name, sp.price_cents, sp.billing_interval\n  FROM user_subscriptions us\n  LEFT JOIN users u ON u.id = us.user_id\n  LEFT JOIN subscription_plans sp ON sp.id = us.plan_id\n  ORDER BY us.created_at DESC, us.id DESC\n  LIMIT 100\n") : [];
$invoices = sf_admin_table_exists('invoices') ? sf_admin_fetch_all("\n  SELECT i.*, u.email, u.display_name\n  FROM invoices i\n  LEFT JOIN users u ON u.id = i.user_id\n  ORDER BY i.created_at DESC, i.id DESC\n  LIMIT 100\n") : [];
$transactions = sf_admin_table_exists('payment_transactions') ? sf_admin_fetch_all("\n  SELECT pt.*, u.email, u.display_name\n  FROM payment_transactions pt\n  LEFT JOIN users u ON u.id = pt.user_id\n  ORDER BY pt.created_at DESC, pt.id DESC\n  LIMIT 100\n") : [];
$webhooks = sf_admin_table_exists('billing_webhook_events') ? sf_admin_fetch_all("SELECT * FROM billing_webhook_events ORDER BY created_at DESC, id DESC LIMIT 50") : [];

sf_admin_shell_start('Billing v1', 'Subscriptions + payments', 'Review sandbox/processor checkouts, active subscriptions, invoices, payment transactions, and webhook events.', 'billing');
?>
<section class="sf-admin-metrics-grid">
  <article><span>Checkouts</span><strong><?= number_format((int)$summary['checkouts']) ?></strong><small><?= number_format((int)$summary['completed_checkouts']) ?> completed</small></article>
  <article><span>Revenue</span><strong><?= sf_billing_money((int)$summary['revenue_cents']) ?></strong><small>Paid transactions</small></article>
  <article><span>Invoices</span><strong><?= number_format((int)$summary['invoices']) ?></strong><small>Generated billing records</small></article>
  <article><span>Active Subs</span><strong><?= number_format((int)$summary['active_subscriptions']) ?></strong><small>Active/trialing members</small></article>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Runtime</span><h2>Billing status</h2></div><a href="<?= sf_url('docs/BILLING_SUBSCRIPTIONS_V1.md') ?>">Docs</a></div>
  <div class="sf-admin-roadmap">
    <div><span><?= sf_billing_is_ready() ? '✓' : '!' ?></span><strong><?= sf_billing_is_ready() ? 'Billing tables ready' : 'Migration needed' ?></strong><p><?= sf_billing_is_ready() ? 'Checkout, invoice, payment, and webhook tables are installed.' : 'Run database/migrations/004_billing_entitlements.sql after the existing migrations.' ?></p></div>
    <div><span>✓</span><strong>Sandbox processor</strong><p>Checkout can activate real subscriptions without charging a card while the product is tested.</p></div>
    <div><span>→</span><strong>Production processor</strong><p>Set SF_PAYMENT_PROVIDER and wire api/billing-webhook.php to Stripe or the final processor.</p></div>
  </div>
</section>

<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Subscriptions</span><h2>Latest subscriptions</h2></div></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Member</th><th>Plan</th><th>Status</th><th>Period End</th><th>Provider Ref</th></tr></thead><tbody>
    <?php if (!$subscriptions): ?><tr><td colspan="5">No subscriptions found.</td></tr><?php endif; ?>
    <?php foreach ($subscriptions as $sub): ?><tr><td><?= sf_admin_h($sub['display_name'] ?: $sub['email']) ?></td><td><?= sf_admin_h($sub['plan_name'] ?? '') ?></td><td><?= sf_admin_h($sub['status'] ?? '') ?><?= !empty($sub['cancel_at_period_end']) ? '<br><small>cancel at period end</small>' : '' ?></td><td><?= sf_admin_h($sub['current_period_end'] ?? '—') ?></td><td><?= sf_admin_h($sub['external_subscription_id'] ?? $sub['provider_subscription_id'] ?? '') ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>

<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Checkouts</span><h2>Checkout sessions</h2></div></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Member</th><th>Plan</th><th>Status</th><th>Total</th><th>Expires</th><th>Created</th></tr></thead><tbody>
    <?php if (!$checkouts): ?><tr><td colspan="6">No checkout sessions found.</td></tr><?php endif; ?>
    <?php foreach ($checkouts as $checkout): ?><tr><td><?= sf_admin_h($checkout['display_name'] ?: $checkout['email']) ?></td><td><?= sf_admin_h($checkout['plan_name'] ?? '') ?></td><td><?= sf_admin_h($checkout['status'] ?? '') ?></td><td><?= sf_billing_money((int)($checkout['amount_cents'] ?? 0), (string)($checkout['currency'] ?? 'USD')) ?></td><td><?= sf_admin_h($checkout['expires_at'] ?? '') ?></td><td><?= sf_admin_h($checkout['created_at'] ?? '') ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>

<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Invoices</span><h2>Latest invoices</h2></div></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Invoice</th><th>Member</th><th>Status</th><th>Total</th><th>Paid</th><th>Created</th></tr></thead><tbody>
    <?php if (!$invoices): ?><tr><td colspan="6">No invoices found.</td></tr><?php endif; ?>
    <?php foreach ($invoices as $invoice): ?><tr><td><?= sf_admin_h($invoice['invoice_number'] ?? '') ?></td><td><?= sf_admin_h($invoice['display_name'] ?: $invoice['email']) ?></td><td><?= sf_admin_h($invoice['status'] ?? '') ?></td><td><?= sf_billing_money((int)($invoice['total_cents'] ?? 0), (string)($invoice['currency'] ?? 'USD')) ?></td><td><?= sf_admin_h($invoice['paid_at'] ?? '—') ?></td><td><?= sf_admin_h($invoice['created_at'] ?? '') ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>

<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Transactions</span><h2>Payment transactions</h2></div></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Member</th><th>Provider</th><th>Type</th><th>Status</th><th>Amount</th><th>Created</th></tr></thead><tbody>
    <?php if (!$transactions): ?><tr><td colspan="6">No transactions found.</td></tr><?php endif; ?>
    <?php foreach ($transactions as $transaction): ?><tr><td><?= sf_admin_h($transaction['display_name'] ?: $transaction['email']) ?></td><td><?= sf_admin_h($transaction['provider'] ?? '') ?></td><td><?= sf_admin_h($transaction['transaction_type'] ?? '') ?></td><td><?= sf_admin_h($transaction['status'] ?? '') ?></td><td><?= sf_billing_money((int)($transaction['amount_cents'] ?? 0), (string)($transaction['currency'] ?? 'USD')) ?></td><td><?= sf_admin_h($transaction['created_at'] ?? '') ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>

<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Webhooks</span><h2>Recent webhook events</h2></div></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Provider</th><th>Event</th><th>Status</th><th>Processed</th><th>Created</th></tr></thead><tbody>
    <?php if (!$webhooks): ?><tr><td colspan="5">No webhook events found.</td></tr><?php endif; ?>
    <?php foreach ($webhooks as $event): ?><tr><td><?= sf_admin_h($event['provider'] ?? '') ?></td><td><?= sf_admin_h($event['event_type'] ?? '') ?></td><td><?= sf_admin_h($event['status'] ?? '') ?></td><td><?= sf_admin_h($event['processed_at'] ?? '—') ?></td><td><?= sf_admin_h($event['created_at'] ?? '') ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>
<?php
sf_admin_shell_end();
require __DIR__ . '/../includes/footer.php';
?>
