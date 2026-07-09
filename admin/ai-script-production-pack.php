<?php
$pageTitle = 'AI Script Production Pack';
$pageDescription = 'Draft production handoff packs for reviewed AI storyboard scene shells.';
$pageClass = 'membership-page admin-catalog-page storyboarding-system-page ai-script-production-pack-page';
require __DIR__ . '/../includes/storyboards.php';
require_once __DIR__ . '/../includes/storyboarding_system.php';
require_once __DIR__ . '/../includes/story_scene_backgrounds.php';
require_once __DIR__ . '/../includes/story_series_assets.php';
require_once __DIR__ . '/../includes/admin_catalog.php';

function sf_aipp_text($value, string $fallback = 'Not set'): string { $text = trim((string)($value ?? '')); return $text === '' ? $fallback : $text; }
function sf_aipp_snip($value, int $length = 220, string $fallback = 'Not set'): string { $text = sf_aipp_text($value, $fallback); return strlen($text) > $length ? rtrim(substr($text, 0, $length - 1)) . '…' : $text; }
function sf_aipp_first_id(array $rows): int { foreach ($rows as $row) { $id = (int)($row['id'] ?? 0); if ($id > 0) return $id; } return 0; }
function sf_aipp_find(array $rows, int $id): array { foreach ($rows as $row) if ((int)($row['id'] ?? 0) === $id) return $row; return []; }
function sf_aipp_seasons(): array { return function_exists('sf_story_v1_seasons') ? sf_story_v1_seasons() : []; }
function sf_aipp_episodes(int $seasonId = 0): array { return function_exists('sf_story_v1_episodes') ? sf_story_v1_episodes($seasonId ?: 0) : []; }
function sf_aipp_scene_rows(int $episodeId = 0): array { if ($episodeId > 0 && function_exists('sf_story_v1_episode_storyboards')) return sf_story_v1_episode_storyboards($episodeId); if (!sf_admin_table_exists('storyboards')) return []; return sf_admin_fetch_all("SELECT * FROM storyboards WHERE producer_scene_status IN ('ready','needs_review','outline') ORDER BY COALESCE(updated_at, created_at) DESC, id DESC LIMIT 80"); }
function sf_aipp_scene_title(array $row): string { return sf_aipp_text($row['title'] ?? $row['project_title'] ?? '', 'Storyboard #' . (int)($row['id'] ?? 0)); }
function sf_aipp_scene_status(array $row): string { return sf_aipp_text($row['producer_scene_status'] ?? $row['status'] ?? '', 'outline'); }
function sf_aipp_packet(array $season, array $episode, array $scene): array {
  $sceneTitle = sf_aipp_scene_title($scene);
  $summary = sf_aipp_text($scene['short_prompt'] ?? $scene['source_script'] ?? $scene['description'] ?? '', 'Review the selected scene and prepare production direction.');
  $source = sf_aipp_text($scene['source_script'] ?? $scene['short_prompt'] ?? '', $summary);
  $tone = sf_aipp_text($scene['tone'] ?? '', 'Cinematic microdrama');
  $setting = sf_aipp_text($episode['setting_label'] ?? '', 'Confirm location/background');
  $tasks = [
    ['team'=>'Writing', 'task'=>'Lock the 30-second beat structure and make the opening action obvious.', 'priority'=>'High'],
    ['team'=>'Director', 'task'=>'Confirm the emotional turn, blocking, and final hook for the scene.', 'priority'=>'High'],
    ['team'=>'Continuity', 'task'=>'Check character, prop, wardrobe, and timeline continuity against the episode.', 'priority'=>'High'],
    ['team'=>'Art / Location', 'task'=>'Confirm background, time of day, lighting, and visual references.', 'priority'=>'Normal'],
    ['team'=>'Media', 'task'=>'Prepare image/video prompt references only after the scene is approved for production.', 'priority'=>'Normal'],
  ];
  return [
    'scene_title'=>$sceneTitle,
    'season'=>sf_aipp_text($season['title'] ?? '', 'No season selected'),
    'episode'=>sf_aipp_text($episode['title'] ?? '', 'No episode selected'),
    'status'=>sf_aipp_scene_status($scene),
    'summary'=>$summary,
    'source'=>$source,
    'tone'=>$tone,
    'setting'=>$setting,
    'visual_prompt'=>'Cinematic still frame for ' . $sceneTitle . '. Tone: ' . $tone . '. Setting: ' . $setting . '. Scene direction: ' . sf_aipp_snip($summary, 260),
    'director_notes'=>'Stage the scene around one clear action, one emotional shift, and one final cliffhanger or visual hook.',
    'tasks'=>$tasks,
  ];
}

$seasons = sf_aipp_seasons();
$seasonId = sf_admin_int($_GET['season_id'] ?? null, 0) ?? 0;
if ($seasonId <= 0) $seasonId = sf_aipp_first_id($seasons);
$selectedSeason = sf_aipp_find($seasons, $seasonId) ?: ($seasons[0] ?? []);
$episodes = sf_aipp_episodes($seasonId);
$episodeId = sf_admin_int($_GET['episode_id'] ?? null, 0) ?? 0;
if ($episodeId <= 0) $episodeId = sf_aipp_first_id($episodes);
$selectedEpisode = sf_aipp_find($episodes, $episodeId) ?: ($episodes[0] ?? []);
$scenes = sf_aipp_scene_rows($episodeId);
$sceneId = sf_admin_int($_GET['scene_id'] ?? null, 0) ?? 0;
if ($sceneId <= 0) $sceneId = sf_aipp_first_id($scenes);
$selectedScene = sf_aipp_find($scenes, $sceneId) ?: ($scenes[0] ?? []);
$packet = sf_aipp_packet($selectedSeason, $selectedEpisode, $selectedScene);

require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('AI Script Producer', 'Production handoff pack', 'Draft production checklists and prompts for reviewed AI scene shells.', 'ai-script-production-pack');
?>
<style>
.ai-script-production-pack-page .sf-pack-hero,.ai-script-production-pack-page .sf-pack-layout{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(320px,.8fr);gap:16px;margin-bottom:18px}.ai-script-production-pack-page .sf-pack-panel{padding:20px;border:1px solid rgba(232,198,127,.16);border-radius:22px;background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.025));box-shadow:0 20px 56px rgba(0,0,0,.22)}.ai-script-production-pack-page .sf-pack-panel h2{margin:8px 0;color:#fff;font-size:clamp(30px,4vw,56px);letter-spacing:-.05em;line-height:.98}.ai-script-production-pack-page .sf-pack-copy,.ai-script-production-pack-page .sf-pack-panel p{color:rgba(255,255,255,.68);line-height:1.55}.ai-script-production-pack-page .sf-pack-context-grid,.ai-script-production-pack-page .sf-pack-task-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.ai-script-production-pack-page .sf-pack-task{padding:15px;border:1px solid rgba(255,255,255,.08);border-radius:16px;background:rgba(0,0,0,.15)}.ai-script-production-pack-page .sf-pack-task strong{display:block;color:#fff}.ai-script-production-pack-page textarea{width:100%;min-height:150px;resize:vertical}.ai-script-production-pack-page .sf-pack-pill{display:inline-block;margin-top:8px;border:1px solid rgba(232,198,127,.18);border-radius:999px;padding:4px 8px;color:#f5d98d;font-size:11px;font-weight:900}@media(max-width:980px){.ai-script-production-pack-page .sf-pack-hero,.ai-script-production-pack-page .sf-pack-layout,.ai-script-production-pack-page .sf-pack-context-grid,.ai-script-production-pack-page .sf-pack-task-grid{grid-template-columns:1fr}}
</style>
<section class="sf-pack-hero"><div class="sf-pack-panel"><span class="sf-panel-eyebrow">Phase 9</span><h2>Production handoff</h2><p>Create a draft handoff pack from a reviewed AI scene: writing notes, director notes, visual prompt, and production checklist.</p></div><div class="sf-pack-panel"><span class="sf-panel-eyebrow">Guardrail</span><h2>Draft only</h2><p>No publishing, media generation, messages, assignments, or status changes happen from this page.</p></div></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Context</span><h2>Select scene for handoff</h2></div><div><a href="<?= sf_url('admin/ai-script-review-queue.php') ?>">Review Queue</a> · <a href="<?= sf_url('admin/ai-script-assistant.php') ?>">AI Script Producer</a></div></div><form method="get" class="sf-admin-form"><div class="sf-pack-context-grid"><label>Season<select name="season_id" onchange="this.form.submit()"><option value="0">Select season</option><?php foreach ($seasons as $season): ?><option value="<?= (int)$season['id'] ?>"<?= (int)$season['id'] === $seasonId ? ' selected' : '' ?>><?= sf_admin_h($season['title'] ?? 'Season') ?></option><?php endforeach; ?></select></label><label>Episode<select name="episode_id" onchange="this.form.submit()"><option value="0">Select episode</option><?php foreach ($episodes as $episode): ?><option value="<?= (int)$episode['id'] ?>"<?= (int)$episode['id'] === $episodeId ? ' selected' : '' ?>>Episode <?= (int)($episode['episode_number'] ?? 1) ?> — <?= sf_admin_h($episode['title'] ?? 'Episode') ?></option><?php endforeach; ?></select></label><label>Scene<select name="scene_id" onchange="this.form.submit()"><option value="0">Select scene</option><?php foreach ($scenes as $scene): ?><option value="<?= (int)$scene['id'] ?>"<?= (int)$scene['id'] === $sceneId ? ' selected' : '' ?>><?= sf_admin_h(sf_aipp_scene_title($scene)) ?> — <?= sf_admin_h(sf_aipp_scene_status($scene)) ?></option><?php endforeach; ?></select></label></div></form></section>
<?php if (!$selectedScene): ?><section class="sf-admin-panel"><p class="sf-pack-copy">No storyboard scene shell is available for this selection yet.</p></section><?php else: ?>
<section class="sf-pack-layout"><article class="sf-pack-panel"><span class="sf-panel-eyebrow">Handoff Packet</span><h2><?= sf_admin_h($packet['scene_title']) ?></h2><p class="sf-pack-copy"><strong>Season:</strong> <?= sf_admin_h($packet['season']) ?><br><strong>Episode:</strong> <?= sf_admin_h($packet['episode']) ?><br><strong>Status:</strong> <?= sf_admin_h($packet['status']) ?><br><strong>Tone:</strong> <?= sf_admin_h($packet['tone']) ?></p><p class="sf-pack-copy"><strong>Scene summary:</strong><br><?= sf_admin_h($packet['summary']) ?></p><span class="sf-pack-pill">Draft production pack</span></article><aside class="sf-pack-panel"><span class="sf-panel-eyebrow">Director Notes</span><p class="sf-pack-copy"><?= sf_admin_h($packet['director_notes']) ?></p><p class="sf-pack-copy"><strong>Setting:</strong><br><?= sf_admin_h($packet['setting']) ?></p></aside></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Production Checklist</span><h2><?= count($packet['tasks']) ?> handoff items</h2></div></div><div class="sf-pack-task-grid"><?php foreach ($packet['tasks'] as $task): ?><article class="sf-pack-task"><span class="sf-panel-eyebrow"><?= sf_admin_h($task['team']) ?></span><strong><?= sf_admin_h($task['task']) ?></strong><p class="sf-pack-copy">Priority: <?= sf_admin_h($task['priority']) ?></p></article><?php endforeach; ?></div></section>
<section class="sf-pack-layout"><article class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Visual Prompt Draft</span><h2>For later media review</h2></div></div><textarea readonly><?= sf_admin_h($packet['visual_prompt']) ?></textarea><p class="sf-pack-copy">This is a draft reference only. Media generation should remain behind review and approval.</p></article><aside class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Source Direction</span><h2>Scene source</h2></div></div><textarea readonly><?= sf_admin_h($packet['source']) ?></textarea></aside></section>
<?php endif; ?>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Safety</span><h2>Handoff-only controls</h2></div></div><p class="sf-pack-copy">This phase creates a reviewable production packet in the browser only. It does not save new records, publish scenes, generate images, queue notifications, assign tasks, or change review status.</p></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
