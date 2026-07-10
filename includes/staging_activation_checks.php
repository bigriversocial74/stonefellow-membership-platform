<?php

declare(strict_types=1);

function sf_sa_table_count(string $table,string $where='1=1',array $params=[]): int {
    if(!sf_admin_table_exists($table))return 0;$row=sf_admin_fetch_one('SELECT COUNT(*) total FROM `'.str_replace('`','',$table).'` WHERE '.$where,$params);return(int)($row['total']??0);
}

function sf_sa_required_catalog_counts(): array {
    $map=['series'=>'catalog_series','seasons'=>'seasons','episodes'=>'episodes','videos'=>'videos','albums'=>'albums','songs'=>'songs','characters'=>'story_characters','products'=>'products','plans'=>'subscription_plans'];$out=[];foreach($map as$label=>$table)$out[$label]=sf_sa_table_count($table);return$out;
}

function sf_sa_media_role_count(string $entityType,string $role): int {
    return sf_admin_table_exists('media_objects')?sf_sa_table_count('media_objects',"entity_type=? AND role=? AND status='ready'",[$entityType,$role]):0;
}

function sf_sa_automated_results(?array $run=null): array {
    $run=$run?:[];$targetSha=strtolower((string)($run['target_commit_sha']??(getenv('SF_RELEASE_COMMIT_SHA')?:'')));$hostList=array_values(array_filter(array_map('trim',explode(',',(string)(getenv('SF_ALLOWED_HOSTS')?:'')))));$https=function_exists('sf_slc_https')?sf_slc_https():((!empty($_SERVER['HTTPS'])&&strtolower((string)$_SERVER['HTTPS'])!=='off'));
    $secrets=['SF_APP_KEY'=>64,'SF_HASH_SALT'=>32,'SF_MEDIA_SIGNING_KEY'=>32,'SF_MEDIA_DELIVERY_SESSION_SECRET'=>32,'SF_MEDIA_WORKER_SECRET'=>32,'SF_CATALOG_RUNNER_SECRET'=>32,'SF_STAGING_ACTIVATION_SECRET'=>32,'SF_OPS_SCHEDULER_SECRET'=>32,'SF_PUBLISHING_RUN_SECRET'=>32,'SF_NOTIFICATION_WEBHOOK_SECRET'=>32,'SF_STRIPE_SECRET_KEY'=>16,'SF_STRIPE_WEBHOOK_SECRET'=>16];$bad=[];$values=[];foreach($secrets as$name=>$min){$v=(string)(getenv($name)?:'');if(!sf_sa_secret_ok($name,$min))$bad[]=$name;if($v!=='')$values[]=$v;}$distinct=count($values)===count(array_unique($values));
    $shortcuts=['SF_ALLOW_PUBLIC_FIRST_ADMIN','SF_ALLOW_SANDBOX_SUBSCRIPTIONS','SF_ALLOW_SANDBOX_MERCH','SF_ALLOW_SANDBOX_PAYMENTS','SF_ALLOW_UNSIGNED_SANDBOX_WEBHOOKS','SF_ALLOW_INTERNAL_BILLING_WEBHOOK'];$enabled=[];foreach($shortcuts as$name)if(sf_sa_bool_env($name))$enabled[]=$name;
    $catalog=sf_lco_latest_snapshot();$catalogOk=$catalog&&(int)($catalog['overall_score']??0)===100&&($catalog['snapshot_status']??'')==='passed'&&preg_match('/^[a-f0-9]{40}$/',$targetSha)&&hash_equals($targetSha,strtolower((string)($catalog['target_commit_sha']??'')));
    $counts=sf_sa_required_catalog_counts();$empty=array_keys(array_filter($counts,static fn($n)=>(int)$n<1));$openSamples=sf_admin_table_exists('catalog_sample_flags')?sf_sa_table_count('catalog_sample_flags',"flag_status='open' AND confidence_percent>=80"):1;
    $media=sf_mp_provider_summary();$mediaRuntime=!empty($media['schema_ready'])&&!empty($media['storage_ready'])&&!empty($media['ffmpeg_ready'])&&!empty($media['ffprobe_ready']);$health=sf_admin_table_exists('media_storage_health_runs')?sf_admin_fetch_one("SELECT * FROM media_storage_health_runs WHERE status='healthy' AND completed_at>=DATE_SUB(NOW(),INTERVAL 24 HOUR) ORDER BY completed_at DESC,id DESC LIMIT 1"):null;
    $songCount=(int)($counts['songs']??0);$videoCount=(int)($counts['videos']??0);$audioCounts=['stream'=>sf_sa_media_role_count('song','stream'),'preview'=>sf_sa_media_role_count('song','preview'),'waveform'=>sf_sa_media_role_count('song','waveform')];$audioOk=$songCount>0&&min($audioCounts)>=$songCount;$videoCounts=['manifest'=>sf_sa_media_role_count('video','manifest'),'segment'=>sf_sa_media_role_count('video','segment'),'poster'=>sf_sa_media_role_count('video','poster')];$videoOk=$videoCount>0&&$videoCounts['manifest']>=$videoCount&&$videoCounts['segment']>=$videoCount&&$videoCounts['poster']>=$videoCount;
    $commerce=sf_commerce_provider_summary();$commerceOk=!empty($commerce['checkout_ready']);$mailProvider=strtolower((string)(getenv('SF_MAIL_PROVIDER')?:getenv('SF_EMAIL_PROVIDER')?:''));$deliveryOk=$mailProvider!==''&&!in_array($mailProvider,['log','sandbox','preview'],true)&&sf_sa_secret_ok('SF_NOTIFICATION_WEBHOOK_SECRET',32);
    $backup=sf_dor_latest_verified_backup(24);$certificate=sf_slc_latest_passed();$certificateOk=$certificate&&(float)($certificate['overall_score']??0)===100.0&&preg_match('/^[a-f0-9]{40}$/',$targetSha)&&hash_equals($targetSha,strtolower((string)($certificate['target_commit_sha']??'')));$coverage=$certificate?sf_sa_scenario_coverage((int)$certificate['id']):['ok'=>false,'missing'=>array_keys(sf_sim_catalog()),'passed'=>[],'required'=>array_keys(sf_sim_catalog())];$releaseSha=strtolower(trim((string)(getenv('SF_RELEASE_COMMIT_SHA')?:'')));$releaseOk=preg_match('/^[a-f0-9]{40}$/',$releaseSha)&&preg_match('/^[a-f0-9]{40}$/',$targetSha)&&hash_equals($targetSha,$releaseSha);
    return [
      'environment.staging_mode'=>[sf_sa_env()==='staging',sf_sa_env()==='staging'?'SF_ENV is staging.':'SF_ENV must equal staging.',['environment'=>sf_sa_env()]],
      'environment.https_hosts'=>[$https&&$hostList&&!in_array('*',$hostList,true),$https&&$hostList&&!in_array('*',$hostList,true)?'HTTPS and explicit allowed hosts are configured.':'HTTPS or explicit allowed hosts are missing.',['https'=>$https,'allowed_hosts'=>$hostList]],
      'environment.secrets'=>[!$bad&&$distinct,!$bad&&$distinct?'Required staging secrets are strong and distinct.':'Missing, weak, placeholder, or reused secrets detected.',['invalid'=>$bad,'distinct'=>$distinct]],
      'environment.no_shortcuts'=>[!$enabled,!$enabled?'Unsafe staging shortcuts are disabled.':'Unsafe shortcuts remain enabled.',['enabled'=>$enabled]],
      'catalog.snapshot'=>[$catalogOk,$catalogOk?'A 100% catalog snapshot matches the activation commit.':'Create a 100% catalog snapshot for the exact activation commit.',['snapshot_id'=>$catalog['id']??null,'score'=>$catalog['overall_score']??null,'target_commit_sha'=>$catalog['target_commit_sha']??null]],
      'catalog.real_records'=>[!$empty,!$empty?'Every required launch catalog type contains records.':'Empty launch catalog types: '.implode(', ',$empty).'.',['counts'=>$counts]],
      'catalog.no_samples'=>[$openSamples===0,$openSamples===0?'No unresolved high-confidence sample flags remain.':$openSamples.' high-confidence sample flags remain open.',['open_flags'=>$openSamples]],
      'media.runtime'=>[$mediaRuntime,$mediaRuntime?'Protected storage and processing binaries are ready.':'Storage, FFmpeg, or FFprobe is not ready.',$media],
      'media.health'=>[$health!==null,$health?'Fresh healthy storage evidence exists.':'No healthy storage read/write/delete run exists from the last 24 hours.',['health_run_id'=>$health['id']??null,'completed_at'=>$health['completed_at']??null]],
      'media.audio_assets'=>[$audioOk,$audioOk?'Every song has ready stream, preview, and waveform objects.':'Processed audio assets are incomplete.',['songs'=>$songCount,'roles'=>$audioCounts]],
      'media.video_assets'=>[$videoOk,$videoOk?'Every video has ready manifest, segment, and poster objects.':'Processed HLS video assets are incomplete.',['videos'=>$videoCount,'roles'=>$videoCounts]],
      'commerce.provider'=>[$commerceOk,$commerceOk?'Stripe Connect test checkout is enabled.':'Stripe Connect test checkout is not ready.',$commerce],
      'delivery.configuration'=>[$deliveryOk,$deliveryOk?'Transactional delivery provider and signing secret are configured.':'Configure a real staging mail provider and notification webhook secret.',['provider'=>$mailProvider]],
      'recovery.backup'=>[$backup!==null,$backup?'Fresh verified backup '.$backup['run_key'].' is available.':'No fully verified backup from the last 24 hours.',['backup_id'=>$backup['id']??null,'run_key'=>$backup['run_key']??null]],
      'certification.launch'=>[$certificateOk,$certificateOk?'100% launch certificate matches the activation commit.':'Launch certificate is missing, incomplete, or bound to another commit.',['certificate_id'=>$certificate['id']??null,'target_commit_sha'=>$certificate['target_commit_sha']??null]],
      'certification.matrix'=>[!empty($coverage['ok']),!empty($coverage['ok'])?'Every required staging integration scenario passed.':'Missing scenarios: '.implode(', ',array_slice($coverage['missing']??[],0,10)).'.',$coverage],
      'release.commit'=>[$releaseOk,$releaseOk?'SF_RELEASE_COMMIT_SHA matches the activation commit.':'Configure the exact activation commit in SF_RELEASE_COMMIT_SHA.',['configured'=>$releaseSha,'target'=>$targetSha]],
    ];
}

function sf_sa_run_automated(int $runId): array {
    $run=sf_sa_run($runId);if(!$run||sf_sa_locked($run))return['ok'=>false,'processed'=>0,'passed'=>0,'failed'=>0];$out=['ok'=>true,'processed'=>0,'passed'=>0,'failed'=>0];foreach(sf_sa_automated_results($run) as$key=>$r){$out['processed']++;$ok=(bool)$r[0];sf_sa_record($runId,$key,$ok?'passed':'failed',(string)$r[1],(array)($r[2]??[]));$ok?$out['passed']++:$out['failed']++;}return$out;
}
