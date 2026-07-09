<?php
$pageTitle = 'AI Script Shot List';
$pageDescription = 'Draft shot lists and media prompts for reviewed AI storyboard scene shells.';
$pageClass = 'membership-page admin-catalog-page storyboarding-system-page ai-script-shot-list-page';
require __DIR__ . '/../includes/storyboards.php';
require_once __DIR__ . '/../includes/storyboarding_system.php';
require_once __DIR__ . '/../includes/admin_catalog.php';

function sf_aisl_text($value, string $fallback = 'Not set'): string { $text = trim((string)($value ?? '')); return $text === '' ? $fallback : $text; }
function sf_aisl_snip($value, int $length = 240, string $fallback = 'Not set'): string { $text = sf_aisl_text($value, $fallback); return strlen($text) > $length ? rtrim(substr($text, 0, $length - 1)) . '…' : $text; }
function sf_aisl_first_id(array $rows): int { foreach ($rows as $row) { $id = (int)($row['id'] ?? 0); if ($id > 0) return $id; } return 0; }
function sf_aisl_find(array $rows, int $id): array { foreach ($rows as $row) if ((int)($row['id'] ?? 0) === $id) return $row; return []; }
function sf_aisl_scene_title(array $row): string { return sf_aisl_text($row['title'] ?? $row['project_title'] ?? '', 'Storyboard #' . (int)($row['id'] ?? 0)); }
function sf_aisl_scene_status(array $row): string { return sf_aisl_text($row['producer_scene_status'] ?? $row['status'] ?? '', 'outline'); }
function sf_aisl_scene_rows(int $episodeId = 0): array { if ($episodeId > 0 && function_exists('sf_story_v1_episode_storyboards')) return sf_story_v1_episode_storyboards($episodeId); if (!sf_admin_table_exists('storyboards')) return []; return sf_admin_fetch_all("SELECT * FROM storyboards WHERE producer_scene_status IN ('ready','needs_review','outline') ORDER BY COALESCE(updated_at, created_at) DESC, id DESC LIMIT 80"); }
function sf_aisl_shots(array $season, array $episode, array $scene): array {
  $title = sf_aisl_scene_title($scene);
  $summary = sf_aisl_text($scene['source_script'] ?? $scene['short_prompt'] ?? '', 'Draft a clean visual interpretation of the selected scene.');
  $tone = sf_aisl_text($scene['tone'] ?? '', 'cinematic microdrama');
  $setting = sf_aisl_text($episode['setting_label'] ?? '', 'confirm location');
  $base = 'Scene: ' . $title . '. Tone: ' . $tone . '. Setting: ' . $setting . '. Direction: ' . sf_aisl_snip($summary, 280);
  return [
    ['type'=>'Opening Establishing Shot','duration'=>'0–4 sec','frame'=>'Wide frame that instantly shows where the scene happens.','prompt'=>$base . ' Opening wide shot, clear geography, dramatic but readable composition.'],
    ['type'=>'Character Action Shot','duration'=>'4–10 sec','frame'=>'Medium shot focused on the first visible action or decision.','prompt'=>$base . ' Medium shot, character action is clear, body language tells the conflict.'],
    ['type'=>'Reaction Close-Up','duration'=>'10–17 sec','frame'=>'Close-up that shows the emotional turn.','prompt'=>$base . ' Close-up reaction, expressive eyes, tension visible, shallow depth of field.'],
    ['type'=>'Insert / Continuity Detail','duration'=>'17–23 sec','frame'=>'Prop, phone, text, object, wardrobe, or location detail that anchors continuity.','prompt'=>$base . ' Insert shot of the key continuity detail, crisp focus, story information readable.'],
    ['type'=>'Final Hook Shot','duration'=>'23–30 sec','frame'=>'Final image that creates a hook, cliffhanger, reveal, or comedic button.','prompt'=>$base . ' Final hook shot, strong last-frame reveal, designed for a 30-second microdrama ending.'],
  ];
}

$seasons = function_exists('sf_story_v1_seasons') ? sf_story_v1_seasons() : [];
$seasonId = sf_admin_int($_GET['season_id'] ?? null, 0) ?? 0;
if ($seasonId <= 0) $seasonId = sf_aisl_first_id($seasons);
$selectedSeason = sf_aisl_find($seasons, $seasonId) ?: ($seasons[0] ?? []);
$episodes = function_exists('sf_story_v1_episodes') ? sf_story_v1_episodes($seasonId ?: 0) : [];
$episodeId = sf_admin_int($_GET['episode_id'] ?? null, 0) ?? 0;
if ($episodeId <= 0) $episodeId = sf_aisl_first_id($episodes);
$selectedEpisode = sf_aisl_find($episodes, $episodeId) ?: ($episodes[0] ?? []);
$scenes = sf_aisl_scene_rows($episodeId);
$sceneId = sf_admin_int($_GET['scene_id'] ?? null, 0) ?? 0;
if ($sceneId <= 0) $sceneId = sf_aisl_first_id($scenes);
$selectedScene = sf_aisl_find($scenes, $sceneId) ?: ($scenes[0] ?? []);
$shots = $selectedScene ? sf_aisl_shots($selectedSeason, $selectedEpisode, $selectedScene) : [];
$sceneTitle = $selectedScene ? sf_aisl_scene_title($selectedScene) : 'No scene selected';
$videoPrompt = $selectedScene ? '30-second vertical microdrama video plan for ' . $sceneTitle . ":\n" : '';
foreach ($shots as $shot) $videoPrompt .= '- ' . $shot['duration'] . ' ' . $shot['type'] . ': ' . $shot['frame'] . "\n";

require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('AI Script Producer', 'Shot list pack', 'Draft shot lists and media prompts for reviewed AI scene shells.', 'ai-script-shot-list');
?>
<style>
.ai-script-shot-list-page .sf-shot-hero,.ai-script-shot-list-page .sf-shot-layout{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(320px,.8fr);gap:16px;margin-bottom:18px}.ai-script-shot-list-page .sf-shot-panel{padding:20px;border:1px solid rgba(232,198,127,.16);border-radius:22px;background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.025));box-shadow:0 20px 56px rgba(0,0,0,.22)}.ai-script-shot-list-page .sf-shot-panel h2{margin:8px 0;color:#fff;font-size:clamp(30px,4vw,56px);letter-spacing:-.05em;line-height:.98}.ai-script-shot-list-page .sf-shot-copy,.ai-script-shot-list-page .sf-shot-panel p{color:rgba(255,255,255,.68);line-height:1.55}.ai-script-shot-list-page .sf-shot-context-grid,.ai-script-shot-list-page .sf-shot-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.ai-script-shot-list-page .sf-shot-card{padding:16px;border:1px solid rgba(255,255,255,.08);border-radius:18px;background:rgba(0,0,0,.15)}.ai-script-shot-list-page .sf-shot-card h3{color:#fff;margin:10px 0 8px}.ai-script-shot-list-page textarea{width:100%;min-height:150px;resize:vertical}.ai-script-shot-list-page .sf-shot-pill{display:inline-block;margin-top:8px;border:1px solid rgba(232,198,127,.18);border-radius:999px;padding:4px 8px;color:#f5d98d;font-size:11px;font-weight:900}@media(max-width:980px){.ai-script-shot-list-page .sf-shot-hero,.ai-script-shot-list-page .sf-shot-layout,.ai-script-shot-list-page .sf-shot-context-grid,.ai-script-shot-list-page .sf-shot-grid{grid-template-columns:1fr}}
</style>
<section class="sf-shot-hero"><div class="sf-shot-panel"><span class="sf-panel-eyebrow">Phase 10</span><h2>Shot list pack</h2><p>Turn a reviewed AI scene shell into a five-beat shot list and media prompt draft for 30-second microdrama production planning.</p></div><div class="sf-shot-panel"><span class="sf-panel-eyebrow">Guardrail</span><h2>No generation</h2><p>This page drafts shot prompts only. It does not generate media, save prompts, publish scenes, or send notifications.</p></div></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Context</span><h2>Select scene</h2></div><div><a href="<?= sf_url('admin/ai-script-production-pack.php') ?>">Production Pack</a> · <a href="<?= sf_url('admin/ai-script-review-queue.php') ?>">Review Queue</a></div></div><form method="get" class="sf-admin-form"><div class="sf-shot-context-grid"><label>Season<select name="season_id" onchange="this.form.submit()"><option value="0">Select season</option><?php foreach ($seasons as $season): ?><option value="<?= (int)$season['id'] ?>"<?= (int)$season['id'] === $seasonId ? ' selected' : '' ?>><?= sf_admin_h($season['title'] ?? 'Season') ?></option><?php endforeach; ?></select></label><label>Episode<select name="episode_id" onchange="this.form.submit()"><option value="0">Select episode</option><?php foreach ($episodes as $episode): ?><option value="<?= (int)$episode['id'] ?>"<?= (int)$episode['id'] === $episodeId ? ' selected' : '' ?>>Episode <?= (int)($episode['episode_number'] ?? 1) ?> — <?= sf_admin_h($episode['title'] ?? 'Episode') ?></option><?php endforeach; ?></select></label><label>Scene<select name="scene_id" onchange="this.form.submit()"><option value="0">Select scene</option><?php foreach ($scenes as $scene): ?><option value="<?= (int)$scene['id'] ?>"<?= (int)$scene['id'] === $sceneId ? ' selected' : '' ?>><?= sf_admin_h(sf_aisl_scene_title($scene)) ?> — <?= sf_admin_h(sf_aisl_scene_status($scene)) ?></option><?php endforeach; ?></select></label></div></form></section>
<?php if (!$selectedScene): ?><section class="sf-admin-panel"><p class="sf-shot-copy">No scene shell is available for this selection.</p></section><?php else: ?>
<section class="sf-shot-layout"><article class="sf-shot-panel"><span class="sf-panel-eyebrow">Selected Scene</span><h2><?= sf_admin_h($sceneTitle) ?></h2><p class="sf-shot-copy"><strong>Status:</strong> <?= sf_admin_h(sf_aisl_scene_status($selectedScene)) ?><br><strong>Tone:</strong> <?= sf_admin_h(sf_aisl_text($selectedScene['tone'] ?? '', 'Cinematic microdrama')) ?></p><p class="sf-shot-copy"><?= sf_admin_h(sf_aisl_snip($selectedScene['source_script'] ?? $selectedScene['short_prompt'] ?? '', 360, 'No source direction saved.')) ?></p><span class="sf-shot-pill">Draft-only shot list</span></article><aside class="sf-shot-panel"><span class="sf-panel-eyebrow">30-Second Video Prompt</span><textarea readonly><?= sf_admin_h($videoPrompt) ?></textarea></aside></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Shot Cards</span><h2><?= count($shots) ?> planned shots</h2></div></div><div class="sf-shot-grid"><?php foreach ($shots as $shot): ?><article class="sf-shot-card"><span class="sf-panel-eyebrow"><?= sf_admin_h($shot['duration']) ?></span><h3><?= sf_admin_h($shot['type']) ?></h3><p class="sf-shot-copy"><?= sf_admin_h($shot['frame']) ?></p><textarea readonly rows="6"><?= sf_admin_h($shot['prompt']) ?></textarea></article><?php endforeach; ?></div></section>
<?php endif; ?>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Safety</span><h2>Prompt planning only</h2></div></div><p class="sf-shot-copy">This phase creates browser-only prompt drafts. It does not call an AI media provider, write to the database, publish, queue messages, or assign production tasks.</p></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
