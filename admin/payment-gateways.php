<?php
$pageTitle = 'Payment Gateways';
$pageDescription = 'Configure payment provider readiness and production gateway status.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/payment_gateway.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) { sf_admin_flash('error', 'Security check failed.'); sf_admin_redirect(); }
  if (!sf_settings_ready()) { sf_admin_flash('warning', 'Run migration 007 before saving payment provider settings.'); sf_admin_redirect(); }
  $provider = in_array($_POST['payment_provider'] ?? 'sandbox', ['sandbox','stripe','paypal'], true) ? $_POST['payment_provider'] : 'sandbox';
  $mode = in_array($_POST['payment_mode'] ?? 'sandbox', ['sandbox','test','live'], true) ? $_POST['payment_mode'] : 'sandbox';
  sf_update_setting('payment_provider', $provider, 'payments', true);
  sf_update_setting('payment_mode', $mode, 'payments', true);
  sf_update_setting('stripe_publishable_key', trim((string)($_POST['stripe_publishable_key'] ?? '')), 'payments', true);
  sf_update_setting('paypal_client_id', trim((string)($_POST['paypal_client_id'] ?? '')), 'payments', true);
  sf_admin_flash('success', 'Gateway settings saved. Secret keys still belong in environment variables.');
  sf_admin_redirect();
}

require __DIR__ . '/../includes/header.php';
$status = sf_payment_gateway_status();
sf_admin_shell_start('Payment Gateways', 'Production pass v1', 'Stripe Checkout session creation, webhook verification, lifecycle event handling, and production readiness checks.', 'payments');
?>
<section class="sf-admin-card-grid">
  <div class="sf-admin-action-card"><span>Active Provider</span><strong><?= sf_admin_h($status['label']) ?></strong><small><?= !empty($status['ready']) ? 'Ready' : 'Needs server credentials' ?></small></div>
  <div class="sf-admin-action-card"><span>Mode</span><strong><?= sf_admin_h($status['mode']) ?></strong><small>Sandbox, test, or live.</small></div>
  <a class="sf-admin-action-card" href="<?= sf_url('api/payment-webhook.php?provider=' . urlencode($status['provider'])) ?>"><span>Webhook</span><strong>Receiver</strong><small>Verified provider events update billing lifecycle.</small></a>
</section>
<section class="sf-admin-two-col sf-admin-two-col-wide">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Settings</span><h2>Provider selection</h2></div></div>
    <form class="sf-admin-form" method="post">
      <?= sf_csrf_field() ?>
      <label>Provider<?= sf_admin_select('payment_provider', ['sandbox'=>'Sandbox','stripe'=>'Stripe','paypal'=>'PayPal'], sf_payment_provider()) ?></label>
      <label>Mode<?= sf_admin_select('payment_mode', ['sandbox'=>'Sandbox','test'=>'Test','live'=>'Live'], sf_payment_mode()) ?></label>
      <label>Stripe Publishable Key<input name="stripe_publishable_key" value="<?= sf_admin_h(sf_get_setting('stripe_publishable_key', '')) ?>"<?= sf_admin_form_disabled_attr() ?>></label>
      <label>PayPal Client ID<input name="paypal_client_id" value="<?= sf_admin_h(sf_get_setting('paypal_client_id', '')) ?>"<?= sf_admin_form_disabled_attr() ?>></label>
      <div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>>Save Gateway Settings</button></div>
    </form>
  </article>
  <aside class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Production Credentials</span><h2>Server-side environment</h2></div></div>
    <div class="sf-admin-roadmap">
      <div><span><?= getenv('SF_STRIPE_SECRET_KEY') ? '✓' : '!' ?></span><strong>Stripe Secret</strong><p><?= sf_admin_h($status['stripe_secret']) ?></p></div>
      <div><span><?= getenv('SF_STRIPE_WEBHOOK_SECRET') ? '✓' : '!' ?></span><strong>Stripe Webhook</strong><p><?= sf_admin_h($status['webhook_secret']) ?></p></div>
      <div><span>✓</span><strong>Lifecycle Events</strong><p>Checkout complete, subscription canceled, and failed invoice events are processed.</p></div>
    </div>
    <p class="sf-admin-copy">Keep secrets in environment variables: <code>SF_STRIPE_SECRET_KEY</code>, <code>SF_STRIPE_WEBHOOK_SECRET</code>, <code>SF_PAYPAL_SECRET</code>, and <code>SF_PAYPAL_WEBHOOK_ID</code>.</p>
  </aside>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
