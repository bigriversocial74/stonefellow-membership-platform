  </main>
  <footer class="home-footer-full site-global-footer">
    <div class="home-footer">
      <div class="home-footer-brand">
        <div class="home-brand footer-home-brand"><img src="<?= sf_asset('images/brand/footer-brand-approved.png') ?>" alt="Stonefellow" class="footer-brand-image"></div>
      </div>
      <div class="home-footer-links grouped-links">
        <div>
          <h4>Explore</h4>
          <a href="<?= sf_url('index.php') ?>">Home</a>
          <a href="<?= sf_url('series.php') ?>">Series</a>
          <a href="<?= sf_url('episodes.php') ?>">Episodes</a>
          <a href="<?= sf_url('music.php') ?>">Music</a>
          <a href="<?= sf_url('search.php') ?>">Search</a>
          <a href="<?= sf_url('cast.php') ?>">Cast</a>
          <a href="<?= sf_url('merch.php') ?>">Merch</a>
          <a href="<?= sf_url('cart.php') ?>">Cart</a>
          <a href="<?= sf_url('checkout.php') ?>">Checkout</a>
          <a href="<?= sf_url('member.php') ?>">Member Dashboard</a>
          <a href="<?= sf_url('library.php') ?>">Library</a>
          <a href="<?= sf_url('watchlist.php') ?>">Watchlist</a>
          <a href="<?= sf_url('account.php') ?>">Account</a>
          <a href="<?= sf_url('playlists.php') ?>">Playlists</a>
          <a href="<?= sf_url('admin/index.php') ?>">Admin</a>
        </div>
        <div>
          <h4>Support</h4>
          <a href="<?= sf_url('signin.php') ?>">Sign In</a>
          <a href="<?= sf_url('signup.php') ?>">Create Account</a>
          <a href="<?= sf_url('forgot-password.php') ?>">Forgot Password</a>
          <a href="<?= sf_url('offline.php') ?>">Offline Shell</a>
          <a href="<?= sf_url('logout.php') ?>">Logout</a>
        </div>
      </div>
      <div class="home-footer-social">
        <h4>Follow Us</h4>
        <div class="social-row">
          <span>f</span><span>◎</span><span>◉</span><span>♪</span><span>𝕏</span>
        </div>
      </div>
      <form class="home-newsletter" action="#" method="post">
        <h4>Newsletter</h4>
        <div class="newsletter-row">
          <input id="newsletter-email" name="email" type="email" placeholder="Your email address">
          <button type="submit">➤</button>
        </div>
      </form>
      <div class="home-copyright">© 2024 Stonefellow. All Rights Reserved.</div>
    </div>
  </footer>
  <script src="<?= sf_asset('js/stonefellow.js') ?>"></script>
  <script src="<?= sf_asset('js/pwa-upload.js') ?>"></script>
</body>
</html>
