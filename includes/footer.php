  </main>
  <section class="sf-reskin-newsletter" aria-labelledby="sf-reskin-newsletter-title">
    <div class="sf-reskin-newsletter-copy">
      <h2 id="sf-reskin-newsletter-title">STAY IN THE LOOP</h2>
      <div class="sf-lux-small-ornament" aria-hidden="true"><span></span><b>✦</b><span></span></div>
      <p>Get new episodes, soundtrack drops, tour stories, and exclusive Stonefellow content delivered straight to you.</p>
    </div>
    <form action="<?= sf_url('signup.php') ?>" method="get" class="sf-reskin-newsletter-form">
      <label class="sr-only" for="sf-newsletter-email">Email address</label>
      <input id="sf-newsletter-email" name="email" type="email" autocomplete="email" placeholder="Enter your email address" required>
      <button type="submit">SUBSCRIBE</button>
    </form>
    <div class="sf-reskin-newsletter-art" aria-hidden="true">
      <img src="<?= sf_asset('images/home/live-reference-card.png') ?>" alt="">
      <img src="<?= sf_asset('images/episodes/template-card-01.png') ?>" alt="">
      <img src="<?= sf_asset('images/music/soundtrack-cover.png') ?>" alt="">
      <span>SF</span>
    </div>
  </section>
  <footer class="home-footer-full site-global-footer" aria-label="Site footer">
    <div class="home-footer sf-reskin-footer">
      <a class="sf-reskin-footer-brand" href="<?= sf_url('index.php') ?>" aria-label="Stonefellow home">Stonefellow</a>
      <div class="sf-reskin-social" aria-label="Social links">
        <a href="https://instagram.com/stonefellow" target="_blank" rel="noopener noreferrer" aria-label="Stonefellow on Instagram">◎</a>
        <a href="https://youtube.com/@stonefellow" target="_blank" rel="noopener noreferrer" aria-label="Stonefellow on YouTube">▶</a>
        <a href="https://x.com/stonefellow" target="_blank" rel="noopener noreferrer" aria-label="Stonefellow on X">𝕏</a>
        <a href="https://facebook.com/stonefellow" target="_blank" rel="noopener noreferrer" aria-label="Stonefellow on Facebook">f</a>
      </div>
      <nav class="sf-reskin-footer-nav" aria-label="Footer navigation">
        <a href="<?= sf_url('series.php') ?>">About</a>
        <a href="<?= sf_url('episodes.php') ?>">Episodes</a>
        <a href="<?= sf_url('music.php') ?>">Music</a>
        <a href="<?= sf_url('cast.php') ?>">Cast</a>
        <a href="<?= sf_url('merch.php') ?>">Shop</a>
        <a href="<?= sf_url('support.php') ?>">Contact</a>
      </nav>
      <p class="sf-reskin-copyright">© <?= date('Y') ?> Stonefellow. All Rights Reserved.</p>
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