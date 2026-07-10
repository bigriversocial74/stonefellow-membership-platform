<?php
require_once __DIR__.'/../includes/release_candidate.php';
require_once __DIR__.'/../includes/data_ops_recovery.php';
require_once __DIR__.'/../includes/staging_launch_certification.php';
header('Content-Type: text/plain; charset=utf-8');
$qaSections=sf_qa_all_checks();$qaChecks=sf_qa_flatten($qaSections);$pkgChecks=sf_pkg_checks();$smokeChecks=sf_smoke_checks();$rcChecks=sf_rc_checks();
$opsChecks=array_map(static function(array $check): array{$check['group']=$check['section']??'Operations';return $check;},sf_dor_operations_checks());
$slcRun=sf_slc_latest_passed();$releaseSha=strtolower(trim((string)(getenv('SF_RELEASE_COMMIT_SHA')?:'')));$slcStatus='pass';$slcDetail='A current 100% staging launch certification is available.';
if(!$slcRun){$slcStatus=sf_dor_env()==='production'?'fail':'warn';$slcDetail='No 100% passed Staging Launch Certification exists.';}
elseif($releaseSha!==''&&($slcRun['target_commit_sha']??'')!==$releaseSha){$slcStatus='fail';$slcDetail='Passed certification commit does not match SF_RELEASE_COMMIT_SHA.';}
$slcChecks=[['group'=>'Staging Launch Certification','label'=>'100% deployed certification for release commit','status'=>$slcStatus,'detail'=>$slcDetail]];
$allChecks=array_merge($qaChecks,$pkgChecks,$smokeChecks,$rcChecks,$opsChecks,$slcChecks);
$qaScore=sf_qa_score($qaChecks);$pkgScore=sf_pkg_score($pkgChecks);$smokeScore=sf_smoke_score($smokeChecks);$rcScore=sf_rc_score($rcChecks);$opsScore=sf_dor_score($opsChecks);$slcScore=$slcStatus==='pass'?100:0;$overallScore=sf_qa_score($allChecks);
$fails=array_values(array_filter($allChecks,static fn($c)=>in_array(($c['status']??''),['fail','missing'],true)));$warnings=array_values(array_filter($allChecks,static fn($c)=>in_array(($c['status']??''),['warn','preview','manual'],true)));
$manifest=sf_pkg_manifest_summary();$smokeCounts=sf_smoke_counts($smokeChecks);$rcSummary=sf_rc_summary();
echo "Stonefellow Deployment Preflight\n";
echo 'Generated: '.date('c')."\n";
echo 'Environment: '.sf_dor_env()."\n";
echo 'Repository: '.sf_rc_repo_full_name()."\n";
echo 'Target Branch: '.sf_rc_target_branch()."\n";
echo 'Target Migration: '.sf_dor_latest_migration_key()."\n";
echo "Overall Score: {$overallScore}%\nQA Score: {$qaScore}%\nPackage Readiness Score: {$pkgScore}%\nSmoke Test Score: {$smokeScore}%\nRelease Candidate Score: {$rcScore}%\nOperations & Recovery Score: {$opsScore}%\nStaging Launch Certification Score: {$slcScore}%\n";
echo 'Release Candidate Status: '.$rcSummary['release_candidate_status']."\n";
echo 'Certified Commit: '.($slcRun['target_commit_sha']??'none')."\n";
echo 'Required Files Present: '.(int)$manifest['present'].' / '.(int)$manifest['total']."\n";
echo 'Smoke Scenarios: '.(int)$smokeCounts['total'].' total, '.(int)$smokeCounts['fail'].' fail, '.(int)$smokeCounts['warn'].' warn, '.(int)$smokeCounts['manual']." manual\n";
echo 'Failures: '.count($fails)."\nReview Items: ".count($warnings)."\n\n";
echo "QA Sections\n-----------\n";foreach(sf_qa_section_summary() as $section)echo $section['section'].': '.(int)$section['score'].'% · checks '.(int)$section['count'].' · fails '.(int)$section['fails'].' · review '.(int)$section['warnings']."\n";
echo "\nPackage Readiness\n-----------------\n";foreach(sf_pkg_group_summary($pkgChecks) as $group)echo $group['group'].': '.(int)$group['score'].'% · checks '.(int)$group['count'].' · fails '.(int)$group['fails'].' · review '.(int)$group['warnings']."\n";
echo "\nSmoke Tests\n-----------\n";foreach(sf_smoke_group_summary($smokeChecks) as $group)echo $group['group'].': '.(int)$group['score'].'% · scenarios '.(int)$group['count'].' · fails '.(int)$group['fails'].' · warnings '.(int)$group['warnings'].' · manual '.(int)$group['manual']."\n";
echo "\nRelease Candidate\n-----------------\n";foreach(sf_rc_group_summary($rcChecks) as $group)echo $group['group'].': '.(int)$group['score'].'% · checks '.(int)$group['count'].' · fails '.(int)$group['fails'].' · warnings '.(int)$group['warnings'].' · manual '.(int)$group['manual']."\n";
echo "\nOperations & Recovery\n---------------------\n";foreach(sf_dor_section_summary($opsChecks) as $section)echo $section['section'].': '.(int)$section['score'].'% · checks '.(int)$section['count'].' · fails '.(int)$section['fails'].' · review '.(int)$section['warnings']."\n";
echo "\nStaging Launch Certification\n----------------------------\n";echo strtoupper($slcStatus).': '.$slcDetail."\n";
if($fails){echo "\nBlocking Failures\n-----------------\n";foreach($fails as $fail)echo 'FAIL: '.($fail['label']??$fail['scenario']??'check').' - '.($fail['detail']??'')."\n";}
if($warnings){echo "\nReview Items\n------------\n";foreach($warnings as $warning)echo 'WARN: '.($warning['label']??$warning['scenario']??'check').' - '.($warning['detail']??'')."\n";}
echo "\nLaunch Gate\n-----------\n";echo count($fails)>0?"BLOCKED: Resolve all code, data, backup, release, recovery, and deployed certification failures before launch.\n":"READY: No blocking failures detected. Complete remaining manual checks before launch.\n";
exit(count($fails)>0?1:0);
