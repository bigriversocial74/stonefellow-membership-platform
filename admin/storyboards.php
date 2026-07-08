<?php
$pageTitle = 'Storyboards';
$pageDescription = 'One-screen Stonefellow producer workspace for Season 1, episodes, current storyboard scenes, and builder scene cards.';
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
      sf_admin_flash('warning', 'Run migration 021 before saving AI storyboard scenes.');
      sf_admin_redirect();
    }
    $storyboardId = sf_storyboard_create_project($_POST);
    $episodeIdForScene = sf_admin_int($_POST['story_episode_id'] ?? null, 0) ?? 0;
    if ($storyboardId > 0 && $episodeIdForScene > 0 && sf_story_v1_bridge_ready()) {
      $episode = sf_story_v1_find(sf_story_v1_episodes(), $episodeIdForScene) ?: [];
      $seasonIdForScene = (int)($episode['story_season_id'] ?? 0);
      sf_admin_execute('UPDATE storyboards SET story_season_id = ?, story_episode_id = ?, producer_scene_order = IF(producer_scene_order > 0, producer_scene_order, id * 10), producer_scene_status = ?, updated_at = NOW() WHERE id = ?', [$seasonIdForScene ?: null, $episodeIdForScene, 'outline', $storyboardId]);
    }
    if ($storyboardId > 0) {
      sf_admin_flash('success', 'Storyboard scene created. Open Builder to manage its scene cards.');
      sf_admin_redirect(sf_url('admin/storyboards.php?episode_id=' . $episodeIdForScene . '&scene_id=' . $storyboardId . '#producer-board'));
    }
    sf_admin_flash('error', 'Storyboard scene could not be created.');
    sf_admin_redirect();
  }

  if (!sf_story_v1_ready()) {
    sf_admin_flash('warning', 'Import database/storyboarding_system_v1.sql before saving seasons or episodes.');
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
    if (sf_admin_column_exists('story_episodes', 'episode_outline')) $payload['episode_outline'] = sf_admin_nullable_string($_POST['episode_outline'] ?? '');
    if (sf_admin_column_exists('story_episodes', 'setting_label')) $payload['setting_label'] = sf_admin_nullable_string($_POST['setting_label'] ?? '') ?: '';
    $newId = sf_story_v1_save_row('story_episodes', $payload, $id);
    if ($newId) sf_story_v1_sync_episode_characters($newId, $_POST['episode_character_ids'] ?? []);
    sf_admin_flash($newId ? 'success' : 'error', $newId ? 'Episode saved.' : 'Episode could not be saved.');
    sf_admin_redirect(sf_url('admin/storyboards.php?season_id=' . $seasonId . '&episode_id=' . ($newId ?: $id)));
  }

  sf_admin_redirect();
}

$counts = sf_story_v1_counts();
$seasons = sf_story_v1_seasons();
$allEpisodes = sf_story_v1_episodes();
$seasonId = sf_admin_int($_GET['season_id'] ?? null, 0) ?? 0;
if ($seasonId <= 0) $seasonId = (int)($allEpisodes[0]['story_season_id'] ?? sf_story_v1_first_id($seasons));
$selectedSeason = sf_story_v1_find($seasons, $seasonId) ?: ($seasons[0] ?? []);
$episodes = sf_story_v1_episodes($seasonId);
$episodeId = sf_admin_int($_GET['episode_id'] ?? null, 0) ?? 0;
if ($episodeId <= 0) $episodeId = sf_story_v1_first_id($episodes ?: $allEpisodes);
$selectedEpisode = sf_story_v1_find($allEpisodes, $episodeId) ?: ($episodes[0] ?? []);
if (!$seasonId && $selectedEpisode) $seasonId = (int)($selectedEpisode['story_season_id'] ?? 0);
$episodeScenes = sf_story_v1_episode_storyboards($episodeId);
$sceneId = sf_admin_int($_GET['scene_id'] ?? null, 0) ?? 0;
if ($sceneId <= 0) $sceneId = sf_story_v1_first_id($episodeScenes);
$selectedScene = sf_story_v1_find($episodeScenes, $sceneId) ?: ($episodeScenes[0] ?? []);
$selectedSceneCards = $selectedScene ? sf_storyboard_scenes((int)$selectedScene['id']) : [];
$characters = sf_story_v1_characters('active');
$episodeCharacters = sf_story_v1_episode_characters($episodeId);
$episodeCharacterIds = array_map(static fn($row) => (int)($row['id'] ?? 0), $episodeCharacters);
$providerOptions = function_exists('sf_ai_provider_options') ? sf_ai_provider_options() : ['chatgpt'=>'ChatGPT / OpenAI','claude'=>'Claude / Anthropic'];
$disabled = sf_story_v1_disabled_attr();
$bridgeReady = sf_story_v1_bridge_ready();
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Storyboarding', 'Season 1 producer board', 'Season → Episode → current storyboard scenes → Open Builder for scene cards.', 'storyboards');
?>
<div class="sf-story-v1-toolbar"><a href="<?= sf_url('admin/storyboards.php') ?>">Producer Board</a><a href="<?= sf_url('admin/story-characters.php') ?>">Character Catalog</a><a href="<?= sf_url('database/storyboarding_season_episode_bridge_v1.sql') ?>">Bridge SQL</a></div>
<?php if (!$bridgeReady): ?><section class="sf-story-v1-warning"><strong>Bridge SQL required:</strong> Import <code>database/storyboarding_season_episode_bridge_v1.sql</code> to force Season 1 / Episode 1 and assign existing storyboard rows as episode scenes.</section><?php endif; ?>

<section class="sf-admin-card-grid sf-producer-stats">
  <div class="sf-admin-action-card"><span>Seasons</span><strong><?= (int)$counts['seasons'] ?></strong><small>Season 1 forced.</small></div>
  <div class="sf-admin-action-card"><span>Episodes</span><strong><?= (int)$counts['episodes'] ?></strong><small>Inside Season 1.</small></div>
  <div class="sf-admin-action-card"><span>Storyboard Scenes</span><strong><?= count($episodeScenes) ?></strong><small>Current rows assigned to episode.</small></div>
  <div class="sf-admin-action-card"><span>Builder Cards</span><strong><?= count($selectedSceneCards) ?></strong><small>Inside selected scene builder.</small></div>
  <div class="sf-admin-action-card"><span>Characters</span><strong><?= count($episodeCharacters) ?: (int)$counts['characters'] ?></strong><small>Episode main cast.</small></div>
</section>

<section class="sf-admin-panel sf-producer-board-shell" id="producer-board">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Producer Board</span><h2>Season → Episode → Storyboard Scene → Builder Scene Cards</h2></div><span class="sf-admin-mini-pill">Backward data bridge</span></div>
  <p class="sf-admin-copy">The existing AI storyboard rows are now treated as the current scenes under Season 1 / Episode 1. Open Builder remains the place where the selected storyboard scene expands into the detailed scene-sheet and visual cards.</p>
  <div class="sf-producer-board">
    <section class="sf-producer-column sf-producer-column-season">
      <div class="sf-producer-column-head"><span>Step 1</span><h3>Season</h3></div>
      <article class="sf-story-v1-item sf-story-v1-selected"><h3>S<?= (int)($selectedSeason['season_number'] ?? 1) ?> · <?= sf_admin_h($selectedSeason['title'] ?? 'Season 1') ?></h3><p><?= sf_admin_h($selectedSeason['logline'] ?? '') ?></p><div class="sf-story-v1-meta"><span><?= sf_story_v1_status_label((string)($selectedSeason['status'] ?? 'active')) ?></span></div></article>
      <div class="sf-story-v1-muted-copy"><strong>Season Outline</strong><p><?= sf_admin_h($selectedSeason['description'] ?? 'Season outline pending.') ?></p><p><?= sf_admin_h($selectedSeason['arc_notes'] ?? '') ?></p></div>
      <details><summary>Edit Season Outline</summary><form class="sf-admin-form" method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="save_season"><input type="hidden" name="id" value="<?= (int)($selectedSeason['id'] ?? 0) ?>"><label>Title<input name="title" value="<?= sf_admin_h($selectedSeason['title'] ?? 'Season 1') ?>"<?= $disabled ?>></label><label>Logline<textarea name="logline" rows="2"<?= $disabled ?>><?= sf_admin_h($selectedSeason['logline'] ?? '') ?></textarea></label><label>Season Outline<textarea name="description" rows="4"<?= $disabled ?>><?= sf_admin_h($selectedSeason['description'] ?? '') ?></textarea></label><label>Season Arc<textarea name="arc_notes" rows="4"<?= $disabled ?>><?= sf_admin_h($selectedSeason['arc_notes'] ?? '') ?></textarea></label><input type="hidden" name="season_number" value="<?= (int)($selectedSeason['season_number'] ?? 1) ?>"><input type="hidden" name="status" value="<?= sf_admin_h($selectedSeason['status'] ?? 'active') ?>"><button type="submit"<?= $disabled ?>>Save Season</button></form></details>
    </section>

    <section class="sf-producer-column sf-producer-column-episode">
      <div class="sf-producer-column-head"><span>Step 2</span><h3>Episode</h3></div>
      <div class="sf-story-v1-list"><?php foreach ($episodes as $episode): ?><article class="sf-story-v1-item <?= (int)$episode['id'] === $episodeId ? 'sf-story-v1-selected' : '' ?>"><h3>E<?= (int)$episode['episode_number'] ?> · <?= sf_admin_h($episode['title']) ?></h3><p><?= sf_admin_h($episode['logline'] ?? '') ?></p><div class="sf-story-v1-meta"><span><?= sf_story_v1_status_label((string)($episode['production_status'] ?? 'outline')) ?></span><span><?= (int)($episode['runtime_target_minutes'] ?? 0) ?> min</span></div><a href="<?= sf_url('admin/storyboards.php?season_id=' . (int)$seasonId . '&episode_id=' . (int)$episode['id']) ?>">Select Episode</a></article><?php endforeach; ?></div>
      <div class="sf-story-v1-muted-copy"><strong>Episode Outline</strong><p><?= sf_admin_h(sf_story_v1_episode_outline_text($selectedEpisode)) ?></p><?php if (!empty($selectedEpisode['setting_label'])): ?><p><strong>Setting:</strong> <?= sf_admin_h($selectedEpisode['setting_label']) ?></p><?php endif; ?></div>
      <details open><summary>Episode Settings + AI Outline</summary><form class="sf-admin-form" method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="save_episode"><input type="hidden" name="id" value="<?= (int)$episodeId ?>"><input type="hidden" name="story_season_id" value="<?= (int)$seasonId ?>"><label>Episode Title<input name="title" value="<?= sf_admin_h($selectedEpisode['title'] ?? 'Episode 1') ?>"<?= $disabled ?>></label><label>Episode Logline<textarea name="logline" rows="2"<?= $disabled ?>><?= sf_admin_h($selectedEpisode['logline'] ?? '') ?></textarea></label><label>Episode Outline<textarea name="episode_outline" rows="4"<?= $disabled ?>><?= sf_admin_h(sf_story_v1_episode_outline_text($selectedEpisode)) ?></textarea></label><label>Optional Setting<input name="setting_label" value="<?= sf_admin_h($selectedEpisode['setting_label'] ?? '') ?>"<?= $disabled ?>></label><div class="sf-story-v1-characters"><?php foreach ($characters as $char): ?><label><input type="checkbox" name="episode_character_ids[]" value="<?= (int)$char['id'] ?>" <?= in_array((int)$char['id'], $episodeCharacterIds, true) ? 'checked' : '' ?><?= $disabled ?>><?= sf_admin_h($char['character_name']) ?></label><?php endforeach; ?></div><input type="hidden" name="episode_number" value="<?= (int)($selectedEpisode['episode_number'] ?? 1) ?>"><input type="hidden" name="runtime_target_minutes" value="<?= (int)($selectedEpisode['runtime_target_minutes'] ?? 48) ?>"><input type="hidden" name="production_status" value="<?= sf_admin_h($selectedEpisode['production_status'] ?? 'outline') ?>"><button type="submit"<?= $disabled ?>>Save Episode</button></form><form class="sf-admin-form" method="post" action="<?= sf_url('api/story-episode-outline.php') ?>"><?= sf_csrf_field() ?><input type="hidden" name="episode_id" value="<?= (int)$episodeId ?>"><input type="hidden" name="return_url" value="<?= sf_admin_h(sf_url('admin/storyboards.php?episode_id=' . (int)$episodeId . '#producer-board')) ?>"><label>AI Provider<?= sf_admin_select('provider_key', $providerOptions, (string)($selectedEpisode['ai_outline_provider'] ?? '')) ?></label><label>AI Instruction<textarea name="prompt" rows="2" placeholder="Generate or improve this episode outline using the current scene list and selected main characters."></textarea></label><button type="submit"<?= (!$bridgeReady ? ' disabled' : '') ?>>Generate Episode Outline</button></form></details>
    </section>

    <section class="sf-producer-column sf-producer-column-scenes">
      <div class="sf-producer-column-head"><span>Step 3</span><h3>Episode Scenes</h3></div>
      <p class="sf-admin-copy">These are the current storyboard rows. Dragging changes scene order for this episode.</p>
      <div class="sf-story-v1-list" data-story-drag-list data-save-url="<?= sf_url('api/storyboarding-system.php') ?>" data-action="reorder_scene_sheets" data-csrf="<?= sf_admin_h(sf_csrf_token()) ?>">
        <?php foreach ($episodeScenes as $scene): ?><article class="sf-story-v1-item <?= (int)$scene['id'] === $sceneId ? 'sf-story-v1-selected' : '' ?>" data-story-id="<?= (int)$scene['id'] ?>"><div class="sf-story-v1-item-head"><span class="sf-story-v1-drag">↕</span><div><h3><?= sf_admin_h($scene['title'] ?? 'Storyboard Scene') ?></h3><p><?= sf_admin_h($scene['prompt'] ?? $scene['genre'] ?? '') ?></p></div></div><div class="sf-story-v1-meta"><span><?= sf_story_v1_status_label((string)($scene['status'] ?? 'outline')) ?></span><span><?= (int)($scene['completed_scenes'] ?? 0) ?> builder cards</span><span><?= (int)($scene['characters'] ?? 0) ?> characters</span></div><div class="sf-story-v1-mini-actions"><a href="<?= sf_url('admin/storyboards.php?episode_id=' . (int)$episodeId . '&scene_id=' . (int)$scene['id'] . '#producer-board') ?>">Select</a><a href="<?= sf_url('admin/storyboard-builder.php?project_id=' . (int)$scene['id']) ?>">Open Builder</a></div></article><?php endforeach; ?>
        <?php if (!$episodeScenes): ?><article class="sf-story-v1-item"><h3>No storyboard scenes assigned</h3><p>Import the bridge SQL to assign existing storyboard rows to Season 1 / Episode 1, or create a new storyboard scene below.</p></article><?php endif; ?>
      </div>
      <details><summary>Add Storyboard Scene</summary><form class="sf-admin-form" method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="create_ai_storyboard"><input type="hidden" name="story_episode_id" value="<?= (int)$episodeId ?>"><label>Scene Title<input name="title" placeholder="Backstage Stories"<?= sf_admin_form_disabled_attr() ?>></label><label>Scene Prompt<textarea name="short_prompt" rows="3" placeholder="Describe the scene/storyboard item."<?= sf_admin_form_disabled_attr() ?>></textarea></label><input type="hidden" name="genre" value="Scene"><input type="hidden" name="scene_count" value="9"><button type="submit"<?= sf_admin_form_disabled_attr() ?>>Create Scene</button></form></details>
    </section>

    <section class="sf-producer-column sf-producer-column-cards">
      <div class="sf-producer-column-head"><span>Step 4</span><h3>Builder Cards</h3></div>
      <?php if ($selectedScene): ?><article class="sf-story-v1-item sf-story-v1-selected"><h3><?= sf_admin_h($selectedScene['title'] ?? 'Selected Scene') ?></h3><p><?= sf_admin_h($selectedScene['prompt'] ?? '') ?></p><div class="sf-story-v1-mini-actions"><a href="<?= sf_url('admin/storyboard-builder.php?project_id=' . (int)$selectedScene['id']) ?>">Open Builder</a></div></article><?php endif; ?>
      <div class="sf-story-v1-card-list"><?php foreach ($selectedSceneCards as $card): ?><article class="sf-story-v1-scene-card"><span class="sf-story-v1-card-type">Card <?= (int)($card['number'] ?? 0) ?> · <?= sf_admin_h($card['status'] ?? 'draft') ?></span><strong><?= sf_admin_h($card['title'] ?? '') ?></strong><small><?= sf_admin_h($card['prompt'] ?? $card['summary'] ?? '') ?></small></article><?php endforeach; ?><?php if (!$selectedSceneCards): ?><article class="sf-story-v1-item"><h3>No builder cards yet</h3><p>Open Builder to generate or edit this scene’s cards.</p></article><?php endif; ?></div>
    </section>
  </div>
</section>

<script src="<?= sf_asset('js/storyboarding-system.js') ?>"></script>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>