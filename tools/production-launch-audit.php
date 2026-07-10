<?php

declare(strict_types=1);
$root=dirname(__DIR__);$read=static fn(string $f):string=>is_file($root.'/'.$f)?(string)file_get_contents($root.'/'.$f):'';
$sections=[
 'Promotion persistence'=>[['database/production_launch_promotion_v1.sql',['production_launch_promotions','production_launch_approvals','production_launch_checks','production_launch_events','UNIQUE KEY uniq_production_launch_release']]],
 'Release binding'=>[['includes/production_launch.php',['sf_prod_binding_gate','target_commit_sha','Certificate commit does not match','Promotion backup must match']]],
 'Scenario coverage'=>[['includes/production_launch.php',['sf_prod_scenario_coverage','Missing passed integration scenarios','execution_status=\'passed\'']],['includes/release_candidate.php',['Integration scenario coverage','sf_rc_integration_coverage']]],
 'Independent approvals'=>[['includes/production_launch.php',['sf_prod_approval_gate','technical','operations','security','business','distinct approver','creator cannot be an approver']]],
 'Phase evidence'=>[['includes/production_launch.php',['pre_deploy','post_deploy','rollback','sf_prod_record_check','evidence_reference','evidence_sha256']],['admin/production-launch.php',['Launch Checks','Approved package evidence']]],
 'Fail-closed transitions'=>[['includes/production_launch.php',['Invalid promotion state transition','Transition blocked','sf_prod_phase_gate','sf_prod_artifact_gate']],['admin/production-launch.php',['Evaluate Transition','confirm(']]],
 'Signed deployment events'=>[['api/production-deployment-event.php',['production_only','invalid_signature','commit_mismatch','event_id_required','duplicate']],['includes/production_launch.php',['sf_prod_signature_valid','sf_prod_ingest_deployment_event','sf_sim_redact']]],
 'Post-deploy verification'=>[['includes/production_launch.php',['auth_postdeploy','billing_postdeploy','media_postdeploy','notifications_postdeploy','scheduler_postdeploy','preflight_postdeploy','monitoring_stable']]],
 'Rollback readiness'=>[['includes/production_launch.php',['rollback_trigger','rollback_procedure','rollback_owner','rollback_command','restore_ready','rollback_drill']]],
 'Preflight and CI enforcement'=>[['deploy/preflight.php',['Production Launch Promotion Score','SF_RELEASE_COMMIT_SHA','approved, deploying, deployed, or verified']],['.github/workflows/code-audit.yml',['production_launch_smoke.php','production-launch-audit.php']]],
];
$fail=[];$earned=0;$total=0;echo "Stonefellow Release Candidate & Production Launch Audit v1\n".str_repeat('=',70)."\n";
foreach($sections as $section=>$checks){$pass=0;foreach($checks as [$file,$markers]){$total++;$body=$read($file);$missing=[];foreach($markers as $m)if($body===''||stripos($body,(string)$m)===false)$missing[]=$m;if(!$missing){$pass++;$earned++;}else{$fail[]=$section.': '.$file.' missing ['.implode(', ',$missing).'].';}}$score=(int)round($pass/count($checks)*10);echo sprintf("%-38s %d/10 (%d/%d)\n",$section,$score,$pass,count($checks));}
$overall=$total?round($earned/$total*10,1):0;echo str_repeat('-',70)."\nOverall score: {$overall}/10\n";if($fail){echo "\nBlocking findings:\n- ".implode("\n- ",$fail)."\n";exit(1);}echo "Result: PASS — all ten sections score 10/10.\n";
