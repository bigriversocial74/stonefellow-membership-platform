<?php
require_once __DIR__ . '/../includes/package_readiness.php';
header('Content-Type: text/plain; charset=utf-8');
$qaSections = sf_qa_all_checks();
$qaChecks = sf_qa_flatten($qaSections);
$pkgChecks = sf_pkg_checks();
$allChecks = array_merge($qaChecks, $pkgChecks);
$qaScore = sf_qa_score($qaChecks);
$pkgScore = sf_pkg_score($pkgChecks);
$overallScore = sf_qa_score($allChecks);
$fails = array_values(array_filter($allChecks, static fn($c) => in_array(($c['status'] ?? ''), ['fail','missing'], true)));
$warnings = array_values(array_filter($allChecks, static fn($c) => in_array(($c['status'] ?? ''), ['warn','preview','manual'], true)));
$manifest = sf_pkg_manifest_summary();
echo "Stonefellow Deployment Preflight\n";
echo "Generated: " . date('c') . "\n";
echo "Target Migration: 020\n";
echo "Overall Score: {$overallScore}%\n";
echo "QA Score: {$qaScore}%\n";
echo "Package Readiness Score: {$pkgScore}%\n";
echo "Required Files Present: " . (int)$manifest['present'] . " / " . (int)$manifest['total'] . "\n";
echo "Failures: " . count($fails) . "\n";
echo "Review Items: " . count($warnings) . "\n\n";
echo "QA Sections\n";
echo "-----------\n";
foreach (sf_qa_section_summary() as $section) {
  echo $section['section'] . ': ' . (int)$section['score'] . '% · checks ' . (int)$section['count'] . ' · fails ' . (int)$section['fails'] . ' · review ' . (int)$section['warnings'] . "\n";
}
echo "\nPackage Readiness\n";
echo "-----------------\n";
foreach (sf_pkg_group_summary($pkgChecks) as $group) {
  echo $group['group'] . ': ' . (int)$group['score'] . '% · checks ' . (int)$group['count'] . ' · fails ' . (int)$group['fails'] . ' · review ' . (int)$group['warnings'] . "\n";
}
if ($fails) {
  echo "\nBlocking Failures\n";
  echo "-----------------\n";
  foreach ($fails as $fail) echo "FAIL: " . ($fail['label'] ?? 'check') . " - " . ($fail['detail'] ?? '') . "\n";
}
if ($warnings) {
  echo "\nReview Items\n";
  echo "------------\n";
  foreach ($warnings as $warning) echo "WARN: " . ($warning['label'] ?? 'check') . " - " . ($warning['detail'] ?? '') . "\n";
}
echo "\nLaunch Gate\n";
echo "-----------\n";
echo count($fails) > 0 ? "BLOCKED: Resolve failures before launch.\n" : "READY: No blocking failures detected. Complete manual smoke tests before launch.\n";
exit(count($fails) > 0 ? 1 : 0);
