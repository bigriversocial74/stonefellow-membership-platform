<?php
$pageTitle = 'Analytics Dashboard';
$pageDescription = 'Stonefellow analytics overview for audio plays, video watches, member activity, subscriptions, playlists, and merch revenue.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_analytics.php';
require __DIR__ . '/../includes/header.php';

$days = sf_analytics_days();
$overview = sf_analytics_overview($days);
$daily = sf_analytics_daily_activity(min($days, 30));
$topSongs = sf_analytics_audio_top_songs($days, 5);
$topVideos = sf_analytics_video_top_videos($days, 5);
$memberRows = array_slice(sf_analytics_member_activity($days, 8), 0, 8);

sf_admin_shell_start('Analytics Dashboard v1', 'Performance control center', 'Track audio, video, episodes, member activity, playlists, subscriptions, and merch revenue for the Stonefellow membership platform.', 'analytics');
sf_analytics_range_tabs($days);
?>
<section class="sf-admin-card-grid sf-analytics-card-grid">
  <?php sf_analytics_metric_card('Audio Events', number_format((int)$overview['audio']['events']), sf_analytics_time((int)$overview['audio']['seconds']) . ' streamed'); ?>
  <?php sf_analytics_metric_card('Audio Completions', number_format((int)$overview['audio']['completes']), sf_analytics_percent($overview['audio']['completes'], $overview['audio']['events']) . ' completion event rate'); ?>
  <?php sf_analytics_metric_card('Video Events', number_format((int)$overview['video']['events']), sf_analytics_time((int)$overview['video']['seconds']) . ' watched'); ?>
  <?php sf_analytics_metric_card('Video Completions', number_format((int)$overview['video']['completes']), sf_analytics_percent($overview['video']['completes'], $overview['video']['events']) . ' completion event rate'); ?>
  <?php sf_analytics_metric_card('Members', number_format((int)$overview['members']['total']), number_format((int)$overview['members']['new']) . ' new in range'); ?>
  <?php sf_analytics_metric_card('Active Subscriptions', number_format((int)$overview['members']['active_subscriptions']), number_format((int)$overview['members']['recent_logins']) . ' recent logins'); ?>
  <?php sf_analytics_metric_card('New Playlists', number_format((int)$overview['members']['playlists']), 'Created in selected range'); ?>
  <?php sf_analytics_metric_card('Merch Revenue', sf_analytics_money((int)$overview['commerce']['revenue_cents']), number_format((int)$overview['commerce']['orders']) . ' paid/fulfilled orders'); ?>
</section>

<section class="sf-admin-two-col">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head">
      <div><span class="sf-panel-eyebrow">Daily Activity</span><h2>Audio events</h2></div>
      <a href="<?= sf_analytics_url('admin/audio-analytics.php', $days) ?>">Audio Report</a>
    </div>
    <?php sf_analytics_bars($daily, 'audio'); ?>
  </article>
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head">
      <div><span class="sf-panel-eyebrow">Daily Activity</span><h2>Video events</h2></div>
      <a href="<?= sf_analytics_url('admin/video-analytics.php', $days) ?>">Video Report</a>
    </div>
    <?php sf_analytics_bars($daily, 'video'); ?>
  </article>
</section>

<section class="sf-admin-two-col">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Audio</span><h2>Top songs</h2></div><a href="<?= sf_url('admin/music-songs.php') ?>">Manage Songs</a></div>
    <div class="sf-admin-list">
      <?php foreach ($topSongs as $song): ?>
        <a class="sf-admin-list-row" href="<?= sf_url('admin/music-songs.php?edit=' . (int)($song['song_id'] ?? 0)) ?>">
          <strong><?= sf_admin_h($song['title'] ?? '') ?></strong>
          <span><?= sf_analytics_time((int)($song['seconds'] ?? 0)) ?></span>
          <em><?= number_format((int)($song['events'] ?? 0)) ?> events · <?= number_format((int)($song['listeners'] ?? 0)) ?> listeners</em>
        </a>
      <?php endforeach; ?>
    </div>
  </article>
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Video</span><h2>Top videos</h2></div><a href="<?= sf_url('admin/videos.php') ?>">Manage Videos</a></div>
    <div class="sf-admin-list">
      <?php foreach ($topVideos as $video): ?>
        <a class="sf-admin-list-row" href="<?= sf_url('admin/videos.php?edit=' . (int)($video['video_id'] ?? 0)) ?>">
          <strong><?= sf_admin_h($video['title'] ?? '') ?></strong>
          <span><?= sf_analytics_time((int)($video['seconds'] ?? 0)) ?></span>
          <em><?= number_format((int)($video['events'] ?? 0)) ?> events · <?= number_format((int)($video['viewers'] ?? 0)) ?> viewers</em>
        </a>
      <?php endforeach; ?>
    </div>
  </article>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head">
    <div><span class="sf-panel-eyebrow">Members</span><h2>Most active members</h2></div>
    <a href="<?= sf_analytics_url('admin/member-activity.php', $days) ?>">Member Activity</a>
  </div>
  <div class="sf-admin-table-wrap">
    <table class="sf-admin-table">
      <thead><tr><th>Member</th><th>Plan</th><th>Audio</th><th>Video</th><th>Playlists</th><th>Last Login</th></tr></thead>
      <tbody>
        <?php foreach ($memberRows as $member): ?>
          <tr>
            <td><a href="<?= sf_url('admin/members.php?edit=' . (int)($member['id'] ?? 0)) ?>"><?= sf_admin_h($member['display_name'] ?: $member['email'] ?: 'Member') ?></a></td>
            <td><?= sf_admin_h($member['plan_name'] ?: 'Free') ?></td>
            <td><?= number_format((int)($member['audio_events'] ?? 0)) ?> · <?= sf_analytics_time((int)($member['audio_seconds'] ?? 0)) ?></td>
            <td><?= number_format((int)($member['video_events'] ?? 0)) ?> · <?= sf_analytics_time((int)($member['video_seconds'] ?? 0)) ?></td>
            <td><?= number_format((int)($member['playlist_count'] ?? 0)) ?></td>
            <td><?= sf_admin_h($member['last_login_at'] ?? '—') ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$memberRows): ?><tr><td colspan="6">No database member activity yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head">
    <div><span class="sf-panel-eyebrow">Operational Notes</span><h2>What this section measures</h2></div>
  </div>
  <div class="sf-admin-roadmap sf-admin-roadmap-compact">
    <div><span>Audio</span><strong>Plays + progress</strong><p>Uses <code>audio_play_events</code> and <code>user_song_progress</code> from the tracking APIs.</p></div>
    <div><span>Video</span><strong>Watch time + completion</strong><p>Uses <code>video_watch_events</code>, <code>user_video_progress</code>, and episode progress tables.</p></div>
    <div><span>Members</span><strong>Subscription activity</strong><p>Combines users, subscriptions, logins, playlists, and access patterns.</p></div>
    <div><span>Commerce</span><strong>Paid merch orders</strong><p>Reads paid/fulfilled orders so membership and merch can be watched together.</p></div>
  </div>
</section>
<?php
sf_admin_shell_end();
require __DIR__ . '/../includes/footer.php';
?>
