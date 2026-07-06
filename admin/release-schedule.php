<?php
$pageTitle = 'Release Schedule';
$pageDescription = 'Schedule episode and video release windows.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require __DIR__ . '/../includes/header.php';
$episodes = sf_admin_episodes();
$videos = sf_admin_videos();
sf_admin_shell_start('Release Schedule', 'Episode and video calendar', 'Review publish status, release dates, access levels, and watch-next gaps before launch.', 'release-schedule');
?>
<section class="sf-admin-card-grid">
  <a class="sf-admin-action-card" href="<?= sf_url('admin/episodes.php') ?>"><span>Episodes</span><strong><?= count($episodes) ?> records</strong><small>Edit story metadata and release windows.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/videos.php') ?>"><span>Videos</span><strong><?= count($videos) ?> records</strong><small>Edit stream files and access gates.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/seasons.php') ?>"><span>Seasons</span><strong>Season manager</strong><small>Group episode arcs and poster art.</small></a>
</section>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Episode Calendar</span><h2>Release readiness</h2></div></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Episode</th><th>Release</th><th>Access</th><th>Status</th><th>Runtime</th></tr></thead><tbody>
    <?php foreach ($episodes as $ep): ?><tr><td><strong><?= sf_admin_h(($ep['number'] ?? ('S' . ($ep['season_number'] ?? 1) . ':E' . ($ep['episode_number'] ?? ''))) . ' ' . ($ep['title'] ?? '')) ?></strong><small><?= sf_admin_h($ep['slug'] ?? '') ?></small></td><td><?= sf_admin_h($ep['release_at'] ?? 'Unscheduled') ?></td><td><?= sf_admin_h(sf_access_label((string)($ep['access_level'] ?? 'subscriber'))) ?></td><td><?= sf_admin_status_badge((string)($ep['status'] ?? 'draft')) ?></td><td><?= sf_admin_h($ep['runtime_minutes'] ?? '—') ?> min</td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Video Calendar</span><h2>Watch-page records</h2></div></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Video</th><th>Release</th><th>Access</th><th>Status</th><th>Type</th></tr></thead><tbody>
    <?php foreach ($videos as $video): ?><tr><td><strong><?= sf_admin_h($video['title'] ?? '') ?></strong><small><?= sf_admin_h($video['slug'] ?? '') ?></small></td><td><?= sf_admin_h($video['release_at'] ?? 'Unscheduled') ?></td><td><?= sf_admin_h(sf_access_label((string)($video['access_level'] ?? 'subscriber'))) ?></td><td><?= sf_admin_status_badge((string)($video['status'] ?? 'draft')) ?></td><td><?= sf_admin_h(str_replace('_',' ',(string)($video['video_type'] ?? 'episode'))) ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
