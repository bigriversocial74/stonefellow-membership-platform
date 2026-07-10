<?php
$pageTitle = 'Player';
$pageDescription = 'Stonefellow member streaming player with database-backed songs, full-track playback, playlists, queue, and sticky controls.';
$pageClass = 'music-app-page music-player-page music-browse-page';
$pageRobots = 'noindex,nofollow,noarchive';
require __DIR__ . '/includes/audio_player.php';
require __DIR__ . '/includes/music_catalog.php';
require_once __DIR__ . '/includes/frontend_quality.php';
$member = sf_member_snapshot();
if (!sf_auth_user()) {
  sf_auth_flash('warning', 'Sign in to access the Stonefellow member player. The public music page remains available without an account.');
  sf_redirect(sf_url('signin.php?next=' . urlencode('player.php')));
}
if (empty($member['can_stream_full_music']) && !sf_access_allows('subscriber', (string)($member['access_level'] ?? 'public'))) {
  sf_auth_flash('warning', 'A paid membership is required to access the full player.');
  sf_redirect(sf_url('subscribe.php'));
}
$catalogSongs = sf_music_public_catalog_songs();
$featuredSong = $catalogSongs[0] ?? null;
$newReleases = array_slice($catalogSongs, 0, 4);
$continueListening = [$catalogSongs[9] ?? $catalogSongs[0] ?? null, $catalogSongs[7] ?? $catalogSongs[0] ?? null, $catalogSongs[6] ?? $catalogSongs[0] ?? null];
$continueListening = array_values(array_filter($continueListening));
$playlistCards = $musicPlaylists ?? [];
$trackPayloads = sf_audio_tracks_payload($catalogSongs, $member);
$trackMap = sf_audio_track_map($catalogSongs, $member);
$featuredTrack = $featuredSong ? ($trackMap[(int)($featuredSong['id'] ?? 0)] ?? ($trackPayloads[0] ?? [])) : [];
$albumSlug = $featuredSong['album_slug'] ?? ($musicAlbum['slug'] ?? 'the-road-is-calling');
$canonical = sf_frontend_canonical_url();
$socialImage = sf_frontend_social_image();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title><?= htmlspecialchars($pageTitle) ?> | <?= htmlspecialchars($site['name']) ?></title>
  <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
  <meta name="robots" content="<?= htmlspecialchars($pageRobots) ?>">
  <meta name="theme-color" content="#0b0907">
  <meta name="color-scheme" content="dark light">
  <link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= htmlspecialchars($pageTitle . ' | ' . $site['name']) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($pageDescription) ?>">
  <meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
  <meta property="og:image" content="<?= htmlspecialchars($socialImage) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Cinzel:wght@600;700&family=Bodoni+Moda:opsz,wght@6..96,500;6..96,600;6..96,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= sf_asset('css/stonefellow.css') ?>">
  <link rel="stylesheet" href="<?= sf_asset('css/frontend-quality.css') ?>">
  <script type="application/ld+json"><?= sf_frontend_json_ld($pageTitle, $pageDescription) ?></script>
</head>
<body class="<?= htmlspecialchars($pageClass) ?>">
  <a class="sf-skip-link" href="#main-content">Skip to player content</a>
  <div class="site-noise" aria-hidden="true"></div>
  <section class="sf-stream-shell" data-sf-music-app data-player-state-api="<?= sf_url('api/player-state.php') ?>">
    <audio data-sf-audio preload="metadata" aria-label="Stonefellow audio player"></audio>

    <aside class="sf-stream-sidebar" aria-label="Player sidebar">
      <a class="sf-stream-brand" href="<?= sf_url('index.php') ?>" aria-label="Stonefellow home"><img src="<?= sf_asset('images/brand/footer-brand-approved.png') ?>" alt="Stonefellow" decoding="async" fetchpriority="high"></a>
      <nav class="sf-stream-nav" aria-label="Music navigation"><a class="is-active" aria-current="page" href="<?= sf_url('player.php') ?>"><span aria-hidden="true">⌂</span>Home</a><a href="#new"><span aria-hidden="true">⌕</span>Search</a><a href="#library"><span aria-hidden="true">|||</span>Your Library</a></nav>
      <div class="sf-sidebar-group"><h2>Your Library</h2><a href="<?= sf_url('playlists.php') ?>"><span aria-hidden="true">♬</span>Playlists</a><a href="<?= sf_url('album.php?slug=' . urlencode($albumSlug)) ?>"><span aria-hidden="true">◴</span>Albums</a><a href="<?= sf_url('song.php?slug=' . urlencode($featuredSong['slug'] ?? '')) ?>"><span aria-hidden="true">♪</span>Songs</a><a href="<?= sf_url('cast.php') ?>"><span aria-hidden="true">♙</span>Artists</a></div>
      <div class="sf-sidebar-group sf-sidebar-playlists"><div class="sf-sidebar-title-row"><h2>Playlists</h2><a href="<?= sf_url('playlists.php') ?>" aria-label="Create a playlist">＋</a></div><?php foreach ($playlistCards as $playlist): ?><a href="<?= sf_url($playlist['url']) ?>"><?= htmlspecialchars($playlist['title']) ?></a><?php endforeach; ?></div>
    </aside>

    <main class="sf-stream-main" id="main-content" tabindex="-1">
      <header class="sf-stream-topbar">
        <div class="sf-history-buttons"><a href="<?= sf_url('music.php') ?>" aria-label="Back to public music">‹</a><a href="<?= sf_url('album.php?slug=' . urlencode($albumSlug)) ?>" aria-label="Open featured album">›</a></div>
        <label class="sf-stream-search"><span aria-hidden="true">⌕</span><span class="sf-visually-hidden">Search Stonefellow music</span><input type="search" placeholder="Search Stonefellow" aria-label="Search Stonefellow"></label>
        <div class="sf-topbar-actions"><a href="<?= sf_url('account-billing.php') ?>"><?= htmlspecialchars($member['access_label']) ?></a><a class="sf-avatar" href="<?= sf_url('member.php') ?>" aria-label="Open member dashboard"><img src="<?= sf_asset('images/cast/cast-jax.png') ?>" alt="" loading="lazy" decoding="async"></a></div>
      </header>

      <section class="sf-home-section" aria-labelledby="player-welcome-title">
        <h1 id="player-welcome-title">Welcome back</h1>
        <div class="sf-quick-grid">
          <a class="sf-quick-card" href="<?= sf_url('album.php?slug=' . urlencode($albumSlug)) ?>"><img src="<?= sf_asset('images/cast/band-portraits.png') ?>" alt="Stonefellow band playlist" loading="lazy" decoding="async"><strong>My Playlist</strong><span><?= count($catalogSongs) ?> songs</span></a>
          <a class="sf-quick-card sf-liked-card" href="#playlists"><span class="sf-heart-large" aria-hidden="true">♥</span><strong>Liked Songs</strong><span>Member library</span></a>
          <a class="sf-quick-card" href="<?= sf_url('album.php?slug=' . urlencode($albumSlug)) ?>"><img src="<?= sf_asset('images/music/music-live-02.png') ?>" alt="Stonefellow live" loading="lazy" decoding="async"><strong>Stonefellow Live</strong><span>Database tracks</span></a>
          <a class="sf-quick-card" href="<?= sf_url('album.php?slug=' . urlencode($albumSlug)) ?>"><img src="<?= sf_asset('images/music/soundtrack-cover.png') ?>" alt="Road trip playlist" loading="lazy" decoding="async"><strong>Road Trip Essentials</strong><span><?= count($catalogSongs) ?> songs</span></a>
        </div>
      </section>

      <section class="sf-home-section sf-admin-panel" aria-labelledby="subscriber-playback-title">
        <div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Member Player</span><h2 id="subscriber-playback-title">Subscriber full-track playback</h2></div><a href="<?= sf_url('account-billing.php') ?>"><?= htmlspecialchars($member['access_label']) ?></a></div>
        <p class="sf-admin-copy">This full player is for logged-in paying members. The public music page remains available without an account.</p>
      </section>

      <section id="new" class="sf-home-section" aria-labelledby="new-releases-title">
        <div class="sf-section-title"><h2 id="new-releases-title">New Releases</h2><a href="<?= sf_url('album.php?slug=' . urlencode($albumSlug)) ?>">View all</a></div>
        <div class="sf-release-grid"><?php foreach ($newReleases as $song): ?><a class="sf-release-card" href="<?= sf_url('song.php?slug=' . urlencode($song['slug'])) ?>"><img src="<?= sf_asset($song['cover']) ?>" alt="<?= htmlspecialchars($song['title']) ?> cover" loading="lazy" decoding="async"><strong><?= htmlspecialchars($song['title']) ?></strong><span><?= htmlspecialchars($song['artist']) ?></span></a><?php endforeach; ?></div>
      </section>

      <section class="sf-home-section" id="library" aria-labelledby="continue-listening-title">
        <div class="sf-section-title"><h2 id="continue-listening-title">Continue Listening</h2><a href="<?= sf_url('album.php?slug=' . urlencode($albumSlug)) ?>">View all</a></div>
        <div class="sf-continue-list">
          <?php foreach ($continueListening as $i => $song): $track = $trackMap[(int)$song['id']] ?? sf_audio_track_payload($song, $member); ?>
            <article class="sf-continue-row"><img src="<?= sf_asset($song['cover']) ?>" alt="<?= htmlspecialchars($song['title']) ?> cover" loading="lazy" decoding="async"><div><strong><?= htmlspecialchars($song['title']) ?></strong><span><?= htmlspecialchars($track['source_mode'] === 'full' ? 'Full track' : 'Preview') ?> · <?= htmlspecialchars($song['artist']) ?></span></div><div class="sf-mini-progress"><span style="width:<?= [22,34,58][$i] ?? 40 ?>%"></span></div><button type="button" data-sf-play-song data-song-id="<?= (int)$track['id'] ?>" data-title="<?= htmlspecialchars($track['title']) ?>" data-artist="<?= htmlspecialchars($track['artist']) ?>" data-src="<?= htmlspecialchars($track['src']) ?>" data-cover="<?= htmlspecialchars($track['cover']) ?>" data-url="<?= htmlspecialchars($track['url']) ?>" data-duration="<?= htmlspecialchars($track['duration']) ?>" data-duration-seconds="<?= (int)$track['duration_seconds'] ?>" data-source-mode="<?= htmlspecialchars($track['source_mode']) ?>" aria-label="Play <?= htmlspecialchars($track['title']) ?>">▶</button></article>
          <?php endforeach; ?>
        </div>
      </section>

      <section id="playlists" class="sf-home-section sf-last-section" aria-labelledby="playlists-title"><div class="sf-section-title"><h2 id="playlists-title">Your Playlists</h2><a href="<?= sf_url('playlists.php') ?>">View all</a></div><div class="sf-playlist-grid"><?php foreach ($playlistCards as $playlist): ?><a class="sf-playlist-card" href="<?= sf_url($playlist['url']) ?>"><img src="<?= sf_asset($playlist['cover']) ?>" alt="<?= htmlspecialchars($playlist['title']) ?> cover" loading="lazy" decoding="async"><strong><?= htmlspecialchars($playlist['title']) ?></strong><span><?= htmlspecialchars($playlist['songs']) ?></span></a><?php endforeach; ?><a class="sf-playlist-card sf-create-card" href="<?= sf_url('playlists.php') ?>"><span aria-hidden="true">＋</span><strong>Create Playlist</strong></a></div></section>
    </main>

    <footer class="sf-now-player" data-sf-player aria-label="Now playing controls">
      <div class="sf-now-track"><img data-sf-player-cover src="<?= htmlspecialchars($featuredTrack['cover'] ?? sf_asset($featuredSong['cover'] ?? 'images/music/soundtrack-cover.png')) ?>" alt="<?= htmlspecialchars($featuredTrack['title'] ?? $featuredSong['title'] ?? 'Stonefellow') ?> cover" decoding="async"><div><a data-sf-player-link href="<?= htmlspecialchars($featuredTrack['url'] ?? '#') ?>" data-sf-player-title><?= htmlspecialchars($featuredTrack['title'] ?? $featuredSong['title'] ?? 'Stonefellow') ?></a><span data-sf-player-artist><?= htmlspecialchars($featuredTrack['artist'] ?? $featuredSong['artist'] ?? 'Stonefellow') ?></span></div></div>
      <button class="sf-now-like" type="button" data-sf-save aria-label="Save current song to library">♡</button>
      <div class="sf-now-controls"><div class="sf-control-row"><button type="button" data-sf-shuffle aria-label="Shuffle playback">⌘</button><button type="button" data-sf-prev aria-label="Previous track">◀</button><button class="sf-now-play" type="button" data-sf-player-toggle aria-label="Play or pause">Ⅱ</button><button type="button" data-sf-next aria-label="Next track">▶</button><button type="button" data-sf-repeat aria-label="Repeat track">↻</button></div><div class="sf-now-progress"><span data-sf-current>0:00</span><div><i data-sf-progress></i></div><span data-sf-duration><?= htmlspecialchars($featuredTrack['duration'] ?? $featuredSong['duration'] ?? '0:00') ?></span></div></div>
      <div class="sf-now-tools" aria-label="Playback status"><span><?= htmlspecialchars($featuredTrack['source_mode'] ?? 'full') ?></span><span aria-hidden="true">▤</span><span aria-hidden="true">♩</span><div class="sf-volume" aria-label="Volume level"><i></i></div></div>
    </footer>
  </section>
  <script>window.STONEFELLOW_TRACKS = <?= json_encode($trackPayloads, JSON_UNESCAPED_SLASHES) ?>; window.STONEFELLOW_RUNTIME = {libraryApi: "<?= sf_url('api/library.php') ?>", playlistApi: "<?= sf_url('api/playlist.php') ?>"};</script>
  <script defer src="<?= sf_asset('js/stonefellow.js') ?>"></script>
  <script defer src="<?= sf_asset('js/member-runtime.js') ?>"></script>
  <script defer src="<?= sf_asset('js/frontend-quality.js') ?>"></script>
</body>
</html>
