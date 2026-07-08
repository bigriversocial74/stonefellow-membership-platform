<?php
$pageTitle = 'Player';
$pageDescription = 'Stonefellow streaming player with database-backed songs, full-track playback, playlists, queue, and sticky controls.';
$pageClass = 'music-app-page music-player-page music-browse-page';
require __DIR__ . '/includes/audio_player.php';
require __DIR__ . '/includes/music_catalog.php';
$member = sf_member_snapshot();
$catalogSongs = sf_music_public_catalog_songs();
$featuredSong = $catalogSongs[0] ?? null;
$newReleases = array_slice($catalogSongs, 0, 4);
$continueListening = [$catalogSongs[9] ?? $catalogSongs[0] ?? null, $catalogSongs[7] ?? $catalogSongs[0] ?? null, $catalogSongs[6] ?? $catalogSongs[0] ?? null];
$continueListening = array_values(array_filter($continueListening));
$playlistCards = $musicPlaylists ?? [];
$albumMinutes = function_exists('sf_music_minutes') ? sf_music_minutes($catalogSongs) : 0;
$trackPayloads = sf_audio_tracks_payload($catalogSongs, $member);
$trackMap = sf_audio_track_map($catalogSongs, $member);
$featuredTrack = $featuredSong ? ($trackMap[(int)($featuredSong['id'] ?? 0)] ?? ($trackPayloads[0] ?? [])) : [];
$albumSlug = $featuredSong['album_slug'] ?? ($musicAlbum['slug'] ?? 'the-road-is-calling');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?> | <?= htmlspecialchars($site['name']) ?></title>
  <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Cinzel:wght@600;700&family=Bodoni+Moda:opsz,wght@6..96,500;6..96,600;6..96,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= sf_asset('css/stonefellow.css') ?>">
</head>
<body class="<?= htmlspecialchars($pageClass) ?>">
  <div class="site-noise" aria-hidden="true"></div>
  <section class="sf-stream-shell" data-sf-music-app data-player-state-api="<?= sf_url('api/player-state.php') ?>">
    <audio data-sf-audio preload="metadata"></audio>

    <aside class="sf-stream-sidebar">
      <a class="sf-stream-brand" href="<?= sf_url('index.php') ?>" aria-label="Stonefellow home"><img src="<?= sf_asset('images/brand/footer-brand-approved.png') ?>" alt="Stonefellow"></a>
      <nav class="sf-stream-nav" aria-label="Music navigation"><a class="is-active" href="<?= sf_url('player.php') ?>"><span>⌂</span>Home</a><a href="#new"><span>⌕</span>Search</a><a href="#library"><span>|||</span>Your Library</a></nav>
      <div class="sf-sidebar-group"><h3>Your Library</h3><a href="<?= sf_url('playlists.php') ?>"><span>♬</span>Playlists</a><a href="<?= sf_url('album.php?slug=' . urlencode($albumSlug)) ?>"><span>◴</span>Albums</a><a href="<?= sf_url('song.php?slug=' . urlencode($featuredSong['slug'] ?? '')) ?>"><span>♪</span>Songs</a><a href="<?= sf_url('cast.php') ?>"><span>♙</span>Artists</a></div>
      <div class="sf-sidebar-group sf-sidebar-playlists"><div class="sf-sidebar-title-row"><h3>Playlists</h3><a href="#playlists">＋</a></div><?php foreach ($playlistCards as $playlist): ?><a href="<?= sf_url($playlist['url']) ?>"><?= htmlspecialchars($playlist['title']) ?></a><?php endforeach; ?></div>
    </aside>

    <main class="sf-stream-main">
      <header class="sf-stream-topbar">
        <div class="sf-history-buttons"><a href="<?= sf_url('index.php') ?>">‹</a><a href="<?= sf_url('album.php?slug=' . urlencode($albumSlug)) ?>">›</a></div>
        <label class="sf-stream-search"><span>⌕</span><input type="search" placeholder="Search Stonefellow" aria-label="Search Stonefellow"></label>
        <div class="sf-topbar-actions"><a href="<?= sf_url('signup.php') ?>">Subscribe</a><a class="sf-avatar" href="<?= sf_url('signin.php') ?>"><img src="<?= sf_asset('images/cast/cast-jax.png') ?>" alt="Account"></a></div>
      </header>

      <section class="sf-home-section">
        <h1>Welcome back</h1>
        <div class="sf-quick-grid">
          <a class="sf-quick-card" href="<?= sf_url('album.php?slug=' . urlencode($albumSlug)) ?>"><img src="<?= sf_asset('images/cast/band-portraits.png') ?>" alt="Stonefellow band playlist"><strong>My Playlist</strong><span><?= count($catalogSongs) ?> songs</span></a>
          <a class="sf-quick-card sf-liked-card" href="#playlists"><span class="sf-heart-large">♥</span><strong>Liked Songs</strong><span>Member library</span></a>
          <a class="sf-quick-card" href="<?= sf_url('album.php?slug=' . urlencode($albumSlug)) ?>"><img src="<?= sf_asset('images/music/music-live-02.png') ?>" alt="Stonefellow live"><strong>Stonefellow Live</strong><span>Database tracks</span></a>
          <a class="sf-quick-card" href="<?= sf_url('album.php?slug=' . urlencode($albumSlug)) ?>"><img src="<?= sf_asset('images/music/soundtrack-cover.png') ?>" alt="Road trip playlist"><strong>Road Trip Essentials</strong><span><?= count($catalogSongs) ?> songs</span></a>
        </div>
      </section>

      <section class="sf-home-section sf-admin-panel">
        <div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Audio Player v3</span><h2>Database-backed full-track playback</h2></div><a href="<?= sf_url('account-billing.php') ?>"><?= htmlspecialchars($member['access_label']) ?></a></div>
        <p class="sf-admin-copy">The player now reads published tracks from the database and plays the attached full-track source when available. Demo tracks are editable sample records in the admin song catalog.</p>
      </section>

      <section id="new" class="sf-home-section">
        <div class="sf-section-title"><h2>New Releases</h2><a href="<?= sf_url('album.php?slug=' . urlencode($albumSlug)) ?>">View all</a></div>
        <div class="sf-release-grid"><?php foreach ($newReleases as $song): ?><a class="sf-release-card" href="<?= sf_url('song.php?slug=' . urlencode($song['slug'])) ?>"><img src="<?= sf_asset($song['cover']) ?>" alt="<?= htmlspecialchars($song['title']) ?> cover"><strong><?= htmlspecialchars($song['title']) ?></strong><span><?= htmlspecialchars($song['artist']) ?></span></a><?php endforeach; ?></div>
      </section>

      <section class="sf-home-section">
        <div class="sf-section-title"><h2>Continue Listening</h2><a href="<?= sf_url('album.php?slug=' . urlencode($albumSlug)) ?>">View all</a></div>
        <div class="sf-continue-list">
          <?php foreach ($continueListening as $i => $song): $track = $trackMap[(int)$song['id']] ?? sf_audio_track_payload($song, $member); ?>
            <article class="sf-continue-row"><img src="<?= sf_asset($song['cover']) ?>" alt="<?= htmlspecialchars($song['title']) ?> cover"><div><strong><?= htmlspecialchars($song['title']) ?></strong><span><?= htmlspecialchars($track['source_mode'] === 'full' ? 'Full track' : 'Preview') ?> · <?= htmlspecialchars($song['artist']) ?></span></div><div class="sf-mini-progress"><span style="width:<?= [22,34,58][$i] ?? 40 ?>%"></span></div><button type="button" data-sf-play-song data-song-id="<?= (int)$track['id'] ?>" data-title="<?= htmlspecialchars($track['title']) ?>" data-artist="<?= htmlspecialchars($track['artist']) ?>" data-src="<?= htmlspecialchars($track['src']) ?>" data-cover="<?= htmlspecialchars($track['cover']) ?>" data-url="<?= htmlspecialchars($track['url']) ?>" data-duration="<?= htmlspecialchars($track['duration']) ?>" data-duration-seconds="<?= (int)$track['duration_seconds'] ?>" data-source-mode="<?= htmlspecialchars($track['source_mode']) ?>" aria-label="Play <?= htmlspecialchars($track['title']) ?>">▶</button></article>
          <?php endforeach; ?>
        </div>
      </section>

      <section id="playlists" class="sf-home-section sf-last-section"><div class="sf-section-title"><h2>Your Playlists</h2><a href="<?= sf_url('playlists.php') ?>">View all</a></div><div class="sf-playlist-grid"><?php foreach ($playlistCards as $playlist): ?><a class="sf-playlist-card" href="<?= sf_url($playlist['url']) ?>"><img src="<?= sf_asset($playlist['cover']) ?>" alt="<?= htmlspecialchars($playlist['title']) ?> cover"><strong><?= htmlspecialchars($playlist['title']) ?></strong><span><?= htmlspecialchars($playlist['songs']) ?></span></a><?php endforeach; ?><a class="sf-playlist-card sf-create-card" href="<?= sf_url('playlists.php') ?>"><span>＋</span><strong>Create Playlist</strong></a></div></section>
    </main>

    <footer class="sf-now-player" data-sf-player>
      <div class="sf-now-track"><img data-sf-player-cover src="<?= htmlspecialchars($featuredTrack['cover'] ?? sf_asset($featuredSong['cover'] ?? 'images/music/soundtrack-cover.png')) ?>" alt="<?= htmlspecialchars($featuredTrack['title'] ?? $featuredSong['title'] ?? 'Stonefellow') ?> cover"><div><a data-sf-player-link href="<?= htmlspecialchars($featuredTrack['url'] ?? '#') ?>" data-sf-player-title><?= htmlspecialchars($featuredTrack['title'] ?? $featuredSong['title'] ?? 'Stonefellow') ?></a><span data-sf-player-artist><?= htmlspecialchars($featuredTrack['artist'] ?? $featuredSong['artist'] ?? 'Stonefellow') ?></span></div></div>
      <button class="sf-now-like" type="button" data-sf-save>♡</button>
      <div class="sf-now-controls"><div class="sf-control-row"><button type="button" data-sf-shuffle>⌘</button><button type="button" data-sf-prev>◀</button><button class="sf-now-play" type="button" data-sf-player-toggle>Ⅱ</button><button type="button" data-sf-next>▶</button><button type="button" data-sf-repeat>↻</button></div><div class="sf-now-progress"><span data-sf-current>0:00</span><div><i data-sf-progress></i></div><span data-sf-duration><?= htmlspecialchars($featuredTrack['duration'] ?? $featuredSong['duration'] ?? '0:00') ?></span></div></div>
      <div class="sf-now-tools"><span><?= htmlspecialchars($featuredTrack['source_mode'] ?? 'full') ?></span><span>▤</span><span>♩</span><div class="sf-volume"><i></i></div></div>
    </footer>
  </section>
  <script>window.STONEFELLOW_TRACKS = <?= json_encode($trackPayloads, JSON_UNESCAPED_SLASHES) ?>; window.STONEFELLOW_RUNTIME = {libraryApi: "<?= sf_url('api/library.php') ?>", playlistApi: "<?= sf_url('api/playlist.php') ?>"};</script>
  <script src="<?= sf_asset('js/stonefellow.js') ?>"></script>
  <script src="<?= sf_asset('js/member-runtime.js') ?>"></script>
</body>
</html>
