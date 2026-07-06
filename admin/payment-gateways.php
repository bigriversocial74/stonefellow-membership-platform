<?php
$pageTitle = 'Payment Gateways';
$pageDescription = 'Configure payment provider readiness and adapter status.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/payment_gateway.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
    sf_admin_flash('error', 'Security check failed.');
    sf_admin_redirect();
  }
  if (!sf_settings_ready()) {
    sf_admin_flash('warning', 'Run migration 007 before saving payment provider settings.');
    sf_admin_redirect();
  }
  $provider = in_array($_POST['payment_provider'] ?? 'sandbox', ['sandbox','stripe','paypal'], true) ? $_POST['payment_provider'] : 'sandbox';
  sf_update_setting('payment_provider', $provider, 'payments', true);
  sf_update_setting('stripe_publishable_key', trim((string)($_POST['stripe_publishable_key'] ?? '')), 'payments', true);
  sf_update_setting('paypal_client_id', trim((string)($_POST['paypal_client_id'] ?? '')), 'payments', true);
  sf_admin_flash('success', 'Payment gateway settings saved.');
  sf_admin_redirect();
}

require __DIR__ . '/../includes/header.php';
$status = sf_payment_gateway_status();
sf_admin_shell_start('Payment Gateways', 'Gateway adapter v1', 'Use sandbox now while keeping the Stripe/PayPal adapter boundary ready for production checkout and webhook replacement.', 'payments');
?>
<section class="sf-admin-card-grid">
  <div class="sf-admin-action-card"><span>Active Provider</span><strong><?= sf_admin_h($status['label']) ?></strong><small><?= !empty($status['ready']) ? 'Ready' : 'Needs credentials' ?></small></div>
  <div class="sf-admin-action-card"><span>Mode</span><strong><?= sf_admin_h($status['mode']) ?></strong><small>Controlled by environment and settings.</small></div>
  <a class="sf-admin-action-card" href="<?= sf_url('api/payment-webhook.php') ?>"><span>Webhook</span><strong>Endpoint Ready</strong><small>Provider event receiver shell.</small></a>
</section>
<section class="sf-admin-two-col sf-admin-two-col-wide">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Settings</span><h2>Provider selection</h2></div></div>
    <form class="sf-admin-form" method="post">
      <?= sf_csrf_field() ?>
      <label>Provider<?= sf_admin_select('payment_provider', ['sandbox'=>'Sandbox','stripe'=>'Stripe','paypal'=>'PayPal'], sf_payment_provider()) ?></label>
      <label>Stripe Publishable Key<input name="stripe_publishable_key" value="<?= sf_admin_h(sf_get_setting('stripe_publishable_key', '')) ?>"<?= sf_admin_form_disabled_attr() ?>></label>
      <label>PayPal Client ID<input name="paypal_client_id" value="<?= sf_admin_h(sf_get_setting('paypal_client_id', '')) ?>"<?= sf_admin_form_disabled_attr() ?>></label>
      <div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>>Save Gateway Settings</button></div>
    </form>
  </article>
  <aside class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Production Credentials</span><h2>Server-side environment</h2></div></div>
    <p class="sf-admin-copy">Keep secret keys in environment variables, not database settings: <code>SF_STRIPE_SECRET_KEY</code>, <code>SF_STRIPE_WEBHOOK_SECRET</code>, <code>SF_PAYPAL_SECRET</code>, and <code>SF_PAYPAL_WEBHOOK_ID</code>.</p>
    <div class="sf-admin-roadmap"><div><span>✓</span><strong>Sandbox</strong><p>Local membership and merch payments are already operational.</p></div><div><span>→</span><strong>Stripe</strong><p>Adapter boundary is ready for Checkout Session creation.</p></div><div><span>→</span><strong>PayPal</strong><p>Adapter boundary is ready for Orders API creation.</p></div></div>
  </aside>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
