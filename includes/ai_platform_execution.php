<?php
function sf_ai_exec_registry_ready(): bool { return sf_admin_table_exists('ai_platform_actions'); }
function sf_ai_exec_log_ready(): bool { return sf_admin_table_exists('ai_platform_action_executions'); }
function sf_ai_exec_routes(): array {
  return [
    'review' => ['label'=>'Complete Review Action','description'=>'Marks an approved review action executed after the admin confirms it. No platform target data is changed.','side_effect'=>'registry_only'],
    'queue_media_generation' => ['label'=>'Queue Media Generation','description'=>'Creates one generation queue record from an approved media prompt. No provider is called.','side_effect'=>'queue_record'],
    'block_media_generation' => ['label'=>'Block Media Generation Job','description'=>'Marks one generation queue record blocked for review.','side_effect'=>'status_update'],
  ];
}
function sf_ai_exec_payload(array $action): array { $raw = trim((string)($action['payload_json'] ?? '')); if ($raw === '') return []; $data = json_decode($raw, true); return is_array($data) ? $data : []; }
function sf_ai_exec_log(int $actionId, string $route, string $status, string $message, array $result = []): void {
  if (!sf_ai_exec_log_ready()) return;
  sf_admin_execute('INSERT INTO ai_platform_action_executions (platform_action_id, route_key, execution_status, result_message, result_json, executed_by_user_id, completed_at) VALUES (?, ?, ?, ?, ?, ?, CASE WHEN ? IN (\'completed\',\'failed\',\'blocked\') THEN NOW() ELSE NULL END)', [$actionId, $route, $status, $message, json_encode($result, JSON_UNESCAPED_SLASHES), function_exists('sf_current_user_id') ? sf_current_user_id() : null, $status]);
}
function sf_ai_exec_mark_action(int $actionId, string $status, string $message): void {
  $userId = function_exists('sf_current_user_id') ? sf_current_user_id() : null;
  $execution = $status === 'completed' ? 'executed' : ($status === 'blocked' ? 'cancelled' : 'failed');
  sf_admin_execute('UPDATE ai_platform_actions SET execution_status = ?, executed_by_user_id = ?, executed_at = CASE WHEN ? = \'executed\' THEN NOW() ELSE executed_at END WHERE id = ?', [$execution, $userId, $execution, $actionId]);
  sf_admin_audit('ai_platform_action_execution_' . $status, 'ai_platform_action', $actionId, null, ['message'=>$message]);
}
function sf_ai_exec_route_action(int $actionId): array {
  if (!sf_ai_exec_registry_ready() || !sf_ai_exec_log_ready()) return ['ok'=>false,'status'=>'blocked','message'=>'Execution registry tables are not ready.'];
  $action = sf_admin_fetch_one('SELECT * FROM ai_platform_actions WHERE id = ? LIMIT 1', [$actionId]);
  if (!$action) return ['ok'=>false,'status'=>'failed','message'=>'AI platform action was not found.'];
  if (($action['approval_status'] ?? '') !== 'ready_for_execution' || ($action['execution_status'] ?? '') !== 'ready') return ['ok'=>false,'status'=>'blocked','message'=>'Action must be approved and ready for execution first.'];
  $route = (string)($action['action_type'] ?? '');
  $routes = sf_ai_exec_routes();
  if (!isset($routes[$route])) { sf_ai_exec_log($actionId, $route ?: 'unknown', 'blocked', 'Route is not allowlisted.'); return ['ok'=>false,'status'=>'blocked','message'=>'Route is not allowlisted.']; }
  if (($action['risk_level'] ?? 'low') === 'critical' && $route !== 'review') { sf_ai_exec_log($actionId, $route, 'blocked', 'Critical-risk actions require a future dedicated executor.'); return ['ok'=>false,'status'=>'blocked','message'=>'Critical-risk actions require a future dedicated executor.']; }
  $payload = sf_ai_exec_payload($action);
  sf_ai_exec_log($actionId, $route, 'started', 'Execution route started.', ['payload'=>$payload]);
  if ($route === 'review') {
    $message = 'Approved review action completed. No target platform records were changed.';
    sf_ai_exec_log($actionId, $route, 'completed', $message);
    sf_ai_exec_mark_action($actionId, 'completed', $message);
    return ['ok'=>true,'status'=>'completed','message'=>$message];
  }
  if ($route === 'queue_media_generation') {
    $promptId = (int)($payload['media_prompt_id'] ?? 0);
    if ($promptId <= 0 || !sf_admin_table_exists('story_ai_media_prompts') || !sf_admin_table_exists('story_ai_media_generation_jobs')) { sf_ai_exec_log($actionId, $route, 'failed', 'Media prompt or queue table is missing.'); return ['ok'=>false,'status'=>'failed','message'=>'Media prompt or queue table is missing.']; }
    $prompt = sf_admin_fetch_one("SELECT * FROM story_ai_media_prompts WHERE id = ? AND status IN ('approved','ready_for_generation') LIMIT 1", [$promptId]);
    if (!$prompt) { sf_ai_exec_log($actionId, $route, 'failed', 'Prompt is not approved or ready for generation.'); return ['ok'=>false,'status'=>'failed','message'=>'Prompt is not approved or ready for generation.']; }
    $duplicate = sf_admin_fetch_one("SELECT id FROM story_ai_media_generation_jobs WHERE media_prompt_id = ? AND generation_status IN ('queued','blocked','needs_review') LIMIT 1", [$promptId]);
    if ($duplicate) { sf_ai_exec_log($actionId, $route, 'blocked', 'Prompt already has an active generation request.', ['job_id'=>(int)$duplicate['id']]); return ['ok'=>false,'status'=>'blocked','message'=>'Prompt already has an active generation request.']; }
    $ok = sf_admin_execute('INSERT INTO story_ai_media_generation_jobs (media_prompt_id, storyboard_id, story_season_id, story_episode_id, prompt_type, prompt_title, prompt_body, provider_hint, aspect_ratio, generation_status, request_notes, requested_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [(int)$prompt['id'], (int)$prompt['storyboard_id'], $prompt['story_season_id'] ?: null, $prompt['story_episode_id'] ?: null, $prompt['prompt_type'], $prompt['prompt_title'], $prompt['prompt_body'], $prompt['provider_hint'], $prompt['aspect_ratio'], 'queued', 'Queued by AI execution router action #' . $actionId, function_exists('sf_current_user_id') ? sf_current_user_id() : null]);
    $message = $ok ? 'Media generation request queued. No provider was called.' : 'Media generation request could not be queued.';
    sf_ai_exec_log($actionId, $route, $ok ? 'completed' : 'failed', $message);
    if ($ok) sf_ai_exec_mark_action($actionId, 'completed', $message);
    return ['ok'=>$ok,'status'=>$ok ? 'completed' : 'failed','message'=>$message];
  }
  if ($route === 'block_media_generation') {
    $jobId = (int)($payload['generation_job_id'] ?? 0);
    if ($jobId <= 0 || !sf_admin_table_exists('story_ai_media_generation_jobs')) { sf_ai_exec_log($actionId, $route, 'failed', 'Generation job table or id is missing.'); return ['ok'=>false,'status'=>'failed','message'=>'Generation job table or id is missing.']; }
    $before = sf_admin_fetch_one('SELECT * FROM story_ai_media_generation_jobs WHERE id = ? LIMIT 1', [$jobId]);
    $ok = (bool)$before && sf_admin_execute("UPDATE story_ai_media_generation_jobs SET generation_status = 'blocked', reviewed_by_user_id = ?, reviewed_at = NOW() WHERE id = ?", [function_exists('sf_current_user_id') ? sf_current_user_id() : null, $jobId]);
    $after = sf_admin_fetch_one('SELECT * FROM story_ai_media_generation_jobs WHERE id = ? LIMIT 1', [$jobId]);
    if ($ok) sf_admin_audit('ai_router_block_media_generation_job', 'story_ai_media_generation_job', $jobId, $before, $after);
    $message = $ok ? 'Media generation job blocked for review.' : 'Media generation job could not be blocked.';
    sf_ai_exec_log($actionId, $route, $ok ? 'completed' : 'failed', $message, ['generation_job_id'=>$jobId]);
    if ($ok) sf_ai_exec_mark_action($actionId, 'completed', $message);
    return ['ok'=>$ok,'status'=>$ok ? 'completed' : 'failed','message'=>$message];
  }
  sf_ai_exec_log($actionId, $route, 'blocked', 'Unhandled route.');
  return ['ok'=>false,'status'=>'blocked','message'=>'Unhandled route.'];
}
?>
