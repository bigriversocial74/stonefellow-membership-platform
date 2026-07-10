  </main>
  <footer class="home-footer-full site-global-footer" aria-label="Site footer">
    <div class="home-footer">
      <div class="home-footer-brand">
        <div class="home-brand footer-home-brand"><img src="<?= sf_asset('images/brand/footer-brand-approved.png') ?>" alt="Stonefellow" class="footer-brand-image" loading="lazy" decoding="async"></div>
      </div>
      <nav class="home-footer-links grouped-links" aria-label="Footer navigation">
        <div>
          <h4>Explore</h4>
          <a href="<?= sf_url('episodes.php') ?>">Watch Episodes</a>
          <a href="<?= sf_url('music.php') ?>">Listen to Music</a>
          <a href="<?= sf_url('cast.php') ?>">Cast</a>
          <a href="<?= sf_url('series-characters.php') ?>">Series Characters</a>
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
      </nav>
      <div class="home-footer-social">
        <h4>Follow Us</h4>
        <div class="social-row">
          <a href="https://instagram.com/stonefellow" target="_blank" rel="noopener noreferrer" aria-label="Stonefellow on Instagram"><span aria-hidden="true">◎</span></a>
          <a href="https://youtube.com/@stonefellow" target="_blank" rel="noopener noreferrer" aria-label="Stonefellow on YouTube"><span aria-hidden="true">▶</span></a>
          <a href="https://x.com/stonefellow" target="_blank" rel="noopener noreferrer" aria-label="Stonefellow on X"><span aria-hidden="true">𝕏</span></a>
          <a href="https://facebook.com/stonefellow" target="_blank" rel="noopener noreferrer" aria-label="Stonefellow on Facebook"><span aria-hidden="true">f</span></a>
        </div>
      </div>
      <div class="home-newsletter" aria-labelledby="stonefellow-updates-title">
        <h4 id="stonefellow-updates-title">Updates</h4>
        <p>Join Stonefellow to receive account, episode, music, and merch updates.</p>
        <a class="home-subscribe-btn" href="<?= sf_url('signup.php') ?>">Create Account</a>
      </div>
      <div class="home-copyright">© <?= date('Y') ?> Stonefellow. All Rights Reserved.</div>
    </div>
  </footer>
  <script>window.STONEFELLOW_PWA = {serviceWorker: "<?= sf_url('service-worker.js') ?>", offline: "<?= sf_url('offline.php') ?>"}; window.STONEFELLOW_RUNTIME = {libraryApi: "<?= sf_url('api/library.php') ?>", playlistApi: "<?= sf_url('api/playlist.php') ?>"};</script>
  <script defer src="<?= sf_asset('js/stonefellow.js') ?>"></script>
  <script defer src="<?= sf_asset('js/member-runtime.js') ?>"></script>
  <script defer src="<?= sf_asset('js/pwa-upload.js') ?>"></script>
  <script defer src="<?= sf_asset('js/customer-ui.js') ?>"></script>
  <script defer src="<?= sf_asset('js/frontend-quality.js') ?>"></script>
</body>
</html>
