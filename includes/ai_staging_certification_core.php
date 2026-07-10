<?php
if (!defined('SF_AI_STAGING_CERTIFICATION_LOADED')) return;

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
        foreach (sf_ai_cert_catalog() as $key=>$check) {
            $insert->execute([$runId,$key,$check['category'],$check['label'],$check['severity'],(int)$check['required'],(int)$check['automated']]);
        }
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

function sf_ai_cert_run(int $runId): ?array
{
    if (!sf_ai_cert_ready() || $runId <= 0) return null;
    return sf_admin_fetch_one('SELECT * FROM ai_staging_certification_runs WHERE id=? LIMIT 1',[$runId]);
}

function sf_ai_cert_checks(int $runId): array
{
    if (!sf_ai_cert_ready() || $runId <= 0) return [];
    return sf_admin_fetch_all("SELECT * FROM ai_staging_certification_checks WHERE run_id=? ORDER BY FIELD(severity,'critical','high','medium','low','info'),category_key,check_label",[$runId]);
}

function sf_ai_cert_record(int $runId,string $checkKey,string $status,string $message,array $evidence=[]): bool
{
    $catalog = sf_ai_cert_catalog();
    if (!sf_ai_cert_ready() || $runId <= 0 || !isset($catalog[$checkKey])) return false;
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
    $required=(int)($row['required_checks']??0);
    $passed=(int)($row['passed_checks']??0);
    $failed=(int)($row['failed_checks']??0);
    $pending=(int)($row['pending_checks']??0);
    $score=$required>0 ? round(($passed/$required)*100,2) : 0;
    sf_admin_execute('UPDATE ai_staging_certification_runs SET overall_score=?,required_checks=?,passed_checks=?,failed_checks=?,pending_checks=? WHERE id=?',[$score,$required,$passed,$failed,$pending,$runId]);
}

function sf_ai_cert_complete(int $runId,string $notes=''): array
{
    $run=sf_ai_cert_run($runId);
    if(!$run) return ['ok'=>false,'message'=>'Certification run not found.'];
    sf_ai_cert_recalculate($runId);
    $run=sf_ai_cert_run($runId) ?: $run;
    $passed=(int)($run['required_checks']??0)>0
        && (int)($run['passed_checks']??0)===(int)($run['required_checks']??0)
        && (int)($run['failed_checks']??0)===0
        && (int)($run['pending_checks']??0)===0;
    $status=$passed?'passed':'failed';
    sf_admin_execute('UPDATE ai_staging_certification_runs SET run_status=?,certification_notes=?,completed_by_user_id=?,completed_at=NOW() WHERE id=?',[$status,sf_agentic_text($notes,8000),sf_current_user_id(),$runId]);
    sf_admin_audit('complete_ai_staging_certification_run','ai_staging_certification_run',$runId,$run,['status'=>$status]);
    return ['ok'=>$passed,'status'=>$status,'message'=>$passed?'Staging certification passed.':'Certification remains incomplete or has failed checks.'];
}
