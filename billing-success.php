<?php
$pageTitle = 'Membership Active';
$pageDescription = 'Your Stonefellow membership is active.';
$pageClass = 'membership-page billing-page';
require __DIR__ . '/includes/billing.php';
$user = sf_require_login();
$token = trim((string)($_GET['token'] ?? ''));
$checkout = $token ? sf_billing_checkout_by_token($token) : null;
$subscription = sf_billing_active_subscription((int)$user['id']);
require __DIR__ . '/includes/header.php';
?>
<section class="sf-billing-hero sf-billing-success">
  <span class="sf-kicker">Membership Active</span>
  <h1>You are in.</h1>
  <p>Your account now has member access for Stonefellow episodes, music, playlists, and exclusive content.</p>
  <div class="sf-episode-action-row">
    <a class="sf-primary-action" href="<?= sf_url('member.php') ?>">Open Member Dashboard</a>
    <a class="sf-secondary-action" href="<?= sf_url('watch.php') ?>">Watch Now</a>
    <a class="sf-secondary-action" href="<?= sf_url('player.php') ?>">Open Player</a>
  </div>
</section>
<section class="sf-billing-layout sf-billing-layout-tight">
  <article class="sf-billing-card">
    <span class="sf-panel-eyebrow">Current Plan</span>
    <h2><?= sf_auth_h($subscription['plan_name'] ?? $checkout['plan_name'] ?? 'Stonefellow Membership') ?></h2>
    <p>Status: <strong><?= sf_auth_h($subscription['status'] ?? 'active') ?></strong></p>
    <p>Renews/ends: <strong><?= sf_auth_h($subscription['current_period_end'] ?? 'Not set') ?></strong></p>
  </article>
  <article class="sf-billing-card">
    <span class="sf-panel-eyebrow">Billing</span>
    <h2>Manage subscription</h2>
    <p>View invoices, transactions, and cancellation settings from your billing account page.</p>
    <a class="sf-primary-action" href="<?= sf_url('account-billing.php') ?>">Account Billing</a>
  </article>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
