<?php
require_once __DIR__ . '/qa.php';

function sf_pkg_check(string $group, string $status, string $label, string $detail = '', int $weight = 1): array { return ['group'=>$group,'status'=>$status,'label'=>$label,'detail'=>$detail,'weight'=>max(1,$weight)]; }
function sf_pkg_score(array $checks): int { return sf_qa_score($checks); }
function sf_pkg_grade(int $score): string { return sf_qa_grade($score); }
function sf_pkg_badge(string $status): string { return sf_qa_badge($status); }
function sf_pkg_required_files(): array {
  return [
    'Core runtime' => ['index.php','includes/config.php','includes/db.php','includes/auth.php','includes/header.php','includes/footer.php','includes/qa.php','includes/installer.php','includes/package_readiness.php','includes/smoke_tests.php','includes/release_candidate.php','includes/storyboards.php','includes/ai_settings.php','includes/storyboard_generation.php'],
    'Install and deploy' => ['install.php','deploy/preflight.php','.env.example','docs/DEPLOYMENT_RUNBOOK.md','docs/SQL_FILE_MAP.md','docs/FINAL_PRODUCTION_QA_ROUTE_REGISTRY_V2.md','docs/PHASE_37_PACKAGE_READINESS.md','docs/PHASE_38_SMOKE_TESTS.md','docs/PHASE_39_RELEASE_CANDIDATE.md','docs/PHASE_40_STORYBOARDING_MODULE.md','docs/PHASE_41_STORYBOARDING_SQL_AI_SETTINGS.md','docs/PHASE_42_STORYBOARD_GENERATION_API.md'],
    'SQL' => array_merge(array_map(static fn($m) => $m['file'], sf_qa_migration_plan()), ['database/migrations/021_storyboarding_ai_settings.sql']),
    'Admin launch' => ['admin/index.php','admin/release-candidate.php','admin/package-readiness.php','admin/smoke-tests.php','admin/launch-checklist.php','admin/qa.php','admin/migration-checker.php','admin/routes-checker.php','admin/security-check.php','admin/content-audit.php','admin/system-health.php'],
    'Production ops' => ['admin/monitoring.php','admin/incidents.php','admin/backups.php','admin/releases.php','admin/ops-scheduler.php','admin/member-messaging.php','admin/member-lifecycle.php','admin/support.php'],
    'Member runtime' => ['member.php','library.php','watchlist.php','playlists.php','feed.php','notifications.php','messages.php','comments.php','support.php','account.php','account-billing.php'],
    'Commerce and billing' => ['subscribe.php','billing-checkout.php','billing-success.php','billing-cancel.php','merch.php','product.php','cart.php','checkout.php','order-confirmation.php'],
    'Media delivery' => ['stream.php','download.php','api/media-token.php','api/player-state.php','api/audio-track.php','api/video-track.php','api/entitlement-check.php'],
    'APIs' => ['api/search.php','api/analytics-summary.php','api/comments.php','api/notifications.php','api/member-messages.php','api/ops-scheduler.php','api/backup-readiness.php','api/release-manager.php','api/monitoring.php','api/incidents.php','api/storyboard-generate.php'],
    'Smoke test matrix' => ['includes/smoke_tests.php','admin/smoke-tests.php','docs/PHASE_38_SMOKE_TESTS.md'],
    'Release candidate' => ['includes/release_candidate.php','admin/release-candidate.php','docs/PHASE_39_RELEASE_CANDIDATE.md'],
    'Storyboarding module' => ['includes/storyboards.php','admin/storyboards.php','admin/storyboard-builder.php','docs/PHASE_40_STORYBOARDING_MODULE.md'],
    'AI settings' => ['includes/ai_settings.php','admin/ai-settings.php','database/migrations/021_storyboarding_ai_settings.sql','docs/PHASE_41_STORYBOARDING_SQL_AI_SETTINGS.md'],
    'Storyboard generation' => ['includes/storyboard_generation.php','api/storyboard-generate.php','docs/PHASE_42_STORYBOARD_GENERATION_API.md'],
    'Styles and assets' => ['assets/css/stonefellow.css','assets/css/admin-polish.css','assets/css/pwa-upload.css','manifest.webmanifest','service-worker.js'],
  ];
}
function sf_pkg_required_dirs(): array { return ['config','storage','assets/images/uploads','assets/audio/uploads','assets/video/uploads','assets/documents/uploads','database/migrations','docs','deploy','admin','api','includes']; }
function sf_pkg_file_manifest(): array { $rows = []; foreach (sf_pkg_required_files() as $group => $files) foreach ($files as $file) { $path = sf_qa_file_path($file); $exists = is_file($path); $rows[] = ['group'=>$group,'path'=>$file,'exists'=>$exists,'size'=>$exists ? (int)filesize($path) : 0,'sha256'=>$exists ? hash_file('sha256', $path) : '']; } return $rows; }
function sf_pkg_manifest_summary(): array { $rows = sf_pkg_file_manifest(); $total = count($rows); $present = count(array_filter($rows, static fn($r) => !empty($r['exists']))); $missing = $total - $present; $bytes = array_sum(array_map(static fn($r) => (int)($r['size'] ?? 0), $rows)); return ['total'=>$total,'present'=>$present,'missing'=>$missing,'bytes'=>$bytes]; }
function sf_pkg_manifest_json(): string { $summary = sf_pkg_manifest_summary(); $payload = ['generated_at'=>date('c'),'platform'=>'Stonefellow Membership Platform','migration_target'=>'021','summary'=>$summary,'files'=>sf_pkg_file_manifest()]; return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); }
function sf_pkg_checks(): array {
  $checks = [];
  foreach (sf_pkg_required_files() as $group => $files) foreach ($files as $file) $checks[] = sf_pkg_check($group, sf_qa_file_exists($file) ? 'pass' : 'fail', 'File: ' . $file, sf_qa_file_exists($file) ? 'Present' : 'Missing', 2);
  foreach (sf_pkg_required_dirs() as $dir) $checks[] = sf_pkg_check('Directories', sf_qa_dir_exists($dir) ? 'pass' : 'fail', 'Directory: ' . $dir, sf_qa_dir_exists($dir) ? 'Present' : 'Missing', 2);
  $checks[] = sf_pkg_check('Installer', sf_qa_contains('includes/installer.php', ["'020'=>", '020_monitoring_incident_alerts.sql']) ? 'pass' : 'fail', 'Installer migration target through 020', 'Installer should include migration 020.', 3);
  $checks[] = sf_pkg_check('SQL', sf_qa_file_exists('database/migrations/021_storyboarding_ai_settings.sql') ? 'pass' : 'fail', 'Migration 021 file', 'Storyboarding SQL + AI settings migration should be present.', 4);
  $checks[] = sf_pkg_check('Admin', sf_qa_contains('admin/index.php', ['admin/package-readiness.php']) ? 'pass' : 'warn', 'Admin package readiness link', 'Admin dashboard should link to package readiness.', 2);
  $checks[] = sf_pkg_check('Admin', sf_qa_contains('admin/index.php', ['admin/smoke-tests.php']) ? 'pass' : 'warn', 'Admin smoke-test link', 'Admin dashboard should link to smoke tests.', 2);
  $checks[] = sf_pkg_check('Admin', sf_qa_contains('admin/index.php', ['admin/release-candidate.php']) ? 'pass' : 'warn', 'Admin release candidate link', 'Admin dashboard should link to release candidate.', 2);
  $checks[] = sf_pkg_check('Admin', sf_qa_contains('admin/index.php', ['admin/storyboards.php']) ? 'pass' : 'warn', 'Admin storyboarding link', 'Admin dashboard should link to storyboarding.', 2);
  $checks[] = sf_pkg_check('Admin', sf_qa_contains('admin/index.php', ['admin/ai-settings.php']) ? 'pass' : 'warn', 'Admin AI settings link', 'Admin dashboard should link to AI settings.', 2);
  $checks[] = sf_pkg_check('Preflight', sf_qa_contains('deploy/preflight.php', ['sf_pkg_checks', 'Package Readiness']) ? 'pass' : 'warn', 'Preflight package checks', 'Preflight should include package readiness output.', 3);
  $checks[] = sf_pkg_check('Preflight', sf_qa_contains('deploy/preflight.php', ['sf_smoke_checks', 'Smoke Tests']) ? 'pass' : 'warn', 'Preflight smoke tests', 'Preflight should include smoke-test output.', 3);
  $checks[] = sf_pkg_check('Preflight', sf_qa_contains('deploy/preflight.php', ['sf_rc_checks', 'Release Candidate']) ? 'pass' : 'warn', 'Preflight release candidate', 'Preflight should include release candidate output.', 3);
  $checks[] = sf_pkg_check('Smoke test matrix', sf_qa_contains('includes/smoke_tests.php', ['sf_smoke_scenarios', 'sf_smoke_checks']) ? 'pass' : 'fail', 'Smoke helper registered', 'Smoke scenario helper should expose matrix and checks.', 3);
  $checks[] = sf_pkg_check('Release candidate', sf_qa_contains('includes/release_candidate.php', ['sf_rc_checks', 'sf_rc_summary']) ? 'pass' : 'fail', 'Release candidate helper registered', 'Release candidate helper should expose final handoff checks.', 3);
  $checks[] = sf_pkg_check('Storyboarding module', sf_qa_contains('includes/storyboards.php', ['sf_storyboard_scenes', 'sf_storyboard_characters', 'sf_storyboard_ready']) ? 'pass' : 'fail', 'Storyboarding helper registered', 'Storyboarding helper should expose DB-aware projects, characters, settings, and scenes.', 3);
  $checks[] = sf_pkg_check('Storyboarding module', sf_qa_contains('admin/storyboard-builder.php', ['Generate 9-Scene Storyboard', 'Regenerate Image', 'Upload Image']) ? 'pass' : 'fail', 'Storyboarding builder shell', 'Builder should include prompt, scene grid, and scene actions.', 3);
  $checks[] = sf_pkg_check('AI settings', sf_qa_contains('includes/ai_settings.php', ['sf_ai_save_provider', 'sf_ai_encrypt_secret']) ? 'pass' : 'fail', 'AI settings helper registered', 'AI helper should save provider settings and encrypt secrets.', 4);
  $checks[] = sf_pkg_check('AI settings', sf_qa_contains('admin/ai-settings.php', ['Claude + ChatGPT keys', 'New API Key']) ? 'pass' : 'fail', 'Admin AI settings page', 'Admin AI settings page should manage provider keys and limits.', 4);
  $checks[] = sf_pkg_check('Storyboard generation', sf_qa_contains('includes/storyboard_generation.php', ['sf_sbgen_generate_storyboard', 'sf_sbgen_call_provider', 'sf_sbgen_save_result']) ? 'pass' : 'fail', 'Generation helper registered', 'Storyboard generation helper should call provider, parse JSON, and save scenes.', 4);
  $checks[] = sf_pkg_check('Storyboard generation', sf_qa_contains('api/storyboard-generate.php', ['sf_sbgen_generate_storyboard', 'admin_required']) ? 'pass' : 'fail', 'Generation API endpoint', 'Storyboard generation API should be admin-gated and call the helper.', 4);
  $checks[] = sf_pkg_check('Storyboard generation', sf_qa_contains('admin/storyboard-builder.php', ['api/storyboard-generate.php']) ? 'pass' : 'warn', 'Builder generation action', 'Storyboard builder should submit prompt to the generation API.', 3);
  $checks[] = sf_pkg_check('Docs', sf_qa_contains('docs/DEPLOYMENT_RUNBOOK.md', ['020_monitoring_incident_alerts.sql', 'admin/package-readiness.php', 'admin/smoke-tests.php', 'admin/release-candidate.php']) ? 'pass' : 'warn', 'Runbook package ready', 'Runbook should reference migration 020, package readiness, smoke tests, and release candidate.', 2);
  $checks[] = sf_pkg_check('Docs', sf_qa_contains('docs/SQL_FILE_MAP.md', ['020_monitoring_incident_alerts.sql']) ? 'pass' : 'fail', 'SQL map target 020', 'SQL map should document migration 020.', 2);
  $routeChecks = sf_qa_route_checks(); $routeFails = count(array_filter($routeChecks, static fn($c) => in_array(($c['status'] ?? ''), ['fail','missing'], true)));
  $checks[] = sf_pkg_check('Routes', $routeFails === 0 ? 'pass' : 'fail', 'Route registry has no missing required routes', $routeFails === 0 ? 'All required route files are present.' : $routeFails . ' route checks are failing.', 4);
  $migrationFiles = array_filter(sf_qa_migration_checks(), static fn($c) => strpos((string)($c['label'] ?? ''), ' — ') !== false); $migrationFails = count(array_filter($migrationFiles, static fn($c) => ($c['status'] ?? '') === 'fail'));
  $checks[] = sf_pkg_check('SQL', $migrationFails === 0 ? 'pass' : 'fail', 'Migration files present through QA target', $migrationFails === 0 ? 'All expected QA migration files are present.' : $migrationFails . ' migration file checks failed.', 4);
  return $checks;
}
function sf_pkg_group_summary(array $checks): array { $summary = []; foreach ($checks as $check) { $group = (string)($check['group'] ?? 'General'); if (!isset($summary[$group])) $summary[$group] = ['group'=>$group,'count'=>0,'fails'=>0,'warnings'=>0,'score'=>0,'checks'=>[]]; $summary[$group]['count']++; $summary[$group]['checks'][] = $check; if (in_array(($check['status'] ?? ''), ['fail','missing'], true)) $summary[$group]['fails']++; if (in_array(($check['status'] ?? ''), ['warn','preview','manual'], true)) $summary[$group]['warnings']++; } foreach ($summary as $group => $data) $summary[$group]['score'] = sf_pkg_score($data['checks']); return array_values($summary); }
function sf_pkg_status_text(array $checks): string { $score = sf_pkg_score($checks); $fails = count(array_filter($checks, static fn($c) => in_array(($c['status'] ?? ''), ['fail','missing'], true))); if ($fails > 0) return 'blocked'; if ($score >= 97) return 'ready'; if ($score >= 90) return 'review'; return 'needs_work'; }
function sf_pkg_render_check_table(array $checks): void { echo '<div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Group</th><th>Check</th><th>Status</th><th>Detail</th></tr></thead><tbody>'; foreach ($checks as $check) echo '<tr><td>' . sf_pkg_h($check['group'] ?? '') . '</td><td><strong>' . sf_pkg_h($check['label'] ?? '') . '</strong></td><td>' . sf_pkg_badge((string)($check['status'] ?? 'info')) . '</td><td>' . sf_pkg_h($check['detail'] ?? '') . '</td></tr>'; echo '</tbody></table></div>'; }
function sf_pkg_h($value): string { return sf_qa_h($value); }
?>
