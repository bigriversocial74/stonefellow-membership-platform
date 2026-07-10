<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/media_pipeline.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'PUT' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') sf_json_response(['ok'=>false,'error'=>'method_not_allowed'],405);
$csrf = (string)($_SERVER['HTTP_X_STONEFELLOW_CSRF'] ?? '');
if (!sf_verify_csrf($csrf)) sf_json_response(['ok'=>false,'error'=>'csrf_failed'],403);
$token = strtolower(trim((string)($_GET['session'] ?? '')));
$part = filter_var($_GET['part'] ?? null, FILTER_VALIDATE_INT);
$max = sf_mp_upload_chunk_size();
$body = file_get_contents('php://input', false, null, 0, $max + 1);
if (!is_string($body) || strlen($body) > $max) sf_json_response(['ok'=>false,'error'=>'chunk_too_large'],413);
$result = sf_mp_receive_upload_chunk($token, $part === false ? -1 : (int)$part, $body, strtolower(trim((string)($_SERVER['HTTP_X_CHUNK_SHA256'] ?? ''))));
sf_json_response($result, !empty($result['ok']) ? 200 : 422);
