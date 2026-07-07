<?php
require_once __DIR__ . '/storyboard_character_actions.php';

function sf_sbq_ready(): bool { return sf_sbc_ready() && sf_admin_table_exists('storyboard_jobs'); }
function sf_sbq_h($value): string { return sf_storyboard_h($value); }
function sf_sbq_job(int $jobId): ?array { if (!sf_sbq_ready() || $jobId <= 0) return null; return sf_admin_fetch_one('SELECT * FROM storyboard_jobs WHERE id = ? LIMIT 1', [$jobId]); }
function sf_sbq_storyboard_scenes(int $storyboardId): array { if (!sf_sbq_ready()) return []; return sf_admin_fetch_all('SELECT * FROM storyboard_scenes WHERE storyboard_id = ? ORDER BY scene_number ASC', [$storyboardId]); }
function sf_sbq_scene_payload(array $scene): array { return ['scene_id'=>(int)$scene['id'], 'scene_number'=>(int)$scene['scene_number'], 'image_prompt'=>(string)($scene['image_prompt'] ?: $scene['scene_prompt']), 'queued_by'=>sf_current_user_id()]; }
function sf_sbq_enqueue_scene_image(int $sceneId, string $jobType = 'generate_scene_image'): array {
  $scene = sf_sba_scene($sceneId); if (!$scene) return ['ok'=>false,'error'=>'scene_not_found'];
  $storyboard = sf_sba_storyboard((int)$scene['storyboard_id']); if (!$storyboard) return ['ok'=>false,'error'=>'storyboard_not_found'];
  $provider = sf_sba_default_image_provider($storyboard); $providerKey = (string)($provider['provider_key'] ?? 'chatgpt');
  $jobId = sf_sbgen_start_job((int)$scene['storyboard_id'], $sceneId, $providerKey, $jobType, sf_sbq_scene_payload($scene));
  if ($jobId <= 0) return ['ok'=>false,'error'=>'job_create_failed'];
  sf_admin_execute("UPDATE storyboard_jobs SET job_status='queued', started_at=NULL, run_after=NOW(), updated_at=NOW() WHERE id=?", [$jobId]);
  sf_admin_execute("UPDATE storyboard_scenes SET image_status='queued', updated_at=NOW() WHERE id=?", [$sceneId]);
  return ['ok'=>true,'job_id'=>$jobId,'scene_id'=>$sceneId];
}
function sf_sbq_enqueue_bulk_images(int $storyboardId): array {
  if (!sf_sbq_ready()) return ['ok'=>false,'error'=>'queue_not_ready'];
  $storyboard = sf_sba_storyboard($storyboardId); if (!$storyboard) return ['ok'=>false,'error'=>'storyboard_not_found'];
  $scenes = sf_sbq_storyboard_scenes($storyboardId); $queued = 0; $failed = 0; $jobs = [];
  foreach ($scenes as $scene) { $result = sf_sbq_enqueue_scene_image((int)$scene['id'], 'generate_scene_image'); if (!empty($result['ok'])) { $queued++; $jobs[] = (int)$result['job_id']; } else $failed++; }
  sf_admin_audit('enqueue_storyboard_image_batch', 'storyboard', $storyboardId, null, ['queued'=>$queued,'failed'=>$failed,'jobs'=>$jobs]);
  return ['ok'=>$queued > 0 && $failed === 0, 'queued'=>$queued, 'failed'=>$failed, 'job_ids'=>$jobs];
}
function sf_sbq_process_next_image_job(int $storyboardId = 0): array {
  if (!sf_sbq_ready()) return ['ok'=>false,'error'=>'queue_not_ready'];
  $params = [];
  $where = "job_status = 'queued' AND job_type IN ('generate_scene_image','regenerate_scene_image') AND (run_after IS NULL OR run_after <= NOW())";
  if ($storyboardId > 0) { $where .= ' AND storyboard_id = ?'; $params[] = $storyboardId; }
  $job = sf_admin_fetch_one('SELECT * FROM storyboard_jobs WHERE ' . $where . ' ORDER BY COALESCE(run_after, created_at) ASC, id ASC LIMIT 1', $params);
  if (!$job) return ['ok'=>true,'processed'=>0,'message'=>'no_queued_jobs'];
  sf_admin_execute("UPDATE storyboard_jobs SET job_status='running', attempts=attempts+1, started_at=NOW(), updated_at=NOW() WHERE id=?", [(int)$job['id']]);
  $result = sf_sba_generate_scene_image((int)$job['scene_id']);
  if (!empty($result['ok'])) {
    sf_admin_execute("UPDATE storyboard_jobs SET job_status='complete', output_json=?, completed_at=NOW(), updated_at=NOW() WHERE id=?", [json_encode($result, JSON_UNESCAPED_SLASHES), (int)$job['id']]);
    return ['ok'=>true,'processed'=>1,'job_id'=>(int)$job['id'],'result'=>$result];
  }
  $attempts = (int)($job['attempts'] ?? 0) + 1; $max = max(1, (int)($job['max_attempts'] ?? 2));
  $status = $attempts >= $max ? 'failed' : 'queued';
  sf_admin_execute('UPDATE storyboard_jobs SET job_status=?, error_message=?, run_after=DATE_ADD(NOW(), INTERVAL 2 MINUTE), updated_at=NOW() WHERE id=?', [$status, $result['error'] ?? 'unknown_error', (int)$job['id']]);
  return ['ok'=>false,'processed'=>1,'job_id'=>(int)$job['id'],'error'=>$result['error'] ?? 'unknown_error'];
}
function sf_sbq_cancel_job(int $jobId): array { $job = sf_sbq_job($jobId); if (!$job) return ['ok'=>false,'error'=>'job_not_found']; if (in_array(($job['job_status'] ?? ''), ['complete','failed','canceled'], true)) return ['ok'=>false,'error'=>'job_not_cancelable']; $ok = sf_admin_execute("UPDATE storyboard_jobs SET job_status='canceled', completed_at=NOW(), updated_at=NOW() WHERE id=?", [$jobId]); return ['ok'=>$ok,'job_id'=>$jobId]; }
function sf_sbq_job_summary(int $storyboardId): array {
  if (!sf_sbq_ready()) return ['total'=>0,'queued'=>0,'running'=>0,'complete'=>0,'failed'=>0,'canceled'=>0];
  $rows = sf_admin_fetch_all('SELECT job_status, COUNT(*) AS total FROM storyboard_jobs WHERE storyboard_id = ? GROUP BY job_status', [$storyboardId]);
  $summary = ['total'=>0,'queued'=>0,'running'=>0,'complete'=>0,'failed'=>0,'canceled'=>0];
  foreach ($rows as $row) { $status = (string)$row['job_status']; $count = (int)$row['total']; $summary['total'] += $count; if (isset($summary[$status])) $summary[$status] = $count; }
  return $summary;
}
function sf_sbq_reference_gallery(int $storyboardId): array {
  if (!sf_sbc_ready()) return [];
  return sf_admin_fetch_all('SELECT r.*, c.character_name, c.role_label, ma.file_path AS media_path FROM storyboard_character_references r INNER JOIN storyboard_characters c ON c.id = r.character_id LEFT JOIN media_assets ma ON ma.id = r.media_asset_id WHERE r.storyboard_id = ? ORDER BY r.is_primary DESC, c.character_order ASC, r.created_at DESC', [$storyboardId]);
}
function sf_sbq_export_data(int $storyboardId): array {
  $storyboard = sf_sba_storyboard($storyboardId); if (!$storyboard) return [];
  $characters = sf_admin_fetch_all('SELECT * FROM storyboard_characters WHERE storyboard_id = ? ORDER BY character_order ASC, id ASC', [$storyboardId]);
  $scenes = sf_admin_fetch_all('SELECT * FROM storyboard_scenes WHERE storyboard_id = ? ORDER BY scene_number ASC', [$storyboardId]);
  $links = sf_admin_fetch_all('SELECT l.scene_id, c.character_name FROM storyboard_scene_characters l INNER JOIN storyboard_characters c ON c.id = l.character_id WHERE l.storyboard_id = ? ORDER BY c.character_order ASC, c.id ASC', [$storyboardId]);
  $byScene = []; foreach ($links as $link) $byScene[(int)$link['scene_id']][] = (string)$link['character_name'];
  foreach ($scenes as &$scene) $scene['characters'] = $byScene[(int)$scene['id']] ?? [];
  return ['storyboard'=>$storyboard,'characters'=>$characters,'scenes'=>$scenes];
}
function sf_sbq_export_screenplay_text(int $storyboardId): string {
  $data = sf_sbq_export_data($storyboardId); if (!$data) return '';
  $b = $data['storyboard']; $out = [];
  $out[] = strtoupper((string)$b['title']); $out[] = str_repeat('=', strlen((string)$b['title'])); $out[] = '';
  if (!empty($b['logline'])) { $out[] = 'LOGLINE'; $out[] = (string)$b['logline']; $out[] = ''; }
  $out[] = 'GENRE: ' . (string)($b['genre'] ?? ''); $out[] = 'TONE: ' . (string)($b['tone'] ?? ''); $out[] = 'VISUAL STYLE: ' . (string)($b['visual_style'] ?? ''); $out[] = '';
  $out[] = 'CHARACTERS'; $out[] = '----------'; foreach ($data['characters'] as $c) $out[] = (string)$c['character_name'] . ' — ' . (string)$c['role_label'] . ' — ' . (string)$c['appearance_notes']; $out[] = '';
  foreach ($data['scenes'] as $s) { $out[] = 'SCENE ' . (int)$s['scene_number'] . ': ' . strtoupper((string)$s['scene_title']); $out[] = 'Location: ' . (string)$s['location_label'] . ' | Time: ' . (string)$s['time_of_day']; $out[] = 'Characters: ' . implode(', ', $s['characters']); $out[] = ''; $out[] = (string)$s['action_notes']; $out[] = ''; $out[] = 'DIALOG'; $out[] = (string)$s['dialog_text']; $out[] = ''; }
  return implode("\n", $out);
}
function sf_sbq_export_shotlist_csv(int $storyboardId): string {
  $data = sf_sbq_export_data($storyboardId); if (!$data) return '';
  $fh = fopen('php://temp', 'r+'); fputcsv($fh, ['Scene #','Title','Location','Time','Characters','Scene Prompt','Image Prompt','Dialog','Action Notes']);
  foreach ($data['scenes'] as $s) fputcsv($fh, [(int)$s['scene_number'], (string)$s['scene_title'], (string)$s['location_label'], (string)$s['time_of_day'], implode(', ', $s['characters']), (string)$s['scene_prompt'], (string)$s['image_prompt'], (string)$s['dialog_text'], (string)$s['action_notes']]);
  rewind($fh); $csv = stream_get_contents($fh); fclose($fh); return (string)$csv;
}
function sf_sbq_export_json(int $storyboardId): string { $data = sf_sbq_export_data($storyboardId); return $data ? json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : ''; }
function sf_sbq_safe_filename(string $title, string $suffix): string { $base = preg_replace('/[^a-z0-9]+/i', '-', strtolower($title)) ?: 'storyboard'; return trim($base, '-') . '-' . $suffix; }
?>
