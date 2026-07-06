<?php
$pageTitle = 'Membership Checkout';
$pageDescription = 'Complete Stonefellow membership checkout.';
$pageClass = 'membership-page billing-page';
require __DIR__ . '/includes/billing.php';
$user = sf_require_login();
$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$checkout = $token !== '' ? sf_billing_checkout_by_token($token) : null;

if (!$checkout || (int)($checkout['user_id'] ?? 0) !== (int)$user['id']) {
  sf_auth_flash('error', 'Checkout session was not found.');
  sf_redirect(sf_url('subscribe.php'));
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
    sf_auth_flash('error', 'Security check failed. Refresh and try again.');
  } elseif (($_POST['action'] ?? '') === 'complete') {
    $payment = [
      'name' => trim((string)($_POST['billing_name'] ?? '')),
      'email' => trim((string)($_POST['billing_email'] ?? $user['email'] ?? '')),
      'method' => 'sandbox_card',
    ];
    if (sf_billing_complete_checkout($token, $payment)) {
      sf_redirect(sf_url('billing-success.php?token=' . urlencode($token)));
    }
  } elseif (($_POST['action'] ?? '') === 'cancel') {
    sf_billing_cancel_checkout($token);
    sf_redirect(sf_url('billing-cancel.php?token=' . urlencode($token)));
  }
  $checkout = sf_billing_checkout_by_token($token);
}

$expired = !empty($checkout['expires_at']) && strtotime((string)$checkout['expires_at']) < time();
$features = array_filter([
  !empty($checkout['allows_full_music']) ? 'Full soundtrack streaming' : null,
  !empty($checkout['allows_video_streaming']) ? 'Episode/video streaming' : null,
  !empty($checkout['allows_playlists']) ? 'Private playlists' : null,
  !empty($checkout['allows_offline_downloads']) ? 'Offline/download access' : null,
]);
$gatewayStatus = function_exists('sf_payment_gateway_status') ? sf_payment_gateway_status() : ['label'=>'Sandbox','ready'=>true];
require __DIR__ . '/includes/header.php';
?>
<section class="sf-billing-hero">
  <span class="sf-kicker">Secure Membership Checkout</span>
  <h1>Complete your Stonefellow access.</h1>
  <p>Sandbox checkout is live for testing. Replace this step with Stripe Checkout or Elements when production payment keys are ready.</p>
</section>

<section class="sf-billing-layout">
  <article class="sf-billing-card sf-billing-form-card">
    <div class="sf-billing-card-head">
      <span>Checkout</span>
      <strong><?= sf_auth_h(ucfirst((string)$checkout['status'])) ?></strong>
    </div>
    <?php if ($expired): ?>
      <div class="sf-access-gate"><span>Expired</span><h2>This checkout session expired.</h2><p>Start a new membership checkout to continue.</p><div class="sf-episode-action-row"><a class="sf-primary-action" href="<?= sf_url('subscribe.php') ?>">Choose Plan</a></div></div>
    <?php elseif (($checkout['status'] ?? '') === 'completed'): ?>
      <div class="sf-access-gate"><span>Complete</span><h2>Your membership is active.</h2><p>You can now watch subscriber video and stream full songs.</p><div class="sf-episode-action-row"><a class="sf-primary-action" href="<?= sf_url('member.php') ?>">Open Member Dashboard</a></div></div>
    <?php else: ?>
      <form method="post" class="sf-billing-form">
        <?= sf_csrf_field() ?>
        <input type="hidden" name="token" value="<?= sf_auth_h($token) ?>">
        <label>Billing Name <input type="text" name="billing_name" value="<?= sf_auth_h($user['display_name'] ?? '') ?>" required></label>
        <label>Billing Email <input type="email" name="billing_email" value="<?= sf_auth_h($user['email'] ?? '') ?>" required></label>
        <fieldset class="sf-billing-fieldset">
          <legend>Payment Method</legend>
          <div class="sf-payment-sandbox-box">
            <strong><?= sf_auth_h($gatewayStatus['label'] ?? 'Sandbox') ?> Checkout</strong>
            <p><?= !empty($gatewayStatus['ready']) ? 'Gateway adapter is ready. Sandbox mode records a paid invoice, payment transaction, active subscription, and content access grants.' : 'Provider credentials are not complete yet, so sandbox completion remains active for testing.' ?></p>
          </div>
        </fieldset>
        <div class="sf-billing-actions">
          <button class="sf-primary-action" type="submit" name="action" value="complete">Pay <?= sf_billing_money((int)$checkout['amount_cents'], (string)$checkout['currency']) ?></button>
          <button class="sf-secondary-action" type="submit" name="action" value="cancel" formnovalidate>Cancel</button>
        </div>
      </form>
    <?php endif; ?>
  </article>

  <aside class="sf-billing-card sf-billing-summary-card">
    <span class="sf-panel-eyebrow">Plan Summary</span>
    <h2><?= sf_auth_h($checkout['plan_name'] ?? 'Membership') ?></h2>
    <div class="sf-billing-price"><?= sf_billing_money((int)$checkout['amount_cents'], (string)$checkout['currency']) ?><span>/ <?= sf_auth_h($checkout['billing_interval'] ?? 'month') ?></span></div>
    <?php if (!empty($checkout['description'])): ?><p><?= sf_auth_h($checkout['description']) ?></p><?php endif; ?>
    <ul class="sf-billing-feature-list">
      <?php foreach ($features as $feature): ?><li><?= sf_auth_h($feature) ?></li><?php endforeach; ?>
    </ul>
    <div class="sf-billing-mini-note">Checkout expires: <?= sf_auth_h($checkout['expires_at'] ?? 'Soon') ?></div>
  </aside>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
