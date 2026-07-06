<?php
require_once __DIR__ . '/../includes/audio_player.php';

$userId = sf_current_user_id();
if (!$userId) {
  sf_json_response(['ok' => false, 'error' => 'login_required'], 401);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  sf_json_response(['ok' => true, 'state' => sf_audio_player_state($userId), 'member' => sf_entitlement_snapshot($userId)]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  sf_json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

$data = sf_request_json();
$state = [
  'queue' => $data['queue'] ?? [],
  'current_song_id' => $data['current_song_id'] ?? null,
  'position_seconds' => $data['position_seconds'] ?? 0,
  'shuffle' => !empty($data['shuffle']),
  'repeat_mode' => $data['repeat_mode'] ?? 'off',
];

$ok = sf_audio_save_player_state($userId, $state);
sf_json_response(['ok' => $ok, 'state' => sf_audio_player_state($userId)]);
