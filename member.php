<?php
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/membership.php';

$user = sf_require_login();
$member = sf_member_snapshot();
$pageTitle = 'Member Dashboard';
$pageDescription = 'Stonefellow member dashboard for video progress, audio history, playlists, and access status.';
$pageClass = 'member-dashboard-page membership-page';
require __DIR__ . '/includes/header.php';
?>
<section class="sf-membership-shell">
  <section class="sf-member-hero">
    <div>
      <span class="sf-panel-eyebrow">Membership</span>
      <h1>Your Stonefellow home base</h1>
      <p>One place for member access, episode progress, audio play tracking, private playlists, and premium video unlocks.</p>
      <div class="sf-episode-action-row">
        <a class="sf-primary-action" href="<?= sf_url('episodes.php') ?>">Continue Watching</a>
        <a class="sf-secondary-action" href="<?= sf_url('player.php') ?>">Open Music Player</a>
        <a class="sf-secondary-action" href="<?= sf_url('account-billing.php') ?>">Billing</a>
      </div>
    </div>
    <article class="sf-member-status-card">
      <span>Current Access</span>
      <strong><?= htmlspecialchars($member['access_label']) ?></strong>
      <small><?= $member['can_watch_episodes'] ? 'Full episode playback enabled' : 'Subscribe to unlock full video and playlist features' ?></small>
      <a href="<?= sf_url('account-billing.php') ?>">Manage Billing</a>
    </article>
  </section>

  <section class="sf-member-grid">
    <article class="sf-member-panel">
      <span class="sf-panel-eyebrow">Video</span>
      <h2>Episode progress</h2>
      <p>Watch pages send play, pause, progress, seek, and complete events to the video tracking API.</p>
      <a href="<?= sf_url('watch.php?slug=first-to-fall-full-episode') ?>">Watch Pilot</a>
    </article>
    <article class="sf-member-panel">
      <span class="sf-panel-eyebrow">Audio</span>
      <h2>Play tracking</h2>
      <p>The streaming player sends play events and resume position to the audio tracking endpoint.</p>
      <a href="<?= sf_url('song.php?slug=born-to-burn') ?>">Play Featured Song</a>
    </article>
    <article class="sf-member-panel">
      <span class="sf-panel-eyebrow">Playlists</span>
      <h2>Member library</h2>
      <p>Private playlists are limited to signed-in members and stored against the user account.</p>
      <a href="<?= sf_url('playlists.php') ?>">Manage Playlists</a>
    </article>
  </section>

  <section class="sf-member-section">
    <div class="sf-member-section-head">
      <div><span class="sf-panel-eyebrow">Continue Watching</span><h2>Resume queue</h2></div>
      <a href="<?= sf_url('episodes.php') ?>">All episodes</a>
    </div>
    <div class="sf-video-card-grid">
      <?php foreach (array_slice($videoCatalog, 0, 3) as $video): ?>
        <a class="sf-video-card" href="<?= sf_url('watch.php?slug=' . urlencode($video['slug'])) ?>">
          <img src="<?= sf_asset($video['poster']) ?>" alt="<?= htmlspecialchars($video['title']) ?> poster">
          <span><?= htmlspecialchars(sf_access_label($video['access_level'])) ?></span>
          <strong><?= htmlspecialchars($video['title']) ?></strong>
          <small><?= htmlspecialchars($video['runtime']) ?> · <?= htmlspecialchars($video['status']) ?></small>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="sf-member-section">
    <div class="sf-member-section-head">
      <div><span class="sf-panel-eyebrow">Private Playlists</span><h2>Member playlists</h2></div>
      <a href="<?= sf_url('playlists.php') ?>">Create Playlist</a>
    </div>
    <div class="sf-playlist-grid">
      <?php foreach ($memberPlaylists as $playlist): ?>
        <article class="sf-member-playlist-card">
          <img src="<?= sf_asset($playlist['cover']) ?>" alt="<?= htmlspecialchars($playlist['title']) ?> cover">
          <div><strong><?= htmlspecialchars($playlist['title']) ?></strong><span><?= (int)$playlist['song_count'] ?> saved items · <?= htmlspecialchars($playlist['visibility']) ?></span></div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
