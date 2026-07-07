<?php
require_once __DIR__ . '/../includes/storyboard_queue_export.php';

$user = sf_auth_user();
if (!$user || (($user['role'] ?? '') !== 'admin' && sf_current_access_level() !== 'admin')) {
  sf_json_response(['ok'=>false,'error'=>'admin_required'],403);
}

$storyboardId = (int)($_GET['storyboard_id'] ?? $_POST['storyboard_id'] ?? 0);
$format = strtolower(trim((string)($_GET['format'] ?? $_POST['format'] ?? 'screenplay')));
$storyboard = sf_sba_storyboard($storyboardId);
if (!$storyboard) sf_json_response(['ok'=>false,'error'=>'storyboard_not_found'],404);

$title = (string)($storyboard['title'] ?? 'storyboard');
if ($format === 'json') {
  $content = sf_sbq_export_json($storyboardId);
  $filename = sf_sbq_safe_filename($title, 'storyboard.json');
  header('Content-Type: application/json; charset=utf-8');
} elseif ($format === 'shotlist' || $format === 'csv') {
  $content = sf_sbq_export_shotlist_csv($storyboardId);
  $filename = sf_sbq_safe_filename($title, 'shot-list.csv');
  header('Content-Type: text/csv; charset=utf-8');
} else {
  $content = sf_sbq_export_screenplay_text($storyboardId);
  $filename = sf_sbq_safe_filename($title, 'screenplay.txt');
  header('Content-Type: text/plain; charset=utf-8');
}

if ($content === '') sf_json_response(['ok'=>false,'error'=>'export_empty'],404);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($content));
echo $content;
exit;
