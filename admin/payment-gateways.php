<?php

declare(strict_types=1);

$pageTitle = 'Payment Providers';
$pageDescription = 'Connect merchant payment providers and manage Stripe Connect readiness.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/live_commerce.php';

$merchant = sf_commerce_default_merchant();

if (!empty($_GET['stripe_refresh'])) {
    $state = trim((string)($_GET['state'] ?? ''));
    $session = sf_commerce_onboarding_session($state);
    if (!$session || strtotime((string)$session['expires_at']) < time() - 3600) {
        sf_admin_flash('error', 'Stripe onboarding session is invalid or expired.');
        sf_admin_redirect(sf_url('admin/payment-gateways.php'));
    }
    $account = sf_commerce_payment_account_by_id((int)$session['payment_account_id']);
    $link = $account ? sf_commerce_create_onboarding_link($account) : ['ok' => false, 'error' => 'Payment account was not found.'];
    if (!empty($link['ok'])) {
        header('Location: ' . $link['url'], true, 303);
        exit;
    }
    sf_admin_flash('error', (string)($link['error'] ?? 'Stripe onboarding could not be refreshed.'));
    sf_admin_redirect(sf_url('admin/payment-gateways.php'));
}

if (!empty($_GET['stripe_return'])) {
    $state = trim((string)($_GET['state'] ?? ''));
    $session = sf_commerce_onboarding_session($state);
    if ($session) {
        sf_commerce_mark_onboarding_returned((int)$session['id']);
        $account = sf_commerce_payment_account_by_id((int)$session['payment_account_id']);
        $sync = $account ? sf_commerce_sync_stripe_account($account) : ['ok' => false, 'error' => 'Payment account was not found.'];
        sf_admin_flash(!empty($sync['ok']) ? 'success' : 'warning', !empty($sync['ok']) ? 'Stripe account status synchronized.' : (string)($sync['error'] ?? 'Stripe onboarding status could not be confirmed.'));
    } else {
        sf_admin_flash('warning', 'Stripe returned without a valid onboarding session.');
    }
    sf_admin_redirect(sf_url('admin/payment-gateways.php'));
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
        sf_admin_flash('error', 'Security check failed.');
        sf_admin_redirect(sf_url('admin/payment-gateways.php'));
    }
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'save_settings') {
        $mode = in_array($_POST['payment_mode'] ?? 'test', ['test','live'], true) ? (string)$_POST['payment_mode'] : 'test';
        sf_update_setting('payment_provider', 'stripe', 'payments', true);
        sf_update_setting('payment_mode', $mode, 'payments', true);
        if ($merchant) {
            $feeBps = max(0, min(10000, (int)($_POST['platform_fee_bps'] ?? 0)));
            sf_db()?->prepare('UPDATE commerce_merchants SET platform_fee_bps=?,updated_at=NOW() WHERE id=?')->execute([$feeBps, (int)$merchant['id']]);
        }
        sf_admin_flash('success', 'Stripe-first provider settings saved. Secret keys remain in environment variables.');
        sf_admin_redirect(sf_url('admin/payment-gateways.php'));
    }
    if ($action === 'connect_stripe') {
        if (!$merchant) {
            sf_admin_flash('error', 'Commerce migration 022 must be imported before onboarding Stripe.');
            sf_admin_redirect(sf_url('admin/payment-gateways.php'));
        }
        $created = sf_commerce_create_stripe_account($merchant);
        if (empty($created['ok']) || empty($created['account'])) {
            sf_admin_flash('error', (string)($created['error'] ?? 'Stripe account could not be created.'));
            sf_admin_redirect(sf_url('admin/payment-gateways.php'));
        }
        $link = sf_commerce_create_onboarding_link($created['account']);
        if (empty($link['ok'])) {
            sf_admin_flash('error', (string)($link['error'] ?? 'Stripe onboarding could not be started.'));
            sf_admin_redirect(sf_url('admin/payment-gateways.php'));
        }
        header('Location: ' . $link['url'], true, 303);
        exit;
    }
    if ($action === 'continue_onboarding') {
        $account = sf_commerce_payment_account_by_id((int)($_POST['payment_account_id'] ?? 0));
        $link = $account ? sf_commerce_create_onboarding_link($account) : ['ok' => false, 'error' => 'Payment account was not found.'];
        if (!empty($link['ok'])) {
            header('Location: ' . $link['url'], true, 303);
            exit;
        }
        sf_admin_flash('error', (string)($link['error'] ?? 'Stripe onboarding could not be continued.'));
        sf_admin_redirect(sf_url('admin/payment-gateways.php'));
    }
    if ($action === 'sync_stripe') {
        $account = sf_commerce_payment_account_by_id((int)($_POST['payment_account_id'] ?? 0));
        $sync = $account ? sf_commerce_sync_stripe_account($account) : ['ok' => false, 'error' => 'Payment account was not found.'];
        sf_admin_flash(!empty($sync['ok']) ? 'success' : 'error', !empty($sync['ok']) ? 'Stripe account synchronized.' : (string)($sync['error'] ?? 'Stripe account sync failed.'));
        sf_admin_redirect(sf_url('admin/payment-gateways.php'));
    }
}

$status = sf_commerce_provider_summary();
$merchant = $status['merchant'];
$account = $status['account'];
$requirements = $account ? json_decode((string)($account['requirements_json'] ?? '{}'), true) : [];
$currentDue = is_array($requirements) ? ($requirements['currently_due'] ?? []) : [];
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Payment Providers', 'Stripe Connect onboarding', 'Stonefellow supports a provider-adapter architecture. Stripe is the first active payment provider and merchants connect through Stripe-hosted onboarding.', 'payments');
?>
<section class="sf-admin-stats-grid">
  <div><span>Active Provider</span><strong>Stripe</strong><small>Additional provider adapters remain disabled until fully implemented.</small></div>
  <div><span>Mode</span><strong><?= sf_admin_h($status['mode']) ?></strong><small>Test and live accounts are stored separately.</small></div>
  <div><span>Platform Credentials</span><strong><?= !empty($status['platform_credentials_ready']) ? 'Ready' : 'Missing' ?></strong><small>Secret key and webhook secret stay server-side.</small></div>
  <div><span>Checkout</span><strong><?= !empty($status['checkout_ready']) ? 'Enabled' : 'Blocked' ?></strong><small>Charges and payouts must both be enabled.</small></div>
</section>

<section class="sf-admin-two-col sf-admin-two-col-wide">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Merchant</span><h2><?= sf_admin_h($merchant['display_name'] ?? 'Commerce migration required') ?></h2></div></div>
    <?php if (!$merchant): ?>
      <p>Import <code>database/migrations/022_live_commerce_stripe_connect.sql</code> to create the merchant payment-account foundation.</p>
    <?php else: ?>
      <form class="sf-admin-form" method="post">
        <?= sf_csrf_field() ?><input type="hidden" name="action" value="save_settings">
        <label>Payment Mode<?= sf_admin_select('payment_mode', ['test'=>'Test','live'=>'Live'], sf_commerce_mode()) ?></label>
        <label>Platform Fee (basis points)<input type="number" name="platform_fee_bps" min="0" max="10000" value="<?= (int)($merchant['platform_fee_bps'] ?? 0) ?>"><small>100 basis points = 1%. Use 0 when Stonefellow keeps no platform fee.</small></label>
        <div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>>Save Provider Settings</button></div>
      </form>
    <?php endif; ?>
  </article>

  <aside class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Stripe Connect</span><h2>Merchant onboarding</h2></div></div>
    <?php if (!$account): ?>
      <p>No Stripe connected account exists for the current mode.</p>
      <form method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="connect_stripe"><button type="submit"<?= (!$merchant || empty($status['platform_credentials_ready'])) ? ' disabled' : '' ?>>Connect with Stripe</button></form>
    <?php else: ?>
      <div class="sf-admin-roadmap">
        <div><span><?= !empty($account['details_submitted']) ? '✓' : '!' ?></span><strong>Business Details</strong><p><?= !empty($account['details_submitted']) ? 'Submitted' : 'Incomplete' ?></p></div>
        <div><span><?= !empty($account['charges_enabled']) ? '✓' : '!' ?></span><strong>Charges</strong><p><?= !empty($account['charges_enabled']) ? 'Enabled' : 'Blocked' ?></p></div>
        <div><span><?= !empty($account['payouts_enabled']) ? '✓' : '!' ?></span><strong>Payouts</strong><p><?= !empty($account['payouts_enabled']) ? 'Enabled' : 'Blocked' ?></p></div>
      </div>
      <p><strong>Status:</strong> <?= sf_admin_h(ucwords(str_replace('_', ' ', (string)$account['onboarding_status']))) ?><br><strong>Connected Account:</strong> <?= sf_admin_h($account['provider_account_id']) ?><br><strong>Last Sync:</strong> <?= sf_admin_h($account['last_synced_at'] ?? 'Never') ?></p>
      <?php if ($currentDue): ?><p><strong>Currently due:</strong> <?= sf_admin_h(implode(', ', array_slice($currentDue, 0, 12))) ?></p><?php endif; ?>
      <div class="sf-admin-form-actions">
        <form method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="continue_onboarding"><input type="hidden" name="payment_account_id" value="<?= (int)$account['id'] ?>"><button type="submit">Continue Stripe Onboarding</button></form>
        <form method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="sync_stripe"><input type="hidden" name="payment_account_id" value="<?= (int)$account['id'] ?>"><button type="submit">Sync Status</button></form>
      </div>
    <?php endif; ?>
  </aside>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Provider Registry</span><h2>Available payment adapters</h2></div><a href="<?= sf_url('admin/payment-reconciliation.php') ?>">Open Reconciliation</a></div>
  <div class="sf-admin-card-grid">
    <?php foreach ($status['providers'] as $provider): ?><div class="sf-admin-action-card"><span><?= !empty($provider['implemented']) ? 'Active Adapter' : 'Adapter Reserved' ?></span><strong><?= sf_admin_h($provider['label']) ?></strong><small><?= !empty($provider['implemented']) ? 'Stripe Connect onboarding and Stripe Checkout are implemented.' : 'Visible in the provider registry but fail-closed until a complete signed adapter is added.' ?></small></div><?php endforeach; ?>
  </div>
  <p class="sf-admin-copy">Webhook endpoint: <code><?= sf_admin_h(sf_commerce_absolute_url('api/payment-webhook.php?provider=stripe')) ?></code></p>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
