<?php
require_once __DIR__ . '/../includes/storyboarding_system.php';
require_once __DIR__ . '/../includes/storyboard_generation.php';

$user = sf_auth_user();
if (!$user || (($user['role'] ?? '') !== 'admin' && sf_current_access_level() !== 'admin')) sf_json_response(['ok'=>false,'error'=>'admin_required'],403);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') sf_json_response(['ok'=>false,'error'=>'post_required'],405);
if (!sf_verify_csrf($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null))) sf_json_response(['ok'=>false,'error'=>'csrf_failed'],403);
if (!sf_story_v1_ready() || !sf_admin_column_exists('story_episodes', 'ai_outline_result_json')) sf_json_response(['ok'=>false,'error'=>'story_episode_bridge_missing','message'=>'Import database/storyboarding_season_episode_bridge_v1.sql first.'],503);

$episodeId = (int)($_POST['episode_id'] ?? 0);
$returnUrl = trim((string)($_POST['return_url'] ?? sf_url('admin/storyboards.php')));
$providerKey = trim((string)($_POST['provider_key'] ?? ''));
$customPrompt = trim((string)($_POST['prompt'] ?? ''));
$episode = null;
foreach (sf_story_v1_episodes() as $row) if ((int)$row['id'] === $episodeId) { $episode = $row; break; }
if (!$episode) {
  sf_admin_flash('error', 'Episode not found.');
  header('Location: ' . $returnUrl); exit;
}

$provider = $providerKey !== '' ? sf_ai_provider($providerKey) : sf_sbgen_default_provider(null);
if (!$provider) {
  sf_admin_flash('error', 'No AI provider is configured.');
  header('Location: ' . $returnUrl); exit;
}

$scenes = sf_story_v1_episode_storyboards($episodeId);
$characters = sf_story_v1_episode_characters($episodeId);
$sceneLines = [];
foreach ($scenes as $i => $scene) $sceneLines[] = ($i + 1) . '. ' . (string)($scene['title'] ?? 'Untitled Scene') . ' — ' . (string)($scene['prompt'] ?? $scene['genre'] ?? '');
$characterLines = [];
foreach ($characters as $character) $characterLines[] = (string)($character['character_name'] ?? 'Character') . ' (' . (string)($character['role_type'] ?? 'character') . ')';

$systemPrompt = 'You are a professional television showrunner and producer. Return JSON only. Create a useful episode outline for a scripted/unscripted music-drama/comedy production workflow. Shape: {"episode_title":"","logline":"","outline":"","setting":"","main_characters":[""],"scene_plan":[{"scene_title":"","purpose":"","notes":""}],"producer_notes":""}. Keep the result concise, production-friendly, and based only on the supplied season, episode, scene, and character context.';
$userPrompt = "Season: " . (string)($episode['season_title'] ?? 'Season 1') . "\n" .
  "Episode: " . (string)($episode['title'] ?? 'Episode 1') . "\n" .
  "Current logline: " . (string)($episode['logline'] ?? '') . "\n" .
  "Current outline: " . sf_story_v1_episode_outline_text($episode) . "\n" .
  "Optional setting: " . (string)($episode['setting_label'] ?? '') . "\n" .
  "Main characters: " . implode(', ', $characterLines) . "\n" .
  "Current scene/storyboard rows assigned to this episode:\n" . implode("\n", $sceneLines) . "\n" .
  ($customPrompt !== '' ? "Producer instruction: {$customPrompt}\n" : '') .
  "Return the JSON object only.";

$result = sf_sbgen_call_provider($provider, $systemPrompt, $userPrompt);
$providerKeyUsed = (string)($provider['provider_key'] ?? $providerKey ?: 'unknown');
if (empty($result['ok'])) {
  sf_admin_execute('UPDATE story_episodes SET ai_outline_status = ?, ai_outline_provider = ?, ai_outline_prompt = ?, ai_outline_result_json = ?, updated_at = NOW() WHERE id = ?', ['failed', $providerKeyUsed, $userPrompt, json_encode(['error'=>$result['error'] ?? 'provider_error'], JSON_UNESCAPED_SLASHES), $episodeId]);
  sf_admin_flash('error', 'Episode outline generation failed: ' . ($result['error'] ?? 'provider_error'));
  header('Location: ' . $returnUrl); exit;
}

$text = trim((string)($result['text'] ?? ''));
$decoded = json_decode($text, true);
if (!is_array($decoded)) {
  $start = strpos($text, '{'); $end = strrpos($text, '}');
  if ($start !== false && $end !== false && $end > $start) $decoded = json_decode(substr($text, $start, $end - $start + 1), true);
}
$output = is_array($decoded) ? $decoded : ['outline'=>$text];
$outline = trim((string)($output['outline'] ?? $text));
$logline = trim((string)($output['logline'] ?? ''));
$setting = trim((string)($output['setting'] ?? ''));

$fields = [
  'ai_outline_status' => 'complete',
  'ai_outline_provider' => $providerKeyUsed,
  'ai_outline_prompt' => $userPrompt,
  'ai_outline_result_json' => json_encode($output, JSON_UNESCAPED_SLASHES),
  'ai_outline_generated_at' => date('Y-m-d H:i:s'),
];
if ($outline !== '') $fields['episode_outline'] = $outline;
if ($logline !== '') $fields['logline'] = $logline;
if ($setting !== '') $fields['setting_label'] = $setting;
$sets = []; $values = [];
foreach ($fields as $key => $value) { $sets[] = '`' . $key . '` = ?'; $values[] = $value; }
$values[] = $episodeId;
sf_admin_execute('UPDATE story_episodes SET ' . implode(', ', $sets) . ', updated_at = NOW() WHERE id = ?', $values);

if (sf_admin_table_exists('ai_usage_events')) {
  $usage = $result['usage'] ?? [];
  $promptTokens = (int)($usage['input_tokens'] ?? $usage['prompt_tokens'] ?? 0);
  $completionTokens = (int)($usage['output_tokens'] ?? $usage['completion_tokens'] ?? 0);
  sf_admin_execute('INSERT INTO ai_usage_events (provider_key, feature_key, related_type, related_id, model_key, request_type, prompt_tokens, completion_tokens, image_count, estimated_cost_cents, request_status, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?, ?)', [$providerKeyUsed, 'story_episode_outline', 'story_episode', $episodeId, (string)($provider['default_model'] ?? ''), 'text', $promptTokens, $completionTokens, 'success', sf_current_user_id()]);
}

sf_admin_flash('success', 'Episode outline generated and saved.');
header('Location: ' . $returnUrl); exit;
?>