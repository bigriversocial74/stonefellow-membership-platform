<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/backup_release.php';
$user = sf_auth_user();
if (!$user || (($user['role'] ?? '') !== 'admin')) sf_json_response(['ok'=>false,'error'=>'admin_required'],403);
if (!sf_sec_can('admin.settings.manage')) sf_json_response(['ok'=>false,'error'=>'permission_denied'],403);
sf_json_response(['ok'=>true,'summary'=>sf_br_summary(),'profiles'=>sf_br_profiles(),'records'=>sf_br_runs(isset($_GET['limit'])?(int)$_GET['limit']:100),'manifest'=>sf_br_manifest()]);
