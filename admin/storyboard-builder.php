<?php
$pageTitle = 'Storyboard Builder';
$pageDescription = 'AI-assisted 9-scene storyboard builder with prompt generation, scene edit persistence, scene rewrite, image regeneration, and upload actions.';
$pageClass = 'membership-page admin-catalog-page storyboards-page storyboard-builder-page';
require __DIR__ . '/../includes/storyboards.php';
$project = sf_storyboard_project((int)($_GET['project_id'] ?? 1));
$projectId = (int)($project['id'] ?? 0);
$settings = sf_storyboard_settings($project);
$characters = sf_storyboard_characters($projectId);
$scenes = sf_storyboard_scenes($projectId);
$returnUrl = sf_url('admin/storyboard-builder.php?project_id=' . $projectId);
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Storyboarding', 'Storyboard builder shell v1', 'Turn a basic script prompt into a 9-scene visual screenplay plan. API keys and provider controls remain in admin AI settings.', 'storyboards');
?>
<section class="sf-admin-card-grid">
  <div class="sf-admin-action-card"><span>Storyboard</span><strong><?= sf_storyboard_h($project['title']) ?></strong><small><?= sf_storyboard_h($project['genre']) ?></small></div>
  <div class="sf-admin-action-card"><span>Scenes</span><strong><?= count($scenes) ?></strong><small><?= sf_storyboard_ready() ? 'Database-backed when scenes exist.' : 'Static shell scene cards.' ?></small></div>
  <div class="sf-admin-action-card"><span>Characters</span><strong><?= count($characters) ?></strong><small>Reference profiles for likeness consistency.</small></div>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/ai-settings.php') ?>"><span>AI Provider</span><strong>Admin Managed</strong><small>No API key fields in the creator workspace.</small></a>
</section>

<section class="sf-admin-two-col sf-admin-two-col-wide">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Script Prompt</span><h2><?= sf_storyboard_h($project['title']) ?></h2></div><span class="sf-admin-mini-pill"><?= strlen((string)($project['prompt'] ?? '')) ?> / 1000</span></div>
    <form class="sf-admin-form" method="post" action="<?= sf_url('api/storyboard-generate.php') ?>">
      <?= sf_csrf_field() ?>
      <input type="hidden" name="storyboard_id" value="<?= (int)$projectId ?>">
      <input type="hidden" name="return_url" value="<?= sf_storyboard_h($returnUrl) ?>">
      <label>Prompt<textarea rows="7" name="story_prompt"<?= sf_admin_form_disabled_attr() ?>><?= sf_storyboard_h($project['prompt']) ?></textarea></label>
      <div class="sf-admin-form-actions"><button type="button"<?= sf_admin_form_disabled_attr() ?>>Enhance Prompt</button><button type="submit"<?= sf_admin_form_disabled_attr() ?>>Generate 9-Scene Storyboard</button></div>
    </form>
  </article>
  <aside class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Storyboard Settings</span><h2>Creator workflow</h2></div><a href="<?= sf_url('admin/ai-settings.php') ?>">AI Settings</a></div>
    <div class="sf-admin-list">
      <?php foreach ($settings as $label => $value): ?>
        <article class="sf-admin-list-row"><strong><?= sf_storyboard_h(ucwords(str_replace('_',' ', $label))) ?></strong><span><?= sf_storyboard_h($value) ?></span></article>
      <?php endforeach; ?>
    </div>
  </aside>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Characters</span><h2>Reference library</h2></div><div class="sf-admin-inline-form"><button type="button"<?= sf_admin_form_disabled_attr() ?>>Add Character</button><button type="button"<?= sf_admin_form_disabled_attr() ?>>Upload Reference</button><button type="button"<?= sf_admin_form_disabled_attr() ?>>Consistency Settings</button></div></div>
  <p class="sf-admin-copy">Character reference images and likeness notes feed the Phase 43 character-consistency payload for scene rewrites and image regeneration.</p>
  <div class="sf-storyboard-character-grid">
    <?php foreach ($characters as $character): ?>
      <article class="sf-storyboard-character-card">
        <div class="sf-storyboard-thumb sf-storyboard-character-thumb" style="background-image:url('<?= sf_storyboard_h(sf_url($character['image'])) ?>')"></div>
        <div class="sf-storyboard-character-body"><span><?= sf_storyboard_h($character['role']) ?></span><strong><?= sf_storyboard_h($character['name']) ?></strong><small><?= sf_storyboard_h($character['summary']) ?></small><small><strong>Consistency:</strong> <?= sf_storyboard_h($character['notes']) ?></small><div class="sf-admin-form-actions"><button type="button"<?= sf_admin_form_disabled_attr() ?>>View Details</button><button type="button"<?= sf_admin_form_disabled_attr() ?>>Replace Image</button></div></div>
      </article>
    <?php endforeach; ?>
    <article class="sf-storyboard-character-card sf-storyboard-empty-card"><div><strong>Add more characters</strong><small>Upload actor, musician, or reference images to improve future visual consistency.</small></div></article>
  </div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Storyboard</span><h2>9-scene screenplay grid</h2></div><div class="sf-admin-inline-form"><button type="button">View 3x3</button><button type="button">Expand All</button></div></div>
  <p class="sf-admin-copy">Phase 43 wires scene editing, single-scene AI rewrite, image regeneration, manual image upload, scene-level job records, and retry-ready actions.</p>
  <div class="sf-storyboard-scene-grid">
    <?php foreach ($scenes as $scene): $sceneId = (int)($scene['id'] ?? 0); $sceneDisabled = ($sceneId > 0 && sf_storyboard_ready()) ? '' : ' disabled'; ?>
      <article id="scene-<?= (int)$scene['number'] ?>" class="sf-storyboard-scene-card">
        <div class="sf-storyboard-thumb sf-storyboard-scene-thumb" style="background-image:url('<?= sf_storyboard_h(sf_url($scene['image'])) ?>')"><span><?= (int)$scene['number'] ?></span></div>
        <div class="sf-storyboard-scene-body">
          <span>Scene <?= (int)$scene['number'] ?> · <?= sf_storyboard_h($scene['status']) ?> · Image <?= sf_storyboard_h($scene['image_status'] ?? 'none') ?> · Rewrite <?= sf_storyboard_h($scene['rewrite_status'] ?? 'none') ?></span>
          <strong><?= sf_storyboard_h($scene['title']) ?></strong>
          <small><strong>Prompt:</strong> <?= sf_storyboard_h($scene['prompt']) ?></small>
          <small><strong>Dialog:</strong> “<?= sf_storyboard_h($scene['dialog']) ?>”</small>
          <small><strong>Characters:</strong> <?php foreach ($scene['characters'] as $characterName) echo sf_storyboard_render_character_chip($characterName) . ' '; ?></small>
          <details class="sf-storyboard-scene-editor">
            <summary>Edit Scene</summary>
            <form class="sf-admin-form" method="post" action="<?= sf_url('api/storyboard-scene-action.php') ?>">
              <?= sf_csrf_field() ?>
              <input type="hidden" name="action" value="edit_scene"><input type="hidden" name="scene_id" value="<?= $sceneId ?>"><input type="hidden" name="return_url" value="<?= sf_storyboard_h($returnUrl . '#scene-' . (int)$scene['number']) ?>">
              <label>Title<input name="scene_title" value="<?= sf_storyboard_h($scene['title']) ?>"<?= $sceneDisabled ?>></label>
              <label>Summary<textarea rows="2" name="scene_summary"<?= $sceneDisabled ?>><?= sf_storyboard_h($scene['summary'] ?? '') ?></textarea></label>
              <label>Scene Prompt<textarea rows="3" name="scene_prompt"<?= $sceneDisabled ?>><?= sf_storyboard_h($scene['prompt']) ?></textarea></label>
              <label>Image Prompt<textarea rows="3" name="image_prompt"<?= $sceneDisabled ?>><?= sf_storyboard_h($scene['image_prompt'] ?? '') ?></textarea></label>
              <label>Dialog<textarea rows="3" name="dialog_text"<?= $sceneDisabled ?>><?= sf_storyboard_h($scene['dialog']) ?></textarea></label>
              <label>Action Notes<textarea rows="2" name="action_notes"<?= $sceneDisabled ?>><?= sf_storyboard_h($scene['action_notes'] ?? '') ?></textarea></label>
              <div class="sf-admin-form-grid"><label>Location<input name="location_label" value="<?= sf_storyboard_h($scene['location_label'] ?? '') ?>"<?= $sceneDisabled ?>></label><label>Time of Day<input name="time_of_day" value="<?= sf_storyboard_h($scene['time_of_day'] ?? '') ?>"<?= $sceneDisabled ?>></label><label>Status><?= sf_admin_select('scene_status', ['draft'=>'Draft','needs_review'=>'Needs Review','ready'=>'Ready','archived'=>'Archived'], 'draft') ?></label></div>
              <div class="sf-admin-form-actions"><button type="submit"<?= $sceneDisabled ?>>Save Scene</button></div>
            </form>
          </details>
          <form class="sf-admin-form sf-storyboard-inline-action" method="post" action="<?= sf_url('api/storyboard-scene-action.php') ?>">
            <?= sf_csrf_field() ?><input type="hidden" name="action" value="rewrite_scene"><input type="hidden" name="scene_id" value="<?= $sceneId ?>"><input type="hidden" name="return_url" value="<?= sf_storyboard_h($returnUrl . '#scene-' . (int)$scene['number']) ?>">
            <label>Rewrite Instruction<input name="rewrite_instruction" placeholder="Make this scene tighter, funnier, more cinematic..."<?= $sceneDisabled ?>></label><button type="submit"<?= $sceneDisabled ?>>Rewrite Scene</button>
          </form>
          <div class="sf-storyboard-scene-actions">
            <form method="post" action="<?= sf_url('api/storyboard-scene-action.php') ?>"><?= sf_csrf_field() ?><input type="hidden" name="action" value="regenerate_image"><input type="hidden" name="scene_id" value="<?= $sceneId ?>"><input type="hidden" name="return_url" value="<?= sf_storyboard_h($returnUrl . '#scene-' . (int)$scene['number']) ?>"><button type="submit"<?= $sceneDisabled ?>>Regenerate Image</button></form>
            <form method="post" enctype="multipart/form-data" action="<?= sf_url('api/storyboard-scene-action.php') ?>"><?= sf_csrf_field() ?><input type="hidden" name="action" value="upload_image"><input type="hidden" name="scene_id" value="<?= $sceneId ?>"><input type="hidden" name="return_url" value="<?= sf_storyboard_h($returnUrl . '#scene-' . (int)$scene['number']) ?>"><input type="file" name="scene_image" accept="image/*"<?= $sceneDisabled ?>><button type="submit"<?= $sceneDisabled ?>>Upload Image</button></form>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Implementation Notes</span><h2>What Phase 43 adds</h2></div><a href="<?= sf_url('docs/PHASE_43_SCENE_ACTIONS.md') ?>">Phase Docs</a></div>
  <div class="sf-admin-roadmap"><div><span>Edit</span><strong>Scene persistence</strong><p>Scene title, prompt, image prompt, dialog, notes, location, time, and status can be saved.</p></div><div><span>AI</span><strong>Rewrite scene</strong><p>One scene can be rewritten with continuity and character consistency context.</p></div><div><span>Image</span><strong>Regenerate or upload</strong><p>Scene image regeneration uses the image provider; uploads replace the active scene image.</p></div><div><span>Jobs</span><strong>Retry-ready tracking</strong><p>Scene actions create job records with success/failure output for retries.</p></div></div>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
