<?php
$pageTitle = 'Sign In';
$pageDescription = 'Sign in to Stonefellow to continue watching episodes, stream full songs, and manage your library.';
$pageClass = 'auth-template';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/auth.php';

$next = sf_safe_next_url($_GET['next'] ?? $_POST['next'] ?? sf_url('member.php'));
if (sf_auth_user()) sf_redirect($next);
$allowRemember = !sf_is_production() || sf_env_bool('SF_ALLOW_REMEMBER_ME', false);
$email = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $email = (string)($_POST['email'] ?? '');
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
    sf_auth_flash('error', 'Security check failed. Refresh and try again.');
  } elseif (sf_auth_secure_login($email, (string)($_POST['password'] ?? ''), $allowRemember && !empty($_POST['remember']))) {
    sf_redirect($next);
  }
}
require __DIR__ . '/includes/header.php';
?>
<section class="auth-page">
  <div class="auth-shell">
    <aside class="auth-poster">
      <img src="<?= sf_asset('images/home/hero-reference-crop.png') ?>" alt="Stonefellow band on stage">
      <div class="auth-poster-copy"><span>Member Access</span><h1>Continue the story.</h1><p>Watch episodes, stream the full soundtrack, save your favorite songs, and pick up where you left off.</p></div>
    </aside>
    <section class="auth-card" aria-labelledby="signin-title">
      <div class="auth-mark">SF</div><span class="auth-kicker">Welcome back</span><h2 id="signin-title">Sign in to Stonefellow</h2><p class="auth-intro">Access your watchlist, music library, subscription, and saved episodes.</p>
      <form class="auth-form" action="<?= sf_url('signin.php') ?>" method="post">
        <?= sf_csrf_field() ?><input type="hidden" name="next" value="<?= sf_auth_h($next) ?>">
        <label for="signin-email">Email address</label><input id="signin-email" name="email" type="email" autocomplete="email" placeholder="you@example.com" value="<?= sf_auth_h($email) ?>" required>
        <div class="auth-label-row"><label for="signin-password">Password</label><a href="<?= sf_url('forgot-password.php') ?>">Forgot password?</a></div>
        <input id="signin-password" name="password" type="password" autocomplete="current-password" placeholder="Your password" required>
        <?php if ($allowRemember): ?><label class="auth-check"><input type="checkbox" name="remember" value="1"> <span>Keep me signed in</span></label><?php endif; ?>
        <button class="auth-submit" type="submit">Sign In</button>
      </form>
      <p class="auth-switch">New to Stonefellow? <a href="<?= sf_url('signup.php') ?>">Create an account</a></p>
      <div class="auth-note">Sign-in attempts are rate limited and secure sessions expire after inactivity.</div>
    </section>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
