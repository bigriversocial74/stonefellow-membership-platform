  </main>
  <footer class="home-footer-full site-global-footer" aria-label="Site footer">
    <div class="home-footer">
      <div class="home-footer-brand">
        <div class="home-brand footer-home-brand"><img src="<?= $sfIsAdminSurface ? sf_asset('images/brand/footer-brand-approved.png') : lk_asset_url('logo') ?>" alt="<?= $sfIsAdminSurface ? 'Stonefellow' : 'Likenessing' ?>" class="footer-brand-image" loading="lazy" decoding="async"></div>
        <?php if (!$sfIsAdminSurface): ?><p>Your face. Your voice. Their contract.</p><?php endif; ?>
      </div>
      <nav class="home-footer-links grouped-links" aria-label="Footer navigation">
        <div>
          <h4>Explore</h4>
          <a href="<?= sf_url('episodes.php') ?>">Watch Episodes</a>
          <a href="<?= sf_url('cast.php') ?>">Meet the Cast</a>
          <a href="<?= sf_url('series.php') ?>">About the Series</a>
          <a href="<?= sf_url('extras.php') ?>">Extras</a>
          <a href="<?= sf_url('news.php') ?>">News</a>
          <a href="<?= sf_url('merch.php') ?>">Shop</a>
        </div>
        <div>
          <h4>Account</h4>
          <a href="<?= sf_url('member.php') ?>">Member Dashboard</a>
          <a href="<?= sf_url('library.php') ?>">My Library</a>
          <a href="<?= sf_url('signin.php') ?>">Sign In</a>
          <a href="<?= sf_url('signup.php') ?>">Create Account</a>
          <a href="<?= sf_url('account.php') ?>">Account Settings</a>
          <a href="<?= sf_url('support.php') ?>">Support</a>
        </div>
      </nav>
      <div class="home-footer-social">
        <h4>Follow</h4>
        <div class="social-row">
          <a href="https://instagram.com/likenessing" target="_blank" rel="noopener noreferrer" aria-label="Likenessing on Instagram"><span aria-hidden="true">◎</span></a>
          <a href="https://youtube.com/@likenessing" target="_blank" rel="noopener noreferrer" aria-label="Likenessing on YouTube"><span aria-hidden="true">▶</span></a>
          <a href="https://x.com/likenessing" target="_blank" rel="noopener noreferrer" aria-label="Likenessing on X"><span aria-hidden="true">𝕏</span></a>
          <a href="https://facebook.com/likenessing" target="_blank" rel="noopener noreferrer" aria-label="Likenessing on Facebook"><span aria-hidden="true">f</span></a>
        </div>
      </div>
      <div class="home-newsletter" aria-labelledby="likenessing-updates-title">
        <h4 id="likenessing-updates-title">Stay in the Loop</h4>
        <p>Get episode announcements, behind-the-scenes stories, and production updates.</p>
        <a class="home-subscribe-btn" href="<?= sf_url('signup.php') ?>">Join the List</a>
      </div>
      <div class="home-copyright">© <?= date('Y') ?> <?= $sfIsAdminSurface ? 'Stonefellow' : 'Likenessing Productions' ?>. All Rights Reserved.</div>
    </div>
  </footer>
  <script>window.STONEFELLOW_PWA = {serviceWorker: "<?= sf_url('service-worker.js') ?>", offline: "<?= sf_url('offline.php') ?>"}; window.STONEFELLOW_RUNTIME = {libraryApi: "<?= sf_url('api/library.php') ?>", playlistApi: "<?= sf_url('api/playlist.php') ?>"};</script>
  <script defer src="<?= sf_asset('js/stonefellow.js') ?>"></script>
  <script defer src="<?= sf_asset('js/member-runtime.js') ?>"></script>
  <script defer src="<?= sf_asset('js/pwa-upload.js') ?>"></script>
  <script defer src="<?= sf_asset('js/customer-ui.js') ?>"></script>
  <script defer src="<?= sf_asset('js/frontend-quality.js') ?>"></script>
  <?php if (!$sfIsAdminSurface): ?><script defer src="<?= sf_asset('js/likenessing-theme.js?v=20260716') ?>"></script><?php endif; ?>
</body>
</html>
