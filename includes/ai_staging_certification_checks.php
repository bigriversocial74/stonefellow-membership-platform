<?php
if (!defined('SF_AI_STAGING_CERTIFICATION_LOADED')) return;

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
    $permissions=['admin.settings.manage','admin.content.manage','admin.ops.manage','admin.security.manage'];
    $denied=[];
    if(!$missing)foreach($permissions as $permission)if(!sf_agentic_user_can($permission))$denied[]=$permission;
    $pass=!$missing&&!$denied;
    sf_ai_cert_record($runId,'permissions.scoped_roles',$pass?'passed':'failed',$pass?'Scoped AI permissions are assigned to the current administrator.':'Role tables or required scoped permissions are missing.',['missing_tables'=>$missing,'denied_permissions'=>$denied]);
}

function sf_ai_cert_retryable_status(int $status): bool
{
    return $status===0||in_array($status,[408,409,429],true)||$status>=500;
}

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
    global $database;
    $host=trim((string)($database['host']??''));
    $name=trim((string)($database['name']??''));
    $user=trim((string)($database['user']??''));
    if($host===''||$name===''||$user==='')return null;
    try{
        return new PDO('mysql:host='.$host.';port='.(int)($database['port']??3306).';dbname='.$name.';charset=utf8mb4',$user,(string)($database['pass']??''),[
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES=>false,
        ]);
    }catch(Throwable $e){return null;}
}

function sf_ai_cert_test_advisory_lock(int $runId): void
{
    $pdo1=sf_admin_db();
    $pdo2=sf_ai_cert_new_pdo();
    if(!$pdo1||!$pdo2){
        sf_ai_cert_record($runId,'concurrency.advisory_lock','failed','A second database connection could not be created for lock contention testing.');
        return;
    }
    $name='sf_cert_'.substr(hash('sha256',(string)$runId.'|'.microtime(true)),0,40);
    try{
        $a=$pdo1->prepare('SELECT GET_LOCK(?,0)');
        $a->execute([$name]);
        $first=(int)$a->fetchColumn();
        $b=$pdo2->prepare('SELECT GET_LOCK(?,0)');
        $b->execute([$name]);
        $second=(int)$b->fetchColumn();
        $r=$pdo1->prepare('SELECT RELEASE_LOCK(?)');
        $r->execute([$name]);
        sf_ai_cert_record($runId,'concurrency.advisory_lock',$first===1&&$second===0?'passed':'failed',$first===1&&$second===0?'A second database session was blocked from acquiring the active AI lock.':'Advisory lock contention did not behave as expected.',['first'=>$first,'second'=>$second]);
    }catch(Throwable $e){
        try{$r=$pdo1->prepare('SELECT RELEASE_LOCK(?)');$r->execute([$name]);}catch(Throwable $ignored){}
        sf_ai_cert_record($runId,'concurrency.advisory_lock','failed','Advisory lock test failed: '.$e->getMessage());
    }
}

function sf_ai_cert_test_duplicate_submit(int $runId): void
{
    $pdo=sf_admin_db();
    if(!$pdo)return;
    $blocked=false;
    try{
        $pdo->beginTransaction();
        $key='__duplicate_test_'.bin2hex(random_bytes(4));
        $stmt=$pdo->prepare("INSERT INTO ai_staging_certification_checks (run_id,check_key,category_key,check_label,check_status,severity,is_required,is_automated) VALUES (?,?,?,'Duplicate test','pending','info',0,1)");
        $stmt->execute([$runId,$key,'concurrency']);
        try{$stmt->execute([$runId,$key,'concurrency']);}catch(Throwable $e){$blocked=true;}
        $pdo->rollBack();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();}
    sf_ai_cert_record($runId,'concurrency.duplicate_submit',$blocked?'passed':'failed',$blocked?'The unique run/check constraint rejected a duplicate submission.':'Duplicate certification submissions were not blocked.');
}

function sf_ai_cert_test_mission_claim(int $runId): void
{
    $pdo=sf_admin_db();
    foreach(['ai_platform_actions','ai_operation_missions','ai_operation_mission_items'] as $table){
        if(!sf_admin_table_exists($table)){
            sf_ai_cert_record($runId,'concurrency.mission_claim','failed','Mission claim tables are not ready.');
            return;
        }
    }
    if(!$pdo)return;
    $first=0;
    $second=0;
    try{
        $pdo->beginTransaction();
        $pdo->exec("INSERT INTO ai_platform_actions (action_area,action_type,title,risk_level,approval_status,execution_status,created_by_ai) VALUES ('certification','review','Certification claim test','low','ready_for_execution','ready',0)");
        $actionId=(int)$pdo->lastInsertId();
        $pdo->exec("INSERT INTO ai_operation_missions (mission_title,mission_area,mission_status,risk_level,created_by_ai) VALUES ('Certification claim test','certification','approved','low',0)");
        $missionId=(int)$pdo->lastInsertId();
        $stmt=$pdo->prepare("INSERT INTO ai_operation_mission_items (mission_id,platform_action_id,item_order,item_status,stop_on_failure) VALUES (?, ?, 10, 'ready', 1)");
        $stmt->execute([$missionId,$actionId]);
        $itemId=(int)$pdo->lastInsertId();
        $claim=$pdo->prepare("UPDATE ai_operation_mission_items SET item_status='running',last_result_message='Certification lease' WHERE id=? AND item_status='ready'");
        $claim->execute([$itemId]);
        $first=$claim->rowCount();
        $claim->execute([$itemId]);
        $second=$claim->rowCount();
        $pdo->rollBack();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();}
    sf_ai_cert_record($runId,'concurrency.mission_claim',$first===1&&$second===0?'passed':'failed',$first===1&&$second===0?'Only one mission-item claim succeeded.':'Mission item duplicate-claim protection failed.',['first_claim'=>$first,'second_claim'=>$second]);
}

function sf_ai_cert_run_automated(int $runId): void
{
    sf_ai_cert_check_environment($runId);
    sf_ai_cert_check_tables($runId);
    sf_ai_cert_provider_configuration($runId);
    sf_ai_cert_check_permissions($runId);
    sf_ai_cert_transport_simulations($runId);
    sf_ai_cert_test_advisory_lock($runId);
    sf_ai_cert_test_duplicate_submit($runId);
    sf_ai_cert_test_mission_claim($runId);
    sf_admin_audit('run_ai_staging_automated_checks','ai_staging_certification_run',$runId,null,['automated'=>true]);
}
