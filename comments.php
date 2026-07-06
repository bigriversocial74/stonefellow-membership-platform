<?php
require __DIR__ . '/includes/engagement.php';
$contentType = trim((string)($_GET['content_type'] ?? $_POST['content_type'] ?? 'episode'));
$contentId = (int)($_GET['content_id'] ?? $_POST['content_id'] ?? 0);
$slug = trim((string)($_GET['slug'] ?? $_POST['slug'] ?? ''));
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) { sf_auth_flash('error', 'Security check failed.'); sf_redirect(sf_url('comments.php')); }
  $user = sf_require_login();
  if (($_POST['action'] ?? '') === 'react') {
    $result = sf_comment_react((int)$user['id'], (string)($_POST['target_type'] ?? 'comment'), (int)($_POST['target_id'] ?? 0), (string)($_POST['reaction_type'] ?? 'like'));
  } else {
    $result = sf_comment_create((int)$user['id'], $contentType, $contentId, $slug, (string)($_POST['body'] ?? ''), isset($_POST['parent_comment_id']) ? (int)$_POST['parent_comment_id'] : null);
  }
  sf_auth_flash(!empty($result['ok']) ? 'success' : 'error', $result['message'] ?? 'Comment action complete.');
  sf_redirect(sf_url('comments.php?content_type=' . urlencode($contentType) . '&content_id=' . $contentId . ($slug ? '&slug=' . urlencode($slug) : '')));
}
$comments = sf_comments_for($contentType, $contentId, $slug, 'approved');
$summary = sf_comment_summary();
$pageTitle = 'Comments';
$pageDescription = 'Stonefellow fan comments and reactions for episodes, videos, songs, albums, posts, and products.';
$pageClass = 'member-dashboard-page membership-page comments-page';
require __DIR__ . '/includes/header.php';
?>
<section class="sf-membership-shell">
  <section class="sf-member-hero">
    <div><span class="sf-panel-eyebrow">Fan Engagement</span><h1>Join the Stonefellow conversation.</h1><p>Comments are available for episodes, videos, songs, albums, posts, and products with moderation controls for safe community growth.</p><div class="sf-episode-action-row"><a class="sf-primary-action" href="<?= sf_eng_h(sf_eng_content_url($contentType, $slug, $contentId)) ?>">Back to Content</a><a class="sf-secondary-action" href="<?= sf_url('notifications.php') ?>">Notifications</a><a class="sf-secondary-action" href="<?= sf_url('member.php') ?>">Member</a></div></div>
    <article class="sf-member-status-card"><span>Engagement</span><strong><?= (int)$summary['comments'] ?></strong><small><?= (int)$summary['reactions'] ?> reactions · <?= (int)$summary['pending'] ?> pending moderation</small><a href="<?= sf_url('admin/comments.php') ?>">Moderation</a></article>
  </section>
  <section class="sf-member-section">
    <div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Comment Thread</span><h2><?= htmlspecialchars(ucfirst($contentType)) ?> <?= $slug ? '· ' . htmlspecialchars($slug) : ($contentId ? '#' . (int)$contentId : '') ?></h2></div><a href="<?= sf_url('api/comments.php?content_type=' . urlencode($contentType) . '&content_id=' . $contentId . ($slug ? '&slug=' . urlencode($slug) : '')) ?>">API</a></div>
    <?php if (sf_auth_user()): ?>
      <form class="sf-admin-form" method="post"><?= sf_csrf_field() ?><input type="hidden" name="content_type" value="<?= htmlspecialchars($contentType) ?>"><input type="hidden" name="content_id" value="<?= (int)$contentId ?>"><input type="hidden" name="slug" value="<?= htmlspecialchars($slug) ?>"><label>Add Comment<textarea name="body" rows="4" maxlength="2000" placeholder="Share your reaction..." required></textarea></label><div class="sf-admin-form-actions"><button type="submit">Post Comment</button></div></form>
    <?php else: ?>
      <div class="sf-access-gate"><span>Sign in</span><h2>Members can comment and react.</h2><p>Create an account or sign in to join the conversation.</p><div class="sf-episode-action-row"><a class="sf-primary-action" href="<?= sf_url('signin.php') ?>">Sign In</a><a class="sf-secondary-action" href="<?= sf_url('signup.php') ?>">Create Account</a></div></div>
    <?php endif; ?>
  </section>
  <section class="sf-member-section">
    <div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Community</span><h2><?= count($comments) ?> approved comments</h2></div></div>
    <div class="sf-admin-list"><?php foreach ($comments as $comment): ?><article class="sf-admin-list-row"><strong><?= htmlspecialchars($comment['author_name'] ?? $comment['display_name'] ?? $comment['email'] ?? 'Member') ?></strong><span><?= htmlspecialchars($comment['created_at'] ?? '') ?></span><p><?= nl2br(htmlspecialchars($comment['body'] ?? '')) ?></p><em><?= (int)($comment['reaction_count'] ?? 0) ?> reactions</em><?php if (sf_auth_user()): ?><form method="post"><?= sf_csrf_field() ?><input type="hidden" name="content_type" value="<?= htmlspecialchars($contentType) ?>"><input type="hidden" name="content_id" value="<?= (int)$contentId ?>"><input type="hidden" name="slug" value="<?= htmlspecialchars($slug) ?>"><input type="hidden" name="action" value="react"><input type="hidden" name="target_type" value="comment"><input type="hidden" name="target_id" value="<?= (int)($comment['id'] ?? 0) ?>"><button class="sf-secondary-action" type="submit">Like</button></form><?php endif; ?></article><?php endforeach; ?><?php if (!$comments): ?><article class="sf-admin-list-row"><strong>No approved comments yet.</strong><p>Be the first to start the thread.</p></article><?php endif; ?></div>
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
