<?php
$pageTitle = 'Content Ops';
$pageDescription = 'Creator and admin content operations dashboard for daily publishing, media, payments, notification, and catalog tasks.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/activity_ops.php';
require __DIR__ . '/../includes/header.php';
$counts = sf_ops_metric_counts();
$tasks = sf_ops_tasks();
$recent = sf_ops_recent_member_actions();
sf_admin_shell_start('Content Ops', 'Creator/Admin operations dashboard', 'A daily command center for content tasks, missing media, drafts, releases, payments, orders, notifications, analytics, and imports.', 'content-ops');
?>
<section class="sf-admin-card-grid">
  <div class="sf-admin-action-card"><span>Members</span><strong><?= (int)$counts['members'] ?></strong><small><?= (int)$counts['active_subscriptions'] ?> active subscriptions.</small></div>
  <div class="sf-admin-action-card"><span>Orders</span><strong><?= (int)$counts['open_orders'] ?></strong><small>Open fulfillment items.</small></div>
  <div class="sf-admin-action-card"><span>Notifications</span><strong><?= (int)$counts['queued_notifications'] ?></strong><small><?= (int)$counts['failed_notifications'] ?> failed.</small></div>
  <div class="sf-admin-action-card"><span>Library</span><strong><?= (int)$counts['library_items'] ?></strong><small>Saved/watchlist/liked items.</small></div>
</section>
<section class="sf-admin-two-col sf-admin-two-col-wide">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Priority Tasks</span><h2><?= count($tasks) ?> ops tasks</h2></div><a href="<?= sf_url('admin/launch-checklist.php') ?>">Launch Checklist</a></div>
    <div class="sf-admin-roadmap">
      <?php foreach ($tasks as $task): ?>
        <div><span><?= strtoupper(substr((string)$task['priority'],0,1)) ?></span><strong><?= sf_admin_h($task['title']) ?></strong><p><?= sf_admin_h($task['detail']) ?></p><a href="<?= sf_admin_h($task['url']) ?>">Open</a></div>
      <?php endforeach; ?>
    </div>
  </article>
  <aside class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Fast Links</span><h2>Daily shortcuts</h2></div></div>
    <div class="sf-admin-card-grid">
      <a class="sf-admin-action-card" href="<?= sf_url('admin/publishing.php') ?>"><span>Publish</span><strong>Workflow</strong><small>Draft/scheduled/live content.</small></a>
      <a class="sf-admin-action-card" href="<?= sf_url('admin/uploads.php') ?>"><span>Media</span><strong>Uploads</strong><small>Images/audio/video files.</small></a>
      <a class="sf-admin-action-card" href="<?= sf_url('admin/import.php') ?>"><span>Import</span><strong>Content</strong><small>CSV/JSON content loader.</small></a>
      <a class="sf-admin-action-card" href="<?= sf_url('admin/streaming-analytics.php') ?>"><span>Analytics</span><strong>Streams</strong><small>Engagement intelligence.</small></a>
      <a class="sf-admin-action-card" href="<?= sf_url('admin/payment-gateways.php') ?>"><span>Payments</span><strong>Gateway</strong><small>Stripe/PayPal readiness.</small></a>
      <a class="sf-admin-action-card" href="<?= sf_url('admin/activity-feed.php') ?>"><span>Feed</span><strong>Activity</strong><small>Member/admin timeline.</small></a>
    </div>
  </aside>
</section>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Recent Operations</span><h2>Members and commerce</h2></div><a href="<?= sf_url('api/ops-summary.php') ?>">JSON API</a></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Activity</th><th>Type</th><th>Detail</th><th>When</th><th>Action</th></tr></thead><tbody>
    <?php foreach ($recent as $event): ?>
      <tr><td><strong><?= sf_admin_h($event['title'] ?? 'Activity') ?></strong></td><td><?= sf_admin_h($event['event_group'] ?? '') ?></td><td><?= sf_admin_h($event['detail'] ?? '') ?></td><td><?= sf_admin_h($event['occurred_at'] ?? '') ?></td><td><?php if (!empty($event['action_url'])): ?><a href="<?= sf_admin_h($event['action_url']) ?>">Open</a><?php else: ?>—<?php endif; ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$recent): ?><tr><td colspan="5">No recent member/order activity found yet.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
