<?php
$pageTitle = 'Account Privacy';
$pageDescription = 'Export your Stonefellow account data, review retention boundaries, and deactivate your account.';
$pageClass = 'membership-page account-page';
$pageRobots = 'noindex,nofollow,noarchive';
require __DIR__ . '/includes/data.php';
require_once __DIR__ . '/includes/account_privacy.php';
$user = sf_require_login();
$blockers = sf_privacy_deactivation_blockers((int)$user['id']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
        sf_auth_flash('error', 'Security check failed. Refresh and try again.');
        sf_redirect(sf_url('account-privacy.php'));
    }
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'export') sf_privacy_download_export((int)$user['id']);
    if ($action === 'deactivate') {
        $result = sf_privacy_deactivate_account((int)$user['id'], (string)($_POST['confirmation'] ?? ''));
        if (!empty($result['ok'])) {
            sf_auth_logout(false);
            unset($_SESSION['sf_auth_last_activity'], $_SESSION['sf_auth_absolute_expires_at'], $_SESSION['sf_auth_fingerprint'], $_SESSION['sf_session_key']);
            session_regenerate_id(true);
            sf_auth_flash('success', 'Your account has been deactivated. Contact support if you need retained transaction records or account assistance.');
            sf_redirect(sf_url('signin.php'));
        }
        sf_auth_flash('error', (string)($result['message'] ?? 'Account deactivation failed.'));
        sf_redirect(sf_url('account-privacy.php'));
    }
}

require __DIR__ . '/includes/header.php';
?>
<section class="sf-membership-shell">
  <section class="sf-member-hero">
    <div><span class="sf-panel-eyebrow">Privacy Center</span><h1>Your account data and choices.</h1><p>Download a structured copy of your account information or deactivate access after active memberships and orders are resolved.</p><div class="sf-episode-action-row"><a class="sf-secondary-action" href="<?= sf_url('account.php') ?>">Back to Account</a><a class="sf-secondary-action" href="<?= sf_url('support.php') ?>">Contact Support</a></div></div>
    <article class="sf-member-status-card"><span>Account</span><strong><?= sf_auth_h($user['display_name'] ?: $user['email']) ?></strong><small>Exports are generated only for the signed-in account.</small></article>
  </section>

  <section class="sf-admin-two-col">
    <article class="sf-admin-panel">
      <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Data Portability</span><h2>Download your information</h2></div></div>
      <p class="sf-admin-copy">The JSON export includes available profile, membership, access, library, playlist, progress, order, support, notification, and comment records associated with your user ID.</p>
      <form method="post" class="sf-admin-form"><?= sf_csrf_field() ?><input type="hidden" name="action" value="export"><div class="sf-admin-form-actions"><button type="submit">Download Account Export</button></div></form>
    </article>
    <article class="sf-admin-panel">
      <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Retention</span><h2>Records that may remain</h2></div></div>
      <p class="sf-admin-copy">Deactivation disables sign-in and revokes tokens. Financial, tax, fulfillment, fraud-prevention, security, and legal records may remain where operationally or legally required.</p>
      <p class="sf-admin-copy">Contact support for a manual privacy review or correction request.</p>
    </article>
  </section>

  <section class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Account Deactivation</span><h2>Disable access and revoke credentials</h2></div></div>
    <?php if ($blockers): ?><div class="sf-admin-alert sf-admin-alert-warning" role="status"><strong>Deactivation is currently blocked.</strong><ul><?php foreach ($blockers as $blocker): ?><li><?= sf_auth_h($blocker) ?></li><?php endforeach; ?></ul></div><?php else: ?><p class="sf-admin-copy">This action disables your account, revokes authentication tokens, signs out the current device, and revokes tracked administrator sessions. It does not automatically erase legally retained records.</p><form method="post" class="sf-admin-form" onsubmit="return confirm('Deactivate this Stonefellow account and revoke sign-in access?')"><?= sf_csrf_field() ?><input type="hidden" name="action" value="deactivate"><label for="privacy-confirmation">Type DEACTIVATE to confirm<input id="privacy-confirmation" name="confirmation" autocomplete="off" pattern="DEACTIVATE" required></label><div class="sf-admin-form-actions"><button class="sf-danger-action" type="submit">Deactivate Account</button></div></form><?php endif; ?>
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
