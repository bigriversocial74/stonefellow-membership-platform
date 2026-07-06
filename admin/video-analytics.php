<?php
$pageTitle = 'Video Analytics';
$pageDescription = 'Stonefellow video analytics for watch time, completions, episode progress, and recent tracking events.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_analytics.php';
require __DIR__ . '/../includes/header.php';

$days = sf_analytics_days();
$overview = sf_analytics_overview($days);
$topVideos = sf_analytics_video_top_videos($days, 25);
$events = sf_analytics_video_events($days, 60);

sf_admin_shell_start('Video Analytics', 'Episode watch performance', 'Measure starts, watch time, completions, episode engagement, and recent video tracking events across the selected date range.', 'video-analytics');
sf_analytics_range_tabs($days);
?>
<section class="sf-admin-card-grid sf-analytics-card-grid">
  <?php sf_analytics_metric_card('Video Events', number_format((int)$overview['video']['events']), 'Tracked API events'); ?>
  <?php sf_analytics_metric_card('Watch Time', sf_analytics_time((int)$overview['video']['seconds']), 'Total video time'); ?>
  <?php sf_analytics_metric_card('Videos Watched', number_format((int)$overview['video']['videos']), 'Distinct videos'); ?>
  <?php sf_analytics_metric_card('Viewers', number_format((int)$overview['video']['viewers']), 'Distinct signed-in members'); ?>
  <?php sf_analytics_metric_card('Completions', number_format((int)$overview['video']['completes']), sf_analytics_percent($overview['video']['completes'], $overview['video']['events']) . ' of events'); ?>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Top Videos</span><h2>Ranked by watch time</h2></div><a href="<?= sf_url('admin/videos.php') ?>">Manage Videos</a></div>
  <div class="sf-admin-table-wrap">
    <table class="sf-admin-table">
      <thead><tr><th>Video</th><th>Episode</th><th>Events</th><th>Viewers</th><th>Watch Time</th><th>Completions</th><th>Avg %</th></tr></thead>
      <tbody>
        <?php foreach ($topVideos as $video): ?>
          <tr>
            <td><a href="<?= sf_url('admin/videos.php?edit=' . (int)($video['video_id'] ?? 0)) ?>"><?= sf_admin_h($video['title'] ?? '') ?></a></td>
            <td><?= sf_admin_h($video['episode_title'] ?? 'Standalone') ?></td>
            <td><?= number_format((int)($video['events'] ?? 0)) ?></td>
            <td><?= number_format((int)($video['viewers'] ?? 0)) ?></td>
            <td><?= sf_analytics_time((int)($video['seconds'] ?? 0)) ?></td>
            <td><?= number_format((int)($video['completes'] ?? 0)) ?></td>
            <td><?= number_format((float)($video['avg_percent'] ?? 0), 1) ?>%</td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Recent Events</span><h2>Latest video tracking payloads</h2></div><a href="<?= sf_url('api/video-track.php') ?>">API Endpoint</a></div>
  <div class="sf-admin-table-wrap">
    <table class="sf-admin-table">
      <thead><tr><th>Time</th><th>Video</th><th>Episode</th><th>Member</th><th>Event</th><th>Position</th><th>Watched</th><th>Page</th></tr></thead>
      <tbody>
        <?php foreach ($events as $event): ?>
          <tr>
            <td><?= sf_admin_h($event['created_at'] ?? '') ?></td>
            <td><?= sf_admin_h($event['video_title'] ?? '') ?></td>
            <td><?= sf_admin_h($event['episode_title'] ?? '—') ?></td>
            <td><?= sf_admin_h($event['display_name'] ?: $event['email'] ?: 'Guest') ?></td>
            <td><?= sf_admin_status_badge((string)($event['event_type'] ?? 'play')) ?></td>
            <td><?= sf_analytics_time((int)($event['position_seconds'] ?? 0)) ?></td>
            <td><?= sf_analytics_time((int)($event['seconds_watched'] ?? 0)) ?></td>
            <td><?= sf_admin_h($event['source_page'] ?? '—') ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$events): ?><tr><td colspan="8">No video tracking events yet. Events will appear after the watch page posts to <code>api/video-track.php</code>.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php
sf_admin_shell_end();
require __DIR__ . '/../includes/footer.php';
?>
