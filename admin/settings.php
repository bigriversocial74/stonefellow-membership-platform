<?php
$pageTitle = 'Site Settings';
$pageDescription = 'Manage Stonefellow site settings, runtime toggles, and public configuration.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/settings.php';

$groups = [
  'site' => ['site_name','site_tagline','base_url','support_email','admin_email'],
  'runtime' => ['maintenance_mode','member_signup_enabled','checkout_enabled','uploads_public_base'],
  'payments' => ['payment_provider','stripe_publishable_key','paypal_client_id'],
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
    sf_admin_flash('error', 'Security check failed. Refresh and try again.');
    sf_admin_redirect();
  }
  if (!sf_settings_ready()) {
    sf_admin_flash('warning', 'Run migration 007 before saving settings. Static defaults are still being used.');
    sf_admin_redirect();
  }
  foreach ($groups as $group => $keys) {
    foreach ($keys as $key) {
      $value = trim((string)($_POST[$key] ?? ''));
      if (in_array($key, ['maintenance_mode','member_signup_enabled','checkout_enabled'], true)) {
        $value = isset($_POST[$key]) ? '1' : '0';
      }
      $isPublic = in_array($key, ['site_name','site_tagline','base_url','support_email','uploads_public_base','member_signup_enabled','checkout_enabled','payment_provider','stripe_publishable_key','paypal_client_id'], true);
      sf_update_setting($key, $value, $group, $isPublic);
    }
  }
  sf_admin_audit('update_settings', 'site_settings', null, null, $_POST);
  sf_admin_flash('success', 'Settings saved.');
  sf_admin_redirect();
}

require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Site Settings', 'Installer + runtime settings', 'Manage public site identity, operational toggles, upload base paths, and payment provider settings.', 'settings');
?>
<section class="sf-admin-two-col sf-admin-two-col-wide">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Configuration</span><h2>Runtime settings</h2></div></div>
    <form class="sf-admin-form" method="post">
      <?= sf_csrf_field() ?>
      <label>Site Name<input name="site_name" value="<?= sf_admin_h(sf_get_setting('site_name', 'Stonefellow')) ?>"<?= sf_admin_form_disabled_attr() ?>></label>
      <label>Tagline<textarea name="site_tagline" rows="2"<?= sf_admin_form_disabled_attr() ?>><?= sf_admin_h(sf_get_setting('site_tagline', '')) ?></textarea></label>
      <div class="sf-admin-form-grid"><label>Base URL<input name="base_url" value="<?= sf_admin_h(sf_get_setting('base_url', '')) ?>" placeholder="leave blank for relative links"<?= sf_admin_form_disabled_attr() ?>></label><label>Uploads Public Base<input name="uploads_public_base" value="<?= sf_admin_h(sf_get_setting('uploads_public_base', 'assets/')) ?>"<?= sf_admin_form_disabled_attr() ?>></label></div>
      <div class="sf-admin-form-grid"><label>Support Email<input type="email" name="support_email" value="<?= sf_admin_h(sf_get_setting('support_email', 'support@stonefellow.tv')) ?>"<?= sf_admin_form_disabled_attr() ?>></label><label>Admin Alert Email<input type="email" name="admin_email" value="<?= sf_admin_h(sf_get_setting('admin_email', 'support@stonefellow.tv')) ?>"<?= sf_admin_form_disabled_attr() ?>></label></div>
      <div class="sf-admin-form-grid"><label>Payment Provider<?= sf_admin_select('payment_provider', ['sandbox'=>'Sandbox','stripe'=>'Stripe','paypal'=>'PayPal'], sf_get_setting('payment_provider','sandbox')) ?></label><label>Stripe Publishable Key<input name="stripe_publishable_key" value="<?= sf_admin_h(sf_get_setting('stripe_publishable_key', '')) ?>"<?= sf_admin_form_disabled_attr() ?>></label><label>PayPal Client ID<input name="paypal_client_id" value="<?= sf_admin_h(sf_get_setting('paypal_client_id', '')) ?>"<?= sf_admin_form_disabled_attr() ?>></label></div>
      <label class="sf-admin-check"><input type="checkbox" name="member_signup_enabled" value="1" <?= sf_get_setting('member_signup_enabled','1') === '1' ? 'checked' : '' ?><?= sf_admin_form_disabled_attr() ?>> Member signup enabled</label>
      <label class="sf-admin-check"><input type="checkbox" name="checkout_enabled" value="1" <?= sf_get_setting('checkout_enabled','1') === '1' ? 'checked' : '' ?><?= sf_admin_form_disabled_attr() ?>> Checkout enabled</label>
      <label class="sf-admin-check"><input type="checkbox" name="maintenance_mode" value="1" <?= sf_get_setting('maintenance_mode','0') === '1' ? 'checked' : '' ?><?= sf_admin_form_disabled_attr() ?>> Maintenance mode</label>
      <div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>>Save Settings</button></div>
    </form>
  </article>
  <aside class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Install Notes</span><h2>Required SQL</h2></div></div>
    <p class="sf-admin-copy">Saving settings requires <code>database/migrations/007_site_settings_installer.sql</code>. The page remains safe in static preview mode and reads environment variables as fallbacks.</p>
    <div class="sf-admin-roadmap"><div><span>1</span><strong>Base SQL</strong><p>Install catalog, members, merch, and subscriptions.</p></div><div><span>2</span><strong>Migrations 001–010</strong><p>Add tracking, uploads, billing, notifications, settings, gateway adapters, and video v2.</p></div><div><span>3</span><strong>Health check</strong><p>Use System Health before launch.</p></div></div>
  </aside>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
