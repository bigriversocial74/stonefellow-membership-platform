<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/media_delivery.php';
require_once __DIR__ . '/includes/media_pipeline.php';
require_once __DIR__ . '/includes/revenue_access_governance.php';

$validation = sf_mp_validate_object_token(trim((string)($_GET['t'] ?? '')));
if (empty($validation['ok'])) { http_response_code(401); echo 'Stonefellow media unavailable.'; exit; }
$payload = $validation['payload'];
$object = sf_mp_object_by_id((int)$payload['oid']);
if (!$object || ($object['status'] ?? '') !== 'ready') { http_response_code(404); echo 'Stonefellow media unavailable.'; exit; }
$current = sf_current_user_id();
if ((int)($payload['uid'] ?? 0) > 0 && (int)$payload['uid'] !== (int)$current) { http_response_code(401); echo 'Stonefellow media unavailable.'; exit; }
$role = (string)($object['role'] ?? 'stream');
$publicRole = in_array($role,['preview','poster','thumbnail','waveform'],true) || ($object['visibility'] ?? '') === 'public';
$allowed = $publicRole;
if (!$allowed && $object['entity_type'] === 'song') { $record=sf_media_song_record((int)$object['entity_id']); $allowed=$record&&sf_media_user_can_access('song',$record,$role==='download'?'full':'stream'); }
if (!$allowed && $object['entity_type'] === 'video') { $record=sf_media_video_record((int)$object['entity_id']); $allowed=$record&&sf_media_user_can_access('video',$record,'stream'); }
if (!$allowed) { http_response_code(403); echo 'Stonefellow media unavailable.'; exit; }
if (($object['driver'] ?? 'local') === 'local') sf_mp_serve_local_object($object,(string)($payload['d']??'inline'));
$url = sf_mp_remote_object_url($object,300);
if ($url === '') { http_response_code(503); echo 'Stonefellow media provider unavailable.'; exit; }
header('Cache-Control: private, no-store');
header('Location: '.$url, true, 302);
exit;
