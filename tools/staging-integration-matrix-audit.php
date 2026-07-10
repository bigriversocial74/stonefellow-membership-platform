<?php

declare(strict_types=1);
$root=dirname(__DIR__);$read=static fn(string $f):string=>is_file($root.'/'.$f)?(string)file_get_contents($root.'/'.$f):'';
$sections=[
 'Scenario persistence'=>[['database/staging_integration_matrix_v1.sql',['staging_integration_executions','staging_integration_assertions','staging_integration_events','execution_key','correlation_id']]],
 'Authentication scenarios'=>[['includes/staging_integration_matrix.php',['auth_account_lifecycle','auth_role_separation','login_throttle','revoked_session_denied']]],
 'Billing scenarios'=>[['includes/staging_integration_matrix.php',['billing_checkout_activation','billing_subscription_lifecycle','amount_currency_match','refund_reconciliation']]],
 'Media scenarios'=>[['includes/staging_integration_matrix.php',['media_access_delivery','media_tracking_resume','cross_account_rejection','bounded_progress']]],
 'Delivery scenarios'=>[['includes/staging_integration_matrix.php',['notification_provider_delivery','notification_preferences_campaigns','retry_backoff','preference_suppression']]],
 'Content and AI scenarios'=>[['includes/staging_integration_matrix.php',['content_release_integrity','ai_supervised_execution','import_atomicity','snapshot_restore']]],
 'Operations and browser scenarios'=>[['includes/staging_integration_matrix.php',['operations_concurrency_recovery','browser_quality_matrix','isolated_restore','accessibility']]],
 'Assertion evidence'=>[['includes/staging_integration_matrix.php',['sf_sim_record_assertion','source_reference','evidence_sha256','strlen($sourceReference)<8']],['admin/staging-integration-matrix.php',['Required scenario proof','Evidence SHA-256','Passed executions are immutable']]],
 'Signed event correlation'=>[['api/staging-integration-event.php',['staging_only','invalid_signature','execution_not_found','sf_sim_ingest_event']],['includes/staging_integration_matrix.php',['sf_sim_event_signature_valid','source_event_id','[redacted]','payload_hash']]],
 'Certification promotion'=>[['includes/staging_integration_matrix.php',['sf_sim_complete_execution','sf_slc_record','assertion_counts','certification checks were updated']],['.github/workflows/code-audit.yml',['staging_integration_matrix_smoke.php','staging-integration-matrix-audit.php']]],
];
$fail=[];$earned=0;$total=0;echo "Stonefellow Staging Integration Matrix Audit v1\n".str_repeat('=',66)."\n";
foreach($sections as $section=>$checks){$pass=0;foreach($checks as [$file,$markers]){$total++;$body=$read($file);$missing=[];foreach($markers as $m)if($body===''||stripos($body,(string)$m)===false)$missing[]=$m;if(!$missing){$pass++;$earned++;}else{$fail[]=$section.': '.$file.' missing ['.implode(', ',$missing).'].';}}$score=(int)round($pass/count($checks)*10);echo sprintf("%-38s %d/10 (%d/%d)\n",$section,$score,$pass,count($checks));}
$overall=$total?round($earned/$total*10,1):0;echo str_repeat('-',66)."\nOverall score: {$overall}/10\n";if($fail){echo "\nBlocking findings:\n- ".implode("\n- ",$fail)."\n";exit(1);}echo "Result: PASS — all ten sections score 10/10.\n";
