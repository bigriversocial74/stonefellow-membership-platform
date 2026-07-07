<?php
require_once __DIR__ . '/storyboard_generation.php';

function sf_sba_ready(): bool { return sf_sbgen_ready() && sf_admin_table_exists('storyboard_scene_characters'); }
function sf_sba_h($value): string { return sf_storyboard_h($value); }
function sf_sba_scene(int $sceneId): ?array { if (!sf_sba_ready() || $sceneId <= 0) return null; return sf_admin_fetch_one('SELECT s.*, b.title AS storyboard_title, b.default_text_provider, b.default_image_provider, b.visual_style, b.aspect_ratio FROM storyboard_scenes s INNER JOIN storyboards b ON b.id = s.storyboard_id WHERE s.id = ? LIMIT 1', [$sceneId]); }
function sf_sba_storyboard(int $storyboardId): ?array { if (!sf_storyboard_ready() || $storyboardId <= 0) return null; return sf_admin_fetch_one('SELECT * FROM storyboards WHERE id = ? LIMIT 1', [$storyboardId]); }
function sf_sba_scene_characters(int $storyboardId, int $sceneId): array { if (!sf_sba_ready()) return []; return sf_admin_fetch_all('SELECT c.*, ma.file_path AS reference_path FROM storyboard_scene_characters l INNER JOIN storyboard_characters c ON c.id = l.character_id LEFT JOIN media_assets ma ON ma.id = c.reference_asset_id WHERE l.storyboard_id = ? AND l.scene_id = ? ORDER BY c.character_order ASC, c.id ASC', [$storyboardId, $sceneId]); }
function sf_sba_character_consistency_prompt(int $storyboardId, int $sceneId = 0): string {
  $rows = $sceneId > 0 ? sf_sba_scene_characters($storyboardId, $sceneId) : sf_admin_fetch_all('SELECT c.*, ma.file_path AS reference_path FROM storyboard_characters c LEFT JOIN media_assets ma ON ma.id = c.reference_asset_id WHERE c.storyboard_id = ? AND c.status = ? ORDER BY c.character_order ASC, c.id ASC', [$storyboardId, 'active']);
  $lines = [];
  foreach ($rows as $row) {
    $parts = [];
    $parts[] = 'Character: ' . (string)($row['character_name'] ?? '');
    if (!empty($row['role_label'])) $parts[] = 'Role: ' . $row['role_label'];
    if (!empty($row['appearance_notes'])) $parts[] = 'Appearance: ' . $row['appearance_notes'];
    if (!empty($row['wardrobe_notes'])) $parts[] = 'Wardrobe: ' . $row['wardrobe_notes'];
    if (!empty($row['consistency_prompt'])) $parts[] = 'Consistency: ' . $row['consistency_prompt'];
    if (!empty($row['reference_path'])) $parts[] = 'Reference image path: ' . $row['reference_path'];
    $lines[] = implode(' | ', $parts);
  }
  return implode("\n", $lines);
}
function sf_sba_scene_context(int $storyboardId, int $sceneId): string {
  $scenes = sf_admin_fetch_all('SELECT scene_number, scene_title, scene_summary FROM storyboard_scenes WHERE storyboard_id = ? ORDER BY scene_number ASC', [$storyboardId]);
  $lines = [];
  foreach ($scenes as $scene) $lines[] = 'Scene ' . (int)$scene['scene_number'] . ': ' . (string)$scene['scene_title'] . ' — ' . (string)$scene['scene_summary'];
  return implode("\n", $lines);
}
function sf_sba_update_scene(int $sceneId, array $payload): array {
  $scene = sf_sba_scene($sceneId); if (!$scene) return ['ok'=>false,'error'=>'scene_not_found'];
  $fields = [
    'scene_title'=>trim((string)($payload['scene_title'] ?? $scene['scene_title'] ?? '')),
    'scene_summary'=>trim((string)($payload['scene_summary'] ?? $scene['scene_summary'] ?? '')),
    'scene_prompt'=>trim((string)($payload['scene_prompt'] ?? $scene['scene_prompt'] ?? '')),
    'image_prompt'=>trim((string)($payload['image_prompt'] ?? $scene['image_prompt'] ?? '')),
    'dialog_text'=>trim((string)($payload['dialog_text'] ?? $scene['dialog_text'] ?? '')),
    'action_notes'=>trim((string)($payload['action_notes'] ?? $scene['action_notes'] ?? '')),
    'location_label'=>trim((string)($payload['location_label'] ?? $scene['location_label'] ?? '')),
    'time_of_day'=>trim((string)($payload['time_of_day'] ?? $scene['time_of_day'] ?? '')),
    'scene_status'=>trim((string)($payload['scene_status'] ?? $scene['scene_status'] ?? 'draft')) ?: 'draft',
  ];
  $ok = sf_admin_execute('UPDATE storyboard_scenes SET scene_title=?, scene_summary=?, scene_prompt=?, image_prompt=?, dialog_text=?, action_notes=?, location_label=?, time_of_day=?, scene_status=?, updated_at=NOW() WHERE id=?', [$fields['scene_title'],$fields['scene_summary'],$fields['scene_prompt'],$fields['image_prompt'],$fields['dialog_text'],$fields['action_notes'],$fields['location_label'],$fields['time_of_day'],$fields['scene_status'],$sceneId]);
  if ($ok) { sf_admin_execute('UPDATE storyboards SET updated_at = NOW() WHERE id = ?', [(int)$scene['storyboard_id']]); sf_admin_audit('update_storyboard_scene', 'storyboard_scene', $sceneId, null, $fields); }
  return ['ok'=>$ok,'scene_id'=>$sceneId];
}
function sf_sba_rewrite_system_prompt(): string { return 'You rewrite one scene inside a larger 9-scene storyboard. Return JSON only with this exact shape: {"scene_title":"","scene_summary":"","scene_prompt":"","image_prompt":"","dialog_text":"","action_notes":"","location_label":"","time_of_day":"","characters":["Character Name"]}. Preserve continuity with the surrounding scenes and keep character likeness notes consistent.'; }
function sf_sba_rewrite_user_prompt(array $scene, string $instruction = ''): string {
  return "Storyboard: " . (string)$scene['storyboard_title'] . "\nVisual style: " . (string)$scene['visual_style'] . "\nAspect ratio: " . (string)$scene['aspect_ratio'] . "\nScene number: " . (int)$scene['scene_number'] . "\nCurrent title: " . (string)$scene['scene_title'] . "\nCurrent summary: " . (string)$scene['scene_summary'] . "\nCurrent scene prompt: " . (string)$scene['scene_prompt'] . "\nCurrent image prompt: " . (string)$scene['image_prompt'] . "\nCurrent dialog: " . (string)$scene['dialog_text'] . "\nCurrent action notes: " . (string)$scene['action_notes'] . "\nRewrite instruction: " . trim($instruction ?: 'Rewrite this scene for stronger pacing, cleaner dialog, and better visual clarity.') . "\n\nStoryboard continuity:\n" . sf_sba_scene_context((int)$scene['storyboard_id'], (int)$scene['id']) . "\n\nCharacter consistency notes:\n" . sf_sba_character_consistency_prompt((int)$scene['storyboard_id'], (int)$scene['id']);
}
function sf_sba_parse_scene_json(string $text): array {
  $text = trim(preg_replace('/^```(?:json)?\s*/i', '', trim($text)));
  $text = preg_replace('/\s*```$/', '', $text);
  $json = json_decode($text, true);
  if (!is_array($json)) { $start = strpos($text, '{'); $end = strrpos($text, '}'); if ($start !== false && $end !== false && $end > $start) $json = json_decode(substr($text, $start, $end - $start + 1), true); }
  return is_array($json) ? ['ok'=>true,'data'=>$json] : ['ok'=>false,'error'=>'invalid_scene_json'];
}
function sf_sba_sync_scene_characters(int $storyboardId, int $sceneId, array $names): void {
  sf_admin_execute('DELETE FROM storyboard_scene_characters WHERE scene_id = ?', [$sceneId]);
  foreach ($names as $name) { $name = trim((string)$name); if ($name === '') continue; $characterId = sf_sbgen_find_or_create_character($storyboardId, ['name'=>$name,'role'=>'Character'], 99); if ($characterId > 0) sf_admin_execute('INSERT IGNORE INTO storyboard_scene_characters (storyboard_id, scene_id, character_id, presence_label) VALUES (?, ?, ?, ?)', [$storyboardId,$sceneId,$characterId,'in_scene']); }
}
function sf_sba_rewrite_scene(int $sceneId, string $instruction = ''): array {
  $scene = sf_sba_scene($sceneId); if (!$scene) return ['ok'=>false,'error'=>'scene_not_found'];
  $storyboard = sf_sba_storyboard((int)$scene['storyboard_id']); if (!$storyboard) return ['ok'=>false,'error'=>'storyboard_not_found'];
  $provider = sf_sbgen_default_provider($storyboard); if (!$provider) return ['ok'=>false,'error'=>'provider_missing'];
  if (($provider['status'] ?? '') !== 'active' || !in_array(($provider['key_status'] ?? ''), ['configured','connected'], true)) return ['ok'=>false,'error'=>'provider_not_active_or_configured'];
  $providerKey = (string)($provider['provider_key'] ?? 'chatgpt');
  $jobId = sf_sbgen_start_job((int)$scene['storyboard_id'], $sceneId, $providerKey, 'rewrite_scene', ['instruction'=>$instruction]);
  sf_admin_execute("UPDATE storyboard_scenes SET rewrite_status='queued', updated_at=NOW() WHERE id=?", [$sceneId]);
  $call = sf_sbgen_call_provider($provider, sf_sba_rewrite_system_prompt(), sf_sba_rewrite_user_prompt($scene, $instruction));
  if (!$call['ok']) { sf_admin_execute("UPDATE storyboard_scenes SET rewrite_status='failed', updated_at=NOW() WHERE id=?", [$sceneId]); sf_sbgen_finish_job($jobId, 'failed', [], $call['error']); sf_sbgen_log_usage($providerKey, (int)$scene['storyboard_id'], [], 'failed'); return ['ok'=>false,'error'=>$call['error'] ?: 'provider_error']; }
  $parsed = sf_sba_parse_scene_json($call['text']);
  if (!$parsed['ok']) { sf_admin_execute("UPDATE storyboard_scenes SET rewrite_status='failed', updated_at=NOW() WHERE id=?", [$sceneId]); sf_sbgen_finish_job($jobId, 'failed', ['raw_text'=>$call['text']], $parsed['error']); sf_sbgen_log_usage($providerKey, (int)$scene['storyboard_id'], $call['usage'], 'failed'); return ['ok'=>false,'error'=>$parsed['error']]; }
  $data = $parsed['data'];
  $save = sf_sba_update_scene($sceneId, ['scene_title'=>$data['scene_title'] ?? $scene['scene_title'], 'scene_summary'=>$data['scene_summary'] ?? $scene['scene_summary'], 'scene_prompt'=>$data['scene_prompt'] ?? $scene['scene_prompt'], 'image_prompt'=>$data['image_prompt'] ?? $scene['image_prompt'], 'dialog_text'=>$data['dialog_text'] ?? $scene['dialog_text'], 'action_notes'=>$data['action_notes'] ?? $scene['action_notes'], 'location_label'=>$data['location_label'] ?? $scene['location_label'], 'time_of_day'=>$data['time_of_day'] ?? $scene['time_of_day'], 'scene_status'=>'needs_review']);
  if (!empty($data['characters']) && is_array($data['characters'])) sf_sba_sync_scene_characters((int)$scene['storyboard_id'], $sceneId, $data['characters']);
  sf_admin_execute("UPDATE storyboard_scenes SET rewrite_status='rewritten', last_rewritten_at=NOW(), updated_at=NOW() WHERE id=?", [$sceneId]);
  sf_sbgen_finish_job($jobId, 'complete', $data); sf_sbgen_log_usage($providerKey, (int)$scene['storyboard_id'], $call['usage'], 'success');
  return ['ok'=>!empty($save['ok']),'scene_id'=>$sceneId];
}
function sf_sba_default_image_provider(array $storyboard): ?array { $key = trim((string)($storyboard['default_image_provider'] ?? '')); if ($key !== '') { $provider = sf_ai_provider($key); if ($provider) return $provider; } foreach (sf_ai_providers() as $provider) if (!empty($provider['is_default_image'])) return $provider; return null; }
function sf_sba_image_prompt(array $scene): string { return trim((string)($scene['image_prompt'] ?: $scene['scene_prompt'])) . "\n\nCharacter consistency notes:\n" . sf_sba_character_consistency_prompt((int)$scene['storyboard_id'], (int)$scene['id']) . "\n\nUse a cinematic storyboard frame, strong composition, production still quality, and match the storyboard visual style: " . (string)($scene['visual_style'] ?? 'cinematic realistic') . '. Do not add captions, subtitles, UI, watermarks, or text in the image.'; }
function sf_sba_store_generated_image(int $sceneId, string $base64, string $extension = 'png'): array {
  $assetRoot = realpath(__DIR__ . '/../assets'); if ($assetRoot === false) return ['ok'=>false,'error'=>'asset_root_missing'];
  $folder = 'images/uploads/storyboards/' . date('Y/m'); $targetDir = $assetRoot . '/' . $folder; if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true)) return ['ok'=>false,'error'=>'upload_folder_failed'];
  $extension = in_array($extension, ['png','webp','jpeg','jpg'], true) ? ($extension === 'jpg' ? 'jpeg' : $extension) : 'png';
  $filename = 'scene-' . $sceneId . '-' . substr(bin2hex(random_bytes(8)), 0, 12) . '.' . $extension;
  $bytes = base64_decode($base64, true); if ($bytes === false || strlen($bytes) < 100) return ['ok'=>false,'error'=>'invalid_image_data'];
  $path = $targetDir . '/' . $filename; if (file_put_contents($path, $bytes) === false) return ['ok'=>false,'error'=>'image_write_failed']; @chmod($path, 0644);
  $relativePath = $folder . '/' . $filename;
  $assetId = sf_admin_insert_media_asset(['title'=>'Storyboard Scene ' . $sceneId . ' Generated Image','file_path'=>$relativePath,'file_type'=>'image','alt_text'=>'Generated storyboard scene image','usage_key'=>'storyboard_scene_generated','original_filename'=>$filename,'mime_type'=>'image/' . $extension,'file_size_bytes'=>strlen($bytes),'checksum_sha256'=>hash_file('sha256', $path),'storage_disk'=>'local_assets','uploaded_by_user_id'=>sf_current_user_id()]);
  return $assetId > 0 ? ['ok'=>true,'asset_id'=>$assetId,'path'=>$relativePath] : ['ok'=>false,'error'=>'asset_record_failed'];
}
function sf_sba_generate_scene_image(int $sceneId): array {
  $scene = sf_sba_scene($sceneId); if (!$scene) return ['ok'=>false,'error'=>'scene_not_found'];
  $storyboard = sf_sba_storyboard((int)$scene['storyboard_id']); if (!$storyboard) return ['ok'=>false,'error'=>'storyboard_not_found'];
  $provider = sf_sba_default_image_provider($storyboard); if (!$provider) return ['ok'=>false,'error'=>'image_provider_missing'];
  if (($provider['provider_key'] ?? '') !== 'chatgpt') return ['ok'=>false,'error'=>'image_provider_not_supported_yet'];
  if (($provider['status'] ?? '') !== 'active' || !in_array(($provider['key_status'] ?? ''), ['configured','connected'], true)) return ['ok'=>false,'error'=>'image_provider_not_active_or_configured'];
  $secret = sf_ai_decrypt_secret($provider['encrypted_api_key'] ?? ''); if ($secret === '') return ['ok'=>false,'error'=>'image_provider_key_missing'];
  $providerKey = (string)$provider['provider_key']; $model = trim((string)($provider['image_model'] ?? '')) ?: 'gpt-image-1';
  $jobId = sf_sbgen_start_job((int)$scene['storyboard_id'], $sceneId, $providerKey, 'regenerate_scene_image', ['scene_id'=>$sceneId]);
  sf_admin_execute("UPDATE storyboard_scenes SET image_status='generating', updated_at=NOW() WHERE id=?", [$sceneId]);
  $payload = ['model'=>$model, 'prompt'=>sf_sba_image_prompt($scene), 'size'=>'1536x1024', 'quality'=>'medium', 'output_format'=>'png', 'n'=>1];
  $result = sf_sbgen_http_json('https://api.openai.com/v1/images/generations', ['Content-Type: application/json','Authorization: Bearer ' . $secret], $payload, max(30, (int)($provider['timeout_seconds'] ?? 90)), max(0, (int)($provider['max_retries'] ?? 1)));
  if (!$result['ok']) { sf_admin_execute("UPDATE storyboard_scenes SET image_status='failed', updated_at=NOW() WHERE id=?", [$sceneId]); sf_sbgen_finish_job($jobId, 'failed', [], $result['error']); sf_sbgen_log_usage($providerKey, (int)$scene['storyboard_id'], [], 'failed'); return ['ok'=>false,'error'=>$result['error'] ?: 'image_provider_error']; }
  $json = $result['json'] ?? []; $base64 = (string)($json['data'][0]['b64_json'] ?? '');
  $stored = sf_sba_store_generated_image($sceneId, $base64, 'png');
  if (!$stored['ok']) { sf_admin_execute("UPDATE storyboard_scenes SET image_status='failed', updated_at=NOW() WHERE id=?", [$sceneId]); sf_sbgen_finish_job($jobId, 'failed', $json, $stored['error']); return $stored; }
  sf_admin_execute("UPDATE storyboard_scenes SET generated_image_asset_id=?, image_status='generated', last_image_generated_at=NOW(), updated_at=NOW() WHERE id=?", [(int)$stored['asset_id'], $sceneId]);
  sf_sbgen_finish_job($jobId, 'complete', ['asset_id'=>$stored['asset_id'],'path'=>$stored['path']]);
  sf_sbgen_log_usage($providerKey, (int)$scene['storyboard_id'], $json['usage'] ?? [], 'success');
  return ['ok'=>true,'scene_id'=>$sceneId,'asset_id'=>(int)$stored['asset_id'],'path'=>$stored['path']];
}
function sf_sba_upload_scene_image(int $sceneId, string $field = 'scene_image'): array {
  $scene = sf_sba_scene($sceneId); if (!$scene) return ['ok'=>false,'error'=>'scene_not_found'];
  $upload = sf_admin_handle_upload($field, 'image', 'storyboard_scene_uploaded', 'Storyboard Scene ' . (int)$scene['scene_number'] . ' Upload', 'Uploaded storyboard scene image');
  if (empty($upload['ok'])) return ['ok'=>false,'error'=>$upload['message'] ?? 'upload_failed'];
  sf_admin_execute("UPDATE storyboard_scenes SET uploaded_image_asset_id=?, image_status='uploaded', updated_at=NOW() WHERE id=?", [(int)$upload['id'], $sceneId]);
  $jobId = sf_sbgen_start_job((int)$scene['storyboard_id'], $sceneId, 'manual_upload', 'upload_scene_image', ['asset_id'=>(int)$upload['id']]); sf_sbgen_finish_job($jobId, 'complete', ['asset_id'=>(int)$upload['id'],'path'=>$upload['path'] ?? '']);
  sf_admin_audit('upload_storyboard_scene_image', 'storyboard_scene', $sceneId, null, ['asset_id'=>$upload['id']]);
  return ['ok'=>true,'scene_id'=>$sceneId,'asset_id'=>(int)$upload['id'],'path'=>$upload['path'] ?? ''];
}
function sf_sba_retry_scene_job(int $jobId): array { $job = sf_admin_fetch_one('SELECT * FROM storyboard_jobs WHERE id=? LIMIT 1', [$jobId]); if (!$job) return ['ok'=>false,'error'=>'job_not_found']; $type = (string)$job['job_type']; if ($type === 'rewrite_scene') return sf_sba_rewrite_scene((int)$job['scene_id']); if (in_array($type, ['generate_scene_image','regenerate_scene_image'], true)) return sf_sba_generate_scene_image((int)$job['scene_id']); return ['ok'=>false,'error'=>'retry_not_supported_for_job_type']; }
?>
