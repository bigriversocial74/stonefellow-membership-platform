<?php

declare(strict_types=1);
putenv('SF_SKIP_INSTALL_REDIRECT=1');
putenv('SF_ENV=testing');
putenv('SF_APP_KEY='.str_repeat('a',64));
putenv('SF_HASH_SALT='.str_repeat('b',40));
putenv('SF_PRODUCTION_DEPLOYMENT_EVENT_SECRET='.str_repeat('c',40));
require_once __DIR__.'/../includes/production_launch.php';
$fail=[];$assert=static function(bool $ok,string $message)use(&$fail):void{if(!$ok)$fail[]=$message;};
$catalog=sf_prod_check_catalog();
$assert(count($catalog)>=20,'Production launch catalog should cover all launch phases.');
foreach(['pre_deploy','deploy','post_deploy','rollback'] as $phase)$assert((bool)array_filter($catalog,static fn($c)=>($c['phase']??'')===$phase),'Missing phase '.$phase);
$assert(sf_prod_approval_types()===['technical','operations','security','business'],'Four approval roles are required.');
$assert(in_array('verified',sf_prod_statuses(),true)&&in_array('rolled_back',sf_prod_statuses(),true),'Terminal states should be defined.');
$raw='{"promotion_key":"test","event_id":"evt_1"}';$signature=hash_hmac('sha256',$raw,(string)getenv('SF_PRODUCTION_DEPLOYMENT_EVENT_SECRET'));
$assert(sf_prod_signature_valid($raw,$signature),'Valid production deployment signature should pass.');
$assert(!sf_prod_signature_valid($raw,str_repeat('0',64)),'Invalid production deployment signature should fail.');
$root=dirname(__DIR__);$markers=[
 'database/production_launch_promotion_v1.sql'=>['production_launch_promotions','production_launch_approvals','production_launch_checks','production_launch_events','target_commit_sha','artifact_sha256'],
 'includes/production_launch.php'=>['sf_prod_binding_gate','sf_prod_approval_gate','sf_prod_phase_gate','sf_prod_transition','Each approval must use a distinct approver','sf_prod_latest_for_sha'],
 'admin/production-launch.php'=>['Independent Approval','Approved package evidence','Evaluate Transition','Event Ledger','sf_csrf_field'],
 'api/production-deployment-event.php'=>['production_only','HTTP_X_STONEFELLOW_DEPLOYMENT_SIGNATURE','commit_mismatch',"'status'=>'duplicate'"],
 'deploy/preflight.php'=>['Production Launch Promotion Score','No approved, deploying, deployed, or verified production promotion','SF_RELEASE_COMMIT_SHA'],
 'includes/release_candidate.php'=>['100% launch certification','Integration scenario coverage','Approved production promotion'],
 '.env.example'=>['SF_RELEASE_COMMIT_SHA','SF_PRODUCTION_LAUNCH_REQUIRE_DISTINCT_APPROVERS=1','SF_PRODUCTION_DEPLOYMENT_EVENT_SECRET'],
];
foreach($markers as $file=>$needles){$body=(string)file_get_contents($root.'/'.$file);foreach($needles as $needle)$assert(stripos($body,$needle)!==false,$file.' missing '.$needle);}
if($fail){fwrite(STDERR,"Production launch smoke failures:\n- ".implode("\n- ",$fail)."\n");exit(1);}echo "Production launch smoke: PASS\n";
