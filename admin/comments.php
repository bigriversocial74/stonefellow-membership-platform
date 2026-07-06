<?php
$pageTitle = 'Comment Moderation';
$pageDescription = 'Moderate fan comments, replies, reactions, and safe community engagement.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/engagement.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $id = (int)($_POST['comment_id'] ?? 0);
  $status = (string)($_POST['status'] ?? 'pending');
  if (sf_comment_update_status($id, $status)) sf_admin_flash('success', 'Comment updated.'); else sf_admin_flash('warning', 'Comment update was not saved. Confirm comments tables are installed.');
  sf_admin_redirect();
}
require __DIR__ . '/../includes/header.php';
$summary = sf_comment_summary();
$queue = sf_comment_moderation_queue(200);
$approved = sf_comments_for((string)($_GET['content_type'] ?? 'episode'), (int)($_GET['content_id'] ?? 0), (string)($_GET['slug'] ?? ''), 'approved', 80);
sf_admin_shell_start('Comments', 'Fan moderation queue', 'Approve, hide, reject, and audit member comments across episodes, videos, songs, albums, posts, and products.', 'comments');
?>
<section class="sf-admin-card-grid">
  <div class="sf-admin-action-card"><span>Total Comments</span><strong><?= (int)$summary['comments'] ?></strong><small>All known comment records.</small></div>
  <div class="sf-admin-action-card"><span>Pending</span><strong><?= (int)$summary['pending'] ?></strong><small>Needs moderation.</small></div>
  <div class="sf-admin-action-card"><span>Approved</span><strong><?= (int)$summary['approved'] ?></strong><small>Visible in threads.</small></div>
  <div class="sf-admin-action-card"><span>Reactions</span><strong><?= (int)$summary['reactions'] ?></strong><small>Likes/love/fire/laugh/wow.</small></div>
</section>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Moderation Queue</span><h2><?= count($queue) ?> review items</h2></div><a href="<?= sf_url('admin/engagement.php') ?>">Engagement</a></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Comment</th><th>Content</th><th>Author</th><th>Status</th><th>Moderate</th></tr></thead><tbody>
    <?php foreach ($queue as $comment): ?><tr><td><strong><?= sf_admin_h(substr((string)($comment['body'] ?? ''), 0, 120)) ?></strong><small><?= sf_admin_h($comment['created_at'] ?? '') ?></small></td><td><?= sf_admin_h(($comment['content_type'] ?? '') . ' #' . ($comment['content_id'] ?? '')) ?><small><?= sf_admin_h($comment['content_slug'] ?? '') ?></small></td><td><?= sf_admin_h($comment['display_name'] ?? $comment['author_name'] ?? $comment['email'] ?? 'Member') ?></td><td><?= sf_admin_status_badge((string)($comment['status'] ?? 'pending')) ?></td><td><form class="sf-admin-inline-form" method="post"><?= sf_csrf_field() ?><input type="hidden" name="comment_id" value="<?= (int)($comment['id'] ?? 0) ?>"><button name="status" value="approved" type="submit">Approve</button><button name="status" value="hidden" type="submit">Hide</button><button name="status" value="rejected" type="submit">Reject</button><button name="status" value="spam" type="submit">Spam</button></form></td></tr><?php endforeach; ?>
    <?php if (!$queue): ?><tr><td colspan="5">No moderation items found.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Approved Preview</span><h2>Recent visible comments</h2></div><a href="<?= sf_url('comments.php') ?>">Public thread</a></div><div class="sf-admin-list"><?php foreach ($approved as $comment): ?><article class="sf-admin-list-row"><strong><?= sf_admin_h($comment['display_name'] ?? $comment['author_name'] ?? 'Member') ?></strong><span><?= sf_admin_h(($comment['content_type'] ?? 'content') . ' · ' . ($comment['created_at'] ?? '')) ?></span><p><?= sf_admin_h($comment['body'] ?? '') ?></p><em><?= (int)($comment['reaction_count'] ?? 0) ?> reactions</em></article><?php endforeach; ?></div></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
