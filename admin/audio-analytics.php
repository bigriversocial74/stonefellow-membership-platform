<?php
$pageTitle = 'Audio Analytics';
$pageDescription = 'Stonefellow song-level audio analytics for plays, listeners, seconds streamed, completions, and recent events.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_analytics.php';
require __DIR__ . '/../includes/header.php';

$days = sf_analytics_days();
$overview = sf_analytics_overview($days);
$topSongs = sf_analytics_audio_top_songs($days, 25);
$events = sf_analytics_audio_events($days, 60);

sf_admin_shell_start('Audio Analytics', 'Song performance', 'Measure plays, progress, completion, listener activity, and soundtrack engagement across the selected date range.', 'audio-analytics');
sf_analytics_range_tabs($days);
?>
<section class="sf-admin-card-grid sf-analytics-card-grid">
  <?php sf_analytics_metric_card('Audio Events', number_format((int)$overview['audio']['events']), 'Tracked API events'); ?>
  <?php sf_analytics_metric_card('Seconds Streamed', sf_analytics_time((int)$overview['audio']['seconds']), 'Total audio time'); ?>
  <?php sf_analytics_metric_card('Songs Played', number_format((int)$overview['audio']['songs']), 'Distinct songs'); ?>
  <?php sf_analytics_metric_card('Listeners', number_format((int)$overview['audio']['listeners']), 'Distinct signed-in members'); ?>
  <?php sf_analytics_metric_card('Completions', number_format((int)$overview['audio']['completes']), sf_analytics_percent($overview['audio']['completes'], $overview['audio']['events']) . ' of events'); ?>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Top Songs</span><h2>Ranked by streamed time</h2></div><a href="<?= sf_url('admin/music-songs.php') ?>">Manage Songs</a></div>
  <div class="sf-admin-table-wrap">
    <table class="sf-admin-table">
      <thead><tr><th>Song</th><th>Album</th><th>Events</th><th>Listeners</th><th>Time</th><th>Completions</th><th>Avg %</th></tr></thead>
      <tbody>
        <?php foreach ($topSongs as $song): ?>
          <tr>
            <td><a href="<?= sf_url('admin/music-songs.php?edit=' . (int)($song['song_id'] ?? 0)) ?>"><?= sf_admin_h($song['title'] ?? '') ?></a></td>
            <td><?= sf_admin_h($song['album_title'] ?? '—') ?></td>
            <td><?= number_format((int)($song['events'] ?? 0)) ?></td>
            <td><?= number_format((int)($song['listeners'] ?? 0)) ?></td>
            <td><?= sf_analytics_time((int)($song['seconds'] ?? 0)) ?></td>
            <td><?= number_format((int)($song['completes'] ?? 0)) ?></td>
            <td><?= number_format((float)($song['avg_percent'] ?? 0), 1) ?>%</td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Recent Events</span><h2>Latest audio tracking payloads</h2></div><a href="<?= sf_url('api/audio-track.php') ?>">API Endpoint</a></div>
  <div class="sf-admin-table-wrap">
    <table class="sf-admin-table">
      <thead><tr><th>Time</th><th>Song</th><th>Member</th><th>Event</th><th>Position</th><th>Seconds</th><th>Page</th></tr></thead>
      <tbody>
        <?php foreach ($events as $event): ?>
          <tr>
            <td><?= sf_admin_h($event['created_at'] ?? '') ?></td>
            <td><?= sf_admin_h($event['song_title'] ?? '') ?></td>
            <td><?= sf_admin_h($event['display_name'] ?: $event['email'] ?: 'Guest') ?></td>
            <td><?= sf_admin_status_badge((string)($event['event_type'] ?? 'play')) ?></td>
            <td><?= sf_analytics_time((int)($event['position_seconds'] ?? 0)) ?></td>
            <td><?= sf_analytics_time((int)($event['seconds_played'] ?? 0)) ?></td>
            <td><?= sf_admin_h($event['source_page'] ?? '—') ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$events): ?><tr><td colspan="7">No audio tracking events yet. Events will appear after the player posts to <code>api/audio-track.php</code>.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php
sf_admin_shell_end();
require __DIR__ . '/../includes/footer.php';
?>
