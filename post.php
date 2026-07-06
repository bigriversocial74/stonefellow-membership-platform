<?php
require __DIR__ . '/includes/posts.php';
$slug = trim((string)($_GET['slug'] ?? 'behind-the-first-chapter'));
$post = sf_post_by_slug($slug) ?: (sf_posts_all('published', 1)[0] ?? null);
if (!$post) { http_response_code(404); $pageTitle='Post Not Found'; $pageDescription='Stonefellow post not found.'; require __DIR__ . '/includes/header.php'; echo '<section class="sf-membership-shell"><div class="sf-access-gate"><span>404</span><h1>Post not found</h1><p>This creator post is not available.</p><a href="'.sf_url('feed.php').'">Back to Feed</a></div></section>'; require __DIR__ . '/includes/footer.php'; exit; }
$pageTitle = $post['title'] ?? 'Stonefellow Post';
$pageDescription = $post['excerpt'] ?? 'Stonefellow creator post and fan thread.';
$pageClass = 'member-dashboard-page membership-page post-page';
$media = sf_post_media((int)($post['id'] ?? 0));
require __DIR__ . '/includes/header.php';
?>
<section class="sf-membership-shell">
  <section class="sf-member-hero">
    <div><span class="sf-panel-eyebrow"><?= htmlspecialchars(ucfirst((string)($post['post_type'] ?? 'news'))) ?></span><h1><?= htmlspecialchars($post['title'] ?? 'Stonefellow Post') ?></h1><p><?= htmlspecialchars($post['excerpt'] ?? '') ?></p><div class="sf-episode-action-row"><a class="sf-primary-action" href="<?= sf_url('feed.php') ?>">Back to Feed</a><?php if (!empty($post['linked_content_type'])): ?><a class="sf-secondary-action" href="<?= htmlspecialchars(sf_post_link_url($post)) ?>">Open Linked Content</a><?php endif; ?><a class="sf-secondary-action" href="<?= sf_url('notifications.php') ?>">Notifications</a></div></div>
    <article class="sf-member-status-card"><span>Community</span><strong><?= (int)($post['comment_count'] ?? sf_post_comment_count('post', (int)($post['id'] ?? 0), (string)($post['slug'] ?? ''))) ?></strong><small><?= (int)($post['reaction_count'] ?? 0) ?> reactions · <?= htmlspecialchars($post['published_at'] ?? $post['created_at'] ?? '') ?></small><a href="<?= sf_url('comments.php?content_type=post&content_id=' . (int)($post['id'] ?? 0) . '&slug=' . urlencode((string)($post['slug'] ?? ''))) ?>">Full Thread</a></article>
  </section>
  <section class="sf-member-section">
    <?php if (!empty($post['image_path'])): ?><img class="sf-post-hero-image" src="<?= sf_asset($post['image_path']) ?>" alt="<?= htmlspecialchars($post['title'] ?? 'Post') ?> image"><?php endif; ?>
    <article class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">By <?= htmlspecialchars($post['author_name'] ?? 'Stonefellow') ?></span><h2><?= htmlspecialchars($post['title'] ?? '') ?></h2></div></div><p class="sf-admin-copy"><?= nl2br(htmlspecialchars($post['body'] ?? '')) ?></p></article>
    <?php if ($media): ?><div class="sf-video-card-grid"><?php foreach ($media as $item): ?><article class="sf-video-card"><img src="<?= sf_asset($item['media_path'] ?? '') ?>" alt="<?= htmlspecialchars($item['caption'] ?? 'Post media') ?>"><span><?= htmlspecialchars($item['media_type'] ?? 'media') ?></span><strong><?= htmlspecialchars($item['caption'] ?? 'Post media') ?></strong></article><?php endforeach; ?></div><?php endif; ?>
  </section>
  <?php sf_inline_comment_widget('post', (int)($post['id'] ?? 0), (string)($post['slug'] ?? ''), 'Post comments'); ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
