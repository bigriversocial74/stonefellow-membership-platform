  </main>
<?php if ($sfIsAdminSurface): ?>
  <footer class="home-footer-full site-global-footer" aria-label="Site footer">
    <div class="home-footer">
      <div class="home-footer-brand"><div class="home-brand footer-home-brand"><img src="<?= sf_asset('images/brand/footer-brand-approved.png') ?>" alt="Stonefellow" class="footer-brand-image" loading="lazy" decoding="async"></div></div>
      <nav class="home-footer-links grouped-links" aria-label="Footer navigation"><div><h4>Explore</h4><a href="<?= sf_url('episodes.php') ?>">Watch Episodes</a><a href="<?= sf_url('music.php') ?>">Listen to Music</a><a href="<?= sf_url('cast.php') ?>">Cast</a><a href="<?= sf_url('merch.php') ?>">Shop Merch</a></div><div><h4>Support</h4><a href="<?= sf_url('support.php') ?>">Support Center</a><a href="<?= sf_url('account.php') ?>">Account Settings</a></div></nav>
      <div class="home-copyright">© <?= date('Y') ?> Stonefellow. All Rights Reserved.</div>
    </div>
  </footer>
<?php else: ?>
  <footer class="home-footer-full site-global-footer" aria-label="Site footer">
    <div class="home-footer lk-shell">
      <div class="home-footer-brand"><a href="<?= sf_url('index.php') ?>"><img src="<?= sf_asset('images/likenessing/logo-lockup-v2.png') ?>" alt="Likenessing" class="footer-brand-image" loading="lazy" decoding="async"></a><p>An original comedy series about fame, identity, and the fine print.</p></div>
      <nav class="home-footer-links grouped-links" aria-label="Footer navigation"><div><h4>Explore</h4><a href="<?= sf_url('episodes.php') ?>">Watch Episodes</a><a href="<?= sf_url('cast.php') ?>">Cast</a><a href="<?= sf_url('series.php') ?>">About</a><a href="<?= sf_url('extras.php') ?>">Extras</a></div><div><h4>Account</h4><a href="<?= sf_url('signin.php') ?>">Sign In</a><a href="<?= sf_url('signup.php') ?>">Create Account</a><a href="<?= sf_url('watchlist.php') ?>">My List</a><a href="<?= sf_url('support.php') ?>">Support</a></div></nav>
      <div class="home-footer-social"><h4>Follow the Show</h4><div class="social-row"><a href="#" aria-label="Likenessing on Instagram">IG</a><a href="#" aria-label="Likenessing on X">X</a><a href="#" aria-label="Likenessing on YouTube">YT</a><a href="#" aria-label="Likenessing on Facebook">F</a></div></div>
      <div class="home-newsletter"><h4>Production</h4><p>News, casting notes, episode announcements, and behind-the-scenes access.</p><a class="home-subscribe-btn" href="<?= sf_url('news.php') ?>">Read the News</a></div>
      <div class="home-copyright">© <?= date('Y') ?> Likenessing Productions. All Rights Reserved.</div>
    </div>
  </footer>
<?php endif; ?>
  <script>window.STONEFELLOW_PWA = {serviceWorker: "<?= sf_url('service-worker.js') ?>", offline: "<?= sf_url('offline.php') ?>"}; window.STONEFELLOW_RUNTIME = {libraryApi: "<?= sf_url('api/library.php') ?>", playlistApi: "<?= sf_url('api/playlist.php') ?>"};</script>
  <script defer src="<?= sf_asset('js/stonefellow.js') ?>"></script>
  <script defer src="<?= sf_asset('js/member-runtime.js') ?>"></script>
  <script defer src="<?= sf_asset('js/pwa-upload.js') ?>"></script>
  <script defer src="<?= sf_asset('js/customer-ui.js') ?>"></script>
  <script defer src="<?= sf_asset('js/frontend-quality.js') ?>"></script>
</body>
</html>
