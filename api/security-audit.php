<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_security.php';
$user = sf_auth_user();
if (!$user || (($user['role'] ?? '') !== 'admin')) sf_json_response(['ok'=>false,'error'=>'admin_required'],403);
if (!sf_sec_can('admin.audit.view')) sf_json_response(['ok'=>false,'error'=>'permission_denied'],403);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $data = sf_request_json(); if (!$data && $_POST) $data = $_POST;
  sf_sec_audit((string)($data['event_type'] ?? 'manual_audit_event'), (string)($data['severity'] ?? 'info'), (string)($data['entity_type'] ?? ''), (int)($data['entity_id'] ?? 0), (array)($data['metadata'] ?? []));
  sf_json_response(['ok'=>true]);
}
sf_json_response(['ok'=>true,'summary'=>sf_sec_dashboard_summary(),'events'=>sf_sec_audit_events(isset($_GET['limit'])?(int)$_GET['limit']:250),'roles'=>sf_sec_roles(),'admins'=>sf_sec_admin_users()]);
