<?php
require_once __DIR__.'/../includes/release_candidate.php';
require_once __DIR__.'/../includes/data_ops_recovery.php';
require_once __DIR__.'/../includes/staging_launch_certification.php';
require_once __DIR__.'/../includes/production_launch.php';
require_once __DIR__.'/../includes/platform_certification.php';
header('Content-Type: text/plain; charset=utf-8');
$qaSections=sf_qa_all_checks();$qaChecks=sf_qa_flatten($qaSections);$pkgChecks=sf_pkg_checks();$smokeChecks=sf_smoke_checks();$rcChecks=sf_rc_checks();
$opsChecks=array_map(static function(array $check): array{$check['group']=$check['section']??'Operations';return $check;},sf_dor_operations_checks());
$environment=sf_dor_env();$production=$environment==='production';$releaseSha=strtolower(trim((string)(getenv('SF_RELEASE_COMMIT_SHA')?:'')));
$platformSourceChecks=sf_pc_static_checks();$platformSourceScore=sf_pc_score($platformSourceChecks);$platformChecks=[['group'=>'Whole Platform Source Certification','label'=>'Cumulative source and CI certification','status'=>$platformSourceScore===100?'pass':'fail','detail'=>$platformSourceScore===100?'All cumulative source sections score 10/10.':'Whole-platform source score is '.$platformSourceScore.'%; resolve missing audit files or markers.']];
$slcRun=sf_slc_latest_passed();$slcStatus='pass';$slcDetail='A current 100% staging launch certification is available.';
if(!$slcRun){$slcStatus=$production?'fail':'warn';$slcDetail='No 100% passed Staging Launch Certification exists.';}
elseif($releaseSha!==''&&($slcRun['target_commit_sha']??'')!==$releaseSha){$slcStatus='fail';$slcDetail='Passed certification commit does not match SF_RELEASE_COMMIT_SHA.';}
$slcChecks=[['group'=>'Staging Launch Certification','label'=>'100% deployed certification for release commit','status'=>$slcStatus,'detail'=>$slcDetail]];
$promotion=null;$promotionStatus='warn';$promotionDetail='Set SF_RELEASE_COMMIT_SHA and approve a production launch promotion.';
if(!preg_match('/^[a-f0-9]{40}$/',$releaseSha)){$promotionStatus=$production?'fail':'warn';$promotionDetail='SF_RELEASE_COMMIT_SHA must be the exact 40-character deployed commit.';}
else{$promotion=sf_prod_latest_for_sha($releaseSha);if($promotion){$promotionStatus='pass';$promotionDetail='Promotion '.$promotion['promotion_key'].' status '.$promotion['promotion_status'].' matches the release commit.';}elseif($production){$promotionStatus='fail';$promotionDetail='No approved, deploying, deployed, or verified production promotion matches SF_RELEASE_COMMIT_SHA.';}}
$promotionChecks=[['group'=>'Production Launch Promotion','label'=>'Approved promotion for exact release commit','status'=>$promotionStatus,'detail'=>$promotionDetail]];
$allChecks=array_merge($platformChecks,$qaChecks,$pkgChecks,$smokeChecks,$rcChecks,$opsChecks,$slcChecks,$promotionChecks);
$qaScore=sf_qa_score($qaChecks);$pkgScore=sf_pkg_score($pkgChecks);$smokeScore=sf_smoke_score($smokeChecks);$rcScore=sf_rc_score($rcChecks);$opsScore=sf_dor_score($opsChecks);$slcScore=$slcStatus==='pass'?100:0;$promotionScore=$promotionStatus==='pass'?100:0;$overallScore=sf_qa_score($allChecks);
$fails=array_values(array_filter($allChecks,static fn($c)=>in_array(($c['status']??''),['fail','missing'],true)));$warnings=array_values(array_filter($allChecks,static fn($c)=>in_array(($c['status']??''),['warn','preview','manual'],true)));
$manifest=sf_pkg_manifest_summary();$smokeCounts=sf_smoke_counts($smokeChecks);$rcSummary=sf_rc_summary();
echo "Stonefellow Deployment Preflight\n";
echo 'Generated: '.date('c')."\n";
echo 'Environment: '.$environment."\n";
echo 'Repository: '.sf_rc_repo_full_name()."\n";
echo 'Target Branch: '.sf_rc_target_branch()."\n";
echo 'Target Migration: '.sf_dor_latest_migration_key()."\n";
echo 'Release Commit: '.($releaseSha?:'missing')."\n";
echo "Overall Score: {$overallScore}%\nWhole Platform Source Certification Score: {$platformSourceScore}%\nQA Score: {$qaScore}%\nPackage Readiness Score: {$pkgScore}%\nSmoke Test Score: {$smokeScore}%\nRelease Candidate Score: {$rcScore}%\nOperations & Recovery Score: {$opsScore}%\nStaging Launch Certification Score: {$slcScore}%\nProduction Launch Promotion Score: {$promotionScore}%\n";
echo 'Release Candidate Status: '.$rcSummary['release_candidate_status']."\n";
echo 'Certified Commit: '.($slcRun['target_commit_sha']??'none')."\n";
echo 'Promotion: '.($promotion['promotion_key']??'none')."\n";
echo 'Required Files Present: '.(int)$manifest['present'].' / '.(int)$manifest['total']."\n";
echo 'Smoke Scenarios: '.(int)$smokeCounts['total'].' total, '.(int)$smokeCounts['fail'].' fail, '.(int)$smokeCounts['warn'].' warn, '.(int)$smokeCounts['manual']." manual\n";
echo 'Failures: '.count($fails)."\nReview Items: ".count($warnings)."\n\n";
echo "Whole Platform Source Certification\n-----------------------------------\n";foreach(sf_pc_section_summary($platformSourceChecks) as $section)echo $section['section'].': '.(int)$section['score'].'% · passed '.(int)$section['passed'].' / '.(int)$section['total']."\n";
echo "\nQA Sections\n-----------\n";foreach(sf_qa_section_summary() as $section)echo $section['section'].': '.(int)$section['score'].'% · checks '.(int)$section['count'].' · fails '.(int)$section['fails'].' · review '.(int)$section['warnings']."\n";
echo "\nPackage Readiness\n-----------------\n";foreach(sf_pkg_group_summary($pkgChecks) as $group)echo $group['group'].': '.(int)$group['score'].'% · checks '.(int)$group['count'].' · fails '.(int)$group['fails'].' · review '.(int)$group['warnings']."\n";
echo "\nSmoke Tests\n-----------\n";foreach(sf_smoke_group_summary($smokeChecks) as $group)echo $group['group'].': '.(int)$group['score'].'% · scenarios '.(int)$group['count'].' · fails '.(int)$group['fails'].' · warnings '.(int)$group['warnings'].' · manual '.(int)$group['manual']."\n";
echo "\nRelease Candidate\n-----------------\n";foreach(sf_rc_group_summary($rcChecks) as $group)echo $group['group'].': '.(int)$group['score'].'% · checks '.(int)$group['count'].' · fails '.(int)$group['fails'].' · warnings '.(int)$group['warnings'].' · manual '.(int)$group['manual']."\n";
echo "\nOperations & Recovery\n---------------------\n";foreach(sf_dor_section_summary($opsChecks) as $section)echo $section['section'].': '.(int)$section['score'].'% · checks '.(int)$section['count'].' · fails '.(int)$section['fails'].' · review '.(int)$section['warnings']."\n";
echo "\nStaging Launch Certification\n----------------------------\n".strtoupper($slcStatus).': '.$slcDetail."\n";
echo "\nProduction Launch Promotion\n---------------------------\n".strtoupper($promotionStatus).': '.$promotionDetail."\n";
if($fails){echo "\nBlocking Failures\n-----------------\n";foreach($fails as $fail)echo 'FAIL: '.($fail['label']??$fail['scenario']??'check').' - '.($fail['detail']??'')."\n";}
if($warnings){echo "\nReview Items\n------------\n";foreach($warnings as $warning)echo 'WARN: '.($warning['label']??$warning['scenario']??'check').' - '.($warning['detail']??'')."\n";}
echo "\nLaunch Gate\n-----------\n";echo count($fails)>0?"BLOCKED: Resolve all source, data, backup, release, recovery, certification, and promotion failures before launch.\n":"READY: Source certification is 10/10 with no blocking launch failures. Complete phase-appropriate deployment verification.\n";
exit(count($fails)>0?1:0);
