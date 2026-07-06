<?php
require __DIR__ . '/includes/engagement.php';
$user = sf_require_login();
$userId = (int)$user['id'];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) { sf_auth_flash('error', 'Security check failed.'); sf_redirect(sf_url('notifications.php')); }
  $action = (string)($_POST['action'] ?? '');
  $id = (int)($_POST['id'] ?? 0);
  if ($action === 'mark_read') sf_member_notification_update($userId, $id, 'read');
  if ($action === 'dismiss') sf_member_notification_update($userId, $id, 'dismissed');
  if ($action === 'mark_all_read') sf_member_notifications_mark_all_read($userId);
  sf_redirect(sf_url('notifications.php'));
}
$status = trim((string)($_GET['status'] ?? ''));
$notifications = sf_member_notifications($userId, $status);
$summary = sf_member_notification_summary($userId);
$pageTitle = 'Notifications';
$pageDescription = 'Stonefellow member notification center for unread updates, comments, account, billing, and streaming alerts.';
$pageClass = 'member-dashboard-page membership-page';
require __DIR__ . '/includes/header.php';
?>
<section class="sf-membership-shell">
  <section class="sf-member-hero">
    <div><span class="sf-panel-eyebrow">Notification Center</span><h1>Your Stonefellow updates.</h1><p>Follow account, billing, comments, replies, watchlist, music, episode, and system updates from one member inbox.</p><div class="sf-episode-action-row"><a class="sf-primary-action" href="<?= sf_url('member.php') ?>">Member Dashboard</a><a class="sf-secondary-action" href="<?= sf_url('comments.php') ?>">Comments</a><a class="sf-secondary-action" href="<?= sf_url('library.php') ?>">Library</a></div></div>
    <article class="sf-member-status-card"><span>Unread</span><strong><?= (int)$summary['unread'] ?></strong><small><?= (int)$summary['total'] ?> total notifications</small><form method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="mark_all_read"><button class="sf-secondary-action" type="submit">Mark All Read</button></form></article>
  </section>
  <section class="sf-member-grid">
    <?php foreach ([''=>'All','unread'=>'Unread','read'=>'Read','dismissed'=>'Dismissed'] as $key=>$label): ?><a class="sf-member-panel" href="<?= sf_url('notifications.php' . ($key!=='' ? '?status=' . urlencode($key) : '')) ?>"><span class="sf-panel-eyebrow"><?= htmlspecialchars($label) ?></span><h2><?= $key==='' ? (int)$summary['total'] : (int)($summary[$key] ?? 0) ?></h2><p><?= $key==='' ? 'All notification center items.' : 'Filter member notifications.' ?></p></a><?php endforeach; ?>
  </section>
  <section class="sf-member-section">
    <div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Inbox</span><h2><?= count($notifications) ?> notifications</h2></div><a href="<?= sf_url('api/notifications.php') ?>">API</a></div>
    <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Notification</th><th>Type</th><th>Status</th><th>When</th><th>Actions</th></tr></thead><tbody>
      <?php foreach ($notifications as $note): ?><tr><td><strong><?= htmlspecialchars($note['title'] ?? 'Notification') ?></strong><small><?= htmlspecialchars($note['body'] ?? $note['body_preview'] ?? '') ?></small></td><td><?= htmlspecialchars($note['notification_type'] ?? 'system') ?></td><td><?= htmlspecialchars(ucfirst((string)($note['status'] ?? 'unread'))) ?></td><td><?= htmlspecialchars($note['created_at'] ?? '') ?></td><td><div class="sf-episode-action-row"><?php if (!empty($note['action_url'])): ?><a class="sf-secondary-action" href="<?= htmlspecialchars($note['action_url']) ?>">Open</a><?php endif; ?><form method="post"><?= sf_csrf_field() ?><input type="hidden" name="id" value="<?= (int)($note['id'] ?? 0) ?>"><button class="sf-secondary-action" name="action" value="mark_read" type="submit">Read</button><button class="sf-secondary-action" name="action" value="dismiss" type="submit">Dismiss</button></form></div></td></tr><?php endforeach; ?>
      <?php if (!$notifications): ?><tr><td colspan="5">No notifications found.</td></tr><?php endif; ?>
    </tbody></table></div>
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
