<?php
$pageTitle = 'Engagement Dashboard';
$pageDescription = 'Admin engagement dashboard for comments, reactions, member notifications, and moderation health.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/engagement.php';
require __DIR__ . '/../includes/header.php';
$commentSummary = sf_comment_summary();
$notificationSummary = ['total'=>0,'unread'=>0,'read'=>0,'dismissed'=>0];
if (sf_eng_table_exists('member_notifications')) {
  foreach (sf_eng_fetch_all('SELECT status, COUNT(*) AS total FROM member_notifications GROUP BY status') as $row) { $notificationSummary[(string)$row['status']] = (int)$row['total']; $notificationSummary['total'] += (int)$row['total']; }
} else {
  $notificationSummary = sf_member_notification_summary(sf_current_user_id() ?: 0);
}
$queue = sf_comment_moderation_queue(20);
sf_admin_shell_start('Engagement', 'Fan engagement dashboard', 'Track member notification center adoption, comments, reactions, moderation queue, and community safety signals.', 'engagement');
?>
<section class="sf-admin-card-grid">
  <a class="sf-admin-action-card" href="<?= sf_url('admin/comments.php') ?>"><span>Comments</span><strong><?= (int)$commentSummary['comments'] ?></strong><small><?= (int)$commentSummary['pending'] ?> pending moderation.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('comments.php') ?>"><span>Public</span><strong>Threads</strong><small>Open the fan comments page.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('notifications.php') ?>"><span>Notifications</span><strong><?= (int)$notificationSummary['unread'] ?></strong><small><?= (int)$notificationSummary['total'] ?> total center items.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('api/comments.php') ?>"><span>API</span><strong>Comments</strong><small>JSON endpoint for future widgets.</small></a>
</section>
<section class="sf-admin-two-col sf-admin-two-col-wide">
  <article class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Moderation</span><h2>Queue</h2></div><a href="<?= sf_url('admin/comments.php') ?>">Moderate</a></div><div class="sf-admin-roadmap"><?php foreach (array_slice($queue,0,8) as $comment): ?><div><span>!</span><strong><?= sf_admin_h(substr((string)($comment['body'] ?? 'Comment'),0,80)) ?></strong><p><?= sf_admin_h(($comment['content_type'] ?? 'content') . ' · ' . ($comment['status'] ?? 'pending')) ?></p></div><?php endforeach; ?><?php if (!$queue): ?><div><span>✓</span><strong>No pending moderation</strong><p>Fan engagement is clear right now.</p></div><?php endif; ?></div></article>
  <aside class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Notification Center</span><h2>Member inbox status</h2></div><a href="<?= sf_url('api/notifications.php') ?>">API</a></div><div class="sf-admin-roadmap"><div><span><?= (int)$notificationSummary['total'] ?></span><strong>Total</strong><p>Member notification center records.</p></div><div><span><?= (int)$notificationSummary['unread'] ?></span><strong>Unread</strong><p>Needs member attention.</p></div><div><span><?= (int)$notificationSummary['read'] ?></span><strong>Read</strong><p>Viewed by members.</p></div><div><span><?= (int)$notificationSummary['dismissed'] ?></span><strong>Dismissed</strong><p>Cleared from inbox.</p></div></div></aside>
</section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Engagement Layer</span><h2>What is active</h2></div></div><div class="sf-admin-roadmap"><div><span>01</span><strong>Member Notifications</strong><p>Unread/read/dismissed notification center and JSON API.</p></div><div><span>02</span><strong>Comments</strong><p>Comment threads for episodes, videos, songs, albums, posts, and products.</p></div><div><span>03</span><strong>Reactions</strong><p>Like/love/fire/laugh/wow reaction runtime foundation.</p></div><div><span>04</span><strong>Moderation</strong><p>Admin queue for pending, hidden, spam, approved, and rejected comments.</p></div></div></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
