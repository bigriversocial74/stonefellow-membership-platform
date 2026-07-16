<?php
$pageTitle = 'Forgot Password';
$pageDescription = 'Reset your DesertRio account password.';
$pageClass = 'auth-template desertrio-auth-template';
$pageExtraStyles = ['css/desertrio-account.css'];
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/auth.php';

if (sf_auth_user()) sf_redirect(sf_url('member.php'));
$resetLink = null;
$email = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $email = (string)($_POST['email'] ?? '');
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
    sf_auth_flash('error', 'Security check failed. Refresh and try again.');
  } else {
    $token = sf_auth_secure_reset_create($email);
    sf_auth_flash('success', 'If that account exists, reset instructions have been sent.');
    if ($token) $resetLink = sf_url('reset-password.php?token=' . urlencode($token));
  }
}
require __DIR__ . '/includes/header.php';
?>
<section class="auth-page compact-auth">
  <div class="auth-shell single">
    <section class="auth-card" aria-labelledby="forgot-title">
      <div class="auth-mark">DR</div><span class="auth-kicker">Account Recovery</span><h2 id="forgot-title">Reset your password</h2><p class="auth-intro">Enter your email address and we will send instructions when a matching account exists.</p>
      <form class="auth-form" action="<?= sf_url('forgot-password.php') ?>" method="post">
        <?= sf_csrf_field() ?><label for="forgot-email">Email address</label><input id="forgot-email" name="email" type="email" autocomplete="email" maxlength="190" placeholder="you@example.com" value="<?= sf_auth_h($email) ?>" required><button class="auth-submit" type="submit">Send Reset Instructions</button>
      </form>
      <?php if ($resetLink): ?><div class="auth-note auth-reset-link-note"><strong>Development-only reset link</strong><br><a href="<?= sf_auth_h($resetLink) ?>"><?= sf_auth_h($resetLink) ?></a></div><?php else: ?><div class="auth-note">Reset tokens are never displayed in production. Configure transactional email before launch.</div><?php endif; ?>
      <p class="auth-switch"><a href="<?= sf_url('signin.php') ?>">Return to sign in</a></p>
    </section>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>