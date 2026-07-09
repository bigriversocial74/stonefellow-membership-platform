<?php
$pageTitle = 'AI Script Team Briefing';
$pageDescription = 'Draft team updates and production task briefings from AI Script Producer context.';
$pageClass = 'membership-page admin-catalog-page ai-script-briefing-page';
require __DIR__ . '/../includes/storyboards.php';
require_once __DIR__ . '/../includes/storyboarding_system.php';
require_once __DIR__ . '/../includes/story_scene_backgrounds.php';
require_once __DIR__ . '/../includes/story_series_assets.php';
require_once __DIR__ . '/../includes/admin_catalog.php';

function sf_aisb_text($value, string $fallback = 'Not set'): string { $text = trim((string)($value ?? '')); return $text === '' ? $fallback : $text; }
function sf_aisb_snip($value, int $length = 180, string $fallback = 'Not set'): string { $text = sf_aisb_text($value, $fallback); return strlen($text) > $length ? rtrim(substr($text, 0, $length - 1)) . '…' : $text; }
function sf_aisb_first_id(array $rows): int { foreach ($rows as $row) { $id = (int)($row['id'] ?? 0); if ($id > 0) return $id; } return 0; }
function sf_aisb_find(array $rows, int $id): array { foreach ($rows as $row) if ((int)($row['id'] ?? 0) === $id) return $row; return []; }
function sf_aisb_line(string $owner, string $task, string $priority = 'Normal'): array { return ['owner'=>$owner, 'task'=>$task, 'priority'=>$priority]; }
function sf_aisb_context(array $request): array {
  $seasons = function_exists('sf_story_v1_seasons') ? sf_story_v1_seasons() : [];
  $allEpisodes = function_exists('sf_story_v1_episodes') ? sf_story_v1_episodes() : [];
  $seasonId = sf_admin_int($request['season_id'] ?? null, 0) ?? 0;
  if ($seasonId <= 0) $seasonId = (int)($allEpisodes[0]['story_season_id'] ?? sf_aisb_first_id($seasons));
  $selectedSeason = sf_aisb_find($seasons, $seasonId) ?: ($seasons[0] ?? []);
  $seasonId = (int)($selectedSeason['id'] ?? $seasonId);
  $episodes = $seasonId > 0 && function_exists('sf_story_v1_episodes') ? sf_story_v1_episodes($seasonId) : $allEpisodes;
  $episodeId = sf_admin_int($request['episode_id'] ?? null, 0) ?? 0;
  if ($episodeId <= 0 || !sf_aisb_find($episodes, $episodeId)) $episodeId = sf_aisb_first_id($episodes ?: $allEpisodes);
  $selectedEpisode = sf_aisb_find($episodes, $episodeId) ?: sf_aisb_find($allEpisodes, $episodeId) ?: ($episodes[0] ?? []);
  $episodeId = (int)($selectedEpisode['id'] ?? $episodeId);
  $scenes = $episodeId > 0 && function_exists('sf_story_v1_episode_storyboards') ? sf_story_v1_episode_storyboards($episodeId) : [];
  $sceneId = sf_admin_int($request['scene_id'] ?? null, 0) ?? 0;
  if ($sceneId <= 0 || !sf_aisb_find($scenes, $sceneId)) $sceneId = sf_aisb_first_id($scenes);
  $selectedScene = sf_aisb_find($scenes, $sceneId) ?: ($scenes[0] ?? []);
  $characters = function_exists('sf_story_v1_characters') ? sf_story_v1_characters('active') : [];
  $backgrounds = function_exists('sf_scene_backgrounds_all') ? sf_scene_backgrounds_all('active') : [];
  $assets = function_exists('sf_series_assets_all') ? sf_series_assets_all('active') : [];
  return compact('seasons','allEpisodes','seasonId','selectedSeason','episodes','episodeId','selectedEpisode','scenes','sceneId','selectedScene','characters','backgrounds','assets');
}
function sf_aisb_mentions(string $prompt, array $rows, array $keys): array {
  $out = [];
  $lower = strtolower($prompt);
  foreach ($rows as $row) {
    foreach ($keys as $key) {
      $name = trim((string)($row[$key] ?? ''));
      if ($name !== '' && strpos($lower, strtolower($name)) !== false) { $out[] = $name; break; }
    }
  }
  return array_values(array_unique($out));
}
function sf_aisb_build_brief(string $prompt, array $ctx): array {
  $season = sf_aisb_text($ctx['selectedSeason']['title'] ?? '', 'No season selected');
  $episode = sf_aisb_text($ctx['selectedEpisode']['title'] ?? '', 'No episode selected');
  $scene = sf_aisb_text($ctx['selectedScene']['title'] ?? '', 'No scene selected');
  $charNames = sf_aisb_mentions($prompt, $ctx['characters'] ?? [], ['character_name','name']);
  $bgNames = sf_aisb_mentions($prompt, $ctx['backgrounds'] ?? [], ['background_name','location_label','name']);
  $assetNames = sf_aisb_mentions($prompt, $ctx['assets'] ?? [], ['asset_name','name']);
  $lower = strtolower($prompt);
  $tasks = [];
  $tasks[] = sf_aisb_line('Writing', 'Review the selected scene/episode direction and tighten story beats for ' . $episode . '.', 'High');
  if (strpos($lower, 'dialog') !== false || strpos($lower, 'argue') !== false) $tasks[] = sf_aisb_line('Writing', 'Polish dialogue and make the emotional turn clear.', 'High');
  if ($charNames) $tasks[] = sf_aisb_line('Continuity', 'Confirm character continuity for: ' . implode(', ', $charNames) . '.', 'High');
  else $tasks[] = sf_aisb_line('Continuity', 'Confirm which characters are required for the selected scene.', 'Normal');
  if ($bgNames) $tasks[] = sf_aisb_line('Art / Locations', 'Verify selected background/location: ' . implode(', ', $bgNames) . '.', 'Normal');
  elseif (strpos($lower, 'background') !== false || strpos($lower, 'location') !== false || strpos($lower, 'pool') !== false || strpos($lower, 'desert') !== false) $tasks[] = sf_aisb_line('Art / Locations', 'Create or select a matching scene background/location reference.', 'High');
  if ($assetNames) $tasks[] = sf_aisb_line('Props / Assets', 'Confirm asset continuity for: ' . implode(', ', $assetNames) . '.', 'Normal');
  elseif (strpos($lower, 'asset') !== false || strpos($lower, 'prop') !== false || strpos($lower, 'car') !== false || strpos($lower, 'truck') !== false) $tasks[] = sf_aisb_line('Props / Assets', 'Create or select the recurring prop/asset requested in the prompt.', 'Normal');
  if (strpos($lower, 'cliffhanger') !== false) $tasks[] = sf_aisb_line('Producer', 'Confirm the cliffhanger lands cleanly and tees up the next scene.', 'High');
  $subject = 'AI Script Producer Update — ' . $episode;
  $body = "Team update for {$season} / {$episode}\n\nFocus scene: {$scene}\n\nProducer direction:\n" . ($prompt !== '' ? $prompt : 'No custom producer prompt entered yet.') . "\n\nWhat needs attention:\n";
  foreach ($tasks as $task) $body .= '- [' . $task['priority'] . '] ' . $task['owner'] . ': ' . $task['task'] . "\n";
  $body .= "\nStatus: Draft only. Review before sending or assigning work.\n";
  return ['subject'=>$subject,'body'=>$body,'tasks'=>$tasks,'characters'=>$charNames,'backgrounds'=>$bgNames,'assets'=>$assetNames];
}

$request = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? $_POST : $_GET;
$ctx = sf_aisb_context($request);
extract($ctx);
$prompt = trim((string)($request['brief_prompt'] ?? ''));
$brief = sf_aisb_build_brief($prompt, $ctx);

require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('AI Script Producer', 'Team briefing drafts', 'Draft production tasks and notification copy from the selected script context. Nothing is sent from this page.', 'ai-script-assistant');
?>
<style>
.ai-script-briefing-page .sf-brief-hero,.ai-script-briefing-page .sf-brief-layout{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(320px,.8fr);gap:16px;margin-bottom:18px}.ai-script-briefing-page .sf-brief-panel{padding:20px;border:1px solid rgba(232,198,127,.16);border-radius:22px;background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.025));box-shadow:0 20px 56px rgba(0,0,0,.22)}.ai-script-briefing-page .sf-brief-panel h2{margin:8px 0;color:#fff;font-size:clamp(30px,4vw,56px);letter-spacing:-.05em;line-height:.98}.ai-script-briefing-page .sf-brief-copy,.ai-script-briefing-page .sf-brief-panel p{color:rgba(255,255,255,.68);line-height:1.55}.ai-script-briefing-page .sf-brief-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.ai-script-briefing-page .sf-brief-task{padding:14px;border:1px solid rgba(255,255,255,.08);border-radius:16px;background:rgba(0,0,0,.15)}.ai-script-briefing-page .sf-brief-task strong{display:block;color:#fff}.ai-script-briefing-page .sf-brief-task small,.ai-script-briefing-page .sf-brief-pill{display:inline-block;margin-top:8px;border:1px solid rgba(232,198,127,.18);border-radius:999px;padding:4px 8px;color:#f5d98d;font-size:11px;font-weight:900}.ai-script-briefing-page textarea{width:100%;min-height:160px;resize:vertical}.ai-script-briefing-page .sf-brief-context-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.ai-script-briefing-page .sf-brief-context-grid select{width:100%}@media(max-width:980px){.ai-script-briefing-page .sf-brief-hero,.ai-script-briefing-page .sf-brief-layout,.ai-script-briefing-page .sf-brief-grid,.ai-script-briefing-page .sf-brief-context-grid{grid-template-columns:1fr}}
</style>
<section class="sf-brief-hero">
  <div class="sf-brief-panel"><span class="sf-panel-eyebrow">Phase 5</span><h2>Team briefing drafts</h2><p>Turn the selected season, episode, scene, and producer prompt into a reviewable task list and notification draft.</p></div>
  <div class="sf-brief-panel"><span class="sf-panel-eyebrow">Guardrail</span><h2>No send</h2><p>This page does not send messages, queue campaigns, publish content, or assign work automatically. It prepares copy and task direction for review.</p></div>
</section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Context</span><h2>Choose script context</h2></div><a href="<?= sf_url('admin/ai-script-assistant.php?season_id=' . (int)$seasonId . '&episode_id=' . (int)$episodeId . '&scene_id=' . (int)$sceneId) ?>">Back to AI Script Producer</a></div><form method="get" class="sf-admin-form"><div class="sf-brief-context-grid"><label>Season<select name="season_id" onchange="this.form.submit()"><option value="0">Select season</option><?php foreach ($seasons as $season): ?><option value="<?= (int)$season['id'] ?>"<?= (int)$season['id'] === $seasonId ? ' selected' : '' ?>><?= sf_admin_h($season['title'] ?? 'Season') ?></option><?php endforeach; ?></select></label><label>Episode<select name="episode_id" onchange="this.form.submit()"><option value="0">Select episode</option><?php foreach ($episodes as $episodeRow): ?><option value="<?= (int)$episodeRow['id'] ?>"<?= (int)$episodeRow['id'] === $episodeId ? ' selected' : '' ?>>Episode <?= (int)($episodeRow['episode_number'] ?? 1) ?> — <?= sf_admin_h($episodeRow['title'] ?? 'Episode') ?></option><?php endforeach; ?></select></label><label>Scene<select name="scene_id" onchange="this.form.submit()"><option value="0">Select scene</option><?php foreach ($scenes as $sceneRow): ?><option value="<?= (int)$sceneRow['id'] ?>"<?= (int)$sceneRow['id'] === $sceneId ? ' selected' : '' ?>><?= sf_admin_h($sceneRow['title'] ?? ('Scene #' . (int)$sceneRow['id'])) ?></option><?php endforeach; ?></select></label></div></form></section>
<section class="sf-brief-layout"><div class="sf-brief-panel"><span class="sf-panel-eyebrow">Producer prompt</span><form class="sf-admin-form" method="post"><?= sf_csrf_field() ?><input type="hidden" name="season_id" value="<?= (int)$seasonId ?>"><input type="hidden" name="episode_id" value="<?= (int)$episodeId ?>"><input type="hidden" name="scene_id" value="<?= (int)$sceneId ?>"><label>What should the team know?<textarea name="brief_prompt" placeholder="Example: Let the team know Scene 4 now happens at the pool at night, Rio and Mia are added, the cliffhanger is the text message, and art needs a neon blue pool background."><?= sf_admin_h($prompt) ?></textarea></label><div class="sf-admin-form-actions"><button type="submit">Generate Team Brief</button></div></form></div><aside class="sf-brief-panel"><span class="sf-panel-eyebrow">Selected context</span><p class="sf-brief-copy"><strong>Season:</strong> <?= sf_admin_h(sf_aisb_text($selectedSeason['title'] ?? '')) ?><br><strong>Episode:</strong> <?= sf_admin_h(sf_aisb_text($selectedEpisode['title'] ?? '')) ?><br><strong>Scene:</strong> <?= sf_admin_h(sf_aisb_text($selectedScene['title'] ?? '')) ?></p><p class="sf-brief-copy"><strong>Detected:</strong><br><span class="sf-brief-pill">Characters: <?= count($brief['characters']) ?></span> <span class="sf-brief-pill">Backgrounds: <?= count($brief['backgrounds']) ?></span> <span class="sf-brief-pill">Assets: <?= count($brief['assets']) ?></span></p></aside></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Production Tasks</span><h2><?= count($brief['tasks']) ?> draft tasks</h2></div></div><div class="sf-brief-grid"><?php foreach ($brief['tasks'] as $task): ?><article class="sf-brief-task"><span class="sf-panel-eyebrow"><?= sf_admin_h($task['owner']) ?></span><strong><?= sf_admin_h($task['task']) ?></strong><small><?= sf_admin_h($task['priority']) ?></small></article><?php endforeach; ?></div></section>
<section class="sf-brief-layout"><article class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Notification Draft</span><h2><?= sf_admin_h($brief['subject']) ?></h2></div><a href="<?= sf_url('admin/member-messaging.php') ?>">Open Messaging</a></div><textarea readonly rows="14"><?= sf_admin_h($brief['body']) ?></textarea><p class="sf-brief-copy">Copy this into messaging or team tools after review. A future phase can save this as a draft campaign or route it to internal team notifications.</p></article><aside class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Next Automation Step</span><h2>Human approval first</h2></div></div><div class="sf-brief-task"><strong>Draft → Review → Send</strong><p class="sf-brief-copy">The AI can prepare the message. Sending should stay locked until role permissions, audit history, and delivery approval are added.</p></div><div class="sf-brief-task"><strong>Future controls</strong><p class="sf-brief-copy">Save draft campaign, assign internal tasks, notify only selected team roles, and log every AI-generated notification.</p></div></aside></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
