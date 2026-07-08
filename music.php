<?php
$pageTitle = 'Music';
$pageDescription = 'Stream the official Stonefellow soundtrack from the database-backed public catalog.';
$pageClass = 'music-template';
require __DIR__ . '/includes/music_catalog.php';
require __DIR__ . '/includes/header.php';

$catalogSongs = sf_music_public_catalog_songs();
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
      <p>The public music page now reads from the database song catalog. Use the admin song manager to edit the sample tracks, upload full audio, and control which tracks appear in the public player.</p>
      <div class="music-actions">
        <a href="#tracks" class="music-btn music-btn-primary"><span class="music-play-icon"></span>Play the Soundtrack</a>
        <a href="player.php" class="music-btn music-btn-outline">Open Full Player</a>
        <a href="subscribe.php" class="music-btn music-btn-outline">Subscribe</a>
      </div>
    </div>
    <div class="music-hero-art">
      <img src="<?= sf_asset('images/music/music-hero-guitar.png') ?>" alt="Stonefellow guitarist under dramatic stage lights">
    </div>
  </section>

  <?php if ($featuredSong): ?>
  <section id="album" class="music-album-panel streaming-album-panel">
    <div class="music-album-cover">
      <img data-player-cover src="<?= sf_asset($featuredSong['cover']) ?>" alt="Stonefellow soundtrack cover">
    </div>
    <div class="music-album-info">
      <div class="music-eyebrow">Featured · Database Catalog</div>
      <h2><?= htmlspecialchars($featuredSong['album_title'] ?? 'The Road Is Calling') ?></h2>
      <div class="album-meta"><?= count($catalogSongs) ?> Public Tracks · Full-song playback</div>
      <p>These tracks are loaded from the database. Edit the existing sample records in the admin song catalog and attach full audio files as needed.</p>
      <div class="stream-mode-banner">
        <span>Public Player</span>
        <strong>Full track mode</strong>
        <a href="player.php">Open player →</a>
      </div>
      <div class="catalog-player" data-player-shell>
        <button class="catalog-play-main" type="button" data-play-song
          data-title="<?= htmlspecialchars($featuredSong['title']) ?>"
          data-artist="<?= htmlspecialchars($featuredSong['artist']) ?>"
          data-src="<?= sf_asset($featuredSong['full_src'] ?: $featuredSong['preview_src']) ?>"
          data-cover="<?= sf_asset($featuredSong['cover']) ?>"
          data-duration-seconds="<?= (int)$featuredSong['duration_seconds'] ?>"
          data-preview-seconds="<?= (int)$featuredSong['duration_seconds'] ?>"
          data-source-mode="full">▶</button>
        <div class="catalog-player-meta">
          <span>Now Playing</span>
          <strong data-player-title><?= htmlspecialchars($featuredSong['title']) ?></strong>
          <em data-player-artist><?= htmlspecialchars($featuredSong['artist']) ?></em>
          <div class="catalog-progress"><span data-player-progress></span></div>
          <small><b data-player-current>0:00</b> / <b data-player-limit><?= htmlspecialchars($featuredSong['duration']) ?></b></small>
        </div>
        <div class="catalog-player-actions">
          <button type="button" data-save-song>♡ Save</button>
          <a href="player.php">Open Full Player</a>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <section id="tracks" class="music-content-grid streaming-content-grid">
    <div class="music-list-panel catalog-list-panel">
      <div class="music-section-head compact-head">
        <div><h3>Streaming Catalog</h3><p>Database-backed public tracks from the admin song manager.</p></div>
      </div>
      <ol class="music-track-list catalog-track-list">
        <?php foreach ($catalogSongs as $i => $track): ?>
          <li class="catalog-row <?= $i === 0 ? 'is-active' : '' ?>" data-song-row>
            <button class="track-play" type="button" data-play-song
              data-title="<?= htmlspecialchars($track['title']) ?>"
              data-artist="<?= htmlspecialchars($track['artist']) ?>"
              data-src="<?= sf_asset($track['full_src'] ?: $track['preview_src']) ?>"
              data-cover="<?= sf_asset($track['cover']) ?>"
              data-duration-seconds="<?= (int)$track['duration_seconds'] ?>"
              data-preview-seconds="<?= (int)$track['duration_seconds'] ?>"
              data-source-mode="full">▶</button>
            <span class="track-number"><?= htmlspecialchars($track['track']) ?></span>
            <div class="track-name-block">
              <strong><?= htmlspecialchars($track['title']) ?></strong>
              <small><?= htmlspecialchars($track['episode']) ?></small>
            </div>
            <em><?= htmlspecialchars($track['duration']) ?></em>
            <span class="preview-badge">Full track</span>
            <button class="library-btn" type="button" data-save-song title="Save requires login">♡</button>
            <a class="unlock-link" href="player.php">Play</a>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>

    <div class="episode-song-panel library-panel">
      <h3>Admin-Controlled Library</h3>
      <div class="library-teaser">
        <div class="library-icon">SF</div>
        <div>
          <strong>Edit these records in Songs.</strong>
          <p>The demo songs are now real sample records. Replace titles, cover art, and audio files in the admin catalog.</p>
        </div>
      </div>
      <div class="library-actions-grid">
        <a href="player.php">Open Player</a>
        <a href="#tracks">Browse Catalog</a>
        <a href="subscribe.php">Subscribe</a>
      </div>
      <h3 class="songs-from-heading">Songs From Episodes</h3>
      <div class="episode-song-list">
        <?php foreach ($episodeSongs as $song): ?>
          <div class="episode-song-row catalog-episode-row">
            <img src="<?= sf_asset($song['cover']) ?>" alt="<?= htmlspecialchars($song['title']) ?> episode still">
            <button type="button" class="mini-play" data-play-song
              data-title="<?= htmlspecialchars($song['title']) ?>"
              data-artist="<?= htmlspecialchars($song['artist']) ?>"
              data-src="<?= sf_asset($song['full_src'] ?: $song['preview_src']) ?>"
              data-cover="<?= sf_asset($song['cover']) ?>"
              data-duration-seconds="<?= (int)$song['duration_seconds'] ?>"
              data-preview-seconds="<?= (int)$song['duration_seconds'] ?>"
              data-source-mode="full">▶</button>
            <div><span><?= htmlspecialchars($song['episode_short']) ?></span><strong><?= htmlspecialchars($song['title']) ?></strong><small>Full track attached</small></div>
            <em><?= htmlspecialchars($song['duration']) ?></em>
            <a href="player.php" class="add-song">▶</a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section id="playlists" class="playlist-preview-panel">
    <div class="music-section-head">
      <div><h3>Library & Playlists</h3><p>Foundation for the logged-in streaming platform.</p></div>
      <a href="subscribe.php" class="music-btn music-btn-outline">Subscribe</a>
    </div>
    <div class="playlist-grid">
      <article><span>System Playlist</span><h4>Road Songs</h4><p>Episode tracks for driving, escape, and bad decisions.</p></article>
      <article><span>Subscriber Playlist</span><h4>Saved Songs</h4><p>Logged-in users collect their favorite Stonefellow tracks.</p></article>
      <article><span>Playback History</span><h4>Recently Played</h4><p>Resume songs and track engagement across the catalog.</p></article>
    </div>
  </section>

  <section id="live" class="music-live-panel">
    <div class="music-section-head">
      <div><h3>Live Sessions</h3><p>Raw performances. Real moments. Public clips, full sessions for subscribers.</p></div>
      <a href="subscribe.php" class="music-btn music-btn-outline">Unlock Sessions</a>
    </div>
    <div class="live-session-grid">
      <?php foreach ($liveSessions as $session): ?>
        <a href="subscribe.php" class="live-session-card"><img src="<?= sf_asset('images/music/' . $session['img']) ?>" alt="<?= htmlspecialchars($session['title']) ?>"><span class="live-play">▶</span><strong><?= htmlspecialchars($session['title']) ?></strong><small>Live from the Saloon · Locked</small></a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="music-platform-panel"><div class="platform-mark">SF</div><div><h3>Database Songs. Full Playback.</h3><p>The public soundtrack is now connected to the songs and song_files tables so admin edits flow into the public player.</p></div></section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
