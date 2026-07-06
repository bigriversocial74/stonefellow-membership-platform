<?php
$pageTitle = 'Member Activity';
$pageDescription = 'Stonefellow member activity report for subscriptions, logins, playlists, audio engagement, and video engagement.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_analytics.php';
require __DIR__ . '/../includes/header.php';

$days = sf_analytics_days();
$overview = sf_analytics_overview($days);
$members = sf_analytics_member_activity($days, 200);
$daily = sf_analytics_daily_activity(min($days, 30));

sf_admin_shell_start('Member Activity', 'Subscriber behavior', 'Review member engagement across audio, video, playlist creation, account status, subscriptions, and recent logins.', 'member-activity');
sf_analytics_range_tabs($days);
?>
<section class="sf-admin-card-grid sf-analytics-card-grid">
  <?php sf_analytics_metric_card('Total Members', number_format((int)$overview['members']['total']), number_format((int)$overview['members']['new']) . ' new in range'); ?>
  <?php sf_analytics_metric_card('Active Subscriptions', number_format((int)$overview['members']['active_subscriptions']), 'Active/trialing and not expired'); ?>
  <?php sf_analytics_metric_card('Recent Logins', number_format((int)$overview['members']['recent_logins']), 'Members logged in during range'); ?>
  <?php sf_analytics_metric_card('New Playlists', number_format((int)$overview['members']['playlists']), 'Private/system playlists created'); ?>
  <?php sf_analytics_metric_card('Audio Engagement', sf_analytics_time((int)$overview['audio']['seconds']), number_format((int)$overview['audio']['events']) . ' events'); ?>
  <?php sf_analytics_metric_card('Video Engagement', sf_analytics_time((int)$overview['video']['seconds']), number_format((int)$overview['video']['events']) . ' events'); ?>
</section>

<section class="sf-admin-two-col">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Growth</span><h2>New members</h2></div></div>
    <?php sf_analytics_bars($daily, 'members'); ?>
  </article>
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Retention</span><h2>Engagement rules</h2></div></div>
    <div class="sf-admin-roadmap sf-admin-roadmap-compact">
      <div><span>High</span><strong>Audio + video</strong><p>Members with both listening and watching activity are the strongest subscription-retention signal.</p></div>
      <div><span>Watch</span><strong>No recent login</strong><p>Paid members with no recent login should be targeted with episode or playlist email campaigns.</p></div>
    </div>
  </article>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Members</span><h2>Activity table</h2></div><a href="<?= sf_url('admin/members.php') ?>">Manage Members</a></div>
  <div class="sf-admin-table-wrap">
    <table class="sf-admin-table">
      <thead><tr><th>Member</th><th>Status</th><th>Plan</th><th>Audio Events</th><th>Audio Time</th><th>Video Events</th><th>Video Time</th><th>Playlists</th><th>Last Login</th></tr></thead>
      <tbody>
        <?php foreach ($members as $member): ?>
          <tr>
            <td><a href="<?= sf_url('admin/members.php?edit=' . (int)($member['id'] ?? 0)) ?>"><?= sf_admin_h($member['display_name'] ?: $member['email'] ?: 'Member') ?></a><br><small><?= sf_admin_h($member['email'] ?? '') ?></small></td>
            <td><?= sf_admin_status_badge((string)($member['status'] ?? 'active')) ?></td>
            <td><?= sf_admin_h($member['plan_name'] ?: 'Free') ?><br><small><?= sf_admin_h($member['subscription_status'] ?: 'none') ?></small></td>
            <td><?= number_format((int)($member['audio_events'] ?? 0)) ?></td>
            <td><?= sf_analytics_time((int)($member['audio_seconds'] ?? 0)) ?></td>
            <td><?= number_format((int)($member['video_events'] ?? 0)) ?></td>
            <td><?= sf_analytics_time((int)($member['video_seconds'] ?? 0)) ?></td>
            <td><?= number_format((int)($member['playlist_count'] ?? 0)) ?></td>
            <td><?= sf_admin_h($member['last_login_at'] ?? '—') ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$members): ?><tr><td colspan="9">No database members yet. Member activity will populate after signup and tracking events.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php
sf_admin_shell_end();
require __DIR__ . '/../includes/footer.php';
?>
