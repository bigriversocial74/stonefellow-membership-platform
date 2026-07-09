<?php
$pageTitle = 'AI Script Media Prep Queue';
$pageDescription = 'Save approved AI media prompt cards before any generation is allowed.';
$pageClass = 'membership-page admin-catalog-page storyboarding-system-page ai-script-media-prep-page';
require __DIR__ . '/../includes/storyboards.php';
require_once __DIR__ . '/../includes/storyboarding_system.php';
require_once __DIR__ . '/../includes/admin_catalog.php';

function sf_aismp_text($value, string $fallback = 'Not set'): string { $text = trim((string)($value ?? '')); return $text === '' ? $fallback : $text; }
function sf_aismp_snip($value, int $length = 240, string $fallback = 'Not set'): string { $text = sf_aismp_text($value, $fallback); return strlen($text) > $length ? rtrim(substr($text, 0, $length - 1)) . '…' : $text; }
function sf_aismp_first_id(array $rows): int { foreach ($rows as $row) { $id = (int)($row['id'] ?? 0); if ($id > 0) return $id; } return 0; }
function sf_aismp_find(array $rows, int $id): array { foreach ($rows as $row) if ((int)($row['id'] ?? 0) === $id) return $row; return []; }
function sf_aismp_ready(): bool { return sf_admin_table_exists('story_ai_media_prompts'); }
function sf_aismp_statuses(): array { return ['draft'=>'Draft','needs_revision'=>'Needs Revision','approved'=>'Approved','ready_for_generation'=>'Ready for Generation']; }
function sf_aismp_scene_title(array $row): string { return sf_aismp_text($row['title'] ?? $row['project_title'] ?? '', 'Storyboard #' . (int)($row['id'] ?? 0)); }
function sf_aismp_scene_status(array $row): string { return sf_aismp_text($row['producer_scene_status'] ?? $row['status'] ?? '', 'outline'); }
function sf_aismp_scene_rows(int $episodeId = 0): array { if ($episodeId > 0 && function_exists('sf_story_v1_episode_storyboards')) return sf_story_v1_episode_storyboards($episodeId); if (!sf_admin_table_exists('storyboards')) return []; return sf_admin_fetch_all("SELECT * FROM storyboards ORDER BY COALESCE(updated_at, created_at) DESC, id DESC LIMIT 100"); }
function sf_aismp_cards(array $season, array $episode, array $scene): array {
  $title = sf_aismp_scene_title($scene);
  $summary = sf_aismp_text($scene['source_script'] ?? $scene['short_prompt'] ?? '', 'Draft visual media preparation for the selected scene.');
  $tone = sf_aismp_text($scene['tone'] ?? '', 'cinematic microdrama');
  $setting = sf_aismp_text($episode['setting_label'] ?? '', 'confirm location and background');
  $base = 'Scene: ' . $title . '. Tone: ' . $tone . '. Setting: ' . $setting . '. Direction: ' . sf_aismp_snip($summary, 320);
  return [
    ['type'=>'still_image','title'=>'Still Image Prompt — ' . $title,'aspect'=>'16:9','provider'=>'image','body'=>$base . ' Create a polished cinematic still frame that clearly communicates the scene conflict, readable emotion, and location.'],
    ['type'=>'vertical_video','title'=>'Vertical Video Prompt — ' . $title,'aspect'=>'9:16','provider'=>'video','body'=>$base . ' Create a 30-second vertical microdrama video plan with opening action, reaction, continuity insert, and final hook.'],
    ['type'=>'character_reference','title'=>'Character Reference Prompt — ' . $title,'aspect'=>'4:5','provider'=>'image','body'=>$base . ' Prepare character-reference framing for wardrobe, expression, body language, and continuity across future shots.'],
    ['type'=>'location_background','title'=>'Location / Background Prompt — ' . $title,'aspect'=>'16:9','provider'=>'image','body'=>$base . ' Prepare a clean location/background reference with lighting, time of day, production design, and readable geography.'],
    ['type'=>'continuity_detail','title'=>'Continuity Detail Prompt — ' . $title,'aspect'=>'1:1','provider'=>'image','body'=>$base . ' Prepare the key prop, phone, text, wardrobe, or object detail that anchors continuity in the scene.'],
    ['type'=>'thumbnail_hook','title'=>'Final Hook Thumbnail — ' . $title,'aspect'=>'9:16','provider'=>'image','body'=>$base . ' Prepare a high-impact thumbnail/final hook frame with a clear reveal, cliffhanger, or comedic button.'],
  ];
}
function sf_aismp_recent(int $storyboardId = 0): array { if (!sf_aismp_ready()) return []; $params=[]; $where=''; if ($storyboardId > 0) { $where=' WHERE storyboard_id = ?'; $params[]=$storyboardId; } return sf_admin_fetch_all('SELECT * FROM story_ai_media_prompts' . $where . ' ORDER BY created_at DESC, id DESC LIMIT 80', $params); }

$seasons = function_exists('sf_story_v1_seasons') ? sf_story_v1_seasons() : [];
$seasonId = sf_admin_int($_REQUEST['season_id'] ?? null, 0) ?? 0;
if ($seasonId <= 0) $seasonId = sf_aismp_first_id($seasons);
$selectedSeason = sf_aismp_find($seasons, $seasonId) ?: ($seasons[0] ?? []);
$episodes = function_exists('sf_story_v1_episodes') ? sf_story_v1_episodes($seasonId ?: 0) : [];
$episodeId = sf_admin_int($_REQUEST['episode_id'] ?? null, 0) ?? 0;
if ($episodeId <= 0) $episodeId = sf_aismp_first_id($episodes);
$selectedEpisode = sf_aismp_find($episodes, $episodeId) ?: ($episodes[0] ?? []);
$scenes = sf_aismp_scene_rows($episodeId);
$sceneId = sf_admin_int($_REQUEST['scene_id'] ?? null, 0) ?? 0;
if ($sceneId <= 0) $sceneId = sf_aismp_first_id($scenes);
$selectedScene = sf_aismp_find($scenes, $sceneId) ?: ($scenes[0] ?? []);
$cards = $selectedScene ? sf_aismp_cards($selectedSeason, $selectedEpisode, $selectedScene) : [];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'save_prompt_cards') {
  if (!sf_aismp_ready()) {
    sf_admin_flash('error', 'Media prompt table is not ready. Import database/story_ai_media_prompts.sql first.');
  } elseif (!$selectedScene || $sceneId <= 0) {
    sf_admin_flash('error', 'Select a scene before saving prompt cards.');
  } else {
    $saved = 0; $userId = function_exists('sf_current_user_id') ? sf_current_user_id() : null;
    foreach ($cards as $card) {
      $ok = sf_admin_execute('INSERT INTO story_ai_media_prompts (storyboard_id, story_season_id, story_episode_id, prompt_type, prompt_title, prompt_body, provider_hint, aspect_ratio, status, created_by_user_id, approved_by_user_id, approved_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())', [$sceneId, $seasonId ?: null, $episodeId ?: null, $card['type'], $card['title'], $card['body'], $card['provider'], $card['aspect'], 'approved', $userId, $userId]);
      if ($ok) $saved++;
    }
    sf_admin_audit('ai_script_save_media_prompt_cards', 'storyboard', $sceneId, null, ['saved'=>$saved,'episode_id'=>$episodeId]);
    sf_admin_flash($saved > 0 ? 'success' : 'error', $saved > 0 ? 'Saved ' . $saved . ' approved media prompt cards.' : 'No media prompt cards were saved.');
  }
  sf_admin_redirect(sf_url('admin/ai-script-media-prep.php?season_id=' . (int)$seasonId . '&episode_id=' . (int)$episodeId . '&scene_id=' . (int)$sceneId));
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'update_prompt_status') {
  $promptId = sf_admin_int($_POST['prompt_id'] ?? null, 0) ?? 0;
  $status = trim((string)($_POST['status'] ?? ''));
  if (!sf_aismp_ready() || $promptId <= 0 || !array_key_exists($status, sf_aismp_statuses())) {
    sf_admin_flash('error', 'Invalid media prompt status action.');
  } else {
    $before = sf_admin_fetch_one('SELECT * FROM story_ai_media_prompts WHERE id = ? LIMIT 1', [$promptId]);
    $userId = function_exists('sf_current_user_id') ? sf_current_user_id() : null;
    $ok = sf_admin_execute('UPDATE story_ai_media_prompts SET status = ?, approved_by_user_id = CASE WHEN ? IN (\'approved\',\'ready_for_generation\') THEN ? ELSE approved_by_user_id END, approved_at = CASE WHEN ? IN (\'approved\',\'ready_for_generation\') THEN NOW() ELSE approved_at END WHERE id = ?', [$status, $status, $userId, $status, $promptId]);
    $after = sf_admin_fetch_one('SELECT * FROM story_ai_media_prompts WHERE id = ? LIMIT 1', [$promptId]);
    if ($ok) sf_admin_audit('ai_script_media_prompt_status_update', 'story_ai_media_prompt', $promptId, $before, $after);
    sf_admin_flash($ok ? 'success' : 'error', $ok ? 'Media prompt status updated.' : 'Media prompt status could not be updated.');
  }
  sf_admin_redirect(sf_url('admin/ai-script-media-prep.php?season_id=' . (int)$seasonId . '&episode_id=' . (int)$episodeId . '&scene_id=' . (int)$sceneId));
}

$recentPrompts = sf_aismp_recent($sceneId);
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('AI Script Producer', 'Media prep queue', 'Save approved AI media prompt cards before any generation is allowed.', 'ai-script-media-prep');
?>
<style>
.ai-script-media-prep-page .sf-mp-hero,.ai-script-media-prep-page .sf-mp-layout{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(320px,.8fr);gap:16px;margin-bottom:18px}.ai-script-media-prep-page .sf-mp-panel{padding:20px;border:1px solid rgba(232,198,127,.16);border-radius:22px;background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.025));box-shadow:0 20px 56px rgba(0,0,0,.22)}.ai-script-media-prep-page .sf-mp-panel h2{margin:8px 0;color:#fff;font-size:clamp(30px,4vw,56px);letter-spacing:-.05em;line-height:.98}.ai-script-media-prep-page .sf-mp-copy,.ai-script-media-prep-page .sf-mp-panel p{color:rgba(255,255,255,.68);line-height:1.55}.ai-script-media-prep-page .sf-mp-context-grid,.ai-script-media-prep-page .sf-mp-card-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.ai-script-media-prep-page .sf-mp-card{padding:16px;border:1px solid rgba(255,255,255,.08);border-radius:18px;background:rgba(0,0,0,.15)}.ai-script-media-prep-page .sf-mp-card h3{color:#fff;margin:10px 0 8px}.ai-script-media-prep-page textarea{width:100%;min-height:145px;resize:vertical}.ai-script-media-prep-page .sf-mp-pill{display:inline-block;margin-top:8px;border:1px solid rgba(232,198,127,.18);border-radius:999px;padding:4px 8px;color:#f5d98d;font-size:11px;font-weight:900}@media(max-width:980px){.ai-script-media-prep-page .sf-mp-hero,.ai-script-media-prep-page .sf-mp-layout,.ai-script-media-prep-page .sf-mp-context-grid,.ai-script-media-prep-page .sf-mp-card-grid{grid-template-columns:1fr}}
</style>
<section class="sf-mp-hero"><div class="sf-mp-panel"><span class="sf-panel-eyebrow">Phase 11</span><h2>Media prep queue</h2><p>Save approved media prompt cards for reviewed AI scenes before any image or video generation is allowed.</p></div><div class="sf-mp-panel"><span class="sf-panel-eyebrow">Guardrail</span><h2>No generation</h2><p>This page saves prompt records only. It does not call media providers, generate files, publish scenes, or send notifications.</p></div></section>
<?php if (!sf_aismp_ready()): ?><section class="sf-story-v1-warning"><strong>SQL required:</strong> Import <code>database/story_ai_media_prompts.sql</code> before saving media prompt cards.</section><?php endif; ?>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Context</span><h2>Select scene</h2></div><div><a href="<?= sf_url('admin/ai-script-shot-list.php') ?>">Shot List</a> · <a href="<?= sf_url('admin/ai-script-production-pack.php') ?>">Production Pack</a></div></div><form method="get" class="sf-admin-form"><div class="sf-mp-context-grid"><label>Season<select name="season_id" onchange="this.form.submit()"><option value="0">Select season</option><?php foreach ($seasons as $season): ?><option value="<?= (int)$season['id'] ?>"<?= (int)$season['id'] === $seasonId ? ' selected' : '' ?>><?= sf_admin_h($season['title'] ?? 'Season') ?></option><?php endforeach; ?></select></label><label>Episode<select name="episode_id" onchange="this.form.submit()"><option value="0">Select episode</option><?php foreach ($episodes as $episode): ?><option value="<?= (int)$episode['id'] ?>"<?= (int)$episode['id'] === $episodeId ? ' selected' : '' ?>>Episode <?= (int)($episode['episode_number'] ?? 1) ?> — <?= sf_admin_h($episode['title'] ?? 'Episode') ?></option><?php endforeach; ?></select></label><label>Scene<select name="scene_id" onchange="this.form.submit()"><option value="0">Select scene</option><?php foreach ($scenes as $scene): ?><option value="<?= (int)$scene['id'] ?>"<?= (int)$scene['id'] === $sceneId ? ' selected' : '' ?>><?= sf_admin_h(sf_aismp_scene_title($scene)) ?> — <?= sf_admin_h(sf_aismp_scene_status($scene)) ?></option><?php endforeach; ?></select></label></div></form></section>
<?php if (!$selectedScene): ?><section class="sf-admin-panel"><p class="sf-mp-copy">No scene shell is available for this selection.</p></section><?php else: ?>
<section class="sf-mp-layout"><article class="sf-mp-panel"><span class="sf-panel-eyebrow">Selected Scene</span><h2><?= sf_admin_h(sf_aismp_scene_title($selectedScene)) ?></h2><p class="sf-mp-copy"><strong>Status:</strong> <?= sf_admin_h(sf_aismp_scene_status($selectedScene)) ?><br><strong>Prompt cards:</strong> <?= count($cards) ?></p><p class="sf-mp-copy"><?= sf_admin_h(sf_aismp_snip($selectedScene['source_script'] ?? $selectedScene['short_prompt'] ?? '', 360, 'No source direction saved.')) ?></p></article><aside class="sf-mp-panel"><span class="sf-panel-eyebrow">Approval Save</span><p class="sf-mp-copy">Saving creates approved prompt records for later generation review. It still does not generate any media.</p><form method="post" class="sf-admin-form"><?= sf_csrf_field() ?><input type="hidden" name="action" value="save_prompt_cards"><input type="hidden" name="season_id" value="<?= (int)$seasonId ?>"><input type="hidden" name="episode_id" value="<?= (int)$episodeId ?>"><input type="hidden" name="scene_id" value="<?= (int)$sceneId ?>"><div class="sf-admin-form-actions"><button type="submit"<?= sf_aismp_ready() ? '' : ' disabled' ?>>Approve & Save Prompt Cards</button></div></form></aside></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Generated Prompt Cards</span><h2><?= count($cards) ?> cards ready</h2></div></div><div class="sf-mp-card-grid"><?php foreach ($cards as $card): ?><article class="sf-mp-card"><span class="sf-panel-eyebrow"><?= sf_admin_h($card['type']) ?> · <?= sf_admin_h($card['aspect']) ?></span><h3><?= sf_admin_h($card['title']) ?></h3><textarea readonly><?= sf_admin_h($card['body']) ?></textarea><span class="sf-mp-pill">Provider hint: <?= sf_admin_h($card['provider']) ?></span></article><?php endforeach; ?></div></section>
<?php endif; ?>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Saved Queue</span><h2><?= count($recentPrompts) ?> saved prompt(s)</h2></div></div><?php if (!$recentPrompts): ?><p class="sf-mp-copy">No saved media prompt records for this scene yet.</p><?php else: ?><div class="sf-mp-card-grid"><?php foreach ($recentPrompts as $prompt): ?><article class="sf-mp-card"><?= sf_admin_status_badge((string)($prompt['status'] ?? 'draft')) ?><h3><?= sf_admin_h($prompt['prompt_title'] ?? 'Prompt') ?></h3><p class="sf-mp-copy"><strong><?= sf_admin_h($prompt['prompt_type'] ?? 'prompt') ?></strong> · <?= sf_admin_h($prompt['aspect_ratio'] ?? 'ratio') ?> · <?= sf_admin_h($prompt['provider_hint'] ?? 'provider') ?></p><textarea readonly><?= sf_admin_h($prompt['prompt_body'] ?? '') ?></textarea><form method="post" class="sf-admin-form"><?= sf_csrf_field() ?><input type="hidden" name="action" value="update_prompt_status"><input type="hidden" name="prompt_id" value="<?= (int)($prompt['id'] ?? 0) ?>"><input type="hidden" name="season_id" value="<?= (int)$seasonId ?>"><input type="hidden" name="episode_id" value="<?= (int)$episodeId ?>"><input type="hidden" name="scene_id" value="<?= (int)$sceneId ?>"><label>Status<select name="status"><?php foreach (sf_aismp_statuses() as $key => $label): ?><option value="<?= sf_admin_h($key) ?>"<?= (string)($prompt['status'] ?? '') === $key ? ' selected' : '' ?>><?= sf_admin_h($label) ?></option><?php endforeach; ?></select></label><div class="sf-admin-form-actions"><button type="submit">Update Status</button></div></form></article><?php endforeach; ?></div><?php endif; ?></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Safety</span><h2>Prep queue only</h2></div></div><p class="sf-mp-copy">This phase saves prompt preparation records only. Future phases can add provider-specific generation behind explicit approval, audit history, and rollback controls.</p></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
