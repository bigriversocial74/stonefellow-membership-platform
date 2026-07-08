<?php
$pageTitle = 'Storyboards';
$pageDescription = 'Season-first Stonefellow storyboarding workspace with episode, scene sheet, scene card, character catalog, and AI storyboard project entry points.';
$pageClass = 'membership-page admin-catalog-page storyboards-page storyboarding-system-page';
require __DIR__ . '/../includes/storyboards.php';
require_once __DIR__ . '/../includes/storyboarding_system.php';
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
$storyCounts = function_exists('sf_story_v1_counts') ? sf_story_v1_counts() : ['seasons'=>0,'episodes'=>0,'scenes'=>0,'cards'=>0,'characters'=>0];
$storySeasons = function_exists('sf_story_v1_seasons') ? sf_story_v1_seasons() : [];
$storyEpisodes = function_exists('sf_story_v1_episodes') ? sf_story_v1_episodes() : [];
$providerOptions = function_exists('sf_ai_provider_options') ? sf_ai_provider_options() : ['chatgpt'=>'ChatGPT / OpenAI','claude'=>'Claude / Anthropic'];
$isCreating = isset($_GET['new']);
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Storyboarding', 'Season-first story development', 'Start with seasons, then episodes, then scene sheets and scene cards. AI storyboard projects remain available below for visual generation.', 'storyboards');
?>
<section class="sf-admin-card-grid" style="grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));">
  <div class="sf-admin-action-card" style="min-height:108px;"><span>Seasons</span><strong><?= (int)$storyCounts['seasons'] ?></strong><small>Required first step before episodes.</small></div>
  <div class="sf-admin-action-card" style="min-height:108px;"><span>Episodes</span><strong><?= (int)$storyCounts['episodes'] ?></strong><small>Each episode owns its scene sheets.</small></div>
  <div class="sf-admin-action-card" style="min-height:108px;"><span>Scene Sheets</span><strong><?= (int)$storyCounts['scenes'] ?></strong><small>Editable scene title, setting, purpose, and cast.</small></div>
  <div class="sf-admin-action-card" style="min-height:108px;"><span>Scene Cards</span><strong><?= (int)$storyCounts['cards'] ?></strong><small>Some scenes can have more cards than others.</small></div>
  <div class="sf-admin-action-card" style="min-height:108px;"><span>Characters</span><strong><?= (int)$storyCounts['characters'] ?></strong><small>Main catalog for appearances and arcs.</small></div>
</section>

<section class="sf-admin-panel sf-story-v1-season-overview">
  <div class="sf-admin-panel-head">
    <div><span class="sf-panel-eyebrow">Seasons First</span><h2>Season → Episode → Scene Sheet → Scene Cards</h2></div>
    <div class="sf-admin-inline-form"><a href="<?= sf_url('admin/story-system.php') ?>">Open Story System</a><a href="<?= sf_url('admin/story-characters.php') ?>">Character Catalog</a></div>
  </div>
  <p class="sf-admin-copy">A user should create a season before creating an episode. Each episode then holds multiple scene sheets. Each scene sheet can hold multiple ordered scene cards, and different scenes can have different card counts.</p>
  <div class="sf-story-v1-season-strip">
    <?php if (!$storySeasons): ?><article class="sf-story-v1-season-card"><strong>No seasons yet</strong><p>Create your first season to unlock episode and scene planning.</p><a href="<?= sf_url('admin/story-system.php') ?>">Create Season</a></article><?php endif; ?>
    <?php foreach ($storySeasons as $season): $seasonEpisodes = array_values(array_filter($storyEpisodes, static fn($episode) => (int)($episode['story_season_id'] ?? 0) === (int)($season['id'] ?? 0))); ?>
      <article class="sf-story-v1-season-card">
        <span>Season <?= (int)($season['season_number'] ?? 1) ?></span>
        <strong><?= sf_storyboard_h($season['title'] ?? 'Untitled Season') ?></strong>
        <p><?= sf_storyboard_h($season['logline'] ?? 'Season logline pending.') ?></p>
        <div class="sf-story-v1-meta"><span><?= count($seasonEpisodes) ?> episodes</span><span><?= sf_storyboard_h(ucwords(str_replace('_',' ', (string)($season['status'] ?? 'draft')))) ?></span></div>
        <a href="<?= sf_url('admin/story-system.php?season_id=' . (int)($season['id'] ?? 0)) ?>">Manage Season</a>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="sf-admin-panel sf-story-v1-workflow-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Workflow</span><h2>Correct creation order</h2></div><span class="sf-admin-mini-pill">Backward compatible</span></div>
  <div class="sf-admin-roadmap">
    <div><span>1</span><strong>Create Season</strong><p>Season title, logline, theme notes, status, and long arc.</p></div>
    <div><span>2</span><strong>Create Episode</strong><p>Episode belongs to a season and carries logline, synopsis, runtime target, and status.</p></div>
    <div><span>3</span><strong>Add Scene Sheets</strong><p>Each episode has ordered scene sheets with editable scene title, location, time, conflict, and production notes.</p></div>
    <div><span>4</span><strong>Add Scene Cards</strong><p>Each scene can have as many scene cards as needed: beats, dialogue, camera, music, prop, wardrobe, or notes.</p></div>
  </div>
</section>

<?php if (!$isCreating): ?>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head">
    <div><span class="sf-panel-eyebrow">AI Storyboard Projects</span><h2>Current storyboards</h2></div>
    <a href="<?= sf_url('admin/storyboards.php?new=1') ?>">Add AI Storyboard</a>
  </div>
  <p class="sf-admin-copy">These are the visual/AI storyboard projects. They should now be treated as a generation layer after the season, episode, and scene-sheet structure is planned.</p>
  <div class="sf-admin-table-wrap">
    <table class="sf-admin-table">
      <thead><tr><th>Storyboard</th><th>Status</th><th>Scenes</th><th>Characters</th><th>Updated</th><th></th></tr></thead>
      <tbody>
        <?php if (!$projects): ?><tr><td colspan="6">No storyboard projects yet. Use Add AI Storyboard after your season/episode outline is ready.</td></tr><?php endif; ?>
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
    <div><span class="sf-panel-eyebrow">Create</span><h2>New AI storyboard project</h2></div>
    <a href="<?= sf_url('admin/storyboards.php') ?>">Back to Storyboards</a>
  </div>
  <p class="sf-admin-copy">Use this after the season, episode, and scene-sheet structure is ready. This builder remains for 9-scene visual generation and AI-assisted boards.</p>
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
    <div class="sf-admin-form-grid"><label>Image Provider<?= sf_admin_select('default_image_provider', $providerOptions, 'chatgpt') ?></label></div>
    <div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>>Create AI Storyboard</button></div>
  </form>
</section>
<?php endif; ?>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>