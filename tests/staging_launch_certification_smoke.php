<?php

declare(strict_types=1);
putenv('SF_SKIP_INSTALL_REDIRECT=1');
putenv('SF_ENV=testing');
putenv('SF_APP_KEY='.str_repeat('a',64));
putenv('SF_HASH_SALT='.str_repeat('b',40));
putenv('SF_MEDIA_SIGNING_KEY='.str_repeat('c',40));
putenv('SF_AI_SETTINGS_SECRET='.str_repeat('d',40));
putenv('SF_OPS_SCHEDULER_SECRET='.str_repeat('e',40));
putenv('SF_PUBLISHING_RUN_SECRET='.str_repeat('f',40));
putenv('SF_NOTIFICATION_WEBHOOK_SECRET='.str_repeat('1',40));
require_once __DIR__.'/../includes/staging_launch_certification.php';
$fail=[];$assert=static function(bool $ok,string $m)use(&$fail):void{if(!$ok)$fail[]=$m;};
$catalog=sf_slc_catalog();
$assert(count($catalog)>=30,'Catalog should cover the full launch matrix.');
foreach(['environment','database','authentication','billing','media','notifications','content','ai','operations','browser','release'] as $stage){$assert((bool)array_filter($catalog,static fn($c)=>($c['stage']??'')===$stage),'Missing stage '.$stage);}
$assert(sf_slc_uuid()!==sf_slc_uuid(),'Run UUIDs should be unique.');
$assert(sf_slc_secret_ok('SF_APP_KEY',64),'Strong application key should pass.');
putenv('SF_APP_KEY=replace-with-secret');$assert(!sf_slc_secret_ok('SF_APP_KEY',64),'Placeholder secret should fail.');
$root=dirname(__DIR__);$markers=[
 'database/staging_launch_certification_v1.sql'=>['staging_launch_certification_runs','staging_launch_certification_checks','staging_launch_certification_evidence','staging_launch_certification_events','UNIQUE KEY uniq_staging_launch_run_check'],
 'includes/staging_launch_certification.php'=>['sf_slc_run_automated','sf_slc_manual_check','sf_slc_complete','sf_slc_latest_passed','SF_ALLOW_UNSIGNED_SANDBOX_WEBHOOKS'],
 'admin/staging-launch-certification.php'=>['40-character Git SHA','Evidence Ledger','Evaluate 100% Launch Gate','sf_csrf_field'],
 'deploy/preflight.php'=>['Staging Launch Certification'],
 '.env.staging.example'=>['SF_OPS_SCHEDULER_SECRET','SF_NOTIFICATION_WEBHOOK_SECRET','SF_PUBLISHING_RUN_SECRET'],
];
foreach($markers as $file=>$needles){$body=(string)file_get_contents($root.'/'.$file);foreach($needles as $n)$assert(stripos($body,$n)!==false,$file.' missing '.$n);}
if($fail){fwrite(STDERR,"Staging launch certification smoke failures:\n- ".implode("\n- ",$fail)."\n");exit(1);}echo "Staging launch certification smoke: PASS\n";
