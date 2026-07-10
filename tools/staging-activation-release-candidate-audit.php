<?php

declare(strict_types=1);
$root=dirname(__DIR__);$sections=[
 'Environment & Secrets'=>[['.env.staging-activation.example',['SF_STAGING_ACTIVATION_SECRET','SF_RELEASE_COMMIT_SHA']],['includes/staging_activation_checks.php',['environment.staging_mode','environment.secrets','environment.no_shortcuts']]],
 'Real Catalog'=>[['includes/staging_activation_checks.php',['catalog.snapshot','catalog.real_records','catalog.no_samples']],['admin/staging-activation.php',['catalog-operations.php','Blocker Board']]],
 'Media & Playback'=>[['includes/staging_activation_checks.php',['media.runtime','media.audio_assets','media.video_assets']],['includes/staging_activation_core.php',['media.playback']],['docs/STAGING_ACTIVATION_RELEASE_CANDIDATE_V1.md',['real audio and video catalog']]],
 'Membership & Progress'=>[['includes/staging_activation_core.php',['membership.account_flow','membership.role_boundary','membership.subscription_lifecycle','membership.progress']]],
 'Commerce & Billing'=>[['includes/staging_activation_core.php',['commerce.provider','commerce.merch_transaction','commerce.subscription_transaction','commerce.refund_inventory']]],
 'Delivery & Scheduler'=>[['includes/staging_activation_core.php',['delivery.configuration','delivery.transactional','delivery.scheduler']]],
 'Browser & Quality'=>[['includes/staging_activation_core.php',['browser.desktop_mobile','browser.accessibility','browser.performance']]],
 'Backup & Recovery'=>[['includes/staging_activation_checks.php',['recovery.backup']],['includes/staging_activation_core.php',['recovery.restore']],['docs/STAGING_ACTIVATION_RELEASE_CANDIDATE_V1.md',['backup, restore, and rollback evidence']]],
 'Certification & Integration'=>[['includes/staging_activation_checks.php',['certification.launch','certification.matrix']],['includes/staging_activation_core.php',['certification.preflight']]],
 'Exact Release Candidate'=>[['includes/staging_activation_candidate.php',['sf_sa_candidate_gate','artifact_sha256','candidate_status=\'frozen\'']],['deploy/staging-activation-preflight.php',['Exact release commit match']],['database/migrations/025_staging_activation_release_candidate.sql',['staging_release_candidate_events']]],
];
$fail=[];echo "Stonefellow Staging Activation & Release Candidate v1\n".str_repeat('=',78)."\n";foreach($sections as$name=>$checks){$passed=0;foreach($checks as[$file,$markers]){$body=is_file($root.'/'.$file)?(string)file_get_contents($root.'/'.$file):'';$ok=$body!=='';foreach($markers as$marker)if(stripos($body,$marker)===false)$ok=false;$passed+=(int)$ok;if(!$ok)$fail[]=$name.': '.$file.' is missing required controls.';}$score=count($checks)?(int)round($passed/count($checks)*10):0;echo sprintf("%-38s %d/10 (%d/%d)\n",$name,$score,$passed,count($checks));if($score!==10)$fail[]=$name.' did not score 10/10.';}
echo str_repeat('-',78)."\nOverall source score: ".($fail?'BLOCKED':'10.0/10')."\n";if($fail){echo "\nBlocking findings:\n- ".implode("\n- ",array_values(array_unique($fail)))."\n";exit(1);}echo "Result: PASS — All ten staging activation sections score 10/10.\n";
