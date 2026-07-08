document.addEventListener('DOMContentLoaded', function () {
  if (!document.body.classList.contains('sf-logged-in')) {
    var publicNav = document.querySelector('.home-nav[data-site-nav]');
    if (publicNav && !publicNav.querySelector('.home-mobile-account-links')) {
      var accountGroup = document.createElement('div');
      accountGroup.className = 'home-mobile-account-links';
      accountGroup.innerHTML = '<span>Account</span><a href="signin.php">Sign In</a><a href="signup.php">Create Account</a><a href="forgot-password.php">Forgot Password</a>';
      publicNav.appendChild(accountGroup);
    }
  }
});
