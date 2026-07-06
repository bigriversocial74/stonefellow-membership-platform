<?php
require __DIR__ . '/../includes/billing.php';

$raw = file_get_contents('php://input') ?: '';
$payload = json_decode($raw, true);
if (!is_array($payload)) {
  $payload = $_POST ?: ['ping' => true, 'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET'];
}
$provider = (string)($payload['provider'] ?? $_GET['provider'] ?? sf_payment_provider());
$eventType = (string)($payload['type'] ?? $payload['event_type'] ?? 'gateway.ping');
$verified = sf_payment_verify_webhook($provider, $raw, $_SERVER);
$status = $verified ? 'processed' : 'received';
$error = $verified ? null : 'Provider verification did not pass.';
$result = ['ok' => false, 'message' => 'Provider verification required.'];
if ($verified) {
  $result = sf_payment_process_gateway_event($provider, $eventType, $payload);
  $status = !empty($result['ok']) ? 'processed' : 'failed';
  $error = !empty($result['ok']) ? null : ($result['message'] ?? 'Processing failed.');
}
sf_payment_record_gateway_event($provider, $eventType, $payload, $status, $error);
sf_json_response(['ok' => true, 'provider' => $provider, 'event_type' => $eventType, 'verified' => $verified, 'status' => $status, 'result' => $result]);
