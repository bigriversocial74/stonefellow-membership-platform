<?php
$pageTitle = 'Story Character Catalog';
$pageDescription = 'Main character catalog for Stonefellow storyboarding, episode planning, and scene appearances.';
$pageClass = 'membership-page admin-catalog-page storyboarding-system-page';
require __DIR__ . '/../includes/storyboarding_system.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) { sf_admin_flash('error', 'Security check failed.'); sf_admin_redirect(); }
  if (!sf_story_v1_ready()) { sf_admin_flash('warning', 'Import database/storyboarding_system_v1.sql before saving characters.'); sf_admin_redirect(); }
  $action = (string)($_POST['action'] ?? '');
  $id = sf_admin_int($_POST['id'] ?? null, 0) ?? 0;
  if ($action === 'save_character') {
    $name = trim((string)($_POST['character_name'] ?? ''));
    if ($name === '') { sf_admin_flash('error', 'Character name is required.'); sf_admin_redirect(); }
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
      'image_path' => sf_admin_nullable_string($_POST['image_path'] ?? '') ?: '',
      'status' => $_POST['status'] ?? 'active',
      'sort_order' => sf_admin_int($_POST['sort_order'] ?? null, 10) ?? 10,
    ];
    $newId = sf_story_v1_save_row('story_characters', $payload, $id);
    sf_admin_flash($newId ? 'success' : 'error', $newId ? 'Character saved.' : 'Character could not be saved.');
    sf_admin_redirect(sf_url('admin/story-characters.php' . ($newId ? '?edit=' . $newId : '')));
  }
  sf_admin_redirect();
}

$characters = sf_story_v1_characters();
$editId = sf_admin_int($_GET['edit'] ?? null, 0) ?? 0;
$edit = sf_story_v1_find($characters, $editId) ?: [];
$showForm = isset($_GET['new']) || $edit;
$disabled = sf_story_v1_disabled_attr();
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Characters', 'Main Character Catalog', 'Build reusable character profiles for seasons, episodes, scene sheets, and scene-card planning.', 'story-characters');
?>
<div class="sf-story-v1-toolbar"><a href="<?= sf_url('admin/story-system.php') ?>">Story System</a><a href="<?= sf_url('admin/storyboards.php') ?>">AI Storyboards</a><a href="<?= sf_url('database/storyboarding_system_v1.sql') ?>">SQL Migration</a></div>
<?php if (!sf_story_v1_ready()): ?><section class="sf-story-v1-warning"><strong>SQL required:</strong> Import <code>database/storyboarding_system_v1.sql</code> to enable live character saves.</section><?php endif; ?>
<?php if (!$showForm): ?>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Catalog</span><h2><?= count($characters) ?> characters</h2></div><a href="<?= sf_url('admin/story-characters.php?new=1') ?>">Add Character</a></div>
  <div class="sf-story-v1-character-grid">
    <?php foreach ($characters as $character): ?>
      <article class="sf-story-v1-character-card">
        <?php if (!empty($character['image_path'])): ?><img class="sf-story-v1-avatar" src="<?= sf_asset($character['image_path']) ?>" alt="<?= sf_admin_h($character['character_name']) ?>"><?php endif; ?>
        <div><span class="sf-story-v1-card-type"><?= sf_admin_h(sf_story_v1_status_label((string)($character['role_type'] ?? 'supporting'))) ?></span><h3><?= sf_admin_h($character['character_name']) ?></h3><p><?= sf_admin_h($character['short_bio'] ?? '') ?></p></div>
        <div class="sf-story-v1-meta"><span><?= sf_admin_h($character['status'] ?? 'active') ?></span><span><?= (int)($character['scene_count'] ?? 0) ?> scene appearances</span><?php if (!empty($character['actor_name'])): ?><span><?= sf_admin_h($character['actor_name']) ?></span><?php endif; ?></div>
        <div class="sf-story-v1-mini-actions"><a href="<?= sf_url('admin/story-characters.php?edit=' . (int)$character['id']) ?>">Edit Profile</a></div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php else: ?>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow"><?= $edit ? 'Edit Character' : 'Add Character' ?></span><h2><?= $edit ? sf_admin_h($edit['character_name'] ?? '') : 'New character' ?></h2></div><a href="<?= sf_url('admin/story-characters.php') ?>">Back to Catalog</a></div>
  <form class="sf-admin-form" method="post">
    <?= sf_csrf_field() ?><input type="hidden" name="action" value="save_character"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <div class="sf-story-v1-form-grid"><label>Name<input name="character_name" value="<?= sf_admin_h($edit['character_name'] ?? '') ?>" required<?= $disabled ?>></label><label>Actor / Performer<input name="actor_name" value="<?= sf_admin_h($edit['actor_name'] ?? '') ?>"<?= $disabled ?>></label><label>Role Type><?= sf_admin_select('role_type', sf_story_v1_role_options(), $edit['role_type'] ?? 'supporting') ?></label></div>
    <div class="sf-story-v1-form-grid"><label>Slug<input name="slug" value="<?= sf_admin_h($edit['slug'] ?? '') ?>" placeholder="auto-generated"<?= $disabled ?>></label><label>Status><?= sf_admin_select('status', ['active'=>'Active','inactive'=>'Inactive','archived'=>'Archived'], $edit['status'] ?? 'active') ?></label><label>Sort<input type="number" name="sort_order" value="<?= sf_admin_h($edit['sort_order'] ?? 10) ?>"<?= $disabled ?>></label></div>
    <label>Image Path<input name="image_path" value="<?= sf_admin_h($edit['image_path'] ?? '') ?>" placeholder="images/cast/cast-jax.png"<?= $disabled ?>></label>
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