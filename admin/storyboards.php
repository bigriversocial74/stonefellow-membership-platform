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
$isCreating = isset($_GET['new']);
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Storyboarding', 'Storyboard projects', 'Create and manage AI-assisted 9-scene visual screenplay projects. API keys are managed in admin AI settings and never shown in this workspace.', 'storyboards');
?>
<section class="sf-admin-card-grid" style="grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));">
  <div class="sf-admin-action-card" style="min-height: 108px;"><span>Projects</span><strong><?= count($projects) ?></strong><small><?= sf_storyboard_ready() ? 'Current storyboard projects.' : 'Static preview until migration 021.' ?></small></div>
  <div class="sf-admin-action-card" style="min-height: 108px;"><span>Scenes</span><strong>9</strong><small>Default screenplay plan per project.</small></div>
  <div class="sf-admin-action-card" style="min-height: 108px;"><span>AI Provider</span><strong>Admin Managed</strong><small>Claude/ChatGPT keys stay in AI settings.</small></div>
  <div class="sf-admin-action-card" style="min-height: 108px;"><span>Status</span><strong><?= sf_storyboard_ready() ? 'Ready' : 'Setup' ?></strong><small><?= sf_storyboard_ready() ? 'Database-backed workspace.' : 'Install migration 021 to save.' ?></small></div>
</section>

<?php if (!$isCreating): ?>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head">
    <div><span class="sf-panel-eyebrow">Project List</span><h2>Current storyboards</h2></div>
    <a href="<?= sf_url('admin/storyboards.php?new=1') ?>">Add Storyboard</a>
  </div>
  <div class="sf-admin-table-wrap">
    <table class="sf-admin-table">
      <thead><tr><th>Storyboard</th><th>Status</th><th>Scenes</th><th>Characters</th><th>Updated</th><th></th></tr></thead>
      <tbody>
        <?php if (!$projects): ?><tr><td colspan="6">No storyboard projects yet. Use Add Storyboard to create the first project.</td></tr><?php endif; ?>
        <?php foreach ($projects as $project): ?>
          <tr>
            <td><strong><?= sf_storyboard_h($project['title'] ?? '') ?></strong><small><?= sf_storyboard_h($project['genre'] ?? 'Storyboard project') ?></small></td>
            <td><?= sf_admin_status_badge(sf_storyboard_status_label((string)($project['status'] ?? 'draft'))) ?></td>
            <td><?= (int)($project['completed_scenes'] ?? 0) ?> / <?= (int)($project['scene_count'] ?? 9) ?></td>
            <td><?= (int)($project['characters'] ?? 0) ?></td>
            <td><?= sf_storyboard_h($project['updated_at'] ?? '') ?></td>
            <td><a href="<?= sf_url('admin/storyboard-builder.php?project_id=' . (int)($project['id'] ?? 0)) ?>">Open Builder</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php else: ?>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head">
    <div><span class="sf-panel-eyebrow">Create</span><h2>New storyboard project</h2></div>
    <a href="<?= sf_url('admin/storyboards.php') ?>">Back to Storyboards</a>
  </div>
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
</section>
<?php endif; ?>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
