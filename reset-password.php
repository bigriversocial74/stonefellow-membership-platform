<?php
$pageTitle = 'Reset Password';
$pageDescription = 'Choose a new Stonefellow account password.';
$pageClass = 'auth-template';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/auth.php';

$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
    sf_auth_flash('error', 'Security check failed. Refresh and try again.');
  } elseif (sf_password_reset_apply($token, (string)($_POST['password'] ?? ''), (string)($_POST['password_confirm'] ?? ''))) {
    sf_redirect(sf_url('signin.php'));
  }
}

require __DIR__ . '/includes/header.php';
?>
<section class="auth-page compact-auth">
  <div class="auth-shell single">
    <section class="auth-card" aria-labelledby="reset-title">
      <div class="auth-mark">SF</div>
      <span class="auth-kicker">Secure reset</span>
      <h2 id="reset-title">Choose a new password</h2>
      <p class="auth-intro">Create a strong password to protect your episode history, library, and subscription access.</p>

      <form class="auth-form" action="<?= sf_url('reset-password.php') ?>" method="post">
        <?= sf_csrf_field() ?>
        <input type="hidden" name="token" value="<?= sf_auth_h($token) ?>">
        <label for="reset-password">New password</label>
        <input id="reset-password" name="password" type="password" autocomplete="new-password" placeholder="New password" minlength="8" required>
        <label for="reset-password-confirm">Confirm new password</label>
        <input id="reset-password-confirm" name="password_confirm" type="password" autocomplete="new-password" placeholder="Confirm password" minlength="8" required>
        <button class="auth-submit" type="submit">Update Password</button>
      </form>

      <p class="auth-switch"><a href="<?= sf_url('signin.php') ?>">Return to sign in</a></p>
      <div class="auth-note">Reset links expire after 45 minutes and are marked used after a successful password change.</div>
    </section>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
