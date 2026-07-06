<?php
$pageTitle = 'Media Catalog Dashboard';
$pageDescription = 'Media Catalog + Upload Manager v1 for albums, songs, episodes, videos, assets, membership access, and local/CDN storage.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require __DIR__ . '/../includes/header.php';

$counts = [
  'Albums' => sf_admin_table_exists('albums') ? sf_admin_count_table('albums') : count(sf_admin_albums()),
  'Songs' => sf_admin_table_exists('songs') ? sf_admin_count_table('songs') : count(sf_admin_songs()),
  'Episodes' => sf_admin_table_exists('episodes') ? sf_admin_count_table('episodes') : count(sf_admin_episodes()),
  'Videos' => sf_admin_table_exists('videos') ? sf_admin_count_table('videos') : count(sf_admin_videos()),
  'Assets' => sf_admin_table_exists('media_assets') ? sf_admin_count_table('media_assets') : 0,
  'Access Grants' => sf_admin_table_exists('content_access_grants') ? sf_admin_count_table('content_access_grants') : 0,
];

$latestSongs = array_slice(sf_admin_songs(), 0, 5);
$latestVideos = array_slice(sf_admin_videos(), 0, 5);

sf_admin_shell_start('Catalog + Upload Manager v1', 'Media catalog control center', 'Manage Stonefellow music, episodes, videos, media assets, and membership access rules from one admin foundation.', 'music');
?>
<section class="sf-admin-card-grid">
  <?php foreach ($counts as $label => $total): ?>
    <article class="sf-admin-stat-card">
      <span><?= sf_admin_h($label) ?></span>
      <strong><?= number_format((int)$total) ?></strong>
      <small><?= sf_admin_db_ready() ? 'Database table count' : 'Static preview count' ?></small>
    </article>
  <?php endforeach; ?>
</section>

<section class="sf-admin-two-col">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head">
      <div><span class="sf-panel-eyebrow">Music</span><h2>Recent songs</h2></div>
      <a href="<?= sf_url('admin/music-songs.php') ?>">Manage Songs</a>
    </div>
    <div class="sf-admin-list">
      <?php foreach ($latestSongs as $song): ?>
        <a class="sf-admin-list-row" href="<?= sf_url('admin/music-songs.php?edit=' . (int)($song['id'] ?? 0)) ?>">
          <span><?= sf_admin_h(str_pad((string)($song['track_number'] ?? $song['track'] ?? ''), 2, '0', STR_PAD_LEFT)) ?></span>
          <strong><?= sf_admin_h($song['title'] ?? '') ?></strong>
          <em><?= sf_admin_h($song['album_title'] ?? 'The Road Is Calling') ?></em>
          <?= sf_admin_status_badge((string)($song['status'] ?? 'published')) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </article>
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head">
      <div><span class="sf-panel-eyebrow">Video</span><h2>Recent videos</h2></div>
      <a href="<?= sf_url('admin/videos.php') ?>">Manage Videos</a>
    </div>
    <div class="sf-admin-list">
      <?php foreach ($latestVideos as $video): ?>
        <a class="sf-admin-list-row" href="<?= sf_url('admin/videos.php?edit=' . (int)($video['id'] ?? 0)) ?>">
          <span><?= sf_admin_h(strtoupper((string)($video['video_type'] ?? 'video'))) ?></span>
          <strong><?= sf_admin_h($video['title'] ?? '') ?></strong>
          <em><?= sf_admin_h($video['episode_title'] ?? $video['episode_slug'] ?? 'Standalone') ?></em>
          <?= sf_admin_status_badge((string)($video['status'] ?? 'draft')) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </article>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head">
    <div><span class="sf-panel-eyebrow">Build Flow</span><h2>Recommended production sequence</h2></div>
  </div>
  <div class="sf-admin-roadmap">
    <div><span>01</span><strong>Catalog</strong><p>Create albums, songs, episodes, videos, and upload/register media assets.</p></div>
    <div><span>02</span><strong>Files</strong><p>Attach uploaded/registered audio and video assets to preview/full/stream/trailer file variants.</p></div>
    <div><span>03</span><strong>Access</strong><p>Map plans and grants to subscriber, premium, and founding fan access.</p></div>
    <div><span>04</span><strong>Tracking</strong><p>Use audio/video APIs already added to record plays, progress, and completion.</p></div>
  </div>
</section>
<?php
sf_admin_shell_end();
require __DIR__ . '/../includes/footer.php';
?>
