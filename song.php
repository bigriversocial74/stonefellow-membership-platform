<?php
require __DIR__ . '/includes/membership.php';
require __DIR__ . '/includes/data.php';
$member = sf_member_snapshot();
$canStreamFullMusic = !empty($member['can_stream_full_music']);
$slug = $_GET['slug'] ?? 'born-to-burn';
$currentSong = sf_song_by_slug($catalogSongs, $slug) ?? ($catalogSongs[0] ?? null);
$pageTitle = $currentSong['title'] ?? 'Song';
$pageDescription = 'Stonefellow full song detail page with track player, lyrics, and sticky footer audio controls.';
$pageClass = 'music-app-page music-detail-full-page music-song-full-page';
$lyrics = [
  'We were born to burn',
  'Chasing down the sun',
  'Through the fire, through the rain',
  "We're still standing here",
  '',
  'No looking back',
  "We're on the attack",
  'This is our time, this is our fate',
  'We were born to burn',
  'We were born to burn',
];
$currentIndex = 0;
foreach ($catalogSongs as $i => $song) {
  if (($song['slug'] ?? '') === ($currentSong['slug'] ?? '')) {
    $currentIndex = $i;
    break;
  }
}
$upNextSongs = array_slice(array_merge(array_slice($catalogSongs, $currentIndex + 1), array_slice($catalogSongs, 0, $currentIndex)), 0, 5);
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
      <a href="<?= sf_url('album.php?slug=' . urlencode($musicAlbum['slug'])) ?>"><?= htmlspecialchars($musicAlbum['title']) ?></a>
      <span>/</span>
      <strong><?= htmlspecialchars($currentSong['title']) ?></strong>
    </div>
    <div class="sf-detail-actions">
      <a href="<?= sf_url('album.php?slug=' . urlencode($musicAlbum['slug'])) ?>">Album</a>
      <a href="<?= sf_url('player.php') ?>">Open Player</a>
    </div>
  </header>

  <section class="sf-song-main sf-page-panel">
    <a class="sf-back-link" href="<?= sf_url('album.php?slug=' . urlencode($musicAlbum['slug'])) ?>" aria-label="Back to album">←</a>
    <section class="sf-song-full-layout">
      <div class="sf-track-stage">
        <img class="sf-track-cover" src="<?= sf_asset($currentSong['cover']) ?>" alt="<?= htmlspecialchars($currentSong['title']) ?> cover">
        <div class="sf-track-title-row">
          <div>
            <span class="sf-track-kicker">Track <?= htmlspecialchars($currentSong['track']) ?> · <?= htmlspecialchars($currentSong['episode_short'] ?? $currentSong['episode']) ?></span>
            <h1><?= htmlspecialchars($currentSong['title']) ?></h1>
            <a href="<?= sf_url('album.php?slug=' . urlencode($musicAlbum['slug'])) ?>"><?= htmlspecialchars($currentSong['artist']) ?></a>
          </div>
          <div class="sf-song-actions"><button type="button" data-sf-save aria-label="Save song">♡</button><button type="button" aria-label="More options">…</button></div>
        </div>
        <div class="sf-track-progress"><div><i data-sf-progress style="width:36%"></i></div><span><b data-sf-current>1:24</b><b data-sf-duration><?= htmlspecialchars($currentSong['duration']) ?></b></span></div>
        <div class="sf-track-controls">
          <button type="button">⌘</button><button type="button" data-sf-prev>◀</button>
          <button class="sf-track-play" type="button" data-sf-play-song
            data-song-id="<?= (int)$currentSong['id'] ?>"
            data-title="<?= htmlspecialchars($currentSong['title']) ?>"
            data-artist="<?= htmlspecialchars($currentSong['artist']) ?>"
            data-src="<?= sf_asset(sf_song_audio_src($currentSong, $canStreamFullMusic)) ?>"
            data-cover="<?= sf_asset($currentSong['cover']) ?>"
            data-url="<?= sf_url('song.php?slug=' . urlencode($currentSong['slug'])) ?>"
            data-duration="<?= htmlspecialchars($currentSong['duration']) ?>">Ⅱ</button>
          <button type="button" data-sf-next>▶</button><button type="button">↻</button>
        </div>
      </div>

      <aside class="sf-song-side-panel">
        <section class="sf-lyrics-card">
          <h2>Lyrics</h2>
          <p><?php foreach ($lyrics as $line): ?><?= $line === '' ? '<br>' : htmlspecialchars($line) . '<br>' ?><?php endforeach; ?></p>
        </section>

        <section class="sf-up-next-card">
          <div class="sf-up-next-head"><h2>Up Next</h2><a href="<?= sf_url('album.php?slug=' . urlencode($musicAlbum['slug'])) ?>">View Album</a></div>
          <?php foreach ($upNextSongs as $song): ?>
            <article class="sf-up-next-row" data-sf-track-row>
              <img src="<?= sf_asset($song['cover']) ?>" alt="<?= htmlspecialchars($song['title']) ?> cover">
              <a href="<?= sf_url('song.php?slug=' . urlencode($song['slug'])) ?>"><strong><?= htmlspecialchars($song['title']) ?></strong><span><?= htmlspecialchars($song['duration']) ?> · <?= htmlspecialchars($song['episode_short'] ?? $song['episode']) ?></span></a>
              <button type="button" data-sf-play-song
                data-song-id="<?= (int)$song['id'] ?>"
                data-title="<?= htmlspecialchars($song['title']) ?>"
                data-artist="<?= htmlspecialchars($song['artist']) ?>"
                data-src="<?= sf_asset(sf_song_audio_src($song, $canStreamFullMusic)) ?>"
                data-cover="<?= sf_asset($song['cover']) ?>"
                data-url="<?= sf_url('song.php?slug=' . urlencode($song['slug'])) ?>"
                data-duration="<?= htmlspecialchars($song['duration']) ?>">▶</button>
            </article>
          <?php endforeach; ?>
        </section>
      </aside>
    </section>
  </section>

  <footer class="sf-now-player" data-sf-player>
    <div class="sf-now-track">
      <img data-sf-player-cover src="<?= sf_asset($currentSong['cover']) ?>" alt="<?= htmlspecialchars($currentSong['title']) ?> cover">
      <div><a data-sf-player-link href="<?= sf_url('song.php?slug=' . urlencode($currentSong['slug'])) ?>" data-sf-player-title><?= htmlspecialchars($currentSong['title']) ?></a><span data-sf-player-artist><?= htmlspecialchars($currentSong['artist']) ?></span></div>
    </div>
    <button class="sf-now-like" type="button" data-sf-save>♡</button>
    <div class="sf-now-controls">
      <div class="sf-control-row"><button type="button">⌘</button><button type="button" data-sf-prev>◀</button><button class="sf-now-play" type="button" data-sf-player-toggle>Ⅱ</button><button type="button" data-sf-next>▶</button><button type="button">↻</button></div>
      <div class="sf-now-progress"><span data-sf-current>0:00</span><div><i data-sf-progress></i></div><span data-sf-duration><?= htmlspecialchars($currentSong['duration']) ?></span></div>
    </div>
    <div class="sf-now-tools"><span>▣</span><span>▤</span><span>♩</span><div class="sf-volume"><i></i></div></div>
  </footer>
</section>
<script>window.STONEFELLOW_TRACKS = <?= $tracksJson ?>;</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
