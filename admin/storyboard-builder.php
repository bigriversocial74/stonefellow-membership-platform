<?php
$pageTitle = 'Storyboard Builder';
$pageDescription = 'AI-assisted 9-scene storyboard builder with character management, modal scene actions, image regeneration, uploads, and job retry controls.';
$pageClass = 'membership-page admin-catalog-page storyboards-page storyboard-builder-page';
require __DIR__ . '/../includes/storyboard_scene_actions.php';
$project = sf_storyboard_project((int)($_GET['project_id'] ?? 1));
$projectId = (int)($project['id'] ?? 0);
$settings = sf_storyboard_settings($project);
$characters = sf_storyboard_characters($projectId);
$scenes = sf_storyboard_scenes($projectId);
$jobs = sf_sba_recent_jobs($projectId, 10);
$returnUrl = sf_url('admin/storyboard-builder.php?project_id=' . $projectId);
$canEdit = $projectId > 0 && sf_storyboard_ready();
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Storyboarding', 'Storyboard builder workspace', 'Manage characters, references, scene assignments, modal scene actions, and retry-ready storyboard jobs. API keys remain in admin AI settings.', 'storyboards');
?>
<section class="sf-admin-card-grid">
  <div class="sf-admin-action-card"><span>Storyboard</span><strong><?= sf_storyboard_h($project['title']) ?></strong><small><?= sf_storyboard_h($project['genre']) ?></small></div>
  <div class="sf-admin-action-card"><span>Scenes</span><strong><?= count($scenes) ?></strong><small><?= $canEdit ? 'Database-backed scene cards.' : 'Static shell scene cards.' ?></small></div>
  <div class="sf-admin-action-card"><span>Characters</span><strong><?= count($characters) ?></strong><small>Reference profiles and scene assignments.</small></div>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/ai-settings.php') ?>"><span>AI Provider</span><strong>Admin Managed</strong><small>No API key fields in the creator workspace.</small></a>
</section>

<section class="sf-admin-two-col sf-admin-two-col-wide">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Script Prompt</span><h2><?= sf_storyboard_h($project['title']) ?></h2></div><span class="sf-admin-mini-pill"><?= strlen((string)($project['prompt'] ?? '')) ?> / 1000</span></div>
    <form class="sf-admin-form" method="post" action="<?= sf_url('api/storyboard-generate.php') ?>">
      <?= sf_csrf_field() ?><input type="hidden" name="storyboard_id" value="<?= (int)$projectId ?>"><input type="hidden" name="return_url" value="<?= sf_storyboard_h($returnUrl) ?>">
      <label>Prompt<textarea rows="7" name="story_prompt"<?= sf_admin_form_disabled_attr() ?>><?= sf_storyboard_h($project['prompt']) ?></textarea></label>
      <div class="sf-admin-form-actions"><button type="button"<?= sf_admin_form_disabled_attr() ?>>Enhance Prompt</button><button type="submit"<?= sf_admin_form_disabled_attr() ?>>Generate 9-Scene Storyboard</button></div>
    </form>
  </article>
  <aside class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Storyboard Settings</span><h2>Creator workflow</h2></div><a href="<?= sf_url('admin/ai-settings.php') ?>">AI Settings</a></div>
    <div class="sf-admin-list"><?php foreach ($settings as $label => $value): ?><article class="sf-admin-list-row"><strong><?= sf_storyboard_h(ucwords(str_replace('_',' ', $label))) ?></strong><span><?= sf_storyboard_h($value) ?></span></article><?php endforeach; ?></div>
  </aside>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head">
    <div><span class="sf-panel-eyebrow">Characters</span><h2>Reference library</h2></div>
    <div class="sf-admin-inline-form"><button type="button" data-sf-modal-open="character-add"<?= $canEdit ? '' : ' disabled' ?>>Add Character</button><button type="button" data-sf-modal-open="bulk-images"<?= $canEdit ? '' : ' disabled' ?>>Bulk Regenerate Images</button></div>
  </div>
  <p class="sf-admin-copy">Add characters, upload reference images, and assign characters to individual scenes. Reference notes feed the rewrite and image-generation consistency payloads.</p>
  <div class="sf-storyboard-character-grid">
    <?php foreach ($characters as $character): ?>
      <article class="sf-storyboard-character-card">
        <div class="sf-storyboard-thumb sf-storyboard-character-thumb" style="background-image:url('<?= sf_storyboard_h(sf_url($character['image'])) ?>')"></div>
        <div class="sf-storyboard-character-body">
          <span><?= sf_storyboard_h($character['role']) ?> · <?= sf_storyboard_h($character['status'] ?? 'active') ?></span><strong><?= sf_storyboard_h($character['name']) ?></strong>
          <small><?= sf_storyboard_h($character['summary']) ?></small><small><strong>Consistency:</strong> <?= sf_storyboard_h($character['notes']) ?></small>
          <div class="sf-admin-form-actions"><button type="button" data-sf-modal-open="character-edit-<?= (int)$character['id'] ?>"<?= $canEdit ? '' : ' disabled' ?>>Edit</button><button type="button" data-sf-modal-open="character-ref-<?= (int)$character['id'] ?>"<?= $canEdit ? '' : ' disabled' ?>>Upload Reference</button></div>
        </div>
      </article>
    <?php endforeach; ?>
    <article class="sf-storyboard-character-card sf-storyboard-empty-card"><div><strong>Add more characters</strong><small>Upload actor, musician, or reference images to improve future visual consistency.</small><button type="button" data-sf-modal-open="character-add"<?= $canEdit ? '' : ' disabled' ?>>Add Character</button></div></article>
  </div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Storyboard</span><h2>9-scene screenplay grid</h2></div><div class="sf-admin-inline-form"><button type="button">View 3x3</button><button type="button">Expand All</button></div></div>
  <p class="sf-admin-copy">Scene controls now open as modals for editing, rewriting, assigning characters, regenerating images, uploading images, and checking job status.</p>
  <div class="sf-storyboard-scene-grid">
    <?php foreach ($scenes as $scene): $sceneId = (int)($scene['id'] ?? 0); $sceneDisabled = ($sceneId > 0 && $canEdit) ? '' : ' disabled'; ?>
      <article id="scene-<?= (int)$scene['number'] ?>" class="sf-storyboard-scene-card">
        <div class="sf-storyboard-thumb sf-storyboard-scene-thumb" style="background-image:url('<?= sf_storyboard_h(sf_url($scene['image'])) ?>')"><span><?= (int)$scene['number'] ?></span></div>
        <div class="sf-storyboard-scene-body">
          <span>Scene <?= (int)$scene['number'] ?> · <?= sf_storyboard_h($scene['status']) ?> · Image <?= sf_storyboard_h($scene['image_status'] ?? 'none') ?> · Rewrite <?= sf_storyboard_h($scene['rewrite_status'] ?? 'none') ?></span>
          <strong><?= sf_storyboard_h($scene['title']) ?></strong>
          <small><strong>Prompt:</strong> <?= sf_storyboard_h($scene['prompt']) ?></small><small><strong>Dialog:</strong> “<?= sf_storyboard_h($scene['dialog']) ?>”</small>
          <small><strong>Characters:</strong> <?php foreach ($scene['characters'] as $characterName) echo sf_storyboard_render_character_chip($characterName) . ' '; ?></small>
          <div class="sf-storyboard-scene-actions"><button type="button" data-sf-modal-open="scene-edit-<?= $sceneId ?>"<?= $sceneDisabled ?>>Edit</button><button type="button" data-sf-modal-open="scene-rewrite-<?= $sceneId ?>"<?= $sceneDisabled ?>>Rewrite</button><button type="button" data-sf-modal-open="scene-characters-<?= $sceneId ?>"<?= $sceneDisabled ?>>Characters</button><button type="button" data-sf-modal-open="scene-image-<?= $sceneId ?>"<?= $sceneDisabled ?>>Image</button></div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Jobs</span><h2>Recent scene jobs</h2></div><span class="sf-admin-mini-pill"><?= count($jobs) ?> recent</span></div>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Job</th><th>Status</th><th>Scene</th><th>Provider</th><th>Updated</th><th>Retry</th></tr></thead><tbody>
  <?php if (!$jobs): ?><tr><td colspan="6">No scene jobs yet.</td></tr><?php endif; ?>
  <?php foreach ($jobs as $job): ?><tr><td><strong><?= sf_storyboard_h($job['job_type'] ?? '') ?></strong><small><?= sf_storyboard_h($job['error_message'] ?? '') ?></small></td><td><?= sf_admin_status_badge((string)($job['job_status'] ?? 'queued')) ?></td><td><?= (int)($job['scene_id'] ?? 0) ?></td><td><?= sf_storyboard_h($job['provider_key'] ?? '') ?></td><td><?= sf_storyboard_h($job['updated_at'] ?? $job['created_at'] ?? '') ?></td><td><?php if (($job['job_status'] ?? '') === 'failed'): ?><form method="post" action="<?= sf_url('api/storyboard-scene-action.php') ?>"><?= sf_csrf_field() ?><input type="hidden" name="action" value="retry_job"><input type="hidden" name="job_id" value="<?= (int)$job['id'] ?>"><input type="hidden" name="return_url" value="<?= sf_storyboard_h($returnUrl) ?>"><button type="submit">Retry</button></form><?php else: ?>—<?php endif; ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>

<dialog id="character-add" class="sf-storyboard-modal"><form method="post" action="<?= sf_url('api/storyboard-characters.php') ?>" class="sf-admin-form"><button type="button" class="sf-modal-close" data-sf-modal-close>×</button><h3>Add Character</h3><?= sf_csrf_field() ?><input type="hidden" name="action" value="save_character"><input type="hidden" name="storyboard_id" value="<?= $projectId ?>"><input type="hidden" name="return_url" value="<?= sf_storyboard_h($returnUrl) ?>"><label>Name<input name="character_name" required></label><div class="sf-admin-form-grid"><label>Role<input name="role_label" value="Character"></label><label>Order<input type="number" name="character_order" value="0"></label><label>Likeness<select name="likeness_strength"><option value="loose">Loose</option><option value="medium" selected>Medium</option><option value="strong">Strong</option></select></label></div><label>Appearance<textarea name="appearance_notes" rows="3"></textarea></label><label>Personality<textarea name="personality_notes" rows="2"></textarea></label><label>Wardrobe<textarea name="wardrobe_notes" rows="2"></textarea></label><label>Consistency Prompt<textarea name="consistency_prompt" rows="3"></textarea></label><div class="sf-admin-form-actions"><button type="submit">Save Character</button></div></form></dialog>

<dialog id="bulk-images" class="sf-storyboard-modal"><form method="post" action="<?= sf_url('api/storyboard-characters.php') ?>" class="sf-admin-form"><button type="button" class="sf-modal-close" data-sf-modal-close>×</button><h3>Bulk Regenerate Scene Images</h3><p class="sf-admin-copy">This runs scene image generation for every scene in this storyboard. It can take time and requires the active image provider.</p><?= sf_csrf_field() ?><input type="hidden" name="action" value="bulk_regenerate_images"><input type="hidden" name="storyboard_id" value="<?= $projectId ?>"><input type="hidden" name="return_url" value="<?= sf_storyboard_h($returnUrl) ?>"><div class="sf-admin-form-actions"><button type="submit">Regenerate All Scene Images</button></div></form></dialog>

<?php foreach ($characters as $character): ?>
<dialog id="character-edit-<?= (int)$character['id'] ?>" class="sf-storyboard-modal"><form method="post" action="<?= sf_url('api/storyboard-characters.php') ?>" class="sf-admin-form"><button type="button" class="sf-modal-close" data-sf-modal-close>×</button><h3>Edit <?= sf_storyboard_h($character['name']) ?></h3><?= sf_csrf_field() ?><input type="hidden" name="action" value="save_character"><input type="hidden" name="storyboard_id" value="<?= $projectId ?>"><input type="hidden" name="character_id" value="<?= (int)$character['id'] ?>"><input type="hidden" name="return_url" value="<?= sf_storyboard_h($returnUrl) ?>"><label>Name<input name="character_name" value="<?= sf_storyboard_h($character['name']) ?>" required></label><div class="sf-admin-form-grid"><label>Role<input name="role_label" value="<?= sf_storyboard_h($character['role']) ?>"></label><label>Order<input type="number" name="character_order" value="<?= (int)($character['character_order'] ?? 0) ?>"></label><label>Status<select name="status"><option value="active"<?= ($character['status'] ?? '')==='active'?' selected':'' ?>>Active</option><option value="hidden"<?= ($character['status'] ?? '')==='hidden'?' selected':'' ?>>Hidden</option><option value="archived"<?= ($character['status'] ?? '')==='archived'?' selected':'' ?>>Archived</option></select></label></div><label>Appearance<textarea name="appearance_notes" rows="3"><?= sf_storyboard_h($character['appearance_notes'] ?? '') ?></textarea></label><label>Personality<textarea name="personality_notes" rows="2"><?= sf_storyboard_h($character['personality_notes'] ?? '') ?></textarea></label><label>Wardrobe<textarea name="wardrobe_notes" rows="2"><?= sf_storyboard_h($character['wardrobe_notes'] ?? '') ?></textarea></label><label>Consistency Prompt<textarea name="consistency_prompt" rows="3"><?= sf_storyboard_h($character['consistency_prompt'] ?? '') ?></textarea></label><div class="sf-admin-form-actions"><button type="submit">Save Character</button></div></form></dialog>
<dialog id="character-ref-<?= (int)$character['id'] ?>" class="sf-storyboard-modal"><form method="post" enctype="multipart/form-data" action="<?= sf_url('api/storyboard-characters.php') ?>" class="sf-admin-form"><button type="button" class="sf-modal-close" data-sf-modal-close>×</button><h3>Upload Reference — <?= sf_storyboard_h($character['name']) ?></h3><?= sf_csrf_field() ?><input type="hidden" name="action" value="upload_reference"><input type="hidden" name="storyboard_id" value="<?= $projectId ?>"><input type="hidden" name="character_id" value="<?= (int)$character['id'] ?>"><input type="hidden" name="return_url" value="<?= sf_storyboard_h($returnUrl) ?>"><label>Reference Image<input type="file" name="reference_image" accept="image/*" required></label><div class="sf-admin-form-actions"><button type="submit">Upload Reference</button></div></form></dialog>
<?php endforeach; ?>

<?php foreach ($scenes as $scene): $sceneId = (int)($scene['id'] ?? 0); $currentSceneStatus = (string)($scene['scene_status_raw'] ?? 'draft'); $assignedIds = $sceneId ? sf_sba_scene_character_ids($projectId, $sceneId) : []; ?>
<dialog id="scene-edit-<?= $sceneId ?>" class="sf-storyboard-modal"><form class="sf-admin-form" method="post" action="<?= sf_url('api/storyboard-scene-action.php') ?>"><button type="button" class="sf-modal-close" data-sf-modal-close>×</button><h3>Edit Scene <?= (int)$scene['number'] ?></h3><?= sf_csrf_field() ?><input type="hidden" name="action" value="edit_scene"><input type="hidden" name="scene_id" value="<?= $sceneId ?>"><input type="hidden" name="return_url" value="<?= sf_storyboard_h($returnUrl . '#scene-' . (int)$scene['number']) ?>"><label>Title<input name="scene_title" value="<?= sf_storyboard_h($scene['title']) ?>"></label><label>Summary<textarea rows="2" name="scene_summary"><?= sf_storyboard_h($scene['summary'] ?? '') ?></textarea></label><label>Scene Prompt<textarea rows="3" name="scene_prompt"><?= sf_storyboard_h($scene['prompt']) ?></textarea></label><label>Image Prompt<textarea rows="3" name="image_prompt"><?= sf_storyboard_h($scene['image_prompt'] ?? '') ?></textarea></label><label>Dialog<textarea rows="3" name="dialog_text"><?= sf_storyboard_h($scene['dialog']) ?></textarea></label><label>Action Notes<textarea rows="2" name="action_notes"><?= sf_storyboard_h($scene['action_notes'] ?? '') ?></textarea></label><div class="sf-admin-form-grid"><label>Location<input name="location_label" value="<?= sf_storyboard_h($scene['location_label'] ?? '') ?>"></label><label>Time of Day<input name="time_of_day" value="<?= sf_storyboard_h($scene['time_of_day'] ?? '') ?>"></label><label>Status<select name="scene_status"><option value="draft"<?= $currentSceneStatus==='draft'?' selected':'' ?>>Draft</option><option value="needs_review"<?= $currentSceneStatus==='needs_review'?' selected':'' ?>>Needs Review</option><option value="ready"<?= $currentSceneStatus==='ready'?' selected':'' ?>>Ready</option><option value="archived"<?= $currentSceneStatus==='archived'?' selected':'' ?>>Archived</option></select></label></div><div class="sf-admin-form-actions"><button type="submit">Save Scene</button></div></form></dialog>
<dialog id="scene-rewrite-<?= $sceneId ?>" class="sf-storyboard-modal"><form class="sf-admin-form" method="post" action="<?= sf_url('api/storyboard-scene-action.php') ?>"><button type="button" class="sf-modal-close" data-sf-modal-close>×</button><h3>Rewrite Scene <?= (int)$scene['number'] ?></h3><?= sf_csrf_field() ?><input type="hidden" name="action" value="rewrite_scene"><input type="hidden" name="scene_id" value="<?= $sceneId ?>"><input type="hidden" name="return_url" value="<?= sf_storyboard_h($returnUrl . '#scene-' . (int)$scene['number']) ?>"><label>Rewrite Instruction<input name="rewrite_instruction" placeholder="Make this scene tighter, funnier, more cinematic..."></label><div class="sf-admin-form-actions"><button type="submit">Rewrite Scene</button></div></form></dialog>
<dialog id="scene-characters-<?= $sceneId ?>" class="sf-storyboard-modal"><form method="post" action="<?= sf_url('api/storyboard-characters.php') ?>" class="sf-admin-form"><button type="button" class="sf-modal-close" data-sf-modal-close>×</button><h3>Assign Scene <?= (int)$scene['number'] ?> Characters</h3><?= sf_csrf_field() ?><input type="hidden" name="action" value="assign_scene_characters"><input type="hidden" name="storyboard_id" value="<?= $projectId ?>"><input type="hidden" name="scene_id" value="<?= $sceneId ?>"><input type="hidden" name="return_url" value="<?= sf_storyboard_h($returnUrl . '#scene-' . (int)$scene['number']) ?>"><?php foreach ($characters as $character): ?><label class="sf-admin-check"><input type="checkbox" name="character_ids[]" value="<?= (int)$character['id'] ?>"<?= in_array((int)$character['id'], $assignedIds, true) ? ' checked' : '' ?>> <?= sf_storyboard_h($character['name']) ?> — <?= sf_storyboard_h($character['role']) ?></label><?php endforeach; ?><div class="sf-admin-form-actions"><button type="submit">Save Scene Characters</button></div></form></dialog>
<dialog id="scene-image-<?= $sceneId ?>" class="sf-storyboard-modal"><div class="sf-admin-form"><button type="button" class="sf-modal-close" data-sf-modal-close>×</button><h3>Scene <?= (int)$scene['number'] ?> Image</h3><form method="post" action="<?= sf_url('api/storyboard-scene-action.php') ?>"><?= sf_csrf_field() ?><input type="hidden" name="action" value="regenerate_image"><input type="hidden" name="scene_id" value="<?= $sceneId ?>"><input type="hidden" name="return_url" value="<?= sf_storyboard_h($returnUrl . '#scene-' . (int)$scene['number']) ?>"><button type="submit">Regenerate Image</button></form><form method="post" enctype="multipart/form-data" action="<?= sf_url('api/storyboard-scene-action.php') ?>"><?= sf_csrf_field() ?><input type="hidden" name="action" value="upload_image"><input type="hidden" name="scene_id" value="<?= $sceneId ?>"><input type="hidden" name="return_url" value="<?= sf_storyboard_h($returnUrl . '#scene-' . (int)$scene['number']) ?>"><label>Upload Replacement<input type="file" name="scene_image" accept="image/*" required></label><button type="submit">Upload Image</button></form></div></dialog>
<?php endforeach; ?>

<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Implementation Notes</span><h2>What Phase 44 adds</h2></div><a href="<?= sf_url('docs/PHASE_44_CHARACTER_MANAGEMENT_MODALS.md') ?>">Phase Docs</a></div><div class="sf-admin-roadmap"><div><span>Cast</span><strong>Character management</strong><p>Add/edit characters and upload primary reference images.</p></div><div><span>Assign</span><strong>Scene characters</strong><p>Assign or remove characters per scene from modal controls.</p></div><div><span>Jobs</span><strong>Status + retry</strong><p>Recent jobs display status and failed jobs can be retried.</p></div><div><span>Bulk</span><strong>All scene images</strong><p>Regenerate all scene images from one storyboard-level action.</p></div></div></section>
<script>
document.addEventListener('click', function(event) {
  var openBtn = event.target.closest('[data-sf-modal-open]');
  if (openBtn) { var modal = document.getElementById(openBtn.getAttribute('data-sf-modal-open')); if (modal && modal.showModal) modal.showModal(); }
  if (event.target.matches('[data-sf-modal-close]')) { var dialog = event.target.closest('dialog'); if (dialog) dialog.close(); }
});
</script>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
