<?php
require_once __DIR__ . '/storyboard_scene_actions.php';
require_once __DIR__ . '/storyboarding_system.php';

function sf_sbc_ready(): bool { return sf_storyboard_ready() && sf_admin_table_exists('storyboard_characters') && sf_admin_table_exists('storyboard_character_references'); }
function sf_sbc_storyboard_id_from_character(int $characterId): int { $row = sf_admin_fetch_one('SELECT storyboard_id FROM storyboard_characters WHERE id = ? LIMIT 1', [$characterId]); return (int)($row['storyboard_id'] ?? 0); }
function sf_sbc_catalog_character(int $storyCharacterId): ?array { if ($storyCharacterId <= 0 || !sf_story_v1_ready()) return null; return sf_admin_fetch_one('SELECT * FROM story_characters WHERE id = ? LIMIT 1', [$storyCharacterId]); }
function sf_sbc_catalog_to_consistency(array $row): string { $parts = []; foreach (['personality_notes','relationship_notes','season_arc','motivation'] as $key) { $value = trim((string)($row[$key] ?? '')); if ($value !== '') $parts[] = ucwords(str_replace('_',' ', $key)) . ': ' . $value; } return implode("\n", $parts); }
function sf_sbc_ensure_builder_character_from_catalog(int $storyboardId, int $storyCharacterId): int {
  if (!sf_sbc_ready() || $storyboardId <= 0 || $storyCharacterId <= 0) return 0;
  $catalog = sf_sbc_catalog_character($storyCharacterId); if (!$catalog) return 0;
  $name = trim((string)($catalog['character_name'] ?? '')); if ($name === '') return 0;
  $existing = sf_admin_fetch_one('SELECT id FROM storyboard_characters WHERE storyboard_id = ? AND character_name = ? LIMIT 1', [$storyboardId, $name]);
  $fields = [
    'role_label' => trim((string)($catalog['role_type'] ?? 'Character')) ?: 'Character',
    'appearance_notes' => trim((string)($catalog['short_bio'] ?? '')),
    'personality_notes' => trim((string)($catalog['personality_notes'] ?? '')),
    'wardrobe_notes' => trim((string)($catalog['relationship_notes'] ?? '')),
    'consistency_prompt' => sf_sbc_catalog_to_consistency($catalog),
    'likeness_strength' => 'medium',
    'status' => trim((string)($catalog['status'] ?? 'active')) ?: 'active',
  ];
  if ($existing) {
    sf_admin_execute('UPDATE storyboard_characters SET role_label=?, appearance_notes=?, personality_notes=?, wardrobe_notes=?, consistency_prompt=?, likeness_strength=?, status=?, updated_at=NOW() WHERE id=?', [$fields['role_label'],$fields['appearance_notes'],$fields['personality_notes'],$fields['wardrobe_notes'],$fields['consistency_prompt'],$fields['likeness_strength'],$fields['status'],(int)$existing['id']]);
    return (int)$existing['id'];
  }
  $order = (int)(sf_admin_fetch_one('SELECT COALESCE(MAX(character_order),0) + 1 AS next_order FROM storyboard_characters WHERE storyboard_id = ?', [$storyboardId])['next_order'] ?? 1);
  sf_admin_execute('INSERT INTO storyboard_characters (storyboard_id, character_name, role_label, character_order, appearance_notes, personality_notes, wardrobe_notes, consistency_prompt, likeness_strength, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$storyboardId, $name, $fields['role_label'], $order, $fields['appearance_notes'], $fields['personality_notes'], $fields['wardrobe_notes'], $fields['consistency_prompt'], $fields['likeness_strength'], $fields['status']]);
  return (int)(sf_storyboard_db()?->lastInsertId() ?: 0);
}
function sf_sbc_sync_storyboard_catalog_characters(int $storyboardId, array $storyCharacterIds): array {
  $synced = [];
  foreach (array_unique(array_filter(array_map('intval', $storyCharacterIds))) as $storyCharacterId) {
    $localId = sf_sbc_ensure_builder_character_from_catalog($storyboardId, $storyCharacterId);
    if ($localId > 0) $synced[] = $localId;
  }
  if ($synced) {
    sf_admin_execute('UPDATE storyboards SET updated_at = NOW() WHERE id = ?', [$storyboardId]);
    sf_admin_audit('sync_storyboard_catalog_characters', 'storyboard', $storyboardId, null, ['story_character_ids'=>array_values(array_unique(array_filter(array_map('intval', $storyCharacterIds)))),'local_character_ids'=>$synced]);
  }
  return $synced;
}
function sf_sbc_local_character_id_from_catalog(int $storyboardId, int $storyCharacterId): int {
  $catalog = sf_sbc_catalog_character($storyCharacterId); if (!$catalog) return 0;
  $name = trim((string)($catalog['character_name'] ?? '')); if ($name === '') return 0;
  $row = sf_admin_fetch_one('SELECT id FROM storyboard_characters WHERE storyboard_id = ? AND character_name = ? LIMIT 1', [$storyboardId, $name]);
  return (int)($row['id'] ?? 0);
}
function sf_sbc_add_character(int $storyboardId, array $payload): array {
  if (!sf_sbc_ready()) return ['ok'=>false,'error'=>'character_actions_not_ready'];
  $storyboard = sf_sba_storyboard($storyboardId); if (!$storyboard) return ['ok'=>false,'error'=>'storyboard_not_found'];
  $name = trim((string)($payload['character_name'] ?? $payload['name'] ?? ''));
  if ($name === '') return ['ok'=>false,'error'=>'character_name_required'];
  $existing = sf_admin_fetch_one('SELECT id FROM storyboard_characters WHERE storyboard_id = ? AND character_name = ? LIMIT 1', [$storyboardId, $name]);
  if ($existing) return ['ok'=>false,'error'=>'character_already_exists','character_id'=>(int)$existing['id']];
  $order = (int)(sf_admin_fetch_one('SELECT COALESCE(MAX(character_order),0) + 1 AS next_order FROM storyboard_characters WHERE storyboard_id = ?', [$storyboardId])['next_order'] ?? 1);
  $ok = sf_admin_execute('INSERT INTO storyboard_characters (storyboard_id, character_name, role_label, character_order, appearance_notes, personality_notes, wardrobe_notes, consistency_prompt, likeness_strength, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$storyboardId, $name, trim((string)($payload['role_label'] ?? 'Character')), $order, trim((string)($payload['appearance_notes'] ?? '')), trim((string)($payload['personality_notes'] ?? '')), trim((string)($payload['wardrobe_notes'] ?? '')), trim((string)($payload['consistency_prompt'] ?? '')), trim((string)($payload['likeness_strength'] ?? 'medium')) ?: 'medium', 'active']);
  $characterId = (int)(sf_storyboard_db()?->lastInsertId() ?: 0);
  if ($ok) { sf_admin_execute('UPDATE storyboards SET updated_at = NOW() WHERE id = ?', [$storyboardId]); sf_admin_audit('create_storyboard_character', 'storyboard_character', $characterId, null, ['storyboard_id'=>$storyboardId,'name'=>$name]); }
  return ['ok'=>$ok,'character_id'=>$characterId];
}
function sf_sbc_update_character(int $characterId, array $payload): array {
  if (!sf_sbc_ready()) return ['ok'=>false,'error'=>'character_actions_not_ready'];
  $row = sf_admin_fetch_one('SELECT * FROM storyboard_characters WHERE id = ? LIMIT 1', [$characterId]); if (!$row) return ['ok'=>false,'error'=>'character_not_found'];
  $fields = [trim((string)($payload['character_name'] ?? $row['character_name'])), trim((string)($payload['role_label'] ?? $row['role_label'])), trim((string)($payload['appearance_notes'] ?? $row['appearance_notes'])), trim((string)($payload['personality_notes'] ?? $row['personality_notes'])), trim((string)($payload['wardrobe_notes'] ?? $row['wardrobe_notes'])), trim((string)($payload['consistency_prompt'] ?? $row['consistency_prompt'])), trim((string)($payload['likeness_strength'] ?? $row['likeness_strength'])) ?: 'medium', trim((string)($payload['status'] ?? $row['status'])) ?: 'active', $characterId];
  $ok = sf_admin_execute('UPDATE storyboard_characters SET character_name=?, role_label=?, appearance_notes=?, personality_notes=?, wardrobe_notes=?, consistency_prompt=?, likeness_strength=?, status=?, updated_at=NOW() WHERE id=?', $fields);
  if ($ok) { sf_admin_execute('UPDATE storyboards SET updated_at = NOW() WHERE id = ?', [(int)$row['storyboard_id']]); sf_admin_audit('update_storyboard_character', 'storyboard_character', $characterId, null, ['storyboard_id'=>(int)$row['storyboard_id']]); }
  return ['ok'=>$ok,'character_id'=>$characterId];
}
function sf_sbc_upload_reference(int $characterId, string $field = 'reference_image'): array {
  if (!sf_sbc_ready()) return ['ok'=>false,'error'=>'character_actions_not_ready'];
  $row = sf_admin_fetch_one('SELECT * FROM storyboard_characters WHERE id = ? LIMIT 1', [$characterId]); if (!$row) return ['ok'=>false,'error'=>'character_not_found'];
  $upload = sf_admin_handle_upload($field, 'image', 'storyboard_character_reference', 'Storyboard Character Reference - ' . (string)$row['character_name'], 'Character reference image');
  if (empty($upload['ok'])) return ['ok'=>false,'error'=>$upload['message'] ?? 'upload_failed'];
  $assetId = (int)$upload['id'];
  sf_admin_execute('UPDATE storyboard_character_references SET is_primary = 0 WHERE character_id = ?', [$characterId]);
  sf_admin_execute('INSERT INTO storyboard_character_references (storyboard_id, character_id, media_asset_id, reference_path, reference_type, reference_label, is_primary, consistency_notes, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)', [(int)$row['storyboard_id'], $characterId, $assetId, $upload['path'] ?? '', 'uploaded_image', 'Primary reference', trim((string)($row['consistency_prompt'] ?? '')), sf_current_user_id()]);
  sf_admin_execute('UPDATE storyboard_characters SET reference_asset_id = ?, updated_at=NOW() WHERE id = ?', [$assetId, $characterId]);
  sf_admin_execute('UPDATE storyboards SET updated_at = NOW() WHERE id = ?', [(int)$row['storyboard_id']]);
  sf_admin_audit('upload_storyboard_character_reference', 'storyboard_character', $characterId, null, ['asset_id'=>$assetId]);
  return ['ok'=>true,'character_id'=>$characterId,'asset_id'=>$assetId,'path'=>$upload['path'] ?? ''];
}
function sf_sbc_assign_scene_character(int $storyboardId, int $sceneId, int $characterId, int $storyCharacterId = 0): array {
  if (!sf_sbc_ready()) return ['ok'=>false,'error'=>'character_actions_not_ready'];
  $scene = sf_sba_scene($sceneId); if (!$scene || (int)$scene['storyboard_id'] !== $storyboardId) return ['ok'=>false,'error'=>'scene_not_found'];
  if ($storyCharacterId > 0) $characterId = sf_sbc_ensure_builder_character_from_catalog($storyboardId, $storyCharacterId);
  $char = sf_admin_fetch_one('SELECT id FROM storyboard_characters WHERE id = ? AND storyboard_id = ? LIMIT 1', [$characterId, $storyboardId]); if (!$char) return ['ok'=>false,'error'=>'character_not_found'];
  $ok = sf_admin_execute('INSERT IGNORE INTO storyboard_scene_characters (storyboard_id, scene_id, character_id, presence_label) VALUES (?, ?, ?, ?)', [$storyboardId, $sceneId, $characterId, 'in_scene']);
  if ($ok) sf_admin_audit('assign_scene_character', 'storyboard_scene', $sceneId, null, ['character_id'=>$characterId,'story_character_id'=>$storyCharacterId]);
  return ['ok'=>$ok,'scene_id'=>$sceneId,'character_id'=>$characterId,'story_character_id'=>$storyCharacterId];
}
function sf_sbc_remove_scene_character(int $sceneId, int $characterId, int $storyCharacterId = 0): array {
  if (!sf_sbc_ready()) return ['ok'=>false,'error'=>'character_actions_not_ready'];
  $scene = sf_sba_scene($sceneId); if (!$scene) return ['ok'=>false,'error'=>'scene_not_found'];
  if ($characterId <= 0 && $storyCharacterId > 0) $characterId = sf_sbc_local_character_id_from_catalog((int)$scene['storyboard_id'], $storyCharacterId);
  if ($characterId <= 0) return ['ok'=>false,'error'=>'character_not_found'];
  $ok = sf_admin_execute('DELETE FROM storyboard_scene_characters WHERE scene_id = ? AND character_id = ?', [$sceneId, $characterId]);
  if ($ok) sf_admin_audit('remove_scene_character', 'storyboard_scene', $sceneId, null, ['character_id'=>$characterId,'story_character_id'=>$storyCharacterId]);
  return ['ok'=>$ok,'scene_id'=>$sceneId,'character_id'=>$characterId,'story_character_id'=>$storyCharacterId];
}
function sf_sbc_bulk_regenerate_images(int $storyboardId): array {
  if (!sf_sbc_ready()) return ['ok'=>false,'error'=>'character_actions_not_ready'];
  $rows = sf_admin_fetch_all('SELECT id FROM storyboard_scenes WHERE storyboard_id = ? ORDER BY scene_number ASC', [$storyboardId]);
  $queued = 0; $failed = 0; $errors = [];
  foreach ($rows as $row) { $result = sf_sba_generate_scene_image((int)$row['id']); if (!empty($result['ok'])) $queued++; else { $failed++; $errors[] = $result['error'] ?? 'unknown_error'; } }
  return ['ok'=>$failed === 0,'generated'=>$queued,'failed'=>$failed,'errors'=>array_values(array_unique($errors))];
}
function sf_sbc_recent_jobs(int $storyboardId, int $limit = 10): array {
  if (!sf_admin_table_exists('storyboard_jobs')) return [];
  return sf_admin_fetch_all('SELECT j.*, s.scene_number, s.scene_title FROM storyboard_jobs j LEFT JOIN storyboard_scenes s ON s.id = j.scene_id WHERE j.storyboard_id = ? ORDER BY j.created_at DESC, j.id DESC LIMIT ' . max(1, min(50, $limit)), [$storyboardId]);
}
?>
