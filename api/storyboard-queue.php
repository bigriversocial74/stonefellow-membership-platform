<?php
if (!function_exists('mb_substr')) {
  function mb_substr($string, $start, $length = null, $encoding = null) { return $length === null ? substr((string)$string, (int)$start) : substr((string)$string, (int)$start, (int)$length); }
}
require_once __DIR__ . '/../includes/storyboard_queue_export.php';

$user = sf_auth_user();
if (!$user || (($user['role'] ?? '') !== 'admin' && sf_current_access_level() !== 'admin')) sf_json_response(['ok'=>false,'error'=>'admin_required'],403);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') sf_json_response(['ok'=>false,'error'=>'post_required'],405);
if (!sf_verify_csrf($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null))) sf_json_response(['ok'=>false,'error'=>'csrf_failed'],403);

$data = sf_request_json(); if (!$data && $_POST) $data = $_POST;
$action = (string)($data['action'] ?? 'status');
$returnUrl = trim((string)($data['return_url'] ?? ''));
$result = ['ok'=>false,'error'=>'unknown_action'];

if ($action === 'enqueue_scene_image') $result = sf_sbq_enqueue_scene_image((int)($data['scene_id'] ?? 0));
elseif ($action === 'enqueue_bulk_images') $result = sf_sbq_enqueue_bulk_images((int)($data['storyboard_id'] ?? 0));
elseif ($action === 'process_next') $result = sf_sbq_process_next_image_job((int)($data['storyboard_id'] ?? 0));
elseif ($action === 'cancel_job') $result = sf_sbq_cancel_job((int)($data['job_id'] ?? 0));
elseif ($action === 'status') $result = ['ok'=>true,'summary'=>sf_sbq_job_summary((int)($data['storyboard_id'] ?? 0))];

if ($returnUrl !== '') {
  sf_admin_flash(!empty($result['ok']) ? 'success' : 'error', !empty($result['ok']) ? 'Queue action completed.' : ('Queue action failed: ' . ($result['error'] ?? 'unknown_error')));
  header('Location: ' . $returnUrl);
  exit;
}

sf_json_response($result, !empty($result['ok']) ? 200 : 400);
