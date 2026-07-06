<?php
$pageTitle = 'Create Account';
$pageDescription = 'Create a Stonefellow account to subscribe, stream music, watch episodes, and save your library.';
$pageClass = 'auth-template';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/auth.php';

if (sf_auth_user()) {
  sf_redirect(sf_url('member.php'));
}

$displayName = '';
$email = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $displayName = (string)($_POST['display_name'] ?? '');
  $email = (string)($_POST['email'] ?? '');
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
    sf_auth_flash('error', 'Security check failed. Refresh and try again.');
  } elseif (sf_auth_register($displayName, $email, (string)($_POST['password'] ?? ''), (string)($_POST['password_confirm'] ?? ''), !empty($_POST['terms']))) {
    sf_redirect(sf_url('subscribe.php'));
  }
}

require __DIR__ . '/includes/header.php';
?>
<section class="auth-page signup-page">
  <div class="auth-shell reverse">
    <section class="auth-card" aria-labelledby="signup-title">
      <div class="auth-mark">SF</div>
      <span class="auth-kicker">Join the story</span>
      <h2 id="signup-title">Create your account</h2>
      <p class="auth-intro">Start your library, unlock subscriber access, and stream the Stonefellow soundtrack.</p>

      <form class="auth-form" action="<?= sf_url('signup.php') ?>" method="post">
        <?= sf_csrf_field() ?>
        <label for="signup-name">Display name</label>
        <input id="signup-name" name="display_name" type="text" autocomplete="name" placeholder="Your name" value="<?= sf_auth_h($displayName) ?>" required>

        <label for="signup-email">Email address</label>
        <input id="signup-email" name="email" type="email" autocomplete="email" placeholder="you@example.com" value="<?= sf_auth_h($email) ?>" required>

        <label for="signup-password">Password</label>
        <input id="signup-password" name="password" type="password" autocomplete="new-password" placeholder="Create a password" minlength="8" required>

        <label for="signup-password-confirm">Confirm password</label>
        <input id="signup-password-confirm" name="password_confirm" type="password" autocomplete="new-password" placeholder="Confirm password" minlength="8" required>

        <label class="auth-check"><input type="checkbox" name="terms" value="1" required> <span>I agree to the Terms of Service and Privacy Policy.</span></label>
        <button class="auth-submit" type="submit">Create Account</button>
      </form>

      <p class="auth-switch">Already have an account? <a href="<?= sf_url('signin.php') ?>">Sign in</a></p>
      <div class="auth-note">First registered user becomes admin so the owner can access the catalog manager.</div>
    </section>

    <aside class="auth-poster">
      <img src="<?= sf_asset('images/home/subscribe-preview.png') ?>" alt="Stonefellow subscription access">
      <div class="auth-poster-copy">
        <span>Subscriber Platform</span>
        <h1>Watch. Stream. Save.</h1>
        <p>Build your account around episodes, full songs, playlists, saved favorites, merch drops, and billing access.</p>
      </div>
    </aside>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
