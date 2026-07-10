<?php

declare(strict_types=1);
require_once __DIR__ . '/staging_launch_certification.php';
require_once __DIR__ . '/delivery_integrity.php';

if (defined('SF_STAGING_INTEGRATION_MATRIX_LOADED')) return;
define('SF_STAGING_INTEGRATION_MATRIX_LOADED', true);

function sf_sim_ready(): bool {
    return sf_slc_ready()
        && sf_admin_table_exists('staging_integration_executions')
        && sf_admin_table_exists('staging_integration_assertions')
        && sf_admin_table_exists('staging_integration_events');
}
function sf_sim_uuid(): string { return sf_slc_uuid(); }
function sf_sim_text($value,int $max=8000): string { return sf_slc_text($value,$max); }
function sf_sim_locked(array $execution): bool { return in_array((string)($execution['execution_status']??''),['passed','aborted'],true); }

function sf_sim_catalog(): array {
    return [
      'auth_account_lifecycle'=>[
        'label'=>'Authentication account lifecycle','stage'=>'authentication','checks'=>['authentication.account_flow'],
        'assertions'=>[
          'signup_verified'=>'New account is created with the expected default role and verification state.',
          'login_throttle'=>'Repeated invalid login attempts trigger the configured throttle without account enumeration.',
          'password_reset'=>'Password reset email, single-use token, expiration, and password-policy enforcement pass.',
          'session_revocation'=>'Password change and logout revoke tracked sessions and remember tokens.',
        ],
      ],
      'auth_role_separation'=>[
        'label'=>'Authentication role separation','stage'=>'authentication','checks'=>['authentication.role_separation'],
        'assertions'=>[
          'member_denied_admin'=>'A member account cannot access administrator routes or mutation APIs.',
          'scoped_admin_permissions'=>'A scoped administrator can use allowed routes and is denied unassigned capabilities.',
          'revoked_session_denied'=>'A revoked administrator session remains denied and is not reactivated by tracking.',
        ],
      ],
      'billing_checkout_activation'=>[
        'label'=>'Billing checkout and entitlement activation','stage'=>'billing','checks'=>['billing.checkout_webhook'],
        'assertions'=>[
          'checkout_idempotency'=>'Duplicate checkout submission creates one provider checkout and one pending local claim.',
          'signed_webhook'=>'Unsigned and replayed provider events are rejected; the signed event is accepted once.',
          'amount_currency_match'=>'Activation verifies exact settled amount, currency, provider, and payment identifier.',
          'entitlement_activation'=>'The paid plan grants only its expected capabilities and media access.',
        ],
      ],
      'billing_subscription_lifecycle'=>[
        'label'=>'Billing subscription lifecycle','stage'=>'billing','checks'=>['billing.lifecycle'],
        'assertions'=>[
          'upgrade_downgrade'=>'Upgrade and downgrade replace old grants without privilege leakage.',
          'provider_cancel'=>'Cancellation requires provider confirmation and preserves access only through the valid period.',
          'failed_payment'=>'Past-due or failed-payment state removes or limits entitlement according to policy.',
          'refund_reconciliation'=>'Refund and reconciliation evidence matches local invoices and provider records.',
        ],
      ],
      'media_access_delivery'=>[
        'label'=>'Media access and signed delivery','stage'=>'media','checks'=>['media.access_matrix','media.signed_delivery'],
        'assertions'=>[
          'public_preview'=>'Anonymous and free accounts receive previews rather than full protected assets.',
          'tier_matrix'=>'Subscriber, premium, and founding-fan accounts receive only their allowed catalog tiers.',
          'signed_expiration'=>'Expired or altered signed media URLs are rejected.',
          'cross_account_rejection'=>'A user-bound media token cannot be replayed by another authenticated account.',
          'download_entitlement'=>'Offline delivery requires the explicit download capability.',
        ],
      ],
      'media_tracking_resume'=>[
        'label'=>'Media tracking, completion, and resume','stage'=>'media','checks'=>['media.tracking'],
        'assertions'=>[
          'bounded_progress'=>'Server-side progress rejects impossible client duration and completion claims.',
          'seek_replay_refresh'=>'Seek, replay, and refresh behavior do not create duplicate completion credit.',
          'resume_position'=>'Audio, video, and episode resume positions persist and remain content-bound.',
          'playlist_library'=>'Library and playlist writes remain user-owned and catalog validated.',
        ],
      ],
      'notification_provider_delivery'=>[
        'label'=>'Notification provider delivery and webhooks','stage'=>'notifications','checks'=>['notifications.delivery'],
        'assertions'=>[
          'inbox_delivery'=>'A transactional test message is accepted by the provider and arrives in the target inbox.',
          'signed_events'=>'Delivery, bounce, complaint, and failure callbacks require valid signatures and event IDs.',
          'retry_backoff'=>'Temporary failure enters bounded exponential backoff and stops at the maximum attempt count.',
          'idempotent_delivery'=>'Duplicate queue and provider events do not create duplicate sends or state transitions.',
        ],
      ],
      'notification_preferences_campaigns'=>[
        'label'=>'Notification preferences and campaigns','stage'=>'notifications','checks'=>['notifications.preferences'],
        'assertions'=>[
          'preference_suppression'=>'Disabled marketing channels are recorded as skipped and receive no message.',
          'transactional_preserved'=>'Essential auth, billing, commerce, and security notices are not suppressed as marketing.',
          'audience_snapshot'=>'Campaign audience membership is snapshotted before delivery and remains immutable during send.',
          'channel_outcomes'=>'Email and in-app channels record independent success, failure, or skip outcomes.',
        ],
      ],
      'content_release_integrity'=>[
        'label'=>'Content publishing, import, and moderation','stage'=>'content','checks'=>['content.publishing','content.import_rollback','content.moderation'],
        'assertions'=>[
          'scheduled_publish'=>'A scheduled item publishes only through the authorized due runner at the expected time.',
          'access_search'=>'Draft, archived, expired, and unauthorized content is absent from public pages and search.',
          'import_atomicity'=>'Import preview, bounded commit, and rollback complete atomically with matching digest evidence.',
          'moderation_abuse'=>'Comment validation, parent binding, rate limits, reaction locking, and moderation permissions pass.',
        ],
      ],
      'ai_supervised_execution'=>[
        'label'=>'AI supervised execution and recovery','stage'=>'ai','checks'=>['ai.supervision'],
        'assertions'=>[
          'approval_required'=>'No mission action executes before explicit approval and route-policy authorization.',
          'single_item_execution'=>'Mission execution processes one allowlisted item at a time without freeform or bulk bypass.',
          'budget_and_limits'=>'Provider budget, request limit, token bound, and concurrency controls block excess use.',
          'snapshot_restore'=>'Story, scene, or episode mutation is restored exactly from a verified snapshot.',
        ],
      ],
      'operations_concurrency_recovery'=>[
        'label'=>'Operations concurrency, backup, and restore','stage'=>'operations','checks'=>['operations.scheduler_concurrency','operations.inventory_concurrency','operations.backup_restore','operations.preflight'],
        'assertions'=>[
          'scheduler_exclusion'=>'Concurrent scheduler, campaign, notification, and publishing workers respect advisory locks.',
          'inventory_exclusion'=>'Concurrent checkout attempts cannot oversell or double-apply inventory and payment state.',
          'verified_backup'=>'Database, uploads, and configuration backup artifact has digest, size, timestamp, and location evidence.',
          'isolated_restore'=>'The backup restores in isolation and passes admin login, critical queries, and protected media checks.',
          'preflight_clean'=>'Deployment preflight exits successfully for the exact release commit and certificate.',
        ],
      ],
      'browser_quality_matrix'=>[
        'label'=>'Browser, accessibility, and performance matrix','stage'=>'browser','checks'=>['browser.mobile','browser.accessibility','browser.performance'],
        'assertions'=>[
          'desktop_browsers'=>'Current Chrome, Firefox, Safari, and Edge complete core public/member/admin flows.',
          'mobile_responsive'=>'iOS Safari and Android Chrome layouts, navigation, forms, and media controls pass.',
          'accessibility'=>'Keyboard, focus, screen reader, contrast, zoom, forced colors, and reduced motion pass.',
          'performance'=>'Core Web Vitals and media-loading budgets meet the approved staging thresholds.',
        ],
      ],
    ];
}

function sf_sim_scenarios(): array { $out=[];foreach(sf_sim_catalog() as $key=>$s){$s['scenario_key']=$key;$out[]=$s;}return $out; }
function sf_sim_execution(int $id): ?array { return sf_sim_ready()&&$id>0?sf_admin_fetch_one('SELECT * FROM staging_integration_executions WHERE id=? LIMIT 1',[$id]):null; }
function sf_sim_execution_by_key(string $key): ?array { return sf_sim_ready()&&$key!==''?sf_admin_fetch_one('SELECT * FROM staging_integration_executions WHERE execution_key=? LIMIT 1',[$key]):null; }
function sf_sim_executions(int $runId=0,int $limit=100): array { if(!sf_sim_ready())return [];$where=$runId>0?'WHERE e.certification_run_id=?':'';$params=$runId>0?[$runId]:[];return sf_admin_fetch_all('SELECT e.*,r.run_label FROM staging_integration_executions e INNER JOIN staging_launch_certification_runs r ON r.id=e.certification_run_id '.$where.' ORDER BY e.created_at DESC,e.id DESC LIMIT '.max(1,min(300,$limit)),$params); }
function sf_sim_assertions(int $executionId): array { return sf_sim_ready()&&$executionId>0?sf_admin_fetch_all('SELECT * FROM staging_integration_assertions WHERE execution_id=? ORDER BY id',[$executionId]):[]; }
function sf_sim_events(int $executionId,int $limit=100): array { return sf_sim_ready()&&$executionId>0?sf_admin_fetch_all('SELECT * FROM staging_integration_events WHERE execution_id=? ORDER BY created_at DESC,id DESC LIMIT '.max(1,min(300,$limit)),[$executionId]):[]; }

function sf_sim_create_execution(int $runId,string $scenarioKey,string $accountReference=''): int {
    if(!sf_sim_ready())return 0;$run=sf_slc_run($runId);$catalog=sf_sim_catalog();if(!$run||sf_slc_locked($run)||!isset($catalog[$scenarioKey]))return 0;
    $scenario=$catalog[$scenarioKey];$pdo=sf_admin_db();if(!$pdo)return 0;$correlation='sim-'.date('YmdHis').'-'.substr(bin2hex(random_bytes(6)),0,12);
    try{$pdo->beginTransaction();$s=$pdo->prepare("INSERT INTO staging_integration_executions (certification_run_id,execution_key,scenario_key,scenario_label,execution_status,test_account_reference,correlation_id,environment_key,started_by_user_id) VALUES (?,?,?,?, 'running',?,?,?,?)");$s->execute([$runId,sf_sim_uuid(),$scenarioKey,$scenario['label'],sf_sim_text($accountReference,255)?:null,$correlation,sf_slc_env(),sf_current_user_id()?:null]);$id=(int)$pdo->lastInsertId();$a=$pdo->prepare('INSERT INTO staging_integration_assertions (execution_id,assertion_key,assertion_label,is_required) VALUES (?,?,?,1)');foreach($scenario['assertions'] as $key=>$label)$a->execute([$id,$key,$label]);$pdo->commit();sf_admin_audit('create_staging_integration_execution','staging_integration_execution',$id,null,['scenario'=>$scenarioKey,'run_id'=>$runId,'correlation_id'=>$correlation]);return $id;}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();error_log('Staging integration execution create failed: '.$e->getMessage());return 0;}
}

function sf_sim_record_assertion(int $executionId,string $assertionKey,string $status,string $message,string $sourceReference='',string $sha='',array $evidence=[]): bool {
    $execution=sf_sim_execution($executionId);if(!$execution||sf_sim_locked($execution))return false;$catalog=sf_sim_catalog();$scenario=$catalog[$execution['scenario_key']]??null;if(!$scenario||!isset($scenario['assertions'][$assertionKey]))return false;
    $status=in_array($status,['pending','running','passed','failed','skipped'],true)?$status:'failed';$message=sf_sim_text($message,8000);$sourceReference=sf_sim_text($sourceReference,1000);$sha=strtolower(trim($sha));if($sha!==''&&!preg_match('/^[a-f0-9]{64}$/',$sha))return false;if($status==='passed'&&(strlen($message)<12||strlen($sourceReference)<8))return false;
    $json=$evidence?json_encode($evidence,JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE):null;
    return sf_admin_execute('UPDATE staging_integration_assertions SET assertion_status=?,result_message=?,source_reference=?,evidence_sha256=?,evidence_json=?,checked_by_user_id=?,checked_at=NOW() WHERE execution_id=? AND assertion_key=?',[$status,$message,$sourceReference?:null,$sha?:null,$json,sf_current_user_id()?:null,$executionId,$assertionKey]);
}

function sf_sim_assertion_counts(int $executionId): array { return sf_admin_fetch_one("SELECT COUNT(*) total,SUM(is_required=1) required_count,SUM(is_required=1 AND assertion_status='passed') passed_count,SUM(is_required=1 AND assertion_status='failed') failed_count,SUM(is_required=1 AND assertion_status IN ('pending','running','skipped')) pending_count FROM staging_integration_assertions WHERE execution_id=?",[$executionId])?:['total'=>0,'required_count'=>0,'passed_count'=>0,'failed_count'=>0,'pending_count'=>0]; }

function sf_sim_complete_execution(int $executionId,string $summary): array {
    $execution=sf_sim_execution($executionId);if(!$execution||sf_sim_locked($execution))return ['ok'=>false,'message'=>'Execution is missing or immutable.'];$counts=sf_sim_assertion_counts($executionId);$passed=(int)$counts['required_count']>0&&(int)$counts['passed_count']===(int)$counts['required_count']&&(int)$counts['failed_count']===0&&(int)$counts['pending_count']===0;$status=$passed?'passed':'failed';
    sf_admin_execute('UPDATE staging_integration_executions SET execution_status=?,summary=?,completed_by_user_id=?,completed_at=NOW() WHERE id=?',[$status,sf_sim_text($summary,12000),sf_current_user_id()?:null,$executionId]);
    $catalog=sf_sim_catalog();$scenario=$catalog[$execution['scenario_key']]??null;if($scenario){foreach($scenario['checks'] as $checkKey){$message=$passed?'Integration scenario passed: '.$scenario['label'].'.':'Integration scenario failed or remains incomplete: '.$scenario['label'].'.';sf_slc_record((int)$execution['certification_run_id'],$checkKey,$passed?'passed':'failed',$message,['integration_execution_id'=>$executionId,'execution_key'=>$execution['execution_key'],'correlation_id'=>$execution['correlation_id'],'assertion_counts'=>$counts]);}}
    sf_admin_audit('complete_staging_integration_execution','staging_integration_execution',$executionId,$execution,['status'=>$status,'counts'=>$counts]);return ['ok'=>$passed,'status'=>$status,'message'=>$passed?'Scenario passed and certification checks were updated.':'Scenario failed or has incomplete required assertions.'];
}

function sf_sim_redact($value){if(!is_array($value))return is_scalar($value)?sf_sim_text((string)$value,1000):null;$out=[];foreach(array_slice($value,0,100,true) as $key=>$item){$normalized=strtolower((string)$key);if(preg_match('/(email|name|address|authorization|cookie|token|secret|password|body|html|text)/',$normalized)){$out[$key]='[redacted]';continue;}$out[$key]=sf_sim_redact($item);}return $out;}
function sf_sim_event_secret(): string { return trim((string)(getenv('SF_STAGING_INTEGRATION_EVENT_SECRET')?:'')); }
function sf_sim_event_signature_valid(string $raw,string $provided): bool { $secret=sf_sim_event_secret();if(strlen($secret)<32||$provided==='')return false;$provided=preg_replace('/^sha256=/i','',trim($provided))??'';return strlen($provided)===64&&hash_equals(hash_hmac('sha256',$raw,$secret),$provided); }
function sf_sim_ingest_event(array $execution,string $eventId,string $eventType,string $provider,string $assertionKey,array $payload): array {
    if(!sf_sim_ready()||sf_sim_locked($execution))return ['ok'=>false,'status'=>'rejected','message'=>'Execution is unavailable or immutable.'];$catalog=sf_sim_catalog();$scenario=$catalog[$execution['scenario_key']]??null;if(!$scenario||!isset($scenario['assertions'][$assertionKey]))return ['ok'=>false,'status'=>'rejected','message'=>'Assertion is not part of this scenario.'];
    $eventId=sf_delivery_clean_header($eventId,190);$eventType=sf_delivery_clean_header($eventType,120);$provider=strtolower(sf_delivery_clean_header($provider,80));if($eventId===''||$eventType===''||!preg_match('/^[a-z0-9_-]{1,80}$/',$provider))return ['ok'=>false,'status'=>'rejected','message'=>'Event identity is invalid.'];$hash=hash('sha256',json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE));
    try{$pdo=sf_admin_db();$pdo->beginTransaction();$s=$pdo->prepare("INSERT INTO staging_integration_events (execution_id,source_event_id,event_type,provider,event_status,assertion_key,payload_hash,redacted_payload_json,processed_at) VALUES (?,?,?,?,'processed',?,?,?,NOW())");$s->execute([(int)$execution['id'],$eventId,$eventType,$provider,$assertionKey,$hash,json_encode(sf_sim_redact($payload),JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE)]);$pdo->commit();return ['ok'=>true,'status'=>'processed','message'=>'Event correlated to execution.','payload_hash'=>$hash];}catch(Throwable $e){if(isset($pdo)&&$pdo instanceof PDO&&$pdo->inTransaction())$pdo->rollBack();if(str_contains(strtolower($e->getMessage()),'duplicate'))return ['ok'=>true,'status'=>'duplicate','message'=>'Event was already recorded.'];error_log('Staging integration event failed: '.$e->getMessage());return ['ok'=>false,'status'=>'rejected','message'=>'Event could not be stored.'];}
}
?>
