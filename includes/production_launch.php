<?php

declare(strict_types=1);

require_once __DIR__ . '/release_candidate.php';
require_once __DIR__ . '/staging_integration_matrix.php';

if (defined('SF_PRODUCTION_LAUNCH_LOADED')) return;
define('SF_PRODUCTION_LAUNCH_LOADED', true);

function sf_prod_ready(): bool {
    return sf_admin_db() instanceof PDO
        && sf_admin_table_exists('production_launch_promotions')
        && sf_admin_table_exists('production_launch_approvals')
        && sf_admin_table_exists('production_launch_checks')
        && sf_admin_table_exists('production_launch_events');
}
function sf_prod_text($value,int $max=12000): string { return sf_slc_text($value,$max); }
function sf_prod_bool(string $name,bool $default=false): bool { return sf_slc_bool_env($name,$default); }
function sf_prod_locked(array $promotion): bool { return in_array((string)($promotion['promotion_status']??''),['verified','rolled_back','failed','superseded'],true); }
function sf_prod_statuses(): array { return ['draft','approved','deploying','deployed','verified','failed','rolled_back','superseded']; }

function sf_prod_check_catalog(): array {
    return [
      'release_gate'=>['phase'=>'pre_deploy','label'=>'Existing release, migration, task, and backup gate passes'],
      'certificate_match'=>['phase'=>'pre_deploy','label'=>'100% launch certificate matches the exact release commit'],
      'scenario_coverage'=>['phase'=>'pre_deploy','label'=>'Every staging integration scenario has a passed execution'],
      'backup_match'=>['phase'=>'pre_deploy','label'=>'Release uses the fresh evidence-backed verified backup'],
      'rollback_ready'=>['phase'=>'pre_deploy','label'=>'Rollback owner, trigger threshold, and procedure are documented'],
      'configuration_freeze'=>['phase'=>'pre_deploy','label'=>'Schema, secrets, configuration, and release content are frozen'],
      'approvals_complete'=>['phase'=>'pre_deploy','label'=>'Technical, operations, security, and business approvals are complete'],
      'maintenance_window'=>['phase'=>'deploy','label'=>'Approved deployment window and maintenance communication are active'],
      'artifact_verified'=>['phase'=>'deploy','label'=>'Deployment artifact reference and SHA-256 match the approved build'],
      'migrations_applied'=>['phase'=>'deploy','label'=>'Pending migrations apply once with current checksums'],
      'deployment_health'=>['phase'=>'deploy','label'=>'Application, database, storage, worker, and health endpoints pass'],
      'auth_postdeploy'=>['phase'=>'post_deploy','label'=>'Production authentication and scoped administrator smoke checks pass'],
      'billing_postdeploy'=>['phase'=>'post_deploy','label'=>'Production billing configuration and signed webhook health pass'],
      'media_postdeploy'=>['phase'=>'post_deploy','label'=>'Protected media preview, entitlement, signature, and tracking checks pass'],
      'notifications_postdeploy'=>['phase'=>'post_deploy','label'=>'Transactional provider, signed events, queue, and retry health pass'],
      'scheduler_postdeploy'=>['phase'=>'post_deploy','label'=>'Schedulers, publishing, campaigns, and queue locks remain healthy'],
      'preflight_postdeploy'=>['phase'=>'post_deploy','label'=>'Deployment preflight passes for the deployed commit'],
      'monitoring_stable'=>['phase'=>'post_deploy','label'=>'Error, latency, payment, queue, and incident thresholds remain stable'],
      'rollback_owner'=>['phase'=>'rollback','label'=>'Named rollback decision owner is available'],
      'rollback_command'=>['phase'=>'rollback','label'=>'Rollback package, command sequence, and database decision are verified'],
      'restore_ready'=>['phase'=>'rollback','label'=>'Verified backup and isolated restore evidence remain available'],
      'rollback_drill'=>['phase'=>'rollback','label'=>'Rollback or recovery drill has passed with measured recovery time'],
    ];
}
function sf_prod_approval_types(): array { return ['technical','operations','security','business']; }

function sf_prod_promotion(int $id): ?array { return sf_prod_ready()&&$id>0?sf_admin_fetch_one('SELECT * FROM production_launch_promotions WHERE id=? LIMIT 1',[$id]):null; }
function sf_prod_promotions(int $limit=100): array { return sf_prod_ready()?sf_admin_fetch_all('SELECT p.*,r.release_label,c.run_label,b.run_key backup_key FROM production_launch_promotions p INNER JOIN deployment_releases r ON r.id=p.release_id INNER JOIN staging_launch_certification_runs c ON c.id=p.certification_run_id INNER JOIN backup_runs b ON b.id=p.backup_run_id ORDER BY p.created_at DESC,p.id DESC LIMIT '.max(1,min(300,$limit))):[]; }
function sf_prod_checks(int $promotionId): array { return sf_prod_ready()&&$promotionId>0?sf_admin_fetch_all("SELECT * FROM production_launch_checks WHERE promotion_id=? ORDER BY FIELD(phase_key,'pre_deploy','deploy','post_deploy','rollback'),id",[$promotionId]):[]; }
function sf_prod_approvals(int $promotionId): array { return sf_prod_ready()&&$promotionId>0?sf_admin_fetch_all("SELECT a.*,u.email approver_email,u.display_name approver_name FROM production_launch_approvals a LEFT JOIN users u ON u.id=a.approver_user_id WHERE a.promotion_id=? ORDER BY FIELD(a.approval_type,'technical','operations','security','business')",[$promotionId]):[]; }
function sf_prod_events(int $promotionId,int $limit=150): array { return sf_prod_ready()&&$promotionId>0?sf_admin_fetch_all('SELECT * FROM production_launch_events WHERE promotion_id=? ORDER BY created_at DESC,id DESC LIMIT '.max(1,min(300,$limit)),[$promotionId]):[]; }

function sf_prod_event(int $promotionId,string $type,string $message,array $payload=[],string $status='processed',string $sourceId=''): bool {
    if(!sf_prod_ready()||$promotionId<=0)return false;
    $json=$payload?json_encode(sf_sim_redact($payload),JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE):null;
    $hash=$payload?hash('sha256',json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE)):null;
    return sf_admin_execute('INSERT INTO production_launch_events (promotion_id,source_event_id,event_type,event_status,event_message,payload_hash,redacted_payload_json,actor_user_id,processed_at) VALUES (?,?,?,?,?,?,?,?,NOW())',[$promotionId,$sourceId?:null,sf_prod_text($type,120),in_array($status,['received','verified','processed','rejected'],true)?$status:'processed',sf_prod_text($message,1000),$hash,$json,sf_current_user_id()?:null]);
}

function sf_prod_scenario_coverage(int $certificationRunId): array {
    $required=array_keys(sf_sim_catalog());$rows=sf_sim_ready()?sf_admin_fetch_all("SELECT scenario_key,MAX(completed_at) completed_at FROM staging_integration_executions WHERE certification_run_id=? AND execution_status='passed' GROUP BY scenario_key",[$certificationRunId]):[];
    $passed=[];foreach($rows as $row)$passed[]=(string)$row['scenario_key'];$missing=array_values(array_diff($required,$passed));
    return ['ok'=>!$missing,'required'=>$required,'passed'=>$passed,'missing'=>$missing];
}

function sf_prod_binding_gate(int $releaseId,int $certificateId,int $backupId): array {
    $reasons=[];$release=sf_rel_release($releaseId);$certificate=sf_slc_run($certificateId);$backup=$backupId>0?sf_br_run($backupId):null;
    if(!$release)$reasons[]='Release record is missing.';
    if(!$certificate||($certificate['run_status']??'')!=='passed'||(float)($certificate['overall_score']??0)!==100.0)$reasons[]='A 100% passed launch certificate is required.';
    $sha=strtolower((string)($release['git_sha']??''));if(!preg_match('/^[a-f0-9]{40}$/',$sha))$reasons[]='Release requires a full 40-character commit SHA.';
    if($certificate&&$sha!==strtolower((string)($certificate['target_commit_sha']??'')))$reasons[]='Certificate commit does not match the release commit.';
    if(!$backup)$reasons[]='Backup record is missing.';else{$gate=sf_dor_backup_gate($backupId);if(empty($gate['ok']))$reasons=array_merge($reasons,array_map(static fn($r)=>'Backup: '.$r,$gate['reasons']));}
    if($release&&(int)($release['backup_run_id']??0)!==$backupId)$reasons[]='Promotion backup must match the release backup.';
    if($release){$releaseGate=sf_dor_release_gate($releaseId);if(empty($releaseGate['ok']))$reasons=array_merge($reasons,array_map(static fn($r)=>'Release: '.$r,$releaseGate['reasons']));}
    if($certificate){$coverage=sf_prod_scenario_coverage($certificateId);if(!$coverage['ok'])$reasons[]='Missing passed integration scenarios: '.implode(', ',$coverage['missing']).'.';}else{$coverage=['ok'=>false,'missing'=>array_keys(sf_sim_catalog())];}
    return ['ok'=>!$reasons,'reasons'=>array_values(array_unique($reasons)),'release'=>$release,'certificate'=>$certificate,'backup'=>$backup,'coverage'=>$coverage];
}

function sf_prod_create(array $data): int {
    if(!sf_prod_ready())return 0;$releaseId=(int)($data['release_id']??0);$certificateId=(int)($data['certification_run_id']??0);$backupId=(int)($data['backup_run_id']??0);$binding=sf_prod_binding_gate($releaseId,$certificateId,$backupId);if(!$binding['ok'])return 0;
    $release=$binding['release'];$label=sf_prod_text($data['promotion_label']??'',190);if($label==='')$label='Production launch '.$release['release_label'];$rollbackTrigger=sf_prod_text($data['rollback_trigger']??'',8000);$rollbackProcedure=sf_prod_text($data['rollback_procedure']??'',30000);if(strlen($rollbackTrigger)<12||strlen($rollbackProcedure)<30)return 0;
    $freeze=trim((string)($data['freeze_at']??''));$freeze=$freeze&&strtotime($freeze)!==false?date('Y-m-d H:i:s',strtotime($freeze)):null;$pdo=sf_admin_db();if(!$pdo)return 0;
    try{$pdo->beginTransaction();$s=$pdo->prepare("INSERT INTO production_launch_promotions (promotion_key,promotion_label,release_id,certification_run_id,backup_run_id,target_branch,target_commit_sha,promotion_status,freeze_at,rollback_trigger,rollback_procedure,release_notes,created_by_user_id,updated_by_user_id) VALUES (?,?,?,?,?,?,?,'draft',?,?,?,?,?,?)");$s->execute([sf_slc_uuid(),$label,$releaseId,$certificateId,$backupId,(string)$release['git_branch'],strtolower((string)$release['git_sha']),$freeze,$rollbackTrigger,$rollbackProcedure,sf_prod_text($data['release_notes']??'',30000)?:null,sf_current_user_id()?:null,sf_current_user_id()?:null]);$id=(int)$pdo->lastInsertId();$a=$pdo->prepare('INSERT INTO production_launch_approvals (promotion_id,approval_type) VALUES (?,?)');foreach(sf_prod_approval_types() as $type)$a->execute([$id,$type]);$c=$pdo->prepare('INSERT INTO production_launch_checks (promotion_id,phase_key,check_key,check_label) VALUES (?,?,?,?)');foreach(sf_prod_check_catalog() as $key=>$check)$c->execute([$id,$check['phase'],$key,$check['label']]);$pdo->commit();
      sf_prod_record_check($id,'release_gate','passed','Existing release gate passed.','deployment release '.$releaseId);
      sf_prod_record_check($id,'certificate_match','passed','Certificate is 100% passed and matches commit.','certification run '.$certificateId);
      sf_prod_record_check($id,'scenario_coverage','passed','All '.count($binding['coverage']['required']).' integration scenarios have passed executions.','certification run '.$certificateId);
      sf_prod_record_check($id,'backup_match','passed','Fresh verified backup matches the release.','backup run '.$backupId);
      sf_prod_record_check($id,'rollback_ready','passed','Rollback trigger and procedure are recorded.','promotion '.$id);
      sf_prod_event($id,'promotion.created','Production launch promotion created.',['release_id'=>$releaseId,'certificate_id'=>$certificateId,'backup_id'=>$backupId]);sf_admin_audit('create_production_launch','production_launch_promotion',$id,null,['release_id'=>$releaseId,'commit'=>$release['git_sha']]);return $id;
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();error_log('Production launch create failed: '.$e->getMessage());return 0;}
}

function sf_prod_record_check(int $promotionId,string $checkKey,string $status,string $message,string $reference='',string $sha=''): bool {
    $promotion=sf_prod_promotion($promotionId);$catalog=sf_prod_check_catalog();if(!$promotion||sf_prod_locked($promotion)||!isset($catalog[$checkKey]))return false;$status=in_array($status,['pending','running','passed','failed','skipped'],true)?$status:'failed';$message=sf_prod_text($message,8000);$reference=sf_prod_text($reference,1000);$sha=strtolower(trim($sha));if($sha!==''&&!preg_match('/^[a-f0-9]{64}$/',$sha))return false;if($status==='passed'&&(strlen($message)<12||strlen($reference)<8))return false;
    $allowedPhases=['draft'=>['pre_deploy','rollback'],'approved'=>['deploy','rollback'],'deploying'=>['deploy','rollback'],'deployed'=>['post_deploy','rollback']];$phase=$catalog[$checkKey]['phase'];if(!in_array($phase,$allowedPhases[$promotion['promotion_status']]??[],true))return false;
    $ok=sf_admin_execute('UPDATE production_launch_checks SET check_status=?,result_message=?,evidence_reference=?,evidence_sha256=?,checked_by_user_id=?,checked_at=NOW() WHERE promotion_id=? AND check_key=?',[$status,$message,$reference?:null,$sha?:null,sf_current_user_id()?:null,$promotionId,$checkKey]);if($ok)sf_prod_event($promotionId,'check.'.$status,$checkKey.': '.$message,['check_key'=>$checkKey,'reference'=>$reference]);return $ok;
}

function sf_prod_record_approval(int $promotionId,string $type,string $status,string $note,string $reference='',string $sha=''): bool {
    $promotion=sf_prod_promotion($promotionId);if(!$promotion||$promotion['promotion_status']!=='draft'||!in_array($type,sf_prod_approval_types(),true))return false;$status=in_array($status,['pending','approved','rejected','revoked'],true)?$status:'pending';$note=sf_prod_text($note,8000);$reference=sf_prod_text($reference,1000);$sha=strtolower(trim($sha));if($sha!==''&&!preg_match('/^[a-f0-9]{64}$/',$sha))return false;if($status==='approved'&&(strlen($note)<12||strlen($reference)<8))return false;
    $userId=sf_current_user_id()?:0;if($status==='approved'&&$userId<=0)return false;$ok=sf_admin_execute('UPDATE production_launch_approvals SET approval_status=?,decision_note=?,evidence_reference=?,evidence_sha256=?,approver_user_id=?,decided_at=CASE WHEN ? IN ("approved","rejected","revoked") THEN NOW() ELSE NULL END WHERE promotion_id=? AND approval_type=?',[$status,$note?:null,$reference?:null,$sha?:null,$status==='pending'?null:$userId,$status,$promotionId,$type]);if($ok)sf_prod_event($promotionId,'approval.'.$status,ucfirst($type).' approval '.$status.'.',['approval_type'=>$type,'approver_user_id'=>$userId]);return $ok;
}

function sf_prod_approval_gate(int $promotionId): array {
    $promotion=sf_prod_promotion($promotionId);$rows=sf_prod_approvals($promotionId);$reasons=[];$approved=array_values(array_filter($rows,static fn($r)=>($r['approval_status']??'')==='approved'));foreach(sf_prod_approval_types() as $type)if(!array_filter($approved,static fn($r)=>($r['approval_type']??'')===$type))$reasons[]=ucfirst($type).' approval is missing.';
    $ids=array_values(array_filter(array_map(static fn($r)=>(int)($r['approver_user_id']??0),$approved)));$distinct=sf_prod_bool('SF_PRODUCTION_LAUNCH_REQUIRE_DISTINCT_APPROVERS',true);if($distinct&&count($ids)!==count(array_unique($ids)))$reasons[]='Each approval must use a distinct approver.';if($distinct&&$promotion&&in_array((int)($promotion['created_by_user_id']??0),$ids,true))$reasons[]='The promotion creator cannot be an approver.';
    return ['ok'=>!$reasons,'reasons'=>$reasons,'approvals'=>$rows];
}
function sf_prod_phase_gate(int $promotionId,string $phase): array { $rows=sf_admin_fetch_all('SELECT * FROM production_launch_checks WHERE promotion_id=? AND phase_key=? AND is_required=1',[$promotionId,$phase]);$reasons=[];if(!$rows)$reasons[]='Required '.$phase.' checks are missing.';foreach($rows as $row)if(($row['check_status']??'')!=='passed')$reasons[]='Check not passed: '.($row['check_label']??$row['check_key']).'.';return ['ok'=>!$reasons,'reasons'=>$reasons,'checks'=>$rows]; }
function sf_prod_artifact_gate(array $promotion): array { $reasons=[];$ref=trim((string)($promotion['artifact_reference']??''));$sha=strtolower(trim((string)($promotion['artifact_sha256']??'')));if(strlen($ref)<8)$reasons[]='Deployment artifact reference is required.';if(!preg_match('/^[a-f0-9]{64}$/',$sha))$reasons[]='Deployment artifact SHA-256 is required.';return ['ok'=>!$reasons,'reasons'=>$reasons]; }

function sf_prod_update_artifact(int $promotionId,string $reference,string $sha): bool {
    $promotion=sf_prod_promotion($promotionId);if(!$promotion||!in_array($promotion['promotion_status'],['draft','approved'],true))return false;$reference=sf_prod_text($reference,1000);$sha=strtolower(trim($sha));if(strlen($reference)<8||!preg_match('/^[a-f0-9]{64}$/',$sha))return false;return sf_admin_execute('UPDATE production_launch_promotions SET artifact_reference=?,artifact_sha256=?,updated_by_user_id=? WHERE id=?',[$reference,$sha,sf_current_user_id()?:null,$promotionId]);
}

function sf_prod_transition(int $promotionId,string $requested,string $note=''): array {
    $promotion=sf_prod_promotion($promotionId);if(!$promotion||sf_prod_locked($promotion))return ['ok'=>false,'message'=>'Promotion is missing or immutable.'];$current=(string)$promotion['promotion_status'];$allowed=['draft'=>['approved','failed'],'approved'=>['deploying','failed'],'deploying'=>['deployed','failed','rolled_back'],'deployed'=>['verified','failed','rolled_back']];if(!in_array($requested,$allowed[$current]??[],true))return ['ok'=>false,'message'=>'Invalid promotion state transition.'];$reasons=[];
    if($requested==='approved'){$binding=sf_prod_binding_gate((int)$promotion['release_id'],(int)$promotion['certification_run_id'],(int)$promotion['backup_run_id']);if(!$binding['ok'])$reasons=array_merge($reasons,$binding['reasons']);$approval=sf_prod_approval_gate($promotionId);if(!$approval['ok'])$reasons=array_merge($reasons,$approval['reasons']);$phase=sf_prod_phase_gate($promotionId,'pre_deploy');if(!$phase['ok'])$reasons=array_merge($reasons,$phase['reasons']);if(empty($promotion['freeze_at']))$reasons[]='A configuration freeze time is required.';}
    elseif($requested==='deploying'){$artifact=sf_prod_artifact_gate($promotion);if(!$artifact['ok'])$reasons=array_merge($reasons,$artifact['reasons']);$phase=sf_prod_phase_gate($promotionId,'deploy');foreach($phase['checks'] as $check)if($check['check_key']==='artifact_verified'&&$check['check_status']!=='passed')$reasons[]='Artifact verification check must pass before deployment.';}
    elseif($requested==='deployed'){$phase=sf_prod_phase_gate($promotionId,'deploy');if(!$phase['ok'])$reasons=array_merge($reasons,$phase['reasons']);}
    elseif($requested==='verified'){$phase=sf_prod_phase_gate($promotionId,'post_deploy');if(!$phase['ok'])$reasons=array_merge($reasons,$phase['reasons']);}
    elseif($requested==='rolled_back'){$phase=sf_prod_phase_gate($promotionId,'rollback');if(!$phase['ok'])$reasons=array_merge($reasons,$phase['reasons']);}
    if($reasons)return ['ok'=>false,'message'=>'Transition blocked: '.implode(' ',array_slice(array_unique($reasons),0,8))];
    $columns=['deploying'=>'deployment_started_at','deployed'=>'deployed_at','verified'=>'verified_at','rolled_back'=>'rolled_back_at'];$sql='UPDATE production_launch_promotions SET promotion_status=?,updated_by_user_id=?';$params=[$requested,sf_current_user_id()?:null];if(isset($columns[$requested]))$sql.=',`'.$columns[$requested].'`=NOW()';$sql.=' WHERE id=?';$params[]=$promotionId;sf_admin_execute($sql,$params);
    $release=sf_rel_release((int)$promotion['release_id']);if($release){$map=['approved'=>'ready','deploying'=>'deploying','deployed'=>'deployed','rolled_back'=>'rolled_back','failed'=>'failed'];if(isset($map[$requested]))sf_dor_save_release(array_merge($release,['release_status'=>$map[$requested]]),(int)$release['id']);}
    sf_prod_event($promotionId,'promotion.'.$requested,$note!==''?$note:'Promotion advanced to '.$requested.'.',['from'=>$current,'to'=>$requested]);sf_admin_audit('transition_production_launch','production_launch_promotion',$promotionId,$promotion,['status'=>$requested]);return ['ok'=>true,'message'=>'Promotion advanced to '.$requested.'.'];
}

function sf_prod_latest_for_sha(string $sha,array $statuses=['approved','deploying','deployed','verified']): ?array { if(!sf_prod_ready()||!preg_match('/^[a-f0-9]{40}$/i',$sha))return null;$placeholders=implode(',',array_fill(0,count($statuses),'?'));return sf_admin_fetch_one('SELECT * FROM production_launch_promotions WHERE target_commit_sha=? AND promotion_status IN ('.$placeholders.') ORDER BY created_at DESC,id DESC LIMIT 1',array_merge([strtolower($sha)],$statuses)); }
function sf_prod_latest_verified(): ?array { return sf_prod_ready()?sf_admin_fetch_one("SELECT * FROM production_launch_promotions WHERE promotion_status='verified' ORDER BY verified_at DESC,id DESC LIMIT 1"):null; }

function sf_prod_event_secret(): string { return trim((string)(getenv('SF_PRODUCTION_DEPLOYMENT_EVENT_SECRET')?:'')); }
function sf_prod_signature_valid(string $raw,string $provided): bool { $secret=sf_prod_event_secret();if(strlen($secret)<32||$provided==='')return false;$provided=preg_replace('/^sha256=/i','',trim($provided))??'';return strlen($provided)===64&&hash_equals(hash_hmac('sha256',$raw,$secret),$provided); }
function sf_prod_ingest_deployment_event(array $promotion,array $payload): array {
    if(sf_prod_locked($promotion))return ['ok'=>false,'message'=>'Promotion is immutable.'];$eventId=sf_delivery_clean_header((string)($payload['event_id']??''),190);$eventType=sf_delivery_clean_header((string)($payload['event_type']??''),120);$checkKey=(string)($payload['check_key']??'');$result=(string)($payload['result']??'');$message=sf_prod_text($payload['message']??'',8000);$reference=sf_prod_text($payload['evidence_reference']??'',1000);$sha=strtolower(trim((string)($payload['evidence_sha256']??'')));if($eventId===''||$eventType==='')return ['ok'=>false,'message'=>'Event identity is required.'];
    try{sf_prod_event((int)$promotion['id'],$eventType,$message!==''?$message:'Deployment event received.',$payload,'processed',$eventId);}catch(Throwable $e){if(str_contains(strtolower($e->getMessage()),'duplicate'))return ['ok'=>true,'status'=>'duplicate'];return ['ok'=>false,'message'=>'Event could not be stored.'];}
    if($checkKey!==''&&isset(sf_prod_check_catalog()[$checkKey])&&in_array($result,['passed','failed','running'],true))sf_prod_record_check((int)$promotion['id'],$checkKey,$result,$message!==''?$message:'Automated deployment result: '.$result.'.',$reference!==''?$reference:'deployment event '.$eventId,$sha);
    return ['ok'=>true,'status'=>'processed'];
}
?>
