<?php
$pageTitle = 'Create Account';
$pageDescription = 'Create a DesertRio account to subscribe, watch episodes, and save your member library.';
$pageClass = 'auth-template desertrio-auth-template';
$pageExtraStyles = ['css/desertrio-account.css'];
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/desertrio_theme.php';

if (sf_auth_user()) sf_redirect(sf_url('member.php'));
$displayName = '';
$email = '';
$minPassword = sf_auth_password_min_length();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $displayName = (string)($_POST['display_name'] ?? '');
  $email = (string)($_POST['email'] ?? '');
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
    sf_auth_flash('error', 'Security check failed. Refresh and try again.');
  } elseif (sf_auth_secure_register($displayName, $email, (string)($_POST['password'] ?? ''), (string)($_POST['password_confirm'] ?? ''), !empty($_POST['terms']))) {
    sf_redirect(sf_url('subscribe.php'));
  }
}
require __DIR__ . '/includes/header.php';
?>
<section class="auth-page signup-page">
  <div class="auth-shell reverse">
    <section class="auth-card" aria-labelledby="signup-title">
      <div class="auth-mark">DR</div><span class="auth-kicker">Join the Story</span><h2 id="signup-title">Create your account</h2><p class="auth-intro">Build your watchlist, unlock subscriber access, and follow every DesertRio reveal.</p>
      <form class="auth-form" action="<?= sf_url('signup.php') ?>" method="post">
        <?= sf_csrf_field() ?>
        <label for="signup-name">Display name</label><input id="signup-name" name="display_name" type="text" autocomplete="name" maxlength="120" placeholder="Your name" value="<?= sf_auth_h($displayName) ?>" required>
        <label for="signup-email">Email address</label><input id="signup-email" name="email" type="email" autocomplete="email" maxlength="190" placeholder="you@example.com" value="<?= sf_auth_h($email) ?>" required>
        <label for="signup-password">Password</label><input id="signup-password" name="password" type="password" autocomplete="new-password" placeholder="At least <?= (int)$minPassword ?> characters" minlength="<?= (int)$minPassword ?>" maxlength="4096" aria-describedby="signup-password-help" required>
        <small id="signup-password-help">Use at least <?= (int)$minPassword ?> characters and avoid your email name or common passwords.</small>
        <label for="signup-password-confirm">Confirm password</label><input id="signup-password-confirm" name="password_confirm" type="password" autocomplete="new-password" placeholder="Confirm password" minlength="<?= (int)$minPassword ?>" maxlength="4096" required>
        <label class="auth-check"><input type="checkbox" name="terms" value="1" required> <span>I agree to the Terms of Service and Privacy Policy.</span></label>
        <button class="auth-submit" type="submit">Create Account</button>
      </form>
      <p class="auth-switch">Already have an account? <a href="<?= sf_url('signin.php') ?>">Sign in</a></p>
      <div class="auth-note">Production owner access must be created through the protected installer before public registration.</div>
    </section>
    <aside class="auth-poster"><img src="<?= sf_asset($desertRioAssets['welcome']) ?>" alt="Exclusive DesertRio Arizona residence"><div class="auth-poster-copy"><span>Inside DesertRio</span><h1>Watch. Save. Return.</h1><p>Keep every episode, cast update, purchase, notification, and membership setting connected to one secure account.</p></div></aside>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>