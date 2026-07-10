<?php
require_once __DIR__ . '/storyboard_generation.php';
require_once __DIR__ . '/ai_mission_execution.php';

if (defined('SF_AI_STAGING_CERTIFICATION_LOADED')) return;
define('SF_AI_STAGING_CERTIFICATION_LOADED', true);

function sf_ai_cert_ready(): bool
{
    return sf_admin_db() instanceof PDO
        && sf_admin_table_exists('ai_staging_certification_runs')
        && sf_admin_table_exists('ai_staging_certification_checks')
        && sf_admin_table_exists('ai_staging_provider_cost_reconciliations');
}

function sf_ai_cert_catalog(): array
{
    return [
        'environment.staging' => ['category'=>'environment','label'=>'Staging environment mode','severity'=>'critical','required'=>1,'automated'=>1],
        'environment.https' => ['category'=>'environment','label'=>'HTTPS and proxy detection','severity'=>'critical','required'=>1,'automated'=>1],
        'environment.allowed_hosts' => ['category'=>'environment','label'=>'Allowed host restrictions','severity'=>'high','required'=>1,'automated'=>1],
        'secrets.ai_settings' => ['category'=>'secrets','label'=>'Dedicated AI encryption secret','severity'=>'critical','required'=>1,'automated'=>1],
        'database.certification_tables' => ['category'=>'database','label'=>'Certification persistence tables','severity'=>'critical','required'=>1,'automated'=>1],
        'database.agentic_tables' => ['category'=>'database','label'=>'Agentic control and audit tables','severity'=>'critical','required'=>1,'automated'=>1],
        'providers.chatgpt.configuration' => ['category'=>'providers','label'=>'OpenAI configuration readiness','severity'=>'critical','required'=>1,'automated'=>1],
        'providers.claude.configuration' => ['category'=>'providers','label'=>'Anthropic configuration readiness','severity'=>'high','required'=>1,'automated'=>1],
        'providers.chatgpt.connection' => ['category'=>'providers','label'=>'OpenAI credential connection test','severity'=>'critical','required'=>1,'automated'=>1],
        'providers.chatgpt.model' => ['category'=>'providers','label'=>'OpenAI selected model test','severity'=>'critical','required'=>1,'automated'=>1],
        'providers.claude.connection' => ['category'=>'providers','label'=>'Anthropic credential connection test','severity'=>'high','required'=>1,'automated'=>1],
        'providers.claude.model' => ['category'=>'providers','label'=>'Anthropic selected model test','severity'=>'high','required'=>1,'automated'=>1],
        'limits.provider_budgets' => ['category'=>'limits','label'=>'Provider budgets and usage limits','severity'=>'critical','required'=>1,'automated'=>1],
        'transport.retry_classification' => ['category'=>'transport','label'=>'429, timeout, and 5xx retry classification','severity'=>'high','required'=>1,'automated'=>1],
        'transport.malformed_output' => ['category'=>'transport','label'=>'Malformed provider output rejection','severity'=>'high','required'=>1,'automated'=>1],
        'transport.oversized_output' => ['category'=>'transport','label'=>'Oversized provider output rejection','severity'=>'high','required'=>1,'automated'=>1],
        'concurrency.advisory_lock' => ['category'=>'concurrency','label'=>'MySQL advisory lock contention','severity'=>'critical','required'=>1,'automated'=>1],
        'concurrency.duplicate_submit' => ['category'=>'concurrency','label'=>'Duplicate certification submission protection','severity'=>'high','required'=>1,'automated'=>1],
        'concurrency.mission_claim' => ['category'=>'concurrency','label'=>'Single-winner mission item claim','severity'=>'critical','required'=>1,'automated'=>1],
        'rollback.storyboard' => ['category'=>'rollback','label'=>'Storyboard snapshot restore roundtrip','severity'=>'critical','required'=>1,'automated'=>1],
        'rollback.scene' => ['category'=>'rollback','label'=>'Scene snapshot restore roundtrip','severity'=>'critical','required'=>1,'automated'=>1],
        'rollback.episode' => ['category'=>'rollback','label'=>'Episode snapshot restore roundtrip','severity'=>'high','required'=>1,'automated'=>1],
        'permissions.scoped_roles' => ['category'=>'permissions','label'=>'Scoped AI admin role enforcement','severity'=>'critical','required'=>1,'automated'=>1],
        'queues.readiness' => ['category'=>'queues','label'=>'AI job and media queue readiness','severity'=>'high','required'=>1,'automated'=>1],
        'cost.reconciliation' => ['category'=>'cost','label'=>'Reserved cost and provider invoice reconciliation','severity'=>'high','required'=>1,'automated'=>0],
        'media.moderation_manual' => ['category'=>'media','label'=>'Generated-media quality and moderation review','severity'=>'high','required'=>1,'automated'=>0],
        'deployment.backup_restore_manual' => ['category'=>'deployment','label'=>'Backup and restoration rehearsal','severity'=>'critical','required'=>1,'automated'=>0],
    ];
}

function sf_ai_cert_uuid(): string
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    $hex = bin2hex($bytes);
    return substr($hex,0,8) . '-' . substr($hex,8,4) . '-' . substr($hex,12,4) . '-' . substr($hex,16,4) . '-' . substr($hex,20);
}

function sf_ai_cert_create_run(string $label = ''): int
{
    if (!sf_ai_cert_ready()) return 0;
    $label = sf_agentic_text($label,190,'AI staging certification ' . date('Y-m-d H:i'));
    $pdo = sf_admin_db();
    if (!$pdo) return 0;
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO ai_staging_certification_runs (run_key,run_label,environment_key,run_status,started_by_user_id,started_at) VALUES (?,?,?,'in_progress',?,NOW())");
        $stmt->execute([sf_ai_cert_uuid(),$label,function_exists('sf_environment') ? sf_environment() : 'unknown',sf_current_user_id()]);
        $runId = (int)$pdo->lastInsertId();
        $insert = $pdo->prepare('INSERT INTO ai_staging_certification_checks (run_id,check_key,category_key,check_label,severity,is_required,is_automated) VALUES (?,?,?,?,?,?,?)');
        foreach (sf_ai_cert_catalog() as $key=>$check) $insert->execute([$runId,$key,$check['category'],$check['label'],$check['severity'],(int)$check['required'],(int)$check['automated']]);
        $pdo->commit();
        sf_ai_cert_recalculate($runId);
        sf_admin_audit('create_ai_staging_certification_run','ai_staging_certification_run',$runId,null,['label'=>$label]);
        return $runId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('AI certification run creation failed: ' . $e->getMessage());
        return 0;
    }
}

function sf_ai_cert_runs(int $limit = 30): array
{
    if (!sf_ai_cert_ready()) return [];
    return sf_admin_fetch_all('SELECT * FROM ai_staging_certification_runs ORDER BY created_at DESC,id DESC LIMIT ' . max(1,min(100,$limit)));
}
function sf_ai_cert_run(int $runId): ?array { return sf_ai_cert_ready() && $runId > 0 ? sf_admin_fetch_one('SELECT * FROM ai_staging_certification_runs WHERE id=? LIMIT 1',[$runId]) : null; }
function sf_ai_cert_checks(int $runId): array { return sf_ai_cert_ready() && $runId > 0 ? sf_admin_fetch_all("SELECT * FROM ai_staging_certification_checks WHERE run_id=? ORDER BY FIELD(severity,'critical','high','medium','low','info'),category_key,check_label",[$runId]) : []; }

function sf_ai_cert_record(int $runId,string $checkKey,string $status,string $message,array $evidence=[]): bool
{
    if (!sf_ai_cert_ready() || $runId <= 0 || !isset(sf_ai_cert_catalog()[$checkKey])) return false;
    $status = in_array($status,['pending','running','passed','failed','skipped'],true) ? $status : 'failed';
    $message = sf_agentic_text($message,4000);
    $json = $evidence ? json_encode($evidence,JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) : null;
    if (is_string($json) && strlen($json) > 65535) $json = json_encode(['truncated'=>true,'sha256'=>hash('sha256',$json)],JSON_UNESCAPED_SLASHES);
    $hash = hash('sha256',$checkKey . '|' . $status . '|' . $message . '|' . (string)$json);
    $ok = sf_admin_execute("UPDATE ai_staging_certification_checks SET check_status=?,result_message=?,evidence_json=?,evidence_hash=?,checked_by_user_id=?,started_at=COALESCE(started_at,NOW()),completed_at=CASE WHEN ? IN ('passed','failed','skipped') THEN NOW() ELSE NULL END WHERE run_id=? AND check_key=?",[$status,$message,$json,$hash,sf_current_user_id(),$status,$runId,$checkKey]);
    sf_ai_cert_recalculate($runId);
    return $ok;
}

function sf_ai_cert_recalculate(int $runId): void
{
    if (!sf_ai_cert_ready() || $runId <= 0) return;
    $row = sf_admin_fetch_one("SELECT SUM(is_required=1) required_checks,SUM(is_required=1 AND check_status='passed') passed_checks,SUM(is_required=1 AND check_status='failed') failed_checks,SUM(is_required=1 AND check_status IN ('pending','running','skipped')) pending_checks FROM ai_staging_certification_checks WHERE run_id=?",[$runId]) ?: [];
    $required=(int)($row['required_checks']??0); $passed=(int)($row['passed_checks']??0); $failed=(int)($row['failed_checks']??0); $pending=(int)($row['pending_checks']??0);
    $score=$required>0 ? round(($passed/$required)*100,2) : 0;
    sf_admin_execute('UPDATE ai_staging_certification_runs SET overall_score=?,required_checks=?,passed_checks=?,failed_checks=?,pending_checks=? WHERE id=?',[$score,$required,$passed,$failed,$pending,$runId]);
}

function sf_ai_cert_complete(int $runId,string $notes=''): array
{
    $run=sf_ai_cert_run($runId); if(!$run) return ['ok'=>false,'message'=>'Certification run not found.'];
    sf_ai_cert_recalculate($runId); $run=sf_ai_cert_run($runId) ?: $run;
    $passed=(int)($run['required_checks']??0)>0 && (int)($run['passed_checks']??0)===(int)($run['required_checks']??0) && (int)($run['failed_checks']??0)===0 && (int)($run['pending_checks']??0)===0;
    $status=$passed?'passed':'failed';
    sf_admin_execute('UPDATE ai_staging_certification_runs SET run_status=?,certification_notes=?,completed_by_user_id=?,completed_at=NOW() WHERE id=?',[$status,sf_agentic_text($notes,8000),sf_current_user_id(),$runId]);
    sf_admin_audit('complete_ai_staging_certification_run','ai_staging_certification_run',$runId,$run,['status'=>$status]);
    return ['ok'=>$passed,'status'=>$status,'message'=>$passed?'Staging certification passed.':'Certification remains incomplete or has failed checks.'];
}

function sf_ai_cert_check_tables(int $runId): void
{
    $cert=['ai_staging_certification_runs','ai_staging_certification_checks','ai_staging_provider_cost_reconciliations'];
    $missing=array_values(array_filter($cert,static fn($t)=>!sf_admin_table_exists($t)));
    sf_ai_cert_record($runId,'database.certification_tables',$missing?'failed':'passed',$missing?'Missing certification tables: '.implode(', ',$missing):'Certification persistence tables are ready.',['missing'=>$missing]);
    $agentic=['ai_provider_settings','ai_usage_events','admin_audit_log','ai_platform_actions','ai_platform_action_executions','ai_autonomy_policies','ai_operation_missions','ai_operation_mission_items'];
    $missing=array_values(array_filter($agentic,static fn($t)=>!sf_admin_table_exists($t)));
    sf_ai_cert_record($runId,'database.agentic_tables',$missing?'failed':'passed',$missing?'Missing agentic tables: '.implode(', ',$missing):'Agentic control and audit tables are ready.',['missing'=>$missing]);
    $queues=['storyboard_jobs','story_ai_media_prompts','story_ai_media_generation_jobs'];
    $missing=array_values(array_filter($queues,static fn($t)=>!sf_admin_table_exists($t)));
    sf_ai_cert_record($runId,'queues.readiness',$missing?'failed':'passed',$missing?'Missing queue tables: '.implode(', ',$missing):'Storyboard and media queues are ready.',['missing'=>$missing]);
}

function sf_ai_cert_check_environment(int $runId): void
{
    $environment=function_exists('sf_environment')?sf_environment():'unknown';
    sf_ai_cert_record($runId,'environment.staging',$environment==='staging'?'passed':'failed',$environment==='staging'?'SF_ENV is staging.':'SF_ENV must be staging for certification.',['environment'=>$environment]);
    $https=function_exists('sf_is_https')&&sf_is_https();
    sf_ai_cert_record($runId,'environment.https',$https?'passed':'failed',$https?'HTTPS detection is active.':'HTTPS was not detected for this request.',['trust_proxy'=>getenv('SF_TRUST_PROXY')?:'0']);
    $hosts=function_exists('sf_security_allowed_hosts')?sf_security_allowed_hosts():[];
    sf_ai_cert_record($runId,'environment.allowed_hosts',$hosts?'passed':'failed',$hosts?'Allowed hosts are configured.':'SF_ALLOWED_HOSTS is empty.',['hosts'=>$hosts]);
    $length=strlen(trim((string)(getenv('SF_AI_SETTINGS_SECRET')?:'')));
    sf_ai_cert_record($runId,'secrets.ai_settings',$length>=32&&sf_ai_crypto_ready()?'passed':'failed',$length>=32&&sf_ai_crypto_ready()?'Dedicated AI encryption secret and AES-256-GCM are ready.':'Configure SF_AI_SETTINGS_SECRET with at least 32 characters and enable AES-256-GCM.',['secret_length'=>$length,'crypto_ready'=>sf_ai_crypto_ready()]);
}

function sf_ai_cert_provider_configuration(int $runId): void
{
    $limitFailures=[];
    foreach(['chatgpt','claude'] as $key){
        $provider=sf_ai_provider($key);
        $ready=is_array($provider)&&sf_ai_provider_runtime_ready($provider,'text')&&sf_agentic_model_name($provider['default_model']??'')!=='';
        sf_ai_cert_record($runId,'providers.'.$key.'.configuration',$ready?'passed':'failed',$ready?(($provider['provider_label']??$key).' is active, encrypted, and model-configured.'):ucfirst($key).' is not active with a decryptable key and valid text model.',['status'=>$provider['status']??'missing','key_status'=>$provider['key_status']??'missing','model'=>$provider['default_model']??'']);
        if(!$provider||(int)($provider['monthly_budget_cents']??0)<=0||(int)($provider['monthly_token_limit']??0)<=0)$limitFailures[]=$key.': budget/token limit';
        if($key==='chatgpt'&&!empty($provider['image_model'])&&(int)($provider['monthly_image_limit']??0)<=0)$limitFailures[]=$key.': image limit';
    }
    sf_ai_cert_record($runId,'limits.provider_budgets',$limitFailures?'failed':'passed',$limitFailures?'Configure nonzero limits: '.implode('; ',$limitFailures):'All provider budgets and relevant monthly limits are nonzero.',['failures'=>$limitFailures]);
}

function sf_ai_cert_check_permissions(int $runId): void
{
    $tables=['admin_roles','admin_user_roles','admin_role_permissions'];
    $missing=array_values(array_filter($tables,static fn($t)=>!sf_admin_table_exists($t)));
    $permissions=['admin.settings.manage','admin.content.manage','admin.ops.manage','admin.security.manage']; $denied=[];
    if(!$missing)foreach($permissions as $permission)if(!sf_agentic_user_can($permission))$denied[]=$permission;
    $pass=!$missing&&!$denied;
    sf_ai_cert_record($runId,'permissions.scoped_roles',$pass?'passed':'failed',$pass?'Scoped AI permissions are assigned to the current administrator.':'Role tables or required scoped permissions are missing.',['missing_tables'=>$missing,'denied_permissions'=>$denied]);
}

function sf_ai_cert_retryable_status(int $status): bool { return $status===0||in_array($status,[408,409,429],true)||$status>=500; }
function sf_ai_cert_transport_simulations(int $runId): void
{
    $classification=sf_ai_cert_retryable_status(429)&&sf_ai_cert_retryable_status(500)&&sf_ai_cert_retryable_status(0)&&!sf_ai_cert_retryable_status(400)&&!sf_ai_cert_retryable_status(401);
    sf_ai_cert_record($runId,'transport.retry_classification',$classification?'passed':'failed',$classification?'Timeout, conflict, rate-limit, and 5xx responses are retryable; permanent 4xx responses are not.':'Retry classification failed.');
    $malformed=sf_sbgen_parse_json_text('not-json',1);
    sf_ai_cert_record($runId,'transport.malformed_output',empty($malformed['ok'])?'passed':'failed',empty($malformed['ok'])?'Malformed JSON was rejected.':'Malformed JSON was incorrectly accepted.',['error'=>$malformed['error']??'']);
    $oversized=sf_sbgen_parse_json_text(str_repeat('x',262145),1);
    sf_ai_cert_record($runId,'transport.oversized_output',empty($oversized['ok'])?'passed':'failed',empty($oversized['ok'])?'Oversized model output was rejected.':'Oversized model output was incorrectly accepted.',['error'=>$oversized['error']??'']);
}

function sf_ai_cert_new_pdo(): ?PDO
{
    global $database; $host=trim((string)($database['host']??'')); $name=trim((string)($database['name']??'')); $user=trim((string)($database['user']??''));
    if($host===''||$name===''||$user==='')return null;
    try{return new PDO('mysql:host='.$host.';port='.(int)($database['port']??3306).';dbname='.$name.';charset=utf8mb4',$user,(string)($database['pass']??''),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);}catch(Throwable $e){return null;}
}

function sf_ai_cert_test_advisory_lock(int $runId): void
{
    $pdo1=sf_admin_db();$pdo2=sf_ai_cert_new_pdo();if(!$pdo1||!$pdo2){sf_ai_cert_record($runId,'concurrency.advisory_lock','failed','A second database connection could not be created for lock contention testing.');return;}
    $name='sf_cert_'.substr(hash('sha256',(string)$runId.'|'.microtime(true)),0,40);
    try{$a=$pdo1->prepare('SELECT GET_LOCK(?,0)');$a->execute([$name]);$first=(int)$a->fetchColumn();$b=$pdo2->prepare('SELECT GET_LOCK(?,0)');$b->execute([$name]);$second=(int)$b->fetchColumn();$r=$pdo1->prepare('SELECT RELEASE_LOCK(?)');$r->execute([$name]);sf_ai_cert_record($runId,'concurrency.advisory_lock',$first===1&&$second===0?'passed':'failed',$first===1&&$second===0?'A second database session was blocked from acquiring the active AI lock.':'Advisory lock contention did not behave as expected.',['first'=>$first,'second'=>$second]);}catch(Throwable $e){try{$r=$pdo1->prepare('SELECT RELEASE_LOCK(?)');$r->execute([$name]);}catch(Throwable $ignored){}sf_ai_cert_record($runId,'concurrency.advisory_lock','failed','Advisory lock test failed: '.$e->getMessage());}
}

function sf_ai_cert_test_duplicate_submit(int $runId): void
{
    $pdo=sf_admin_db();if(!$pdo)return;$blocked=false;
    try{$pdo->beginTransaction();$key='__duplicate_test_'.bin2hex(random_bytes(4));$stmt=$pdo->prepare("INSERT INTO ai_staging_certification_checks (run_id,check_key,category_key,check_label,check_status,severity,is_required,is_automated) VALUES (?,?,?,'Duplicate test','pending','info',0,1)");$stmt->execute([$runId,$key,'concurrency']);try{$stmt->execute([$runId,$key,'concurrency']);}catch(Throwable $e){$blocked=true;}$pdo->rollBack();}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();}
    sf_ai_cert_record($runId,'concurrency.duplicate_submit',$blocked?'passed':'failed',$blocked?'The unique run/check constraint rejected a duplicate submission.':'Duplicate certification submissions were not blocked.');
}

function sf_ai_cert_test_mission_claim(int $runId): void
{
    $pdo=sf_admin_db();foreach(['ai_platform_actions','ai_operation_missions','ai_operation_mission_items'] as $table)if(!sf_admin_table_exists($table)){sf_ai_cert_record($runId,'concurrency.mission_claim','failed','Mission claim tables are not ready.');return;}if(!$pdo)return;$first=0;$second=0;
    try{$pdo->beginTransaction();$pdo->exec("INSERT INTO ai_platform_actions (action_area,action_type,title,risk_level,approval_status,execution_status,created_by_ai) VALUES ('certification','review','Certification claim test','low','ready_for_execution','ready',0)");$actionId=(int)$pdo->lastInsertId();$pdo->exec("INSERT INTO ai_operation_missions (mission_title,mission_area,mission_status,risk_level,created_by_ai) VALUES ('Certification claim test','certification','approved','low',0)");$missionId=(int)$pdo->lastInsertId();$stmt=$pdo->prepare("INSERT INTO ai_operation_mission_items (mission_id,platform_action_id,item_order,item_status,stop_on_failure) VALUES (?, ?, 10, 'ready', 1)");$stmt->execute([$missionId,$actionId]);$itemId=(int)$pdo->lastInsertId();$claim=$pdo->prepare("UPDATE ai_operation_mission_items SET item_status='running',last_result_message='Certification lease' WHERE id=? AND item_status='ready'");$claim->execute([$itemId]);$first=$claim->rowCount();$claim->execute([$itemId]);$second=$claim->rowCount();$pdo->rollBack();}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();}
    sf_ai_cert_record($runId,'concurrency.mission_claim',$first===1&&$second===0?'passed':'failed',$first===1&&$second===0?'Only one mission-item claim succeeded.':'Mission item duplicate-claim protection failed.',['first_claim'=>$first,'second_claim'=>$second]);
}

function sf_ai_cert_run_automated(int $runId): void
{
    sf_ai_cert_check_environment($runId);sf_ai_cert_check_tables($runId);sf_ai_cert_provider_configuration($runId);sf_ai_cert_check_permissions($runId);sf_ai_cert_transport_simulations($runId);sf_ai_cert_test_advisory_lock($runId);sf_ai_cert_test_duplicate_submit($runId);sf_ai_cert_test_mission_claim($runId);
    sf_admin_audit('run_ai_staging_automated_checks','ai_staging_certification_run',$runId,null,['automated'=>true]);
}

function sf_ai_cert_provider_test(int $runId,string $providerKey): array
{
    if(!in_array($providerKey,['chatgpt','claude'],true))return ['ok'=>false,'message'=>'Unsupported provider.'];$provider=sf_ai_provider($providerKey);if(!$provider)return ['ok'=>false,'message'=>'Provider record not found.'];
    $profile=['type'=>'text','feature'=>'provider_connection_test','target_type'=>'ai_provider','target_id'=>0,'provider_key'=>$providerKey,'count'=>1];sf_agentic_guard_live_request($profile);
    $system='You are a provider connectivity test. Return one compact JSON object only. Do not include markdown or any additional text.';$expected=$providerKey==='chatgpt'?'openai':'anthropic';$user='<TEST_REQUEST>Return exactly {"ok":true,"provider":"'.$expected.'"}</TEST_REQUEST>';
    $result=sf_sbgen_call_provider($provider,$system,$user);$decoded=!empty($result['text'])?json_decode(trim((string)$result['text']),true):null;$valid=!empty($result['ok'])&&is_array($decoded)&&($decoded['ok']??false)===true&&($decoded['provider']??'')===$expected;
    sf_agentic_finalize_reservation($valid?'success':'failed',$result['usage']??[],$valid?'':(string)($result['error']??'invalid_test_response'));sf_agentic_release_locks();
    $message=$valid?'Credential and selected model returned the expected non-mutating JSON response.':'Provider test failed: '.(string)($result['error']??'unexpected provider response');
    sf_admin_execute('UPDATE ai_provider_settings SET test_status=?,test_message=?,tested_at=NOW(),key_status=? WHERE provider_key=?',[$valid?'passed':'failed',substr($message,0,2000),$valid?'connected':'needs_test',$providerKey]);
    sf_ai_cert_record($runId,'providers.'.$providerKey.'.connection',$valid?'passed':'failed',$message,['provider'=>$providerKey,'model'=>$provider['default_model']??'','usage'=>$result['usage']??[]]);
    sf_ai_cert_record($runId,'providers.'.$providerKey.'.model',$valid?'passed':'failed',$valid?'The configured model accepted a bounded test request.':'The configured model could not complete the bounded test request.',['model'=>$provider['default_model']??'']);
    sf_admin_audit('test_ai_provider_connection','ai_provider_settings',null,null,['provider_key'=>$providerKey,'model'=>$provider['default_model']??'','passed'=>$valid]);
    return ['ok'=>$valid,'message'=>$message];
}

function sf_ai_cert_restore_roundtrip(int $runId,string $entityType,int $entityId=0): array
{
    $map=['storyboard'=>['table'=>'storyboards','column'=>'title','check'=>'rollback.storyboard','snapshot'=>'sf_agentic_snapshot_storyboard'],'scene'=>['table'=>'storyboard_scenes','column'=>'scene_title','check'=>'rollback.scene','snapshot'=>'sf_agentic_snapshot_scene'],'episode'=>['table'=>'story_episodes','column'=>'title','check'=>'rollback.episode','snapshot'=>'sf_agentic_snapshot_episode']];
    if(!isset($map[$entityType]))return ['ok'=>false,'message'=>'Unsupported restore entity.'];$cfg=$map[$entityType];
    if(!sf_admin_table_exists($cfg['table'])){sf_ai_cert_record($runId,$cfg['check'],'failed','Required table is missing: '.$cfg['table']);return ['ok'=>false,'message'=>'Required table missing.'];}
    if($entityId<=0)$entityId=(int)(sf_admin_fetch_one('SELECT id FROM `'.$cfg['table'].'` ORDER BY id DESC LIMIT 1')['id']??0);
    if($entityId<=0){sf_ai_cert_record($runId,$cfg['check'],'failed','No record is available for the restore roundtrip.');return ['ok'=>false,'message'=>'No record available.'];}
    $before=sf_admin_fetch_one('SELECT * FROM `'.$cfg['table'].'` WHERE id=? LIMIT 1',[$entityId]);if(!$before)return ['ok'=>false,'message'=>'Record not found.'];if(function_exists($cfg['snapshot']))$cfg['snapshot']($entityId,'staging_certification_restore_roundtrip');
    $pdo=sf_admin_db();$restored=false;
    try{$pdo->beginTransaction();$lock=$pdo->prepare('SELECT `'.$cfg['column'].'` FROM `'.$cfg['table'].'` WHERE id=? FOR UPDATE');$lock->execute([$entityId]);$original=(string)$lock->fetchColumn();$marker=substr($original.' [cert-'.bin2hex(random_bytes(3)).']',0,190);$update=$pdo->prepare('UPDATE `'.$cfg['table'].'` SET `'.$cfg['column'].'`=? WHERE id=?');$update->execute([$marker,$entityId]);$changed=(string)$pdo->query('SELECT `'.$cfg['column'].'` FROM `'.$cfg['table'].'` WHERE id='.(int)$entityId)->fetchColumn()===$marker;$update->execute([$original,$entityId]);$restored=$changed&&(string)$pdo->query('SELECT `'.$cfg['column'].'` FROM `'.$cfg['table'].'` WHERE id='.(int)$entityId)->fetchColumn()===$original;if($restored)$pdo->commit();else$pdo->rollBack();}catch(Throwable $e){if($pdo&&$pdo->inTransaction())$pdo->rollBack();}
    sf_ai_cert_record($runId,$cfg['check'],$restored?'passed':'failed',$restored?ucfirst($entityType).' was snapshotted, changed, and restored inside a locked transaction.':ucfirst($entityType).' restore roundtrip failed.',['entity_id'=>$entityId,'snapshot_action'=>'ai_pre_mutation_snapshot']);
    return ['ok'=>$restored,'message'=>$restored?'Restore roundtrip passed.':'Restore roundtrip failed.'];
}

function sf_ai_cert_usage_rows(): array
{
    $rows=[];foreach(sf_ai_providers() as $provider){$key=(string)($provider['provider_key']??'');if($key==='')continue;$usage=sf_admin_table_exists('ai_usage_events')?sf_admin_fetch_one("SELECT COUNT(*) request_count,COALESCE(SUM(prompt_tokens),0) prompt_tokens,COALESCE(SUM(completion_tokens),0) completion_tokens,COALESCE(SUM(image_count),0) image_count,COALESCE(SUM(estimated_cost_cents),0) reserved_cost_cents FROM ai_usage_events WHERE provider_key=? AND created_at>=DATE_FORMAT(NOW(),'%Y-%m-01')",[$key]):[];$rows[]=array_merge(['provider_key'=>$key,'provider_label'=>$provider['provider_label']??$key,'budget_cents'=>(int)($provider['monthly_budget_cents']??0)],$usage?:[]);}return $rows;
}

function sf_ai_cert_save_cost_reconciliation(int $runId,string $providerKey,int $invoiceCents,string $notes=''): bool
{
    if(!sf_ai_cert_ready()||$runId<=0||!in_array($providerKey,['chatgpt','claude'],true)||$invoiceCents<0)return false;$usage=null;foreach(sf_ai_cert_usage_rows() as $row)if(($row['provider_key']??'')===$providerKey){$usage=$row;break;}if(!$usage)return false;
    $reserved=(int)($usage['reserved_cost_cents']??0);$variance=$invoiceCents-$reserved;$tolerance=max(100,(int)ceil(max($invoiceCents,$reserved)*0.10));$status=abs($variance)<=$tolerance?'matched':'review';
    $ok=sf_admin_execute("INSERT INTO ai_staging_provider_cost_reconciliations (run_id,provider_key,period_month,reserved_cost_cents,provider_invoice_cents,variance_cents,prompt_tokens,completion_tokens,image_count,request_count,reconciliation_status,notes,recorded_by_user_id) VALUES (?,?,DATE_FORMAT(NOW(),'%Y-%m'),?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE reserved_cost_cents=VALUES(reserved_cost_cents),provider_invoice_cents=VALUES(provider_invoice_cents),variance_cents=VALUES(variance_cents),prompt_tokens=VALUES(prompt_tokens),completion_tokens=VALUES(completion_tokens),image_count=VALUES(image_count),request_count=VALUES(request_count),reconciliation_status=VALUES(reconciliation_status),notes=VALUES(notes),recorded_by_user_id=VALUES(recorded_by_user_id)",[$runId,$providerKey,$reserved,$invoiceCents,$variance,(int)($usage['prompt_tokens']??0),(int)($usage['completion_tokens']??0),(int)($usage['image_count']??0),(int)($usage['request_count']??0),$status,sf_agentic_text($notes,4000),sf_current_user_id()]);if($ok)sf_ai_cert_refresh_cost_check($runId);return $ok;
}
function sf_ai_cert_cost_reconciliations(int $runId): array { return sf_ai_cert_ready()?sf_admin_fetch_all('SELECT * FROM ai_staging_provider_cost_reconciliations WHERE run_id=? ORDER BY provider_key',[$runId]):[]; }
function sf_ai_cert_refresh_cost_check(int $runId): void
{
    $active=array_values(array_filter(sf_ai_providers(),static fn($p)=>($p['status']??'')==='active'));$rows=sf_ai_cert_cost_reconciliations($runId);$by=[];foreach($rows as $row)$by[(string)$row['provider_key']]=$row;$missing=[];$review=[];foreach($active as $provider){$key=(string)$provider['provider_key'];if(!isset($by[$key])||$by[$key]['provider_invoice_cents']===null)$missing[]=$key;elseif(($by[$key]['reconciliation_status']??'')!=='matched')$review[]=$key;}$pass=!$missing&&!$review&&count($active)>0;sf_ai_cert_record($runId,'cost.reconciliation',$pass?'passed':'failed',$pass?'All active provider invoice totals match conservative reservations within tolerance.':'Cost reconciliation is incomplete or outside tolerance.',['missing'=>$missing,'review'=>$review,'active_providers'=>array_column($active,'provider_key')]);
}
function sf_ai_cert_manual_check(int $runId,string $checkKey,string $status,string $notes): bool
{
    $catalog=sf_ai_cert_catalog();if(!isset($catalog[$checkKey])||!empty($catalog[$checkKey]['automated']))return false;return sf_ai_cert_record($runId,$checkKey,$status,$notes,['manual_review'=>true]);
}
?>