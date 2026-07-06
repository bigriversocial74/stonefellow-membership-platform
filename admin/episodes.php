<?php
$pageTitle = 'Manage Episodes';
$pageDescription = 'Create, edit, schedule, publish, and archive Stonefellow episodes.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
    sf_admin_flash('error', 'Security check failed.');
    sf_admin_redirect();
  }
  if (!sf_admin_db_ready() || !sf_admin_table_exists('episodes')) {
    sf_admin_flash('warning', 'Episodes table is not available. Configure the database and run the SQL first.');
    sf_admin_redirect();
  }
  $action = $_POST['action'] ?? '';
  $id = sf_admin_int($_POST['id'] ?? null, 0) ?? 0;
  if ($action === 'save_episode') {
    $title = trim((string)($_POST['title'] ?? ''));
    if ($title === '') {
      sf_admin_flash('error', 'Episode title is required.');
      sf_admin_redirect();
    }
    $payload = [
      'season_id' => sf_admin_int($_POST['season_id'] ?? null),
      'season_number' => sf_admin_int($_POST['season_number'] ?? null, 1) ?? 1,
      'episode_number' => sf_admin_int($_POST['episode_number'] ?? null, 1) ?? 1,
      'title' => $title,
      'slug' => trim((string)($_POST['slug'] ?? '')) ?: sf_admin_slugify($title),
      'production_code' => sf_admin_nullable_string($_POST['production_code'] ?? ''),
      'short_description' => sf_admin_nullable_string($_POST['short_description'] ?? ''),
      'episode_summary' => sf_admin_nullable_string($_POST['episode_summary'] ?? ''),
      'runtime_minutes' => sf_admin_int($_POST['runtime_minutes'] ?? null),
      'release_at' => sf_admin_datetime_or_null('release_at'),
      'access_level' => $_POST['access_level'] ?? 'subscriber',
      'poster_asset_id' => sf_admin_int($_POST['poster_asset_id'] ?? null),
      'hero_asset_id' => sf_admin_int($_POST['hero_asset_id'] ?? null),
      'next_episode_id' => sf_admin_int($_POST['next_episode_id'] ?? null),
      'is_featured' => sf_admin_checkbox('is_featured'),
      'status' => $_POST['status'] ?? 'draft',
    ];
    $before = $id > 0 ? sf_admin_fetch_one('SELECT * FROM episodes WHERE id = ?', [$id]) : null;
    sf_admin_build_insert_update('episodes', $payload, $id);
    $newId = $id ?: (int)(sf_admin_db()?->lastInsertId() ?: 0);
    sf_admin_audit($id > 0 ? 'update_episode' : 'create_episode', 'episode', $newId, $before, $payload);
    sf_admin_flash('success', $id > 0 ? 'Episode updated.' : 'Episode created.');
    sf_admin_redirect(sf_url('admin/episodes.php' . ($newId ? '?edit=' . $newId : '')));
  }
  if ($action === 'delete_episode' && $id > 0) {
    $before = sf_admin_fetch_one('SELECT * FROM episodes WHERE id = ?', [$id]);
    sf_admin_execute('DELETE FROM episodes WHERE id = ?', [$id]);
    sf_admin_audit('delete_episode', 'episode', $id, $before, null);
    sf_admin_flash('success', 'Episode deleted.');
  }
  sf_admin_redirect();
}

require __DIR__ . '/../includes/header.php';
$episodesList = sf_admin_episodes();
$seasons = sf_admin_seasons();
$assets = sf_admin_assets('image');
$editId = sf_admin_int($_GET['edit'] ?? null, 0) ?? 0;
$edit = sf_admin_selected_row($episodesList, 'episodes', $editId) ?: [];
$columns = sf_admin_episode_columns();

sf_admin_shell_start('Episodes', 'Episode publishing manager v2', 'Manage episodes as full content records with seasons, posters, release windows, access gates, summaries, and watch-next routing.', 'episodes');
?>
<section class="sf-admin-two-col sf-admin-two-col-wide">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Episodes</span><h2><?= count($episodesList) ?> episodes</h2></div><a href="<?= sf_url('admin/episodes.php') ?>">New Episode</a></div>
    <div class="sf-admin-table-wrap">
      <table class="sf-admin-table">
        <thead><tr><th>Episode</th><th>Release</th><th>Access</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($episodesList as $episode): ?>
          <tr>
            <td><strong><?= sf_admin_h(($episode['number'] ?? ('S' . ($episode['season_number'] ?? 1) . ':E' . ($episode['episode_number'] ?? ''))) . ' — ' . ($episode['title'] ?? '')) ?></strong><small><?= sf_admin_h($episode['slug'] ?? '') ?></small></td>
            <td><?= sf_admin_h($episode['release_at'] ?? 'Unscheduled') ?></td>
            <td><?= sf_admin_h(sf_access_label((string)($episode['access_level'] ?? 'subscriber'))) ?></td>
            <td><?= sf_admin_status_badge((string)($episode['status'] ?? 'draft')) ?></td>
            <td><a href="<?= sf_url('admin/episodes.php?edit=' . (int)($episode['id'] ?? 0)) ?>">Edit</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </article>

  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow"><?= $edit ? 'Edit' : 'Create' ?></span><h2><?= $edit ? sf_admin_h($edit['title'] ?? '') : 'New episode' ?></h2></div></div>
    <form class="sf-admin-form" method="post">
      <?= sf_csrf_field() ?><input type="hidden" name="action" value="save_episode"><input type="hidden" name="id" value="<?= sf_admin_h($edit['id'] ?? '') ?>">
      <?php if (in_array('season_id', $columns, true)): ?><label>Season<?= sf_admin_relation_select('season_id', $seasons, $edit['season_id'] ?? '', 'Choose season') ?></label><?php endif; ?>
      <div class="sf-admin-form-grid"><label>Season Number<input type="number" name="season_number" value="<?= sf_admin_h($edit['season_number'] ?? 1) ?>"<?= sf_admin_form_disabled_attr() ?>></label><label>Episode Number<input type="number" name="episode_number" value="<?= sf_admin_h($edit['episode_number'] ?? 1) ?>"<?= sf_admin_form_disabled_attr() ?>></label></div>
      <label>Title<input name="title" value="<?= sf_admin_h($edit['title'] ?? '') ?>" required<?= sf_admin_form_disabled_attr() ?>></label>
      <div class="sf-admin-form-grid"><label>Slug<input name="slug" value="<?= sf_admin_h($edit['slug'] ?? '') ?>" placeholder="auto-generated if blank"<?= sf_admin_form_disabled_attr() ?>></label><?php if (in_array('production_code', $columns, true)): ?><label>Production Code<input name="production_code" value="<?= sf_admin_h($edit['production_code'] ?? '') ?>" placeholder="S01E01"<?= sf_admin_form_disabled_attr() ?>></label><?php endif; ?></div>
      <label>Short Description<textarea name="short_description" rows="3"<?= sf_admin_form_disabled_attr() ?>><?= sf_admin_h($edit['short_description'] ?? '') ?></textarea></label>
      <?php if (in_array('episode_summary', $columns, true)): ?><label>Full Episode Summary<textarea name="episode_summary" rows="6"<?= sf_admin_form_disabled_attr() ?>><?= sf_admin_h($edit['episode_summary'] ?? '') ?></textarea></label><?php endif; ?>
      <div class="sf-admin-form-grid"><label>Runtime Minutes<input type="number" name="runtime_minutes" value="<?= sf_admin_h($edit['runtime_minutes'] ?? '') ?>"<?= sf_admin_form_disabled_attr() ?>></label><?php if (in_array('release_at', $columns, true)): ?><label>Release Date/Time<input type="datetime-local" name="release_at" value="<?= sf_admin_h(isset($edit['release_at']) ? str_replace(' ', 'T', substr((string)$edit['release_at'], 0, 16)) : '') ?>"<?= sf_admin_form_disabled_attr() ?>></label><?php endif; ?></div>
      <?php if (in_array('poster_asset_id', $columns, true)): ?><label>Poster Image<?= sf_admin_asset_select('poster_asset_id', $assets, $edit['poster_asset_id'] ?? '', 'image') ?></label><?= sf_admin_asset_preview_by_id($edit['poster_asset_id'] ?? null, 'image') ?><?php endif; ?>
      <?php if (in_array('hero_asset_id', $columns, true)): ?><label>Hero Image<?= sf_admin_asset_select('hero_asset_id', $assets, $edit['hero_asset_id'] ?? '', 'image') ?></label><?= sf_admin_asset_preview_by_id($edit['hero_asset_id'] ?? null, 'image') ?><?php endif; ?>
      <?php if (in_array('next_episode_id', $columns, true)): ?><label>Watch Next Episode<?= sf_admin_relation_select('next_episode_id', $episodesList, $edit['next_episode_id'] ?? '', 'Auto / none') ?></label><?php endif; ?>
      <div class="sf-admin-form-grid"><?php if (in_array('access_level', $columns, true)): ?><label>Access<?= sf_admin_select('access_level', ['public'=>'Public','free_account'=>'Free Account','subscriber'=>'Subscriber','premium'=>'Premium','founding_fan'=>'Founding Fan'], $edit['access_level'] ?? 'subscriber') ?></label><?php endif; ?><label>Status<?= sf_admin_select('status', ['draft'=>'Draft','published'=>'Published','archived'=>'Archived'], $edit['status'] ?? 'draft') ?></label></div>
      <?php if (in_array('is_featured', $columns, true)): ?><label class="sf-admin-check"><input type="checkbox" name="is_featured" value="1" <?= !empty($edit['is_featured']) ? 'checked' : '' ?><?= sf_admin_form_disabled_attr() ?>> Featured episode</label><?php endif; ?>
      <div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>><?= $edit ? 'Save Episode' : 'Create Episode' ?></button></div>
    </form>
    <?php if ($edit): ?><form method="post" class="sf-admin-delete-form"><?= sf_csrf_field() ?><input type="hidden" name="action" value="delete_episode"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>"><?= sf_admin_confirm_delete_button('Delete Episode') ?></form><?php endif; ?>
  </article>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
