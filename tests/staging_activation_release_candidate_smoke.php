<?php

declare(strict_types=1);
putenv('SF_SKIP_INSTALL_REDIRECT=1');
putenv('SF_ENV=testing');
putenv('SF_APP_KEY='.str_repeat('a',64));
putenv('SF_HASH_SALT='.str_repeat('b',40));
require_once __DIR__.'/../includes/staging_activation.php';
$fail=[];$assert=static function(bool $ok,string $message)use(&$fail):void{if(!$ok)$fail[]=$message;};
$catalog=sf_sa_catalog();$sections=array_values(array_unique(array_column($catalog,'section')));
$assert(count($sections)===10,'Staging activation must contain exactly ten sections.');
$assert(count($catalog)>=30,'Staging activation must contain a complete blocker catalog.');
$assert(isset($catalog['catalog.snapshot'],$catalog['media.video_assets'],$catalog['commerce.merch_transaction'],$catalog['recovery.restore'],$catalog['release.approvals']),'Critical activation checks are missing.');
$assert(sf_sa_locked(['run_status'=>'passed']),'Passed activation runs must be immutable.');
$assert(!sf_sa_locked(['run_status'=>'running']),'Running activation runs must remain editable.');
$assert(function_exists('sf_sa_candidate_gate')&&function_exists('sf_sa_create_candidate')&&function_exists('sf_sa_freeze_candidate'),'Release-candidate gate functions are missing.');
$root=dirname(__DIR__);$markers=[
 'database/migrations/025_staging_activation_release_candidate.sql'=>['staging_activation_runs','staging_release_candidates','artifact_sha256'],
 'admin/staging-activation.php'=>['Blocker Board','Run Automated Checks','Freeze Candidate'],
 'api/staging-activation-status.php'=>['invalid_signature','hash_hmac','operationally_ready'],
 'deploy/staging-activation-preflight.php'=>['Frozen release candidate','Exact release commit match'],
 '.github/workflows/code-audit.yml'=>['staging_activation_release_candidate_smoke.php','staging-activation-release-candidate-audit.php'],
];
foreach($markers as$file=>$needles){$body=is_file($root.'/'.$file)?(string)file_get_contents($root.'/'.$file):'';foreach($needles as$needle)$assert($body!==''&&stripos($body,$needle)!==false,$file.' missing '.$needle);}
if($fail){fwrite(STDERR,"Staging activation release candidate smoke failures:\n- ".implode("\n- ",$fail)."\n");exit(1);}echo "Staging activation and release candidate smoke: PASS\n";
