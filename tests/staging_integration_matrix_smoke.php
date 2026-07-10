<?php

declare(strict_types=1);
putenv('SF_SKIP_INSTALL_REDIRECT=1');
putenv('SF_ENV=testing');
putenv('SF_APP_KEY='.str_repeat('a',64));
putenv('SF_HASH_SALT='.str_repeat('b',40));
putenv('SF_STAGING_INTEGRATION_EVENT_SECRET='.str_repeat('c',40));
require_once __DIR__.'/../includes/staging_integration_matrix.php';
$fail=[];$assert=static function(bool $ok,string $message)use(&$fail):void{if(!$ok)$fail[]=$message;};
$catalog=sf_sim_catalog();
$assert(count($catalog)>=10,'Integration catalog should cover all deployed stages.');
foreach(['authentication','billing','media','notifications','content','ai','operations','browser'] as $stage){$assert((bool)array_filter($catalog,static fn($s)=>($s['stage']??'')===$stage),'Missing integration stage '.$stage);}
foreach($catalog as $key=>$scenario){$assert(!empty($scenario['label']),'Scenario '.$key.' requires a label.');$assert(count($scenario['checks']??[])>=1,'Scenario '.$key.' must promote a certification check.');$assert(count($scenario['assertions']??[])>=3,'Scenario '.$key.' requires meaningful assertions.');}
$raw='{"execution_key":"test"}';$signature=hash_hmac('sha256',$raw,(string)getenv('SF_STAGING_INTEGRATION_EVENT_SECRET'));
$assert(sf_sim_event_signature_valid($raw,$signature),'Valid integration event HMAC should pass.');
$assert(!sf_sim_event_signature_valid($raw,str_repeat('0',64)),'Invalid integration event HMAC should fail.');
$redacted=sf_sim_redact(['email'=>'fan@example.com','token'=>'secret','event_id'=>'evt_1']);
$assert(($redacted['email']??'')==='[redacted]'&&($redacted['token']??'')==='[redacted]'&&($redacted['event_id']??'')==='evt_1','Integration event privacy redaction should preserve identity and remove PII/secrets.');
$root=dirname(__DIR__);$markers=[
 'database/staging_integration_matrix_v1.sql'=>['staging_integration_executions','staging_integration_assertions','staging_integration_events','correlation_id','source_event_id'],
 'includes/staging_integration_matrix.php'=>['sf_sim_create_execution','sf_sim_record_assertion','sf_sim_complete_execution','sf_slc_record','sf_sim_ingest_event'],
 'admin/staging-integration-matrix.php'=>['Correlation ID','Required scenario proof','Evaluate Scenario','sf_csrf_field'],
 'api/staging-integration-event.php'=>['staging_only','HTTP_X_STONEFELLOW_INTEGRATION_SIGNATURE','invalid_signature','sf_sim_ingest_event'],
 '.env.staging.example'=>['SF_STAGING_INTEGRATION_EVENT_SECRET','SF_STAGING_INTEGRATION_EVENT_MAX_BYTES=262144'],
];
foreach($markers as $file=>$needles){$body=(string)file_get_contents($root.'/'.$file);foreach($needles as $needle)$assert(stripos($body,$needle)!==false,$file.' missing '.$needle);}
if($fail){fwrite(STDERR,"Staging integration matrix smoke failures:\n- ".implode("\n- ",$fail)."\n");exit(1);}echo "Staging integration matrix smoke: PASS\n";
