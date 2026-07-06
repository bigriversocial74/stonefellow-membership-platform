<?php
require __DIR__ . '/includes/feed_personalization.php';
$user = sf_auth_user();
$userId = (int)($user['id'] ?? 0);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $userId) {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) { sf_auth_flash('error', 'Security check failed.'); sf_redirect(sf_url('feed.php')); }
  $action = (string)($_POST['action'] ?? '');
  if ($action === 'save_preferences') { sf_feed_save_preferences($userId, $_POST['preferences'] ?? []); sf_auth_flash('success', 'Feed preferences saved.'); }
  if ($action === 'follow') { sf_feed_follow_save($userId, (string)($_POST['target_type'] ?? 'creator'), (string)($_POST['target_slug'] ?? ''), (int)($_POST['target_id'] ?? 0), (string)($_POST['label'] ?? ''), 'following'); sf_auth_flash('success', 'Follow saved.'); }
  if ($action === 'unfollow') { sf_feed_follow_remove($userId, (string)($_POST['target_type'] ?? 'creator'), (string)($_POST['target_slug'] ?? ''), (int)($_POST['target_id'] ?? 0)); sf_auth_flash('success', 'Follow removed.'); }
  if ($action === 'save_item') { sf_feed_item_status($userId, (int)($_POST['post_id'] ?? 0), 'saved', (int)($_POST['score'] ?? 0), 'Saved from feed'); sf_auth_flash('success', 'Post saved.'); }
  if ($action === 'hide_item') { sf_feed_item_status($userId, (int)($_POST['post_id'] ?? 0), 'hidden', (int)($_POST['score'] ?? 0), 'Hidden from feed'); sf_auth_flash('success', 'Post hidden.'); }
  sf_redirect(sf_url('feed.php'));
}
$posts = $userId ? sf_feed_personalized_posts($userId, 80) : sf_posts_all('published', 80);
$preferences = sf_feed_member_preferences($userId);
$followMap = sf_feed_member_follow_map($userId);
$pageTitle = 'News Feed';
$pageDescription = 'Stonefellow personalized creator posts, news updates, soundtrack notes, episode drops, and member discussion threads.';
$pageClass = 'member-dashboard-page membership-page feed-page';
require __DIR__ . '/includes/header.php';
?>
<section class="sf-membership-shell">
  <section class="sf-member-hero">
    <div><span class="sf-panel-eyebrow">Personalized Feed</span><h1>News, drops, and behind-the-scenes notes.</h1><p><?= $userId ? 'Your feed is ranked by follows, preferences, comments, reactions, and saved items.' : 'Sign in to follow music, episodes, and creator posts for a personalized feed.' ?></p><div class="sf-episode-action-row"><a class="sf-primary-action" href="<?= sf_url('episodes.php') ?>">Episodes</a><a class="sf-secondary-action" href="<?= sf_url('player.php') ?>">Music</a><a class="sf-secondary-action" href="<?= sf_url('comments.php') ?>">Fan Comments</a></div></div>
    <article class="sf-member-status-card"><span><?= $userId ? 'For You' : 'Published' ?></span><strong><?= count($posts) ?></strong><small><?= $userId ? count($followMap) . ' follows active' : 'Creator/news feed posts' ?></small><a href="<?= sf_url('api/posts.php') ?>">Feed API</a></article>
  </section>
  <?php if ($userId): ?>
  <section class="sf-member-section"><div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Preferences</span><h2>Tune your feed</h2></div><a href="<?= sf_url('api/feed-preferences.php') ?>">Preferences API</a></div><form class="sf-admin-form" method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="save_preferences"><div class="sf-admin-card-grid"><?php foreach (sf_feed_preference_keys() as $key=>$label): ?><label class="sf-member-panel"><span class="sf-panel-eyebrow"><?= htmlspecialchars($label) ?></span><h2><?= (int)($preferences[$key] ?? 50) ?></h2><input type="range" min="0" max="100" name="preferences[<?= htmlspecialchars($key) ?>]" value="<?= (int)($preferences[$key] ?? 50) ?>"></label><?php endforeach; ?></div><div class="sf-admin-form-actions"><button type="submit">Save Preferences</button></div></form><div class="sf-episode-action-row"><?php foreach (sf_feed_follow_targets() as $target=>$label): $parts=explode(':',$target,2); $type=$parts[0]; $slug=$parts[1]??'creator'; $isFollow=isset($followMap[$type . ':' . $slug]); ?><form method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="<?= $isFollow ? 'unfollow' : 'follow' ?>"><input type="hidden" name="target_type" value="<?= htmlspecialchars($type) ?>"><input type="hidden" name="target_slug" value="<?= htmlspecialchars($slug) ?>"><input type="hidden" name="label" value="<?= htmlspecialchars($label) ?>"><button class="sf-secondary-action" type="submit"><?= $isFollow ? '✓ ' : '+ ' ?><?= htmlspecialchars($label) ?></button></form><?php endforeach; ?></div></section>
  <?php endif; ?>
  <section class="sf-member-section">
    <div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Latest</span><h2><?= $userId ? 'For you' : 'Creator posts' ?></h2></div><a href="<?= sf_url('admin/posts.php') ?>">Manage</a></div>
    <div class="sf-video-card-grid">
      <?php foreach ($posts as $post): ?>
        <article class="sf-video-card"><a href="<?= sf_url('post.php?slug=' . urlencode((string)$post['slug'])) ?>"><img src="<?= sf_asset($post['image_path'] ?? 'images/episodes/episode-01.png') ?>" alt="<?= htmlspecialchars($post['title'] ?? 'Post') ?> image"><span><?= htmlspecialchars(ucfirst((string)($post['post_type'] ?? 'news'))) ?> · <?= htmlspecialchars($post['published_at'] ?? $post['created_at'] ?? '') ?></span><strong><?= htmlspecialchars($post['title'] ?? 'Stonefellow post') ?></strong><small><?= htmlspecialchars($post['excerpt'] ?? '') ?></small><em><?= (int)($post['comment_count'] ?? 0) ?> comments · <?= (int)($post['reaction_count'] ?? 0) ?> reactions · <?= (int)($post['personalization_score'] ?? 50) ?> score</em></a><?php if ($userId): ?><form class="sf-episode-action-row" method="post"><?= sf_csrf_field() ?><input type="hidden" name="post_id" value="<?= (int)($post['id'] ?? 0) ?>"><input type="hidden" name="score" value="<?= (int)($post['personalization_score'] ?? 0) ?>"><button class="sf-secondary-action" name="action" value="save_item" type="submit"><?= !empty($post['is_saved']) ? 'Saved' : 'Save' ?></button><button class="sf-secondary-action" name="action" value="hide_item" type="submit">Hide</button></form><small><?= htmlspecialchars($post['personalization_reason'] ?? 'Latest post') ?></small><?php endif; ?></article>
      <?php endforeach; ?>
    </div>
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
