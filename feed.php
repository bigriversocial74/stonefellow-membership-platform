<?php
require __DIR__ . '/includes/posts.php';
$posts = sf_posts_all('published', 80);
$pageTitle = 'News Feed';
$pageDescription = 'Stonefellow creator posts, news updates, soundtrack notes, episode drops, and member discussion threads.';
$pageClass = 'member-dashboard-page membership-page feed-page';
require __DIR__ . '/includes/header.php';
?>
<section class="sf-membership-shell">
  <section class="sf-member-hero">
    <div><span class="sf-panel-eyebrow">Creator Feed</span><h1>News, drops, and behind-the-scenes notes.</h1><p>Follow Stonefellow posts, soundtrack notes, episode updates, merch announcements, and fan discussion from one public feed.</p><div class="sf-episode-action-row"><a class="sf-primary-action" href="<?= sf_url('episodes.php') ?>">Episodes</a><a class="sf-secondary-action" href="<?= sf_url('player.php') ?>">Music</a><a class="sf-secondary-action" href="<?= sf_url('comments.php') ?>">Fan Comments</a></div></div>
    <article class="sf-member-status-card"><span>Published</span><strong><?= count($posts) ?></strong><small>Creator/news feed posts.</small><a href="<?= sf_url('api/posts.php') ?>">Feed API</a></article>
  </section>
  <section class="sf-member-section">
    <div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Latest</span><h2>Creator posts</h2></div><a href="<?= sf_url('admin/posts.php') ?>">Manage</a></div>
    <div class="sf-video-card-grid">
      <?php foreach ($posts as $post): ?>
        <a class="sf-video-card" href="<?= sf_url('post.php?slug=' . urlencode((string)$post['slug'])) ?>">
          <img src="<?= sf_asset($post['image_path'] ?? 'images/episodes/episode-01.png') ?>" alt="<?= htmlspecialchars($post['title'] ?? 'Post') ?> image">
          <span><?= htmlspecialchars(ucfirst((string)($post['post_type'] ?? 'news'))) ?> · <?= htmlspecialchars($post['published_at'] ?? $post['created_at'] ?? '') ?></span>
          <strong><?= htmlspecialchars($post['title'] ?? 'Stonefellow post') ?></strong>
          <small><?= htmlspecialchars($post['excerpt'] ?? '') ?></small>
          <em><?= (int)($post['comment_count'] ?? 0) ?> comments · <?= (int)($post['reaction_count'] ?? 0) ?> reactions</em>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
