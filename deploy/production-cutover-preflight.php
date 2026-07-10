<?php

declare(strict_types=1);
putenv('SF_SKIP_INSTALL_REDIRECT=1');
require_once __DIR__.'/../includes/production_cutover.php';
$sha=strtolower(trim((string)(getenv('SF_RELEASE_COMMIT_SHA')?:'')));$run=sf_pch_latest_completed();$certificate=sf_pch_latest_certificate();$checks=[
 ['Production cutover SQL',sf_pch_ready(),'Import database/migrations/026_production_cutover_hypercare.sql.'],
 ['Exact production commit',preg_match('/^[a-f0-9]{40}$/',$sha)===1,'Configure SF_RELEASE_COMMIT_SHA.'],
 ['Completed production cutover',$run!==null,'Complete the production cutover command center.'],
 ['Exact commit match',$run&&$sha&&hash_equals($sha,strtolower((string)$run['target_commit_sha'])),'Completed cutover must match the deployed commit.'],
 ['Production verification certificate',$certificate!==null,'Issue the production verification certificate after 72-hour hypercare.'],
 ['No rollback recommendation',$run&&!$run['rollback_recommended'],'Resolve thresholds or execute rollback.'],
];$failed=[];echo "Stonefellow Production Cutover Preflight\n".str_repeat('=',72)."\n";foreach($checks as[$label,$ok,$detail]){echo sprintf("%-42s %s\n",$label,$ok?'PASS':'FAIL');if(!$ok)$failed[]=$detail;}if($failed){echo "\nBlocking findings:\n- ".implode("\n- ",$failed)."\n";exit(1);}echo "Result: PASS — production cutover, hypercare, certificate, and exact commit match.\n";
