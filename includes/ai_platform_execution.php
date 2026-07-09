<?php
require_once __DIR__ . '/storyboards.php';
require_once __DIR__ . '/storyboarding_system.php';
require_once __DIR__ . '/storyboard_character_actions.php';
require_once __DIR__ . '/story_scene_backgrounds.php';
require_once __DIR__ . '/ops_scheduler_messaging.php';

function sf_ai_exec_registry_ready(): bool { return sf_admin_table_exists('ai_platform_actions'); }
function sf_ai_exec_log_ready(): bool { return sf_admin_table_exists('ai_platform_action_executions'); }
function sf_ai_exec_user_id(): ?int { return function_exists('sf_current_user_id') ? sf_current_user_id() : null; }
function sf_ai_exec_routes(): array {
  return [
    'review' => ['label'=>'Complete Review Action','description'=>'Marks an approved review action executed after the admin confirms it. No platform target data is changed.','side_effect'=>'registry_only'],
    'queue_media_generation' => ['label'=>'Queue Media Generation','description'=>'Creates one generation queue record from an approved media prompt. No provider is called.','side_effect'=>'queue_record'],
    'block_media_generation' => ['label'=>'Block Media Generation Job','description'=>'Marks one generation queue record blocked for review.','side_effect'=>'status_update'],
    'create_story_scene_shell' => ['label'=>'Create Story Scene Shell','description'=>'Creates one approved storyboard scene shell tied to a story episode. No publishing or media generation.','side_effect'=>'story_create'],
    'update_scene_status' => ['label'=>'Update Scene Status','description'=>'Updates a storyboard shell or internal scene to a safe production status. Published/archived statuses are blocked.','side_effect'=>'status_update'],
    'mark_scene_needs_review' => ['label'=>'Mark Scene Needs Review','description'=>'Sets a scene shell or internal frame to needs_review.','side_effect'=>'status_update'],
    'mark_scene_ready' => ['label'=>'Mark Scene Ready','description'=>'Sets a scene shell or internal frame to ready. This does not publish the scene.','side_effect'=>'status_update'],
    'assign_scene_character' => ['label'=>'Assign Scene Character','description'=>'Syncs an approved story character to a storyboard and optionally assigns it to an internal scene frame.','side_effect'=>'continuity_update'],
    'assign_scene_background' => ['label'=>'Assign Scene Background','description'=>'Assigns an approved background to an internal storyboard scene frame.','side_effect'=>'continuity_update'],
    'queue_media_prompt' => ['label'=>'Queue Media Prompt','description'=>'Saves one approved media-prep prompt record from an approved action. No provider is called.','side_effect'=>'queue_record'],
    'create_team_briefing_draft' => ['label'=>'Create Team Briefing Draft','description'=>'Creates a draft member-message campaign for production briefing review. It does not send or queue recipients.','side_effect'=>'draft_record'],
  ];
}
function sf_ai_exec_payload(array $action): array { $raw = trim((string)($action['payload_json'] ?? '')); if ($raw === '') return []; $data = json_decode($raw, true); return is_array($data) ? $data : []; }
function sf_ai_exec_text(array $payload, string $key, string $fallback = ''): string { $value = trim((string)($payload[$key] ?? '')); return $value === '' ? $fallback : $value; }
function sf_ai_exec_safe_status(string $status): string { $status = trim($status); return in_array($status, ['draft','outline','in_progress','needs_review','ready'], true) ? $status : 'needs_review'; }
function sf_ai_exec_array_ints($value): array { return array_values(array_unique(array_filter(array_map('intval', is_array($value) ? $value : [$value])))); }
function sf_ai_exec_first_frame_scene_id(int $storyboardId): int { if (!sf_admin_table_exists('storyboard_scenes') || $storyboardId <= 0) return 0; $row = sf_admin_fetch_one('SELECT id FROM storyboard_scenes WHERE storyboard_id = ? ORDER BY scene_number ASC, id ASC LIMIT 1', [$storyboardId]); return (int)($row['id'] ?? 0); }
function sf_ai_exec_storyboard_status_column(): string { foreach (['producer_scene_status','storyboard_status','generation_status'] as $col) if (sf_admin_column_exists('storyboards', $col)) return $col; return ''; }
function sf_ai_exec_log(int $actionId, string $route, string $status, string $message, array $result = []): void {
  if (!sf_ai_exec_log_ready()) return;
  sf_admin_execute('INSERT INTO ai_platform_action_executions (platform_action_id, route_key, execution_status, result_message, result_json, executed_by_user_id, completed_at) VALUES (?, ?, ?, ?, ?, ?, CASE WHEN ? IN (\'completed\',\'failed\',\'blocked\') THEN NOW() ELSE NULL END)', [$actionId, $route, $status, $message, json_encode($result, JSON_UNESCAPED_SLASHES), sf_ai_exec_user_id(), $status]);
}
function sf_ai_exec_mark_action(int $actionId, string $status, string $message): void {
  $execution = $status === 'completed' ? 'executed' : ($status === 'blocked' ? 'cancelled' : 'failed');
  sf_admin_execute('UPDATE ai_platform_actions SET execution_status = ?, approval_status = CASE WHEN ? = \'cancelled\' THEN \'blocked\' ELSE approval_status END, executed_by_user_id = ?, executed_at = CASE WHEN ? = \'executed\' THEN NOW() ELSE executed_at END WHERE id = ?', [$execution, $execution, sf_ai_exec_user_id(), $execution, $actionId]);
  sf_admin_audit('ai_platform_action_execution_' . $status, 'ai_platform_action', $actionId, null, ['message'=>$message]);
}
function sf_ai_exec_fail(int $actionId, string $route, string $status, string $message, array $result = []): array {
  sf_ai_exec_log($actionId, $route ?: 'unknown', $status, $message, $result);
  sf_ai_exec_mark_action($actionId, $status === 'blocked' ? 'blocked' : 'failed', $message);
  return ['ok'=>false,'status'=>$status,'message'=>$message];
}
function sf_ai_exec_complete(int $actionId, string $route, string $message, array $result = []): array {
  sf_ai_exec_log($actionId, $route, 'completed', $message, $result);
  sf_ai_exec_mark_action($actionId, 'completed', $message);
  return ['ok'=>true,'status'=>'completed','message'=>$message] + $result;
}
function sf_ai_exec_route_update_scene_status(int $actionId, string $route, array $payload, string $defaultStatus = ''): array {
  $status = sf_ai_exec_safe_status($defaultStatus !== '' ? $defaultStatus : sf_ai_exec_text($payload, 'status', 'needs_review'));
  $sceneId = (int)($payload['scene_id'] ?? 0);
  $storyboardId = (int)($payload['storyboard_id'] ?? 0);
  if ($sceneId > 0 && sf_admin_table_exists('storyboard_scenes')) {
    $before = sf_admin_fetch_one('SELECT * FROM storyboard_scenes WHERE id = ? LIMIT 1', [$sceneId]);
    if (!$before) return sf_ai_exec_fail($actionId, $route, 'failed', 'Internal storyboard scene was not found.');
    $ok = sf_admin_execute('UPDATE storyboard_scenes SET scene_status = ?, updated_at = NOW() WHERE id = ?', [$status, $sceneId]);
    if ($ok) {
      $storyboardId = (int)($before['storyboard_id'] ?? 0);
      if ($storyboardId > 0) sf_admin_execute('UPDATE storyboards SET updated_at = NOW() WHERE id = ?', [$storyboardId]);
      $after = sf_admin_fetch_one('SELECT * FROM storyboard_scenes WHERE id = ? LIMIT 1', [$sceneId]);
      sf_admin_audit('ai_router_update_scene_status', 'storyboard_scene', $sceneId, $before, $after);
    }
    return $ok ? sf_ai_exec_complete($actionId, $route, 'Internal scene status updated to ' . $status . '.', ['scene_id'=>$sceneId,'status'=>$status]) : sf_ai_exec_fail($actionId, $route, 'failed', 'Internal scene status could not be updated.');
  }
  if ($storyboardId > 0 && sf_admin_table_exists('storyboards')) {
    $statusCol = sf_ai_exec_storyboard_status_column();
    if ($statusCol === '') return sf_ai_exec_fail($actionId, $route, 'failed', 'No supported storyboard status column exists.');
    $before = sf_admin_fetch_one('SELECT * FROM storyboards WHERE id = ? LIMIT 1', [$storyboardId]);
    if (!$before) return sf_ai_exec_fail($actionId, $route, 'failed', 'Storyboard scene shell was not found.');
    $ok = sf_admin_execute('UPDATE storyboards SET `' . $statusCol . '` = ?, updated_at = NOW() WHERE id = ?', [$status, $storyboardId]);
    $after = sf_admin_fetch_one('SELECT * FROM storyboards WHERE id = ? LIMIT 1', [$storyboardId]);
    if ($ok) sf_admin_audit('ai_router_update_storyboard_status', 'storyboard', $storyboardId, $before, $after);
    return $ok ? sf_ai_exec_complete($actionId, $route, 'Storyboard scene shell status updated to ' . $status . '.', ['storyboard_id'=>$storyboardId,'status'=>$status]) : sf_ai_exec_fail($actionId, $route, 'failed', 'Storyboard scene shell status could not be updated.');
  }
  return sf_ai_exec_fail($actionId, $route, 'failed', 'Provide scene_id or storyboard_id.');
}
function sf_ai_exec_route_create_story_scene_shell(int $actionId, string $route, array $payload): array {
  if (!sf_storyboard_ready()) return sf_ai_exec_fail($actionId, $route, 'failed', 'Storyboard SQL is not ready.');
  $episodeId = (int)($payload['story_episode_id'] ?? $payload['episode_id'] ?? 0);
  if ($episodeId <= 0 || !sf_admin_column_exists('storyboards', 'story_episode_id')) return sf_ai_exec_fail($actionId, $route, 'failed', 'A story_episode_id and storyboard episode bridge are required.');
  $seasonId = (int)($payload['story_season_id'] ?? $payload['season_id'] ?? 0);
  $title = sf_ai_exec_text($payload, 'title', 'AI Scene Shell');
  $prompt = sf_ai_exec_text($payload, 'source_script', sf_ai_exec_text($payload, 'prompt', 'Approved AI-created scene shell.'));
  $short = sf_ai_exec_text($payload, 'short_prompt', substr($prompt, 0, 220));
  $projectId = sf_storyboard_create_project(['title'=>$title,'short_prompt'=>$short,'source_script'=>$prompt,'genre'=>sf_ai_exec_text($payload, 'genre', 'AI Routed Scene'),'tone'=>sf_ai_exec_text($payload, 'tone', 'AI Platform routed draft'),'visual_style'=>sf_ai_exec_text($payload, 'visual_style', 'Cinematic realistic'),'aspect_ratio'=>sf_ai_exec_text($payload, 'aspect_ratio', '16:9'),'scene_count'=>1,'default_text_provider'=>sf_ai_exec_text($payload, 'default_text_provider', 'chatgpt'),'default_image_provider'=>sf_ai_exec_text($payload, 'default_image_provider', 'chatgpt')]);
  if ($projectId <= 0) return sf_ai_exec_fail($actionId, $route, 'failed', 'Scene shell could not be created.');
  $sets = []; $params = [];
  foreach (['story_season_id'=>$seasonId ?: null,'story_episode_id'=>$episodeId,'producer_scene_order'=>$projectId * 10,'producer_scene_status'=>'outline'] as $col => $value) if (sf_admin_column_exists('storyboards', $col)) { $sets[] = '`' . $col . '` = ?'; $params[] = $value; }
  if ($sets) { $sets[] = 'updated_at = NOW()'; $params[] = $projectId; sf_admin_execute('UPDATE storyboards SET ' . implode(', ', $sets) . ' WHERE id = ?', $params); }
  $characterIds = sf_ai_exec_array_ints($payload['story_character_ids'] ?? $payload['character_ids'] ?? []);
  if ($characterIds && function_exists('sf_sbc_sync_storyboard_catalog_characters')) sf_sbc_sync_storyboard_catalog_characters($projectId, $characterIds);
  sf_admin_audit('ai_router_create_story_scene_shell', 'storyboard', $projectId, null, ['episode_id'=>$episodeId,'season_id'=>$seasonId,'character_ids'=>$characterIds]);
  return sf_ai_exec_complete($actionId, $route, 'Story scene shell created. No publishing or media generation occurred.', ['storyboard_id'=>$projectId,'episode_id'=>$episodeId]);
}
function sf_ai_exec_route_assign_scene_character(int $actionId, string $route, array $payload): array {
  $storyboardId = (int)($payload['storyboard_id'] ?? 0);
  $sceneId = (int)($payload['scene_id'] ?? 0);
  $storyCharacterId = (int)($payload['story_character_id'] ?? 0);
  $characterId = (int)($payload['character_id'] ?? 0);
  if ($storyboardId <= 0 || ($storyCharacterId <= 0 && $characterId <= 0)) return sf_ai_exec_fail($actionId, $route, 'failed', 'Provide storyboard_id and story_character_id or character_id.');
  if ($storyCharacterId > 0 && function_exists('sf_sbc_sync_storyboard_catalog_characters')) {
    $synced = sf_sbc_sync_storyboard_catalog_characters($storyboardId, [$storyCharacterId]);
    if ($characterId <= 0) $characterId = (int)($synced[0] ?? 0);
  }
  if ($sceneId <= 0) $sceneId = sf_ai_exec_first_frame_scene_id($storyboardId);
  if ($sceneId > 0 && function_exists('sf_sbc_assign_scene_character')) {
    $result = sf_sbc_assign_scene_character($storyboardId, $sceneId, $characterId, $storyCharacterId);
    return !empty($result['ok']) ? sf_ai_exec_complete($actionId, $route, 'Character assigned to scene continuity.', $result) : sf_ai_exec_fail($actionId, $route, 'failed', 'Character could not be assigned to the scene.', $result);
  }
  if ($characterId > 0) return sf_ai_exec_complete($actionId, $route, 'Character synced to storyboard. No internal frame scene was available for scene-level assignment.', ['storyboard_id'=>$storyboardId,'character_id'=>$characterId]);
  return sf_ai_exec_fail($actionId, $route, 'failed', 'Character could not be synced.');
}
function sf_ai_exec_route_assign_scene_background(int $actionId, string $route, array $payload): array {
  $sceneId = (int)($payload['scene_id'] ?? 0);
  $storyboardId = (int)($payload['storyboard_id'] ?? 0);
  $backgroundId = (int)($payload['background_id'] ?? $payload['story_scene_background_id'] ?? 0);
  if ($sceneId <= 0 && $storyboardId > 0) $sceneId = sf_ai_exec_first_frame_scene_id($storyboardId);
  if ($sceneId <= 0 || $backgroundId <= 0 || !function_exists('sf_scene_background_assign_scene')) return sf_ai_exec_fail($actionId, $route, 'failed', 'Provide scene_id/storyboard_id and background_id.');
  $result = sf_scene_background_assign_scene($sceneId, $backgroundId, sf_ai_exec_text($payload, 'usage_notes', 'Assigned by AI execution router.'));
  return !empty($result['ok']) ? sf_ai_exec_complete($actionId, $route, 'Scene background assigned.', $result) : sf_ai_exec_fail($actionId, $route, 'failed', 'Scene background could not be assigned.', $result);
}
function sf_ai_exec_route_queue_media_prompt(int $actionId, string $route, array $payload): array {
  if (!sf_admin_table_exists('story_ai_media_prompts')) return sf_ai_exec_fail($actionId, $route, 'failed', 'Media prompt table is not ready.');
  $storyboardId = (int)($payload['storyboard_id'] ?? 0);
  if ($storyboardId <= 0) return sf_ai_exec_fail($actionId, $route, 'failed', 'Provide storyboard_id.');
  $title = sf_ai_exec_text($payload, 'prompt_title', sf_ai_exec_text($payload, 'title', 'AI Routed Media Prompt'));
  $body = sf_ai_exec_text($payload, 'prompt_body', sf_ai_exec_text($payload, 'body', 'Approved media prompt queued by AI execution router.'));
  $status = in_array((string)($payload['status'] ?? 'approved'), ['draft','needs_revision','approved','ready_for_generation'], true) ? (string)$payload['status'] : 'approved';
  $ok = sf_admin_execute('INSERT INTO story_ai_media_prompts (storyboard_id, story_season_id, story_episode_id, prompt_type, prompt_title, prompt_body, provider_hint, aspect_ratio, status, created_by_user_id, approved_by_user_id, approved_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CASE WHEN ? IN (\'approved\',\'ready_for_generation\') THEN NOW() ELSE NULL END)', [$storyboardId, (int)($payload['story_season_id'] ?? 0) ?: null, (int)($payload['story_episode_id'] ?? 0) ?: null, sf_ai_exec_text($payload, 'prompt_type', 'image'), $title, $body, sf_ai_exec_text($payload, 'provider_hint', 'image'), sf_ai_exec_text($payload, 'aspect_ratio', '16:9'), $status, sf_ai_exec_user_id(), sf_ai_exec_user_id(), $status]);
  $newId = (int)(sf_admin_db()?->lastInsertId() ?: 0);
  if ($ok) sf_admin_audit('ai_router_queue_media_prompt', 'story_ai_media_prompt', $newId, null, ['storyboard_id'=>$storyboardId,'status'=>$status]);
  return $ok ? sf_ai_exec_complete($actionId, $route, 'Media prompt saved. No provider was called.', ['media_prompt_id'=>$newId,'storyboard_id'=>$storyboardId]) : sf_ai_exec_fail($actionId, $route, 'failed', 'Media prompt could not be saved.');
}
function sf_ai_exec_route_create_team_briefing_draft(int $actionId, string $route, array $payload): array {
  if (!function_exists('sf_msg_ready') || !sf_msg_ready()) return sf_ai_exec_fail($actionId, $route, 'failed', 'Member Messaging tables are not ready.');
  $title = sf_ai_exec_text($payload, 'title', 'AI Production Briefing Draft');
  $subject = sf_ai_exec_text($payload, 'subject', $title);
  $body = sf_ai_exec_text($payload, 'body', 'AI-routed production briefing draft for admin review.');
  $campaignId = sf_msg_save_campaign(['title'=>$title,'subject'=>$subject,'body'=>$body,'audience_filter'=>'manual','channel_email'=>0,'channel_in_app'=>1,'honors_preferences'=>1,'status'=>'draft','action_url'=>sf_ai_exec_text($payload, 'action_url', 'admin/ai-platform-control.php')], 0);
  return $campaignId > 0 ? sf_ai_exec_complete($actionId, $route, 'Team briefing draft campaign created. It was not sent or queued.', ['campaign_id'=>$campaignId]) : sf_ai_exec_fail($actionId, $route, 'failed', 'Team briefing draft could not be created.');
}
function sf_ai_exec_route_action(int $actionId): array {
  if (!sf_ai_exec_registry_ready() || !sf_ai_exec_log_ready()) return ['ok'=>false,'status'=>'blocked','message'=>'Execution registry tables are not ready.'];
  $action = sf_admin_fetch_one('SELECT * FROM ai_platform_actions WHERE id = ? LIMIT 1', [$actionId]);
  if (!$action) return ['ok'=>false,'status'=>'failed','message'=>'AI platform action was not found.'];
  if (($action['approval_status'] ?? '') !== 'ready_for_execution' || ($action['execution_status'] ?? '') !== 'ready') return ['ok'=>false,'status'=>'blocked','message'=>'Action must be approved and ready for execution first.'];
  $route = (string)($action['action_type'] ?? '');
  $routes = sf_ai_exec_routes();
  if (!isset($routes[$route])) return sf_ai_exec_fail($actionId, $route, 'blocked', 'Route is not allowlisted.');
  if (($action['risk_level'] ?? 'low') === 'critical' && $route !== 'review') return sf_ai_exec_fail($actionId, $route, 'blocked', 'Critical-risk actions require a future dedicated executor.');
  $payload = sf_ai_exec_payload($action);
  sf_ai_exec_log($actionId, $route, 'started', 'Execution route started.', ['payload'=>$payload]);
  if ($route === 'review') return sf_ai_exec_complete($actionId, $route, 'Approved review action completed. No target platform records were changed.');
  if ($route === 'queue_media_generation') {
    $promptId = (int)($payload['media_prompt_id'] ?? 0);
    if ($promptId <= 0 || !sf_admin_table_exists('story_ai_media_prompts') || !sf_admin_table_exists('story_ai_media_generation_jobs')) return sf_ai_exec_fail($actionId, $route, 'failed', 'Media prompt or queue table is missing.');
    $prompt = sf_admin_fetch_one("SELECT * FROM story_ai_media_prompts WHERE id = ? AND status IN ('approved','ready_for_generation') LIMIT 1", [$promptId]);
    if (!$prompt) return sf_ai_exec_fail($actionId, $route, 'failed', 'Prompt is not approved or ready for generation.');
    $duplicate = sf_admin_fetch_one("SELECT id FROM story_ai_media_generation_jobs WHERE media_prompt_id = ? AND generation_status IN ('queued','blocked','needs_review') LIMIT 1", [$promptId]);
    if ($duplicate) return sf_ai_exec_fail($actionId, $route, 'blocked', 'Prompt already has an active generation request.', ['job_id'=>(int)$duplicate['id']]);
    $ok = sf_admin_execute('INSERT INTO story_ai_media_generation_jobs (media_prompt_id, storyboard_id, story_season_id, story_episode_id, prompt_type, prompt_title, prompt_body, provider_hint, aspect_ratio, generation_status, request_notes, requested_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [(int)$prompt['id'], (int)$prompt['storyboard_id'], $prompt['story_season_id'] ?: null, $prompt['story_episode_id'] ?: null, $prompt['prompt_type'], $prompt['prompt_title'], $prompt['prompt_body'], $prompt['provider_hint'], $prompt['aspect_ratio'], 'queued', 'Queued by AI execution router action #' . $actionId, sf_ai_exec_user_id()]);
    return $ok ? sf_ai_exec_complete($actionId, $route, 'Media generation request queued. No provider was called.') : sf_ai_exec_fail($actionId, $route, 'failed', 'Media generation request could not be queued.');
  }
  if ($route === 'block_media_generation') {
    $jobId = (int)($payload['generation_job_id'] ?? 0);
    if ($jobId <= 0 || !sf_admin_table_exists('story_ai_media_generation_jobs')) return sf_ai_exec_fail($actionId, $route, 'failed', 'Generation job table or id is missing.');
    $before = sf_admin_fetch_one('SELECT * FROM story_ai_media_generation_jobs WHERE id = ? LIMIT 1', [$jobId]);
    $ok = (bool)$before && sf_admin_execute("UPDATE story_ai_media_generation_jobs SET generation_status = 'blocked', reviewed_by_user_id = ?, reviewed_at = NOW() WHERE id = ?", [sf_ai_exec_user_id(), $jobId]);
    $after = sf_admin_fetch_one('SELECT * FROM story_ai_media_generation_jobs WHERE id = ? LIMIT 1', [$jobId]);
    if ($ok) sf_admin_audit('ai_router_block_media_generation_job', 'story_ai_media_generation_job', $jobId, $before, $after);
    return $ok ? sf_ai_exec_complete($actionId, $route, 'Media generation job blocked for review.', ['generation_job_id'=>$jobId]) : sf_ai_exec_fail($actionId, $route, 'failed', 'Media generation job could not be blocked.');
  }
  if ($route === 'create_story_scene_shell') return sf_ai_exec_route_create_story_scene_shell($actionId, $route, $payload);
  if ($route === 'update_scene_status') return sf_ai_exec_route_update_scene_status($actionId, $route, $payload);
  if ($route === 'mark_scene_needs_review') return sf_ai_exec_route_update_scene_status($actionId, $route, $payload, 'needs_review');
  if ($route === 'mark_scene_ready') return sf_ai_exec_route_update_scene_status($actionId, $route, $payload, 'ready');
  if ($route === 'assign_scene_character') return sf_ai_exec_route_assign_scene_character($actionId, $route, $payload);
  if ($route === 'assign_scene_background') return sf_ai_exec_route_assign_scene_background($actionId, $route, $payload);
  if ($route === 'queue_media_prompt') return sf_ai_exec_route_queue_media_prompt($actionId, $route, $payload);
  if ($route === 'create_team_briefing_draft') return sf_ai_exec_route_create_team_briefing_draft($actionId, $route, $payload);
  return sf_ai_exec_fail($actionId, $route, 'blocked', 'Unhandled route.');
}
?>
