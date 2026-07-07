<?php
$pageTitle = 'Storyboard Builder';
$pageDescription = 'AI-assisted 9-scene storyboard builder shell with script prompt, scene settings, characters, reference images, and editable scene cards.';
$pageClass = 'membership-page admin-catalog-page storyboards-page storyboard-builder-page';
require __DIR__ . '/../includes/storyboards.php';
$project = sf_storyboard_project((int)($_GET['project_id'] ?? 1));
$settings = sf_storyboard_settings();
$characters = sf_storyboard_characters();
$scenes = sf_storyboard_scenes();
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Storyboarding', 'Storyboard builder shell v1', 'Turn a basic script prompt into a 9-scene visual screenplay plan. This phase is UI-only; API keys and provider controls remain in the admin settings phase.', 'storyboards');
?>
<section class="sf-admin-card-grid">
  <div class="sf-admin-action-card"><span>Storyboard</span><strong><?= sf_storyboard_h($project['title']) ?></strong><small><?= sf_storyboard_h($project['genre']) ?></small></div>
  <div class="sf-admin-action-card"><span>Scenes</span><strong><?= count($scenes) ?></strong><small>Generated placeholder scene cards.</small></div>
  <div class="sf-admin-action-card"><span>Characters</span><strong><?= count($characters) ?></strong><small>Reference profiles for likeness consistency.</small></div>
  <div class="sf-admin-action-card"><span>AI Provider</span><strong>Admin Managed</strong><small>No API key fields in the creator workspace.</small></div>
</section>

<section class="sf-admin-two-col sf-admin-two-col-wide">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Script Prompt</span><h2><?= sf_storyboard_h($project['title']) ?></h2></div><span class="sf-admin-mini-pill">171 / 1000</span></div>
    <form class="sf-admin-form" method="post">
      <?= sf_csrf_field() ?>
      <label>Prompt<textarea rows="7" name="story_prompt"<?= sf_admin_form_disabled_attr() ?>><?= sf_storyboard_h($project['prompt']) ?></textarea></label>
      <div class="sf-admin-form-actions">
        <button type="button"<?= sf_admin_form_disabled_attr() ?>>Enhance Prompt</button>
        <button type="button"<?= sf_admin_form_disabled_attr() ?>>Generate 9-Scene Storyboard</button>
      </div>
    </form>
  </article>
  <aside class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Storyboard Settings</span><h2>Creator workflow</h2></div><a href="<?= sf_url('admin/settings.php') ?>">AI Settings Later</a></div>
    <div class="sf-admin-list">
      <?php foreach ($settings as $label => $value): ?>
        <article class="sf-admin-list-row"><strong><?= sf_storyboard_h(ucwords(str_replace('_',' ', $label))) ?></strong><span><?= sf_storyboard_h($value) ?></span></article>
      <?php endforeach; ?>
    </div>
  </aside>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head">
    <div><span class="sf-panel-eyebrow">Characters</span><h2>Reference library</h2></div>
    <div class="sf-admin-inline-form"><button type="button"<?= sf_admin_form_disabled_attr() ?>>Add Character</button><button type="button"<?= sf_admin_form_disabled_attr() ?>>Upload Reference</button><button type="button"<?= sf_admin_form_disabled_attr() ?>>Consistency Settings</button></div>
  </div>
  <p class="sf-admin-copy">Character reference images and likeness notes will guide image generation in later phases. Phase 40 displays the workflow shell without making external API calls.</p>
  <div class="sf-storyboard-character-grid">
    <?php foreach ($characters as $character): ?>
      <article class="sf-storyboard-character-card">
        <div class="sf-storyboard-thumb sf-storyboard-character-thumb" style="background-image:url('<?= sf_storyboard_h(sf_url($character['image'])) ?>')"></div>
        <div class="sf-storyboard-character-body">
          <span><?= sf_storyboard_h($character['role']) ?></span>
          <strong><?= sf_storyboard_h($character['name']) ?></strong>
          <small><?= sf_storyboard_h($character['summary']) ?></small>
          <small><strong>Consistency:</strong> <?= sf_storyboard_h($character['notes']) ?></small>
          <div class="sf-admin-form-actions"><button type="button"<?= sf_admin_form_disabled_attr() ?>>View Details</button><button type="button"<?= sf_admin_form_disabled_attr() ?>>Replace Image</button></div>
        </div>
      </article>
    <?php endforeach; ?>
    <article class="sf-storyboard-character-card sf-storyboard-empty-card"><div><strong>Add more characters</strong><small>Upload actor, musician, or reference images to improve future visual consistency.</small></div></article>
  </div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head">
    <div><span class="sf-panel-eyebrow">Storyboard</span><h2>9-scene screenplay grid</h2></div>
    <div class="sf-admin-inline-form"><button type="button">View 3x3</button><button type="button">Expand All</button></div>
  </div>
  <p class="sf-admin-copy">Each card supports manual editing now and is prepared for later AI rewrite, image regeneration, and user image upload actions.</p>
  <div class="sf-storyboard-scene-grid">
    <?php foreach ($scenes as $scene): ?>
      <article id="scene-<?= (int)$scene['number'] ?>" class="sf-storyboard-scene-card">
        <div class="sf-storyboard-thumb sf-storyboard-scene-thumb" style="background-image:url('<?= sf_storyboard_h(sf_url($scene['image'])) ?>')"><span><?= (int)$scene['number'] ?></span></div>
        <div class="sf-storyboard-scene-body">
          <span>Scene <?= (int)$scene['number'] ?> · <?= sf_storyboard_h($scene['status']) ?></span>
          <strong><?= sf_storyboard_h($scene['title']) ?></strong>
          <small><strong>Prompt:</strong> <?= sf_storyboard_h($scene['prompt']) ?></small>
          <small><strong>Dialog:</strong> “<?= sf_storyboard_h($scene['dialog']) ?>”</small>
          <small><strong>Characters:</strong> <?php foreach ($scene['characters'] as $characterName) echo sf_storyboard_render_character_chip($characterName) . ' '; ?></small>
          <div class="sf-storyboard-scene-actions">
            <button type="button"<?= sf_admin_form_disabled_attr() ?>>Edit</button>
            <button type="button"<?= sf_admin_form_disabled_attr() ?>>Rewrite Scene</button>
            <button type="button"<?= sf_admin_form_disabled_attr() ?>>Regenerate Image</button>
            <button type="button"<?= sf_admin_form_disabled_attr() ?>>Upload Image</button>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Implementation Notes</span><h2>What this shell prepares</h2></div><a href="<?= sf_url('docs/PHASE_40_STORYBOARDING_MODULE.md') ?>">Phase Docs</a></div>
  <div class="sf-admin-roadmap">
    <div><span>Prompt</span><strong>Script to 9 scenes</strong><p>Future API endpoint expands a basic prompt into structured scene data.</p></div>
    <div><span>AI</span><strong>Rewrite per scene</strong><p>Each scene can be rewritten independently while preserving story continuity.</p></div>
    <div><span>Image</span><strong>Regenerate or upload</strong><p>Each scene supports generated imagery or manual replacement.</p></div>
    <div><span>Cast</span><strong>Character consistency</strong><p>Character reference images and notes will be passed into generation jobs.</p></div>
  </div>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
