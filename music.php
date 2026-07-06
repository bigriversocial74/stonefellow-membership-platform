<?php
$pageTitle = 'Music';
$pageDescription = 'Stream the official Stonefellow soundtrack and preview songs from the streaming catalog.';
$pageClass = 'music-template';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/header.php';

$featuredSong = $catalogSongs[0] ?? null;
$episodeSongs = array_slice($catalogSongs, 0, 5);
$liveSessions = [
  ['img'=>'music-live-01.png','title'=>'Riptide (Live)','slug'=>'riptide'],
  ['img'=>'music-live-02.png','title'=>'Long Road Home (Live)','slug'=>'long-road-home'],
  ['img'=>'music-live-03.png','title'=>'The Road Is Calling (Live)','slug'=>'the-road-is-calling'],
  ['img'=>'music-live-04.png','title'=>'Burn It Down (Live)','slug'=>'burn-it-down'],
  ['img'=>'music-live-05.png','title'=>'Nothing Left (Live)','slug'=>'nothing-left'],
];
?>
<section class="music-page streaming-page" data-music-app>
  <audio data-main-audio preload="metadata"></audio>

  <section class="music-hero">
    <div class="music-hero-copy">
      <h1>Music</h1>
      <div class="music-kicker">The Soundtrack of the Road.</div>
      <p>The official Stonefellow catalog powers both the public preview page and the subscriber streaming library. Public listeners get 30-second previews. Subscribers unlock the full tracks.</p>
      <div class="music-actions">
        <a href="#tracks" class="music-btn music-btn-primary"><span class="music-play-icon"></span>Preview the Soundtrack</a>
        <a href="player.php" class="music-btn music-btn-outline">Open Full Player</a>
        <a href="subscribe.php" class="music-btn music-btn-outline">Subscribe to Unlock</a>
      </div>
    </div>
    <div class="music-hero-art">
      <img src="<?= sf_asset('images/music/music-hero-guitar.png') ?>" alt="Stonefellow guitarist under dramatic stage lights">
    </div>
  </section>

  <?php if ($featuredSong): ?>
  <section id="album" class="music-album-panel streaming-album-panel">
    <div class="music-album-cover">
      <img data-player-cover src="<?= sf_asset($featuredSong['cover']) ?>" alt="Stonefellow The Road Is Calling soundtrack cover">
    </div>
    <div class="music-album-info">
      <div class="music-eyebrow">Featured · Public Preview Mode</div>
      <h2>The Road Is Calling</h2>
      <div class="album-meta"><?= count($catalogSongs) ?> Catalog Tracks · 30-Second Public Previews</div>
      <p>The public music landing page now reads from the streaming catalog. These preview players are wired for catalog audio paths and subscriber-only full-track access.</p>
      <div class="stream-mode-banner">
        <span>Preview Mode</span>
        <strong>30 seconds only</strong>
        <a href="subscribe.php">Stream full songs →</a>
      </div>
      <div class="catalog-player" data-player-shell>
        <button class="catalog-play-main" type="button" data-play-song
          data-title="<?= htmlspecialchars($featuredSong['title']) ?>"
          data-artist="<?= htmlspecialchars($featuredSong['artist']) ?>"
          data-src="<?= sf_asset($featuredSong['preview_src']) ?>"
          data-cover="<?= sf_asset($featuredSong['cover']) ?>"
          data-preview-seconds="<?= (int)$featuredSong['preview_seconds'] ?>">▶</button>
        <div class="catalog-player-meta">
          <span>Now Previewing</span>
          <strong data-player-title><?= htmlspecialchars($featuredSong['title']) ?></strong>
          <em data-player-artist><?= htmlspecialchars($featuredSong['artist']) ?></em>
          <div class="catalog-progress"><span data-player-progress></span></div>
          <small><b data-player-current>0:00</b> / <b data-player-limit>0:30</b></small>
        </div>
        <div class="catalog-player-actions">
          <button type="button" data-save-song>♡ Save</button>
          <a href="subscribe.php">Unlock Full Track</a>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <section id="tracks" class="music-content-grid streaming-content-grid">
    <div class="music-list-panel catalog-list-panel">
      <div class="music-section-head compact-head">
        <div><h3>Streaming Catalog</h3><p>Public preview source for the subscriber music library.</p></div>
      </div>
      <ol class="music-track-list catalog-track-list">
        <?php foreach ($catalogSongs as $i => $track): ?>
          <li class="catalog-row <?= $i === 0 ? 'is-active' : '' ?>" data-song-row>
            <button class="track-play" type="button" data-play-song
              data-title="<?= htmlspecialchars($track['title']) ?>"
              data-artist="<?= htmlspecialchars($track['artist']) ?>"
              data-src="<?= sf_asset($track['preview_src']) ?>"
              data-cover="<?= sf_asset($track['cover']) ?>"
              data-preview-seconds="<?= (int)$track['preview_seconds'] ?>">▶</button>
            <span class="track-number"><?= htmlspecialchars($track['track']) ?></span>
            <div class="track-name-block">
              <strong><?= htmlspecialchars($track['title']) ?></strong>
              <small><?= htmlspecialchars($track['episode']) ?></small>
            </div>
            <em><?= htmlspecialchars($track['duration']) ?></em>
            <span class="preview-badge">30 sec preview</span>
            <button class="library-btn" type="button" data-save-song title="Save requires login">♡</button>
            <a class="unlock-link" href="subscribe.php">Unlock</a>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>

    <div class="episode-song-panel library-panel">
      <h3>Subscriber Library</h3>
      <div class="library-teaser">
        <div class="library-icon">SF</div>
        <div>
          <strong>Build the logged-in music experience next.</strong>
          <p>Subscribers will stream full songs, save tracks, create playlists, and resume recently played audio.</p>
        </div>
      </div>
      <div class="library-actions-grid">
        <a href="subscribe.php">Subscribe to Stream</a>
        <a href="#tracks">Browse Catalog</a>
        <a href="#playlists">Preview Playlists</a>
      </div>
      <h3 class="songs-from-heading">Songs From Episodes</h3>
      <div class="episode-song-list">
        <?php foreach ($episodeSongs as $song): ?>
          <div class="episode-song-row catalog-episode-row">
            <img src="<?= sf_asset($song['cover']) ?>" alt="<?= htmlspecialchars($song['title']) ?> episode still">
            <button type="button" class="mini-play" data-play-song
              data-title="<?= htmlspecialchars($song['title']) ?>"
              data-artist="<?= htmlspecialchars($song['artist']) ?>"
              data-src="<?= sf_asset($song['preview_src']) ?>"
              data-cover="<?= sf_asset($song['cover']) ?>"
              data-preview-seconds="<?= (int)$song['preview_seconds'] ?>">▶</button>
            <div><span><?= htmlspecialchars($song['episode_short']) ?></span><strong><?= htmlspecialchars($song['title']) ?></strong><small>Preview available</small></div>
            <em><?= htmlspecialchars($song['duration']) ?></em>
            <a href="subscribe.php" class="add-song">🔒</a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section id="playlists" class="playlist-preview-panel">
    <div class="music-section-head">
      <div><h3>Library & Playlists</h3><p>Foundation for the logged-in streaming platform.</p></div>
      <a href="subscribe.php" class="music-btn music-btn-outline">Unlock Library</a>
    </div>
    <div class="playlist-grid">
      <article>
        <span>System Playlist</span>
        <h4>Road Songs</h4>
        <p>Episode tracks for driving, escape, and bad decisions.</p>
      </article>
      <article>
        <span>Subscriber Playlist</span>
        <h4>Saved Songs</h4>
        <p>Logged-in users collect their favorite Stonefellow tracks.</p>
      </article>
      <article>
        <span>Playback History</span>
        <h4>Recently Played</h4>
        <p>Resume songs and track engagement across the catalog.</p>
      </article>
    </div>
  </section>

  <section id="live" class="music-live-panel">
    <div class="music-section-head">
      <div><h3>Live Sessions</h3><p>Raw performances. Real moments. Public clips, full sessions for subscribers.</p></div>
      <a href="subscribe.php" class="music-btn music-btn-outline">Unlock Sessions</a>
    </div>
    <div class="live-session-grid">
      <?php foreach ($liveSessions as $session): ?>
        <a href="subscribe.php" class="live-session-card">
          <img src="<?= sf_asset('images/music/' . $session['img']) ?>" alt="<?= htmlspecialchars($session['title']) ?>">
          <span class="live-play">▶</span>
          <strong><?= htmlspecialchars($session['title']) ?></strong>
          <small>Live from the Saloon · Locked</small>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="music-platform-panel">
    <div class="platform-mark">SF</div>
    <div><h3>Public Previews. Subscriber Streaming.</h3><p>This catalog is ready to connect to login, subscription entitlements, full audio files, saved songs, playlists, and play history.</p></div>
    <div class="platform-buttons"><a href="subscribe.php">Start Streaming</a><a href="#tracks">Preview Catalog</a></div>
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
