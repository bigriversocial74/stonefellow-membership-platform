<?php
require_once __DIR__ . '/../includes/billing_provider_runtime.php';

sf_security_require_method('POST');

try {
    $raw = sf_security_raw_body(1048576);
    if ($raw === '') throw new InvalidArgumentException('Empty webhook body.');
    $payload = json_decode($raw,true,64,JSON_THROW_ON_ERROR);
    if (!is_array($payload)) throw new InvalidArgumentException('Webhook body must be an object.');
} catch (LengthException $e) {
    sf_json_response(['ok'=>false,'error'=>'payload_too_large'],413);
} catch (Throwable $e) {
    sf_json_response(['ok'=>false,'error'=>'invalid_webhook_payload'],400);
}

$pdo=sf_db();
if(!$pdo || !sf_billing_table_exists('billing_webhook_events')) sf_json_response(['ok'=>false,'error'=>'billing_webhook_unavailable'],503);

$secret=(string)(getenv('SF_BILLING_WEBHOOK_SECRET') ?: '');
$allowUnsigned=!sf_is_production() && sf_env_bool('SF_ALLOW_UNSIGNED_SANDBOX_WEBHOOKS',false);
$provided=trim((string)($_SERVER['HTTP_X_STONEFELLOW_SIGNATURE'] ?? ''));
if($secret==='') {
    if(!$allowUnsigned) sf_json_response(['ok'=>false,'error'=>'webhook_secret_not_configured'],503);
} else {
    $expected=hash_hmac('sha256',$raw,$secret);
    if(!preg_match('/^[a-f0-9]{64}$/i',$provided) || !hash_equals($expected,$provided)) sf_json_response(['ok'=>false,'error'=>'invalid_signature'],401);
}

$provider=substr(strtolower(trim((string)($payload['provider'] ?? sf_billing_provider()))),0,80);
$eventId=substr(trim((string)($payload['id'] ?? $payload['event_id'] ?? '')),0,190);
$eventType=substr(trim((string)($payload['type'] ?? $payload['event_type'] ?? 'unknown')),0,120);
if($eventId==='') $eventId=hash('sha256',$provider.'|'.$eventType.'|'.$raw);
$status='received';$error=null;$processedAt=null;

try {
    $stmt=$pdo->prepare("INSERT IGNORE INTO billing_webhook_events (provider, provider_event_id, event_type, status, payload_json) VALUES (?, ?, ?, 'received', ?)");
    $stmt->execute([$provider,$eventId,$eventType,json_encode($payload,JSON_UNESCAPED_SLASHES)]);
    if($stmt->rowCount()===0) sf_json_response(['ok'=>true,'status'=>'duplicate','event_type'=>$eventType]);

    if(in_array($eventType,['checkout.completed','payment.succeeded','invoice.paid'],true)) {
        $token=(string)($payload['checkout_token'] ?? $payload['data']['checkout_token'] ?? $payload['data']['object']['metadata']['checkout_token'] ?? '');
        if($token==='') { $status='ignored';$error='No checkout token supplied.'; }
        else {
            $result=sf_billing_complete_provider_checkout($token,[
                'provider'=>$provider,
                'provider_payment_id'=>(string)($payload['payment_id'] ?? $payload['data']['payment_id'] ?? $payload['data']['object']['payment_intent'] ?? $eventId),
                'provider_subscription_id'=>(string)($payload['subscription_id'] ?? $payload['data']['subscription_id'] ?? $payload['data']['object']['subscription'] ?? ''),
                'provider_customer_id'=>(string)($payload['customer_id'] ?? $payload['data']['customer_id'] ?? $payload['data']['object']['customer'] ?? ''),
                'payload'=>$payload,
            ]);
            $status=!empty($result['ok'])?'processed':'failed';$error=!empty($result['ok'])?null:(string)($result['message'] ?? 'Checkout activation failed.');
        }
    } elseif(in_array($eventType,['subscription.canceled','customer.subscription.deleted'],true)) {
        $subscriptionRef=(string)($payload['subscription_id'] ?? $payload['data']['subscription_id'] ?? $payload['data']['object']['id'] ?? '');
        if($subscriptionRef!=='' && sf_billing_table_exists('user_subscriptions')) {
            $subscriptionRow=null;
            try{$lookup=$pdo->prepare('SELECT * FROM user_subscriptions WHERE external_subscription_id=? OR provider_subscription_id=? LIMIT 1');$lookup->execute([$subscriptionRef,$subscriptionRef]);$subscriptionRow=$lookup->fetch() ?: null;}catch(Throwable $ignore){}
            $pdo->prepare("UPDATE user_subscriptions SET status='canceled', canceled_at=NOW(), updated_at=NOW() WHERE external_subscription_id=? OR provider_subscription_id=?")->execute([$subscriptionRef,$subscriptionRef]);
            if($subscriptionRow && !empty($subscriptionRow['user_id'])){$recipient=sf_notify_user_recipient((int)$subscriptionRow['user_id']);if($recipient)sf_notify_send_template('subscription_canceled',$recipient,['subscription_status'=>'canceled','period_end'=>(string)($subscriptionRow['current_period_end'] ?? '')],['notification_type'=>'billing','metadata'=>['event'=>'webhook_subscription_canceled','subscription_ref'=>$subscriptionRef],'dispatch'=>true]);}
            $status='processed';
        } else { $status='ignored';$error='No subscription reference supplied.'; }
    } elseif(in_array($eventType,['payment.failed','invoice.payment_failed','charge.failed'],true)) {
        foreach(sf_notify_admin_recipients() as $adminRecipient) sf_notify_send_template('admin_failed_payment',$adminRecipient,['payment_status'=>(string)($payload['status'] ?? $eventType),'provider_payment_id'=>(string)($payload['payment_id'] ?? $payload['data']['payment_id'] ?? $eventId),'error_message'=>(string)($payload['error_message'] ?? $payload['data']['error_message'] ?? 'Provider reported a failed payment.')],['notification_type'=>'admin','metadata'=>['event'=>$eventType,'provider_event_id'=>$eventId],'dispatch'=>true]);
        $status='processed';
    } else $status='ignored';

    if(in_array($status,['processed','ignored'],true)) $processedAt=(new DateTimeImmutable())->format('Y-m-d H:i:s');
    $pdo->prepare('UPDATE billing_webhook_events SET status=?, error_message=?, processed_at=? WHERE provider=? AND provider_event_id=?')->execute([$status,$error,$processedAt,$provider,$eventId]);
    sf_json_response(['ok'=>$status!=='failed','status'=>$status,'event_type'=>$eventType],$status==='failed'?500:200);
} catch(Throwable $e) {
    error_log('Stonefellow billing webhook failed ['.sf_security_request_id().']: '.$e->getMessage());
    try{$pdo->prepare("UPDATE billing_webhook_events SET status='failed', error_message=? WHERE provider=? AND provider_event_id=?")->execute(['Webhook processing failed.',$provider,$eventId]);}catch(Throwable $ignore){}
    sf_json_response(['ok'=>false,'error'=>'webhook_processing_failed'],500);
}
