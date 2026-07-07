<?php
$pageTitle = 'Storyboard Builder';
$pageDescription = 'AI-assisted 9-scene storyboard builder shell with script prompt, scene settings, characters, reference images, and editable scene cards.';
$pageClass = 'membership-page admin-catalog-page storyboards-page storyboard-builder-page';
require __DIR__ . '/../includes/storyboards.php';
$project = sf_storyboard_project((int)($_GET['project_id'] ?? 1));
$projectId = (int)($project['id'] ?? 0);
$settings = sf_storyboard_settings($project);
$characters = sf_storyboard_characters($projectId);
$scenes = sf_storyboard_scenes($projectId);
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
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Storyboard Settings</span><h2>Creator workflow</h2></div><a href="<?= sf_url('admin/ai-settings.php') ?>">AI Settings</a></div>
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
  <p class="sf-admin-copy">Character reference images and likeness notes are now backed by migration 021 tables when installed. Later generation jobs will use these records for consistency.</p>
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
  <p class="sf-admin-copy">Scene records are database-ready in Phase 41. AI generation and action endpoints come next.</p>
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
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Implementation Notes</span><h2>What Phase 41 adds</h2></div><a href="<?= sf_url('docs/PHASE_41_STORYBOARDING_SQL_AI_SETTINGS.md') ?>">Phase Docs</a></div>
  <div class="sf-admin-roadmap">
    <div><span>SQL</span><strong>Persistence</strong><p>Storyboards, scenes, characters, references, jobs, AI provider settings, and usage events.</p></div>
    <div><span>Admin</span><strong>AI settings</strong><p>Claude and ChatGPT keys, defaults, limits, and secure key storage.</p></div>
    <div><span>Next</span><strong>Generation API</strong><p>Phase 42 should connect prompt expansion to provider settings and save 9 scenes.</p></div>
    <div><span>Later</span><strong>Scene actions</strong><p>Rewrite, regenerate image, upload image, and character consistency endpoints.</p></div>
  </div>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
