<?php
$pageTitle = 'Manage Seasons';
$pageDescription = 'Create seasons, release windows, and season-level publishing controls.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
    sf_admin_flash('error', 'Security check failed.');
    sf_admin_redirect();
  }
  if (!sf_admin_db_ready() || !sf_admin_table_exists('seasons')) {
    sf_admin_flash('warning', 'Run migration 009 before saving seasons.');
    sf_admin_redirect();
  }
  $action = $_POST['action'] ?? '';
  $id = sf_admin_int($_POST['id'] ?? null, 0) ?? 0;
  if ($action === 'save_season') {
    $title = trim((string)($_POST['title'] ?? ''));
    if ($title === '') {
      sf_admin_flash('error', 'Season title is required.');
      sf_admin_redirect();
    }
    $payload = [
      'season_number' => sf_admin_int($_POST['season_number'] ?? null, 1) ?? 1,
      'title' => $title,
      'slug' => trim((string)($_POST['slug'] ?? '')) ?: sf_admin_slugify($title),
      'description' => sf_admin_nullable_string($_POST['description'] ?? ''),
      'release_year' => sf_admin_int($_POST['release_year'] ?? null),
      'status' => $_POST['status'] ?? 'draft',
      'poster_asset_id' => sf_admin_int($_POST['poster_asset_id'] ?? null),
    ];
    $before = $id > 0 ? sf_admin_fetch_one('SELECT * FROM seasons WHERE id=?', [$id]) : null;
    sf_admin_build_insert_update('seasons', $payload, $id);
    $newId = $id ?: (int)(sf_admin_db()?->lastInsertId() ?: 0);
    sf_admin_audit($id > 0 ? 'update_season' : 'create_season', 'season', $newId, $before, $payload);
    sf_admin_flash('success', $id > 0 ? 'Season updated.' : 'Season created.');
    sf_admin_redirect(sf_url('admin/seasons.php' . ($newId ? '?edit=' . $newId : '')));
  }
  if ($action === 'delete_season' && $id > 0) {
    $before = sf_admin_fetch_one('SELECT * FROM seasons WHERE id=?', [$id]);
    sf_admin_execute('DELETE FROM seasons WHERE id=?', [$id]);
    sf_admin_audit('delete_season', 'season', $id, $before, null);
    sf_admin_flash('success', 'Season deleted.');
  }
  sf_admin_redirect();
}

require __DIR__ . '/../includes/header.php';
$seasons = sf_admin_seasons();
$assets = sf_admin_assets('image');
$editId = sf_admin_int($_GET['edit'] ?? null, 0) ?? 0;
$edit = sf_admin_selected_row($seasons, 'seasons', $editId) ?: [];
sf_admin_shell_start('Seasons', 'Season publishing manager', 'Organize episodes by season, add poster art, and control season-level publishing status.', 'seasons');
?>
<section class="sf-admin-two-col sf-admin-two-col-wide">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Seasons</span><h2><?= count($seasons) ?> seasons</h2></div><a href="<?= sf_url('admin/seasons.php') ?>">New Season</a></div>
    <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Season</th><th>Year</th><th>Status</th><th></th></tr></thead><tbody>
      <?php foreach ($seasons as $season): ?><tr><td><strong><?= sf_admin_h($season['title'] ?? '') ?></strong><small><?= sf_admin_h($season['slug'] ?? '') ?></small></td><td><?= sf_admin_h($season['release_year'] ?? '—') ?></td><td><?= sf_admin_status_badge((string)($season['status'] ?? 'draft')) ?></td><td><a href="<?= sf_url('admin/seasons.php?edit=' . (int)($season['id'] ?? 0)) ?>">Edit</a></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </article>
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow"><?= $edit ? 'Edit' : 'Create' ?></span><h2><?= $edit ? sf_admin_h($edit['title'] ?? '') : 'New season' ?></h2></div></div>
    <form class="sf-admin-form" method="post">
      <?= sf_csrf_field() ?><input type="hidden" name="action" value="save_season"><input type="hidden" name="id" value="<?= sf_admin_h($edit['id'] ?? '') ?>">
      <div class="sf-admin-form-grid"><label>Season Number<input type="number" name="season_number" value="<?= sf_admin_h($edit['season_number'] ?? 1) ?>"<?= sf_admin_form_disabled_attr() ?>></label><label>Release Year<input type="number" name="release_year" value="<?= sf_admin_h($edit['release_year'] ?? date('Y')) ?>"<?= sf_admin_form_disabled_attr() ?>></label></div>
      <label>Title<input name="title" value="<?= sf_admin_h($edit['title'] ?? '') ?>" required<?= sf_admin_form_disabled_attr() ?>></label>
      <label>Slug<input name="slug" value="<?= sf_admin_h($edit['slug'] ?? '') ?>" placeholder="auto-generated if blank"<?= sf_admin_form_disabled_attr() ?>></label>
      <label>Description<textarea name="description" rows="4"<?= sf_admin_form_disabled_attr() ?>><?= sf_admin_h($edit['description'] ?? '') ?></textarea></label>
      <label>Poster Image<?= sf_admin_asset_select('poster_asset_id', $assets, $edit['poster_asset_id'] ?? '', 'image') ?></label>
      <?= sf_admin_asset_preview_by_id($edit['poster_asset_id'] ?? null, 'image') ?>
      <label>Status<?= sf_admin_select('status', ['draft'=>'Draft','published'=>'Published','archived'=>'Archived'], $edit['status'] ?? 'draft') ?></label>
      <div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>>Save Season</button></div>
    </form>
    <?php if ($edit): ?><form method="post" class="sf-admin-delete-form"><?= sf_csrf_field() ?><input type="hidden" name="action" value="delete_season"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>"><?= sf_admin_confirm_delete_button('Delete Season') ?></form><?php endif; ?>
  </article>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
