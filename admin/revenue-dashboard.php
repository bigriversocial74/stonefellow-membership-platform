<?php
$pageTitle = 'Launch Revenue Dashboard';
$pageDescription = 'Stonefellow launch revenue dashboard for MRR, ARR, subscription revenue, merch revenue, checkout conversion, and churn risk.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/revenue_dashboard.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) { sf_admin_flash('error','Security check failed.'); sf_admin_redirect(); }
  sf_admin_flash(sf_rev_save_snapshot() ? 'success' : 'warning', sf_rev_table_exists('launch_revenue_snapshots') ? 'Revenue snapshot saved.' : 'Snapshot table not installed. Run migration 015.');
  sf_admin_redirect();
}
require __DIR__ . '/../includes/header.php';
$summary = sf_rev_summary(30);
$plans = sf_rev_plan_breakdown();
$funnel = sf_rev_checkout_funnel(30);
$transactions = sf_rev_recent_transactions(30);
$snapshots = sf_rev_recent_snapshots(14);
sf_admin_shell_start('Launch Revenue', 'Revenue dashboard v1', 'Track subscription revenue, merch orders, checkout conversion, MRR, ARR, paid members, grace/churn risk, and engagement conversion signals.', 'revenue-dashboard');
?>
<section class="sf-admin-card-grid">
  <div class="sf-admin-action-card"><span>MRR</span><strong><?= sf_rev_money((int)$summary['mrr_cents']) ?></strong><small>Active monthly recurring revenue.</small></div>
  <div class="sf-admin-action-card"><span>ARR</span><strong><?= sf_rev_money((int)$summary['arr_cents']) ?></strong><small>Annualized run rate.</small></div>
  <div class="sf-admin-action-card"><span>30-day Revenue</span><strong><?= sf_rev_money((int)$summary['total_revenue_cents']) ?></strong><small><?= sf_rev_money((int)$summary['subscription_revenue_cents']) ?> subscriptions · <?= sf_rev_money((int)$summary['merch_revenue_cents']) ?> merch.</small></div>
  <div class="sf-admin-action-card"><span>Paid Members</span><strong><?= (int)$summary['paid_members'] ?></strong><small><?= (int)$summary['active_subscriptions'] ?> active/trialing subscriptions.</small></div>
  <div class="sf-admin-action-card"><span>Checkout CVR</span><strong><?= number_format((float)$summary['checkout_conversion_rate'],2) ?>%</strong><small><?= (int)$summary['checkout_completed'] ?> / <?= (int)$summary['checkout_starts'] ?> completed.</small></div>
  <div class="sf-admin-action-card"><span>Churn Risk</span><strong><?= (int)$summary['grace_or_churn_risk'] ?></strong><small>Past due, canceling, or ending soon.</small></div>
</section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Snapshot</span><h2>Save launch snapshot</h2></div><a href="<?= sf_url('api/revenue-summary.php') ?>">JSON API</a></div><form class="sf-admin-form" method="post"><?= sf_csrf_field() ?><p class="sf-admin-copy">Save today’s launch snapshot for founder/admin reporting. Snapshots include revenue, checkout conversion, subscribers, churn risk, comments, reactions, and feed saves.</p><div class="sf-admin-form-actions"><button type="submit">Save Snapshot</button><a href="<?= sf_url('admin/tier-manager.php') ?>">Tier Manager</a></div></form></section>
<section class="sf-admin-two-col sf-admin-two-col-wide">
  <article class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Plan Revenue</span><h2>Subscription mix</h2></div><a href="<?= sf_url('subscribe.php') ?>">Pricing</a></div><div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Plan</th><th>Price</th><th>Active Subs</th><th>Members</th><th>MRR</th></tr></thead><tbody><?php foreach($plans as $plan): ?><tr><td><strong><?= sf_admin_h($plan['name'] ?? '') ?></strong><small><?= sf_admin_h($plan['slug'] ?? '') ?></small></td><td><?= sf_rev_money((int)($plan['price_cents'] ?? 0)) ?> / <?= sf_admin_h($plan['billing_interval'] ?? 'month') ?></td><td><?= (int)($plan['active_subscriptions'] ?? 0) ?></td><td><?= (int)($plan['members'] ?? 0) ?></td><td><?= sf_rev_money((int)round((float)($plan['mrr_cents'] ?? 0))) ?></td></tr><?php endforeach; ?><?php if(!$plans): ?><tr><td colspan="5">No plan revenue data yet.</td></tr><?php endif; ?></tbody></table></div></article>
  <aside class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Checkout Funnel</span><h2>Conversion path</h2></div></div><div class="sf-admin-roadmap"><?php foreach($funnel as $row): ?><div><span><?= (int)$row['count'] ?></span><strong><?= sf_admin_h($row['label']) ?></strong><p><?= number_format((float)$row['rate'],2) ?>% relative rate.</p></div><?php endforeach; ?></div></aside>
</section>
<section class="sf-admin-two-col sf-admin-two-col-wide">
  <article class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Recent Payments</span><h2>Transactions</h2></div><a href="<?= sf_url('admin/payment-gateways.php') ?>">Gateways</a></div><div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Member</th><th>Type</th><th>Status</th><th>Amount</th><th>Created</th></tr></thead><tbody><?php foreach($transactions as $tx): ?><tr><td><strong><?= sf_admin_h($tx['display_name'] ?? $tx['email'] ?? 'Member') ?></strong><small><?= sf_admin_h($tx['provider'] ?? 'sandbox') ?></small></td><td><?= sf_admin_h($tx['transaction_type'] ?? '') ?></td><td><?= sf_admin_h($tx['status'] ?? '') ?></td><td><?= sf_rev_money((int)($tx['amount_cents'] ?? 0)) ?></td><td><?= sf_admin_h($tx['created_at'] ?? '') ?></td></tr><?php endforeach; ?><?php if(!$transactions): ?><tr><td colspan="5">No paid transactions yet.</td></tr><?php endif; ?></tbody></table></div></article>
  <aside class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Engagement Conversion</span><h2>Demand signals</h2></div><a href="<?= sf_url('admin/engagement-analytics.php') ?>">Engagement</a></div><div class="sf-admin-roadmap"><div><span><?= (int)$summary['comments'] ?></span><strong>Comments</strong><p>Community activity in the last 30 days.</p></div><div><span><?= (int)$summary['reactions'] ?></span><strong>Reactions</strong><p>Fan reaction activity.</p></div><div><span><?= (int)$summary['feed_saves'] ?></span><strong>Feed Saves</strong><p>Personalized feed save intent.</p></div><div><span><?= (int)$summary['orders'] ?></span><strong>Merch Orders</strong><p>Paid/authorized merch demand.</p></div></div></aside>
</section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Snapshots</span><h2>Recent saved launch snapshots</h2></div></div><div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Date</th><th>MRR</th><th>ARR</th><th>Total Revenue</th><th>Paid Members</th><th>Checkout CVR</th></tr></thead><tbody><?php foreach($snapshots as $snap): ?><tr><td><?= sf_admin_h($snap['snapshot_date'] ?? '') ?></td><td><?= sf_rev_money((int)($snap['mrr_cents'] ?? 0)) ?></td><td><?= sf_rev_money((int)($snap['arr_cents'] ?? 0)) ?></td><td><?= sf_rev_money((int)($snap['total_revenue_cents'] ?? 0)) ?></td><td><?= (int)($snap['paid_members'] ?? 0) ?></td><td><?= number_format((float)($snap['checkout_conversion_rate'] ?? 0),2) ?>%</td></tr><?php endforeach; ?><?php if(!$snapshots): ?><tr><td colspan="6">No saved snapshots yet.</td></tr><?php endif; ?></tbody></table></div></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
