<?php
$pageTitle = 'Series Assets';
$pageDescription = 'Reusable Stonefellow series assets for props, instruments, vehicles, wardrobe, and recurring story items.';
$pageClass = 'membership-page admin-catalog-page storyboarding-system-page series-assets-page';
require __DIR__ . '/../includes/story_series_assets.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) { sf_admin_flash('error', 'Security check failed.'); sf_admin_redirect(); }
  if (!sf_series_assets_ready()) { sf_admin_flash('warning', 'Import database/story_series_assets_v1.sql before saving series assets.'); sf_admin_redirect(); }
  $action = (string)($_POST['action'] ?? 'save_asset');
  $id = sf_admin_int($_POST['id'] ?? null, 0) ?? 0;
  if ($action === 'save_asset') {
    $name = trim((string)($_POST['asset_name'] ?? ''));
    if ($name === '') { sf_admin_flash('error', 'Asset name is required.'); sf_admin_redirect(); }
    $imagePath = sf_admin_nullable_string($_POST['image_path'] ?? '') ?: '';
    if (isset($_FILES['asset_image']) && is_array($_FILES['asset_image']) && (int)($_FILES['asset_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
      $upload = sf_admin_handle_upload('asset_image', 'image', 'story_series_asset', 'Series Asset - ' . $name, $name . ' reference image');
      if (empty($upload['ok'])) { sf_admin_flash('error', $upload['message'] ?? 'Asset image upload failed.'); sf_admin_redirect(sf_url('admin/series-assets.php' . ($id > 0 ? '?edit=' . $id : '?new=1'))); }
      $imagePath = (string)($upload['path'] ?? $imagePath);
    }
    $payload = ['asset_name'=>$name,'slug'=>trim((string)($_POST['slug'] ?? '')) ?: sf_story_v1_unique_slug('story_series_assets', $name, $id),'asset_type'=>$_POST['asset_type'] ?? 'prop','short_description'=>sf_admin_nullable_string($_POST['short_description'] ?? ''),'continuity_notes'=>sf_admin_nullable_string($_POST['continuity_notes'] ?? ''),'image_path'=>$imagePath,'status'=>$_POST['status'] ?? 'active','sort_order'=>sf_admin_int($_POST['sort_order'] ?? null, 10) ?? 10];
    $newId = sf_series_assets_save($payload, $id);
    sf_admin_flash($newId ? 'success' : 'error', $newId ? 'Series asset saved.' : 'Series asset could not be saved.');
    sf_admin_redirect(sf_url('admin/series-assets.php' . ($newId ? '?edit=' . $newId : '')));
  }
  if ($action === 'sync_asset_characters') {
    $assetId = $id;
    $characterIds = array_values(array_unique(array_filter(array_map('intval', $_POST['character_ids'] ?? []))));
    if ($assetId > 0) {
      sf_admin_execute('DELETE FROM story_character_series_assets WHERE story_series_asset_id = ?', [$assetId]);
      foreach ($characterIds as $characterId) sf_admin_execute('INSERT IGNORE INTO story_character_series_assets (story_character_id, story_series_asset_id) VALUES (?, ?)', [$characterId, $assetId]);
      sf_admin_flash('success', 'Asset character assignments saved.');
    }
    sf_admin_redirect(sf_url('admin/series-assets.php?edit=' . $assetId));
  }
  if ($action === 'sync_asset_scenes') {
    $assetId = $id;
    $sceneIds = array_values(array_unique(array_filter(array_map('intval', $_POST['storyboard_ids'] ?? []))));
    if ($assetId > 0) {
      sf_admin_execute('DELETE FROM storyboard_series_assets WHERE story_series_asset_id = ?', [$assetId]);
      foreach ($sceneIds as $storyboardId) sf_admin_execute('INSERT IGNORE INTO storyboard_series_assets (storyboard_id, story_series_asset_id) VALUES (?, ?)', [$storyboardId, $assetId]);
      sf_admin_flash('success', 'Asset scene assignments saved.');
    }
    sf_admin_redirect(sf_url('admin/series-assets.php?edit=' . $assetId));
  }
  sf_admin_redirect();
}

$assets = sf_series_assets_all();
$activeAssets = array_values(array_filter($assets, static fn($row) => (string)($row['status'] ?? 'active') === 'active'));
$editId = sf_admin_int($_GET['edit'] ?? null, 0) ?? 0;
$edit = sf_series_assets_find($assets, $editId) ?: [];
$showForm = isset($_GET['new']) || $edit;
$disabled = sf_series_assets_disabled_attr();
$characters = sf_story_v1_characters('active');
$sceneSheets = sf_story_v1_episode_storyboards(sf_admin_int($_GET['episode_id'] ?? null, 0) ?? 0);
if (!$sceneSheets && sf_storyboard_ready()) $sceneSheets = sf_storyboard_projects();
$assignedCharacterIds = [];
$assignedSceneIds = [];
if ($edit && sf_series_assets_ready()) {
  $assignedCharacterIds = array_map(static fn($row) => (int)($row['story_character_id'] ?? 0), sf_admin_fetch_all('SELECT story_character_id FROM story_character_series_assets WHERE story_series_asset_id = ?', [(int)$edit['id']]));
  $assignedSceneIds = array_map(static fn($row) => (int)($row['storyboard_id'] ?? 0), sf_admin_fetch_all('SELECT storyboard_id FROM storyboard_series_assets WHERE story_series_asset_id = ?', [(int)$edit['id']]));
}
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Series Assets', 'Series Assets Catalog', 'Manage reusable props, instruments, wardrobe, vehicles, and recurring story assets that stay consistent across the series.', 'series-assets');
?>
<style>.series-assets-page .sf-series-asset-thumb{width:64px;height:64px;border-radius:16px;border:1px solid rgba(232,198,127,.22);background:rgba(255,255,255,.04);background-size:cover;background-position:center;display:grid;place-items:center;color:rgba(255,255,255,.42);font-weight:950}.series-assets-page .sf-series-asset-image-field{display:grid;grid-template-columns:96px minmax(0,1fr);gap:14px;align-items:center;padding:14px;border:1px solid rgba(255,255,255,.08);border-radius:18px;background:rgba(0,0,0,.15)}.series-assets-page .sf-series-asset-image-field .sf-series-asset-thumb{width:96px;height:96px}.series-assets-page .sf-series-assignment-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px}@media(max-width:760px){.series-assets-page .sf-series-asset-image-field,.series-assets-page .sf-series-assignment-grid{grid-template-columns:1fr}.series-assets-page .sf-series-asset-image-field .sf-series-asset-thumb{width:100%;height:180px}}</style>
<?php if (!sf_series_assets_ready()): ?><section class="sf-story-v1-warning"><strong>SQL required:</strong> Import <code>database/story_series_assets_v1.sql</code> after the existing storyboarding SQL to enable Series Assets.</section><?php endif; ?>
<section class="sf-admin-card-grid sf-character-catalog-stats"><div class="sf-admin-action-card"><span>Total Assets</span><strong><?= count($assets) ?></strong><small>Reusable continuity assets.</small></div><div class="sf-admin-action-card"><span>Active</span><strong><?= count($activeAssets) ?></strong><small>Available for assignment.</small></div><div class="sf-admin-action-card"><span>Scene Uses</span><strong><?= array_sum(array_map(static fn($row)=>(int)($row['scene_count'] ?? 0), $assets)) ?></strong><small>Assigned scene sheets.</small></div></section>
<?php if (!$showForm): ?>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Catalog</span><h2>Series Assets</h2></div><a href="<?= sf_url('admin/series-assets.php?new=1') ?>">Add Asset</a></div><p class="sf-admin-copy">Create reusable assets like guitars, vans, key props, wardrobe, documents, and set pieces that should stay visually and narratively consistent across the series.</p><div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Image</th><th>Asset</th><th>Type</th><th>Description</th><th>Characters</th><th>Scenes</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php foreach ($assets as $asset): $image = trim((string)($asset['image_path'] ?? '')); ?><tr><td><div class="sf-series-asset-thumb"<?= $image !== '' ? ' style="background-image:url(' . sf_admin_h(sf_asset($image)) . ')"' : '' ?>><?= $image === '' ? 'IMG' : '' ?></div></td><td><strong><?= sf_admin_h($asset['asset_name'] ?? '') ?></strong><small><?= sf_admin_h($asset['slug'] ?? '') ?></small></td><td><?= sf_admin_h(sf_story_v1_status_label((string)($asset['asset_type'] ?? 'prop'))) ?></td><td><strong><?= sf_admin_h($asset['short_description'] ?? '') ?></strong><small><?= sf_admin_h($asset['continuity_notes'] ?? '') ?></small></td><td><?= (int)($asset['character_count'] ?? 0) ?></td><td><?= (int)($asset['scene_count'] ?? 0) ?></td><td><?= sf_admin_status_badge((string)($asset['status'] ?? 'active')) ?></td><td><a href="<?= sf_url('admin/series-assets.php?edit=' . (int)$asset['id']) ?>">Edit</a></td></tr><?php endforeach; ?><?php if (!$assets): ?><tr><td colspan="8">No series assets yet.</td></tr><?php endif; ?></tbody></table></div></section>
<?php else: ?>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow"><?= $edit ? 'Edit Asset' : 'Add Asset' ?></span><h2><?= $edit ? sf_admin_h($edit['asset_name'] ?? '') : 'New Series Asset' ?></h2></div><a href="<?= sf_url('admin/series-assets.php') ?>">Back to Assets</a></div><form class="sf-admin-form" method="post" enctype="multipart/form-data"><?= sf_csrf_field() ?><input type="hidden" name="action" value="save_asset"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>"><div class="sf-story-v1-form-grid"><label>Asset Name<input name="asset_name" value="<?= sf_admin_h($edit['asset_name'] ?? '') ?>" required<?= $disabled ?>></label><label>Type<?= sf_admin_select('asset_type', sf_series_asset_type_options(), $edit['asset_type'] ?? 'prop') ?></label><label>Status<?= sf_admin_select('status', sf_series_assets_status_options(), $edit['status'] ?? 'active') ?></label></div><div class="sf-story-v1-form-grid"><label>Slug<input name="slug" value="<?= sf_admin_h($edit['slug'] ?? '') ?>" placeholder="auto-generated"<?= $disabled ?>></label><label>Sort<input type="number" name="sort_order" value="<?= sf_admin_h($edit['sort_order'] ?? 10) ?>"<?= $disabled ?>></label></div><?php $editImage = trim((string)($edit['image_path'] ?? '')); ?><div class="sf-series-asset-image-field"><div class="sf-series-asset-thumb"<?= $editImage !== '' ? ' style="background-image:url(' . sf_admin_h(sf_asset($editImage)) . ')"' : '' ?>><?= $editImage === '' ? 'IMG' : '' ?></div><div><label>Upload Reference Image<input type="file" name="asset_image" accept="image/*"<?= $disabled ?>></label><label>Image Path<input name="image_path" value="<?= sf_admin_h($editImage) ?>" placeholder="images/props/jax-guitar.png"<?= $disabled ?>></label></div></div><label>Short Description<textarea name="short_description" rows="3"<?= $disabled ?>><?= sf_admin_h($edit['short_description'] ?? '') ?></textarea></label><label>Continuity Notes<textarea name="continuity_notes" rows="4"<?= $disabled ?>><?= sf_admin_h($edit['continuity_notes'] ?? '') ?></textarea></label><div class="sf-admin-form-actions"><button type="submit"<?= $disabled ?>>Save Asset</button></div></form></section>
<?php if ($edit): ?><section class="sf-series-assignment-grid"><div class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Characters</span><h2>Assign to Characters</h2></div></div><form class="sf-admin-form" method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="sync_asset_characters"><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><div class="sf-story-v1-characters"><?php foreach ($characters as $character): $cid=(int)($character['id'] ?? 0); ?><label><input type="checkbox" name="character_ids[]" value="<?= $cid ?>"<?= in_array($cid, $assignedCharacterIds, true) ? ' checked' : '' ?><?= $disabled ?>><?= sf_admin_h($character['character_name'] ?? 'Character') ?></label><?php endforeach; ?></div><div class="sf-admin-form-actions"><button type="submit"<?= $disabled ?>>Save Character Assignments</button></div></form></div><div class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Scenes</span><h2>Assign to Scene Sheets</h2></div></div><form class="sf-admin-form" method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="sync_asset_scenes"><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><div class="sf-story-v1-characters"><?php foreach ($sceneSheets as $scene): $sid=(int)($scene['id'] ?? 0); ?><label><input type="checkbox" name="storyboard_ids[]" value="<?= $sid ?>"<?= in_array($sid, $assignedSceneIds, true) ? ' checked' : '' ?><?= $disabled ?>><?= sf_admin_h($scene['title'] ?? 'Scene') ?></label><?php endforeach; ?></div><div class="sf-admin-form-actions"><button type="submit"<?= $disabled ?>>Save Scene Assignments</button></div></form></div></section><?php endif; ?>
<?php endif; ?>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
