<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/media_pipeline.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') sf_json_response(['ok'=>false,'error'=>'method_not_allowed'],405);
$data = sf_request_json(65536);
if (!sf_verify_csrf($data['csrf_token'] ?? null)) sf_json_response(['ok'=>false,'error'=>'csrf_failed'],403);
$result = sf_mp_create_upload_session($data, sf_current_user_id());
sf_json_response($result, !empty($result['ok']) ? 201 : 422);
