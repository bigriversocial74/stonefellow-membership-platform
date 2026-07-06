<?php
$pageTitle = 'Activity Feed';
$pageDescription = 'Member and admin activity timeline for signups, streams, saves, orders, notifications, payments, and publishing.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/activity_ops.php';
require __DIR__ . '/../includes/header.php';
$group = trim((string)($_GET['group'] ?? ''));
$events = sf_activity_recent(120, $group);
$summary = sf_activity_summary(30);
sf_admin_shell_start('Activity Feed', 'Notifications v2 / member activity', 'A daily operations timeline for member signups, purchases, streams, saves, watchlist adds, notifications, payments, and publishing changes.', 'activity-feed');
?>
<section class="sf-admin-card-grid">
  <div class="sf-admin-action-card"><span>30-day events</span><strong><?= (int)$summary['events'] ?></strong><small><?= (int)$summary['members'] ?> engaged members.</small></div>
  <div class="sf-admin-action-card"><span>Streams</span><strong><?= (int)$summary['streams'] ?></strong><small>Audio/video activity.</small></div>
  <div class="sf-admin-action-card"><span>Commerce</span><strong><?= (int)$summary['commerce'] ?></strong><small>Purchases and order activity.</small></div>
  <div class="sf-admin-action-card"><span>Warnings</span><strong><?= (int)$summary['warnings'] ?></strong><small>Failed or review-needed events.</small></div>
</section>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Filters</span><h2>Activity groups</h2></div><a href="<?= sf_url('api/activity-feed.php') ?>">JSON API</a></div>
  <div class="sf-episode-action-row">
    <?php foreach ([''=>'All','member'=>'Members','stream'=>'Streams','library'=>'Library','commerce'=>'Commerce','notification'=>'Notifications','payment'=>'Payments','publish'=>'Publishing'] as $key=>$label): ?>
      <a class="sf-secondary-action<?= $group===$key ? ' is-active' : '' ?>" href="<?= sf_url('admin/activity-feed.php' . ($key!=='' ? '?group=' . urlencode($key) : '')) ?>"><?= sf_admin_h($label) ?></a>
    <?php endforeach; ?>
  </div>
</section>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Timeline</span><h2><?= count($events) ?> recent events</h2></div><a href="<?= sf_url('admin/content-ops.php') ?>">Open Ops Dashboard</a></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Event</th><th>Group</th><th>Detail</th><th>When</th><th>Action</th></tr></thead><tbody>
    <?php foreach ($events as $event): ?>
      <tr><td><strong><?= sf_admin_h($event['title'] ?? ucfirst(str_replace('_',' ',(string)($event['event_type'] ?? 'activity')))) ?></strong><small><?= sf_admin_h($event['event_type'] ?? '') ?></small></td><td><?= sf_admin_status_badge((string)($event['severity'] ?? 'info') === 'warning' ? 'draft' : 'active') ?><small><?= sf_admin_h($event['event_group'] ?? sf_activity_icon((string)($event['event_type'] ?? 'activity'))) ?></small></td><td><?= sf_admin_h($event['detail'] ?? $event['user_email'] ?? '') ?></td><td><?= sf_admin_h($event['occurred_at'] ?? $event['created_at'] ?? '') ?></td><td><?php if (!empty($event['action_url'])): ?><a href="<?= sf_admin_h($event['action_url']) ?>">Open</a><?php else: ?>—<?php endif; ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$events): ?><tr><td colspan="5">No activity events found yet.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
