  </main>
  <footer class="home-footer-full site-global-footer">
    <div class="home-footer">
      <div class="home-footer-brand">
        <div class="home-brand footer-home-brand"><img src="<?= sf_asset('images/brand/footer-brand-approved.png') ?>" alt="Stonefellow" class="footer-brand-image"></div>
      </div>
      <div class="home-footer-links grouped-links">
        <div>
          <h4>Explore</h4>
          <a href="<?= sf_url('episodes.php') ?>">Watch Episodes</a>
          <a href="<?= sf_url('music.php') ?>">Listen to Music</a>
          <a href="<?= sf_url('merch.php') ?>">Shop Merch</a>
          <a href="<?= sf_url('member.php') ?>">Member Dashboard</a>
          <a href="<?= sf_url('signup.php') ?>">Create Account</a>
        </div>
        <div>
          <h4>Support</h4>
          <a href="<?= sf_url('support.php') ?>">Support Center</a>
          <a href="<?= sf_url('signin.php') ?>">Sign In</a>
          <a href="<?= sf_url('forgot-password.php') ?>">Forgot Password</a>
          <a href="<?= sf_url('account.php') ?>">Account Settings</a>
        </div>
      </div>
      <div class="home-footer-social">
        <h4>Follow Us</h4>
        <div class="social-row">
          <a href="https://instagram.com/stonefellow" target="_blank" rel="noopener" aria-label="Stonefellow on Instagram">◎</a>
          <a href="https://youtube.com/@stonefellow" target="_blank" rel="noopener" aria-label="Stonefellow on YouTube">▶</a>
          <a href="https://x.com/stonefellow" target="_blank" rel="noopener" aria-label="Stonefellow on X">𝕏</a>
          <a href="https://facebook.com/stonefellow" target="_blank" rel="noopener" aria-label="Stonefellow on Facebook">f</a>
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
  <script>window.STONEFELLOW_PWA = {serviceWorker: "<?= sf_url('service-worker.js') ?>", offline: "<?= sf_url('offline.php') ?>"};</script>
  <script src="<?= sf_asset('js/stonefellow.js') ?>"></script>
  <script src="<?= sf_asset('js/pwa-upload.js') ?>"></script>
</body>
</html>
