<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/media_pipeline.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') sf_json_response(['ok'=>false,'error'=>'method_not_allowed'],405);
$secret = sf_mp_env('SF_MEDIA_WORKER_SECRET');
$timestamp = (int)($_SERVER['HTTP_X_STONEFELLOW_TIMESTAMP'] ?? 0);
$signature = strtolower(trim((string)($_SERVER['HTTP_X_STONEFELLOW_SIGNATURE'] ?? '')));
$body = file_get_contents('php://input', false, null, 0, 65536);
$body = is_string($body) ? $body : '';
if (strlen($secret) < 32 || $timestamp <= 0 || abs(time()-$timestamp) > 300) sf_json_response(['ok'=>false,'error'=>'unauthorized'],401);
$expected = hash_hmac('sha256', $timestamp . '.' . hash('sha256',$body), $secret);
if (!preg_match('/^[a-f0-9]{64}$/',$signature) || !hash_equals($expected,$signature)) sf_json_response(['ok'=>false,'error'=>'invalid_signature'],401);
$data = $body === '' ? [] : (json_decode($body,true) ?: []);
$result = sf_mp_run_worker((int)($data['max_jobs'] ?? 3));
$result['expired_upload_sessions'] = sf_mp_expire_upload_sessions(500);
sf_json_response($result);
