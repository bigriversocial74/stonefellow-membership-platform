<?php

declare(strict_types=1);

require_once __DIR__ . '/admin_catalog.php';
require_once __DIR__ . '/data_ops_recovery.php';

if (defined('SF_STAGING_LAUNCH_CERTIFICATION_LOADED')) return;
define('SF_STAGING_LAUNCH_CERTIFICATION_LOADED', true);

function sf_slc_env(): string { return strtolower(trim((string)(getenv('SF_ENV') ?: 'production'))); }
function sf_slc_text($value, int $max = 4000): string { $value = trim((string)$value); return function_exists('mb_substr') ? mb_substr($value, 0, $max) : substr($value, 0, $max); }
function sf_slc_bool_env(string $name, bool $default = false): bool { $raw = getenv($name); if ($raw === false || trim((string)$raw) === '') return $default; return in_array(strtolower(trim((string)$raw)), ['1','true','yes','on'], true); }
function sf_slc_uuid(): string { $b=random_bytes(16);$b[6]=chr((ord($b[6])&0x0f)|0x40);$b[8]=chr((ord($b[8])&0x3f)|0x80);$h=bin2hex($b);return substr($h,0,8).'-'.substr($h,8,4).'-'.substr($h,12,4).'-'.substr($h,16,4).'-'.substr($h,20); }
function sf_slc_ready(): bool { return sf_admin_db() instanceof PDO && sf_admin_table_exists('staging_launch_certification_runs') && sf_admin_table_exists('staging_launch_certification_checks') && sf_admin_table_exists('staging_launch_certification_evidence') && sf_admin_table_exists('staging_launch_certification_events'); }
function sf_slc_locked(array $run): bool { return in_array((string)($run['run_status'] ?? ''), ['passed','superseded'], true); }

function sf_slc_catalog(): array {
    return [
        'environment.staging_mode'=>['stage'=>'environment','label'=>'Dedicated staging environment mode','severity'=>'critical','required'=>1,'automated'=>1],
        'environment.https'=>['stage'=>'environment','label'=>'HTTPS request and trusted proxy behavior','severity'=>'critical','required'=>1,'automated'=>1],
        'environment.allowed_hosts'=>['stage'=>'environment','label'=>'Allowed-host restriction configured','severity'=>'critical','required'=>1,'automated'=>1],
        'environment.secret_strength'=>['stage'=>'environment','label'=>'Dedicated strong application secrets','severity'=>'critical','required'=>1,'automated'=>1],
        'environment.no_shortcuts'=>['stage'=>'environment','label'=>'Production-impacting shortcuts disabled','severity'=>'critical','required'=>1,'automated'=>1],
        'database.persistence'=>['stage'=>'database','label'=>'Launch-certification persistence tables','severity'=>'critical','required'=>1,'automated'=>1],
        'database.integrity'=>['stage'=>'database','label'=>'Migration checksums, foreign keys, strict mode, and InnoDB','severity'=>'critical','required'=>1,'automated'=>1],
        'authentication.account_flow'=>['stage'=>'authentication','label'=>'Signup, login, logout, recovery, and session lifecycle','severity'=>'critical','required'=>1,'automated'=>0],
        'authentication.role_separation'=>['stage'=>'authentication','label'=>'Member, administrator, and scoped-role separation','severity'=>'critical','required'=>1,'automated'=>0],
        'billing.test_configuration'=>['stage'=>'billing','label'=>'Stripe test-mode configuration','severity'=>'critical','required'=>1,'automated'=>1],
        'billing.checkout_webhook'=>['stage'=>'billing','label'=>'Checkout, signed webhook, and entitlement activation','severity'=>'critical','required'=>1,'automated'=>0],
        'billing.lifecycle'=>['stage'=>'billing','label'=>'Upgrade, downgrade, cancellation, failed payment, and refund','severity'=>'critical','required'=>1,'automated'=>0],
        'media.access_matrix'=>['stage'=>'media','label'=>'Preview, subscriber, premium, and download access matrix','severity'=>'critical','required'=>1,'automated'=>0],
        'media.signed_delivery'=>['stage'=>'media','label'=>'Signed media expiry and cross-account rejection','severity'=>'critical','required'=>1,'automated'=>0],
        'media.tracking'=>['stage'=>'media','label'=>'Watch/listen progress, completion, replay, and resume integrity','severity'=>'high','required'=>1,'automated'=>0],
        'notifications.configuration'=>['stage'=>'notifications','label'=>'Transactional provider and signed webhook configuration','severity'=>'critical','required'=>1,'automated'=>1],
        'notifications.delivery'=>['stage'=>'notifications','label'=>'Inbox delivery, bounce, complaint, retry, and max-attempt behavior','severity'=>'critical','required'=>1,'automated'=>0],
        'notifications.preferences'=>['stage'=>'notifications','label'=>'Preference suppression and duplicate-safe campaign delivery','severity'=>'high','required'=>1,'automated'=>0],
        'content.publishing'=>['stage'=>'content','label'=>'Draft, scheduled release, archive, and access filtering','severity'=>'critical','required'=>1,'automated'=>0],
        'content.import_rollback'=>['stage'=>'content','label'=>'Import preview, atomic commit, and rollback rehearsal','severity'=>'high','required'=>1,'automated'=>0],
        'content.moderation'=>['stage'=>'content','label'=>'Comments, reactions, moderation, and abuse controls','severity'=>'high','required'=>1,'automated'=>0],
        'ai.certification'=>['stage'=>'ai','label'=>'AI provider staging certification passed','severity'=>'critical','required'=>1,'automated'=>1],
        'ai.supervision'=>['stage'=>'ai','label'=>'Approval-required mission execution and rollback proof','severity'=>'critical','required'=>1,'automated'=>0],
        'operations.scheduler_concurrency'=>['stage'=>'operations','label'=>'Concurrent queue, scheduler, and publishing workers','severity'=>'critical','required'=>1,'automated'=>0],
        'operations.inventory_concurrency'=>['stage'=>'operations','label'=>'Concurrent inventory and checkout protection','severity'=>'critical','required'=>1,'automated'=>0],
        'operations.backup_restore'=>['stage'=>'operations','label'=>'Verified backup artifact and isolated restore rehearsal','severity'=>'critical','required'=>1,'automated'=>0],
        'operations.preflight'=>['stage'=>'operations','label'=>'Deployment preflight completes without blocking failures','severity'=>'critical','required'=>1,'automated'=>0],
        'browser.mobile'=>['stage'=>'browser','label'=>'Mobile and responsive browser matrix','severity'=>'high','required'=>1,'automated'=>0],
        'browser.accessibility'=>['stage'=>'browser','label'=>'Keyboard, screen reader, contrast, zoom, and reduced motion','severity'=>'high','required'=>1,'automated'=>0],
        'browser.performance'=>['stage'=>'browser','label'=>'Core Web Vitals and media-loading budget','severity'=>'high','required'=>1,'automated'=>0],
        'release.commit'=>['stage'=>'release','label'=>'Exact 40-character release commit and branch recorded','severity'=>'critical','required'=>1,'automated'=>1],
        'release.rollback'=>['stage'=>'release','label'=>'Rollback procedure, owner, and decision threshold','severity'=>'critical','required'=>1,'automated'=>0],
        'release.freeze'=>['stage'=>'release','label'=>'Schema, configuration, and content freeze approved','severity'=>'critical','required'=>1,'automated'=>0],
        'release.approval'=>['stage'=>'release','label'=>'Final launch approval and evidence review','severity'=>'critical','required'=>1,'automated'=>0],
    ];
}

function sf_slc_event(int $runId,string $type,string $message,array $metadata=[]): void {
    if (!sf_slc_ready() || $runId<=0) return;
    $json=$metadata?json_encode($metadata,JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE):null;
    sf_admin_execute('INSERT INTO staging_launch_certification_events (run_id,event_type,event_message,actor_user_id,metadata_json) VALUES (?,?,?,?,?)',[$runId,sf_slc_text($type,120),sf_slc_text($message,1000),sf_current_user_id()?:null,$json]);
}

function sf_slc_create_run(string $label,string $branch,string $sha): int {
    if (!sf_slc_ready()) return 0;
    $label=sf_slc_text($label,190); if($label==='')$label='Stonefellow launch certification '.date('Y-m-d H:i');
    $branch=sf_slc_text($branch,190); if($branch==='')$branch='main';
    $sha=strtolower(trim($sha)); if($sha!==''&&!preg_match('/^[a-f0-9]{40}$/',$sha)) return 0;
    $pdo=sf_admin_db(); if(!$pdo)return 0;
    try{
        $pdo->beginTransaction();
        $s=$pdo->prepare("INSERT INTO staging_launch_certification_runs (run_key,run_label,environment_key,target_branch,target_commit_sha,run_status,started_by_user_id,started_at) VALUES (?,?,?,?,?,'in_progress',?,NOW())");
        $s->execute([sf_slc_uuid(),$label,sf_slc_env(),$branch,$sha!==''?$sha:null,sf_current_user_id()?:null]);
        $id=(int)$pdo->lastInsertId();
        $i=$pdo->prepare('INSERT INTO staging_launch_certification_checks (run_id,check_key,stage_key,check_label,severity,is_required,is_automated) VALUES (?,?,?,?,?,?,?)');
        foreach(sf_slc_catalog() as $key=>$c)$i->execute([$id,$key,$c['stage'],$c['label'],$c['severity'],(int)$c['required'],(int)$c['automated']]);
        $pdo->commit(); sf_slc_recalculate($id); sf_slc_event($id,'run.created','Launch certification run created.',['branch'=>$branch,'commit'=>$sha]);
        sf_admin_audit('create_staging_launch_certification','staging_launch_certification_run',$id,null,['label'=>$label,'branch'=>$branch,'commit'=>$sha]);
        return $id;
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();error_log('Launch certification create failed: '.$e->getMessage());return 0;}
}

function sf_slc_runs(int $limit=40): array { return sf_slc_ready()?sf_admin_fetch_all('SELECT * FROM staging_launch_certification_runs ORDER BY created_at DESC,id DESC LIMIT '.max(1,min(100,$limit))):[]; }
function sf_slc_run(int $id): ?array { return sf_slc_ready()&&$id>0?sf_admin_fetch_one('SELECT * FROM staging_launch_certification_runs WHERE id=? LIMIT 1',[$id]):null; }
function sf_slc_checks(int $id): array { return sf_slc_ready()&&$id>0?sf_admin_fetch_all("SELECT * FROM staging_launch_certification_checks WHERE run_id=? ORDER BY FIELD(severity,'critical','high','medium','low','info'),stage_key,check_label",[$id]):[]; }
function sf_slc_evidence(int $id): array { return sf_slc_ready()&&$id>0?sf_admin_fetch_all('SELECT * FROM staging_launch_certification_evidence WHERE run_id=? ORDER BY created_at DESC,id DESC',[$id]):[]; }
function sf_slc_events(int $id,int $limit=100): array { return sf_slc_ready()&&$id>0?sf_admin_fetch_all('SELECT * FROM staging_launch_certification_events WHERE run_id=? ORDER BY created_at DESC,id DESC LIMIT '.max(1,min(300,$limit)),[$id]):[]; }

function sf_slc_record(int $runId,string $key,string $status,string $message,array $evidence=[]): bool {
    $run=sf_slc_run($runId);$catalog=sf_slc_catalog();if(!$run||sf_slc_locked($run)||!isset($catalog[$key]))return false;
    $status=in_array($status,['pending','running','passed','failed','skipped','not_applicable'],true)?$status:'failed';
    $json=$evidence?json_encode($evidence,JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE):null;if(is_string($json)&&strlen($json)>65535)$json=json_encode(['truncated'=>true,'sha256'=>hash('sha256',$json)]);
    $message=sf_slc_text($message,8000);$hash=hash('sha256',$key.'|'.$status.'|'.$message.'|'.(string)$json);
    $ok=sf_admin_execute("UPDATE staging_launch_certification_checks SET check_status=?,result_message=?,evidence_json=?,evidence_hash=?,checked_by_user_id=?,started_at=COALESCE(started_at,NOW()),completed_at=CASE WHEN ? IN ('passed','failed','skipped','not_applicable') THEN NOW() ELSE NULL END WHERE run_id=? AND check_key=?",[$status,$message,$json,$hash,sf_current_user_id()?:null,$status,$runId,$key]);
    if($ok){sf_slc_recalculate($runId);sf_slc_event($runId,'check.'.$status,$key.': '.$message,['check_key'=>$key,'evidence_hash'=>$hash]);}
    return $ok;
}

function sf_slc_add_evidence(int $runId,string $checkKey,string $type,string $label,string $source,string $sha,array $metadata=[]): int {
    $run=sf_slc_run($runId);$catalog=sf_slc_catalog();if(!$run||sf_slc_locked($run)||!isset($catalog[$checkKey]))return 0;
    $types=['note','url','file_hash','provider_event','browser_test','database_test','backup','restore','approval'];if(!in_array($type,$types,true))$type='note';
    $label=sf_slc_text($label,255);$source=sf_slc_text($source,1000);$sha=strtolower(trim($sha));if($label===''||strlen($source)<8)return 0;if($sha!==''&&!preg_match('/^[a-f0-9]{64}$/',$sha))return 0;
    $json=$metadata?json_encode($metadata,JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE):null;
    $ok=sf_admin_execute("INSERT INTO staging_launch_certification_evidence (run_id,check_key,evidence_type,evidence_label,source_reference,artifact_sha256,metadata_json,verification_status,submitted_by_user_id,verified_by_user_id,verified_at) VALUES (?,?,?,?,?,?,?,'verified',?,?,NOW())",[$runId,$checkKey,$type,$label,$source,$sha!==''?$sha:null,$json,sf_current_user_id()?:null,sf_current_user_id()?:null]);
    if(!$ok)return 0;$id=(int)(sf_admin_db()?->lastInsertId()?:0);sf_slc_event($runId,'evidence.added','Verified evidence added for '.$checkKey,['evidence_id'=>$id,'type'=>$type]);return $id;
}

function sf_slc_manual_check(int $runId,string $key,string $status,string $message,string $type='note',string $source='',string $sha=''): bool {
    $catalog=sf_slc_catalog();if(!isset($catalog[$key])||!empty($catalog[$key]['automated']))return false;
    $message=sf_slc_text($message,8000);if($status==='passed'&&strlen($message)<12)return false;
    if($status==='passed'){if(sf_slc_add_evidence($runId,$key,$type,$message,$source!==''?$source:$message,$sha)<=0)return false;}
    return sf_slc_record($runId,$key,$status,$message,['manual'=>true,'evidence_type'=>$type,'source_reference'=>$source,'artifact_sha256'=>$sha]);
}

function sf_slc_secret_ok(string $name,int $min=32): bool { $v=(string)(getenv($name)?:'');return strlen($v)>=$min&&!str_contains(strtolower($v),'replace')&&!str_contains(strtolower($v),'change-me'); }
function sf_slc_https(): bool { return (!empty($_SERVER['HTTPS'])&&strtolower((string)$_SERVER['HTTPS'])!=='off') || (sf_slc_bool_env('SF_TRUST_PROXY')&&strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO']??''))==='https'); }

function sf_slc_automated_results(): array {
    $hostList=array_values(array_filter(array_map('trim',explode(',',(string)(getenv('SF_ALLOWED_HOSTS')?:'')))));
    $secrets=['SF_APP_KEY','SF_HASH_SALT','SF_MEDIA_SIGNING_KEY','SF_AI_SETTINGS_SECRET','SF_OPS_SCHEDULER_SECRET','SF_PUBLISHING_RUN_SECRET','SF_NOTIFICATION_WEBHOOK_SECRET'];
    $secretValues=[];$secretFailures=[];foreach($secrets as $name){$v=(string)(getenv($name)?:'');if(!sf_slc_secret_ok($name,$name==='SF_APP_KEY'?64:32))$secretFailures[]=$name;$secretValues[$name]=$v;}
    $nonEmpty=array_values(array_filter($secretValues,static fn($v)=>$v!==''));$distinct=count($nonEmpty)===count(array_unique($nonEmpty));
    $shortcuts=['SF_ALLOW_PUBLIC_FIRST_ADMIN','SF_ALLOW_SANDBOX_SUBSCRIPTIONS','SF_ALLOW_SANDBOX_MERCH','SF_ALLOW_SANDBOX_PAYMENTS','SF_ALLOW_UNSIGNED_SANDBOX_WEBHOOKS','SF_ALLOW_INTERNAL_BILLING_WEBHOOK'];$enabled=[];foreach($shortcuts as $n)if(sf_slc_bool_env($n))$enabled[]=$n;
    $paymentProvider=strtolower((string)(getenv('SF_PAYMENT_PROVIDER')?:''));$paymentMode=strtolower((string)(getenv('SF_PAYMENT_MODE')?:''));$stripeReady=$paymentProvider==='stripe'&&in_array($paymentMode,['test','sandbox'],true)&&sf_slc_secret_ok('SF_STRIPE_SECRET_KEY',16)&&sf_slc_secret_ok('SF_STRIPE_WEBHOOK_SECRET',16);
    $mailProvider=strtolower((string)(getenv('SF_MAIL_PROVIDER')?:getenv('SF_EMAIL_PROVIDER')?:''));$mailReady=$mailProvider!==''&&!in_array($mailProvider,['log','sandbox','preview'],true)&&sf_slc_secret_ok('SF_NOTIFICATION_WEBHOOK_SECRET',32);
    $aiPassed=false;if(sf_admin_table_exists('ai_staging_certification_runs')){$r=sf_admin_fetch_one("SELECT id,overall_score FROM ai_staging_certification_runs WHERE run_status='passed' AND overall_score=100 ORDER BY completed_at DESC,id DESC LIMIT 1");$aiPassed=(bool)$r;}
    $integrity=[];if(function_exists('sf_dor_operations_checks'))$integrity=sf_dor_operations_checks();$integrityFail=array_values(array_filter($integrity,static fn($c)=>in_array((string)($c['status']??''),['fail','missing'],true)));
    return [
      'environment.staging_mode'=>[sf_slc_env()==='staging',sf_slc_env()==='staging'?'Dedicated staging mode confirmed.':'SF_ENV must equal staging.',['environment'=>sf_slc_env()]],
      'environment.https'=>[sf_slc_https(),'HTTPS '.(sf_slc_https()?'confirmed.':'was not detected.'),['https'=>sf_slc_https(),'trust_proxy'=>sf_slc_bool_env('SF_TRUST_PROXY')]],
      'environment.allowed_hosts'=>[count($hostList)>0&&!in_array('*',$hostList,true),'Allowed hosts '.($hostList?'configured.':'missing.'),['allowed_hosts'=>$hostList]],
      'environment.secret_strength'=>[!$secretFailures&&$distinct,!$secretFailures&&$distinct?'Required secrets are strong and distinct.':'Weak, placeholder, missing, or reused secrets detected.',['invalid'=>$secretFailures,'distinct'=>$distinct]],
      'environment.no_shortcuts'=>[!$enabled,!$enabled?'Production-impacting shortcuts are disabled.':'Unsafe shortcuts are enabled.',['enabled'=>$enabled]],
      'database.persistence'=>[sf_slc_ready(),sf_slc_ready()?'Certification tables are available.':'Import database/staging_launch_certification_v1.sql.'],
      'database.integrity'=>[!$integrityFail,!$integrityFail?'Operations integrity checks have no blocking failures.':'Operations integrity checks reported blockers.',['blocking'=>array_slice($integrityFail,0,20)]],
      'billing.test_configuration'=>[$stripeReady,$stripeReady?'Stripe test-mode credentials and webhook secret are configured.':'Stripe test mode, secret key, and webhook secret are required.',['provider'=>$paymentProvider,'mode'=>$paymentMode]],
      'notifications.configuration'=>[$mailReady,$mailReady?'Transactional provider and webhook secret are configured.':'Configure a real staging mail provider and webhook secret.',['provider'=>$mailProvider]],
      'ai.certification'=>[$aiPassed,$aiPassed?'A 100% passed AI staging certification exists.':'Complete AI staging certification first.'],
    ];
}

function sf_slc_run_automated(int $runId): array {
    $run=sf_slc_run($runId);if(!$run||sf_slc_locked($run))return ['ok'=>false,'processed'=>0,'passed'=>0,'failed'=>0];
    $out=['ok'=>true,'processed'=>0,'passed'=>0,'failed'=>0];foreach(sf_slc_automated_results() as $key=>$r){$out['processed']++;$ok=(bool)$r[0];sf_slc_record($runId,$key,$ok?'passed':'failed',(string)$r[1],(array)($r[2]??[]));$ok?$out['passed']++:$out['failed']++;}return $out;
}

function sf_slc_recalculate(int $runId): void {
    if(!sf_slc_ready()||$runId<=0)return;$r=sf_admin_fetch_one("SELECT SUM(is_required=1) required_checks,SUM(is_required=1 AND check_status='passed') passed_checks,SUM(is_required=1 AND check_status='failed') failed_checks,SUM(is_required=1 AND check_status IN ('pending','running','skipped')) pending_checks FROM staging_launch_certification_checks WHERE run_id=?",[$runId])?:[];
    $required=(int)($r['required_checks']??0);$passed=(int)($r['passed_checks']??0);$failed=(int)($r['failed_checks']??0);$pending=(int)($r['pending_checks']??0);$score=$required?round($passed/$required*100,2):0;
    sf_admin_execute('UPDATE staging_launch_certification_runs SET required_checks=?,passed_checks=?,failed_checks=?,pending_checks=?,overall_score=? WHERE id=?',[$required,$passed,$failed,$pending,$score,$runId]);
}

function sf_slc_complete(int $runId,string $notes): array {
    $run=sf_slc_run($runId);if(!$run||sf_slc_locked($run))return ['ok'=>false,'message'=>'Run is missing or immutable.'];sf_slc_recalculate($runId);$run=sf_slc_run($runId)?:$run;
    $ok=(int)$run['required_checks']>0&&(int)$run['passed_checks']===(int)$run['required_checks']&&(int)$run['failed_checks']===0&&(int)$run['pending_checks']===0&&preg_match('/^[a-f0-9]{40}$/',(string)$run['target_commit_sha']);
    $status=$ok?'passed':'failed';sf_admin_execute('UPDATE staging_launch_certification_runs SET run_status=?,certification_notes=?,completed_by_user_id=?,completed_at=NOW() WHERE id=?',[$status,sf_slc_text($notes,12000),sf_current_user_id()?:null,$runId]);sf_slc_event($runId,'run.'.$status,$ok?'Launch certification passed.':'Launch certification failed or remains incomplete.');sf_admin_audit('complete_staging_launch_certification','staging_launch_certification_run',$runId,$run,['status'=>$status]);
    return ['ok'=>$ok,'status'=>$status,'message'=>$ok?'All required launch checks passed at 100%.':'Required checks remain failed or incomplete.'];
}

function sf_slc_latest_passed(): ?array { return sf_slc_ready()?sf_admin_fetch_one("SELECT * FROM staging_launch_certification_runs WHERE run_status='passed' AND overall_score=100 ORDER BY completed_at DESC,id DESC LIMIT 1"):null; }
function sf_slc_stage_summary(int $runId): array { if(!sf_slc_ready())return [];return sf_admin_fetch_all("SELECT stage_key,COUNT(*) total,SUM(check_status='passed') passed,SUM(check_status='failed') failed,SUM(check_status IN ('pending','running','skipped')) pending,ROUND(SUM(check_status='passed')/COUNT(*)*100,1) score FROM staging_launch_certification_checks WHERE run_id=? AND is_required=1 GROUP BY stage_key ORDER BY stage_key",[$runId]); }
?>
