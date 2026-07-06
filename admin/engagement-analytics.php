<?php
$pageTitle = 'Engagement Analytics v2';
$pageDescription = 'Stonefellow engagement analytics for personalized feed performance, comments, reactions, follows, saves, hides, and top members.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/feed_personalization.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  sf_engagement_recalculate_member_scores();
  sf_admin_flash('success', 'Member engagement scores recalculated.');
  sf_admin_redirect();
}
require __DIR__ . '/../includes/header.php';
$summary = sf_engagement_analytics_summary(30);
sf_admin_shell_start('Engagement Analytics', 'Member engagement analytics v2', 'Measure the personalized feed, comments, reactions, saves, hides, follows, and top engaged members.', 'engagement-analytics');
?>
<section class="sf-admin-card-grid">
  <div class="sf-admin-action-card"><span>Published Posts</span><strong><?= (int)$summary['posts'] ?></strong><small>Active feed inventory.</small></div>
  <div class="sf-admin-action-card"><span>Comments</span><strong><?= (int)$summary['comments'] ?></strong><small>Last 30 days.</small></div>
  <div class="sf-admin-action-card"><span>Reactions</span><strong><?= (int)$summary['reactions'] ?></strong><small>Last 30 days.</small></div>
  <div class="sf-admin-action-card"><span>Follows</span><strong><?= (int)$summary['follows'] ?></strong><small>Active follow records.</small></div>
  <div class="sf-admin-action-card"><span>Saves</span><strong><?= (int)$summary['saves'] ?></strong><small>Saved feed items.</small></div>
  <div class="sf-admin-action-card"><span>Hides</span><strong><?= (int)$summary['hides'] ?></strong><small>Hidden/dismissed feed items.</small></div>
</section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Controls</span><h2>Score refresh</h2></div><a href="<?= sf_url('api/engagement-analytics.php') ?>">JSON API</a></div><form class="sf-admin-form" method="post"><?= sf_csrf_field() ?><p class="sf-admin-copy">Recalculate member scores from comments, reactions, follows, feed saves, and streams. Scores feed future segmentation, loyalty, and notification targeting.</p><div class="sf-admin-form-actions"><button type="submit">Recalculate Member Scores</button></div></form></section>
<section class="sf-admin-two-col sf-admin-two-col-wide">
  <article class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Top Content</span><h2>Engagement score</h2></div><a href="<?= sf_url('admin/posts.php') ?>">Posts</a></div><div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Post</th><th>Type</th><th>Comments</th><th>Reactions</th><th>Saves</th><th>Score</th></tr></thead><tbody><?php foreach ($summary['top_posts'] as $post): ?><tr><td><strong><?= sf_admin_h($post['title'] ?? '') ?></strong><small><?= sf_admin_h($post['slug'] ?? '') ?></small></td><td><?= sf_admin_h($post['post_type'] ?? '') ?></td><td><?= (int)($post['comments'] ?? 0) ?></td><td><?= (int)($post['reactions'] ?? 0) ?></td><td><?= (int)($post['saves'] ?? 0) ?></td><td><strong><?= (int)($post['score'] ?? 0) ?></strong></td></tr><?php endforeach; ?><?php if (!$summary['top_posts']): ?><tr><td colspan="6">No post analytics yet.</td></tr><?php endif; ?></tbody></table></div></article>
  <aside class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Top Members</span><h2>Engagement score</h2></div><a href="<?= sf_url('admin/members.php') ?>">Members</a></div><div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Member</th><th>Score</th><th>Comments</th><th>Reactions</th><th>Saves</th><th>Follows</th></tr></thead><tbody><?php foreach ($summary['top_members'] as $member): ?><tr><td><strong><?= sf_admin_h($member['display_name'] ?? $member['email'] ?? 'Member') ?></strong><small><?= sf_admin_h($member['email'] ?? '') ?></small></td><td><strong><?= (int)($member['score'] ?? 0) ?></strong></td><td><?= (int)($member['comment_count'] ?? 0) ?></td><td><?= (int)($member['reaction_count'] ?? 0) ?></td><td><?= (int)($member['save_count'] ?? 0) ?></td><td><?= (int)($member['follow_count'] ?? 0) ?></td></tr><?php endforeach; ?><?php if (!$summary['top_members']): ?><tr><td colspan="6">Run score refresh after members engage.</td></tr><?php endif; ?></tbody></table></div></aside>
</section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Phase 22</span><h2>Analytics coverage</h2></div></div><div class="sf-admin-roadmap"><div><span>01</span><strong>Feed Signals</strong><p>Saves, hides, dismissals, personalized scores, and follow preferences.</p></div><div><span>02</span><strong>Community Signals</strong><p>Comments and reactions by content and member.</p></div><div><span>03</span><strong>Member Scores</strong><p>Score members by comments, reactions, follows, saves, and streams.</p></div><div><span>04</span><strong>Content Scores</strong><p>Rank posts by comments, reactions, saves, and future conversion events.</p></div></div></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
