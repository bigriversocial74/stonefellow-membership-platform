<?php

declare(strict_types=1);

require __DIR__ . '/../includes/live_commerce.php';
sf_security_require_method('POST');
$secret = trim((string)(getenv('SF_COMMERCE_MAINTENANCE_SECRET') ?: ''));
$timestamp = (int)($_SERVER['HTTP_X_STONEFELLOW_TIMESTAMP'] ?? 0);
$signature = trim((string)($_SERVER['HTTP_X_STONEFELLOW_SIGNATURE'] ?? ''));
if (strlen($secret) < 32 || $timestamp <= 0 || abs(time() - $timestamp) > 300) sf_json_response(['ok' => false, 'error' => 'unauthorized'], 401);
$expected = hash_hmac('sha256', $timestamp . '.commerce-maintenance', $secret);
if (!preg_match('/^[a-f0-9]{64}$/i', $signature) || !hash_equals($expected, $signature)) sf_json_response(['ok' => false, 'error' => 'invalid_signature'], 401);
$released = sf_commerce_release_expired_reservations(2000);
sf_json_response(['ok' => true, 'released_reservations' => $released]);
