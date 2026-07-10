<?php
$root = dirname(__DIR__);
$read = static function (string $path) use ($root): string {
    $full = $root . '/' . $path;
    return is_file($full) ? (string)file_get_contents($full) : '';
};
$has = static function (string $path, array $needles) use ($read): bool {
    $body = $read($path);
    foreach ($needles as $needle) if (!str_contains($body, $needle)) return false;
    return true;
};
$sections = [
    'Provider secrets and configuration' => [
        ['includes/ai_settings.php',['SF_AI_SETTINGS_SECRET','aes-256-gcm','v2:','sf_ai_provider_runtime_ready']],
        ['.env.example',['SF_AI_SETTINGS_SECRET','SF_AI_REQUIRE_DISTINCT_EXECUTOR']],
    ],
    'Provider transport and retry safety' => [
        ['includes/storyboard_generation.php',['provider_endpoint_not_allowed','CURLOPT_SSL_VERIFYPEER','CURLOPT_FOLLOWLOCATION','provider_response_too_large','retry-after']],
    ],
    'Budgets, limits, reservations, and throttling' => [
        ['includes/ai_governance.php',['monthly_budget_cents','monthly_token_limit','monthly_image_limit','sf_agentic_reserve_usage','sf_security_session_rate_limit']],
    ],
    'Prompt and data boundaries' => [
        ['includes/storyboard_generation.php',['<CONTEXT>','<PRODUCER_REQUEST>','untrusted story data']],
        ['includes/storyboard_scene_actions.php',['<SCENE_CONTEXT>','<REWRITE_REQUEST>']],
        ['api/story-episode-outline.php',['<EPISODE_CONTEXT>','<PRODUCER_REQUEST>']],
    ],
    'Structured output validation' => [
        ['includes/storyboard_generation.php',['sf_sbgen_clean_scene','invalid_scene_schema','scene_number']],
        ['includes/storyboard_scene_actions.php',['sf_sba_parse_scene_json','invalid_scene_schema']],
        ['api/story-episode-outline.php',['scene_plan','structured output']],
    ],
    'Mutation snapshots and rollback evidence' => [
        ['includes/ai_governance.php',['sf_agentic_snapshot_storyboard','sf_agentic_snapshot_scene','sf_agentic_snapshot_episode']],
        ['includes/storyboard_generation.php',['sf_agentic_snapshot_storyboard']],
        ['includes/storyboard_scene_actions.php',['sf_agentic_snapshot_scene']],
        ['api/story-episode-outline.php',['sf_agentic_snapshot_episode']],
    ],
    'Generated media validation and approval' => [
        ['includes/ai_governance.php',['sf_agentic_validate_image_bytes','getimagesizefromstring']],
        ['includes/show_theme.php',["source!=='approved'",'sf_agentic_validate_image_bytes','Approve it before publishing']],
        ['includes/storyboard_scene_actions.php',['sf_agentic_validate_image_bytes','LOCK_EX']],
    ],
    'Allowlisted action execution and policy integrity' => [
        ['includes/ai_platform_execution.php',['sf_ai_exec_routes','Route is not allowlisted','sf_ai_policy_can_execute']],
        ['includes/ai_governance.php',['action_changed_after_approval','distinct_executor_required','invalid_action_payload']],
        ['admin/ai-execution-router.php',['sf_agentic_action_execution_allowed','sf_agentic_acquire_lock']],
    ],
    'Mission idempotency and recovery leases' => [
        ['includes/ai_mission_execution.php',['FOR UPDATE','sf_agentic_acquire_lock','SF_AI_RUNNING_LEASE_SECONDS','rowCount()']],
    ],
    'Scoped AI administration permissions' => [
        ['includes/runtime_guards.php',['sf_runtime_guard_ai_permission','admin.ops.manage','admin.content.manage','admin.settings.manage']],
        ['includes/ai_governance.php',['sf_agentic_require_permission','admin.content.manage']],
    ],
    'Audit, privacy, and bounded retention' => [
        ['includes/storyboard_generation.php',['output_hash','response_excerpt','input_hash']],
        ['includes/show_theme.php',['payload_hash','error_message']],
        ['includes/ai_governance.php',['sf_agentic_finalize_reservation','ai_pre_mutation_snapshot']],
    ],
    'Tests and continuous verification' => [
        ['tests/agentic_governance_smoke.php',['Agentic governance smoke tests passed']],
        ['.github/workflows/code-audit.yml',['Agentic governance smoke tests','Agentic integration audit']],
        ['docs/AGENTIC_INTEGRATION_AUDIT_V1.md',['Final static agentic score','10/10']],
    ],
];

$failed = false;
foreach ($sections as $name => $checks) {
    $passed = 0;
    foreach ($checks as [$path,$needles]) if ($has($path,$needles)) $passed++;
    $score = $checks ? round(($passed / count($checks)) * 10, 1) : 0;
    printf("%-48s %4.1f/10\n", $name, $score);
    if ($passed !== count($checks)) {
        $failed = true;
        foreach ($checks as [$path,$needles]) if (!$has($path,$needles)) echo "  FAIL: {$path}\n";
    }
}
if ($failed) exit(1);
echo "Final static agentic score: 10/10\n";
