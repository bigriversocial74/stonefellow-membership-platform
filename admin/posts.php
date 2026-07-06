<?php
$pageTitle = 'Creator Posts';
$pageDescription = 'Manage Stonefellow creator/news posts, feed publish states, media links, and fan discussion threads.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/posts.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $action = (string)($_POST['action'] ?? 'save');
  $id = (int)($_POST['id'] ?? 0);
  if ($action === 'delete') { sf_post_delete($id); sf_admin_flash('success', 'Post deleted.'); sf_admin_redirect(); }
  if ($action === 'status') { sf_post_update_status($id, (string)($_POST['status'] ?? 'draft')); sf_admin_flash('success', 'Post status updated.'); sf_admin_redirect(); }
  $newId = sf_post_save($_POST, $id);
  sf_admin_flash($newId ? 'success' : 'error', $newId ? 'Post saved.' : 'Post was not saved. Confirm the posts table is installed.');
  sf_admin_redirect($newId ? sf_url('admin/posts.php?edit=' . $newId) : null);
}

require __DIR__ . '/../includes/header.php';
$posts = sf_posts_all('', 200);
$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId > 0 && sf_eng_table_exists('creator_posts') ? sf_eng_fetch_one('SELECT * FROM creator_posts WHERE id=? LIMIT 1', [$editId]) : null;
$summary = ['all'=>count($posts),'draft'=>0,'scheduled'=>0,'published'=>0,'archived'=>0];
foreach ($posts as $post) { $summary[(string)($post['status'] ?? 'draft')] = ($summary[(string)($post['status'] ?? 'draft')] ?? 0) + 1; }
sf_admin_shell_start('Creator Posts', 'News feed manager', 'Create and publish Stonefellow news posts, soundtrack notes, episode drops, and fan discussion entry points.', 'posts');
?>
<section class="sf-admin-card-grid">
  <a class="sf-admin-action-card" href="<?= sf_url('feed.php') ?>"><span>Public</span><strong>Feed</strong><small>Open the public creator/news feed.</small></a>
  <div class="sf-admin-action-card"><span>Published</span><strong><?= (int)$summary['published'] ?></strong><small>Live posts.</small></div>
  <div class="sf-admin-action-card"><span>Drafts</span><strong><?= (int)$summary['draft'] ?></strong><small>Needs content/publish review.</small></div>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/comments.php') ?>"><span>Comments</span><strong>Moderation</strong><small>Manage post and content comments.</small></a>
</section>
<section class="sf-admin-two-col sf-admin-two-col-wide">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Posts</span><h2><?= count($posts) ?> records</h2></div><a href="<?= sf_url('admin/posts.php') ?>">New</a></div>
    <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Post</th><th>Type</th><th>Status</th><th>Published</th><th>Actions</th></tr></thead><tbody>
      <?php foreach ($posts as $post): ?><tr><td><strong><?= sf_admin_h($post['title'] ?? '') ?></strong><small><?= sf_admin_h($post['slug'] ?? '') ?></small></td><td><?= sf_admin_h($post['post_type'] ?? 'news') ?></td><td><?= sf_admin_status_badge((string)($post['status'] ?? 'draft')) ?></td><td><?= sf_admin_h($post['published_at'] ?? '') ?></td><td><a href="<?= sf_url('admin/posts.php?edit=' . (int)($post['id'] ?? 0)) ?>">Edit</a> · <a href="<?= sf_url('post.php?slug=' . urlencode((string)($post['slug'] ?? ''))) ?>">Open</a></td></tr><?php endforeach; ?>
      <?php if (!$posts): ?><tr><td colspan="5">No posts found yet.</td></tr><?php endif; ?>
    </tbody></table></div>
  </article>
  <aside class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow"><?= $edit ? 'Edit' : 'Create' ?></span><h2><?= $edit ? sf_admin_h($edit['title'] ?? '') : 'New creator post' ?></h2></div></div>
    <form class="sf-admin-form" method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
      <label>Title<input name="title" value="<?= sf_admin_h($edit['title'] ?? '') ?>" required<?= sf_admin_form_disabled_attr() ?>></label>
      <div class="sf-admin-form-grid"><label>Slug<input name="slug" value="<?= sf_admin_h($edit['slug'] ?? '') ?>" placeholder="auto-from-title"<?= sf_admin_form_disabled_attr() ?>></label><label>Post Type<?= sf_admin_select('post_type', ['news'=>'News','episode'=>'Episode','music'=>'Music','merch'=>'Merch','behind_scenes'=>'Behind Scenes'], $edit['post_type'] ?? 'news') ?></label></div>
      <label>Excerpt<textarea name="excerpt" rows="3"<?= sf_admin_form_disabled_attr() ?>><?= sf_admin_h($edit['excerpt'] ?? '') ?></textarea></label>
      <label>Body<textarea name="body" rows="9" required<?= sf_admin_form_disabled_attr() ?>><?= sf_admin_h($edit['body'] ?? '') ?></textarea></label>
      <label>Image Path<input name="image_path" value="<?= sf_admin_h($edit['image_path'] ?? '') ?>" placeholder="images/episodes/episode-01.png"<?= sf_admin_form_disabled_attr() ?>></label>
      <div class="sf-admin-form-grid"><label>Status<?= sf_admin_select('status', ['draft'=>'Draft','scheduled'=>'Scheduled','published'=>'Published','archived'=>'Archived'], $edit['status'] ?? 'draft') ?></label><label>Published At<input type="datetime-local" name="published_at" value="<?= !empty($edit['published_at']) ? sf_admin_h(str_replace(' ', 'T', substr((string)$edit['published_at'],0,16))) : '' ?>"<?= sf_admin_form_disabled_attr() ?>></label></div>
      <div class="sf-admin-form-grid"><label>Linked Type<?= sf_admin_select('linked_content_type', [''=>'None','episode'=>'Episode','video'=>'Video','song'=>'Song','album'=>'Album','product'=>'Product'], $edit['linked_content_type'] ?? '') ?></label><label>Linked Slug<input name="linked_content_slug" value="<?= sf_admin_h($edit['linked_content_slug'] ?? '') ?>"<?= sf_admin_form_disabled_attr() ?>></label></div>
      <label>Linked Content ID<input type="number" name="linked_content_id" value="<?= sf_admin_h($edit['linked_content_id'] ?? '') ?>"<?= sf_admin_form_disabled_attr() ?>></label>
      <label class="sf-admin-check"><input type="checkbox" name="is_featured" value="1"<?= !empty($edit['is_featured']) ? ' checked' : '' ?><?= sf_admin_form_disabled_attr() ?>> Feature this post</label>
      <div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>>Save Post</button><?php if ($edit): ?><a href="<?= sf_url('post.php?slug=' . urlencode((string)$edit['slug'])) ?>">Open Public</a><?php endif; ?></div>
    </form>
    <?php if ($edit): ?><form class="sf-admin-delete-form" method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?= sf_admin_confirm_delete_button('Delete Post') ?></form><?php endif; ?>
  </aside>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
