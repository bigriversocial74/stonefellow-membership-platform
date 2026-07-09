<?php
$pageTitle = 'AI Script Batch Scenes';
$pageDescription = 'Create multiple approved storyboard scene shells from one AI Script Producer chat prompt.';
$pageClass = 'membership-page admin-catalog-page storyboarding-system-page ai-script-batch-scenes-page';
require __DIR__ . '/../includes/storyboards.php';
require_once __DIR__ . '/../includes/storyboarding_system.php';
require_once __DIR__ . '/../includes/storyboard_character_actions.php';
require_once __DIR__ . '/../includes/admin_catalog.php';

function sf_aibs_text($value, string $fallback = 'Not set'): string { $text = trim((string)($value ?? '')); return $text === '' ? $fallback : $text; }
function sf_aibs_snip($value, int $length = 180, string $fallback = 'Not set'): string { $text = sf_aibs_text($value, $fallback); return strlen($text) > $length ? rtrim(substr($text, 0, $length - 1)) . '…' : $text; }
function sf_aibs_first_id(array $rows): int { foreach ($rows as $row) { $id = (int)($row['id'] ?? 0); if ($id > 0) return $id; } return 0; }
function sf_aibs_find(array $rows, int $id): array { foreach ($rows as $row) if ((int)($row['id'] ?? 0) === $id) return $row; return []; }
function sf_aibs_slug_title(string $prompt, string $fallback = 'AI Batch Scene'): string { if (preg_match('/\b(?:called|named|titled)\s+([^\,\.\n]{3,90})/i', $prompt, $m)) return trim($m[1]); return $fallback; }
function sf_aibs_scene_count(string $prompt): int { $lower = strtolower($prompt); if (preg_match('/\b([0-9]{1,2})\s+(?:scene|scenes|cards|clips|beats)\b/i', $prompt, $m)) return max(1, min(10, (int)$m[1])); foreach (['one'=>1,'two'=>2,'three'=>3,'four'=>4,'five'=>5,'six'=>6,'seven'=>7,'eight'=>8,'nine'=>9,'ten'=>10] as $word=>$num) if (preg_match('/\b' . $word . '\s+(?:scene|scenes|cards|clips|beats)\b/', $lower)) return $num; return 3; }
function sf_aibs_tone(string $prompt): string { $tone=[]; $lower=strtolower($prompt); foreach (['comedy','funny','dark','erotic','romantic','tension','dramatic','reality','scripted','cliffhanger','betrayal','pool','desert','night'] as $hint) if (strpos($lower,$hint)!==false) $tone[]=$hint; return $tone ? implode(', ', array_unique($tone)) : 'AI Script Producer batch scene'; }
function sf_aibs_mentions(string $prompt, array $rows): array { $out=[]; $lower=strtolower($prompt); foreach ($rows as $row) { $name=trim((string)($row['character_name'] ?? $row['name'] ?? '')); if ($name!=='' && strpos($lower,strtolower($name))!==false) $out[(int)($row['id'] ?? 0)]=$name; } return $out; }
function sf_aibs_context(array $request): array {
  $seasons = function_exists('sf_story_v1_seasons') ? sf_story_v1_seasons() : [];
  $allEpisodes = function_exists('sf_story_v1_episodes') ? sf_story_v1_episodes() : [];
  $seasonId = sf_admin_int($request['season_id'] ?? null, 0) ?? 0;
  if ($seasonId <= 0) $seasonId = (int)($allEpisodes[0]['story_season_id'] ?? sf_aibs_first_id($seasons));
  $selectedSeason = sf_aibs_find($seasons, $seasonId) ?: ($seasons[0] ?? []);
  $seasonId = (int)($selectedSeason['id'] ?? $seasonId);
  $episodes = $seasonId > 0 && function_exists('sf_story_v1_episodes') ? sf_story_v1_episodes($seasonId) : $allEpisodes;
  $episodeId = sf_admin_int($request['episode_id'] ?? null, 0) ?? 0;
  if ($episodeId <= 0 || !sf_aibs_find($episodes, $episodeId)) $episodeId = sf_aibs_first_id($episodes ?: $allEpisodes);
  $selectedEpisode = sf_aibs_find($episodes, $episodeId) ?: sf_aibs_find($allEpisodes, $episodeId) ?: ($episodes[0] ?? []);
  $episodeId = (int)($selectedEpisode['id'] ?? $episodeId);
  $scenes = $episodeId > 0 && function_exists('sf_story_v1_episode_storyboards') ? sf_story_v1_episode_storyboards($episodeId) : [];
  $characters = function_exists('sf_story_v1_characters') ? sf_story_v1_characters('active') : [];
  return compact('seasons','allEpisodes','seasonId','selectedSeason','episodes','episodeId','selectedEpisode','scenes','characters');
}
function sf_aibs_batch_plan(string $prompt, array $ctx): array {
  $count = sf_aibs_scene_count($prompt);
  $base = sf_aibs_slug_title($prompt, sf_aibs_text($ctx['selectedEpisode']['title'] ?? '', 'AI Batch Scene'));
  $characterMap = sf_aibs_mentions($prompt, $ctx['characters'] ?? []);
  $items = [];
  for ($i = 1; $i <= $count; $i++) {
    $items[] = [
      'number' => $i,
      'title' => $base . ' — Scene ' . str_pad((string)$i, 2, '0', STR_PAD_LEFT),
      'summary' => 'AI batch scene ' . $i . ' of ' . $count . ' for ' . sf_aibs_text($ctx['selectedEpisode']['title'] ?? '', 'the selected episode') . '.',
      'prompt' => trim($prompt . "\n\nScene " . $i . ' of ' . $count . ': create a distinct 30-second microdrama scene card with clear action, conflict, and a visual beat.'),
    ];
  }
  return ['count'=>$count,'base_title'=>$base,'characters'=>$characterMap,'items'=>$items,'tone'=>sf_aibs_tone($prompt)];
}
function sf_aibs_save_batch(string $prompt, array $ctx): array {
  if (!function_exists('sf_storyboard_ready') || !sf_storyboard_ready()) return ['ok'=>false,'message'=>'Storyboard SQL is required before creating batch scene cards.','created'=>[]];
  $episodeId = (int)($ctx['episodeId'] ?? 0); $seasonId = (int)($ctx['seasonId'] ?? 0);
  if ($episodeId <= 0) return ['ok'=>false,'message'=>'Select an episode before creating batch scene cards.','created'=>[]];
  $plan = sf_aibs_batch_plan($prompt, $ctx); $created=[]; $existingCount = count($ctx['scenes'] ?? []); $charIds = array_keys($plan['characters']);
  foreach ($plan['items'] as $index => $item) {
    $id = sf_storyboard_create_project(['title'=>$item['title'],'short_prompt'=>sf_aibs_snip($item['summary'], 220),'source_script'=>$item['prompt'],'genre'=>'AI Script Producer Batch Scene','tone'=>$plan['tone'],'visual_style'=>'Cinematic realistic','aspect_ratio'=>'16:9','scene_count'=>1,'default_text_provider'=>'chatgpt','default_image_provider'=>'chatgpt']);
    if ($id > 0) {
      $sets=[]; $params=[]; foreach (['story_season_id'=>$seasonId ?: null,'story_episode_id'=>$episodeId,'producer_scene_order'=>($existingCount + $index + 1) * 10,'producer_scene_status'=>'outline'] as $col=>$value) if (sf_admin_column_exists('storyboards',$col)) { $sets[]="$col = ?"; $params[]=$value; }
      if ($sets) { $sets[]='updated_at = NOW()'; $params[]=$id; sf_admin_execute('UPDATE storyboards SET ' . implode(', ', $sets) . ' WHERE id = ?', $params); }
      if ($charIds && function_exists('sf_sbc_sync_storyboard_catalog_characters')) sf_sbc_sync_storyboard_catalog_characters($id, $charIds);
      sf_admin_audit('ai_script_batch_create_scene', 'storyboard', $id, null, ['prompt'=>$prompt,'batch_number'=>$item['number'],'episode_id'=>$episodeId]);
      $created[] = $id;
    }
  }
  return ['ok'=>count($created)>0,'message'=>'Created ' . count($created) . ' of ' . (int)$plan['count'] . ' requested scene cards.','created'=>$created];
}

$request = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? $_POST : $_GET;
$ctx = sf_aibs_context($request);
extract($ctx);
$prompt = trim((string)($request['batch_prompt'] ?? ''));
$plan = $prompt !== '' ? sf_aibs_batch_plan($prompt, $ctx) : sf_aibs_batch_plan('Create three 30-second scene cards.', $ctx);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'approve_batch_create') {
  $result = sf_aibs_save_batch($prompt, $ctx);
  sf_admin_flash(!empty($result['ok']) ? 'success' : 'error', $result['message']);
  sf_admin_redirect(sf_url('admin/ai-script-batch-scenes.php?season_id=' . (int)$seasonId . '&episode_id=' . (int)$episodeId));
}

require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('AI Script Producer', 'Batch scene creation', 'Create multiple approved storyboard scene shells from one chat prompt.', 'ai-script-batch-scenes');
?>
<style>
.ai-script-batch-scenes-page .sf-batch-hero,.ai-script-batch-scenes-page .sf-batch-layout{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(320px,.8fr);gap:16px;margin-bottom:18px}.ai-script-batch-scenes-page .sf-batch-panel{padding:20px;border:1px solid rgba(232,198,127,.16);border-radius:22px;background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.025));box-shadow:0 20px 56px rgba(0,0,0,.22)}.ai-script-batch-scenes-page .sf-batch-panel h2{margin:8px 0;color:#fff;font-size:clamp(30px,4vw,56px);letter-spacing:-.05em;line-height:.98}.ai-script-batch-scenes-page .sf-batch-copy,.ai-script-batch-scenes-page .sf-batch-panel p{color:rgba(255,255,255,.68);line-height:1.55}.ai-script-batch-scenes-page textarea{width:100%;min-height:150px;resize:vertical}.ai-script-batch-scenes-page .sf-batch-context-grid,.ai-script-batch-scenes-page .sf-batch-card-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.ai-script-batch-scenes-page .sf-batch-scene-card{padding:15px;border:1px solid rgba(255,255,255,.08);border-radius:16px;background:rgba(0,0,0,.15)}.ai-script-batch-scenes-page .sf-batch-scene-card strong{display:block;color:#fff}.ai-script-batch-scenes-page .sf-batch-pill{display:inline-block;margin-top:8px;border:1px solid rgba(232,198,127,.18);border-radius:999px;padding:4px 8px;color:#f5d98d;font-size:11px;font-weight:900}@media(max-width:980px){.ai-script-batch-scenes-page .sf-batch-hero,.ai-script-batch-scenes-page .sf-batch-layout,.ai-script-batch-scenes-page .sf-batch-context-grid,.ai-script-batch-scenes-page .sf-batch-card-grid{grid-template-columns:1fr}}
</style>
<section class="sf-batch-hero"><div class="sf-batch-panel"><span class="sf-panel-eyebrow">Phase 7</span><h2>Batch scenes</h2><p>Create multiple approved storyboard scene shells from one chat prompt, including 30-second microdrama scene batches.</p></div><div class="sf-batch-panel"><span class="sf-panel-eyebrow">Guardrail</span><h2>Create only</h2><p>This creates outline scene shells only. It does not publish, delete, generate images, send messages, or overwrite existing scenes.</p></div></section>
<?php if (!function_exists('sf_storyboard_ready') || !sf_storyboard_ready()): ?><section class="sf-story-v1-warning"><strong>SQL required:</strong> Storyboard tables are required before batch scene cards can be saved.</section><?php endif; ?>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Context</span><h2>Choose target episode</h2></div><a href="<?= sf_url('admin/ai-script-assistant.php?season_id=' . (int)$seasonId . '&episode_id=' . (int)$episodeId) ?>">Back to AI Script Producer</a></div><form method="get" class="sf-admin-form"><div class="sf-batch-context-grid"><label>Season<select name="season_id" onchange="this.form.submit()"><option value="0">Select season</option><?php foreach ($seasons as $season): ?><option value="<?= (int)$season['id'] ?>"<?= (int)$season['id'] === $seasonId ? ' selected' : '' ?>><?= sf_admin_h($season['title'] ?? 'Season') ?></option><?php endforeach; ?></select></label><label>Episode<select name="episode_id" onchange="this.form.submit()"><option value="0">Select episode</option><?php foreach ($episodes as $episodeRow): ?><option value="<?= (int)$episodeRow['id'] ?>"<?= (int)$episodeRow['id'] === $episodeId ? ' selected' : '' ?>>Episode <?= (int)($episodeRow['episode_number'] ?? 1) ?> — <?= sf_admin_h($episodeRow['title'] ?? 'Episode') ?></option><?php endforeach; ?></select></label><label>Existing scenes<input value="<?= count($scenes) ?> selected episode scenes" readonly></label></div></form></section>
<section class="sf-batch-layout"><div class="sf-batch-panel"><span class="sf-panel-eyebrow">Chat Prompt</span><form method="post" class="sf-admin-form"><?= sf_csrf_field() ?><input type="hidden" name="season_id" value="<?= (int)$seasonId ?>"><input type="hidden" name="episode_id" value="<?= (int)$episodeId ?>"><label>Batch scene request<textarea name="batch_prompt" placeholder="Example: Create five 30-second microdrama scenes where Rio lies about the record deal, Mia finds the text, Jax watches from the balcony, and each scene ends with a stronger cliffhanger."><?= sf_admin_h($prompt) ?></textarea></label><div class="sf-admin-form-actions"><button type="submit">Generate Batch Plan</button></div></form></div><aside class="sf-batch-panel"><span class="sf-panel-eyebrow">Plan Summary</span><p class="sf-batch-copy"><strong>Episode:</strong> <?= sf_admin_h(sf_aibs_text($selectedEpisode['title'] ?? '', 'No episode')) ?><br><strong>Requested scenes:</strong> <?= (int)$plan['count'] ?><br><strong>Tone hints:</strong> <?= sf_admin_h($plan['tone']) ?></p><p><span class="sf-batch-pill">Characters detected: <?= count($plan['characters']) ?></span></p></aside></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Batch Plan</span><h2><?= (int)$plan['count'] ?> scene shells ready for review</h2></div></div><div class="sf-batch-card-grid"><?php foreach ($plan['items'] as $item): ?><article class="sf-batch-scene-card"><span class="sf-panel-eyebrow">Scene <?= (int)$item['number'] ?></span><strong><?= sf_admin_h($item['title']) ?></strong><p class="sf-batch-copy"><?= sf_admin_h($item['summary']) ?></p><small><?= sf_admin_h(sf_aibs_snip($item['prompt'], 130)) ?></small></article><?php endforeach; ?></div><?php if ($prompt !== ''): ?><form method="post" class="sf-admin-form"><?= sf_csrf_field() ?><input type="hidden" name="action" value="approve_batch_create"><input type="hidden" name="season_id" value="<?= (int)$seasonId ?>"><input type="hidden" name="episode_id" value="<?= (int)$episodeId ?>"><input type="hidden" name="batch_prompt" value="<?= sf_admin_h($prompt) ?>"><div class="sf-admin-form-actions"><button type="submit"<?= ($episodeId > 0 && function_exists('sf_storyboard_ready') && sf_storyboard_ready()) ? '' : ' disabled' ?>>Approve & Create Batch</button></div></form><?php endif; ?></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Safety</span><h2>What this phase does not do</h2></div></div><p class="sf-batch-copy">Batch creation only adds new outline storyboard scene shells to the selected episode. It does not publish them, generate media, notify the team, or overwrite existing scene cards.</p></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
