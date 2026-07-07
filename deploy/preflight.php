<?php
require_once __DIR__ . '/../includes/release_candidate.php';
header('Content-Type: text/plain; charset=utf-8');
$qaSections = sf_qa_all_checks();
$qaChecks = sf_qa_flatten($qaSections);
$pkgChecks = sf_pkg_checks();
$smokeChecks = sf_smoke_checks();
$rcChecks = sf_rc_checks();
$allChecks = array_merge($qaChecks, $pkgChecks, $smokeChecks, $rcChecks);
$qaScore = sf_qa_score($qaChecks);
$pkgScore = sf_pkg_score($pkgChecks);
$smokeScore = sf_smoke_score($smokeChecks);
$rcScore = sf_rc_score($rcChecks);
$overallScore = sf_qa_score($allChecks);
$fails = array_values(array_filter($allChecks, static fn($c) => in_array(($c['status'] ?? ''), ['fail','missing'], true)));
$warnings = array_values(array_filter($allChecks, static fn($c) => in_array(($c['status'] ?? ''), ['warn','preview','manual'], true)));
$manifest = sf_pkg_manifest_summary();
$smokeCounts = sf_smoke_counts($smokeChecks);
$rcSummary = sf_rc_summary();
echo "Stonefellow Deployment Preflight\n";
echo "Generated: " . date('c') . "\n";
echo "Repository: " . sf_rc_repo_full_name() . "\n";
echo "Target Branch: " . sf_rc_target_branch() . "\n";
echo "Target Migration: " . sf_rc_target_migration() . "\n";
echo "Overall Score: {$overallScore}%\n";
echo "QA Score: {$qaScore}%\n";
echo "Package Readiness Score: {$pkgScore}%\n";
echo "Smoke Test Score: {$smokeScore}%\n";
echo "Release Candidate Score: {$rcScore}%\n";
echo "Release Candidate Status: " . $rcSummary['release_candidate_status'] . "\n";
echo "Required Files Present: " . (int)$manifest['present'] . " / " . (int)$manifest['total'] . "\n";
echo "Smoke Scenarios: " . (int)$smokeCounts['total'] . " total, " . (int)$smokeCounts['fail'] . " fail, " . (int)$smokeCounts['warn'] . " warn, " . (int)$smokeCounts['manual'] . " manual\n";
echo "Failures: " . count($fails) . "\n";
echo "Review Items: " . count($warnings) . "\n\n";
echo "QA Sections\n-----------\n";
foreach (sf_qa_section_summary() as $section) echo $section['section'] . ': ' . (int)$section['score'] . '% · checks ' . (int)$section['count'] . ' · fails ' . (int)$section['fails'] . ' · review ' . (int)$section['warnings'] . "\n";
echo "\nPackage Readiness\n-----------------\n";
foreach (sf_pkg_group_summary($pkgChecks) as $group) echo $group['group'] . ': ' . (int)$group['score'] . '% · checks ' . (int)$group['count'] . ' · fails ' . (int)$group['fails'] . ' · review ' . (int)$group['warnings'] . "\n";
echo "\nSmoke Tests\n-----------\n";
foreach (sf_smoke_group_summary($smokeChecks) as $group) echo $group['group'] . ': ' . (int)$group['score'] . '% · scenarios ' . (int)$group['count'] . ' · fails ' . (int)$group['fails'] . ' · warnings ' . (int)$group['warnings'] . ' · manual ' . (int)$group['manual'] . "\n";
echo "\nRelease Candidate\n-----------------\n";
foreach (sf_rc_group_summary($rcChecks) as $group) echo $group['group'] . ': ' . (int)$group['score'] . '% · checks ' . (int)$group['count'] . ' · fails ' . (int)$group['fails'] . ' · warnings ' . (int)$group['warnings'] . ' · manual ' . (int)$group['manual'] . "\n";
if ($fails) {
  echo "\nBlocking Failures\n-----------------\n";
  foreach ($fails as $fail) echo "FAIL: " . ($fail['label'] ?? $fail['scenario'] ?? 'check') . " - " . ($fail['detail'] ?? '') . "\n";
}
if ($warnings) {
  echo "\nReview Items\n------------\n";
  foreach ($warnings as $warning) echo "WARN: " . ($warning['label'] ?? $warning['scenario'] ?? 'check') . " - " . ($warning['detail'] ?? '') . "\n";
}
echo "\nLaunch Gate\n-----------\n";
echo count($fails) > 0 ? "BLOCKED: Resolve failures before launch.\n" : "READY: No blocking failures detected. Complete manual release-candidate checks before launch.\n";
exit(count($fails) > 0 ? 1 : 0);
