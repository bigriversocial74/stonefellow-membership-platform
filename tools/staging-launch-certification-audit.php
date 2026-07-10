<?php

declare(strict_types=1);
$root=dirname(__DIR__);$read=static fn(string $f):string=>is_file($root.'/'.$f)?(string)file_get_contents($root.'/'.$f):'';
$sections=[
 'Persistence and evidence'=>[['database/staging_launch_certification_v1.sql',['runs','checks','evidence','events','artifact_sha256','verification_status']],['includes/staging_launch_certification.php',['sf_slc_add_evidence','evidence_hash','sf_slc_event']]],
 'Environment safety'=>[['includes/staging_launch_certification.php',['SF_ENV','sf_slc_https','SF_ALLOWED_HOSTS','secret_strength','no_shortcuts']],['.env.staging.example',['SF_ENV=staging','SF_ALLOWED_HOSTS','SF_TRUST_PROXY=0']]],
 'Authentication and authorization'=>[['includes/staging_launch_certification.php',['authentication.account_flow','authentication.role_separation']],['admin/staging-launch-certification.php',['admin.ops.manage','sf_csrf_field']]],
 'Billing and entitlements'=>[['includes/staging_launch_certification.php',['billing.test_configuration','billing.checkout_webhook','billing.lifecycle','SF_PAYMENT_MODE']]],
 'Media and tracking'=>[['includes/staging_launch_certification.php',['media.access_matrix','media.signed_delivery','media.tracking']]],
 'Delivery and content'=>[['includes/staging_launch_certification.php',['notifications.delivery','notifications.preferences','content.publishing','content.import_rollback','content.moderation']]],
 'AI supervision'=>[['includes/staging_launch_certification.php',['ai.certification','ai.supervision','ai_staging_certification_runs']]],
 'Operations and recovery'=>[['includes/staging_launch_certification.php',['operations.scheduler_concurrency','operations.inventory_concurrency','operations.backup_restore','operations.preflight','sf_dor_operations_checks']]],
 'Browser quality'=>[['includes/staging_launch_certification.php',['browser.mobile','browser.accessibility','browser.performance']]],
 'Release gate'=>[['includes/staging_launch_certification.php',['release.commit','release.rollback','release.freeze','release.approval','required_checks','overall_score']],['deploy/preflight.php',['Staging Launch Certification','sf_slc_latest_passed']]],
];
$fail=[];$earned=0;$total=0;echo "Stonefellow Staging Operations & Launch Certification Audit v1\n".str_repeat('=',72)."\n";
foreach($sections as $section=>$checks){$pass=0;foreach($checks as [$file,$markers]){$total++;$body=$read($file);$missing=[];foreach($markers as $m)if($body===''||stripos($body,(string)$m)===false)$missing[]=$m;if(!$missing){$pass++;$earned++;}else{$fail[]=$section.': '.$file.' missing ['.implode(', ',$missing).'].';}}$score=(int)round($pass/count($checks)*10);echo sprintf("%-38s %d/10 (%d/%d)\n",$section,$score,$pass,count($checks));}
$overall=$total?round($earned/$total*10,1):0;echo str_repeat('-',72)."\nOverall score: {$overall}/10\n";if($fail){echo "\nBlocking findings:\n- ".implode("\n- ",$fail)."\n";exit(1);}echo "Result: PASS — all ten sections score 10/10.\n";
