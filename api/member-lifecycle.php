<?php
require_once __DIR__ . '/../includes/member_lifecycle_support.php';
$user = sf_auth_user();
if (!$user || (($user['role'] ?? '') !== 'admin' && sf_current_access_level() !== 'admin')) sf_json_response(['ok'=>false,'error'=>'admin_required'],403);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') sf_json_response(['ok'=>true,'summary'=>sf_lifecycle_summary(),'members'=>sf_lifecycle_segments(),'tasks'=>sf_lifecycle_tasks(0,100)]);
$data = sf_request_json();
if (!$data && $_POST) $data = $_POST;
$action = (string)($data['action'] ?? 'task');
if ($action === 'note') sf_json_response(['ok'=>sf_lifecycle_create_note((int)($data['user_id'] ?? 0),(string)($data['note'] ?? ''),(string)($data['note_type'] ?? 'general'))]);
if ($action === 'task') sf_json_response(['ok'=>sf_lifecycle_create_task((int)($data['user_id'] ?? 0),(string)($data['title'] ?? ''),(string)($data['detail'] ?? ''),(string)($data['task_type'] ?? 'manual'),(string)($data['priority'] ?? 'medium'),(string)($data['due_at'] ?? ''))]);
if ($action === 'task_status') sf_json_response(['ok'=>sf_lifecycle_update_task((int)($data['task_id'] ?? 0),(string)($data['status'] ?? 'open'))]);
sf_json_response(['ok'=>false,'error'=>'unsupported_action'],422);
