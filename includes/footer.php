  </main>

  <?php if ($sfIsAdminSurface): ?>
    <footer class="home-footer-full site-global-footer" aria-label="Site footer">
      <div class="home-footer">
        <div class="home-copyright">© <?= date('Y') ?> Stonefellow. All Rights Reserved.</div>
      </div>
    </footer>
  <?php else: ?>
    <section class="dr-newsletter" aria-labelledby="dr-newsletter-title">
      <div class="dr-newsletter-copy">
        <h2 id="dr-newsletter-title">Stay in the Loop</h2>
        <div class="dr-small-ornament" aria-hidden="true"><span></span><b>✦</b><span></span></div>
        <p>Get the latest news, episode drops, and exclusive DesertRio content delivered straight to you.</p>
      </div>
      <form class="dr-newsletter-form" action="<?= sf_url('signup.php') ?>" method="get">
        <label class="sr-only" for="dr-newsletter-email">Email address</label>
        <input id="dr-newsletter-email" type="email" name="email" autocomplete="email" placeholder="Enter your email address" required>
        <button type="submit">Subscribe</button>
      </form>
      <img class="dr-newsletter-art" src="<?= sf_asset('images/desertrio/desertrio-newsletter.svg') ?>" alt="DesertRio poolside and desert scenes" loading="lazy" decoding="async">
    </section>

    <footer class="dr-site-footer site-global-footer" aria-label="Site footer">
      <a class="dr-footer-logo" href="<?= sf_url('index.php') ?>" aria-label="DesertRio home">DesertRio</a>
      <div class="dr-social" aria-label="Social links">
        <a href="https://instagram.com/desertrio" target="_blank" rel="noopener noreferrer" aria-label="DesertRio on Instagram">◎</a>
        <a href="https://tiktok.com/@desertrio" target="_blank" rel="noopener noreferrer" aria-label="DesertRio on TikTok">♪</a>
        <a href="https://youtube.com/@desertrio" target="_blank" rel="noopener noreferrer" aria-label="DesertRio on YouTube">▶</a>
        <a href="https://facebook.com/desertrio" target="_blank" rel="noopener noreferrer" aria-label="DesertRio on Facebook">f</a>
      </div>
      <nav class="dr-footer-nav" aria-label="Footer navigation">
        <a href="<?= sf_url('series.php') ?>">About</a>
        <a href="<?= sf_url('episodes.php') ?>">Episodes</a>
        <a href="<?= sf_url('cast.php') ?>">Cast</a>
        <a href="<?= sf_url('support.php') ?>">Press</a>
        <a href="<?= sf_url('support.php') ?>">Contact</a>
        <a href="<?= sf_url('account.php') ?>">Privacy</a>
      </nav>
      <p class="dr-copyright">© <?= date('Y') ?> DesertRio. All Rights Reserved.</p>
    </footer>
    <link rel="stylesheet" href="<?= sf_asset('css/desertrio-public-fixes.css') ?>">
  <?php endif; ?>

  <script>
    window.STONEFELLOW_PWA = {
      serviceWorker: "<?= sf_url('service-worker.js') ?>",
      offline: "<?= sf_url('offline.php') ?>",
      brandName: "<?= $sfIsAdminSurface ? 'Stonefellow' : 'DesertRio' ?>"
    };
    window.STONEFELLOW_RUNTIME = {
      libraryApi: "<?= sf_url('api/library.php') ?>",
      playlistApi: "<?= sf_url('api/playlist.php') ?>"
    };
  </script>
  <script defer src="<?= sf_asset('js/stonefellow.js') ?>"></script>
  <script defer src="<?= sf_asset('js/member-runtime.js') ?>"></script>
  <script defer src="<?= sf_asset('js/pwa-upload.js') ?>"></script>
  <script defer src="<?= sf_asset('js/customer-ui.js') ?>"></script>
  <script defer src="<?= sf_asset('js/frontend-quality.js') ?>"></script>
  <?php if (!$sfIsAdminSurface): ?>
    <script defer src="<?= sf_asset('js/desertrio-public-fixes.js') ?>"></script>
  <?php endif; ?>
</body>
</html>
