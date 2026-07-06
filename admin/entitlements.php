<?php
$pageTitle = 'Entitlements';
$pageDescription = 'Subscription enforcement, grace periods, direct grants, and member access snapshots.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require __DIR__ . '/../includes/header.php';

$members = sf_admin_table_exists('users') ? sf_admin_fetch_all("SELECT id, email, display_name, role, status, created_at FROM users ORDER BY created_at DESC, id DESC LIMIT 100") : [];
$plans = sf_admin_table_exists('subscription_plans') ? sf_admin_fetch_all("SELECT * FROM subscription_plans ORDER BY is_featured DESC, price_cents ASC, id ASC") : [];
$subscriptions = sf_admin_table_exists('user_subscriptions') ? sf_admin_fetch_all("SELECT us.*, u.email, u.display_name, sp.name AS plan_name, sp.slug AS plan_slug FROM user_subscriptions us LEFT JOIN users u ON u.id=us.user_id LEFT JOIN subscription_plans sp ON sp.id=us.plan_id ORDER BY us.updated_at DESC, us.id DESC LIMIT 100") : [];
$grants = sf_admin_table_exists('content_access_grants') ? sf_admin_fetch_all("SELECT cag.*, u.email, u.display_name FROM content_access_grants cag LEFT JOIN users u ON u.id=cag.user_id ORDER BY cag.id DESC LIMIT 100") : [];

sf_admin_shell_start('Entitlements', 'Subscription Enforcement v2', 'Review access levels, grace-period behavior, direct grants, and active subscription enforcement across the streaming platform.', 'entitlements');
?>
<section class="sf-admin-card-grid">
  <a class="sf-admin-action-card" href="<?= sf_url('api/entitlement-check.php?required=subscriber') ?>"><span>API</span><strong>Entitlement Check</strong><small>JSON access snapshot for the current user.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/media-access.php') ?>"><span>Grants</span><strong>Access Rules</strong><small>Manage direct content grants.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/billing.php') ?>"><span>Billing</span><strong>Subscriptions</strong><small>Checkout, invoices, and payment status.</small></a>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Policy</span><h2>Runtime enforcement</h2></div></div>
  <div class="sf-admin-roadmap">
    <div><span>✓</span><strong>Expired lockout</strong><p>Active/trialing subscriptions must be inside the current period. Past-due access only remains through the configured grace window.</p></div>
    <div><span>✓</span><strong>Grace period</strong><p><code>SF_SUBSCRIPTION_GRACE_DAYS</code> controls the grace window. Default is <?= (int)sf_entitlement_grace_days() ?> days.</p></div>
    <div><span>✓</span><strong>Direct grants</strong><p>Video, song, episode, album, playlist, and site feature grants can override plan status while valid.</p></div>
    <div><span>✓</span><strong>Tier ranking</strong><p>Public → Free Account → Subscriber → Premium → Founding Fan → Admin.</p></div>
  </div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Subscriptions</span><h2><?= count($subscriptions) ?> recent</h2></div></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Member</th><th>Plan</th><th>Status</th><th>Period End</th><th>Access</th></tr></thead><tbody>
    <?php foreach ($subscriptions as $row): $snap = sf_entitlement_snapshot((int)$row['user_id']); ?>
      <tr><td><strong><?= sf_admin_h($row['display_name'] ?: $row['email']) ?></strong><small><?= sf_admin_h($row['email']) ?></small></td><td><?= sf_admin_h($row['plan_name'] ?? $row['plan_slug'] ?? '') ?></td><td><?= sf_admin_status_badge((string)($row['status'] ?? '')) ?></td><td><?= sf_admin_h($row['current_period_end'] ?? 'open') ?></td><td><?= sf_admin_h($snap['access_label']) ?><?= !empty($snap['in_grace_period']) ? ' · grace' : '' ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$subscriptions): ?><tr><td colspan="5">No subscriptions found yet.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Direct Grants</span><h2><?= count($grants) ?> recent</h2></div><a href="<?= sf_url('admin/media-access.php') ?>">Manage grants</a></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Member</th><th>Type</th><th>Content</th><th>Level</th><th>Window</th></tr></thead><tbody>
    <?php foreach ($grants as $grant): ?>
      <tr><td><strong><?= sf_admin_h($grant['display_name'] ?: $grant['email']) ?></strong><small><?= sf_admin_h($grant['email']) ?></small></td><td><?= sf_admin_h($grant['content_type'] ?? '') ?></td><td><?= sf_admin_h($grant['content_id'] ?? 'All') ?></td><td><?= sf_admin_h(sf_access_label((string)($grant['access_level'] ?? 'subscriber'))) ?></td><td><?= sf_admin_h(($grant['starts_at'] ?? 'now') . ' → ' . ($grant['expires_at'] ?? 'open')) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$grants): ?><tr><td colspan="5">No direct grants found yet.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
