<?php
$pageTitle = 'Storyboards';
$pageDescription = 'Combined Stonefellow storyboarding workspace for seasons, episodes, scene sheets, scene cards, characters, and AI storyboard generation.';
$pageClass = 'membership-page admin-catalog-page storyboards-page storyboarding-system-page';
require __DIR__ . '/../includes/storyboards.php';
require_once __DIR__ . '/../includes/storyboarding_system.php';
require_once __DIR__ . '/../includes/ai_settings.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
    sf_admin_flash('error', 'Security check failed. Refresh and try again.');
    sf_admin_redirect();
  }

  $action = (string)($_POST['action'] ?? 'create_ai_storyboard');
  $id = sf_admin_int($_POST['id'] ?? null, 0) ?? 0;

  if ($action === 'create_ai_storyboard') {
    if (!sf_storyboard_ready()) {
      sf_admin_flash('warning', 'Run migration 021 before saving AI storyboard projects. Static shell data is still being used.');
      sf_admin_redirect();
    }
    $storyboardId = sf_storyboard_create_project($_POST);
    if ($storyboardId > 0) {
      sf_admin_flash('success', 'AI storyboard project created.');
      sf_admin_redirect(sf_url('admin/storyboard-builder.php?project_id=' . $storyboardId));
    }
    sf_admin_flash('error', 'AI storyboard project could not be created.');
    sf_admin_redirect();
  }

  if (!sf_story_v1_ready()) {
    sf_admin_flash('warning', 'Import database/storyboarding_system_v1.sql before saving seasons, episodes, scene sheets, or cards.');
    sf_admin_redirect();
  }

  if ($action === 'save_season') {
    $title = trim((string)($_POST['title'] ?? ''));
    if ($title === '') { sf_admin_flash('error', 'Season title is required.'); sf_admin_redirect(); }
    $slug = trim((string)($_POST['slug'] ?? '')) ?: sf_story_v1_unique_slug('story_seasons', $title, $id);
    $payload = [
      'season_number' => sf_admin_int($_POST['season_number'] ?? null, 1) ?? 1,
      'title' => $title,
      'slug' => $slug,
      'logline' => sf_admin_nullable_string($_POST['logline'] ?? '') ?: '',
      'description' => sf_admin_nullable_string($_POST['description'] ?? ''),
      'theme_notes' => sf_admin_nullable_string($_POST['theme_notes'] ?? ''),
      'arc_notes' => sf_admin_nullable_string($_POST['arc_notes'] ?? ''),
      'status' => $_POST['status'] ?? 'draft',
      'sort_order' => sf_admin_int($_POST['sort_order'] ?? null, 10) ?? 10,
    ];
    $newId = sf_story_v1_save_row('story_seasons', $payload, $id);
    sf_admin_flash($newId ? 'success' : 'error', $newId ? 'Season saved.' : 'Season could not be saved.');
    sf_admin_redirect(sf_url('admin/storyboards.php?season_id=' . ($newId ?: $id)));
  }

  if ($action === 'save_episode') {
    $title = trim((string)($_POST['title'] ?? ''));
    $seasonId = sf_admin_int($_POST['story_season_id'] ?? null, 0) ?? 0;
    if ($title === '' || $seasonId <= 0) { sf_admin_flash('error', 'Episode title and season are required.'); sf_admin_redirect(); }
    $slug = trim((string)($_POST['slug'] ?? '')) ?: sf_story_v1_unique_slug('story_episodes', $title, $id);
    $payload = [
      'story_season_id' => $seasonId,
      'episode_number' => sf_admin_int($_POST['episode_number'] ?? null, 1) ?? 1,
      'title' => $title,
      'slug' => $slug,
      'logline' => sf_admin_nullable_string($_POST['logline'] ?? '') ?: '',
      'synopsis' => sf_admin_nullable_string($_POST['synopsis'] ?? ''),
      'runtime_target_minutes' => sf_admin_int($_POST['runtime_target_minutes'] ?? null),
      'production_status' => $_POST['production_status'] ?? 'outline',
      'sort_order' => sf_admin_int($_POST['sort_order'] ?? null, 10) ?? 10,
    ];
    $newId = sf_story_v1_save_row('story_episodes', $payload, $id);
    sf_admin_flash($newId ? 'success' : 'error', $newId ? 'Episode saved.' : 'Episode could not be saved.');
    sf_admin_redirect(sf_url('admin/storyboards.php?season_id=' . $seasonId . '&episode_id=' . ($newId ?: $id)));
  }

  if ($action === 'save_scene_sheet') {
    $title = trim((string)($_POST['scene_title'] ?? ''));
    $episodeId = sf_admin_int($_POST['story_episode_id'] ?? null, 0) ?? 0;
    if ($title === '' || $episodeId <= 0) { sf_admin_flash('error', 'Scene title and episode are required.'); sf_admin_redirect(); }
    $payload = [
      'story_episode_id' => $episodeId,
      'scene_number' => sf_admin_int($_POST['scene_number'] ?? null, 1) ?? 1,
      'scene_title' => $title,
      'location_label' => sf_admin_nullable_string($_POST['location_label'] ?? '') ?: '',
      'time_of_day' => sf_admin_nullable_string($_POST['time_of_day'] ?? '') ?: '',
      'scene_summary' => sf_admin_nullable_string($_POST['scene_summary'] ?? ''),
      'scene_purpose' => sf_admin_nullable_string($_POST['scene_purpose'] ?? ''),
      'emotional_beat' => sf_admin_nullable_string($_POST['emotional_beat'] ?? ''),
      'conflict_notes' => sf_admin_nullable_string($_POST['conflict_notes'] ?? ''),
      'production_notes' => sf_admin_nullable_string($_POST['production_notes'] ?? ''),
      'scene_status' => $_POST['scene_status'] ?? 'draft',
      'sort_order' => sf_admin_int($_POST['sort_order'] ?? null, 10) ?? 10,
    ];
    $newId = sf_story_v1_save_row('story_scene_sheets', $payload, $id);
    if ($newId) sf_story_v1_sync_scene_characters($newId, $_POST['character_ids'] ?? []);
    sf_admin_flash($newId ? 'success' : 'error', $newId ? 'Scene sheet saved.' : 'Scene sheet could not be saved.');
    sf_admin_redirect(sf_url('admin/storyboards.php?episode_id=' . $episodeId . '#scene-' . ($newId ?: $id)));
  }

  if ($action === 'save_scene_card') {
    $title = trim((string)($_POST['card_title'] ?? ''));
    $sceneId = sf_admin_int($_POST['story_scene_sheet_id'] ?? null, 0) ?? 0;
    $episodeId = sf_admin_int($_POST['story_episode_id'] ?? null, 0) ?? 0;
    if ($title === '' || $sceneId <= 0) { sf_admin_flash('error', 'Card title and scene are required.'); sf_admin_redirect(); }
    $payload = [
      'story_scene_sheet_id' => $sceneId,
      'card_type' => $_POST['card_type'] ?? 'beat',
      'card_title' => $title,
      'card_body' => sf_admin_nullable_string($_POST['card_body'] ?? ''),
      'sort_order' => sf_admin_int($_POST['sort_order'] ?? null, 10) ?? 10,
    ];
    $newId = sf_story_v1_save_row('story_scene_cards', $payload, $id);
    sf_admin_flash($newId ? 'success' : 'error', $newId ? 'Scene card saved.' : 'Scene card could not be saved.');
    sf_admin_redirect(sf_url('admin/storyboards.php?episode_id=' . $episodeId . '#scene-' . $sceneId));
  }

  sf_admin_redirect();
}

$projects = sf_storyboard_projects();
$counts = sf_story_v1_counts();
$seasons = sf_story_v1_seasons();
$allEpisodes = sf_story_v1_episodes();
$seasonId = sf_admin_int($_GET['season_id'] ?? null, 0) ?? 0;
if ($seasonId <= 0) $seasonId = (int)($allEpisodes[0]['story_season_id'] ?? sf_story_v1_first_id($seasons));
$episodes = sf_story_v1_episodes($seasonId);
$episodeId = sf_admin_int($_GET['episode_id'] ?? null, 0) ?? 0;
if ($episodeId <= 0) $episodeId = sf_story_v1_first_id($episodes ?: $allEpisodes);
$selectedEpisode = sf_story_v1_find($allEpisodes, $episodeId) ?: [];
if (!$seasonId && $selectedEpisode) $seasonId = (int)($selectedEpisode['story_season_id'] ?? 0);
$sceneSheets = sf_story_v1_scene_sheets($episodeId);
$characters = sf_story_v1_characters('active');
$providerOptions = function_exists('sf_ai_provider_options') ? sf_ai_provider_options() : ['chatgpt'=>'ChatGPT / OpenAI','claude'=>'Claude / Anthropic'];
$isCreating = isset($_GET['new']);
$disabled = sf_story_v1_disabled_attr();
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Storyboarding', 'Season-first story development', 'One combined workspace: seasons, episodes, scene sheets, scene cards, characters, and AI storyboard generation.', 'storyboards');
?>
<div class="sf-story-v1-toolbar"><a href="<?= sf_url('admin/storyboards.php') ?>">Storyboards</a><a href="<?= sf_url('admin/story-characters.php') ?>">Character Catalog</a><a href="<?= sf_url('database/storyboarding_system_v1.sql') ?>">SQL Migration</a></div>
<?php if (!sf_story_v1_ready()): ?><section class="sf-story-v1-warning"><strong>SQL required:</strong> Import <code>database/storyboarding_system_v1.sql</code> to enable live saves, drag/drop order persistence, scene cards, and character catalog records.</section><?php endif; ?>
<section class="sf-admin-card-grid" style="grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));">
  <div class="sf-admin-action-card" style="min-height:108px;"><span>Seasons</span><strong><?= (int)$counts['seasons'] ?></strong><small>Required first step before episodes.</small></div>
  <div class="sf-admin-action-card" style="min-height:108px;"><span>Episodes</span><strong><?= (int)$counts['episodes'] ?></strong><small>Episodes belong to seasons.</small></div>
  <div class="sf-admin-action-card" style="min-height:108px;"><span>Scene Sheets</span><strong><?= (int)$counts['scenes'] ?></strong><small>Editable title, setting, purpose, and cast.</small></div>
  <div class="sf-admin-action-card" style="min-height:108px;"><span>Scene Cards</span><strong><?= (int)$counts['cards'] ?></strong><small>Different scenes can have different card counts.</small></div>
  <div class="sf-admin-action-card" style="min-height:108px;"><span>Characters</span><strong><?= (int)$counts['characters'] ?></strong><small>Main catalog for appearances and arcs.</small></div>
</section>

<section class="sf-story-v1-grid">
  <aside class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Step 1 + 2</span><h2>Seasons and episodes</h2></div></div>
    <p class="sf-admin-copy">Create or select a season first. Episodes cannot be created without a season.</p>
    <div class="sf-story-v1-list">
      <?php foreach ($seasons as $season): ?>
        <article class="sf-story-v1-item <?= (int)$season['id'] === $seasonId ? 'sf-story-v1-selected' : '' ?>">
          <div class="sf-story-v1-item-head"><div><h3>S<?= (int)$season['season_number'] ?> · <?= sf_admin_h($season['title']) ?></h3><p><?= sf_admin_h($season['logline'] ?? '') ?></p></div><a href="<?= sf_url('admin/storyboards.php?season_id=' . (int)$season['id']) ?>">Open</a></div>
          <div class="sf-story-v1-meta"><span><?= sf_story_v1_status_label((string)($season['status'] ?? 'draft')) ?></span><span><?= sf_admin_h($season['slug'] ?? '') ?></span></div>
        </article>
      <?php endforeach; ?>
      <?php if (!$seasons): ?><article class="sf-story-v1-item"><h3>No seasons yet</h3><p>Create the first season before adding episodes.</p></article><?php endif; ?>
    </div>
    <details><summary>Add Season</summary><form class="sf-admin-form" method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="save_season"><div class="sf-story-v1-form-grid"><label>Season #<input type="number" name="season_number" value="<?= count($seasons) + 1 ?>"<?= $disabled ?>></label><label>Sort<input type="number" name="sort_order" value="<?= (count($seasons) + 1) * 10 ?>"<?= $disabled ?>></label></div><label>Title<input name="title" placeholder="Season 2"<?= $disabled ?>></label><label>Logline<textarea name="logline" rows="2"<?= $disabled ?>></textarea></label><label>Status<?= sf_admin_select('status', sf_story_v1_status_options(), 'draft') ?></label><div class="sf-admin-form-actions"><button type="submit"<?= $disabled ?>>Save Season</button></div></form></details>
    <hr>
    <div class="sf-story-v1-list">
      <?php foreach ($episodes as $episode): ?>
        <article class="sf-story-v1-item <?= (int)$episode['id'] === $episodeId ? 'sf-story-v1-selected' : '' ?>">
          <div class="sf-story-v1-item-head"><div><h3>E<?= (int)$episode['episode_number'] ?> · <?= sf_admin_h($episode['title']) ?></h3><p><?= sf_admin_h($episode['logline'] ?? '') ?></p></div><a href="<?= sf_url('admin/storyboards.php?season_id=' . (int)$seasonId . '&episode_id=' . (int)$episode['id']) ?>">Open</a></div>
          <div class="sf-story-v1-meta"><span><?= sf_story_v1_status_label((string)($episode['production_status'] ?? 'outline')) ?></span><span><?= (int)($episode['runtime_target_minutes'] ?? 0) ?> min target</span></div>
        </article>
      <?php endforeach; ?>
      <?php if (!$episodes): ?><article class="sf-story-v1-item"><h3>No episodes yet</h3><p>Create the first episode for this season.</p></article><?php endif; ?>
    </div>
    <details><summary>Add Episode</summary><form class="sf-admin-form" method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="save_episode"><label>Season<?= sf_admin_relation_select('story_season_id', $seasons, $seasonId, 'Choose season') ?></label><div class="sf-story-v1-form-grid"><label>Episode #<input type="number" name="episode_number" value="<?= count($episodes) + 1 ?>"<?= $disabled ?>></label><label>Runtime Target<input type="number" name="runtime_target_minutes" value="48"<?= $disabled ?>></label></div><label>Title<input name="title" placeholder="Episode title"<?= $disabled ?>></label><label>Logline<textarea rows="2" name="logline"<?= $disabled ?>></textarea></label><label>Synopsis<textarea rows="4" name="synopsis"<?= $disabled ?>></textarea></label><label>Status<?= sf_admin_select('production_status', sf_story_v1_status_options(), 'outline') ?></label><div class="sf-admin-form-actions"><button type="submit"<?= $disabled ?>>Save Episode</button></div></form></details>
  </aside>

  <section class="sf-admin-panel" id="scenes">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Step 3 + 4</span><h2><?= sf_admin_h($selectedEpisode['title'] ?? 'Choose an episode') ?></h2></div><span class="sf-story-v1-save-status" data-story-save-status></span></div>
    <p class="sf-admin-copy">Drag scene sheets to change episode scene order. Open the modal to edit scene title, setting, purpose, emotional beat, conflict, production notes, and characters. Each scene can have multiple scene cards.</p>
    <div class="sf-story-v1-list" data-story-drag-list data-save-url="<?= sf_url('api/storyboarding-system.php') ?>" data-action="reorder_scene_sheets" data-csrf="<?= sf_admin_h(sf_csrf_token()) ?>">
      <?php foreach ($sceneSheets as $scene): $sceneId = (int)$scene['id']; $cards = sf_story_v1_scene_cards($sceneId); ?>
        <article id="scene-<?= $sceneId ?>" class="sf-story-v1-item" data-story-id="<?= $sceneId ?>">
          <div class="sf-story-v1-item-head"><span class="sf-story-v1-drag">↕</span><div><h3>Scene <span data-story-number><?= (int)$scene['scene_number'] ?></span>: <?= sf_admin_h($scene['scene_title']) ?></h3><p><?= sf_admin_h($scene['scene_summary'] ?? '') ?></p></div><span><?= (int)($scene['card_count'] ?? count($cards)) ?> cards</span></div>
          <div class="sf-story-v1-meta"><span><?= sf_admin_h($scene['location_label'] ?? 'Location TBD') ?></span><span><?= sf_admin_h($scene['time_of_day'] ?? 'Time TBD') ?></span><span><?= sf_story_v1_status_label((string)($scene['scene_status'] ?? 'draft')) ?></span><?php foreach (($scene['characters'] ?? []) as $name): ?><span><?= sf_admin_h($name) ?></span><?php endforeach; ?></div>
          <div class="sf-story-v1-mini-actions"><button type="button" data-story-modal-open="scene-settings-<?= $sceneId ?>">Edit Scene Settings</button><button type="button" data-story-modal-open="scene-card-<?= $sceneId ?>">Add Scene Card</button></div>
          <dialog class="sf-story-v1-modal" id="scene-settings-<?= $sceneId ?>"><form method="dialog" class="sf-story-v1-modal-close"><button aria-label="Close scene settings">×</button></form><div class="sf-story-v1-modal-body"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Scene Settings</span><h2>Scene <?= (int)$scene['scene_number'] ?>: <?= sf_admin_h($scene['scene_title']) ?></h2></div></div><form class="sf-story-v1-inline-form" method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="save_scene_sheet"><input type="hidden" name="id" value="<?= $sceneId ?>"><input type="hidden" name="story_episode_id" value="<?= (int)$episodeId ?>"><input type="hidden" name="scene_number" value="<?= (int)$scene['scene_number'] ?>"><input type="hidden" name="sort_order" value="<?= (int)($scene['sort_order'] ?? 10) ?>"><label>Editable Scene Title<input name="scene_title" value="<?= sf_admin_h($scene['scene_title']) ?>"<?= $disabled ?>></label><div class="sf-story-v1-form-grid"><label>Scene Setting / Location<input name="location_label" value="<?= sf_admin_h($scene['location_label'] ?? '') ?>"<?= $disabled ?>></label><label>Time of Day<input name="time_of_day" value="<?= sf_admin_h($scene['time_of_day'] ?? '') ?>"<?= $disabled ?>></label></div><label>Scene Summary<textarea name="scene_summary" rows="2"<?= $disabled ?>><?= sf_admin_h($scene['scene_summary'] ?? '') ?></textarea></label><label>Scene Purpose<textarea name="scene_purpose" rows="2"<?= $disabled ?>><?= sf_admin_h($scene['scene_purpose'] ?? '') ?></textarea></label><label>Emotional Beat<textarea name="emotional_beat" rows="2"<?= $disabled ?>><?= sf_admin_h($scene['emotional_beat'] ?? '') ?></textarea></label><label>Conflict Notes<textarea name="conflict_notes" rows="2"<?= $disabled ?>><?= sf_admin_h($scene['conflict_notes'] ?? '') ?></textarea></label><label>Production Notes<textarea name="production_notes" rows="2"<?= $disabled ?>><?= sf_admin_h($scene['production_notes'] ?? '') ?></textarea></label><label>Status<?= sf_admin_select('scene_status', sf_story_v1_status_options(), $scene['scene_status'] ?? 'draft') ?></label><div class="sf-story-v1-characters"><?php foreach ($characters as $char): ?><label><input type="checkbox" name="character_ids[]" value="<?= (int)$char['id'] ?>" <?= in_array($char['character_name'], $scene['characters'] ?? [], true) ? 'checked' : '' ?><?= $disabled ?>><?= sf_admin_h($char['character_name']) ?></label><?php endforeach; ?></div><div class="sf-admin-form-actions"><button type="submit"<?= $disabled ?>>Save Scene Settings</button></div></form></div></dialog>
          <div class="sf-story-v1-card-list" data-story-drag-list data-save-url="<?= sf_url('api/storyboarding-system.php') ?>" data-action="reorder_scene_cards" data-csrf="<?= sf_admin_h(sf_csrf_token()) ?>">
            <?php foreach ($cards as $card): ?><article class="sf-story-v1-scene-card" data-story-id="<?= (int)$card['id'] ?>"><span class="sf-story-v1-card-type">↕ <?= sf_admin_h($card['card_type'] ?? 'beat') ?></span><strong><?= sf_admin_h($card['card_title'] ?? '') ?></strong><small><?= sf_admin_h($card['card_body'] ?? '') ?></small></article><?php endforeach; ?>
          </div>
          <dialog class="sf-story-v1-modal" id="scene-card-<?= $sceneId ?>"><form method="dialog" class="sf-story-v1-modal-close"><button aria-label="Close scene card form">×</button></form><div class="sf-story-v1-modal-body"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Scene Card</span><h2>Add card to <?= sf_admin_h($scene['scene_title']) ?></h2></div></div><form class="sf-story-v1-inline-form" method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="save_scene_card"><input type="hidden" name="story_scene_sheet_id" value="<?= $sceneId ?>"><input type="hidden" name="story_episode_id" value="<?= (int)$episodeId ?>"><label>Card Type<?= sf_admin_select('card_type', sf_story_v1_card_type_options(), 'beat') ?></label><label>Card Title<input name="card_title" placeholder="Conflict beat, camera note, dialogue moment..."<?= $disabled ?>></label><label>Card Details<textarea name="card_body" rows="3"<?= $disabled ?>></textarea></label><div class="sf-admin-form-actions"><button type="submit"<?= $disabled ?>>Add Scene Card</button></div></form></div></dialog>
        </article>
      <?php endforeach; ?>
      <?php if (!$sceneSheets): ?><article class="sf-story-v1-item"><h3>No scene sheets yet</h3><p>Add the first scene sheet for this episode.</p></article><?php endif; ?>
    </div>
    <details open><summary>Add Scene Sheet</summary><form class="sf-admin-form" method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="save_scene_sheet"><input type="hidden" name="story_episode_id" value="<?= (int)$episodeId ?>"><div class="sf-story-v1-form-grid"><label>Scene #<input type="number" name="scene_number" value="<?= count($sceneSheets) + 1 ?>"<?= $disabled ?>></label><label>Sort<input type="number" name="sort_order" value="<?= (count($sceneSheets) + 1) * 10 ?>"<?= $disabled ?>></label></div><label>Scene Title<input name="scene_title" placeholder="Scene title"<?= $disabled ?>></label><label>Scene Summary<textarea rows="3" name="scene_summary"<?= $disabled ?>></textarea></label><div class="sf-story-v1-form-grid"><label>Location / Setting<input name="location_label"<?= $disabled ?>></label><label>Time of Day<input name="time_of_day"<?= $disabled ?>></label></div><div class="sf-admin-form-actions"><button type="submit"<?= $disabled ?>>Add Scene Sheet</button></div></form></details>
  </section>
</section>

<?php if (!$isCreating): ?>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">AI Storyboard Projects</span><h2>Visual generation layer</h2></div><a href="<?= sf_url('admin/storyboards.php?new=1') ?>">Add AI Storyboard</a></div>
  <p class="sf-admin-copy">Use AI storyboard projects after the season, episode, and scene-sheet structure is planned.</p>
  <div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Storyboard</th><th>Status</th><th>Scenes</th><th>Characters</th><th>Updated</th><th></th></tr></thead><tbody><?php if (!$projects): ?><tr><td colspan="6">No storyboard projects yet.</td></tr><?php endif; ?><?php foreach ($projects as $project): ?><tr><td><strong><?= sf_storyboard_h($project['title'] ?? '') ?></strong><small><?= sf_storyboard_h($project['genre'] ?? 'Storyboard project') ?></small></td><td><?= sf_admin_status_badge(sf_storyboard_status_label((string)($project['status'] ?? 'draft'))) ?></td><td><?= (int)($project['completed_scenes'] ?? 0) ?> / <?= (int)($project['scene_count'] ?? 9) ?></td><td><?= (int)($project['characters'] ?? 0) ?></td><td><?= sf_storyboard_h($project['updated_at'] ?? '') ?></td><td><a href="<?= sf_url('admin/storyboard-builder.php?project_id=' . (int)($project['id'] ?? 0)) ?>">Open Builder</a></td></tr><?php endforeach; ?></tbody></table></div>
</section>
<?php else: ?>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Create</span><h2>New AI storyboard project</h2></div><a href="<?= sf_url('admin/storyboards.php') ?>">Back to Storyboards</a></div>
  <p class="sf-admin-copy">Use this after the season, episode, and scene-sheet structure is ready. This builder remains for visual generation and AI-assisted boards.</p>
  <form class="sf-admin-form" method="post"><input type="hidden" name="action" value="create_ai_storyboard"><?= sf_csrf_field() ?><label>Title<input name="title" placeholder="Stonefellow and the Sunrise Jam"<?= sf_admin_form_disabled_attr() ?>></label><label>Basic Script Prompt<textarea name="short_prompt" rows="4" placeholder="Describe the story. The generation phase will expand this into 9 scenes."<?= sf_admin_form_disabled_attr() ?>></textarea></label><div class="sf-admin-form-grid"><label>Genre<input name="genre" placeholder="Music comedy drama"<?= sf_admin_form_disabled_attr() ?>></label><label>Tone<input name="tone" placeholder="Cinematic, funny, emotional"<?= sf_admin_form_disabled_attr() ?>></label><label>Visual Style<input name="visual_style" value="Cinematic realistic"<?= sf_admin_form_disabled_attr() ?>></label></div><div class="sf-admin-form-grid"><label>Aspect Ratio<input name="aspect_ratio" value="16:9"<?= sf_admin_form_disabled_attr() ?>></label><label>Scene Count<input type="number" min="1" max="12" name="scene_count" value="9"<?= sf_admin_form_disabled_attr() ?>></label><label>Text Provider<?= sf_admin_select('default_text_provider', $providerOptions, 'chatgpt') ?></label></div><div class="sf-admin-form-grid"><label>Image Provider<?= sf_admin_select('default_image_provider', $providerOptions, 'chatgpt') ?></label></div><div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>>Create AI Storyboard</button></div></form>
</section>
<?php endif; ?>
<script src="<?= sf_asset('js/storyboarding-system.js') ?>"></script>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>