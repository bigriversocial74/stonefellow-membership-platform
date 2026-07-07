<?php
require_once __DIR__ . '/smoke_tests.php';
require_once __DIR__ . '/backup_release.php';

function sf_rc_repo_full_name(): string { return 'bigriversocial74/stonefellow-membership-platform'; }
function sf_rc_target_branch(): string { return 'main'; }
function sf_rc_target_migration(): string { return '021'; }
function sf_rc_zip_url(): string { return 'https://github.com/' . sf_rc_repo_full_name() . '/archive/refs/heads/' . sf_rc_target_branch() . '.zip'; }
function sf_rc_check(string $group, string $status, string $label, string $detail = '', int $weight = 1, string $url = ''): array { return ['group'=>$group,'status'=>$status,'label'=>$label,'detail'=>$detail,'weight'=>max(1,$weight),'url'=>$url]; }
function sf_rc_latest_release(): ?array { $rows = function_exists('sf_rel_releases') ? sf_rel_releases(1) : []; return $rows[0] ?? null; }
function sf_rc_latest_backup(): ?array { $rows = function_exists('sf_br_runs') ? sf_br_runs(1) : []; return $rows[0] ?? null; }
function sf_rc_release_tasks_summary(?array $release = null): array {
  $releaseId = (int)($release['id'] ?? 0);
  $tasks = $releaseId ? sf_rel_tasks($releaseId) : [];
  return ['total'=>count($tasks),'passed'=>count(array_filter($tasks, static fn($t) => ($t['status'] ?? '') === 'passed')),'failed'=>count(array_filter($tasks, static fn($t) => ($t['status'] ?? '') === 'failed')),'waived'=>count(array_filter($tasks, static fn($t) => ($t['status'] ?? '') === 'waived')),'pending'=>count(array_filter($tasks, static fn($t) => ($t['status'] ?? 'pending') === 'pending'))];
}
function sf_rc_checks(): array {
  $qaChecks = sf_qa_flatten(sf_qa_all_checks());
  $pkgChecks = sf_pkg_checks();
  $smokeChecks = sf_smoke_checks();
  $qaFails = count(array_filter($qaChecks, static fn($c) => in_array(($c['status'] ?? ''), ['fail','missing'], true)));
  $pkgFails = count(array_filter($pkgChecks, static fn($c) => in_array(($c['status'] ?? ''), ['fail','missing'], true)));
  $smokeFails = count(array_filter($smokeChecks, static fn($c) => in_array(($c['status'] ?? ''), ['fail','missing'], true)));
  $manualSmoke = count(array_filter($smokeChecks, static fn($c) => ($c['status'] ?? '') === 'manual'));
  $release = sf_rc_latest_release();
  $backup = sf_rc_latest_backup();
  $taskSummary = sf_rc_release_tasks_summary($release);
  $releaseStatus = (string)($release['release_status'] ?? 'missing');
  $backupStatus = (string)($backup['run_status'] ?? 'missing');
  $releaseReady = $release && in_array($releaseStatus, ['ready','deploying','deployed'], true);
  $backupReady = $backup && in_array($backupStatus, ['completed','verified'], true);
  $taskReady = !$release ? false : ((int)$taskSummary['total'] === 0 ? false : ((int)$taskSummary['failed'] === 0 && (int)$taskSummary['pending'] === 0));
  return [
    sf_rc_check('Codebase','pass','Repository target',sf_rc_repo_full_name() . ' / ' . sf_rc_target_branch(),2),
    sf_rc_check('Codebase','pass','Deploy ZIP URL',sf_rc_zip_url(),2,sf_rc_zip_url()),
    sf_rc_check('SQL','pass','Migration target','Base schema plus migrations 001 through ' . sf_rc_target_migration() . '.',3),
    sf_rc_check('SQL',sf_qa_file_exists('database/migrations/021_storyboarding_ai_settings.sql') ? 'pass' : 'fail','Migration 021 file','Storyboarding SQL + AI settings migration should be present.',3),
    sf_rc_check('QA',$qaFails === 0 ? 'pass' : 'fail','Production QA gate',$qaFails === 0 ? 'No blocking QA failures detected.' : $qaFails . ' QA failures detected.',4,'admin/qa.php'),
    sf_rc_check('Package',$pkgFails === 0 ? 'pass' : 'fail','Package readiness gate',$pkgFails === 0 ? 'No package readiness failures detected.' : $pkgFails . ' package failures detected.',4,'admin/package-readiness.php'),
    sf_rc_check('Smoke',$smokeFails === 0 ? ($manualSmoke ? 'manual' : 'pass') : 'fail','Smoke-test gate',$smokeFails === 0 ? ($manualSmoke . ' manual smoke checks remain for live verification.') : $smokeFails . ' smoke-test failures detected.',4,'admin/smoke-tests.php'),
    sf_rc_check('Preflight',sf_qa_file_exists('deploy/preflight.php') ? 'pass' : 'fail','Deployment preflight','deploy/preflight.php should run without blocking failures.',4,'deploy/preflight.php'),
    sf_rc_check('Backup',$backupReady ? 'pass' : ($backup ? 'warn' : 'manual'),'Latest backup record',$backup ? ('Latest backup ' . ($backup['run_key'] ?? '#'.$backup['id']) . ' is ' . $backupStatus . '.') : 'Create a completed or verified backup before deploy.',4,'admin/backups.php'),
    sf_rc_check('Release',$releaseReady ? 'pass' : ($release ? 'warn' : 'manual'),'Release record',$release ? ('Latest release ' . ($release['release_label'] ?? '#'.$release['id']) . ' is ' . $releaseStatus . '.') : 'Create a release record for this deploy.',4,'admin/releases.php'),
    sf_rc_check('Release',$taskReady ? 'pass' : ($release ? 'warn' : 'manual'),'Release checklist tasks',$release ? ((int)$taskSummary['passed'] . ' passed, ' . (int)$taskSummary['waived'] . ' waived, ' . (int)$taskSummary['pending'] . ' pending, ' . (int)$taskSummary['failed'] . ' failed.') : 'Release checklist tasks are created after a release record exists.',3,'admin/releases.php'),
    sf_rc_check('Docs',sf_qa_file_exists('docs/DEPLOYMENT_RUNBOOK.md') ? 'pass' : 'fail','Deployment runbook','Final deployment runbook is present.',2,'docs/DEPLOYMENT_RUNBOOK.md'),
    sf_rc_check('Docs',sf_qa_file_exists('docs/PHASE_39_RELEASE_CANDIDATE.md') ? 'pass' : 'fail','Phase 39 docs','Release candidate handoff documentation is present.',2,'docs/PHASE_39_RELEASE_CANDIDATE.md'),
    sf_rc_check('Docs',sf_qa_file_exists('docs/PHASE_41_STORYBOARDING_SQL_AI_SETTINGS.md') ? 'pass' : 'fail','Phase 41 docs','Storyboarding SQL and AI settings documentation is present.',2,'docs/PHASE_41_STORYBOARDING_SQL_AI_SETTINGS.md'),
  ];
}
function sf_rc_score(array $checks): int { return sf_qa_score($checks); }
function sf_rc_grade(int $score): string { return sf_qa_grade($score); }
function sf_rc_counts(array $checks): array { return ['total'=>count($checks),'pass'=>count(array_filter($checks, static fn($c) => ($c['status'] ?? '') === 'pass')),'fail'=>count(array_filter($checks, static fn($c) => in_array(($c['status'] ?? ''), ['fail','missing'], true))),'warn'=>count(array_filter($checks, static fn($c) => in_array(($c['status'] ?? ''), ['warn','preview'], true))),'manual'=>count(array_filter($checks, static fn($c) => ($c['status'] ?? '') === 'manual'))]; }
function sf_rc_group_summary(array $checks): array {
  $summary = [];
  foreach ($checks as $check) { $group = (string)($check['group'] ?? 'General'); if (!isset($summary[$group])) $summary[$group] = ['group'=>$group,'count'=>0,'fails'=>0,'warnings'=>0,'manual'=>0,'score'=>0,'checks'=>[]]; $summary[$group]['count']++; $summary[$group]['checks'][] = $check; if (in_array(($check['status'] ?? ''), ['fail','missing'], true)) $summary[$group]['fails']++; if (in_array(($check['status'] ?? ''), ['warn','preview'], true)) $summary[$group]['warnings']++; if (($check['status'] ?? '') === 'manual') $summary[$group]['manual']++; }
  foreach ($summary as $group => $data) $summary[$group]['score'] = sf_rc_score($data['checks']);
  return array_values($summary);
}
function sf_rc_status_text(array $checks): string { $counts = sf_rc_counts($checks); $score = sf_rc_score($checks); if ((int)$counts['fail'] > 0) return 'blocked'; if ((int)$counts['warn'] > 0 || (int)$counts['manual'] > 0) return 'manual_review'; return $score >= 97 ? 'release_candidate' : 'needs_review'; }
function sf_rc_summary(): array {
  $qaChecks = sf_qa_flatten(sf_qa_all_checks());
  $pkgChecks = sf_pkg_checks();
  $smokeChecks = sf_smoke_checks();
  $rcChecks = sf_rc_checks();
  return ['repo'=>sf_rc_repo_full_name(),'branch'=>sf_rc_target_branch(),'zip_url'=>sf_rc_zip_url(),'migration_target'=>sf_rc_target_migration(),'qa_score'=>sf_qa_score($qaChecks),'package_score'=>sf_pkg_score($pkgChecks),'smoke_score'=>sf_smoke_score($smokeChecks),'release_candidate_score'=>sf_rc_score($rcChecks),'release_candidate_status'=>sf_rc_status_text($rcChecks),'counts'=>sf_rc_counts($rcChecks)];
}
function sf_rc_render_table(array $checks): void {
  echo '<div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Gate</th><th>Check</th><th>Status</th><th>Detail</th><th>Open</th></tr></thead><tbody>';
  foreach ($checks as $check) { $url = trim((string)($check['url'] ?? '')); echo '<tr><td>' . sf_rc_h($check['group'] ?? '') . '</td><td><strong>' . sf_rc_h($check['label'] ?? '') . '</strong></td><td>' . sf_qa_badge((string)($check['status'] ?? 'info')) . '</td><td>' . sf_rc_h($check['detail'] ?? '') . '</td><td>' . ($url ? '<a href="' . sf_rc_h(strpos($url, 'http') === 0 ? $url : sf_url($url)) . '">Open</a>' : '—') . '</td></tr>'; }
  echo '</tbody></table></div>';
}
function sf_rc_h($value): string { return sf_qa_h($value); }
?>
