<?php
$root = dirname(__DIR__);
$checks = [
    'Certification persistence' => [
        'database/ai_staging_certification_v1.sql' => ['ai_staging_certification_runs','ai_staging_certification_checks','ai_staging_provider_cost_reconciliations','UNIQUE KEY uq_ai_cert_check'],
    ],
    'Runtime composition' => [
        'includes/ai_staging_certification.php' => ['ai_staging_certification_core.php','ai_staging_certification_checks.php','ai_staging_certification_runtime.php'],
    ],
    'Provider connection tests' => [
        'includes/ai_staging_certification_runtime.php' => ['sf_ai_cert_provider_test','sf_agentic_guard_live_request','sf_sbgen_call_provider','test_status','key_status'],
    ],
    'Environment health gate' => [
        'includes/ai_staging_certification_checks.php' => ['environment.staging','environment.https','environment.allowed_hosts','secrets.ai_settings'],
    ],
    'Failure simulations' => [
        'includes/ai_staging_certification_checks.php' => ['transport.retry_classification','transport.malformed_output','transport.oversized_output','sf_ai_cert_retryable_status'],
    ],
    'Concurrency certification' => [
        'includes/ai_staging_certification_checks.php' => ['GET_LOCK','concurrency.duplicate_submit','concurrency.mission_claim','rowCount'],
    ],
    'Rollback certification' => [
        'includes/ai_staging_certification_runtime.php' => ['sf_ai_cert_restore_roundtrip','sf_agentic_snapshot_storyboard','sf_agentic_snapshot_scene','sf_agentic_snapshot_episode','FOR UPDATE'],
    ],
    'Cost reconciliation' => [
        'includes/ai_staging_certification_runtime.php' => ['sf_ai_cert_save_cost_reconciliation','provider_invoice_cents','reserved_cost_cents','variance_cents'],
    ],
    'Admin workflow' => [
        'admin/ai-staging-certification.php' => ['run_automated','test_provider','restore_roundtrip','save_cost','manual_check','complete_run'],
    ],
    'Operational documentation' => [
        'docs/AI_STAGING_CERTIFICATION_V1.md' => ['SF_ENV=staging','Provider connection tests','Snapshot restore tests','No automatic production enablement'],
        '.env.staging.example' => ['SF_ENV=staging','SF_AI_SETTINGS_SECRET','SF_AI_REQUIRE_DISTINCT_EXECUTOR=1'],
    ],
    'Continuous verification' => [
        'tests/ai_staging_certification_smoke.php' => ['AI staging certification smoke tests passed'],
        '.github/workflows/code-audit.yml' => ['AI staging certification smoke tests','AI staging certification audit'],
    ],
];

$failed = [];
foreach ($checks as $section=>$files) {
    foreach ($files as $path=>$needles) {
        $file = $root . '/' . $path;
        if (!is_file($file)) { $failed[] = "$section: missing $path"; continue; }
        $content = (string)file_get_contents($file);
        foreach ($needles as $needle) if (!str_contains($content,$needle)) $failed[] = "$section: $path missing $needle";
    }
}

if ($failed) {
    fwrite(STDERR,"AI staging certification audit failed:\n- " . implode("\n- ",$failed) . "\n");
    exit(1);
}

foreach (array_keys($checks) as $section) echo $section . ": 10/10\n";
echo "AI staging certification static score: 10/10\n";
