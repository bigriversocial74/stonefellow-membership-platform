<?php
$pageTitle = 'The Road Is Calling';
$pageDescription = 'Stonefellow full album page with soundtrack tracklist, album details, signed audio, and sticky streaming player.';
$pageClass = 'music-app-page music-detail-full-page music-album-full-page';
require __DIR__ . '/includes/audio_player.php';
$member = sf_member_snapshot();
$featuredSong = $catalogSongs[0] ?? null;
$albumMinutes = sf_music_minutes($catalogSongs);
$trackPayloads = sf_audio_tracks_payload($catalogSongs, $member);
$trackMap = sf_audio_track_map($catalogSongs, $member);
$featuredTrack = $trackMap[(int)($featuredSong['id'] ?? 0)] ?? ($trackPayloads[0] ?? []);
$tracksJson = json_encode($trackPayloads, JSON_UNESCAPED_SLASHES);
require __DIR__ . '/includes/header.php';
?>
<section class="sf-stream-shell sf-focused-shell sf-full-detail-shell" data-sf-music-app data-player-state-api="<?= sf_url('api/player-state.php') ?>">
  <audio data-sf-audio preload="metadata"></audio>

  <header class="sf-detail-topbar">
    <div class="sf-detail-breadcrumb"><a href="<?= sf_url('music.php') ?>">Music</a><span>/</span><a href="<?= sf_url('player.php') ?>">Full Player</a><span>/</span><strong><?= htmlspecialchars($musicAlbum['title']) ?></strong></div>
    <div class="sf-detail-actions"><a href="<?= sf_url('player.php') ?>">Open Player</a><a href="<?= sf_url('subscribe.php') ?>"><?= !empty($member['can_stream_full_music']) ? 'Member Active' : 'Subscribe' ?></a></div>
  </header>

  <section class="sf-album-main sf-page-panel">
    <a class="sf-back-link" href="<?= sf_url('player.php') ?>" aria-label="Back to player">←</a>
    <section class="sf-album-hero">
      <img class="sf-album-cover-large" src="<?= sf_asset($musicAlbum['cover']) ?>" alt="<?= htmlspecialchars($musicAlbum['title']) ?> album cover">
      <div class="sf-album-copy"><span>Official Album</span><h1><?= htmlspecialchars($musicAlbum['title']) ?></h1><div class="sf-album-artist"><img src="<?= sf_asset('images/brand/logo-mark.png') ?>" alt="Stonefellow mark"><strong><?= htmlspecialchars($musicAlbum['artist']) ?></strong></div><p><?= htmlspecialchars($musicAlbum['year']) ?> · <?= count($catalogSongs) ?> songs, <?= (int)$albumMinutes ?> min · <?= !empty($member['can_stream_full_music']) ? 'signed full-track streaming' : 'public previews' ?></p><div class="sf-album-description"><p>The album page now uses the Audio Player v2 signed track payload so album playback follows the same entitlement boundary as the full player.</p></div></div>
    </section>

    <section class="sf-album-actions">
      <button class="sf-album-play" type="button" data-sf-play-song data-song-id="<?= (int)($featuredTrack['id'] ?? 0) ?>" data-title="<?= htmlspecialchars($featuredTrack['title'] ?? '') ?>" data-artist="<?= htmlspecialchars($featuredTrack['artist'] ?? '') ?>" data-src="<?= htmlspecialchars($featuredTrack['src'] ?? '') ?>" data-cover="<?= htmlspecialchars($featuredTrack['cover'] ?? '') ?>" data-url="<?= htmlspecialchars($featuredTrack['url'] ?? '') ?>" data-duration="<?= htmlspecialchars($featuredTrack['duration'] ?? '') ?>" data-source-mode="<?= htmlspecialchars($featuredTrack['source_mode'] ?? 'preview') ?>">▶ Play</button>
      <button class="sf-album-icon" type="button" data-sf-save aria-label="Save album">♡</button>
      <a class="sf-album-subscribe" href="<?= sf_url('subscribe.php') ?>"><?= !empty($member['can_stream_full_music']) ? 'Full tracks unlocked' : 'Unlock full tracks' ?></a>
    </section>

    <section class="sf-album-table" aria-label="Album songs">
      <div class="sf-album-table-head"><span>#</span><span>Title</span><span>Time</span></div>
      <?php foreach ($catalogSongs as $i => $song): $track = $trackMap[(int)$song['id']] ?? sf_audio_track_payload($song, $member); ?>
        <article class="sf-album-row <?= $i === 0 ? 'is-current' : '' ?>" data-sf-track-row>
          <button type="button" class="sf-row-play" data-sf-play-song data-song-id="<?= (int)$track['id'] ?>" data-title="<?= htmlspecialchars($track['title']) ?>" data-artist="<?= htmlspecialchars($track['artist']) ?>" data-src="<?= htmlspecialchars($track['src']) ?>" data-cover="<?= htmlspecialchars($track['cover']) ?>" data-url="<?= htmlspecialchars($track['url']) ?>" data-duration="<?= htmlspecialchars($track['duration']) ?>" data-source-mode="<?= htmlspecialchars($track['source_mode']) ?>"><?= $i === 0 ? '▮▮' : (int)$song['track'] ?></button>
          <a class="sf-row-title" href="<?= sf_url('song.php?slug=' . urlencode($song['slug'])) ?>"><strong><?= htmlspecialchars($song['title']) ?></strong><span><?= htmlspecialchars($song['artist']) ?> · <?= htmlspecialchars($track['source_mode'] === 'full' ? 'Full stream' : 'Preview') ?></span></a>
          <em><?= htmlspecialchars($song['duration']) ?></em>
        </article>
      <?php endforeach; ?>
    </section>
  </section>

  <footer class="sf-now-player" data-sf-player>
    <div class="sf-now-track"><img data-sf-player-cover src="<?= htmlspecialchars($featuredTrack['cover'] ?? '') ?>" alt="<?= htmlspecialchars($featuredTrack['title'] ?? '') ?> cover"><div><a data-sf-player-link href="<?= htmlspecialchars($featuredTrack['url'] ?? '#') ?>" data-sf-player-title><?= htmlspecialchars($featuredTrack['title'] ?? '') ?></a><span data-sf-player-artist><?= htmlspecialchars($featuredTrack['artist'] ?? '') ?></span></div></div>
    <button class="sf-now-like" type="button" data-sf-save>♡</button>
    <div class="sf-now-controls"><div class="sf-control-row"><button type="button" data-sf-shuffle>⌘</button><button type="button" data-sf-prev>◀</button><button class="sf-now-play" type="button" data-sf-player-toggle>Ⅱ</button><button type="button" data-sf-next>▶</button><button type="button" data-sf-repeat>↻</button></div><div class="sf-now-progress"><span data-sf-current>0:00</span><div><i data-sf-progress></i></div><span data-sf-duration><?= htmlspecialchars($featuredTrack['duration'] ?? '') ?></span></div></div>
    <div class="sf-now-tools"><span><?= htmlspecialchars($featuredTrack['source_mode'] ?? 'preview') ?></span><span>▤</span><span>♩</span><div class="sf-volume"><i></i></div></div>
  </footer>
</section>
<script>window.STONEFELLOW_TRACKS = <?= $tracksJson ?>;</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
