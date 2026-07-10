<?php

declare(strict_types=1);
putenv('SF_SKIP_INSTALL_REDIRECT=1');
$root=dirname(__DIR__);$fail=[];$assert=static function(bool $ok,string $message)use(&$fail):void{if(!$ok)$fail[]=$message;};
$files=[
 'database/migrations/026_production_cutover_hypercare.sql'=>['production_cutover_runs','production_hypercare_checkpoints','production_verification_certificates'],
 'includes/production_cutover.php'=>['production_cutover_core.php','production_cutover_checks.php','production_cutover_hypercare.php'],
 'includes/production_cutover_core.php'=>['sf_pch_create_run','sf_pch_manual_check','production_cutover_checks'],
 'includes/production_cutover_checks.php'=>['sf_pch_automated_results','sf_pch_thresholds','sf_pch_record_decision'],
 'includes/production_cutover_hypercare.php'=>['15m','72h','rollback.recommended','sf_pch_issue_certificate','certificate_sha256'],
 'admin/production-cutover.php'=>['Production Cutover','Hypercare','Rollback'],
 'api/production-cutover-event.php'=>['X_STONEFELLOW_SIGNATURE','POST required'],
 'api/production-cutover-status.php'=>['generated_at','rollback_recommended'],
 'deploy/production-cutover-preflight.php'=>['Production Cutover Preflight','Production verification certificate'],
 'docs/PRODUCTION_CUTOVER_HYPERCARE_V1.md'=>['Final source/control score: **10/10**','Operational boundary'],
 '.github/workflows/code-audit.yml'=>['production_cutover_hypercare_smoke.php','production-cutover-hypercare-audit.php'],
];foreach($files as$path=>$markers){$body=is_file($root.'/'.$path)?(string)file_get_contents($root.'/'.$path):'';$assert($body!=='',$path.' missing.');foreach($markers as$m)$assert(stripos($body,$m)!==false,$path.' missing '.$m);}
if($fail){fwrite(STDERR,"Production cutover hypercare smoke failures:\n- ".implode("\n- ",$fail)."\n");exit(1);}echo "Production cutover and hypercare smoke: PASS\n";
