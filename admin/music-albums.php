<?php
$pageTitle = 'Manage Albums';
$pageDescription = 'Create, edit, publish, and archive Stonefellow albums.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';

if ((($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
  if (!sf_admin_db_ready() || !sf_admin_table_exists('albums')) {
    sf_admin_flash('warning', 'Albums table is not available. Configure the database and run the base SQL first.');
    sf_admin_redirect();
  }
  $action = $_POST['action'] ?? '';
  $id = sf_admin_int($_POST['id'] ?? null, 0) ?? 0;
  if ($action === 'save_album') {
    $title = trim((string)($_POST['title'] ?? ''));
    if ($title === '') {
      sf_admin_flash('error', 'Album title is required.');
      sf_admin_redirect();
    }
    $slug = trim((string)($_POST['slug'] ?? '')) ?: sf_admin_slugify($title);
    $payload = [
      'title' => $title,
      'slug' => $slug,
      'artist' => trim((string)($_POST['artist'] ?? 'Stonefellow')) ?: 'Stonefellow',
      'description' => sf_admin_nullable_string($_POST['description'] ?? ''),
      'cover_asset_id' => sf_admin_int($_POST['cover_asset_id'] ?? null),
      'release_date' => sf_admin_date_or_null('release_date'),
      'status' => $_POST['status'] ?? 'draft',
    ];
    $before = $id > 0 ? sf_admin_fetch_one('SELECT * FROM albums WHERE id = ?', [$id]) : null;
    if ($id > 0) {
      sf_admin_execute('UPDATE albums SET title=?, slug=?, artist=?, description=?, cover_asset_id=?, release_date=?, status=? WHERE id=?', array_merge(array_values($payload), [$id]));
      sf_admin_audit('update_album', 'album', $id, $before, $payload);
      sf_admin_flash('success', 'Album updated.');
    } else {
      sf_admin_execute('INSERT INTO albums (title, slug, artist, description, cover_asset_id, release_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)', array_values($payload));
      $newId = (int)(sf_admin_db()?->lastInsertId() ?: 0);
      sf_admin_audit('create_album', 'album', $newId, null, $payload);
      sf_admin_flash('success', 'Album created.');
    }
  }
  if ($action === 'delete_album' && $id > 0) {
    $before = sf_admin_fetch_one('SELECT * FROM albums WHERE id = ?', [$id]);
    sf_admin_execute('DELETE FROM albums WHERE id = ?', [$id]);
    sf_admin_audit('delete_album', 'album', $id, $before, null);
    sf_admin_flash('success', 'Album deleted.');
  }
  sf_admin_redirect();
}

require __DIR__ . '/../includes/header.php';
$albums = sf_admin_albums();
$assets = sf_admin_assets('image');
$editId = sf_admin_int($_GET['edit'] ?? null, 0) ?? 0;
$edit = sf_admin_selected_row($albums, 'albums', $editId) ?: [];

sf_admin_shell_start('Albums', 'Manage albums', 'Create the soundtrack/album containers that songs attach to and members browse from album pages.', 'albums');
?>
<section class="sf-admin-two-col sf-admin-two-col-wide">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head">
      <div><span class="sf-panel-eyebrow">Album Records</span><h2><?= count($albums) ?> albums</h2></div>
      <a href="<?= sf_url('admin/music-albums.php') ?>">New Album</a>
    </div>
    <div class="sf-admin-table-wrap">
      <table class="sf-admin-table">
        <thead><tr><th>Title</th><th>Artist</th><th>Release</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($albums as $album): ?>
            <tr>
              <td><strong><?= sf_admin_h($album['title'] ?? '') ?></strong><small><?= sf_admin_h($album['slug'] ?? '') ?></small></td>
              <td><?= sf_admin_h($album['artist'] ?? 'Stonefellow') ?></td>
              <td><?= sf_admin_h($album['release_date'] ?? '—') ?></td>
              <td><?= sf_admin_status_badge((string)($album['status'] ?? 'draft')) ?></td>
              <td><a href="<?= sf_url('admin/music-albums.php?edit=' . (int)($album['id'] ?? 0)) ?>">Edit</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </article>

  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow"><?= $edit ? 'Edit' : 'Create' ?></span><h2><?= $edit ? sf_admin_h($edit['title'] ?? '') : 'New album' ?></h2></div></div>
    <form class="sf-admin-form" method="post">
      <?= sf_csrf_field() ?>
      <input type="hidden" name="action" value="save_album">
      <input type="hidden" name="id" value="<?= sf_admin_h($edit['id'] ?? '') ?>">
      <label>Album Title<input name="title" value="<?= sf_admin_h($edit['title'] ?? '') ?>" required<?= sf_admin_form_disabled_attr() ?>></label>
      <label>Slug<input name="slug" value="<?= sf_admin_h($edit['slug'] ?? '') ?>" placeholder="auto-generated if blank"<?= sf_admin_form_disabled_attr() ?>></label>
      <label>Artist<input name="artist" value="<?= sf_admin_h($edit['artist'] ?? 'Stonefellow') ?>"<?= sf_admin_form_disabled_attr() ?>></label>
      <label>Description<textarea name="description" rows="5"<?= sf_admin_form_disabled_attr() ?>><?= sf_admin_h($edit['description'] ?? '') ?></textarea></label>
      <label>Cover Image<?= sf_admin_asset_select('cover_asset_id', $assets, $edit['cover_asset_id'] ?? '', 'image') ?></label>
      <?= sf_admin_asset_preview_by_id($edit['cover_asset_id'] ?? null, 'image') ?>
      <p class="sf-admin-form-note"><a href="<?= sf_url('admin/uploads.php?type=image') ?>">Upload or manage cover images</a></p>
      <div class="sf-admin-form-grid">
        <label>Release Date<input type="date" name="release_date" value="<?= sf_admin_h($edit['release_date'] ?? '') ?>"<?= sf_admin_form_disabled_attr() ?>></label>
        <label>Status<?= sf_admin_select('status', ['draft'=>'Draft','published'=>'Published','archived'=>'Archived'], $edit['status'] ?? 'draft') ?></label>
      </div>
      <div class="sf-admin-form-actions">
        <button type="submit"<?= sf_admin_form_disabled_attr() ?>><?= $edit ? 'Save Album' : 'Create Album' ?></button>
      </div>
    </form>
    <?php if ($edit): ?>
      <form method="post" class="sf-admin-delete-form">
        <input type="hidden" name="action" value="delete_album"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
        <?= sf_admin_confirm_delete_button('Delete Album') ?>
      </form>
    <?php endif; ?>
  </article>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
