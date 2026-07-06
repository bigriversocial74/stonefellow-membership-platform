<?php
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/membership.php';

$user = sf_require_login();
$member = sf_member_snapshot();
$canManagePlaylists = !empty($member['can_manage_playlists']);
$pageTitle = 'Member Playlists';
$pageDescription = 'Stonefellow private playlist manager for paying members.';
$pageClass = 'member-playlists-page membership-page';
require __DIR__ . '/includes/header.php';
?>
<section class="sf-membership-shell">
  <section class="sf-member-hero sf-playlist-hero">
    <div>
      <span class="sf-panel-eyebrow">Private Member Library</span>
      <h1>Playlists for paying members</h1>
      <p>Create personal Stonefellow playlists, save songs from the player, and keep member-only queues tied to the account.</p>
    </div>
    <form class="sf-playlist-create-card" data-sf-playlist-form>
      <label for="playlist-title">New Playlist</label>
      <input id="playlist-title" name="title" type="text" placeholder="Road trip setlist" required>
      <textarea name="description" placeholder="Optional description"></textarea>
      <?php if ($canManagePlaylists): ?>
        <button type="submit">Create Playlist</button>
      <?php else: ?>
        <a class="sf-primary-action" href="<?= sf_url('subscribe.php') ?>">Unlock Playlists</a>
      <?php endif; ?>
      <p data-sf-playlist-message><?= $canManagePlaylists ? 'Connected to paying member account.' : 'A paid membership is required for private playlist saves.' ?></p>
    </form>
  </section>

  <section class="sf-member-section">
    <div class="sf-member-section-head">
      <div><span class="sf-panel-eyebrow">Your Library</span><h2>Saved playlists</h2></div>
      <a href="<?= sf_url('player.php') ?>">Open Player</a>
    </div>
    <div class="sf-playlist-grid" data-sf-playlist-list>
      <?php foreach ($memberPlaylists as $playlist): ?>
        <article class="sf-member-playlist-card">
          <img src="<?= sf_asset($playlist['cover']) ?>" alt="<?= htmlspecialchars($playlist['title']) ?> cover">
          <div>
            <strong><?= htmlspecialchars($playlist['title']) ?></strong>
            <span><?= (int)$playlist['song_count'] ?> saved items · <?= htmlspecialchars($playlist['visibility']) ?></span>
            <p><?= htmlspecialchars($playlist['description']) ?></p>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="sf-member-section">
    <div class="sf-member-section-head">
      <div><span class="sf-panel-eyebrow">Add Music</span><h2>Available songs</h2></div>
      <a href="<?= sf_url('album.php?slug=' . urlencode($musicAlbum['slug'])) ?>">Album</a>
    </div>
    <div class="sf-audio-list-panel">
      <?php foreach ($catalogSongs as $song): ?>
        <article class="sf-audio-list-row">
          <img src="<?= sf_asset($song['cover']) ?>" alt="<?= htmlspecialchars($song['title']) ?> cover">
          <span><?= htmlspecialchars($song['track']) ?></span>
          <a href="<?= sf_url('song.php?slug=' . urlencode($song['slug'])) ?>"><strong><?= htmlspecialchars($song['title']) ?></strong></a>
          <?php if ($canManagePlaylists): ?><button type="button" data-sf-playlist-add data-song-id="<?= (int)$song['id'] ?>">＋ Add</button><?php else: ?><a href="<?= sf_url('subscribe.php') ?>">Unlock</a><?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
