<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/media_pipeline.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') sf_json_response(['ok'=>false,'error'=>'method_not_allowed'],405);
$data = sf_request_json(32768);
if (!sf_verify_csrf($data['csrf_token'] ?? null)) sf_json_response(['ok'=>false,'error'=>'csrf_failed'],403);
$result = sf_mp_complete_upload_session(strtolower(trim((string)($data['session_token'] ?? ''))));
sf_json_response($result, !empty($result['ok']) ? 200 : 422);
