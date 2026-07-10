<?php

declare(strict_types=1);

function sf_sa_ready(): bool {
    return sf_admin_db() instanceof PDO
        && sf_admin_table_exists('staging_activation_runs')
        && sf_admin_table_exists('staging_activation_checks')
        && sf_admin_table_exists('staging_activation_evidence')
        && sf_admin_table_exists('staging_release_candidates')
        && sf_admin_table_exists('staging_release_candidate_events');
}

function sf_sa_uuid(): string { return function_exists('sf_slc_uuid') ? sf_slc_uuid() : bin2hex(random_bytes(16)); }
function sf_sa_text($value, int $max = 12000): string { $value=trim((string)$value);return function_exists('mb_substr')?mb_substr($value,0,$max):substr($value,0,$max); }
function sf_sa_env(): string { return strtolower(trim((string)(getenv('SF_ENV') ?: 'production'))); }
function sf_sa_bool_env(string $name, bool $default=false): bool { $v=getenv($name);if($v===false||trim((string)$v)==='')return$default;return in_array(strtolower(trim((string)$v)),['1','true','yes','on'],true); }
function sf_sa_secret_ok(string $name, int $min=32): bool { $v=(string)(getenv($name)?:'');$l=strtolower($v);return strlen($v)>=$min&&!str_contains($l,'change-me')&&!str_contains($l,'replace')&&!str_contains($l,'example'); }
function sf_sa_locked(array $run): bool { return in_array((string)($run['run_status']??''),['passed','superseded'],true); }
function sf_sa_check(string $section,string $label,bool $ok,string $detail,array $evidence=[]): array { return ['section'=>$section,'label'=>$label,'ok'=>$ok,'detail'=>$detail,'evidence'=>$evidence]; }

function sf_sa_catalog(): array {
    return [
      'environment.staging_mode'=>['section'=>'environment','label'=>'Dedicated staging environment','severity'=>'critical','required'=>1,'automated'=>1],
      'environment.https_hosts'=>['section'=>'environment','label'=>'HTTPS and allowed-host boundary','severity'=>'critical','required'=>1,'automated'=>1],
      'environment.secrets'=>['section'=>'environment','label'=>'Strong distinct staging secrets','severity'=>'critical','required'=>1,'automated'=>1],
      'environment.no_shortcuts'=>['section'=>'environment','label'=>'Sandbox shortcuts and unsafe bypasses disabled','severity'=>'critical','required'=>1,'automated'=>1],
      'catalog.snapshot'=>['section'=>'catalog','label'=>'100% exact-commit catalog readiness snapshot','severity'=>'critical','required'=>1,'automated'=>1],
      'catalog.real_records'=>['section'=>'catalog','label'=>'Real records exist across every launch catalog type','severity'=>'critical','required'=>1,'automated'=>1],
      'catalog.no_samples'=>['section'=>'catalog','label'=>'No unresolved high-confidence sample content','severity'=>'critical','required'=>1,'automated'=>1],
      'catalog.public_pages'=>['section'=>'catalog','label'=>'Public catalog pages, links, images, and metadata','severity'=>'high','required'=>1,'automated'=>0],
      'media.runtime'=>['section'=>'media','label'=>'Protected storage, FFmpeg, and FFprobe ready','severity'=>'critical','required'=>1,'automated'=>1],
      'media.health'=>['section'=>'media','label'=>'Fresh storage read/write/delete health evidence','severity'=>'critical','required'=>1,'automated'=>1],
      'media.audio_assets'=>['section'=>'media','label'=>'Audio streams, previews, and waveforms ready','severity'=>'critical','required'=>1,'automated'=>1],
      'media.video_assets'=>['section'=>'media','label'=>'Video posters, previews, manifests, and segments ready','severity'=>'critical','required'=>1,'automated'=>1],
      'media.playback'=>['section'=>'media','label'=>'Browser playback, seek, expiry, and entitlement matrix','severity'=>'critical','required'=>1,'automated'=>0],
      'membership.account_flow'=>['section'=>'membership','label'=>'Signup, login, recovery, logout, and session revocation','severity'=>'critical','required'=>1,'automated'=>0],
      'membership.role_boundary'=>['section'=>'membership','label'=>'Member and administrator role boundary','severity'=>'critical','required'=>1,'automated'=>0],
      'membership.subscription_lifecycle'=>['section'=>'membership','label'=>'Subscribe, entitlement, cancellation, and failed payment','severity'=>'critical','required'=>1,'automated'=>0],
      'membership.progress'=>['section'=>'membership','label'=>'Playlist, listen, watch, completion, and resume persistence','severity'=>'high','required'=>1,'automated'=>0],
      'commerce.provider'=>['section'=>'commerce','label'=>'Stripe Connect test checkout is enabled','severity'=>'critical','required'=>1,'automated'=>1],
      'commerce.merch_transaction'=>['section'=>'commerce','label'=>'Merchandise checkout, receipt, settlement, and order history','severity'=>'critical','required'=>1,'automated'=>0],
      'commerce.subscription_transaction'=>['section'=>'commerce','label'=>'Membership checkout and recurring invoice lifecycle','severity'=>'critical','required'=>1,'automated'=>0],
      'commerce.refund_inventory'=>['section'=>'commerce','label'=>'Refund, dispute, inventory, and reconciliation flow','severity'=>'critical','required'=>1,'automated'=>0],
      'delivery.configuration'=>['section'=>'delivery','label'=>'Transactional provider and signed webhook configured','severity'=>'critical','required'=>1,'automated'=>1],
      'delivery.transactional'=>['section'=>'delivery','label'=>'Email delivery, bounce, complaint, and retry evidence','severity'=>'critical','required'=>1,'automated'=>0],
      'delivery.scheduler'=>['section'=>'delivery','label'=>'Scheduler, queues, publishing, and advisory locks','severity'=>'critical','required'=>1,'automated'=>0],
      'browser.desktop_mobile'=>['section'=>'browser','label'=>'Desktop and mobile core-flow browser matrix','severity'=>'high','required'=>1,'automated'=>0],
      'browser.accessibility'=>['section'=>'browser','label'=>'Keyboard, screen reader, contrast, zoom, and reduced motion','severity'=>'high','required'=>1,'automated'=>0],
      'browser.performance'=>['section'=>'browser','label'=>'Public, member, admin, and media-loading performance budgets','severity'=>'high','required'=>1,'automated'=>0],
      'recovery.backup'=>['section'=>'recovery','label'=>'Fresh fully verified backup','severity'=>'critical','required'=>1,'automated'=>1],
      'recovery.restore'=>['section'=>'recovery','label'=>'Isolated restore and rollback rehearsal','severity'=>'critical','required'=>1,'automated'=>0],
      'certification.launch'=>['section'=>'certification','label'=>'100% launch certificate matches exact commit','severity'=>'critical','required'=>1,'automated'=>1],
      'certification.matrix'=>['section'=>'certification','label'=>'Every staging integration scenario passed','severity'=>'critical','required'=>1,'automated'=>1],
      'certification.preflight'=>['section'=>'certification','label'=>'Deployment preflight passes for exact commit','severity'=>'critical','required'=>1,'automated'=>0],
      'release.commit'=>['section'=>'release','label'=>'Exact 40-character release commit configured','severity'=>'critical','required'=>1,'automated'=>1],
      'release.freeze_plan'=>['section'=>'release','label'=>'Schema, configuration, content, and artifact freeze plan','severity'=>'critical','required'=>1,'automated'=>0],
      'release.approvals'=>['section'=>'release','label'=>'Technical, operations, security, and business approvals','severity'=>'critical','required'=>1,'automated'=>0],
    ];
}

function sf_sa_scenario_coverage(int $certificationRunId): array {
    $required=array_keys(sf_sim_catalog());
    $rows=sf_sim_ready()?sf_admin_fetch_all("SELECT DISTINCT scenario_key FROM staging_integration_executions WHERE certification_run_id=? AND execution_status='passed'",[$certificationRunId]):[];
    $passed=array_map(static fn($r)=>(string)$r['scenario_key'],$rows);
    $missing=array_values(array_diff($required,$passed));
    return ['ok'=>!$missing,'required'=>$required,'passed'=>$passed,'missing'=>$missing];
}

function sf_sa_run(int $id): ?array { return sf_sa_ready()&&$id>0?sf_admin_fetch_one('SELECT * FROM staging_activation_runs WHERE id=? LIMIT 1',[$id]):null; }
function sf_sa_runs(int $limit=50): array { return sf_sa_ready()?sf_admin_fetch_all('SELECT * FROM staging_activation_runs ORDER BY created_at DESC,id DESC LIMIT '.max(1,min(200,$limit))):[]; }
function sf_sa_checks(int $runId): array { return sf_sa_ready()&&$runId>0?sf_admin_fetch_all("SELECT * FROM staging_activation_checks WHERE run_id=? ORDER BY FIELD(severity,'critical','high','medium','low'),section_key,check_label",[$runId]):[]; }
function sf_sa_evidence(int $runId): array { return sf_sa_ready()&&$runId>0?sf_admin_fetch_all('SELECT * FROM staging_activation_evidence WHERE run_id=? ORDER BY created_at DESC,id DESC',[$runId]):[]; }
function sf_sa_latest_passed(): ?array { return sf_sa_ready()?sf_admin_fetch_one("SELECT * FROM staging_activation_runs WHERE run_status='passed' AND overall_score=100 ORDER BY completed_at DESC,id DESC LIMIT 1"):null; }

function sf_sa_create_run(string $label,string $branch,string $sha): int {
    if(!sf_sa_ready())return 0;$label=sf_sa_text($label,190)?:'Stonefellow staging activation '.date('Y-m-d H:i');$branch=sf_sa_text($branch,190)?:'main';$sha=strtolower(trim($sha));if(!preg_match('/^[a-f0-9]{40}$/',$sha))return 0;$pdo=sf_admin_db();if(!$pdo)return 0;
    try{$pdo->beginTransaction();$s=$pdo->prepare("INSERT INTO staging_activation_runs (run_key,run_label,environment_key,target_branch,target_commit_sha,run_status,started_by_user_id,started_at) VALUES (?,?,?,?,?,'running',?,NOW())");$s->execute([sf_sa_uuid(),$label,sf_sa_env(),$branch,$sha,sf_current_user_id()?:null]);$id=(int)$pdo->lastInsertId();$i=$pdo->prepare('INSERT INTO staging_activation_checks (run_id,check_key,section_key,check_label,severity,is_required,is_automated) VALUES (?,?,?,?,?,?,?)');foreach(sf_sa_catalog() as$key=>$c)$i->execute([$id,$key,$c['section'],$c['label'],$c['severity'],(int)$c['required'],(int)$c['automated']]);$pdo->commit();sf_sa_recalculate($id);sf_admin_audit('create_staging_activation','staging_activation_run',$id,null,['branch'=>$branch,'commit'=>$sha]);return$id;}catch(Throwable$e){if($pdo->inTransaction())$pdo->rollBack();error_log('Staging activation create failed: '.$e->getMessage());return 0;}
}

function sf_sa_record(int $runId,string $key,string $status,string $message,array $evidence=[]): bool {
    $run=sf_sa_run($runId);$catalog=sf_sa_catalog();if(!$run||sf_sa_locked($run)||!isset($catalog[$key]))return false;$status=in_array($status,['pending','running','passed','failed','skipped'],true)?$status:'failed';$message=sf_sa_text($message,12000);$json=$evidence?json_encode($evidence,JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE):null;if(is_string($json)&&strlen($json)>65535)$json=json_encode(['truncated'=>true,'sha256'=>hash('sha256',$json)]);$hash=hash('sha256',$key.'|'.$status.'|'.$message.'|'.(string)$json);$ok=sf_admin_execute("UPDATE staging_activation_checks SET check_status=?,result_message=?,evidence_json=?,evidence_hash=?,checked_by_user_id=?,started_at=COALESCE(started_at,NOW()),completed_at=CASE WHEN ? IN ('passed','failed','skipped') THEN NOW() ELSE NULL END WHERE run_id=? AND check_key=?",[$status,$message,$json,$hash,sf_current_user_id()?:null,$status,$runId,$key]);if($ok)sf_sa_recalculate($runId);return$ok;
}

function sf_sa_add_evidence(int $runId,string $key,string $type,string $label,string $source,string $sha='',array $metadata=[]): int {
    $run=sf_sa_run($runId);$catalog=sf_sa_catalog();if(!$run||sf_sa_locked($run)||!isset($catalog[$key]))return 0;$types=['note','url','provider_event','browser_test','database_test','transaction','backup','restore','artifact','approval'];if(!in_array($type,$types,true))$type='note';$label=sf_sa_text($label,255);$source=sf_sa_text($source,1000);$sha=strtolower(trim($sha));if($label===''||strlen($source)<8||($sha!==''&&!preg_match('/^[a-f0-9]{64}$/',$sha)))return 0;$json=$metadata?json_encode($metadata,JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE):null;$ok=sf_admin_execute("INSERT INTO staging_activation_evidence (run_id,check_key,evidence_type,evidence_label,source_reference,artifact_sha256,metadata_json,verification_status,submitted_by_user_id,verified_by_user_id,verified_at) VALUES (?,?,?,?,?,?,?,'verified',?,?,NOW())",[$runId,$key,$type,$label,$source,$sha?:null,$json,sf_current_user_id()?:null,sf_current_user_id()?:null]);return$ok?(int)(sf_admin_db()?->lastInsertId()?:0):0;
}

function sf_sa_manual_check(int $runId,string $key,string $status,string $message,string $type,string $source,string $sha=''): bool {
    $catalog=sf_sa_catalog();if(!isset($catalog[$key])||!empty($catalog[$key]['automated']))return false;$message=sf_sa_text($message,12000);if($status==='passed'&&(strlen($message)<12||sf_sa_add_evidence($runId,$key,$type,$message,$source,$sha)<=0))return false;return sf_sa_record($runId,$key,$status,$message,['manual'=>true,'evidence_type'=>$type,'source_reference'=>$source,'artifact_sha256'=>$sha]);
}

function sf_sa_recalculate(int $runId): void {
    if(!sf_sa_ready()||$runId<=0)return;$r=sf_admin_fetch_one("SELECT SUM(is_required=1) required_checks,SUM(is_required=1 AND check_status='passed') passed_checks,SUM(is_required=1 AND check_status='failed') failed_checks,SUM(is_required=1 AND check_status IN ('pending','running','skipped')) pending_checks FROM staging_activation_checks WHERE run_id=?",[$runId])?:[];$required=(int)($r['required_checks']??0);$passed=(int)($r['passed_checks']??0);$failed=(int)($r['failed_checks']??0);$pending=(int)($r['pending_checks']??0);$score=$required?round($passed/$required*100,2):0;sf_admin_execute('UPDATE staging_activation_runs SET required_checks=?,passed_checks=?,failed_checks=?,pending_checks=?,overall_score=? WHERE id=?',[$required,$passed,$failed,$pending,$score,$runId]);
}

function sf_sa_complete(int $runId,string $notes): array {
    $run=sf_sa_run($runId);if(!$run||sf_sa_locked($run))return['ok'=>false,'message'=>'Activation run is missing or immutable.'];sf_sa_recalculate($runId);$run=sf_sa_run($runId)?:$run;$ok=(int)$run['required_checks']>0&&(int)$run['passed_checks']===(int)$run['required_checks']&&(int)$run['failed_checks']===0&&(int)$run['pending_checks']===0&&preg_match('/^[a-f0-9]{40}$/',(string)$run['target_commit_sha']);$status=$ok?'passed':'failed';sf_admin_execute('UPDATE staging_activation_runs SET run_status=?,activation_notes=?,completed_by_user_id=?,completed_at=NOW() WHERE id=?',[$status,sf_sa_text($notes,30000),sf_current_user_id()?:null,$runId]);sf_admin_audit('complete_staging_activation','staging_activation_run',$runId,$run,['status'=>$status]);return['ok'=>$ok,'status'=>$status,'message'=>$ok?'Staging activation passed at 100%.':'Required activation checks remain failed or incomplete.'];
}

function sf_sa_section_summary(int $runId): array { return sf_sa_ready()?sf_admin_fetch_all("SELECT section_key,COUNT(*) total,SUM(check_status='passed') passed,SUM(check_status='failed') failed,SUM(check_status IN ('pending','running','skipped')) pending,ROUND(SUM(check_status='passed')/COUNT(*)*100,1) score FROM staging_activation_checks WHERE run_id=? AND is_required=1 GROUP BY section_key ORDER BY section_key",[$runId]):[]; }
