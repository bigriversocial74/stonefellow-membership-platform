<?php
$pageTitle = 'Character Catalog';
$pageDescription = 'Main reusable Stonefellow character catalog for seasons, episodes, scenes, and storyboard builder assignments.';
$pageClass = 'membership-page admin-catalog-page storyboarding-system-page character-catalog-page';
require __DIR__ . '/../includes/storyboarding_system.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) { sf_admin_flash('error', 'Security check failed.'); sf_admin_redirect(); }
  if (!sf_story_v1_ready()) { sf_admin_flash('warning', 'Import database/storyboarding_system_v1.sql before saving characters.'); sf_admin_redirect(); }
  $action = (string)($_POST['action'] ?? 'save_character');
  $id = sf_admin_int($_POST['id'] ?? null, 0) ?? 0;
  if ($action === 'save_character') {
    $name = trim((string)($_POST['character_name'] ?? ''));
    if ($name === '') { sf_admin_flash('error', 'Character name is required.'); sf_admin_redirect(); }
    $imagePath = sf_admin_nullable_string($_POST['image_path'] ?? '') ?: '';
    if (isset($_FILES['character_image']) && is_array($_FILES['character_image']) && (int)($_FILES['character_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
      $upload = sf_admin_handle_upload('character_image', 'image', 'story_character_image', 'Character Image - ' . $name, $name . ' character image');
      if (empty($upload['ok'])) {
        sf_admin_flash('error', $upload['message'] ?? 'Character image upload failed.');
        sf_admin_redirect(sf_url('admin/characters.php' . ($id > 0 ? '?edit=' . $id : '?new=1')));
      }
      $imagePath = (string)($upload['path'] ?? $imagePath);
    }
    $payload = [
      'character_name' => $name,
      'slug' => trim((string)($_POST['slug'] ?? '')) ?: sf_story_v1_unique_slug('story_characters', $name, $id),
      'actor_name' => sf_admin_nullable_string($_POST['actor_name'] ?? '') ?: '',
      'role_type' => $_POST['role_type'] ?? 'supporting',
      'short_bio' => sf_admin_nullable_string($_POST['short_bio'] ?? ''),
      'motivation' => sf_admin_nullable_string($_POST['motivation'] ?? ''),
      'personality_notes' => sf_admin_nullable_string($_POST['personality_notes'] ?? ''),
      'relationship_notes' => sf_admin_nullable_string($_POST['relationship_notes'] ?? ''),
      'season_arc' => sf_admin_nullable_string($_POST['season_arc'] ?? ''),
      'image_path' => $imagePath,
      'status' => $_POST['status'] ?? 'active',
      'sort_order' => sf_admin_int($_POST['sort_order'] ?? null, 10) ?? 10,
    ];
    $newId = sf_story_v1_save_row('story_characters', $payload, $id);
    sf_admin_flash($newId ? 'success' : 'error', $newId ? 'Character saved to the master catalog.' : 'Character could not be saved.');
    sf_admin_redirect(sf_url('admin/characters.php' . ($newId ? '?edit=' . $newId : '')));
  }
  sf_admin_redirect();
}

$characters = sf_story_v1_characters();
$activeCharacters = array_values(array_filter($characters, static fn($row) => (string)($row['status'] ?? 'active') === 'active'));
$editId = sf_admin_int($_GET['edit'] ?? null, 0) ?? 0;
$edit = sf_story_v1_find($characters, $editId) ?: [];
$showForm = isset($_GET['new']) || $edit;
$disabled = sf_story_v1_disabled_attr();
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Characters', 'Character Catalog', 'Manage the master character profiles used by seasons, episodes, scenes, and storyboard builder assignments.', 'characters');
?>
<style>
.character-catalog-page .sf-character-image-preview{width:64px;height:64px;border-radius:16px;border:1px solid rgba(232,198,127,.22);background:rgba(255,255,255,.04);background-size:cover;background-position:center;display:grid;place-items:center;color:rgba(255,255,255,.42);font-weight:950}.character-catalog-page .sf-character-image-field{display:grid;grid-template-columns:96px minmax(0,1fr);gap:14px;align-items:center;padding:14px;border:1px solid rgba(255,255,255,.08);border-radius:18px;background:rgba(0,0,0,.15)}.character-catalog-page .sf-character-image-field .sf-character-image-preview{width:96px;height:96px}.character-catalog-page .sf-character-upload-row{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:end}.character-catalog-page .sf-character-upload-row button{min-height:42px}.character-catalog-page .sf-character-image-path{margin-top:10px}@media(max-width:680px){.character-catalog-page .sf-character-image-field,.character-catalog-page .sf-character-upload-row{grid-template-columns:1fr}.character-catalog-page .sf-character-image-field .sf-character-image-preview{width:100%;height:180px}}
</style>
<?php if (!sf_story_v1_ready()): ?><section class="sf-story-v1-warning"><strong>SQL required:</strong> Import <code>database/storyboarding_system_v1.sql</code> to enable live character saves.</section><?php endif; ?>

<section class="sf-admin-card-grid sf-character-catalog-stats">
  <div class="sf-admin-action-card"><span>Total Characters</span><strong><?= count($characters) ?></strong><small>Reusable catalog profiles.</small></div>
  <div class="sf-admin-action-card"><span>Active</span><strong><?= count($activeCharacters) ?></strong><small>Available for assignment.</small></div>
  <div class="sf-admin-action-card"><span>Assignments</span><strong><?= array_sum(array_map(static fn($row) => (int)($row['scene_count'] ?? 0), $characters)) ?></strong><small>Scene usage count.</small></div>
</section>

<?php if (!$showForm): ?>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Catalog</span><h2>Master Characters</h2></div><a href="<?= sf_url('admin/characters.php?new=1') ?>">Add Character</a></div>
  <p class="sf-admin-copy">This is the source of truth. Seasons, episodes, and scene builders pull from these catalog profiles when assigning characters.</p>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Image</th><th>Character</th><th>Role</th><th>Bio / Motivation</th><th>Scene Uses</th><th>Status</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($characters as $character): $characterImage = trim((string)($character['image_path'] ?? '')); ?>
      <tr><td><div class="sf-character-image-preview"<?= $characterImage !== '' ? ' style="background-image:url(' . sf_admin_h(sf_asset($characterImage)) . ')"' : '' ?>><?= $characterImage === '' ? 'IMG' : '' ?></div></td><td><strong><?= sf_admin_h($character['character_name'] ?? '') ?></strong><small><?= sf_admin_h($character['actor_name'] ?? '') ?></small></td><td><?= sf_admin_h(sf_story_v1_status_label((string)($character['role_type'] ?? 'supporting'))) ?></td><td><strong><?= sf_admin_h($character['short_bio'] ?? '') ?></strong><small><?= sf_admin_h($character['motivation'] ?? '') ?></small></td><td><?= (int)($character['scene_count'] ?? 0) ?></td><td><?= sf_admin_status_badge((string)($character['status'] ?? 'active')) ?></td><td><a href="<?= sf_url('admin/characters.php?edit=' . (int)$character['id']) ?>">Edit</a></td></tr>
    <?php endforeach; ?>
    <?php if (!$characters): ?><tr><td colspan="7">No characters yet.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>
<?php else: ?>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow"><?= $edit ? 'Edit Character' : 'Add Character' ?></span><h2><?= $edit ? sf_admin_h($edit['character_name'] ?? '') : 'New Character' ?></h2></div><a href="<?= sf_url('admin/characters.php') ?>">Back to Catalog</a></div>
  <form class="sf-admin-form" method="post" enctype="multipart/form-data">
    <?= sf_csrf_field() ?><input type="hidden" name="action" value="save_character"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <div class="sf-story-v1-form-grid"><label>Name<input name="character_name" value="<?= sf_admin_h($edit['character_name'] ?? '') ?>" required<?= $disabled ?>></label><label>Actor / Performer<input name="actor_name" value="<?= sf_admin_h($edit['actor_name'] ?? '') ?>"<?= $disabled ?>></label><label>Role Type<?= sf_admin_select('role_type', sf_story_v1_role_options(), $edit['role_type'] ?? 'supporting') ?></label></div>
    <div class="sf-story-v1-form-grid"><label>Slug<input name="slug" value="<?= sf_admin_h($edit['slug'] ?? '') ?>" placeholder="auto-generated"<?= $disabled ?>></label><label>Status<?= sf_admin_select('status', ['active'=>'Active','inactive'=>'Inactive','archived'=>'Archived'], $edit['status'] ?? 'active') ?></label><label>Sort<input type="number" name="sort_order" value="<?= sf_admin_h($edit['sort_order'] ?? 10) ?>"<?= $disabled ?>></label></div>
    <?php $editImage = trim((string)($edit['image_path'] ?? '')); ?>
    <div class="sf-character-image-field">
      <div class="sf-character-image-preview"<?= $editImage !== '' ? ' style="background-image:url(' . sf_admin_h(sf_asset($editImage)) . ')"' : '' ?>><?= $editImage === '' ? 'IMG' : '' ?></div>
      <div>
        <div class="sf-character-upload-row"><label>Upload Character Image<input type="file" name="character_image" accept="image/*"<?= $disabled ?>></label><button type="submit"<?= $disabled ?>>Upload Image</button></div>
        <label class="sf-character-image-path">Image Path<input name="image_path" value="<?= sf_admin_h($editImage) ?>" placeholder="images/cast/cast-jax.png"<?= $disabled ?>></label>
        <p class="sf-admin-copy">Upload saves the image into the media assets folder and stores the path on this master character profile.</p>
      </div>
    </div>
    <label>Short Bio<textarea name="short_bio" rows="3"<?= $disabled ?>><?= sf_admin_h($edit['short_bio'] ?? '') ?></textarea></label>
    <label>Motivation<textarea name="motivation" rows="3"<?= $disabled ?>><?= sf_admin_h($edit['motivation'] ?? '') ?></textarea></label>
    <label>Personality Notes<textarea name="personality_notes" rows="3"<?= $disabled ?>><?= sf_admin_h($edit['personality_notes'] ?? '') ?></textarea></label>
    <label>Relationship Notes<textarea name="relationship_notes" rows="4"<?= $disabled ?>><?= sf_admin_h($edit['relationship_notes'] ?? '') ?></textarea></label>
    <label>Season Arc<textarea name="season_arc" rows="4"<?= $disabled ?>><?= sf_admin_h($edit['season_arc'] ?? '') ?></textarea></label>
    <div class="sf-admin-form-actions"><button type="submit"<?= $disabled ?>>Save Character</button></div>
  </form>
</section>
<?php endif; ?>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
