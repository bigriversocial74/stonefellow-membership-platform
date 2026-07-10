<?php

declare(strict_types=1);
$root=dirname(__DIR__);$sections=[
 'Exact Candidate & Artifact'=>[['includes/production_cutover_core.php',['staging_candidate_id','artifact_sha256','hash_equals']]],
 'Deployment Command Center'=>[['admin/production-cutover.php',['Go / Hold / Rollback','Traffic Activation','Maintenance Window']]],
 'Synthetic Verification'=>[['includes/production_cutover_core.php',['auth.lifecycle','commerce.membership','browser.synthetic']]],
 'Commerce & Membership'=>[['includes/production_cutover_core.php',['commerce.merchandise','commerce.reconciliation']]],
 'Protected Media'=>[['includes/production_cutover_core.php',['media.audio','media.video','media.storage']]],
 'Monitoring & Incidents'=>[['includes/production_cutover_checks.php',['monitoring.baseline','monitoring.incidents','sf_pch_critical_incidents']]],
 'Rollback Safety'=>[['includes/production_cutover_checks.php',['sf_pch_thresholds','sf_pch_record_decision']],['includes/production_cutover_hypercare.php',['rollback.recommended','sf_pch_evaluate_thresholds']]],
 'Hypercare Checkpoints'=>[['includes/production_cutover_hypercare.php',["'15m'","'1h'","'6h'","'24h'","'72h'"]]],
 'Production Certificate'=>[['includes/production_cutover_hypercare.php',['sf_pch_issue_certificate','certificate_sha256','handoff_notes']]],
 'Operations & CI'=>[['tests/production_cutover_hypercare_smoke.php',['PASS']],['deploy/production-cutover-preflight.php',['Production Cutover Preflight']],['.github/workflows/code-audit.yml',['production-cutover-hypercare-audit.php']]],
];$fail=[];echo "Stonefellow Production Cutover & Hypercare v1 Audit\n".str_repeat('=',72)."\n";foreach($sections as$name=>$checks){$passed=0;$total=0;foreach($checks as[$path,$markers]){foreach($markers as$m){$total++;$body=is_file($root.'/'.$path)?(string)file_get_contents($root.'/'.$path):'';if($body!==''&&stripos($body,$m)!==false)$passed++;else$fail[]=$name.': '.$path.' missing '.$m;}}$score=$total?(int)round($passed/$total*10):0;echo sprintf("%-42s %d/10 (%d/%d)\n",$name,$score,$passed,$total);if($score!==10)$fail[]=$name.' is '.$score.'/10.';}if($fail){echo "\nBlocking findings:\n- ".implode("\n- ",array_unique($fail))."\n";exit(1);}echo "Result: PASS — all ten production cutover and hypercare sections score 10/10.\n";
