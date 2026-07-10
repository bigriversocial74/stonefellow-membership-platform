<?php
require __DIR__ . '/../includes/billing_provider_runtime.php';

sf_security_require_method('POST');

try {
    $raw = sf_security_raw_body(1048576);
    if ($raw === '') throw new InvalidArgumentException('Empty webhook body.');
    $payload = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
    if (!is_array($payload)) throw new InvalidArgumentException('Webhook body must be an object.');
} catch (LengthException $e) {
    sf_json_response(['ok'=>false,'error'=>'payload_too_large'],413);
} catch (Throwable $e) {
    sf_json_response(['ok'=>false,'error'=>'invalid_webhook_payload'],400);
}

$provider = strtolower(trim((string)($payload['provider'] ?? $_GET['provider'] ?? sf_payment_provider())));
if (!in_array($provider,['sandbox','stripe','paypal'],true)) sf_json_response(['ok'=>false,'error'=>'unsupported_provider'],400);
if ($provider === 'paypal') sf_json_response(['ok'=>false,'error'=>'paypal_webhook_verification_not_configured'],501);
$eventType = substr(trim((string)($payload['type'] ?? $payload['event_type'] ?? 'gateway.unknown')),0,120);
$eventId = substr(trim((string)($payload['id'] ?? $payload['event_id'] ?? '')),0,190);
if ($eventId === '') $eventId = hash('sha256',$provider.'|'.$eventType.'|'.$raw);
$payload['id'] = $eventId;

$verified = sf_payment_verify_webhook($provider,$raw,$_SERVER);
if (!$verified) {
    sf_payment_record_gateway_event($provider,$eventType,$payload,'rejected','Provider signature verification failed.');
    sf_json_response(['ok'=>false,'provider'=>$provider,'event_type'=>$eventType,'verified'=>false,'status'=>'rejected','error'=>'invalid_signature'],401);
}

$pdo = sf_db();
if ($pdo && sf_settings_table_exists('payment_gateway_webhook_events')) {
    try {
        $seen=$pdo->prepare("SELECT status FROM payment_gateway_webhook_events WHERE provider=? AND provider_event_id=? LIMIT 1");
        $seen->execute([$provider,$eventId]);
        if (in_array((string)$seen->fetchColumn(),['processed','ignored'],true)) {
            sf_json_response(['ok'=>true,'provider'=>$provider,'event_type'=>$eventType,'verified'=>true,'status'=>'duplicate']);
        }
    } catch (Throwable $e) { error_log('Stonefellow gateway duplicate check failed: '.$e->getMessage()); }
}

sf_payment_record_gateway_event($provider,$eventType,$payload,'received');
$result = sf_payment_process_gateway_event($provider,$eventType,$payload);
$status = !empty($result['ok']) ? 'processed' : 'failed';
$error = !empty($result['ok']) ? null : (string)($result['message'] ?? 'Processing failed.');
sf_payment_record_gateway_event($provider,$eventType,$payload,$status,$error);
sf_json_response(['ok'=>!empty($result['ok']),'provider'=>$provider,'event_type'=>$eventType,'verified'=>true,'status'=>$status,'result'=>$result],!empty($result['ok'])?200:500);
