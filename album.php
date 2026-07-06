<?php
$pageTitle = 'The Road Is Calling';
$pageDescription = 'Stonefellow full album page with soundtrack tracklist, album details, and sticky streaming player.';
$pageClass = 'music-app-page music-detail-full-page music-album-full-page';
require __DIR__ . '/includes/membership.php';
require __DIR__ . '/includes/data.php';
$member = sf_member_snapshot();
$canStreamFullMusic = !empty($member['can_stream_full_music']);

$featuredSong = $catalogSongs[0] ?? null;
$albumMinutes = sf_music_minutes($catalogSongs);
$tracksJson = json_encode(array_map(function($song) use ($canStreamFullMusic) {
  return [
    'id' => $song['id'],
    'title' => $song['title'],
    'artist' => $song['artist'],
    'src' => sf_asset(sf_song_audio_src($song, $canStreamFullMusic)),
    'cover' => sf_asset($song['cover']),
    'url' => sf_url('song.php?slug=' . urlencode($song['slug'])),
    'duration' => $song['duration'],
  ];
}, $catalogSongs), JSON_UNESCAPED_SLASHES);
require __DIR__ . '/includes/header.php';
?>
<section class="sf-stream-shell sf-focused-shell sf-full-detail-shell" data-sf-music-app>
  <audio data-sf-audio preload="metadata"></audio>

  <header class="sf-detail-topbar">
    <div class="sf-detail-breadcrumb">
      <a href="<?= sf_url('music.php') ?>">Music</a>
      <span>/</span>
      <a href="<?= sf_url('player.php') ?>">Full Player</a>
      <span>/</span>
      <strong><?= htmlspecialchars($musicAlbum['title']) ?></strong>
    </div>
    <div class="sf-detail-actions">
      <a href="<?= sf_url('player.php') ?>">Open Player</a>
      <a href="<?= sf_url('subscribe.php') ?>">Subscribe</a>
    </div>
  </header>

  <section class="sf-album-main sf-page-panel">
    <a class="sf-back-link" href="<?= sf_url('player.php') ?>" aria-label="Back to player">←</a>
    <section class="sf-album-hero">
      <img class="sf-album-cover-large" src="<?= sf_asset($musicAlbum['cover']) ?>" alt="<?= htmlspecialchars($musicAlbum['title']) ?> album cover">
      <div class="sf-album-copy">
        <span>Official Album</span>
        <h1><?= htmlspecialchars($musicAlbum['title']) ?></h1>
        <div class="sf-album-artist"><img src="<?= sf_asset('images/brand/logo-mark.png') ?>" alt="Stonefellow mark"><strong><?= htmlspecialchars($musicAlbum['artist']) ?></strong></div>
        <p><?= htmlspecialchars($musicAlbum['year']) ?> · <?= count($catalogSongs) ?> songs, <?= (int)$albumMinutes ?> min · Public previews / subscriber streaming</p>
        <div class="sf-album-description">
          <p>The full album page now lives as its own site page with the main Stonefellow header, footer, album hero, soundtrack track list, and persistent player controls.</p>
        </div>
      </div>
    </section>

    <section class="sf-album-actions">
      <button class="sf-album-play" type="button" data-sf-play-song
        data-song-id="<?= (int)$featuredSong['id'] ?>"
        data-title="<?= htmlspecialchars($featuredSong['title']) ?>"
        data-artist="<?= htmlspecialchars($featuredSong['artist']) ?>"
        data-src="<?= sf_asset(sf_song_audio_src($featuredSong, $canStreamFullMusic)) ?>"
        data-cover="<?= sf_asset($featuredSong['cover']) ?>"
        data-url="<?= sf_url('song.php?slug=' . urlencode($featuredSong['slug'])) ?>"
        data-duration="<?= htmlspecialchars($featuredSong['duration']) ?>">▶ Play</button>
      <button class="sf-album-icon" type="button" data-sf-save aria-label="Save album">♡</button>
      <a class="sf-album-subscribe" href="<?= sf_url('subscribe.php') ?>">Unlock full tracks</a>
    </section>

    <section class="sf-album-table" aria-label="Album songs">
      <div class="sf-album-table-head"><span>#</span><span>Title</span><span>Time</span></div>
      <?php foreach ($catalogSongs as $i => $song): ?>
        <article class="sf-album-row <?= $i === 0 ? 'is-current' : '' ?>" data-sf-track-row>
          <button type="button" class="sf-row-play" data-sf-play-song
            data-song-id="<?= (int)$song['id'] ?>"
                data-title="<?= htmlspecialchars($song['title']) ?>"
            data-artist="<?= htmlspecialchars($song['artist']) ?>"
            data-src="<?= sf_asset(sf_song_audio_src($song, $canStreamFullMusic)) ?>"
            data-cover="<?= sf_asset($song['cover']) ?>"
            data-url="<?= sf_url('song.php?slug=' . urlencode($song['slug'])) ?>"
            data-duration="<?= htmlspecialchars($song['duration']) ?>"><?= $i === 0 ? '▮▮' : (int)$song['track'] ?></button>
          <a class="sf-row-title" href="<?= sf_url('song.php?slug=' . urlencode($song['slug'])) ?>"><strong><?= htmlspecialchars($song['title']) ?></strong><span><?= htmlspecialchars($song['artist']) ?> · <?= htmlspecialchars($song['episode_short'] ?? $song['episode']) ?></span></a>
          <em><?= htmlspecialchars($song['duration']) ?></em>
        </article>
      <?php endforeach; ?>
    </section>
  </section>

  <footer class="sf-now-player" data-sf-player>
    <div class="sf-now-track">
      <img data-sf-player-cover src="<?= sf_asset($featuredSong['cover']) ?>" alt="<?= htmlspecialchars($featuredSong['title']) ?> cover">
      <div><a data-sf-player-link href="<?= sf_url('song.php?slug=' . urlencode($featuredSong['slug'])) ?>" data-sf-player-title><?= htmlspecialchars($featuredSong['title']) ?></a><span data-sf-player-artist><?= htmlspecialchars($featuredSong['artist']) ?></span></div>
    </div>
    <button class="sf-now-like" type="button" data-sf-save>♡</button>
    <div class="sf-now-controls">
      <div class="sf-control-row"><button type="button">⌘</button><button type="button" data-sf-prev>◀</button><button class="sf-now-play" type="button" data-sf-player-toggle>Ⅱ</button><button type="button" data-sf-next>▶</button><button type="button">↻</button></div>
      <div class="sf-now-progress"><span data-sf-current>0:00</span><div><i data-sf-progress></i></div><span data-sf-duration><?= htmlspecialchars($featuredSong['duration']) ?></span></div>
    </div>
    <div class="sf-now-tools"><span>▣</span><span>▤</span><span>♩</span><div class="sf-volume"><i></i></div></div>
  </footer>
</section>
<script>window.STONEFELLOW_TRACKS = <?= $tracksJson ?>;</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
