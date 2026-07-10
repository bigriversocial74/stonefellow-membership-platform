<?php
if (!defined('SF_AI_STAGING_CERTIFICATION_LOADED')) return;

function sf_ai_cert_provider_test(int $runId,string $providerKey): array
{
    if(!in_array($providerKey,['chatgpt','claude'],true))return ['ok'=>false,'message'=>'Unsupported provider.'];
    $provider=sf_ai_provider($providerKey);
    if(!$provider)return ['ok'=>false,'message'=>'Provider record not found.'];
    $profile=['type'=>'text','feature'=>'provider_connection_test','target_type'=>'ai_provider','target_id'=>0,'provider_key'=>$providerKey,'count'=>1];
    sf_agentic_guard_live_request($profile);
    $system='You are a provider connectivity test. Return one compact JSON object only. Do not include markdown or any additional text.';
    $expected=$providerKey==='chatgpt'?'openai':'anthropic';
    $user='<TEST_REQUEST>Return exactly {"ok":true,"provider":"'.$expected.'"}</TEST_REQUEST>';
    $result=sf_sbgen_call_provider($provider,$system,$user);
    $decoded=!empty($result['text'])?json_decode(trim((string)$result['text']),true):null;
    $valid=!empty($result['ok'])&&is_array($decoded)&&($decoded['ok']??false)===true&&($decoded['provider']??'')===$expected;
    sf_agentic_finalize_reservation($valid?'success':'failed',$result['usage']??[],$valid?'':(string)($result['error']??'invalid_test_response'));
    sf_agentic_release_locks();
    $message=$valid?'Credential and selected model returned the expected non-mutating JSON response.':'Provider test failed: '.(string)($result['error']??'unexpected provider response');
    sf_admin_execute('UPDATE ai_provider_settings SET test_status=?,test_message=?,tested_at=NOW(),key_status=? WHERE provider_key=?',[$valid?'passed':'failed',substr($message,0,2000),$valid?'connected':'needs_test',$providerKey]);
    sf_ai_cert_record($runId,'providers.'.$providerKey.'.connection',$valid?'passed':'failed',$message,['provider'=>$providerKey,'model'=>$provider['default_model']??'','usage'=>$result['usage']??[]]);
    sf_ai_cert_record($runId,'providers.'.$providerKey.'.model',$valid?'passed':'failed',$valid?'The configured model accepted a bounded test request.':'The configured model could not complete the bounded test request.',['model'=>$provider['default_model']??'']);
    sf_admin_audit('test_ai_provider_connection','ai_provider_settings',null,null,['provider_key'=>$providerKey,'model'=>$provider['default_model']??'','passed'=>$valid]);
    return ['ok'=>$valid,'message'=>$message];
}

function sf_ai_cert_restore_roundtrip(int $runId,string $entityType,int $entityId=0): array
{
    $map=[
        'storyboard'=>['table'=>'storyboards','column'=>'title','check'=>'rollback.storyboard','snapshot'=>'sf_agentic_snapshot_storyboard'],
        'scene'=>['table'=>'storyboard_scenes','column'=>'scene_title','check'=>'rollback.scene','snapshot'=>'sf_agentic_snapshot_scene'],
        'episode'=>['table'=>'story_episodes','column'=>'title','check'=>'rollback.episode','snapshot'=>'sf_agentic_snapshot_episode'],
    ];
    if(!isset($map[$entityType]))return ['ok'=>false,'message'=>'Unsupported restore entity.'];
    $cfg=$map[$entityType];
    if(!sf_admin_table_exists($cfg['table'])){
        sf_ai_cert_record($runId,$cfg['check'],'failed','Required table is missing: '.$cfg['table']);
        return ['ok'=>false,'message'=>'Required table missing.'];
    }
    if($entityId<=0)$entityId=(int)(sf_admin_fetch_one('SELECT id FROM `'.$cfg['table'].'` ORDER BY id DESC LIMIT 1')['id']??0);
    if($entityId<=0){
        sf_ai_cert_record($runId,$cfg['check'],'failed','No record is available for the restore roundtrip.');
        return ['ok'=>false,'message'=>'No record available.'];
    }
    $before=sf_admin_fetch_one('SELECT * FROM `'.$cfg['table'].'` WHERE id=? LIMIT 1',[$entityId]);
    if(!$before)return ['ok'=>false,'message'=>'Record not found.'];
    if(function_exists($cfg['snapshot']))$cfg['snapshot']($entityId,'staging_certification_restore_roundtrip');
    $pdo=sf_admin_db();
    $restored=false;
    try{
        $pdo->beginTransaction();
        $lock=$pdo->prepare('SELECT `'.$cfg['column'].'` FROM `'.$cfg['table'].'` WHERE id=? FOR UPDATE');
        $lock->execute([$entityId]);
        $original=(string)$lock->fetchColumn();
        $marker=substr($original.' [cert-'.bin2hex(random_bytes(3)).']',0,190);
        $update=$pdo->prepare('UPDATE `'.$cfg['table'].'` SET `'.$cfg['column'].'`=? WHERE id=?');
        $update->execute([$marker,$entityId]);
        $changed=(string)$pdo->query('SELECT `'.$cfg['column'].'` FROM `'.$cfg['table'].'` WHERE id='.(int)$entityId)->fetchColumn()===$marker;
        $update->execute([$original,$entityId]);
        $restored=$changed&&(string)$pdo->query('SELECT `'.$cfg['column'].'` FROM `'.$cfg['table'].'` WHERE id='.(int)$entityId)->fetchColumn()===$original;
        if($restored)$pdo->commit();else$pdo->rollBack();
    }catch(Throwable $e){if($pdo&&$pdo->inTransaction())$pdo->rollBack();}
    sf_ai_cert_record($runId,$cfg['check'],$restored?'passed':'failed',$restored?ucfirst($entityType).' was snapshotted, changed, and restored inside a locked transaction.':ucfirst($entityType).' restore roundtrip failed.',['entity_id'=>$entityId,'snapshot_action'=>'ai_pre_mutation_snapshot']);
    return ['ok'=>$restored,'message'=>$restored?'Restore roundtrip passed.':'Restore roundtrip failed.'];
}

function sf_ai_cert_usage_rows(): array
{
    $rows=[];
    foreach(sf_ai_providers() as $provider){
        $key=(string)($provider['provider_key']??'');
        if($key==='')continue;
        $usage=sf_admin_table_exists('ai_usage_events')?sf_admin_fetch_one("SELECT COUNT(*) request_count,COALESCE(SUM(prompt_tokens),0) prompt_tokens,COALESCE(SUM(completion_tokens),0) completion_tokens,COALESCE(SUM(image_count),0) image_count,COALESCE(SUM(estimated_cost_cents),0) reserved_cost_cents FROM ai_usage_events WHERE provider_key=? AND created_at>=DATE_FORMAT(NOW(),'%Y-%m-01')",[$key]):[];
        $rows[]=array_merge(['provider_key'=>$key,'provider_label'=>$provider['provider_label']??$key,'budget_cents'=>(int)($provider['monthly_budget_cents']??0)],$usage?:[]);
    }
    return $rows;
}

function sf_ai_cert_save_cost_reconciliation(int $runId,string $providerKey,int $invoiceCents,string $notes=''): bool
{
    if(!sf_ai_cert_ready()||$runId<=0||!in_array($providerKey,['chatgpt','claude'],true)||$invoiceCents<0)return false;
    $usage=null;
    foreach(sf_ai_cert_usage_rows() as $row){
        if(($row['provider_key']??'')===$providerKey){$usage=$row;break;}
    }
    if(!$usage)return false;
    $reserved=(int)($usage['reserved_cost_cents']??0);
    $variance=$invoiceCents-$reserved;
    $tolerance=max(100,(int)ceil(max($invoiceCents,$reserved)*0.10));
    $status=abs($variance)<=$tolerance?'matched':'review';
    $ok=sf_admin_execute("INSERT INTO ai_staging_provider_cost_reconciliations (run_id,provider_key,period_month,reserved_cost_cents,provider_invoice_cents,variance_cents,prompt_tokens,completion_tokens,image_count,request_count,reconciliation_status,notes,recorded_by_user_id) VALUES (?,?,DATE_FORMAT(NOW(),'%Y-%m'),?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE reserved_cost_cents=VALUES(reserved_cost_cents),provider_invoice_cents=VALUES(provider_invoice_cents),variance_cents=VALUES(variance_cents),prompt_tokens=VALUES(prompt_tokens),completion_tokens=VALUES(completion_tokens),image_count=VALUES(image_count),request_count=VALUES(request_count),reconciliation_status=VALUES(reconciliation_status),notes=VALUES(notes),recorded_by_user_id=VALUES(recorded_by_user_id)",[
        $runId,$providerKey,$reserved,$invoiceCents,$variance,
        (int)($usage['prompt_tokens']??0),(int)($usage['completion_tokens']??0),(int)($usage['image_count']??0),(int)($usage['request_count']??0),
        $status,sf_agentic_text($notes,4000),sf_current_user_id(),
    ]);
    if($ok)sf_ai_cert_refresh_cost_check($runId);
    return $ok;
}

function sf_ai_cert_cost_reconciliations(int $runId): array
{
    return sf_ai_cert_ready()?sf_admin_fetch_all('SELECT * FROM ai_staging_provider_cost_reconciliations WHERE run_id=? ORDER BY provider_key',[$runId]):[];
}

function sf_ai_cert_refresh_cost_check(int $runId): void
{
    $active=array_values(array_filter(sf_ai_providers(),static fn($provider)=>($provider['status']??'')==='active'));
    $rows=sf_ai_cert_cost_reconciliations($runId);
    $byProvider=[];
    foreach($rows as $row)$byProvider[(string)$row['provider_key']]=$row;
    $missing=[];
    $review=[];
    foreach($active as $provider){
        $key=(string)$provider['provider_key'];
        if(!isset($byProvider[$key])||$byProvider[$key]['provider_invoice_cents']===null)$missing[]=$key;
        elseif(($byProvider[$key]['reconciliation_status']??'')!=='matched')$review[]=$key;
    }
    $pass=!$missing&&!$review&&count($active)>0;
    sf_ai_cert_record($runId,'cost.reconciliation',$pass?'passed':'failed',$pass?'All active provider invoice totals match conservative reservations within tolerance.':'Cost reconciliation is incomplete or outside tolerance.',['missing'=>$missing,'review'=>$review,'active_providers'=>array_column($active,'provider_key')]);
}

function sf_ai_cert_manual_check(int $runId,string $checkKey,string $status,string $notes): bool
{
    $catalog=sf_ai_cert_catalog();
    if(!isset($catalog[$checkKey])||!empty($catalog[$checkKey]['automated']))return false;
    return sf_ai_cert_record($runId,$checkKey,$status,$notes,['manual_review'=>true]);
}
