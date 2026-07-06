<?php
require_once __DIR__ . '/../includes/qa.php';
header('Content-Type: text/plain; charset=utf-8');
$checks = sf_qa_flatten(sf_qa_all_checks());
$score = sf_qa_score($checks);
$fails = array_values(array_filter($checks, fn($c) => ($c['status'] ?? '') === 'fail'));
$warnings = array_values(array_filter($checks, fn($c) => ($c['status'] ?? '') === 'warning'));
echo "Stonefellow Deployment Preflight\n";
echo "Score: {$score}%\n";
echo "Failures: " . count($fails) . "\n";
echo "Warnings: " . count($warnings) . "\n\n";
foreach ($fails as $fail) echo "FAIL: " . ($fail['label'] ?? 'check') . " - " . ($fail['detail'] ?? '') . "\n";
foreach ($warnings as $warning) echo "WARN: " . ($warning['label'] ?? 'check') . " - " . ($warning['detail'] ?? '') . "\n";
exit(count($fails) > 0 ? 1 : 0);
