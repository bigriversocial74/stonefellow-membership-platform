<?php
$pageTitle = 'Storyboards';
$pageDescription = 'Storyboard project list and visual screenplay workspace entry point.';
$pageClass = 'membership-page admin-catalog-page storyboards-page';
require __DIR__ . '/../includes/storyboards.php';
require_once __DIR__ . '/../includes/ai_settings.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
    sf_admin_flash('error', 'Security check failed. Refresh and try again.');
    sf_admin_redirect();
  }
  if (!sf_storyboard_ready()) {
    sf_admin_flash('warning', 'Run migration 021 before saving storyboard projects. Static shell data is still being used.');
    sf_admin_redirect();
  }
  $id = sf_storyboard_create_project($_POST);
  if ($id > 0) {
    sf_admin_flash('success', 'Storyboard project created.');
    sf_admin_redirect(sf_url('admin/storyboard-builder.php?project_id=' . $id));
  }
  sf_admin_flash('error', 'Storyboard project could not be created.');
  sf_admin_redirect();
}

$projects = sf_storyboard_projects();
$providerOptions = function_exists('sf_ai_provider_options') ? sf_ai_provider_options() : ['chatgpt'=>'ChatGPT / OpenAI','claude'=>'Claude / Anthropic'];
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Storyboarding', 'Storyboard projects', 'Create and manage AI-assisted 9-scene visual screenplay projects. API keys are managed in admin AI settings and never shown in this workspace.', 'storyboards');
?>
<section class="sf-admin-card-grid">
  <a class="sf-admin-action-card" href="<?= sf_url('admin/storyboard-builder.php') ?>"><span>Create</span><strong>New Storyboard</strong><small>Start from a script prompt and generate a 9-scene screenplay plan.</small></a>
  <div class="sf-admin-action-card"><span>Projects</span><strong><?= count($projects) ?></strong><small><?= sf_storyboard_ready() ? 'Database-backed projects.' : 'Static shell preview until migration 021.' ?></small></div>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/ai-settings.php') ?>"><span>AI Provider</span><strong>Admin Managed</strong><small>Claude/ChatGPT keys and limits belong in admin AI settings.</small></a>
  <div class="sf-admin-action-card"><span>Phase</span><strong>41</strong><small>Persistence tables and admin AI settings added.</small></div>
</section>

<section class="sf-admin-two-col sf-admin-two-col-wide">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Create</span><h2>New storyboard project</h2></div><span class="sf-admin-mini-pill">9 scenes</span></div>
    <form class="sf-admin-form" method="post">
      <?= sf_csrf_field() ?>
      <label>Title<input name="title" placeholder="Stonefellow and the Sunrise Jam"<?= sf_admin_form_disabled_attr() ?>></label>
      <label>Basic Script Prompt<textarea name="short_prompt" rows="4" placeholder="Describe the story. The generation phase will expand this into 9 scenes."<?= sf_admin_form_disabled_attr() ?>></textarea></label>
      <div class="sf-admin-form-grid">
        <label>Genre<input name="genre" placeholder="Music comedy drama"<?= sf_admin_form_disabled_attr() ?>></label>
        <label>Tone<input name="tone" placeholder="Cinematic, funny, emotional"<?= sf_admin_form_disabled_attr() ?>></label>
        <label>Visual Style<input name="visual_style" value="Cinematic realistic"<?= sf_admin_form_disabled_attr() ?>></label>
      </div>
      <div class="sf-admin-form-grid">
        <label>Aspect Ratio<input name="aspect_ratio" value="16:9"<?= sf_admin_form_disabled_attr() ?>></label>
        <label>Scene Count<input type="number" min="1" max="12" name="scene_count" value="9"<?= sf_admin_form_disabled_attr() ?>></label>
        <label>Text Provider<?= sf_admin_select('default_text_provider', $providerOptions, 'chatgpt') ?></label>
      </div>
      <div class="sf-admin-form-grid">
        <label>Image Provider<?= sf_admin_select('default_image_provider', $providerOptions, 'chatgpt') ?></label>
      </div>
      <div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>>Create Storyboard</button></div>
    </form>
  </article>
  <aside class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Required SQL</span><h2>Migration 021</h2></div><a href="<?= sf_url('admin/ai-settings.php') ?>">AI Settings</a></div>
    <p class="sf-admin-copy">Saving storyboards requires <code>database/migrations/021_storyboarding_ai_settings.sql</code>. Until that migration is installed, this page safely shows static shell data.</p>
    <div class="sf-admin-roadmap"><div><span>DB</span><strong>Storyboards</strong><p>Projects, scenes, characters, references, scene-character links, and jobs.</p></div><div><span>AI</span><strong>Provider settings</strong><p>Admin-only Claude/ChatGPT keys, defaults, limits, and usage tracking.</p></div></div>
  </aside>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head">
    <div><span class="sf-panel-eyebrow">Project List</span><h2>Storyboard workspace</h2></div>
    <a href="<?= sf_url('docs/PHASE_41_STORYBOARDING_SQL_AI_SETTINGS.md') ?>">Phase Docs</a>
  </div>
  <div class="sf-admin-table-wrap">
    <table class="sf-admin-table">
      <thead><tr><th>Storyboard</th><th>Status</th><th>Scenes</th><th>Characters</th><th>Updated</th><th>Open</th></tr></thead>
      <tbody>
        <?php foreach ($projects as $project): ?>
          <tr>
            <td><strong><?= sf_storyboard_h($project['title']) ?></strong><small><?= sf_storyboard_h($project['genre']) ?></small></td>
            <td><?= sf_admin_status_badge(sf_storyboard_status_label($project['status'])) ?></td>
            <td><?= (int)$project['completed_scenes'] ?> / <?= (int)$project['scene_count'] ?></td>
            <td><?= (int)$project['characters'] ?></td>
            <td><?= sf_storyboard_h($project['updated_at']) ?></td>
            <td><a href="<?= sf_url('admin/storyboard-builder.php?project_id=' . (int)$project['id']) ?>">Open Builder</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Build Order</span><h2>Production path</h2></div><span class="sf-admin-mini-pill">SQL added</span></div>
  <div class="sf-admin-roadmap">
    <div><span>40</span><strong>Module Shell</strong><p>Storyboard list, builder layout, prompt, settings, characters, and 9-scene grid.</p></div>
    <div><span>41</span><strong>SQL + Admin AI Settings</strong><p>Persist storyboards, scenes, characters, references, jobs, provider settings, and usage limits.</p></div>
    <div><span>42</span><strong>Script Generation API</strong><p>Generate structured 9-scene screenplay data from a basic user prompt.</p></div>
    <div><span>43</span><strong>Images + Scene Actions</strong><p>Generate, upload, rewrite, and regenerate scene visuals with character reference guidance.</p></div>
  </div>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
