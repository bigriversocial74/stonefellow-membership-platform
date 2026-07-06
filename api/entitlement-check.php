<?php
require_once __DIR__ . '/../includes/membership.php';

$contentType = (string)($_GET['content_type'] ?? $_GET['type'] ?? 'site_feature');
$contentId = isset($_GET['content_id']) ? (int)$_GET['content_id'] : null;
$required = (string)($_GET['required'] ?? 'subscriber');
$snapshot = sf_entitlement_snapshot();
$allowed = sf_access_allows($required, $snapshot['access_level']) || sf_user_has_direct_grant($contentType, $contentId);

sf_json_response([
  'ok' => true,
  'allowed' => $allowed,
  'required' => $required,
  'content_type' => $contentType,
  'content_id' => $contentId,
  'entitlement' => $snapshot,
]);
