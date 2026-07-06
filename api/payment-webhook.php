<?php
require __DIR__ . '/../includes/payment_gateway.php';
$raw = file_get_contents('php://input') ?: '';
$payload = json_decode($raw, true);
if (!is_array($payload)) {
  $payload = $_POST ?: ['ping' => true, 'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET'];
}
$provider = (string)($payload['provider'] ?? $_GET['provider'] ?? sf_payment_provider());
$eventType = (string)($payload['type'] ?? $payload['event_type'] ?? 'gateway.ping');
$verified = sf_payment_verify_webhook($provider, $raw, $_SERVER);
sf_payment_record_gateway_event($provider, $eventType, $payload, $verified ? 'processed' : 'received', $verified ? null : 'Signature not verified in adapter shell.');
sf_json_response(['ok' => true, 'provider' => $provider, 'event_type' => $eventType, 'verified' => $verified, 'message' => 'Payment gateway webhook endpoint received the event.']);
